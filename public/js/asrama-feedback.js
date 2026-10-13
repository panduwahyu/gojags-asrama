(() => {
    'use strict';
    const dialog = document.querySelector('[data-feedback-modal]');
    if (!dialog) return;
    const queue = [];
    let previousFocus;
    let previousOverflow;
    function render() {
        const item = queue[0];
        if (!item) {
            dialog.close();
            document.body.style.overflow = previousOverflow || '';
            if (previousFocus?.isConnected) previousFocus.focus();
            return;
        }
        dialog.dataset.kind = item.kind;
        dialog.querySelector('[data-feedback-title]').textContent = item.title;
        dialog.querySelector('[data-feedback-message]').textContent = item.message;
        dialog.querySelector('[data-feedback-icon]').textContent = item.kind === 'error' ? '!' : '✓';
        if (!dialog.open) {
            previousFocus = document.activeElement;
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            dialog.showModal();
        }
        dialog.querySelector('button').focus();
    }
    window.AsramaFeedback = {
        show(message, kind = 'success', title = kind === 'error' ? 'Gagal' : 'Berhasil') {
            queue.push({ message, kind, title }); render();
        },
    };
    function dismiss() { queue.shift(); render(); }
    dialog.querySelector('button').addEventListener('click', dismiss);
    dialog.addEventListener('cancel', event => { event.preventDefault(); dismiss(); });
    const payload = document.getElementById('feedback-payload');
    if (payload) {
        const data = JSON.parse(payload.textContent);
        if (data.errors.length) window.AsramaFeedback.show(data.errors.join('\n'), 'error');
        if (data.success) window.AsramaFeedback.show(data.success, 'success', data.success === 'Izin keluar berhasil diajukan.' ? 'Berhasil diajukan' : 'Berhasil');
    }
})();
