<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Services\ChainException;
use App\Services\CredentialCodec;
use App\Services\VeriCredChain;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrarController extends Controller
{
    public function index(Request $request, VeriCredChain $chain)
    {
        $search = trim((string) $request->query('q'));

        $credentials = Credential::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('student_name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%")
                ->orWhere('program', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('registrar.index', [
            'credentials' => $credentials,
            'search' => $search,
            'chain' => $chain->status(),
            'stats' => [
                'issued' => Credential::count(),
                'active' => Credential::whereNull('revoked_at')->count(),
                'revoked' => Credential::whereNotNull('revoked_at')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('registrar.create', ['types' => Credential::TYPES]);
    }

    public function store(Request $request, VeriCredChain $chain)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(Credential::TYPES)],
            'student_name' => ['required', 'string', 'max:120'],
            'student_id' => ['required', 'string', 'max:40'],
            'program' => ['required', 'string', 'max:160'],
            'honors' => ['nullable', 'string', 'max:80'],
            'gwa' => ['nullable', 'string', 'max:10'],
            'date_conferred' => ['required', 'date'],
        ]);

        try {
            $credential = [
                'v' => '1',
                'type' => $data['type'],
                'student_name' => $data['student_name'],
                'student_id' => $data['student_id'],
                'program' => $data['program'],
                'honors' => $data['honors'] ?? '',
                'gwa' => $data['gwa'] ?? '',
                'date_conferred' => date('Y-m-d', strtotime($data['date_conferred'])),
                'issuer' => strtolower($chain->issuerAddress()),
                'salt' => CredentialCodec::salt(),
            ];

            $hash = CredentialCodec::hash($credential);
            $anchor = $chain->issue($hash);
        } catch (ChainException $e) {
            return back()->withInput()->withErrors(['chain' => $e->getMessage()]);
        }

        $record = Credential::create([
            ...$data,
            'payload' => CredentialCodec::canonical($credential),
            'hash' => $hash,
            'tx_hash' => $anchor['tx'],
            'block_number' => $anchor['block'],
            'issued_by' => $request->user()->id,
        ]);

        return redirect()->route('registrar.show', $record)
            ->with('status', "Anchored on the blockchain in block #{$anchor['block']}.");
    }

    public function show(Credential $credential, VeriCredChain $chain)
    {
        $verifyUrl = rtrim(config('vericred.public_url') ?: url('/'), '/').'/v?c='.$credential->urlToken();

        $qr = (new QRCode(new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            // One merged path instead of a square per module: no hairline seams when printed or rendered from a PDF.
            'connectPaths' => true,
            'drawLightModules' => false,
        ])))->render($verifyUrl);

        $status = $chain->status();

        return view('registrar.show', [
            'credential' => $credential,
            'qr' => $qr,
            'verifyUrl' => $verifyUrl,
            'issuerName' => $status['issuer_name'] ?? 'Issuing School',
            'chain' => $status,
        ]);
    }

    /** The file the student keeps and shares with employers. */
    public function download(Credential $credential, VeriCredChain $chain)
    {
        $file = [
            'vericred' => 1,
            'credential' => $credential->credential(),
            'anchor' => [
                'hash' => $credential->hash,
                'tx_hash' => $credential->tx_hash,
                'block' => $credential->block_number,
                'contract' => rescue(fn () => $chain->contractAddress(), null, false),
                'chain_id' => rescue(fn () => $chain->chainId(), null, false),
            ],
        ];

        $name = preg_replace('/[^A-Za-z0-9-]+/', '-', "{$credential->student_id}-{$credential->type}");

        return response()->streamDownload(
            fn () => print(json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            strtolower($name).'.vericred.json',
            ['Content-Type' => 'application/json'],
        );
    }

    public function revoke(Request $request, Credential $credential, VeriCredChain $chain)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        if ($credential->isRevoked()) {
            return back();
        }

        try {
            $anchor = $chain->revoke($credential->hash);
        } catch (ChainException $e) {
            return back()->withErrors(['chain' => $e->getMessage()]);
        }

        $credential->update([
            'revoked_at' => now(),
            'revoke_reason' => $data['reason'],
            'revoke_tx_hash' => $anchor['tx'],
        ]);

        return back()->with('status', 'Credential revoked on the blockchain. Verifiers will now see it as revoked.');
    }
}
