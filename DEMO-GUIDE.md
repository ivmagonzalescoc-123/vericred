# VeriCred Demo Guide

A step-by-step guide to running VeriCred and presenting it. No blockchain experience needed.

---

## 1. VeriCred in one minute

**The problem:** employers can't easily tell whether a diploma is real. Checking means calling the school and waiting, and fake diplomas are easy to buy or edit.

**The idea:** every diploma gets a **digital fingerprint**, a long code like `e7c6e177...`.

- Every document has its own fingerprint.
- Change even **one letter or number** and the fingerprint becomes completely different.
- You can't work backward from the fingerprint to the document.

The school writes that fingerprint into the **blockchain**. Think of it as a **public logbook written in permanent ink**:

- Anyone can read it.
- Nobody can erase or change what's written.
- Only **approved schools** can write in it.

To verify a diploma, VeriCred takes the fingerprint of the document the employer is holding and looks it up in the logbook.

| What happens | What the employer sees |
|---|---|
| Fingerprint found, written by an approved school | ✅ **Authentic credential** |
| The diploma was edited, so the fingerprint doesn't match | ❌ **Not verified** |
| The school cancelled the diploma | ⚠️ **Credential revoked** |

**Privacy:** only the fingerprint goes on the blockchain. Names, grades and student IDs stay with the school and the graduate. A secret random value (the "salt") is mixed in, so nobody can guess a fingerprint by trying names.

---

## 2. What you are turning on

| Piece | Layman's term | What it looks like |
|---|---|---|
| **Blockchain** (Hardhat) | The public logbook | A black window full of text. **Keep it open.** |
| **Website** (Laravel) | The office where the registrar issues diplomas and employers check them | A browser page at http://localhost:8000 |

For the demo, the blockchain runs **on your own laptop**. It behaves like a real blockchain but is free and private, and it **works without internet**, so venue Wi‑Fi doesn't matter.

---

## 3. First-time setup (only once per laptop)

Skip this if the project already runs on your laptop.

Requirements: [Laragon](https://laragon.org) (it includes PHP, Composer and Node.js).

Open a terminal in the `Startup` folder (in VS Code: **Terminal → New Terminal**) and run:

```bash
composer install
cd blockchain
npm install
cd ..
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
```

> **If `composer install` reads the wrong project** (it mentions another project's name): Windows has a system variable named `Composer` that confuses it. Remove that variable in *System Properties → Environment Variables*, then open a new terminal.

---

## 4. Turning it on

1. Open the folder `C:\laragon\www\Startup`.
2. **Double-click `start-demo.bat`.**
3. Two black windows appear:
   - **"VeriCred Blockchain (keep open)":** the logbook. It lists 20 test accounts at the start; that's normal.
   - **The main window:** wait until it says `Open http://localhost:8000`.
4. Open your browser and go to **http://localhost:8000**.

**Login for the school registrar:**

| Email | Password |
|---|---|
| `registrar@vericred.test` | `password` |

**To turn it off,** close both black windows.

> The demo blockchain starts with an empty logbook each time. `start-demo.bat` automatically re-copies every saved diploma into it, so you don't need to do anything.

### Starting it manually (if the .bat file doesn't work)

Use two terminals in the `Startup` folder.

**Terminal 1** (the blockchain, keep it open):
```bash
cd blockchain
npx hardhat node
```

**Terminal 2:**
```bash
cd blockchain
npm run deploy
cd ..
php artisan vericred:sync
php artisan serve
```

Then open http://localhost:8000.

---

## 5. The demo flow (about 3 minutes)

**Tip:** put the browser and the **blockchain window side by side** on screen. Judges like seeing the logbook react live.

### Step 1: The school issues a diploma

1. Click **School login** and sign in.
2. You should see a **green dot: "Blockchain connected"**.
3. Click **+ Issue credential** and fill in a sample graduate:
   - Name: *Juan P. Dela Cruz*
   - Program: *Bachelor of Science in Information Technology*
   - Honors: *Cum Laude*, GWA: *1.45*
4. Click **Issue & anchor on blockchain**.

👉 **Point at the black window:** a new entry appears, `Contract call: VeriCred#issue … Block #3`.

🗣 *"The school just wrote this diploma's fingerprint into the blockchain. Only the fingerprint: no name, no grades."*

### Step 2: The graduate receives the diploma

You're now on the certificate page with a **QR code**.

🗣 *"The graduate gets this printable diploma with a QR code, plus a digital file they can send to employers."*

Optional: click **Download .vericred.json** to show the digital file.

### Step 3: The employer verifies it

Click **Open verify link**. It does the same thing as scanning the QR code.

The result is a big green **✔ Authentic credential**.

🗣 *"An employer anywhere in the world gets this answer in seconds, without calling the school."*

**Other ways to verify.** All are on the home page (**Verify** in the top menu):

| Option | How | Good for showing |
|---|---|---|
| **Scan QR with camera** | Click it, allow camera access, hold the QR up to the webcam | The most impressive live demo |
| **Upload photo or PDF** | Choose a phone photo of the printed QR, or the certificate saved as PDF | "Even a photo or a scanned PDF works" |
| **Upload .vericred.json** | Same button, choose the downloaded file | The digital-file route |
| **Paste** | Open "Or paste the credential file's contents" | Backup if uploads fail |

To get a PDF for the demo, open the certificate page, click **Print / Save as PDF**, and set the printer to **Save as PDF**.

### Step 4: Someone edits the grade

1. On the same result page, open **"Tamper test"** at the bottom.
2. In the text box, change `"gwa": "1.45"` to `"gwa": "1.05"`.
3. Click **Verify edited document**.

The result is a red **✖ Not verified**.

🗣 *"We changed just one number. The fingerprint changed completely, so the system knows it was altered."*

### Step 5: A fake school tries to issue a diploma

In a terminal in the `Startup` folder, run:

```bash
php artisan vericred:forge-demo
```

The output says **"REJECTED by the smart contract: VeriCred: not an accredited issuer"**.

🗣 *"A diploma mill tried to write a fake medical diploma into the logbook. The blockchain refused, because only approved schools can write in it."*

### Step 6: The school cancels a diploma

1. Go back to **Registrar** and open Juan's record.
2. Under **Revoke credential**, type a reason (e.g. *Issued in error*) and click **Revoke on blockchain**.
3. Click **Open verify link** again.

The result is **⚠ Credential revoked**.

🗣 *"If a diploma was issued by mistake or obtained by fraud, the school can cancel it, and every employer sees that immediately."*

### Step 7: The closing line

🗣 *"No personal data is on the blockchain. Even if our servers shut down, anyone can still verify a diploma, because the proof lives on a public blockchain and not in our database."*

---

## 6. Scanning the QR with a camera or phone (optional)

### Laptop webcam (easiest)

On the laptop, open http://localhost:8000, click **Scan QR with camera**, and allow camera access. Hold the printed diploma, or a phone showing the QR, up to the webcam.

> **Why it only works on localhost or https:** browsers allow live camera access only on secure pages. `localhost` counts as secure; `http://192.168.x.x` does not.

### Phone

On a phone opened through your laptop's IP (`http://192.168.x.x:8000`), live camera scanning is blocked for the reason above. Instead, tap **Upload photo or PDF**, choose **Camera**, and take a picture of the QR. It's verified the same way.

For full live scanning on a phone, use an **https** link, e.g. from ngrok (included in Laragon), and set `VERICRED_PUBLIC_URL` to that https link.

### Scanning with the phone's own camera app

The phone's normal camera app can also scan the QR and open the link directly. A phone can't open `localhost`, because that only means "this laptop". To set it up:

1. Connect the laptop and the phone to the **same Wi‑Fi or phone hotspot**.
2. Find the laptop's IP address: open Command Prompt, type `ipconfig`, and look for **IPv4 Address** (e.g. `192.168.1.5`).
3. Open the `.env` file in the `Startup` folder and set:
   ```
   VERICRED_PUBLIC_URL=http://192.168.1.5:8000
   ```
4. Close everything, run `start-demo.bat` again, and issue a **new** diploma.
5. Scan its QR code with the phone camera.

> **Practice with a phone hotspot.** Venue Wi‑Fi often blocks devices from talking to each other.

---

## 7. Useful commands

Run these in a terminal in the `Startup` folder.

| Command | What it does |
|---|---|
| `php artisan vericred:status` | Checks whether the website can reach the blockchain |
| `php artisan vericred:forge-demo` | Shows a fake school being rejected (Step 5) |
| `php artisan vericred:sync` | Re-copies saved diplomas into a freshly started blockchain |
| `php artisan migrate:fresh --seed` | **Deletes all sample diplomas** for a clean demo |

To change the school name shown on certificates (default: *Demo University*), run this in the `blockchain` folder before deploying:

```bash
set ISSUER_NAME=Your School Name
npm run deploy
```

---

## 8. Troubleshooting

| Problem | Fix |
|---|---|
| Red dot: **"Blockchain offline"** | The blockchain window was closed. Run `start-demo.bat` again. |
| "Contract is missing" message | The blockchain restarted. Run `npm run deploy` in `blockchain`, then `php artisan vericred:sync`. |
| Browser can't reach `localhost:8000` | The main window was closed or hasn't finished starting. Wait for `Open http://localhost:8000`. |
| "Address already in use" / port error | An old copy is still running. Close all black windows (or restart the laptop) and try again. |
| "Live camera scanning needs a secure (https) link" | You're on an `http://192.168…` address. Use **Upload photo or PDF** and take a picture, or open the site via `localhost` or an https link. |
| "Camera permission was denied" | Click the camera icon in the browser's address bar, allow the camera, and reload. |
| "No QR code was found" | Retake the photo closer, sharper and with better light, with the whole QR in the frame. For PDFs, use the certificate's **Print / Save as PDF**. |
| Phone can't open the QR link | Check that the phone is on the same network and `VERICRED_PUBLIC_URL` uses the laptop's IP. Firewall may also block it: allow PHP when Windows asks. |

---

## 9. Before the pitch checklist

- [ ] Rehearse the full flow at least **twice**
- [ ] Run `php artisan migrate:fresh --seed` for a clean database
- [ ] Set the school name with `ISSUER_NAME`
- [ ] Test the phone QR scan on the **hotspot** you will use
- [ ] Laptop charged, with a charger packed
- [ ] Have a screen recording of the demo as a backup in case something fails live
