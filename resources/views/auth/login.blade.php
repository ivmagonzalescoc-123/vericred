@extends('layouts.app')

@section('title', 'School login')

@section('content')
<div class="card" style="max-width:440px;margin:48px auto">
    <h2 style="margin-top:0">Registrar login</h2>
    <p class="muted">For accredited schools issuing credentials.</p>

    @if ($errors->any())
        <div class="alert alert-err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="field">
            <label style="font-weight:400"><input type="checkbox" name="remember"> Remember me</label>
        </div>
        <button class="btn" type="submit" style="width:100%;justify-content:center">Log in</button>
    </form>
</div>
@endsection
