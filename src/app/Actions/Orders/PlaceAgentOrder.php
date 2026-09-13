<?php

namespace App\Actions\Orders;

use App\Mail\Admin\AgentOrderPlacedMail;
use App\Mail\Admin\CustomerOrderPlacedMail;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\CustomerOrder;
use App\Models\Order;
use App\Models\Product;
use App\Support\AdminAccess;
use App\Support\AdminActivity;
use App\Support\AgentOrderDiscount;
use App\Support\CasingStock;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlaceAgentOrder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Agent $agent, array $data, Request $request): Order
    {
        return $this->place($agent, $data, $request, false);
    }

    public function handleCustomer(Agent $agent, array $data, Request $request): CustomerOrder
    {
        return $this->place($agent, $data, $request, true);
    }

    private function place(Agent $agent, array $data, Request $request, bool $customer): Order
    {
        $model = $customer ? CustomerOrder::class : Order::class;
        $existingOrder = $model::query()
            ->whereBelongsTo($agent)
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        if ($existingOrder !== null) {
            return $this->notifyAdmins($existingOrder);
        }

        $paymentProofPaths = $this->storePaymentProofs($data['payment_proofs'] ?? [], $customer);

        try {
            $order = DB::transaction(function () use ($agent, $data, $request, $paymentProofPaths, $customer, $model): Order {
                $items = collect($data['items']);
                $products = Product::query()
                    ->visibleToAgents()
                    ->whereKey($items->pluck('product_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $clickerPricesByProduct = DB::table('product_clicker_prices')
                    ->whereIn('product_id', $products->modelKeys())
                    ->get(['product_id', 'character_count', 'price_rm'])
                    ->groupBy('product_id')
                    ->map(fn (Collection $rows): array => $rows
                        ->mapWithKeys(fn ($row): array => [
                            (int) $row->character_count => (int) round((float) $row->price_rm * 100),
                        ])
                        ->all());

                $clickerImageIds = $items
                    ->flatMap(fn (mixed $item): array => [
                        data_get($item, 'clicker_casing_image_id'),
                        data_get($item, 'clicker_huruf_image_id'),
                    ])
                    ->filter(fn (mixed $id): bool => is_numeric($id) && (int) $id > 0);
                $clickerImagesById = DB::table('product_clicker_images')
                    ->whereIn('id', $clickerImageIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $casingQuantities = app(CasingStock::class)->quantities($clickerImageIds->all());
                $this->ensureEveryProductExists($items, $products);
                $this->ensureStockIsAvailable($items, $products);

                $grossSubtotalCents = 0;
                $subtotalCents = 0;
                $totalUnits = 0;
                $pendingItems = [];
                $orderItems = [];

                foreach ($items as $index => $item) {
                    /** @var Product $product */
                    $product = $products->get($item['product_id']);
                    $quantity = (int) $item['quantity'];
                    $casingStock = CasingStock::enabled($product);
                    $casing = $clickerImagesById->get((int) ($item['clicker_casing_image_id'] ?? 0));
                    $isPreorder = $casingStock ? (int) ($casingQuantities[$casing->id ?? 0][(int) ($item['clicker_character_count'] ?? 0)] ?? 0) <= 0 : $product->prd_balance <= 0;
                    $isClicker = ($product->product_type ?? 'standard') === 'clicker';
                    if (($product->discontinued_at ?? null) && $isPreorder) {
                        throw ValidationException::withMessages(['items' => "{$product->prd_name} telah dihentikan. Pilihan ini sudah habis stok dan tidak boleh dibuat pre-order."]);
                    }

                    $clickerCharacterCount = null;
                    $clickerCharacters = [];
                    $clickerImagePaths = [];

                    if ($isClicker) {
                        foreach (['casing', 'huruf'] as $imageType) {
                            $field = "clicker_{$imageType}_image_id";
                            $imageId = (int) ($item[$field] ?? 0);
                            $image = $clickerImagesById->get($imageId);

                            if (
                                ! $image
                                || (int) $image->product_id !== $product->getKey()
                                || $image->image_type !== $imageType
                            ) {
                                throw ValidationException::withMessages([
                                    "items.{$index}.{$field}" => "Choose a valid {$imageType} image for {$product->prd_name}.",
                                ]);
                            }

                            $clickerImagePaths[$imageType] = (string) $image->image_path;
                        }

                        $clickerCharacterCount = (int) ($item['clicker_character_count'] ?? 0);
                        $clickerCharacters = collect($item['clicker_characters'] ?? [])
                            ->map(fn (mixed $character): string => trim((string) $character))
                            ->filter()
                            ->values()
                            ->all();

                        $clickerUnitPriceCents = data_get(
                            $clickerPricesByProduct,
                            $product->getKey().'.'.$clickerCharacterCount,
                        );

                        if (! is_int($clickerUnitPriceCents)) {
                            throw ValidationException::withMessages([
                                "items.{$index}.clicker_character_count" => "Price for {$clickerCharacterCount} characters is not configured for {$product->prd_name}.",
                            ]);
                        }

                        $sellingPriceCents = $clickerUnitPriceCents;
                    } else {
                        $sellingPriceCents = (int) round((float) $product->price_selling * 100);
                    }

                    $grossSubtotalCents += $sellingPriceCents * $quantity;
                    $totalUnits += $quantity;
                    $pendingItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'reserved_quantity' => $isPreorder ? 0 : $quantity,
                        'is_preorder' => $isPreorder,
                        'selling_price_cents' => $sellingPriceCents,
                        'is_clicker' => $isClicker,
                        'casing_id' => $casingStock ? (int) $casing->id : null,
                        'clicker_character_count' => $clickerCharacterCount,
                        'clicker_characters' => $clickerCharacters,
                        'clicker_casing_image_path' => $clickerImagePaths['casing'] ?? null,
                        'clicker_huruf_image_path' => $clickerImagePaths['huruf'] ?? null,
                    ];
                }

                $discountPercentage = $customer ? 0.0 : AgentOrderDiscount::resolvePercentage(
                    $grossSubtotalCents,
                    (float) $agent->discount_percentage,
                );
                $discountTenths = (int) round($discountPercentage * 10);
                $deliveryFeeCents = (string) $data['fulfilment_method'] === 'delivery'
                    ? AgentOrderDiscount::DELIVERY_FEE_CENTS
                    : 0;

                foreach ($pendingItems as $pendingItem) {
                    /** @var Product $product */
                    $product = $pendingItem['product'];
                    $quantity = (int) $pendingItem['quantity'];
                    $sellingPriceCents = (int) $pendingItem['selling_price_cents'];
                    $unitPriceCents = (int) round($sellingPriceCents * (1000 - $discountTenths) / 1000);
                    $lineTotalCents = $unitPriceCents * $quantity;

                    $subtotalCents += $lineTotalCents;
                    $orderItem = [
                        'product_id' => $product->getKey(),
                        'product_code' => $product->prd_code,
                        'product_name' => $product->prd_name,
                        'quantity' => $quantity,
                        'reserved_quantity' => (int) $pendingItem['reserved_quantity'],
                        'unit_selling_price' => $this->money($sellingPriceCents),
                        'discount_percentage' => number_format($discountPercentage, 1, '.', ''),
                        'unit_price' => $this->money($unitPriceCents),
                        'line_total' => $this->money($lineTotalCents),
                        'is_preorder' => (bool) $pendingItem['is_preorder'],
                    ];

                    if ((bool) ($pendingItem['is_clicker'] ?? false)) {
                        $orderItem['clicker_character_count'] = (int) ($pendingItem['clicker_character_count'] ?? 0);
                        $orderItem['clicker_characters'] = $pendingItem['clicker_characters'] ?? [];
                        $orderItem['clicker_casing_image_path'] = $pendingItem['clicker_casing_image_path'] ?? null;
                        $orderItem['clicker_huruf_image_path'] = $pendingItem['clicker_huruf_image_path'] ?? null;
                    }

                    if ($pendingItem['casing_id'] !== null) {
                        $orderItem['clicker_casing_image_id'] = $pendingItem['casing_id'];
                    }
                    $orderItems[] = $orderItem;

                    if (! $pendingItem['is_preorder']) {
                        if ($pendingItem['casing_id'] !== null) {
                            app(CasingStock::class)->move($product, $pendingItem['casing_id'], (int) $pendingItem['clicker_character_count'], -$quantity);
                        } else {
                            $product->decrement('prd_balance', $quantity);
                        }
                    }
                }

                if (($customer || isset($data['expected_total'])) && (int) round((float) $data['expected_total'] * 100) !== $subtotalCents + $deliveryFeeCents) {
                    $exception = ValidationException::withMessages(['total' => 'Harga, diskaun atau caj telah berubah. Muat semula katalog dan semak jumlah sebelum membuat pesanan.']);
                    $exception->response = response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);

                    throw $exception;
                }

                $order = $model::query()->create([
                    ...($customer ? [
                        'tracking_token' => (string) Str::uuid(),
                        'commission_rate' => 25,
                        'commission_amount' => $this->money((int) round($subtotalCents * 0.25)),
                    ] : []),
                    'idempotency_key' => $data['idempotency_key'],
                    'agent_id' => $agent->getKey(),
                    'fulfilment_method' => $data['fulfilment_method'],
                    'recipient_name' => $data['recipient_name'],
                    'phone_number' => $data['phone_number'],
                    'delivery_address' => $data['fulfilment_method'] === 'delivery' ? $data['delivery_address'] : null,
                    'notes' => $data['notes'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'payment_proof_paths' => $paymentProofPaths,
                    'subtotal' => $this->money($subtotalCents),
                    'delivery_fee' => $deliveryFeeCents > 0 ? $this->money($deliveryFeeCents) : null,
                    'total_amount' => $this->money($subtotalCents + $deliveryFeeCents),
                    'total_units' => $totalUnits,
                    'placed_at' => now(),
                ]);

                $order->update([
                    'order_number' => ($customer ? 'A3DC-' : 'A3D-').$order->placed_at->format('ymd').'-'.str_pad((string) $order->getKey(), 5, '0', STR_PAD_LEFT),
                ]);
                $order->items()->createMany($orderItems);
                $adminRecipientCount = AdminUser::query()->active()->count();

                AdminActivity::record(
                    request: $request,
                    event: $customer ? 'customer.order.created' : 'agent.order.created',
                    description: $customer ? "Customer order {$order->order_number} referred by {$agent->agt_name}." : "Agent {$agent->agt_name} placed order {$order->order_number}.",
                    properties: [
                        'page' => 'Agent Orders',
                        'agent_id' => $agent->getKey(),
                        'actor_name' => $agent->agt_name,
                        'email' => $agent->email,
                        'order_id' => $order->getKey(),
                        'order_number' => $order->order_number,
                        'total_amount' => $order->total_amount,
                        'total_units' => $order->total_units,
                        'payment_method' => $order->payment_method,
                        'payment_proof_count' => count($paymentProofPaths),
                        'fulfilment_method' => $order->fulfilment_method,
                        'admin_recipient_count' => $adminRecipientCount,
                    ],
                );

                return $order->load('items');
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteStoredPaymentProofs($paymentProofPaths, $customer);

            throw $exception;
        }

        return $this->notifyAdmins($order);
    }

    /** @param array<int, UploadedFile>|UploadedFile|null $files */
    private function storePaymentProofs(array|UploadedFile|null $files, bool $customer = false): array
    {
        $uploads = $files instanceof UploadedFile
            ? [$files]
            : array_values(array_filter(is_array($files) ? $files : [], fn ($file) => $file instanceof UploadedFile));

        $paths = [];
        try {
            foreach (array_slice($uploads, 0, 5) as $upload) {
                $paths[] = $this->storePaymentProof($upload, $customer);
            }
        } catch (Throwable $exception) {
            $this->deleteStoredPaymentProofs($paths, $customer);
            throw $exception;
        }

        return $paths;
    }

    private function storePaymentProof(UploadedFile $file, bool $customer = false): string
    {
        if ($customer) {
            $path = $file->store('customer-payment-proofs', 'local');
            if (! $path) {
                throw ValidationException::withMessages(['payment_proofs' => 'Bukti pembayaran tidak dapat disimpan. Sila cuba lagi.']);
            }

            return $path;
        }

        $extension = $file->guessExtension() ?: 'jpg';
        $filename = 'proof-'.Str::uuid().'.'.$extension;
        $relativePath = 'orders/payment-proofs/'.$filename;

        Storage::disk($this->pictureDisk())->put($relativePath, File::get($file->getRealPath()), 'public');

        return $relativePath;
    }

    /** @param array<int, string> $paths */
    private function deleteStoredPaymentProofs(array $paths, bool $customer = false): void
    {
        $disk = Storage::disk($customer ? 'local' : $this->pictureDisk());

        foreach (array_filter($paths, fn ($path) => is_string($path) && $path !== '') as $path) {
            $disk->delete($path);

            if (! $customer && File::exists(public_path($path))) {
                File::delete(public_path($path));
            }
        }
    }

    private function pictureDisk(): string
    {
        $default = (string) config('filesystems.default', 'public');

        if ($default === 's3' && class_exists('League\\Flysystem\\AwsS3V3\\PortableVisibilityConverter')) {
            return 's3';
        }

        return 'public';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     */
    private function ensureEveryProductExists(Collection $items, Collection $products): void
    {
        $selectedProductCount = $items
            ->pluck('product_id')
            ->map(fn (mixed $productId): int => (int) $productId)
            ->unique()
            ->count();

        if ($selectedProductCount !== $products->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more selected products are no longer available.',
            ]);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     */
    private function ensureStockIsAvailable(Collection $items, Collection $products): void
    {
        $quantitiesByProduct = $items
            ->groupBy(fn (array $item): int => (int) $item['product_id'])
            ->map(fn (Collection $productItems): int => $productItems->sum(
                fn (array $item): int => (int) $item['quantity'],
            ));

        foreach ($quantitiesByProduct as $productId => $quantity) {
            $product = $products->get((int) $productId);

            if (! $product instanceof Product || CasingStock::enabled($product) || $product->prd_balance <= 0) {
                continue;
            }

            if ($quantity > $product->prd_balance) {
                throw ValidationException::withMessages([
                    'items' => "Only {$product->prd_balance} units of {$product->prd_name} are available across all cart lines.",
                ]);
            }
        }
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function notifyAdmins(Order $order): Order
    {
        if ($order->admin_notification_sent_at !== null) {
            return $order;
        }

        $adminEmails = $order instanceof CustomerOrder
            ? AdminUser::query()->active()->with('accessRoles')->get()->filter(fn ($admin) => AdminAccess::allows($admin, 'customer-orders.view'))->pluck('email')
            : AdminUser::query()->active()->orderBy('id')->pluck('email');

        if ($adminEmails->isEmpty()) {
            return $order;
        }

        if ($order instanceof CustomerOrder) {
            Mail::to($adminEmails->all())->send(new CustomerOrderPlacedMail($order->id));
            $order->forceFill(['admin_notification_sent_at' => now()])->save();

            return $order;
        }

        Mail::to($adminEmails->all())->send(new AgentOrderPlacedMail(
            orderId: $order->getKey(),
            orderNumber: $order->order_number,
        ));

        $order->forceFill(['admin_notification_sent_at' => now()])->save();

        return $order;
    }
}
