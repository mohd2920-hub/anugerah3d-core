@extends("admin.layouts.app")

@section("title", "Create Email Template | Anugerah3D Admin")

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
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Create email template</h2>
                    <p class="mt-1 text-sm text-slate-500">Prepare the subject, body, and recipients now. The system will only send after you press the send button later.</p>
                </div>
                <a href="{{ route("admin.agent-email-templates.index") }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Back to Templates
                </a>
            </div>
        </div>

        @include("components.admin.agent-email-templates.form-panel")
    </div>
@endsection
