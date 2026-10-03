// Reads a VeriCred QR code in the browser from the camera, a photo/image, or a PDF,
// then opens the normal verification page (/v?c=<token>). Nothing is uploaded until
// a QR code is found, and it works offline (jsQR + pdf.js are served locally).
(function () {
    const root = document.getElementById('scan-tools');
    if (!root) return;

    const form = document.getElementById('verify-form');
    const fileInput = document.getElementById('file');
    const status = document.getElementById('scan-status');
    const cameraBox = document.getElementById('camera-box');
    const video = document.getElementById('camera-video');
    const work = document.createElement('canvas');
    const ctx = work.getContext('2d', { willReadFrequently: true });
    let stream = null;

    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = root.dataset.pdfWorker;
    }

    function setStatus(message, kind) {
        status.textContent = message || '';
        status.className = 'scan-status ' + (kind || '');
        status.hidden = !message;
    }

    /** Pull the credential token out of the QR text and open its verification page. */
    function openFromQr(text) {
        let token = null;
        try {
            token = new URL(text, location.href).searchParams.get('c');
        } catch (e) { /* not a URL */ }

        if (!token) {
            setStatus('A QR code was found, but it is not a VeriCred code.', 'err');
            return false;
        }

        setStatus('QR code found. Checking the blockchain…', 'ok');
        location.href = root.dataset.verifyUrl + '?c=' + encodeURIComponent(token);
        return true;
    }

    /** Look for a QR code in an image-like source, trying a few sizes (large photos decode better scaled down). */
    function findQr(source, width, height, inversion, sizes) {
        const tried = new Set();
        for (const max of sizes || [1200, 1800, 800, 2600]) {
            const scale = Math.min(1, max / Math.max(width, height));
            if (tried.has(scale)) continue;
            tried.add(scale);

            work.width = Math.round(width * scale);
            work.height = Math.round(height * scale);
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, work.width, work.height);
            ctx.drawImage(source, 0, 0, work.width, work.height);

            const pixels = ctx.getImageData(0, 0, work.width, work.height);
            const code = jsQR(pixels.data, pixels.width, pixels.height, { inversionAttempts: inversion || 'attemptBoth' });
            if (code) return code.data;
        }
        return null;
    }

    function loadImage(file) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error('This image could not be opened. Try a JPG or PNG.'));
            img.src = URL.createObjectURL(file);
        });
    }

    async function scanImage(file) {
        const img = await loadImage(file);
        return findQr(img, img.naturalWidth, img.naturalHeight);
    }

    async function scanPdf(file) {
        if (!window.pdfjsLib) throw new Error('The PDF reader did not load. Refresh the page and try again.');

        const pdf = await pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
        const pages = Math.min(pdf.numPages, 5);

        for (let n = 1; n <= pages; n++) {
            setStatus(`Looking for the QR code on page ${n} of ${pdf.numPages}…`);
            const page = await pdf.getPage(n);
            const viewport = page.getViewport({ scale: 3 });
            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

            const text = findQr(canvas, canvas.width, canvas.height);
            if (text) return text;
        }
        return null;
    }

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;

        const name = file.name.toLowerCase();

        // Credential files are checked by the server as before.
        if (name.endsWith('.json') || file.type === 'application/json') {
            setStatus('Checking the credential file…', 'ok');
            form.submit();
            return;
        }

        const isPdf = name.endsWith('.pdf') || file.type === 'application/pdf';
        if (!isPdf && !file.type.startsWith('image/')) {
            setStatus('Please choose a photo/image of the QR code, a PDF, or a .vericred.json file.', 'err');
            fileInput.value = '';
            return;
        }

        stopCamera();
        setStatus(isPdf ? 'Reading the PDF…' : 'Looking for the QR code in the image…');

        try {
            const text = isPdf ? await scanPdf(file) : await scanImage(file);
            if (text) {
                openFromQr(text);
            } else {
                setStatus('No QR code was found. Make sure the whole QR code is visible, sharp and well lit, then try again.', 'err');
            }
        } catch (e) {
            setStatus(e.message || 'That file could not be read.', 'err');
        }

        fileInput.value = '';
    });

    async function startCamera() {
        if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Live camera scanning needs a secure (https) link or localhost. On a phone, tap "Upload photo or PDF" and take a picture of the QR code instead.', 'warn');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
        } catch (e) {
            const reason = e.name === 'NotAllowedError' ? 'Camera permission was denied. Allow camera access in your browser and try again.'
                : e.name === 'NotFoundError' ? 'No camera was found on this device.'
                : 'The camera could not be started: ' + e.message;
            setStatus(reason, 'err');
            return;
        }

        video.srcObject = stream;
        await video.play();
        cameraBox.hidden = false;
        setStatus('Point the camera at the QR code on the diploma.');
        scanFrame();
    }

    function stopCamera() {
        if (stream) stream.getTracks().forEach(t => t.stop());
        stream = null;
        video.srcObject = null;
        cameraBox.hidden = true;
    }

    function scanFrame() {
        if (!stream) return;

        if (video.readyState >= 2 && video.videoWidth) {
            const text = findQr(video, video.videoWidth, video.videoHeight, 'dontInvert', [1280]);
            if (text && openFromQr(text)) {
                stopCamera();
                return;
            }
        }
        setTimeout(scanFrame, 200);
    }

    document.getElementById('btn-camera').addEventListener('click', startCamera);
    document.getElementById('btn-camera-stop').addEventListener('click', () => {
        stopCamera();
        setStatus('');
    });
    window.addEventListener('pagehide', stopCamera);
})();
