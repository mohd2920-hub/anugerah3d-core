@extends("admin.layouts.app")

@section("title", "Email to Agen | Anugerah3D Admin")

@section("page_title", "Email to Agen")

@section("content")
    <div class="space-y-5">
        @if (session("success"))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                {{ session("success") }}
            </div>
        @endif

        @if (session("error"))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ session("error") }}
            </div>
        @endif

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Agent email templates</h2>
                    <p class="mt-1 text-sm text-slate-500">Create and save email templates first. No email will be sent until you press <strong>Send</strong>.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route("admin.agents.index") }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Agents
                    </a>
                    <a href="{{ route("admin.agent-email-templates.create") }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">
                        Create Template
                    </a>
                </div>
            </div>
        </div>

        <div class="hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
            <table class="admin-data-table w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="rounded-tl-lg px-4 py-3 text-left font-semibold text-slate-700">Template</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Recipients</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Subject</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Last sent</th>
                        <th class="rounded-tr-lg px-4 py-3 text-right font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $template->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">Created {{ $template->created_at?->diffForHumans() ?? "-" }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <div class="font-medium text-slate-900">{{ $template->recipientScopeLabel() }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format((int) ($template->resolved_recipient_count ?? 0)) }} saved recipient{{ (int) ($template->resolved_recipient_count ?? 0) === 1 ? "" : "s" }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $template->subject }}</td>
                            <td class="px-4 py-4 text-slate-600">
                                @if ($template->last_sent_at)
                                    <div class="font-medium text-slate-900">{{ $template->last_sent_at->format("d M Y, g:i A") }}</div>
                                    <div class="mt-1 text-xs text-slate-500">by {{ $template->lastSentBy?->name ?? "Admin" }}</div>
                                @else
                                    <span class="text-slate-400">Not sent yet</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route("admin.agent-email-templates.edit", $template) }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route("admin.agent-email-templates.send", $template) }}" onsubmit="return confirm(&quot;Send this email template now?&quot;)">
                                        @csrf
                                        <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                            Send
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">No email template has been created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            @forelse ($templates as $template)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold text-slate-950">{{ $template->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $template->subject }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $template->recipientScopeLabel() }}</span>
                    </div>

                    <div class="mt-4 grid gap-2 text-sm text-slate-600">
                        <p>{{ number_format((int) ($template->resolved_recipient_count ?? 0)) }} saved recipient{{ (int) ($template->resolved_recipient_count ?? 0) === 1 ? "" : "s" }}</p>
                        <p>{{ $template->last_sent_at ? "Last sent ".$template->last_sent_at->format("d M Y, g:i A") : "Not sent yet" }}</p>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route("admin.agent-email-templates.edit", $template) }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Edit
                        </a>
                        <form method="POST" action="{{ route("admin.agent-email-templates.send", $template) }}" class="flex-1" onsubmit="return confirm(&quot;Send this email template now?&quot;)">
                            @csrf
                            <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Send
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                    No email template has been created yet.
                </div>
            @endforelse
        </div>

        {{ $templates->links() }}
    </div>
@endsection
