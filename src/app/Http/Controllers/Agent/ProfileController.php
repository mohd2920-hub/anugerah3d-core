<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\UpdatePasswordRequest;
use App\Http\Requests\Agent\UpdateProfilePictureRequest;
use App\Http\Requests\Agent\UpdateProfileRequest;
use App\Models\Agent;
use App\Models\DataState;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');

        return view('agent.profile', [
            'agent' => $agent,
            'states' => DataState::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');
        $agent->fill($request->validated());
        $changedFields = array_keys($agent->getDirty());
        $agent->save();

        AdminActivity::record(
            request: $request,
            event: 'agent.profile.updated',
            description: "Agent {$agent->login_id} updated their personal details.",
            properties: $this->activityProperties($agent, ['changed_fields' => $changedFields]),
        );

        return redirect()->route('agent.profile')->with('success', 'Personal details updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');
        $agent->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => null,
        ])->save();

        AdminActivity::record(
            request: $request,
            event: 'agent.profile.password_changed',
            description: "Agent {$agent->login_id} changed their password.",
            properties: $this->activityProperties($agent),
        );

        return redirect()->route('agent.profile')->with('success', 'Password changed successfully.');
    }

    public function updateProfilePicture(UpdateProfilePictureRequest $request): RedirectResponse
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');
        $oldPicture = $agent->profile_picture;
        $newPicture = $this->storeProfilePicture($request->file('profile_picture_file'), $agent);

        $agent->forceFill(['profile_picture' => $newPicture])->save();
        $this->deleteOldProfilePicture($oldPicture, $newPicture);

        AdminActivity::record(
            request: $request,
            event: 'agent.profile.picture_updated',
            description: "Agent {$agent->login_id} updated their profile picture.",
            properties: $this->activityProperties($agent),
        );

        return redirect()->route('agent.profile')->with('success', 'Profile picture updated successfully.');
    }

    /** @return array<string, mixed> */
    private function activityProperties(Agent $agent, array $properties = []): array
    {
        return array_merge([
            'page' => 'Profile',
            'actor_type' => 'Agent',
            'actor_name' => $agent->agt_name,
            'agent_id' => $agent->getKey(),
            'login_id' => $agent->login_id,
            'email' => $agent->email,
        ], $properties);
    }

    private function storeProfilePicture(UploadedFile $file, Agent $agent): string
    {
        $directory = public_path('profiles');
        File::ensureDirectoryExists($directory);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to save the image. Please contact support.',
            ])->errorBag('pictureUpdate');
        }

        $source = $this->imageResourceFromUpload($file);

        if (! $source) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to process the selected image.',
            ])->errorBag('pictureUpdate');
        }

        $source = $this->orientCameraImage($source, $file);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $cropSize = min($sourceWidth, $sourceHeight);
        $sourceX = (int) floor(($sourceWidth - $cropSize) / 2);
        $sourceY = (int) floor(($sourceHeight - $cropSize) / 2);
        $thumb = imagecreatetruecolor(300, 300);
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefilledrectangle($thumb, 0, 0, 300, 300, $white);
        imagecopyresampled($thumb, $source, 0, 0, $sourceX, $sourceY, 300, 300, $cropSize, $cropSize);

        $relativePath = 'profiles/agent-'.$agent->getKey().'-'.Str::uuid()->toString().'.jpg';
        $saved = imagejpeg($thumb, public_path($relativePath), 85);
        imagedestroy($source);
        imagedestroy($thumb);

        if (! $saved) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to save the profile picture.',
            ])->errorBag('pictureUpdate');
        }

        return $relativePath;
    }

    private function imageResourceFromUpload(UploadedFile $file)
    {
        return match ($file->getMimeType()) {
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/gif' => imagecreatefromgif($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => imagecreatefromjpeg($file->getRealPath()),
        };
    }

    private function orientCameraImage($source, UploadedFile $file)
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $source;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $source;
        }

        $rotated = imagerotate($source, $angle, 0);

        if ($rotated === false) {
            return $source;
        }

        imagedestroy($source);

        return $rotated;
    }

    private function deleteOldProfilePicture(?string $oldPicture, string $newPicture): void
    {
        if (! $oldPicture || $oldPicture === $newPicture || filter_var($oldPicture, FILTER_VALIDATE_URL)) {
            return;
        }

        if (str_starts_with($oldPicture, 'profiles/')) {
            File::delete(public_path($oldPicture));
        }
    }
}
