<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Minimal JSON-RPC client for the VeriCred contract.
 *
 * Transactions are sent with eth_sendTransaction from the school's wallet,
 * which works against a local Hardhat node (accounts are unlocked there).
 * A public testnet would need the transaction signed with the school's
 * private key before sending.
 */
class VeriCredChain
{
    private ?array $deployment = null;

    public function deployment(): array
    {
        if ($this->deployment === null) {
            $file = config('vericred.chain_file');

            if (! is_file($file)) {
                throw new ChainException('The VeriCred contract is not deployed yet. Run `npm run deploy` inside the blockchain folder.');
            }

            $this->deployment = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        }

        return $this->deployment;
    }

    public function contractAddress(): string
    {
        return $this->deployment()['contract'];
    }

    public function chainId(): int
    {
        return $this->deployment()['chain_id'];
    }

    /** The accredited school wallet this portal issues from. */
    public function issuerAddress(): string
    {
        return $this->deployment()['issuer']['address'];
    }

    /** Connection summary for the dashboard; never throws. */
    public function status(): array
    {
        try {
            $code = $this->rpc('eth_getCode', [$this->contractAddress(), 'latest']);

            if ($code === '0x') {
                return ['online' => false, 'message' => 'Blockchain node is running but the contract is missing. Run `npm run deploy`, then `php artisan vericred:sync`.'];
            }

            return [
                'online' => true,
                'block' => hexdec($this->rpc('eth_blockNumber')),
                'contract' => $this->contractAddress(),
                'chain_id' => $this->chainId(),
                'issuer' => $this->issuerAddress(),
                'issuer_name' => $this->issuerName($this->issuerAddress()),
            ];
        } catch (ChainException $e) {
            return ['online' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  string|null  $from  wallet to send from; defaults to the accredited school
     * @return array{tx: string, block: int}
     */
    public function issue(string $hash, ?string $from = null): array
    {
        return $this->transact('issue', $hash, $from);
    }

    /** @return array{tx: string, block: int} */
    public function revoke(string $hash): array
    {
        return $this->transact('revoke', $hash);
    }

    /** @return array{exists: bool, issuer: string, issued_at: int, revoked: bool} */
    public function verify(string $hash): array
    {
        $words = $this->words($this->call('verify', $this->strip($hash)));

        return [
            'exists' => hexdec($words[0]) === 1,
            'issuer' => '0x'.substr($words[1], 24),
            'issued_at' => hexdec($words[2]),
            'revoked' => hexdec($words[3]) === 1,
        ];
    }

    public function isIssuer(string $address): bool
    {
        return hexdec($this->words($this->call('isIssuer', $this->padAddress($address)))[0]) === 1;
    }

    public function issuerName(string $address): string
    {
        // ABI-encoded string: [offset][length][utf-8 bytes...]
        $hex = substr($this->call('issuerName', $this->padAddress($address)), 2);
        $offset = hexdec(substr($hex, 0, 64)) * 2;
        $length = hexdec(substr($hex, $offset, 64));

        return (string) hex2bin(substr($hex, $offset + 64, $length * 2));
    }

    private function transact(string $function, string $hash, ?string $from = null): array
    {
        $tx = $this->rpc('eth_sendTransaction', [[
            'from' => $from ?? $this->issuerAddress(),
            'to' => $this->contractAddress(),
            'data' => $this->selector($function).$this->strip($hash),
            'gas' => '0x30d40',
        ]]);

        for ($i = 0; $i < 50; $i++) {
            $receipt = $this->rpc('eth_getTransactionReceipt', [$tx]);

            if ($receipt !== null) {
                if (($receipt['status'] ?? '0x0') !== '0x1') {
                    throw new ChainException("Transaction {$tx} was reverted.");
                }

                return ['tx' => $tx, 'block' => hexdec($receipt['blockNumber'])];
            }

            usleep(200_000);
        }

        throw new ChainException("Transaction {$tx} was not mined in time.");
    }

    private function call(string $function, string $args): string
    {
        return $this->rpc('eth_call', [[
            'to' => $this->contractAddress(),
            'data' => $this->selector($function).$args,
        ], 'latest']);
    }

    private function rpc(string $method, array $params = []): mixed
    {
        $url = config('vericred.rpc_url') ?: $this->deployment()['rpc_url'];

        try {
            $response = Http::timeout(10)->post($url, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => $method,
                'params' => $params,
            ]);
        } catch (ConnectionException) {
            throw new ChainException("Cannot reach the blockchain node at {$url}. Start it with `npm run chain` in the blockchain folder.");
        }

        $body = $response->json();

        if (isset($body['error'])) {
            $message = $body['error']['data']['message'] ?? $body['error']['message'] ?? 'Unknown RPC error';

            if (preg_match("/reverted with reason string '([^']+)'/", $message, $m)) {
                $message = $m[1];
            }

            throw new ChainException($message);
        }

        return $body['result'] ?? null;
    }

    private function selector(string $function): string
    {
        return $this->deployment()['selectors'][$function];
    }

    private function words(string $hex): array
    {
        return str_split(substr($hex, 2), 64);
    }

    private function strip(string $hash): string
    {
        return strtolower(preg_replace('/^0x/i', '', $hash));
    }

    private function padAddress(string $address): string
    {
        return str_pad($this->strip($address), 64, '0', STR_PAD_LEFT);
    }
}
