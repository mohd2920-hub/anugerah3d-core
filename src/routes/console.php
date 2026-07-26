<?php

use App\Actions\WeeklyClosing\RunWeeklyClosing;
use App\Actions\WeeklyClosing\SendWeeklyClosingEmails;
use App\Mail\Admin\WeeklyClosingTestMail;
use App\Mail\Agent\WeeklyClosingAgentSampleMail;
use App\Models\Agent;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\WeeklyClosing;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('weekly-closing:test-email {email=juhari@cloudgarden.my} {--name=Syida} {--mode=last}', function () {
    $tz = 'Asia/Kuala_Lumpur';
    $now = CarbonImmutable::now($tz);
    $mode = strtolower((string) $this->option('mode'));

    if ($mode === 'current') {
        $periodStart = $now->startOfWeek(CarbonImmutable::MONDAY);
        $periodEnd = $now;
        $periodLabel = $periodStart->format('d M Y').' - '.$periodEnd->format('d M Y').' (live)';
    } else {
        $periodEnd = $now->startOfWeek(CarbonImmutable::MONDAY);
        $periodStart = $periodEnd->subWeek();
        $periodLabel = $periodStart->format('d M Y').' - '.$periodEnd->subSecond()->format('d M Y');
    }

    $agents = Agent::query()
        ->select(['id', 'agt_name', 'email', 'referrer_id', 'agt_status', 'tier1_percentage', 'tier2_percentage', 'created_at'])
        ->get();

    $activeAgents = $agents->where('agt_status', Agent::StatusActive)->values();

    $orders = Order::query()
        ->whereBetween('placed_at', [$periodStart, $periodEnd])
        ->where('status', '!=', Order::StatusCancelled)
        ->get(['id', 'agent_id', 'total_amount', 'total_units']);

    $posSales = PosSale::query()
        ->whereBetween('sold_at', [$periodStart, $periodEnd])
        ->get(['id', 'sales_agent_id', 'total_amount']);

    $ordersByAgent = $orders->groupBy('agent_id');
    $posSalesByAgent = $posSales->groupBy('sales_agent_id');
    $referralsByReferrer = $agents->groupBy('referrer_id');

    $agentRows = [];
    $pendingPayments = [];
    $totalPayableBonus = 0.0;

    foreach ($activeAgents as $agent) {
        $tier1Agents = collect($referralsByReferrer->get($agent->id, []))->values();
        $tier1AgentIds = $tier1Agents->pluck('id')->all();

        $tier2Agents = collect($tier1AgentIds)
            ->flatMap(fn (int $tier1Id) => $referralsByReferrer->get($tier1Id, []))
            ->values();
        $tier2AgentIds = $tier2Agents->pluck('id')->all();

        $personalOrders = collect($ordersByAgent->get($agent->id, []));
        $personalOrderAmount = (float) $personalOrders->sum('total_amount');
        $personalOrderCount = $personalOrders->count();

        $tier1Orders = collect($orders)->whereIn('agent_id', $tier1AgentIds)->values();
        $tier2Orders = collect($orders)->whereIn('agent_id', $tier2AgentIds)->values();

        $tier1OrderAmount = (float) $tier1Orders->sum('total_amount');
        $tier2OrderAmount = (float) $tier2Orders->sum('total_amount');
        $tier1OrdersCount = $tier1Orders->count();
        $tier2OrdersCount = $tier2Orders->count();

        $tier1Rate = (float) ($agent->tier1_percentage ?? 7);
        $tier2Rate = (float) ($agent->tier2_percentage ?? 3);
        $tier1Bonus = round($tier1OrderAmount * $tier1Rate / 100, 2);
        $tier2Bonus = round($tier2OrderAmount * $tier2Rate / 100, 2);
        $totalBonus = round($tier1Bonus + $tier2Bonus, 2);

        $newAgentsRegistered = $tier1Agents
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $personalPosSales = collect($posSalesByAgent->get($agent->id, []));
        $posSalesAmount = (float) $personalPosSales->sum('total_amount');
        $posSalesCount = $personalPosSales->count();

        $row = [
            'agent_name' => (string) $agent->agt_name,
            'email' => (string) ($agent->email ?? ''),
            'personal_orders' => $personalOrderCount,
            'personal_order_amount' => $personalOrderAmount,
            'new_agents_registered' => $newAgentsRegistered,
            'tier1_agents_total' => count($tier1AgentIds),
            'tier2_agents_total' => count($tier2AgentIds),
            'tier1_orders_count' => $tier1OrdersCount,
            'tier2_orders_count' => $tier2OrdersCount,
            'tier1_order_amount' => $tier1OrderAmount,
            'tier2_order_amount' => $tier2OrderAmount,
            'tier1_bonus' => $tier1Bonus,
            'tier2_bonus' => $tier2Bonus,
            'total_bonus' => $totalBonus,
            'pos_sales_count' => $posSalesCount,
            'pos_sales_amount' => $posSalesAmount,
        ];

        $agentRows[] = $row;

        if ($totalBonus > 0) {
            $pendingPayments[] = [
                'agent_name' => $row['agent_name'],
                'email' => $row['email'],
                'tier1_bonus' => $tier1Bonus,
                'tier2_bonus' => $tier2Bonus,
                'total_bonus' => $totalBonus,
            ];
        }

        $totalPayableBonus += $totalBonus;
    }

    usort($agentRows, fn (array $a, array $b): int => ($b['total_bonus'] <=> $a['total_bonus']) ?: strcmp($a['agent_name'], $b['agent_name']));
    usort($pendingPayments, fn (array $a, array $b): int => ($b['total_bonus'] <=> $a['total_bonus']) ?: strcmp($a['agent_name'], $b['agent_name']));

    $payload = [
        'period_label' => $periodLabel,
        'period_start' => $periodStart->format('Y-m-d H:i:s'),
        'period_end' => $periodEnd->format('Y-m-d H:i:s'),
        'generated_at' => $now->format('Y-m-d H:i:s'),
        'admin' => [
            'total_orders' => $orders->count(),
            'total_order_amount' => (float) $orders->sum('total_amount'),
            'total_order_units' => (int) $orders->sum('total_units'),
            'total_pos_sales' => $posSales->count(),
            'total_pos_amount' => (float) $posSales->sum('total_amount'),
            'total_payable_bonus' => round($totalPayableBonus, 2),
            'pending_payout_count' => count($pendingPayments),
        ],
        'pending_payments' => $pendingPayments,
        'agents' => $agentRows,
    ];

    $snapshotPath = 'weekly-closing/test/'.CarbonImmutable::now($tz)->format('Ymd_His').'_closing_snapshot.json';
    Storage::disk('local')->put($snapshotPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $payload['snapshot_path'] = 'storage/app/private/'.$snapshotPath;

    $email = (string) $this->argument('email');
    $name = trim((string) $this->option('name'));
    $subjectPrefix = '['.($name !== '' ? $name : 'Syida').']';

    Mail::to($email)->send(new WeeklyClosingTestMail(
        payload: $payload,
        subjectPrefix: $subjectPrefix,
    ));

    $this->info('Weekly closing test email sent to '.$email);
    $this->line('Mode: '.$mode);
    $this->line('Subject prefix: '.$subjectPrefix);
    $this->line('Snapshot: '.$payload['snapshot_path']);
})->purpose('Generate weekly closing snapshot and send a consolidated testing email');

Artisan::command('weekly-closing:test-agent-email {email=juhari@cloudgarden.my} {--agent=Azir} {--mode=current}', function () {
    $tz = 'Asia/Kuala_Lumpur';
    $now = CarbonImmutable::now($tz);
    $mode = strtolower((string) $this->option('mode'));

    if ($mode === 'last') {
        $periodEnd = $now->startOfWeek(CarbonImmutable::MONDAY);
        $periodStart = $periodEnd->subWeek();
        $periodLabel = $periodStart->format('d M Y').' - '.$periodEnd->subSecond()->format('d M Y');
    } else {
        $periodStart = $now->startOfWeek(CarbonImmutable::MONDAY);
        $periodEnd = $now;
        $periodLabel = $periodStart->format('d M Y').' - '.$periodEnd->format('d M Y').' (live)';
    }

    $agentKeyword = trim((string) $this->option('agent'));
    $agent = Agent::query()
        ->with(['referrer:id,agt_name,login_id,email,phone_number,profile_picture'])
        ->where('agt_status', Agent::StatusActive)
        ->where(function ($query) use ($agentKeyword): void {
            $query->where('agt_name', 'like', '%'.$agentKeyword.'%')
                ->orWhere('email', 'like', '%'.$agentKeyword.'%')
                ->orWhere('login_id', 'like', '%'.$agentKeyword.'%');
        })
        ->orderBy('agt_name')
        ->firstOrFail(['id', 'agt_name', 'login_id', 'email', 'phone_number', 'referrer_id', 'tier1_percentage', 'tier2_percentage', 'profile_picture']);

    $resolveImageUrl = static function (?string $path): ?string {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (File::exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    };

    $allAgents = Agent::query()->get(['id', 'referrer_id', 'created_at']);
    $referralsByReferrer = $allAgents->groupBy('referrer_id');

    $tier1Agents = collect($referralsByReferrer->get($agent->id, []))->values();
    $tier1AgentIds = $tier1Agents->pluck('id')->all();

    $tier2Agents = collect($tier1AgentIds)
        ->flatMap(fn (int $tier1Id) => $referralsByReferrer->get($tier1Id, []))
        ->values();
    $tier2AgentIds = $tier2Agents->pluck('id')->all();

    $orders = Order::query()
        ->whereBetween('placed_at', [$periodStart, $periodEnd])
        ->where('status', '!=', Order::StatusCancelled)
        ->get(['id', 'agent_id', 'total_amount']);

    $personalOrders = $orders->where('agent_id', $agent->id)->values();
    $tier1Orders = $orders->whereIn('agent_id', $tier1AgentIds)->values();
    $tier2Orders = $orders->whereIn('agent_id', $tier2AgentIds)->values();

    $personalOrderAmount = (float) $personalOrders->sum('total_amount');
    $tier1OrderAmount = (float) $tier1Orders->sum('total_amount');
    $tier2OrderAmount = (float) $tier2Orders->sum('total_amount');

    $tier1Rate = (float) ($agent->tier1_percentage ?? 7);
    $tier2Rate = (float) ($agent->tier2_percentage ?? 3);
    $tier1Bonus = round($tier1OrderAmount * $tier1Rate / 100, 2);
    $tier2Bonus = round($tier2OrderAmount * $tier2Rate / 100, 2);
    $totalBonus = round($tier1Bonus + $tier2Bonus, 2);

    $newAgentsRegistered = $tier1Agents
        ->whereBetween('created_at', [$periodStart, $periodEnd])
        ->count();

    $posSales = PosSale::query()
        ->where('sales_agent_id', $agent->id)
        ->whereBetween('sold_at', [$periodStart, $periodEnd])
        ->get(['id', 'total_amount']);

    $nameParts = preg_split('/\s+/', trim((string) $agent->agt_name)) ?: [];
    $firstName = $nameParts[0] ?? 'Agent';
    $subjectPrefix = '['.$firstName.']';

    $payload = [
        'brand' => [
            'logo_url' => asset('images/anugerah3d-logo.png'),
        ],
        'period_label' => $periodLabel,
        'period_start' => $periodStart->format('Y-m-d H:i:s'),
        'period_end' => $periodEnd->format('Y-m-d H:i:s'),
        'generated_at' => $now->format('Y-m-d H:i:s'),
        'sample_recipient' => (string) $this->argument('email'),
        'agent' => [
            'name' => (string) $agent->agt_name,
            'email' => (string) ($agent->email ?? ''),
            'login_id' => (string) ($agent->login_id ?? ''),
            'thumb_url' => $resolveImageUrl($agent->profile_picture),
        ],
        'bonus' => [
            'tier1_bonus' => $tier1Bonus,
            'tier2_bonus' => $tier2Bonus,
            'total_bonus' => $totalBonus,
            'payout_status' => $totalBonus > 0 ? 'Pending' : 'No payout',
        ],
        'personal' => [
            'orders_count' => $personalOrders->count(),
            'orders_amount' => $personalOrderAmount,
        ],
        'team' => [
            'new_agents_registered' => $newAgentsRegistered,
            'tier1_agents_total' => count($tier1AgentIds),
            'tier2_agents_total' => count($tier2AgentIds),
            'tier1_orders_count' => $tier1Orders->count(),
            'tier2_orders_count' => $tier2Orders->count(),
            'tier1_orders_amount' => $tier1OrderAmount,
            'tier2_orders_amount' => $tier2OrderAmount,
        ],
        'pos' => [
            'sales_count' => $posSales->count(),
            'sales_amount' => (float) $posSales->sum('total_amount'),
        ],
        'referrer' => [
            'exists' => $agent->referrer !== null,
            'name' => (string) ($agent->referrer?->agt_name ?? ''),
            'email' => (string) ($agent->referrer?->email ?? ''),
            'phone' => (string) ($agent->referrer?->phone_number ?? ''),
            'login_id' => (string) ($agent->referrer?->login_id ?? ''),
            'thumb_url' => $resolveImageUrl($agent->referrer?->profile_picture),
            'whatsapp_url' => $agent->referrer?->whatsappUrl('Hi '.$agent->referrer?->agt_name.', saya perlukan bantuan tentang weekly closing.'),
        ],
    ];

    Mail::to((string) $this->argument('email'))->send(new WeeklyClosingAgentSampleMail(
        payload: $payload,
        subjectPrefix: $subjectPrefix,
    ));

    $this->info('Weekly closing sample agent email sent to '.(string) $this->argument('email'));
    $this->line('Agent: '.$agent->agt_name);
    $this->line('Subject prefix: '.$subjectPrefix);
    $this->line('Mode: '.$mode);
})->purpose('Send weekly closing sample email for one agent to a single testing recipient');

Artisan::command('weekly-closing:run {--force}', function () {
    $closing = app(RunWeeklyClosing::class)->handle(
        runAt: CarbonImmutable::now('Asia/Kuala_Lumpur'),
        force: (bool) $this->option('force'),
    );

    $this->info('Weekly closing completed for '.$closing->week_key);
    $this->line('Period: '.$closing->period_start->format('Y-m-d H:i:s').' -> '.$closing->period_end->format('Y-m-d H:i:s'));
    $this->line('Total agents: '.number_format((int) $closing->total_agents));
    $this->line('Total payable bonus: RM '.number_format((float) $closing->total_payable_bonus, 2));
    $this->line('Backup: '.((string) $closing->backup_path));
})->purpose('Run weekly closing snapshot generation for the previous week');

Artisan::command('weekly-closing:send-emails {--week=}', function () {
    $weekKey = trim((string) $this->option('week'));
    $closing = $weekKey !== ''
        ? WeeklyClosing::query()->where('week_key', $weekKey)->firstOrFail()
        : WeeklyClosing::query()->latest('period_end')->firstOrFail();

    $result = app(SendWeeklyClosingEmails::class)->handle($closing);

    $this->info('Weekly closing emails dispatched for '.$closing->week_key);
    $this->line('Agent emails sent: '.number_format($result['agent_emails_sent']));
    $this->line('Admin emails sent: '.number_format($result['admin_emails_sent']));
})->purpose('Send weekly closing summary emails to all agents and admin');

Schedule::command('weekly-closing:run')
    ->weeklyOn(1, '00:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('weekly-closing:send-emails')
    ->weeklyOn(1, '00:05')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping()
    ->onOneServer();
