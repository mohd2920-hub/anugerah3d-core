<?php

namespace App\Actions\Admin;

use App\Models\AgentEmailTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncAgentEmailTemplateVideo
{
    public function handle(AgentEmailTemplate $template, ?UploadedFile $upload, bool $remove): void
    {
        if (! $upload && ! $remove) {
            return;
        }
        if (! Schema::hasColumn('agent_email_templates', 'video_path')) {
            throw ValidationException::withMessages(['template_video' => 'Fungsi video sedang menunggu pengaktifan.']);
        }
        $newPath = null;
        try {
            if ($upload) {
                $directory = 'videos/agent-email-templates';
                File::ensureDirectoryExists(public_path($directory));
                $filename = 'template-'.$template->getKey().'-'.Str::uuid().'.'.$upload->extension();
                $upload->move(public_path($directory), $filename);
                $newPath = $directory.'/'.$filename;
            }
            $template->forceFill(['video_path' => $newPath])->save();
        } catch (Throwable $exception) {
            if ($newPath) {
                File::delete(public_path($newPath));
            }
            throw $exception;
        }
    }
}
