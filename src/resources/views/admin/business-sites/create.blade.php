@extends('admin.layouts.app')
@section('title', 'Add Business Site | Anugerah3D Admin')
@section('page_title', 'Add Business Site')
@section('content')
<div class="max-w-2xl rounded-lg bg-white p-6 shadow-sm">
    @include('admin.business-sites._form', ['action' => route('admin.business-sites.store'), 'method' => 'POST', 'submitLabel' => 'Create site'])
</div>
@endsection
