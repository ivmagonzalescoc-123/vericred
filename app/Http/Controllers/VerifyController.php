<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Services\ChainException;
use App\Services\CredentialCodec;
use App\Services\VeriCredChain;
use Illuminate\Http\Request;

class VerifyController extends Controller
{
    public function home()
    {
        return view('home');
    }

    /** Verify an uploaded .vericred.json file or pasted JSON. */
    public function check(Request $request, VeriCredChain $chain)
    {
        $request->validate([
            'file' => ['nullable', 'file', 'max:64'],
            'json' => ['nullable', 'string', 'max:20000'],
        ]);

        $raw = $request->hasFile('file') ? $request->file('file')->get() : (string) $request->input('json');
        $credential = CredentialCodec::parse($raw);

        if ($credential === null) {
            return back()->withInput()->withErrors(['json' => 'That is not a VeriCred credential. Upload the .vericred.json file or paste its contents.']);
        }

        return $this->result($credential, $chain);
    }

    /** Verify straight from a scanned QR code link. */
    public function link(Request $request, VeriCredChain $chain)
    {
        $credential = CredentialCodec::fromUrlToken((string) $request->query('c'));

        if ($credential === null) {
            return redirect()->route('home')->withErrors(['json' => 'This QR code does not contain a valid VeriCred credential.']);
        }

        return $this->result($credential, $chain);
    }

    private function result(array $credential, VeriCredChain $chain)
    {
        // Recompute the fingerprint from what the verifier holds. Never trust a hash supplied with the document.
        $hash = CredentialCodec::hash($credential);
        $view = ['credential' => $credential, 'hash' => $hash, 'record' => null, 'issuerName' => null];

        try {
            $record = $chain->verify($hash);
            $view['record'] = $record;
            $view['contract'] = $chain->contractAddress();
            $view['chainId'] = $chain->chainId();

            if (! $record['exists']) {
                $view['status'] = 'invalid';
                $view['reason'] = 'No accredited school has anchored this exact document. It was altered after issuance, or it was never issued.';
            } elseif (strcasecmp($record['issuer'], $credential['issuer']) !== 0) {
                $view['status'] = 'invalid';
                $view['reason'] = 'The fingerprint was anchored by a different school than the one this document claims.';
            } else {
                $view['issuerName'] = $chain->issuerName($record['issuer']);
                $view['txHash'] = Credential::where('hash', $hash)->value('tx_hash');

                if ($record['revoked']) {
                    $view['status'] = 'revoked';
                    $view['reason'] = 'The issuing school has revoked this credential.';
                } elseif (! $chain->isIssuer($record['issuer'])) {
                    $view['status'] = 'unaccredited';
                    $view['reason'] = 'The document is unaltered, but its issuer is no longer accredited on VeriCred.';
                } else {
                    $view['status'] = 'valid';
                }
            }
        } catch (ChainException $e) {
            $view['status'] = 'offline';
            $view['reason'] = $e->getMessage();
        }

        return view('verify.result', $view);
    }
}
