import { startQrCamera } from './qr-camera.js';

for (const root of document.querySelectorAll('[data-qr-scanner]')) {
  const video = root.querySelector('video');
  const start = root.querySelector('[data-qr-start]');
  const cancel = root.querySelector('[data-qr-stop]');
  const status = root.querySelector('[data-qr-status]');
  const form = root.querySelector('form');
  let controller;
  let submitting = false;
  const stop = () => {
    controller?.abort(); video.hidden = true; cancel.hidden = true; start.hidden = false;
    if (!submitting) status.textContent = '';
  };
  start.addEventListener('click', async () => {
    if (submitting) return;
    controller = new AbortController();
    const current = controller;
    start.hidden = true; cancel.hidden = false; video.hidden = false;
    status.textContent = 'Mencari QR... Pastikan kode terlihat utuh dan cukup terang.';
    try {
      await startQrCamera(video, token => {
        stop();
        if (!/^gojags-room:v1:[a-f0-9]{64}$/.test(token)) { status.textContent = 'QR bukan QR check-in/check-out Gojags. Silakan scan ulang.'; return; }
        submitting = true; start.disabled = true;
        status.textContent = 'Memvalidasi QR dan mencatat kehadiran...';
        form.elements.qr_token.value = token;
        form.requestSubmit();
      }, current.signal);
    } catch (error) {
      if (current.signal.aborted) return;
      stop();
      status.textContent = error.name === 'NotAllowedError' ? 'Izin kamera ditolak. Izinkan kamera pada pengaturan browser, lalu coba lagi.' : error.name === 'NotFoundError' ? 'Kamera tidak ditemukan.' : error.name === 'NotReadableError' ? 'Kamera sedang digunakan aplikasi lain.' : error.message || 'Kamera tidak dapat dibuka.';
    }
  });
  cancel.addEventListener('click', stop);
  window.addEventListener('pagehide', stop);
  document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); });
}
