@extends('layouts.app')

@section('title', $credential->student_name)

@section('content')
<div style="padding-top:28px">
    <div class="no-print">
        @if (session('status'))
            <div class="alert alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-err">{{ $errors->first() }}</div>
        @endif
        @if ($credential->isRevoked())
            <div class="alert alert-err">Revoked {{ $credential->revoked_at->format('M j, Y') }}: {{ $credential->revoke_reason }}</div>
        @endif

        <div class="actions" style="margin-bottom:18px">
            <a class="btn btn-ghost" href="{{ route('registrar.index') }}">← Back</a>
            <a class="btn" href="{{ route('registrar.download', $credential) }}">Download .vericred.json</a>
            <button class="btn btn-ghost" onclick="window.print()">Print / Save as PDF</button>
            <a class="btn btn-gold" href="{{ $verifyUrl }}" target="_blank">Open verify link</a>
        </div>
    </div>

    <div class="card">
        <div class="certificate">
            <div class="school">{{ $issuerName }}</div>
            <div class="doc-type">{{ $credential->type }}</div>
            <div class="muted">This certifies that</div>
            <div class="name">{{ $credential->student_name }}</div>
            <div>has satisfied the requirements for</div>
            <div style="font-size:20px;font-weight:700;margin:6px 0">{{ $credential->program }}</div>
            @if ($credential->honors)
                <div style="font-style:italic">{{ $credential->honors }}</div>
            @endif
            <div class="muted" style="margin-top:8px">
                Conferred {{ $credential->date_conferred->format('F j, Y') }} · Student ID {{ $credential->student_id }}
                @if ($credential->gwa) · GWA {{ $credential->gwa }} @endif
            </div>
            <div class="qr">{!! $qr !!}</div>
            <div class="small muted">Scan to verify on the blockchain</div>
            <div class="mono" style="margin-top:6px;color:var(--muted)">{{ $credential->hash }}</div>
        </div>
    </div>

    <div class="grid-2 no-print" style="margin-top:18px">
        <div class="card">
            <h3 style="margin-top:0">On-chain record</h3>
            <table class="kv">
                <tr><th>Fingerprint</th><td class="mono">{{ $credential->hash }}</td></tr>
                <tr><th>Transaction</th><td class="mono">{{ $credential->tx_hash }}</td></tr>
                <tr><th>Block</th><td>#{{ $credential->block_number }}</td></tr>
                @if ($credential->revoke_tx_hash)
                    <tr><th>Revocation tx</th><td class="mono">{{ $credential->revoke_tx_hash }}</td></tr>
                @endif
                <tr><th>Issued by</th><td>{{ $credential->issuer?->name }}</td></tr>
            </table>
        </div>

        <div class="card">
            <h3 style="margin-top:0">Revoke credential</h3>
            @if ($credential->isRevoked())
                <p class="muted">This credential is revoked. Verifiers see it as <strong>revoked</strong>.</p>
            @else
                <p class="muted">Use this if the credential was issued in error or obtained fraudulently. This is recorded on the blockchain permanently.</p>
                <form method="POST" action="{{ route('registrar.revoke', $credential) }}" onsubmit="return confirm('Revoke this credential on the blockchain? This cannot be undone.')">
                    @csrf
                    <div class="field">
                        <input type="text" name="reason" placeholder="Reason for revocation" required>
                    </div>
                    <button class="btn btn-danger" type="submit">Revoke on blockchain</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
