@if (session('success'))<p role="status" class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</p>@endif
@if (session('warning'))<p role="alert" class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">{{ session('warning') }}</p>@endif
@if ($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
