@extends('admin.layouts.app')

@section('title', 'Edit Agent | Anugerah3D Admin')

@section('page_title', 'Edit Agent')

@section('content')
    @php
        $loginMessage = $loginInfo['message'] ?? $agent->loginInfoMessage();
        $whatsappUrl = ($loginInfo['whatsapp_url'] ?? null) ?: $agent->whatsappUrl($loginMessage);
    @endphp

    <div class="grid max-w-6xl gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            @include('admin.agents._form', [
                'agent' => $agent,
                'action' => route('admin.agents.update', $agent),
                'method' => 'PUT',
                'submitLabel' => 'Save Changes',
            ])
        </div>

        <div class="space-y-5">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Profile picture</h2>
                @if ($agent->profile_picture)
                    @php
                        $profilePictureUrl = filter_var($agent->profile_picture, FILTER_VALIDATE_URL) ? $agent->profile_picture : asset($agent->profile_picture);
                    @endphp
                    <img src="{{ $profilePictureUrl }}" alt="{{ $agent->agt_name }}" class="mt-3 h-24 w-24 rounded-lg border border-slate-200 object-cover">
                @endif
                <form method="POST" action="{{ route('admin.agents.profile-picture.update', $agent) }}" enctype="multipart/form-data" class="mt-4 space-y-3" data-profile-picture-form>
                    @csrf
                    @method('PUT')
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label for="profile_picture_file" class="inline-flex min-h-10 cursor-pointer items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">Choose picture</label>
                        <label for="profile_picture_camera" class="inline-flex min-h-10 cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Take photo</label>
                    </div>
                    <input id="profile_picture_file" type="file" accept="image/*" data-profile-picture-input class="sr-only">
                    <input id="profile_picture_camera" type="file" accept="image/*" capture="environment" data-profile-picture-input class="sr-only">
                    <p class="hidden text-sm text-slate-500" data-profile-picture-status></p>
                    <noscript>
                        <input type="file" name="profile_picture_file" accept="image/*" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-[#1a73e8] outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                        <button type="submit" class="mt-3 inline-flex w-full min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">Upload picture</button>
                    </noscript>
                    @error('profile_picture_file')
                        <span class="block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </form>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Login Info</h2>
                <textarea id="agent-login-info-message" readonly class="mt-3 min-h-32 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">{{ $loginMessage }}</textarea>
                <div class="mt-3 grid gap-2">
                    <button type="button" data-copy-target="agent-login-info-message" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white transition hover:bg-[#1558b0]">
                        Copy login info
                    </button>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-green-200 bg-white px-4 text-sm font-semibold text-green-700 transition hover:bg-green-50">
                            Open WhatsApp
                        </a>
                    @endif
                </div>

                @if ($agent->agt_status === \App\Models\Agent::StatusActive)
                    <form method="POST" action="{{ route('admin.agents.registration-info.resend', $agent) }}" class="mt-4 border-t border-slate-200 pt-4" onsubmit="return confirm('This will replace the current password with a new 8-character password. Continue?');">
                        @csrf
                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-4 text-sm font-semibold text-[#d95419] transition hover:bg-orange-100">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                            Resend registration info
                        </button>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Creates a new 8-character password and emails the complete login information to this agent.</p>
                    </form>
                @endif
                @error('resend_registration_info')
                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Reset Password</h2>
                <form method="POST" action="{{ route('admin.agents.password.update', $agent) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-700">New Password</label>
                        <input type="password" id="password" name="password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error('password')
                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    </div>

                    <button type="submit" class="inline-flex w-full min-h-10 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-700">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const profilePictureForm = document.querySelector("[data-profile-picture-form]");

            function setProfilePictureStatus(message, isError) {
                if (!profilePictureForm) {
                    return;
                }

                const status = profilePictureForm.querySelector("[data-profile-picture-status]");

                if (!status) {
                    return;
                }

                status.textContent = message;
                status.classList.toggle("hidden", message === "");
                status.classList.toggle("text-red-600", Boolean(isError));
                status.classList.toggle("text-slate-500", !isError);
            }

            function loadProfilePictureImage(file) {
                return new Promise(function (resolve, reject) {
                    const image = new Image();
                    const url = URL.createObjectURL(file);

                    image.onload = function () {
                        URL.revokeObjectURL(url);
                        resolve(image);
                    };

                    image.onerror = function () {
                        URL.revokeObjectURL(url);
                        reject(new Error("Invalid image"));
                    };

                    image.src = url;
                });
            }

            function makeProfilePictureThumb(file) {
                return loadProfilePictureImage(file).then(function (image) {
                    const size = 300;
                    const sourceWidth = image.naturalWidth || image.width;
                    const sourceHeight = image.naturalHeight || image.height;
                    const cropSize = Math.min(sourceWidth, sourceHeight);
                    const sourceX = Math.floor((sourceWidth - cropSize) / 2);
                    const sourceY = Math.floor((sourceHeight - cropSize) / 2);
                    const canvas = document.createElement("canvas");
                    const context = canvas.getContext("2d");

                    canvas.width = size;
                    canvas.height = size;
                    context.fillStyle = "#ffffff";
                    context.fillRect(0, 0, size, size);
                    context.drawImage(image, sourceX, sourceY, cropSize, cropSize, 0, 0, size, size);

                    return new Promise(function (resolve, reject) {
                        canvas.toBlob(function (blob) {
                            if (blob) {
                                resolve(blob);
                                return;
                            }

                            reject(new Error("Unable to prepare image"));
                        }, "image/jpeg", 0.85);
                    });
                });
            }

            function uploadProfilePicture(file) {
                if (!profilePictureForm || !file) {
                    return;
                }

                if (!file.type || !file.type.startsWith("image/")) {
                    setProfilePictureStatus("Please select an image file.", true);
                    return;
                }

                setProfilePictureStatus("Preparing picture...", false);

                makeProfilePictureThumb(file)
                    .then(function (blob) {
                        const formData = new FormData(profilePictureForm);
                        const originalName = file.name || "profile-picture";
                        const dotIndex = originalName.lastIndexOf(".");
                        const baseName = dotIndex > 0 ? originalName.slice(0, dotIndex) : originalName;

                        formData.delete("profile_picture_file");
                        formData.append("profile_picture_file", blob, baseName + ".jpg");
                        setProfilePictureStatus("Uploading picture...", false);

                        return fetch(profilePictureForm.action, {
                            method: "POST",
                            body: formData,
                            credentials: "same-origin",
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        });
                    })
                    .then(function (response) {
                        if (response.ok || response.redirected) {
                            window.location.href = response.url || profilePictureForm.action;
                            return;
                        }

                        if (response.status === 413) {
                            throw new Error("Picture is still too large.");
                        }

                        throw new Error("Unable to upload picture.");
                    })
                    .catch(function (error) {
                        setProfilePictureStatus(error.message || "Unable to upload picture.", true);
                    });
            }

            document.querySelectorAll("[data-profile-picture-input]").forEach(function (input) {
                input.addEventListener("change", function () {
                    uploadProfilePicture(input.files && input.files[0]);
                    input.value = "";
                });
            });

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

            document.querySelectorAll('[data-copy-target]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.dataset.copyTarget);

                    if (target) {
                        copyText(target.value, button);
                    }
                });
            });
        })();
    </script>
@endsection
