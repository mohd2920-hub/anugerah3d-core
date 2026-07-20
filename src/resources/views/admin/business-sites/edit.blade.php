@extends('admin.layouts.app')
@section('title', 'Edit Business Site | Anugerah3D Admin')
@section('page_title', 'Edit Business Site')
@section('content')
<div class="max-w-2xl rounded-lg bg-white p-6 shadow-sm">
    @include('admin.business-sites._form', ['action' => route('admin.business-sites.update', $businessSite), 'method' => 'PUT', 'submitLabel' => 'Save changes'])
</div>
@endsection
