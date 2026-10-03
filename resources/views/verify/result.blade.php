@extends('layouts.app')

@section('title', 'Verification result')

@php
    $verdicts = [
        'valid' => ['✔', 'Authentic credential', 'This document matches the fingerprint anchored on the blockchain by an accredited school. It has not been altered.'],
        'invalid' => ['✖', 'Not verified', $reason ?? ''],
        'revoked' => ['⚠', 'Credential revoked', $reason ?? ''],
        'unaccredited' => ['⚠', 'Issuer not accredited', $reason ?? ''],
        'offline' => ['⚠', 'Blockchain unreachable', $reason ?? ''],
    ];
    [$icon, $title, $message] = $verdicts[$status];

    $labels = [
        'type' => 'Credential',
        'student_name' => 'Graduate',
        'student_id' => 'Student ID',
        'program' => 'Program',
        'honors' => 'Honors',
        'gwa' => 'GWA',
        'date_conferred' => 'Date conferred',
    ];
@endphp

@section('content')
<div style="padding-top:28px">
    <div class="verdict {{ $status }}">
        <div class="icon">{{ $icon }}</div>
        <div>
            <h2>{{ $title }}</h2>
            <p>{{ $message }}</p>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <h3 style="margin-top:0">What the document claims</h3>
            <table class="kv">
                @if ($issuerName)
                    <tr><th>Issued by</th><td>{{ $issuerName }}</td></tr>
                @endif
                @foreach ($labels as $key => $label)
                    @if (($credential[$key] ?? '') !== '')
                        <tr><th>{{ $label }}</th><td>{{ $credential[$key] }}</td></tr>
                    @endif
                @endforeach
            </table>
            @if ($status !== 'valid')
                <p class="muted small" style="margin-bottom:0">These details are shown as submitted. They are <strong>not</strong> confirmed.</p>
            @endif
        </div>

        <div class="card">
            <h3 style="margin-top:0">Blockchain proof</h3>
            <table class="kv">
                <tr><th>Recomputed fingerprint (SHA-256)</th><td class="mono">{{ $hash }}</td></tr>
                @if ($record && $record['exists'])
                    <tr><th>Anchored by wallet</th><td class="mono">{{ $record['issuer'] }}</td></tr>
                    <tr><th>Anchored on</th><td>{{ \Carbon\Carbon::createFromTimestamp($record['issued_at'])->timezone('Asia/Manila')->format('M j, Y g:i A') }}</td></tr>
                    @if (! empty($txHash))
                        <tr><th>Transaction</th><td class="mono">{{ $txHash }}</td></tr>
                    @endif
                @elseif ($record)
                    <tr><th>On-chain record</th><td style="color:var(--red)">None found for this fingerprint</td></tr>
                @endif
                @isset($contract)
                    <tr><th>Smart contract</th><td class="mono">{{ $contract }}</td></tr>
                    <tr><th>Chain ID</th><td>{{ $chainId }}</td></tr>
                @endisset
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <details>
            <summary>Tamper test: edit the document and verify again</summary>
            <p class="muted">Try changing the GWA, the name, or a single digit of the date. The fingerprint will change and verification will fail.</p>
            <form method="POST" action="{{ route('verify.check') }}">
                @csrf
                <textarea name="json" rows="14">{{ json_encode(['credential' => $credential], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>
                <div class="actions" style="margin-top:12px">
                    <button class="btn" type="submit">Verify edited document</button>
                </div>
            </form>
        </details>
    </div>

    <p style="margin-top:22px"><a href="{{ route('home') }}">← Verify another credential</a></p>
</div>
@endsection
