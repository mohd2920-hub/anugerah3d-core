@extends('admin.layouts.app')

@section('title', 'Add Agent | Anugerah3D Admin')

@section('page_title', 'Add Agent')

@section('content')
    <div class="max-w-4xl">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            @include('admin.agents._form', [
                'action' => route('admin.agents.store'),
                'method' => 'POST',
                'submitLabel' => 'Create Agent',
            ])
        </div>
    </div>
@endsection
