<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Display the public landing page.
     */
    public function __invoke(): View
    {
        return view('public.home', [
            'productCategories' => $this->productCategories(),
            'reasons' => $this->reasons(),
            'steps' => $this->steps(),
            'galleryItems' => $this->galleryItems(),
        ]);
    }

    /**
     * @return array<int, array{name: string, description: string, accent: string}>
     */
    private function productCategories(): array
    {
        return [
            ['name' => 'Custom Name Keychain', 'description' => 'Keychain nama untuk hadiah, keluarga, kelas dan komuniti.', 'accent' => 'bg-teal-100 text-teal-700'],
            ['name' => 'Clicker Name', 'description' => 'Aksesori nama yang ringan, kemas dan mudah dibawa.', 'accent' => 'bg-amber-100 text-amber-700'],
            ['name' => 'Miniature', 'description' => 'Mini model untuk koleksi, display meja dan acara khas.', 'accent' => 'bg-rose-100 text-rose-700'],
            ['name' => 'Gift Box', 'description' => 'Kotak hadiah kreatif dengan sentuhan personal.', 'accent' => 'bg-cyan-100 text-cyan-700'],
            ['name' => 'Corporate Gift', 'description' => 'Cenderamata syarikat yang praktikal dan berbeza.', 'accent' => 'bg-zinc-100 text-zinc-700'],
            ['name' => 'Custom 3D Print Request', 'description' => 'Kongsi idea anda, kami bantu cadangkan cetakan yang sesuai.', 'accent' => 'bg-emerald-100 text-emerald-700'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function reasons(): array
    {
        return [
            'Custom design',
            'Affordable pricing',
            'Quality printing',
            'Suitable for gifts and events',
            'Friendly support',
        ];
    }

    /**
     * @return array<int, array{title: string, description: string}>
     */
    private function steps(): array
    {
        return [
            ['title' => 'Send your idea', 'description' => 'Share reference, size, colour or purpose through WhatsApp.'],
            ['title' => 'We prepare design/quotation', 'description' => 'We review the request and suggest the best printing approach.'],
            ['title' => 'Confirm order', 'description' => 'Approve the design, quantity and price before production starts.'],
            ['title' => 'We print and deliver', 'description' => 'Your custom item is printed, checked and prepared for delivery.'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function galleryItems(): array
    {
        return [
            'Name keychain samples',
            'Miniature display pieces',
            'Gift-ready products',
            'Corporate event items',
            'Custom colour options',
            'Special request prints',
        ];
    }
}
