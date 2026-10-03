@echo off
REM Starts the local VeriCred blockchain, deploys the contract, re-anchors
REM stored credentials, and serves the app on your network for QR scanning.
cd /d "%~dp0"

start "VeriCred Blockchain (keep open)" cmd /k "cd blockchain && npx hardhat node"

echo Waiting for the blockchain node...
:wait
timeout /t 2 /nobreak >nul
curl -s -X POST -H "Content-Type: application/json" --data "{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"eth_chainId\",\"params\":[]}" http://127.0.0.1:8545 >nul 2>&1 || goto wait

pushd blockchain
call npm run deploy
popd

php artisan vericred:sync
php artisan vericred:status

echo.
echo Open http://localhost:8000  (registrar: registrar@vericred.test / password)
echo For phone QR scanning, set VERICRED_PUBLIC_URL in .env to http://YOUR-LAN-IP:8000
echo.
php artisan serve --host=0.0.0.0 --port=8000
