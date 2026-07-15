<?php

namespace App\Http\Requests\Agent;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePictureRequest extends FormRequest
{
    protected $errorBag = 'pictureUpdate';

    public function authorize(): bool
    {
        return $this->user('agent') instanceof Agent;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'profile_picture_file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ];
    }
}
