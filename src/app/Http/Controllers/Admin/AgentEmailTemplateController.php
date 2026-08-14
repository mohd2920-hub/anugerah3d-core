<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAgentEmailTemplateRequest;
use App\Mail\AgentTemplateMail;
use App\Models\Agent;
use App\Models\AgentEmailTemplate;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AgentEmailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = AgentEmailTemplate::query()
            ->with(["creator:id,name", "lastSentBy:id,name"])
            ->latest("updated_at")
            ->paginate(12);

        $templates->getCollection()->transform(function (AgentEmailTemplate $template): AgentEmailTemplate {
            $template->setAttribute("resolved_recipient_count", $template->recipientAgents()->count());

            return $template;
        });

        return view("components.admin.agent-email-templates.index-page", [
            "templates" => $templates,
        ]);
    }

    public function create(): View
    {
        return view("components.admin.agent-email-templates.create-page", $this->formData(new AgentEmailTemplate([
            "recipient_scope" => AgentEmailTemplate::RecipientAllAgents,
        ])));
    }

    public function store(SaveAgentEmailTemplateRequest $request): RedirectResponse
    {
        $template = AgentEmailTemplate::query()->create(array_merge(
            $request->templateData(),
            ["created_by_admin_id" => $request->user("admin")?->getKey()],
        ));

        AdminActivity::record(
            request: $request,
            event: "admin.agent_email_template.created",
            description: "Admin created agent email template {$template->name}.",
            adminUser: $request->user("admin"),
            properties: [
                "page" => "Email to Agen",
                "template_id" => $template->getKey(),
                "template_name" => $template->name,
                "recipient_scope" => $template->recipient_scope,
            ],
        );

        return redirect()
            ->route("admin.agent-email-templates.edit", $template)
            ->with("success", "Email template saved. Review it and press Send Email when you are ready.");
    }

    public function edit(AgentEmailTemplate $agentEmailTemplate): View
    {
        return view("components.admin.agent-email-templates.edit-page", $this->formData($agentEmailTemplate));
    }

    public function update(SaveAgentEmailTemplateRequest $request, AgentEmailTemplate $agentEmailTemplate): RedirectResponse
    {
        $agentEmailTemplate->fill($request->templateData())->save();

        AdminActivity::record(
            request: $request,
            event: "admin.agent_email_template.updated",
            description: "Admin updated agent email template {$agentEmailTemplate->name}.",
            adminUser: $request->user("admin"),
            properties: [
                "page" => "Email to Agen",
                "template_id" => $agentEmailTemplate->getKey(),
                "template_name" => $agentEmailTemplate->name,
                "recipient_scope" => $agentEmailTemplate->recipient_scope,
            ],
        );

        return redirect()
            ->route("admin.agent-email-templates.edit", $agentEmailTemplate)
            ->with("success", "Email template updated. No email has been sent yet.");
    }

    public function send(Request $request, AgentEmailTemplate $agentEmailTemplate): RedirectResponse
    {
        $recipients = $agentEmailTemplate->recipientAgents()->get(["id", "agt_name", "email"]);

        $validRecipients = $recipients
            ->filter(static fn (Agent $agent): bool => is_string($agent->email) && filter_var($agent->email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique("email")
            ->values();

        if ($validRecipients->isEmpty()) {
            return back()->with("error", "No valid recipient email addresses were found for this template.");
        }

        foreach ($validRecipients as $recipient) {
            Mail::to($recipient->email)->send(new AgentTemplateMail($agentEmailTemplate, $recipient));
        }

        $agentEmailTemplate->forceFill([
            "last_sent_at" => now(),
            "last_sent_by_admin_id" => $request->user("admin")?->getKey(),
        ])->save();

        $skippedCount = $recipients->count() - $validRecipients->count();

        AdminActivity::record(
            request: $request,
            event: "admin.agent_email_template.sent",
            description: "Admin sent agent email template {$agentEmailTemplate->name}.",
            adminUser: $request->user("admin"),
            properties: [
                "page" => "Email to Agen",
                "template_id" => $agentEmailTemplate->getKey(),
                "template_name" => $agentEmailTemplate->name,
                "recipient_scope" => $agentEmailTemplate->recipient_scope,
                "sent_count" => $validRecipients->count(),
                "skipped_count" => $skippedCount,
            ],
        );

        $message = "Email sent to ".$validRecipients->count()." ".Str::plural("agent", $validRecipients->count()).".";

        if ($skippedCount > 0) {
            $message .= " ".$skippedCount." ".Str::plural("agent", $skippedCount)." skipped because the email address is missing or invalid.";
        }

        return back()->with("success", $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(AgentEmailTemplate $template): array
    {
        return [
            "template" => $template,
            "recipientScopeOptions" => AgentEmailTemplate::recipientScopes(),
            "agents" => Agent::query()
                ->orderBy("agt_name")
                ->get(["id", "agt_name", "login_id", "email", "agt_status"]),
            "selectedAgentIds" => $template->selectedAgentIds(),
        ];
    }
}
