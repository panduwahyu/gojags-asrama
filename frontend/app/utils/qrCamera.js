/**
 * Decode camera frames locally; only the decoded QR token leaves the browser.
 * @param {HTMLVideoElement} video
 * @param {(token: string) => void} onToken
 * @param {AbortSignal} signal
 */
export async function startQrCamera(video, onToken, signal) {
  if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
    throw new Error('Kamera memerlukan HTTPS atau localhost. Buka situs melalui HTTPS.');
  }
  const { default: jsQR } = await import('jsqr');
  if (signal.aborted) return;
  const stream = await navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } });
  let timer = 0;
  const stop = () => { window.clearTimeout(timer); stream.getTracks().forEach(track => track.stop()); if (video.srcObject === stream) video.srcObject = null; };
  signal.addEventListener('abort', stop, { once: true });
  if (signal.aborted) { stop(); return; }
  video.srcObject = stream;
  try { await video.play(); } catch (error) { stop(); if (!signal.aborted) throw error; return; }
  const canvas = document.createElement('canvas');
  const context = canvas.getContext('2d', { willReadFrequently: true });
  if (!context) { stop(); throw new Error('Browser tidak mendukung pemindai kamera.'); }
  const scan = () => {
    if (signal.aborted) return;
    if (video.readyState >= 2 && video.videoWidth) {
      canvas.width = Math.min(video.videoWidth, 640);
      canvas.height = Math.round(video.videoHeight * canvas.width / video.videoWidth);
      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      const frame = context.getImageData(0, 0, canvas.width, canvas.height);
      const code = jsQR(frame.data, frame.width, frame.height, { inversionAttempts: 'dontInvert' });
      if (code?.data) { stop(); onToken(code.data); return; }
    }
    timer = window.setTimeout(scan, 150);
  };
  scan();
}
