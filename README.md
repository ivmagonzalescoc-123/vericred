# VeriCred

> **New to the project?** Start with the [Demo Guide](DEMO-GUIDE.md): what VeriCred is in plain language, how to turn it on, and the pitch demo flow.

Blockchain-anchored academic credentials. Schools anchor a SHA-256 fingerprint of each diploma or TOR on a smart contract. Employers, agencies and embassies anywhere can verify it in seconds. No personal data is stored on-chain.

## How it works

| Where | What is stored | Who sees it |
|---|---|---|
| Blockchain (`VeriCred.sol`) | Only `sha256(credential + secret salt)`, the issuing school's wallet, and a timestamp | Everyone, but it is meaningless on its own |
| School database | Full credential record | Registrar only |
| Graduate | `.vericred.json` file + QR code (data + salt) | Whoever the graduate shares it with |

**Verification:** VeriCred recomputes the hash from the document the verifier holds and checks three things on the chain:

1. The hash exists, so the document is unaltered.
2. It was anchored by an accredited school wallet.
3. It has not been revoked.

**Protections:**
- **Random salt:** the hash cannot be brute-forced back to a name or grade.
- **Issuer allow-list:** only accredited wallets can call `issue()`.
- **Revocation:** only the issuing school can call `revoke()`.
- **No personal data on-chain:** the system is compliant with the Data Privacy Act (RA 10173) by design.

## Project layout

```
blockchain/                 Hardhat project
  contracts/VeriCred.sol    smart contract
  scripts/deploy.js         deploys + accredits the demo school, writes storage/app/vericred-chain.json
app/Services/
  CredentialCodec.php       canonical JSON, salt, SHA-256
  VeriCredChain.php         JSON-RPC client for the contract
app/Http/Controllers/
  VerifyController.php      public verification (upload, paste, QR link)
  RegistrarController.php   issue, certificate + QR, download, revoke
routes/console.php          vericred:status, vericred:sync, vericred:forge-demo
```

## Running it

Requirements: PHP 8.2+, Composer, Node 18+ (all included with Laragon).

**First time only:**
```bash
composer install
cd blockchain && npm install && cd ..
copy .env.example .env        # skip if .env exists
php artisan key:generate
php artisan migrate --seed
```

**Every time:** double-click `start-demo.bat`, or run these manually:
```bash
cd blockchain && npm run chain           # terminal 1 – keep open
cd blockchain && npm run deploy          # terminal 2
php artisan vericred:sync
php artisan serve
```

- Registrar login: `registrar@vericred.test` / `password`
- To change the school name, run `set ISSUER_NAME=Your School Name` before `npm run deploy`.

> The local Hardhat chain lives in memory, so restarting it wipes all anchors. `npm run deploy` followed by `php artisan vericred:sync` re-anchors every credential in the database.

**Phone QR scanning:** phones cannot open `startup.test` or `localhost`. Set `VERICRED_PUBLIC_URL=http://<your-laptop-LAN-IP>:8000` in `.env` and put the phone on the same Wi-Fi. Alternatively, use the ngrok bundled with Laragon and set the variable to the ngrok URL.

## Pitch demo script (about 3 minutes)

1. **Issue:** log in as registrar and issue a diploma. Point out the block number, then open the certificate with its QR code.
2. **Verify:** scan the QR with a phone, or click *Open verify link*. The result is a green **Authentic credential**.
3. **Tamper:** on the result page, open *Tamper test* and change the GWA from `1.45` to `1.05`. The result is a red **Not verified**.
4. **Forge:** run `php artisan vericred:forge-demo`. A diploma mill wallet tries to anchor a fake MD diploma, and the smart contract **rejects** it.
5. **Revoke:** revoke the credential and verify again. The result is **Credential revoked**.
6. **Privacy:** show `storage/app/vericred-chain.json` and a hash. Explain that no name and no grades are on the chain.

## Roadmap after the MVP

- Deploy to a public testnet (Polygon Amoy). This requires signing transactions with the school's private key, kept in a key vault or multisig.
- Merkle batching: one transaction per graduating batch, which lowers cost.
- AI check for tampered scanned paper documents (OCR plus image forensics).
- W3C Verifiable Credentials / Open Badges format, and a student wallet app.
- Integration with CHED and DFA apostille workflows.
