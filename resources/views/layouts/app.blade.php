<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Verify credentials') · VeriCred</title>
    <link rel="stylesheet" href="{{ asset('css/vericred.css') }}">
</head>
<body>
<header class="topbar">
    <div class="container">
        <a href="{{ route('home') }}" class="brand"><span class="brand-mark">V</span> VeriCred</a>
        <nav class="nav">
            <a href="{{ route('home') }}">Verify</a>
            @auth
                <a href="{{ route('registrar.index') }}">Registrar</a>
                <form method="POST" action="{{ route('registrar.logout') }}">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}">School login</a>
            @endauth
        </nav>
    </div>
</header>

@yield('hero')

<main class="container">
    @yield('content')
</main>

<footer>
    VeriCred: blockchain-anchored academic credentials. Only SHA-256 fingerprints are stored on-chain, with no personal data (RA 10173 compliant by design).
</footer>
@stack('scripts')
</body>
</html>
