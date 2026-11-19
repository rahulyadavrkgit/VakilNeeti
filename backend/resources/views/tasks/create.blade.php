@extends('layouts.app')

@section('title', 'Create Task')
@section('page-heading', 'Create Task')

@section('content')
<div class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('tasks.store') }}">
        @csrf
        @include('tasks._form')
    </form>
</div>
@endsection