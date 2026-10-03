<?php

use App\Models\Credential;
use App\Services\ChainException;
use App\Services\CredentialCodec;
use App\Services\VeriCredChain;
use Illuminate\Support\Facades\Artisan;

Artisan::command('vericred:status', function (VeriCredChain $chain) {
    $status = $chain->status();

    if (! $status['online']) {
        $this->error($status['message']);

        return 1;
    }

    $this->info("Connected. Block #{$status['block']} on chain {$status['chain_id']}");
    $this->line("Contract: {$status['contract']}");
    $this->line("Issuer:   {$status['issuer_name']} ({$status['issuer']})");
})->purpose('Check the connection to the VeriCred blockchain');

// Pitch demo: a "diploma mill" wallet that was never accredited tries to anchor a fake diploma.
Artisan::command('vericred:forge-demo', function (VeriCredChain $chain) {
    $fakeMill = '0x3C44CdDdB6a900fa2b585dd299e03d12FA4293BC'; // Hardhat test account #2, never registered

    $fake = [
        'v' => '1', 'type' => 'Diploma', 'student_name' => 'Fake Graduate', 'student_id' => '00-0000-000000',
        'program' => 'Doctor of Medicine', 'honors' => 'Summa Cum Laude', 'gwa' => '1.00',
        'date_conferred' => '2026-06-15', 'issuer' => strtolower($fakeMill), 'salt' => CredentialCodec::salt(),
    ];

    $this->line("Diploma mill wallet {$fakeMill} tries to anchor a fake MD diploma...");

    try {
        $chain->issue(CredentialCodec::hash($fake), $fakeMill);
        $this->error('Unexpected: the forgery was accepted.');

        return 1;
    } catch (ChainException $e) {
        $this->info("REJECTED by the smart contract: {$e->getMessage()}");
    }
})->purpose('Show that unaccredited wallets cannot anchor credentials');

// The local Hardhat chain lives in memory, so restarting it wipes all anchors.
// After `npm run deploy`, this re-anchors every stored credential.
Artisan::command('vericred:sync', function (VeriCredChain $chain) {
    $anchored = $revoked = 0;

    try {
        foreach (Credential::all() as $credential) {
            $record = $chain->verify($credential->hash);

            if (! $record['exists']) {
                $tx = $chain->issue($credential->hash);
                $credential->update(['tx_hash' => $tx['tx'], 'block_number' => $tx['block']]);
                $anchored++;
            }

            if ($credential->isRevoked() && ! $chain->verify($credential->hash)['revoked']) {
                $credential->update(['revoke_tx_hash' => $chain->revoke($credential->hash)['tx']]);
                $revoked++;
            }
        }
    } catch (ChainException $e) {
        $this->error($e->getMessage());

        return 1;
    }

    $this->info("Re-anchored {$anchored} credential(s), re-applied {$revoked} revocation(s).");
})->purpose('Re-anchor stored credentials on a freshly deployed chain');
