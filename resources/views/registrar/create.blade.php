@extends('layouts.app')

@section('title', 'Issue credential')

@section('content')
<div class="card" style="max-width:720px;margin:28px auto">
    <h2 style="margin-top:0">Issue a credential</h2>
    <p class="muted">On submit, VeriCred adds a secret salt, computes the SHA-256 fingerprint, and anchors it on the blockchain from your school's accredited wallet.</p>

    @if ($errors->any())
        <div class="alert alert-err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('registrar.store') }}">
        @csrf
        <div class="grid-2">
            <div class="field">
                <label for="type">Credential type</label>
                <select id="type" name="type">
                    @foreach ($types as $type)
                        <option @selected(old('type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="date_conferred">Date conferred</label>
                <input type="date" id="date_conferred" name="date_conferred" value="{{ old('date_conferred', now()->format('Y-m-d')) }}" required>
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label for="student_name">Full name of graduate</label>
                <input type="text" id="student_name" name="student_name" value="{{ old('student_name') }}" placeholder="Juan P. Dela Cruz" required>
            </div>
            <div class="field">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id" name="student_id" value="{{ old('student_id') }}" placeholder="01-2223-012345" required>
            </div>
        </div>
        <div class="field">
            <label for="program">Program / degree</label>
            <input type="text" id="program" name="program" value="{{ old('program') }}" placeholder="Bachelor of Science in Information Technology" required>
        </div>
        <div class="grid-2">
            <div class="field">
                <label for="honors">Latin honors <span class="muted">(optional)</span></label>
                <input type="text" id="honors" name="honors" value="{{ old('honors') }}" placeholder="Cum Laude">
            </div>
            <div class="field">
                <label for="gwa">GWA <span class="muted">(optional)</span></label>
                <input type="text" id="gwa" name="gwa" value="{{ old('gwa') }}" placeholder="1.45">
            </div>
        </div>
        <div class="actions">
            <button class="btn btn-gold" type="submit">Issue &amp; anchor on blockchain</button>
            <a class="btn btn-ghost" href="{{ route('registrar.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
