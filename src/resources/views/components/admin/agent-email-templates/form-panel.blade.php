@php
    $isEdit = $template->exists;
    $selectedAgentIds = collect(old("agent_ids", $selectedAgentIds))
        ->map(static fn ($id): int => (int) $id)
        ->filter(static fn (int $id): bool => $id > 0)
        ->values()
        ->all();
    $agentPickerOptions = $agents->map(static function ($agent): array {
        return [
            'id' => (int) $agent->id,
            'name' => $agent->agt_name,
            'login_id' => $agent->login_id,
            'email' => $agent->email ?: 'No email address',
            'status' => $agent->agt_status,
            'search' => \Illuminate\Support\Str::lower(trim(implode(' ', array_filter([
                $agent->agt_name,
                $agent->login_id,
                $agent->email,
                $agent->agt_status,
            ])))),
        ];
    })->values();
@endphp

<form method="POST" action="{{ $isEdit ? route("admin.agent-email-templates.update", $template) : route("admin.agent-email-templates.store") }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method("PUT")
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <label for="name" class="text-sm font-semibold text-slate-900">Template name</label>
            <input id="name" type="text" name="name" value="{{ old("name", $template->name) }}" placeholder="Example: Promo Merdeka 2026" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            @error("name")
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="mt-4">
                <p class="text-sm font-semibold text-slate-900">Recipients</p>
                <p class="mt-1 text-sm text-slate-500">Saving this template will not send any email. Delivery only happens when you press <strong>Send Email</strong>.</p>

                <div class="mt-3 grid gap-2">
                    @foreach ($recipientScopeOptions as $value => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition hover:border-blue-200 hover:bg-blue-50/40">
                            <input type="radio" name="recipient_scope" value="{{ $value }}" {{ old("recipient_scope", $template->recipient_scope ?: \App\Models\AgentEmailTemplate::RecipientAllAgents) === $value ? "checked" : "" }} class="mt-1 h-4 w-4 border-slate-300 text-[#1a73e8] focus:ring-[#1a73e8]">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900">{{ $label }}</span>
                                <span class="mt-1 block text-sm text-slate-500">
                                    {{ $value === \App\Models\AgentEmailTemplate::RecipientAllAgents ? "Send to every agent with a valid email address." : "Choose exactly which agents should receive this email." }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error("recipient_scope")
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div id="selected-agent-panel" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm {{ old("recipient_scope", $template->recipient_scope ?: \App\Models\AgentEmailTemplate::RecipientAllAgents) === \App\Models\AgentEmailTemplate::RecipientSelectedAgents ? "" : "hidden" }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Selected agents</h2>
                    <p class="mt-1 text-sm text-slate-500">Search agent, pick from top 10 results, then add more if needed.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $agents->count() }} available</span>
            </div>

            <div class="mt-3" data-agent-picker data-agents='@json($agentPickerOptions)' data-selected='@json($selectedAgentIds)'>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input type="search" data-agent-picker-search placeholder="Search agent name, login ID or email" autocomplete="off" class="w-full rounded-lg border border-slate-300 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-[#1a73e8] focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50/70 p-2.5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Top 10 results</p>
                        <p class="text-xs text-slate-400">Click a name to add</p>
                    </div>
                    <div data-agent-picker-results class="mt-2 space-y-1.5"></div>
                    <p data-agent-picker-results-empty class="hidden rounded-lg border border-dashed border-slate-300 bg-white px-3 py-4 text-center text-sm text-slate-500">No matching agent found.</p>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">Selected list</h3>
                        <span data-agent-picker-count class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">0 selected</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Only agents in this list will receive the email when you press send.</p>
                    <div data-agent-picker-selected class="mt-2 space-y-1.5"></div>
                    <p data-agent-picker-selected-empty class="hidden rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-sm text-slate-500">No agent selected yet.</p>
                </div>

                <div data-agent-picker-inputs></div>
            </div>

            @error("agent_ids")
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error("agent_ids.*")
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <label for="subject" class="text-sm font-semibold text-slate-900">Subject</label>
        <input id="subject" type="text" name="subject" value="{{ old("subject", $template->subject) }}" placeholder="Example: Pengumuman terbaru Anugerah3D" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
        @error("subject")
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <label for="body" class="mt-4 block text-sm font-semibold text-slate-900">Body</label>
        <textarea id="body" name="body" rows="12" placeholder="Tulis kandungan email di sini..." class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm leading-7 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">{{ old("body", $template->body) }}</textarea>
        @error("body")
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-3 rounded-lg border border-blue-100 bg-blue-50 p-3.5 text-sm text-blue-900 sm:flex-row sm:items-center sm:justify-between">
        <p>Email akan menggunakan header standard Anugerah3D dan hanya dihantar selepas anda tekan butang <strong>Send Email</strong>.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route("admin.agent-email-templates.index") }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-blue-200 bg-white px-4 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                Back
            </a>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">
                Save Template
            </button>
        </div>
    </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var radios = document.querySelectorAll("input[name=recipient_scope]");
        var selectedAgentPanel = document.getElementById("selected-agent-panel");
        var picker = document.querySelector("[data-agent-picker]");

        if (! radios.length || ! selectedAgentPanel || ! picker) {
            return;
        }

        var searchInput = picker.querySelector("[data-agent-picker-search]");
        var resultsContainer = picker.querySelector("[data-agent-picker-results]");
        var resultsEmpty = picker.querySelector("[data-agent-picker-results-empty]");
        var selectedContainer = picker.querySelector("[data-agent-picker-selected]");
        var selectedEmpty = picker.querySelector("[data-agent-picker-selected-empty]");
        var hiddenInputs = picker.querySelector("[data-agent-picker-inputs]");
        var selectedCount = picker.querySelector("[data-agent-picker-count]");
        var agents = JSON.parse(picker.dataset.agents || "[]");
        var selectedIds = JSON.parse(picker.dataset.selected || "[]")
            .map(function (id) { return Number(id); })
            .filter(function (id) { return id > 0; });

        function syncSelectedAgentPanel() {
            var checked = document.querySelector("input[name=recipient_scope]:checked");
            var shouldShow = checked && checked.value === "{{ \App\Models\AgentEmailTemplate::RecipientSelectedAgents }}";

            selectedAgentPanel.classList.toggle("hidden", ! shouldShow);
        }

        function selectedAgents() {
            return selectedIds
                .map(function (id) {
                    return agents.find(function (agent) {
                        return Number(agent.id) === Number(id);
                    });
                })
                .filter(Boolean);
        }

        function updateHiddenInputs() {
            hiddenInputs.innerHTML = selectedIds.map(function (id) {
                return "<input type=\"hidden\" name=\"agent_ids[]\" value=\"" + id + "\">";
            }).join("");
        }

        function escapeHtml(value) {
            return String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function updateSelectedList() {
            var selected = selectedAgents();

            selectedCount.textContent = selected.length + " selected";
            selectedEmpty.classList.toggle("hidden", selected.length !== 0);

            selectedContainer.innerHTML = selected.map(function (agent) {
                var summary = [agent.name, agent.email].filter(Boolean).join(", ");

                return ""
                    + '<div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">'
                    + '<div class="min-w-0 flex-1 overflow-hidden">'
                    + '<span class="block truncate text-sm text-slate-700" title="' + escapeHtml(summary) + '">' + escapeHtml(summary) + '</span>'
                    + '</div>'
                    + '<button type="button" data-remove-agent-id="' + agent.id + '" class="inline-flex h-7 flex-none items-center justify-center rounded-full border border-red-200 bg-red-50 px-2.5 text-xs font-semibold text-red-600 transition hover:bg-red-100" aria-label="Remove ' + escapeHtml(agent.name) + '">Remove</button>'
                    + '</div>';
            }).join("");
        }

        function renderResults() {
            var query = (searchInput.value || "").trim().toLowerCase();
            var matches = agents
                .filter(function (agent) {
                    return selectedIds.indexOf(Number(agent.id)) === -1
                        && (agent.search || "").includes(query);
                })
                .slice(0, 10);

            resultsContainer.innerHTML = matches.map(function (agent) {
                var summary = [agent.name, agent.email].filter(Boolean).join(", ");

                return ""
                    + '<button type="button" data-add-agent-id="' + agent.id + '" class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-blue-200 hover:bg-blue-50">'
                    + '<span class="min-w-0 flex-1 overflow-hidden">'
                    + '<span class="block truncate text-sm text-slate-700" title="' + escapeHtml(summary) + '">' + escapeHtml(summary) + '</span>'
                    + '</span>'
                    + '<span class="inline-flex flex-none rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Add</span>'
                    + '</button>';
            }).join("");

            resultsEmpty.classList.toggle("hidden", matches.length !== 0);
        }

        function syncPicker() {
            updateHiddenInputs();
            updateSelectedList();
            renderResults();
        }

        radios.forEach(function (radio) {
            radio.addEventListener("change", syncSelectedAgentPanel);
        });

        searchInput.addEventListener("input", renderResults);
        searchInput.addEventListener("focus", renderResults);

        resultsContainer.addEventListener("click", function (event) {
            var button = event.target.closest("[data-add-agent-id]");

            if (! button) {
                return;
            }

            var agentId = Number(button.dataset.addAgentId);

            if (selectedIds.indexOf(agentId) === -1) {
                selectedIds.push(agentId);
            }

            searchInput.value = "";
            syncPicker();
            searchInput.focus();
        });

        selectedContainer.addEventListener("click", function (event) {
            var button = event.target.closest("[data-remove-agent-id]");

            if (! button) {
                return;
            }

            var agentId = Number(button.dataset.removeAgentId);
            selectedIds = selectedIds.filter(function (id) {
                return id !== agentId;
            });

            syncPicker();
        });

        syncSelectedAgentPanel();
        syncPicker();
    });
</script>
