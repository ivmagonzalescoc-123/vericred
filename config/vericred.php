<?php

return [

    /*
    | Written by `npm run deploy` in /blockchain: contract address, accredited
    | issuer wallet and ABI function selectors.
    */
    'chain_file' => env('VERICRED_CHAIN_FILE', storage_path('app/vericred-chain.json')),

    // Overrides the RPC URL from the chain file (e.g. a testnet endpoint).
    'rpc_url' => env('VERICRED_RPC_URL'),

    /*
    | Base URL encoded in printed QR codes. Must be reachable from the
    | verifier's phone (LAN IP or an ngrok URL), not just "startup.test".
    */
    'public_url' => env('VERICRED_PUBLIC_URL'),

];
