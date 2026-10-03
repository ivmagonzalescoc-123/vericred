@extends('layouts.app')

@section('hero')
<section class="hero">
    <div class="container">
        <h1>Verify any Filipino diploma <span>in 5 seconds</span>, from anywhere in the world.</h1>
        <p>Employers, agencies and embassies can confirm a graduate's credential directly against the blockchain. No calling the registrar, no waiting weeks for authentication.</p>
    </div>
</section>
@endsection

@section('content')
<div class="card pull-up" id="scan-tools"
     data-verify-url="{{ route('verify.link') }}"
     data-pdf-worker="{{ asset('vendor/pdfjs/pdf.worker.min.js') }}">
    <h2 style="margin-top:0">Verify a credential</h2>

    @if ($errors->any())
        <div class="alert alert-err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('verify.check') }}" enctype="multipart/form-data" id="verify-form">
        @csrf
        <div class="verify-options">
            <button type="button" class="verify-option" id="btn-camera">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>
                <strong>Scan QR with camera</strong>
                <span>Point your camera at the diploma's QR code</span>
            </button>
            <label class="verify-option" for="file">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 15v5h16v-5"/></svg>
                <strong>Upload photo or PDF</strong>
                <span>A photo of the QR, the certificate PDF, or the .vericred.json file</span>
                <input type="file" id="file" name="file" accept="image/*,.pdf,application/pdf,.json,application/json" hidden>
            </label>
        </div>

        <div id="camera-box" class="camera-box" hidden>
            <video id="camera-video" playsinline muted></video>
            <div class="camera-frame" aria-hidden="true"></div>
            <button type="button" class="btn btn-ghost" id="btn-camera-stop">Stop camera</button>
        </div>

        <div id="scan-status" class="scan-status" role="status" hidden></div>

        <details class="paste-box" @if (old('json')) open @endif>
            <summary>Or paste the credential file's contents</summary>
            <div class="field" style="margin-top:12px">
                <textarea name="json" placeholder='{"vericred": 1, "credential": { ... }}'>{{ old('json') }}</textarea>
            </div>
            <button class="btn btn-gold" type="submit">Verify on blockchain</button>
        </details>
    </form>
</div>

<section class="section">
    <h2>How it works</h2>
    <div class="grid-3">
        <div class="card">
            <div class="step-num">1</div>
            <h3>School issues</h3>
            <p class="muted">The registrar issues the credential. Its SHA-256 fingerprint, mixed with a secret random salt, is anchored on the blockchain by the school's accredited wallet.</p>
        </div>
        <div class="card">
            <div class="step-num">2</div>
            <h3>Graduate shares</h3>
            <p class="muted">The graduate keeps the credential file and QR code, and decides who to share it with. No personal data is ever written on-chain.</p>
        </div>
        <div class="card">
            <div class="step-num">3</div>
            <h3>Anyone verifies</h3>
            <p class="muted">VeriCred recomputes the fingerprint and checks the chain. Change even one character, like a grade, and the fingerprint no longer matches.</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/jsQR.js') }}"></script>
    <script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
    <script src="{{ asset('js/verify-scan.js') }}"></script>
@endpush
