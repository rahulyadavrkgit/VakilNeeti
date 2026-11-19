@extends('layouts.app')

@section('title', 'Edit Task')
@section('page-heading', 'Edit Task')

@section('content')
<div class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('tasks.update', $task) }}">
        @csrf
        @method('PUT')
        @include('tasks._form')
    </form>
</div>
@endsection