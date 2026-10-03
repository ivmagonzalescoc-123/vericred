@extends('layouts.app')

@section('title', 'Registrar')

@section('content')
<div style="padding-top:28px">
    <div class="actions" style="justify-content:space-between;margin-bottom:18px">
        <div>
            <h1 style="margin:0">Registrar dashboard</h1>
            <p class="muted" style="margin:0">{{ $chain['issuer_name'] ?? 'Issuing school' }}</p>
        </div>
        <a class="btn btn-gold" href="{{ route('registrar.create') }}">+ Issue credential</a>
    </div>

    @if (session('status'))
        <div class="alert alert-ok">{{ session('status') }}</div>
    @endif

    <div class="grid-3" style="margin-bottom:18px">
        <div class="card stat"><div class="muted small">Credentials issued</div><div class="num">{{ $stats['issued'] }}</div></div>
        <div class="card stat"><div class="muted small">Active</div><div class="num">{{ $stats['active'] }}</div></div>
        <div class="card stat"><div class="muted small">Revoked</div><div class="num">{{ $stats['revoked'] }}</div></div>
    </div>

    <div class="card" style="margin-bottom:18px">
        @if ($chain['online'])
            <strong><span class="chain-dot on"></span>Blockchain connected</strong>
            <span class="muted small">· block #{{ $chain['block'] }} · chain {{ $chain['chain_id'] }}</span>
            <div class="small muted" style="margin-top:6px">
                Contract <span class="mono">{{ $chain['contract'] }}</span><br>
                School wallet <span class="mono">{{ $chain['issuer'] }}</span>
            </div>
        @else
            <strong><span class="chain-dot off"></span>Blockchain offline</strong>
            <div class="small muted" style="margin-top:6px">{{ $chain['message'] }}</div>
        @endif
    </div>

    <div class="card">
        <form method="GET" class="actions" style="margin-bottom:14px">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name, student ID or program" style="max-width:360px">
            <button class="btn btn-ghost" type="submit">Search</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr><th>Graduate</th><th>Credential</th><th>Program</th><th>Block</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($credentials as $c)
                    <tr>
                        <td><strong>{{ $c->student_name }}</strong><br><span class="muted small">{{ $c->student_id }}</span></td>
                        <td>{{ $c->type }}</td>
                        <td>{{ $c->program }}</td>
                        <td>#{{ $c->block_number }}</td>
                        <td>
                            @if ($c->isRevoked())
                                <span class="badge badge-bad">Revoked</span>
                            @else
                                <span class="badge badge-ok">Anchored</span>
                            @endif
                        </td>
                        <td><a href="{{ route('registrar.show', $c) }}">Open →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted" style="text-align:center;padding:30px">No credentials yet. Issue your first one.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px">{{ $credentials->links('pagination::simple-default') }}</div>
    </div>
</div>
@endsection
