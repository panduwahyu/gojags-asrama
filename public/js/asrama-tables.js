(() => {
  'use strict';
  // Laravel tables: fetch HTML and replace only the results and pagination.
  document.querySelectorAll('[data-async-table]').forEach(section => {
    const form = section.querySelector('.search-form');
    const input = form.querySelector('[name="q"]');
    const notice = document.createElement('p');
    notice.className = 'table-feedback';
    notice.setAttribute('role', 'status');
    notice.setAttribute('aria-live', 'polite');
    form.after(notice);
    let controller;
    let timer;
    let sequence = 0;

    async function load(url, historyMode = 'push') {
      clearTimeout(timer);
      controller?.abort();
      controller = new AbortController();
      const request = ++sequence;
      section.setAttribute('aria-busy', 'true');
      notice.textContent = 'Memuat data…';
      try {
        const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'text/html' }, credentials: 'same-origin' });
        if (request !== sequence) return;
        if (response.redirected && new URL(response.url).pathname === '/login') {
          window.location.assign(response.url);
          return;
        }
        if (!response.ok) throw new Error('Request failed');
        const html = new DOMParser().parseFromString(await response.text(), 'text/html');
        if (request !== sequence) return;
        const replacement = html.querySelector('[data-async-table]');
        if (!replacement?.querySelector('.table-wrap') || !replacement.querySelector('.pagination')) throw new Error('Invalid results');
        section.querySelector('.table-wrap').replaceWith(replacement.querySelector('.table-wrap'));
        section.querySelector('.pagination').replaceWith(replacement.querySelector('.pagination'));
        const monitoring = html.querySelector('[data-room-monitoring]');
        if (monitoring) document.querySelector('[data-room-monitoring]')?.replaceWith(monitoring);
        form.querySelector('a')?.remove();
        const reset = replacement.querySelector('.search-form a');
        if (reset) form.append(reset);
        if (historyMode !== 'none') history[historyMode === 'replace' ? 'replaceState' : 'pushState']({}, '', url);
        notice.textContent = 'Data diperbarui. ' + section.querySelector('[data-page-indicator]').getAttribute('aria-label');
      } catch (error) {
        if (request === sequence && error.name !== 'AbortError') notice.textContent = 'Data gagal diperbarui. Coba cari atau pilih halaman kembali.';
      } finally {
        if (request === sequence) section.setAttribute('aria-busy', 'false');
      }
    }
    function search(historyMode) {
      const url = new URL(window.location.href);
      url.searchParams.delete('page');
      url.searchParams.set('per_page', section.querySelector('[name="per_page"]').value);
      input.value ? url.searchParams.set('q', input.value) : url.searchParams.delete('q');
      load(url, historyMode);
    }
    form.addEventListener('submit', event => { event.preventDefault(); search('push'); });
    section.addEventListener('change', event => {
      if (event.target.matches('[name="per_page"]')) search('push');
    });
    input.addEventListener('input', () => {
      clearTimeout(timer);
      // Invalidate the previous query immediately, including while debouncing.
      controller?.abort();
      sequence++;
      section.setAttribute('aria-busy', 'false');
      timer = setTimeout(() => search('replace'), 300);
    });
    section.addEventListener('click', event => {
      const link = event.target.closest('.pagination a, .search-form a, a[data-sort]');
      if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      if (link.closest('.search-form')) input.value = '';
      const url = new URL(link.href);
      if (link.matches('[data-sort]')) {
        input.value ? url.searchParams.set('q', input.value) : url.searchParams.delete('q');
        url.searchParams.set('per_page', section.querySelector('[name="per_page"]').value);
      }
      load(url);
    });
    window.addEventListener('popstate', () => {
      input.value = new URL(window.location.href).searchParams.get('q') || '';
      load(new URL(window.location.href), 'none');
    });
    if (section.hasAttribute('data-room-live')) setInterval(() => {
      if (!document.hidden && section.getAttribute('aria-busy') !== 'true' && input.value === (new URL(location.href).searchParams.get('q') || '')) load(new URL(location.href), 'none');
    }, 15000);
  });

  // Legacy pages contain local sample rows; filter and paginate them in place.
  document.querySelectorAll('table[data-local-table]').forEach(table => {
    const body = table.tBodies[0];
    const form = document.createElement('form');
    form.className = 'local-table-search';
    const input = document.createElement('input');
    input.type = 'search';
    input.placeholder = 'Cari data…';
    input.setAttribute('aria-label', 'Cari ' + table.dataset.localTable);
    const submit = document.createElement('button');
    submit.type = 'submit'; submit.textContent = 'Cari';
    const reset = document.createElement('button');
    reset.type = 'button'; reset.textContent = 'Reset'; reset.hidden = true;
    form.append(input, submit, reset);
    const controls = document.createElement('div');
    controls.className = 'local-table-pagination';
    const previous = document.createElement('button');
    previous.type = 'button'; previous.textContent = 'Sebelumnya';
    const next = document.createElement('button');
    next.type = 'button'; next.textContent = 'Berikutnya';
    const label = document.createElement('span');
    label.setAttribute('role', 'status'); label.setAttribute('aria-live', 'polite');
    const first = document.createElement('button');
    first.type = 'button'; first.textContent = 'Awal';
    const last = document.createElement('button');
    last.type = 'button'; last.textContent = 'Akhir';
    const sizeLabel = document.createElement('label');
    sizeLabel.textContent = 'Baris per halaman ';
    const size = document.createElement('select');
    size.setAttribute('aria-label', 'Baris per halaman');
    [10, 50, 100].forEach(value => size.add(new Option(String(value), String(value))));
    sizeLabel.append(size);
    const empty = document.createElement('p');
    empty.textContent = 'Data tidak ditemukan'; empty.hidden = true;
    controls.append(sizeLabel, first, previous, label, next, last);
    table.parentElement.before(form);
    table.parentElement.after(controls);
    controls.after(empty);
    let page = 1;
    let sortIndex = -1;
    let sortDirection = 'asc';
    const headers = Array.from(table.tHead.rows[0].cells);
    const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
    function cellValue(row, index) {
      if (row.cells[index].dataset.sortValue !== undefined) return row.cells[index].dataset.sortValue;
      const text = row.cells[index].textContent.trim();
      const date = text.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (date) return Number(date[3] + date[2] + date[1]);
      if (/^(?:Rp\s*)?[\d.,]+(?:\s*Kg)?$/i.test(text)) {
        return Number(text.replace(/Rp|Kg|\s/gi, '').replace(/\.(?=\d{3}(?:\D|$))/g, '').replace(',', '.'));
      }
      return text;
    }
    headers.forEach((header, index) => {
      if (header.hasAttribute('data-no-sort')) return;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'local-table-sort';
      button.textContent = header.textContent.trim() + ' ';
      const icon = document.createElement('span');
      icon.setAttribute('aria-hidden', 'true');
      button.append(icon);
      header.replaceChildren(button);
      button.addEventListener('click', () => {
        sortDirection = sortIndex === index && sortDirection === 'asc' ? 'desc' : 'asc';
        sortIndex = index; page = 1; render();
      });
    });
    table.addEventListener('table-sort', event => {
      sortIndex = event.detail.index; sortDirection = event.detail.direction; page = 1; render();
    });
    const observer = new MutationObserver(() => { page = 1; render(); });
    function render() {
      observer.disconnect();
      const rows = Array.from(body.rows);
      if (sortIndex >= 0) {
        rows.sort((a, b) => {
          const first = cellValue(a, sortIndex), second = cellValue(b, sortIndex);
          const comparison = typeof first === 'number' && typeof second === 'number' ? first - second : collator.compare(String(first), String(second));
          return sortDirection === 'asc' ? comparison : -comparison;
        });
        body.append(...rows);
      }
      headers.forEach((header, index) => {
        if (header.hasAttribute('data-no-sort')) return;
        header.setAttribute('aria-sort', sortIndex === index ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
        header.querySelector('span').textContent = sortIndex === index ? (sortDirection === 'asc' ? '▲' : '▼') : '↕';
      });
      const matches = rows.filter(row => row.textContent.toLocaleLowerCase('id').includes(input.value.trim().toLocaleLowerCase('id')));
      const perPage = Number(size.value);
      const pages = Math.max(1, Math.ceil(matches.length / perPage));
      page = Math.min(page, pages);
      rows.forEach(row => { row.hidden = true; });
      matches.slice((page - 1) * perPage, page * perPage).forEach(row => { row.hidden = false; });
      previous.disabled = page <= 1; next.disabled = page >= pages;
      first.disabled = previous.disabled; last.disabled = next.disabled;
      reset.hidden = !input.value;
      label.textContent = `${page}/${pages}`;
      label.setAttribute('aria-label', `Halaman ${page} dari ${pages}`);
      empty.hidden = matches.length > 0;
      last.onclick = () => { page = pages; render(); };
      observer.observe(body, { childList: true });
    }
    input.addEventListener('input', () => { page = 1; render(); });
    form.addEventListener('submit', event => { event.preventDefault(); page = 1; render(); });
    reset.addEventListener('click', () => { input.value = ''; page = 1; render(); });
    previous.addEventListener('click', () => { page--; render(); });
    next.addEventListener('click', () => { page++; render(); });
    first.addEventListener('click', () => { page = 1; render(); });
    size.addEventListener('change', () => { page = 1; render(); });
    render();
  });
})();
