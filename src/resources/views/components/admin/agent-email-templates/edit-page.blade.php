@extends("admin.layouts.app")

@section("title", "Edit Email Template | Anugerah3D Admin")

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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">{{ $template->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Update the template any time. Saving changes still will not send email automatically.</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <div class="font-semibold text-slate-900">{{ $template->recipientScopeLabel() }}</div>
                    <div class="mt-1">
                        @if ($template->recipient_scope === \App\Models\AgentEmailTemplate::RecipientSelectedAgents)
                            {{ number_format(count($selectedAgentIds)) }} selected saved ID{{ count($selectedAgentIds) === 1 ? "" : "s" }}
                        @else
                            All agents will be targeted
                        @endif
                    </div>
                    <div class="mt-1">{{ $template->last_sent_at ? "Last sent ".$template->last_sent_at->format("d M Y, g:i A") : "Not sent yet" }}</div>
                </div>
            </div>
        </div>

        @include("components.admin.agent-email-templates.form-panel")

        <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-emerald-950">Ready to send</h2>
                    <p class="mt-1 text-sm text-emerald-800">This action will queue email delivery using the current recipient selection in this template.</p>
                </div>
                <form method="POST" action="{{ route("admin.agent-email-templates.send", $template) }}" onsubmit="return confirm(&quot;Send this email template to the selected recipients now?&quot;)">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Send Email
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
