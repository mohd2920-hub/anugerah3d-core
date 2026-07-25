@extends('admin.layouts.app')

@section('title', $agents->total().' Agents | Anugerah3D Admin')

@section('page_title', $agents->total().' Agents')

@section('content')
    @php
        $formatAmount = static fn ($value): string => number_format((float) $value, 2);
        $formatPercent = static fn ($value): string => number_format((float) $value, 1);
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($loginInfo)
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-semibold text-blue-900">Login info ready</h2>
                        <textarea id="agent-login-info-message" readonly class="mt-3 min-h-28 w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">{{ $loginInfo['message'] ?? '' }}</textarea>
                    </div>
                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col">
                        <button type="button" data-copy-target="agent-login-info-message" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">
                            Copy login info
                        </button>
                        @if ($loginInfo['whatsapp_url'] ?? null)
                            <a href="{{ $loginInfo['whatsapp_url'] }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-blue-200 bg-white px-4 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">
                                Open WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($newRegistrationCount > 0)
            <a href="{{ route('admin.agents.index', ['status' => \App\Models\Agent::StatusPending]) }}" class="flex items-center gap-4 rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm transition hover:bg-blue-100">
                <span class="grid h-11 w-11 flex-none place-items-center rounded-full bg-blue-600 font-bold text-white">{{ $newRegistrationCount }}</span>
                <span class="min-w-0 flex-1"><span class="block font-semibold text-blue-950">Pending agent {{ \Illuminate\Support\Str::plural('registration', $newRegistrationCount) }}</span><span class="mt-0.5 block text-sm text-blue-700">Review details and assign commission before approval.</span></span>
                <span class="text-sm font-semibold text-blue-700">Review now →</span>
            </a>
        @endif

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="agent-search-toggle" data-expanded="false" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-300 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8]" aria-expanded="false" aria-controls="agent-search-form-mobile" aria-label="Toggle search">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </button>
                <a href="{{ route('admin.agents.create') }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-[#1a73e8] px-3 text-xs font-semibold uppercase tracking-wide text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    <span>Add Agent</span>
                </a>
            </div>

            <form id="agent-search-form-mobile" method="GET" action="{{ route('admin.agents.index') }}" class="{{ request('search') || request('status') ? 'mt-3 grid gap-3 lg:grid-cols-[minmax(0,1fr)_180px_auto_auto]' : 'mt-3 hidden gap-3 lg:grid-cols-[minmax(0,1fr)_180px_auto_auto]' }}">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by login ID, name, email, IC or phone..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                <select name="status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                    Search
                </button>
                @if (request('search') || request('status'))
                    <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Clear
                    </a>
                @else
                    <span class="hidden lg:block"></span>
                @endif
            </form>
        </div>

        <div class="hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
            <table class="admin-data-table w-full text-xs">
                <colgroup>
                    <col style="width: 8%;">
                    <col style="width: 18%;">
                    <col style="width: 18%;">
                    <col style="width: 13%;">
                    <col style="width: 13%;">
                    <col style="width: 17%;">
                    <col style="width: 9%;">
                    <col style="width: 12%;">
                </colgroup>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="rounded-tl-lg px-3 py-3 text-left font-semibold text-slate-700">Photo</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Agent</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Contact</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Location</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Sales</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Team</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-700">Status</th>
                        <th class="rounded-tr-lg px-3 py-3 text-right font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($agents as $agent)
                        @php
                            $statusClass = match ($agent->agt_status) {
                                'pending' => 'bg-blue-100 text-blue-700',
                                'new' => 'bg-blue-100 text-blue-700',
                        'active' => 'bg-green-100 text-green-700',
                                'blocked' => 'bg-red-100 text-red-700',
                                'suspended' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                            $profileUrl = $agent->profile_picture ? (filter_var($agent->profile_picture, FILTER_VALIDATE_URL) ? $agent->profile_picture : asset($agent->profile_picture)) : null;
                            $whatsappUrl = $agent->whatsappUrl();
                        @endphp
                        <tr class="cursor-pointer transition hover:bg-slate-50 focus-within:bg-slate-50" data-agent-link="{{ route('admin.agents.show', $agent) }}" tabindex="0" role="link" aria-label="View {{ $agent->agt_name }} details">
                            <td class="px-3 py-3">
                                @if ($profileUrl)
                                    <div class="relative h-11 w-11 overflow-hidden rounded-md border border-slate-200 bg-slate-100">
                                        <img src="{{ $profileUrl }}" alt="{{ $agent->agt_name }}" loading="lazy" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');">
                                        <div class="hidden h-full w-full items-center justify-center text-[10px] font-semibold text-slate-400">{{ $agent->initials() }}</div>
                                    </div>
                                @else
                                    <div class="flex h-11 w-11 items-center justify-center rounded-md border border-slate-200 bg-blue-50 text-xs font-bold text-[#1a73e8]">{{ $agent->initials() }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="truncate font-mono text-[0.72rem] font-semibold text-slate-900" title="{{ $agent->login_id }}">{{ $agent->login_id }}</div>
                                <div class="mt-1 truncate text-sm font-medium text-slate-900" title="{{ $agent->agt_name }}">{{ $agent->agt_name }}</div>
                                <div class="mt-1 truncate text-[0.7rem] text-blue-600">Referrer: {{ $agent->referrer?->agt_name ?? '-' }}</div>

                                @if ($agent->id_number)
                                    <div class="mt-1 truncate text-[0.72rem] text-slate-500" title="{{ $agent->id_number }}">{{ $agent->id_number }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="truncate text-slate-700" title="{{ $agent->email }}">{{ $agent->email }}</div>
                                <div class="mt-1">
                                    @if ($whatsappUrl)
                                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="font-medium text-green-700 transition hover:text-green-800 hover:underline">
                                            {{ $agent->phone_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">No phone</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                <div class="truncate font-medium text-slate-900" title="{{ $agent->city ?: '-' }}">{{ $agent->city ?: '-' }}</div>
                                <div class="mt-1 truncate text-[0.72rem] text-slate-500" title="{{ $agent->state ?: '-' }}">{{ $agent->state ?: '-' }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-900">RM {{ $formatAmount($agent->total_sale) }}</div>
                                <div class="mt-1 text-[0.72rem] text-slate-500">{{ $formatPercent($agent->discount_percentage) }}% discount</div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="text-[0.72rem] text-slate-600">Tier1 : {{ number_format((int) ($agent->team_tier1_count ?? 0)) }} members</div>
                                <div class="mt-1 text-[0.72rem] text-slate-600">Tier2 : {{ number_format((int) ($agent->team_tier2_count ?? 0)) }} members</div>
                                <div class="mt-1 text-[0.72rem] font-semibold text-emerald-700">Bonus : RM {{ $formatAmount($agent->team_bonus_estimate ?? 0) }}</div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex min-w-20 items-center justify-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusOptions[$agent->agt_status] ?? ucfirst($agent->agt_status) }}
                                </span>
                            </td>
                            <td class="relative px-3 py-3 text-right">
                                <div class="relative inline-flex" data-action-menu>
                                    <button type="button" data-action-menu-button class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2" aria-haspopup="menu" aria-expanded="false">
                                        Actions
                                    </button>
                                    <div data-action-menu-panel class="absolute right-0 top-full z-30 mt-2 hidden w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl">
                                        <a href="{{ route('admin.agents.edit', $agent) }}" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                            Edit
                                        </a>
                                        <button type="button" data-copy-text="{{ $agent->loginInfoMessage() }}" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                            Copy login info
                                        </button>
                                        @if ($whatsappUrl)
                                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="block rounded-md px-3 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50">
                                                WhatsApp
                                            </a>
                                        @endif
                                        <button type="button" data-action="{{ route('admin.agents.destroy', $agent) }}" data-name="{{ $agent->agt_name }}" onclick="openDeleteModal(this)" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center">
                                <p class="text-slate-600">No agents found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            @forelse($agents as $agent)
                @php
                    $statusClass = match ($agent->agt_status) {
                        'pending' => 'bg-blue-100 text-blue-700',
                                'new' => 'bg-blue-100 text-blue-700',
                                'active' => 'bg-green-100 text-green-700',
                        'blocked' => 'bg-red-100 text-red-700',
                        'suspended' => 'bg-amber-100 text-amber-700',
                        default => 'bg-slate-100 text-slate-600',
                    };
                    $profileUrl = $agent->profile_picture ? (filter_var($agent->profile_picture, FILTER_VALIDATE_URL) ? $agent->profile_picture : asset($agent->profile_picture)) : null;
                    $whatsappUrl = $agent->whatsappUrl();
                @endphp
                <article class="cursor-pointer rounded-lg border border-slate-200 bg-white p-4 shadow-sm" data-agent-link="{{ route('admin.agents.show', $agent) }}" tabindex="0" role="link" aria-label="View {{ $agent->agt_name }} details">
                    <div class="flex items-start gap-3">
                        @if ($profileUrl)
                            <div class="relative h-16 w-16 flex-none overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                <img src="{{ $profileUrl }}" alt="{{ $agent->agt_name }}" loading="lazy" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');">
                                <div class="hidden h-full w-full items-center justify-center text-xs font-semibold text-slate-400">{{ $agent->initials() }}</div>
                            </div>
                        @else
                            <div class="flex h-16 w-16 flex-none items-center justify-center rounded-lg border border-slate-200 bg-blue-50 text-sm font-bold text-[#1a73e8]">{{ $agent->initials() }}</div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="break-all font-mono text-[0.72rem] font-semibold text-slate-500">{{ $agent->login_id }}</div>
                            <h2 class="mt-0.5 break-words text-base font-semibold text-slate-950">{{ $agent->agt_name }}</h2>
                            <p class="mt-1 text-xs font-medium text-blue-600">Referrer: {{ $agent->referrer?->agt_name ?? '-' }}</p>

                            <span class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                {{ $statusOptions[$agent->agt_status] ?? ucfirst($agent->agt_status) }}
                            </span>
                        </div>

                        <div class="relative flex-none" data-action-menu>
                            <button type="button" data-action-menu-button class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2" aria-haspopup="menu" aria-expanded="false">
                                Actions
                            </button>
                            <div data-action-menu-panel class="absolute right-0 top-full z-30 mt-2 hidden w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl">
                                <a href="{{ route('admin.agents.edit', $agent) }}" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                    Edit
                                </a>
                                <button type="button" data-copy-text="{{ $agent->loginInfoMessage() }}" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                    Copy login info
                                </button>
                                @if ($whatsappUrl)
                                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="block rounded-md px-3 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50">
                                        WhatsApp
                                    </a>
                                @endif
                                <button type="button" data-action="{{ route('admin.agents.destroy', $agent) }}" data-name="{{ $agent->agt_name }}" onclick="openDeleteModal(this)" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Contact</dt>
                            <dd class="mt-1 break-words font-semibold text-slate-900">{{ $agent->email }}</dd>
                            <dd class="mt-0.5 text-xs">
                                @if ($whatsappUrl)
                                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="font-medium text-green-700 hover:underline">{{ $agent->phone_number }}</a>
                                @else
                                    <span class="text-slate-500">No phone</span>
                                @endif
                            </dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Location</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $agent->city ?: '-' }}</dd>
                            <dd class="mt-0.5 text-xs text-slate-500">{{ $agent->state ?: '-' }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Sales</dt>
                            <dd class="mt-1 font-semibold text-slate-900">RM {{ $formatAmount($agent->total_sale) }}</dd>
                            <dd class="mt-0.5 text-xs text-slate-500">{{ $formatPercent($agent->discount_percentage) }}% discount</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Team</dt>
                            <dd class="mt-1 text-xs text-slate-700">Tier1 : {{ number_format((int) ($agent->team_tier1_count ?? 0)) }} members</dd>
                            <dd class="mt-0.5 text-xs text-slate-700">Tier2 : {{ number_format((int) ($agent->team_tier2_count ?? 0)) }} members</dd>
                            <dd class="mt-0.5 text-xs font-semibold text-emerald-700">Bonus : RM {{ $formatAmount($agent->team_bonus_estimate ?? 0) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Last login</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $agent->last_login_at?->format('d M Y') ?: '-' }}</dd>
                            <dd class="mt-0.5 text-xs text-slate-500">{{ $agent->last_login_ip ?: '-' }}</dd>
                        </div>
                    </dl>
                </article>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-6 text-center text-slate-600 shadow-sm">
                    No agents found.
                </div>
            @endforelse
        </div>

        <div id="delete-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 py-6">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl ring-1 ring-slate-900/5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Confirm delete</h2>
                        <p class="mt-1 text-sm text-slate-600">To confirm, enter your admin password. This will permanently delete the selected agent and cannot be undone.</p>
                    </div>
                    <button type="button" onclick="closeDeleteModal()" class="rounded-full border border-slate-200 bg-white p-2 text-slate-600 transition hover:bg-slate-50">
                        &times;
                    </button>
                </div>

                <form method="POST" action="" class="mt-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_action" id="delete_action" value="{{ old('delete_action') }}">
                    <input type="hidden" name="delete_agent_name" id="delete_agent_name" value="{{ old('delete_agent_name') }}">

                    <p class="text-sm text-slate-700">Delete agent: <span data-agent-name class="font-medium text-slate-900"></span></p>

                    <div>
                        <label for="delete_password" class="mb-2 block text-sm font-medium text-slate-700">Admin Password</label>
                        <input id="delete_password" name="delete_password" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @if ($errors->has('delete_password'))
                            <span class="mt-1 block text-sm text-red-600">{{ $errors->first('delete_password') }}</span>
                        @endif
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeDeleteModal()" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                            Delete agent
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex justify-center">
            {{ $agents->links('pagination::tailwind') }}
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('delete-confirm-modal');

            function closeActionMenus() {
                document.querySelectorAll('[data-action-menu-panel]').forEach(function (panel) {
                    panel.classList.add('hidden');
                });

                document.querySelectorAll('[data-action-menu-button]').forEach(function (button) {
                    button.setAttribute('aria-expanded', 'false');
                });
            }

            function fallbackCopy(text) {
                const area = document.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                document.body.removeChild(area);
            }

            function copyText(text, button) {
                const done = function () {
                    if (! button) {
                        return;
                    }

                    const original = button.textContent;
                    button.textContent = 'Copied';
                    window.setTimeout(function () {
                        button.textContent = original;
                    }, 1400);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {
                        fallbackCopy(text);
                        done();
                    });
                    return;
                }

                fallbackCopy(text);
                done();
            }

            function setDeleteModal(action, name) {
                const form = modal.querySelector('form');
                const agentName = modal.querySelector('[data-agent-name]');
                const deleteActionInput = modal.querySelector('[name="delete_action"]');
                const deleteAgentNameInput = modal.querySelector('[name="delete_agent_name"]');

                form.action = action;
                agentName.textContent = name;
                deleteActionInput.value = action;
                deleteAgentNameInput.value = name;
            }

            window.openDeleteModal = function (button) {
                closeActionMenus();
                setDeleteModal(button.dataset.action, button.dataset.name);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.querySelector('[name="delete_password"]').value = '';
                modal.querySelector('[name="delete_password"]').focus();
            };

            window.closeDeleteModal = function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('[data-action-menu-button]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.stopPropagation();

                    const menu = button.closest('[data-action-menu]');
                    const panel = menu.querySelector('[data-action-menu-panel]');
                    const shouldOpen = panel.classList.contains('hidden');

                    closeActionMenus();

                    if (shouldOpen) {
                        panel.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            document.querySelectorAll('[data-copy-text]').forEach(function (button) {
                button.addEventListener('click', function () {
                    closeActionMenus();
                    copyText(button.dataset.copyText, button);
                });
            });

            document.querySelectorAll('[data-copy-target]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.dataset.copyTarget);

                    if (target) {
                        copyText(target.value, button);
                    }
                });
            });

            const searchToggle = document.getElementById('agent-search-toggle');
            const mobileSearchForm = document.getElementById('agent-search-form-mobile');

            if (searchToggle && mobileSearchForm) {
                const isOpen = ! mobileSearchForm.classList.contains('hidden');

                searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                searchToggle.dataset.expanded = isOpen ? 'true' : 'false';

                searchToggle.addEventListener('click', function () {
                    const shouldOpen = mobileSearchForm.classList.contains('hidden');

                    mobileSearchForm.classList.toggle('hidden', ! shouldOpen);
                    mobileSearchForm.classList.toggle('grid', shouldOpen);
                    searchToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                    searchToggle.dataset.expanded = shouldOpen ? 'true' : 'false';

                    if (shouldOpen) {
                        const firstInput = mobileSearchForm.querySelector('input[name="search"]');

                        if (firstInput) {
                            firstInput.focus();
                        }
                    }
                });
            }

            document.querySelectorAll('[data-agent-link]').forEach(function (container) {
                container.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, input, select, textarea, [data-action-menu]')) {
                        return;
                    }

                    const targetUrl = container.dataset.agentLink;

                    if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                });

                container.addEventListener('keydown', function (event) {
                    if (event.target.closest('a, button, input, select, textarea, [data-action-menu]')) {
                        return;
                    }

                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();

                        const targetUrl = container.dataset.agentLink;

                        if (targetUrl) {
                            window.location.href = targetUrl;
                        }
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (! event.target.closest('[data-action-menu]')) {
                    closeActionMenus();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeActionMenus();
                    window.closeDeleteModal();
                }
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    window.closeDeleteModal();
                }
            });

            const persistedDeleteAction = @json(old('delete_action', ''));
            const persistedDeleteAgentName = @json(old('delete_agent_name', ''));

            if (persistedDeleteAction && persistedDeleteAgentName) {
                setDeleteModal(persistedDeleteAction, persistedDeleteAgentName);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.querySelector('[name="delete_password"]').focus();
            }
        })();
    </script>
@endsection
