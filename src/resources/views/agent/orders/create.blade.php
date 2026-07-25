@extends('agent.layouts.app')

@section('title', 'New Order | Anugerah3D Agent')
@section('page_title', 'New Order')
@section('back_url', route('agent.history'))

@section('content')
    @include('agent.partials.product-ordering')
@endsection
