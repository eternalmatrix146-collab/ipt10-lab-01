/* ==========================================================================
   Midnight Market — front-end behaviour (no dependencies)
   ========================================================================== */
(() => {
  'use strict';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const csrf = () => $('meta[name="csrf-token"]')?.content || '';
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /** fetch() wrapper: JSON in/out, CSRF header, readable errors. */
  async function api(url, { method = 'GET', body } = {}) {
    const res = await fetch(url, {
      method,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
        ...(method !== 'GET' ? { 'X-CSRF-TOKEN': csrf() } : {}),
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
      credentials: 'same-origin',
    });
    let data = null;
    try { data = await res.json(); } catch { /* non-JSON body */ }
    if (!res.ok && !(data && ('ok' in data))) {
      const message = data?.error || (res.status === 419 ? 'Session expired — refresh the page.' : `Request failed (${res.status}).`);
      throw new Error(message);
    }
    return data;
  }

  /* ---------- Toasts ---------- */
  const toastLabels = { success: 'Done', error: 'Alert', info: 'Notice' };
  function toast(message, type = 'info') {
    const box = $('#toasts');
    if (!box) return;
    const el = document.createElement('div');
    el.className = `toast toast--${type}`;
    el.setAttribute('role', 'status');
    el.innerHTML = `<span class="toast__tag"></span><p></p><button type="button" class="toast__close" aria-label="Dismiss">×</button>`;
    el.querySelector('.toast__tag').textContent = toastLabels[type] || 'Notice';
    el.querySelector('p').textContent = message;
    box.appendChild(el);
    armToast(el);
  }
  function armToast(el) {
    const close = () => { el.classList.add('is-leaving'); setTimeout(() => el.remove(), 350); };
    el.querySelector('.toast__close')?.addEventListener('click', close);
    setTimeout(close, 4800);
  }
  $$('.toast').forEach(armToast);

  /* ---------- Cart badge ---------- */
  function setCartCount(count) {
    $$('[data-cart-count]').forEach((badge) => {
      if (String(badge.textContent) !== String(count)) {
        badge.textContent = count;
        badge.classList.remove('bump');
        void badge.offsetWidth; // restart animation
        badge.classList.add('bump');
      }
    });
  }

  /* ---------- Page wipe transitions ---------- */
  const wipe = $('.wipe');
  if (wipe && !reducedMotion) {
    document.body.classList.add('is-entering');
    setTimeout(() => document.body.classList.remove('is-entering'), 650);

    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[href]');
      if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      if (link.target && link.target !== '_self') return;
      if (link.hasAttribute('download') || link.dataset.noWipe !== undefined) return;
      const url = new URL(link.href, location.href);
      if (url.origin !== location.origin) return;
      if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
      event.preventDefault();
      wipe.classList.add('is-in');
      setTimeout(() => { location.href = url.href; }, 360);
    });
    window.addEventListener('pageshow', (e) => { if (e.persisted) wipe.classList.remove('is-in'); });
  }

  /* ---------- Mobile nav ---------- */
  const navToggle = $('.nav-toggle');
  navToggle?.addEventListener('click', () => {
    const nav = $('#main-nav');
    const open = !nav.classList.contains('is-open');
    nav.classList.toggle('is-open', open);
    navToggle.setAttribute('aria-expanded', String(open));
  });

  /* ---------- Auto-submit selects, confirmations ---------- */
  $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form?.submit()));
  $$('form[data-confirm]').forEach((form) => form.addEventListener('submit', (e) => {
    if (!confirm(form.dataset.confirm)) e.preventDefault();
  }));
  $$('[data-confirm-click]').forEach((btn) => btn.addEventListener('click', (e) => {
    if (!confirm(btn.dataset.confirmClick)) e.preventDefault();
  }));
  $('.calling-card')?.addEventListener('click', (e) => e.currentTarget.remove());

  /* ---------- Quantity steppers ---------- */
  $$('[data-qty]').forEach((box) => {
    const input = $('input', box);
    let timer;
    $$('[data-step]', box).forEach((btn) => btn.addEventListener('click', () => {
      const min = Number(input.min || 0);
      const max = Number(input.max || 99);
      const next = Math.min(max, Math.max(min, Number(input.value || 0) + Number(btn.dataset.step)));
      if (String(next) === input.value) return;
      input.value = next;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }));
    if (box.hasAttribute('data-autosubmit-qty')) {
      input.addEventListener('change', () => {
        clearTimeout(timer);
        timer = setTimeout(() => box.closest('form')?.submit(), 450);
      });
    }
  });

  /* ---------- AJAX add-to-cart ---------- */
  $$('form[data-ajax-cart]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = $('button[type="submit"]', form);
    const fields = Object.fromEntries(new FormData(form).entries());
    button && (button.disabled = true);
    try {
      const data = await api(form.action, {
        method: 'POST',
        body: { product_id: Number(fields.product_id), quantity: Number(fields.quantity || 1) },
      });
      setCartCount(data.cart_count);
      toast(data.message, data.ok ? 'success' : 'error');
    } catch (err) {
      toast(err.message, 'error');
    } finally {
      button && (button.disabled = false);
    }
  }));

  /* ==========================================================================
     Minimal, safe Markdown for agent replies.
     Everything is HTML-escaped first; then only bold/italic/code/links/lists
     are re-introduced. Links must be same-origin.
     ========================================================================== */
  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  function safeHref(url) {
    try {
      const u = new URL(url, location.origin);
      return u.origin === location.origin ? u.pathname + u.search + u.hash : null;
    } catch { return null; }
  }
  function inline(text) {
    return escapeHtml(text)
      .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (m, label, url) => {
        const href = safeHref(url.replace(/&amp;/g, '&'));
        return href ? `<a href="${escapeHtml(href)}">${label}</a>` : label;
      })
      .replace(/\*\*([^*]+)\*\*/g, '<b>$1</b>')
      .replace(/(^|[\s(])_([^_]+)_(?=$|[\s).,!?])/g, '$1<i>$2</i>')
      .replace(/`([^`]+)`/g, '<code>$1</code>');
  }
  function markdown(text) {
    const blocks = String(text || '').trim().split(/\n{2,}/);
    return blocks.map((block) => {
      const lines = block.split('\n');
      if (lines.every((l) => /^\s*([-*•]|\d+\.)\s+/.test(l))) {
        return '<ul>' + lines.map((l) => `<li>${inline(l.replace(/^\s*([-*•]|\d+\.)\s+/, ''))}</li>`).join('') + '</ul>';
      }
      const out = [];
      let list = [];
      lines.forEach((l) => {
        if (/^\s*[-*•]\s+/.test(l)) { list.push(`<li>${inline(l.replace(/^\s*[-*•]\s+/, ''))}</li>`); return; }
        if (list.length) { out.push(`<ul>${list.join('')}</ul>`); list = []; }
        out.push(inline(l.replace(/^#{1,6}\s*/, '')));
      });
      if (list.length) out.push(`<ul>${list.join('')}</ul>`);
      return `<p>${out.join('<br>').replace(/<br>(<ul>)/g, '$1').replace(/(<\/ul>)<br>/g, '$1')}</p>`;
    }).join('');
  }

  /* ==========================================================================
     Chat component — shared by the Rook widget and the admin Intel console
     ========================================================================== */
  class Chat {
    constructor(root, { endpoint, resetUrl, page = '' }) {
      this.root = root;
      this.endpoint = endpoint;
      this.resetUrl = resetUrl;
      this.page = page;
      this.log = $('[data-chat-log]', root);
      this.form = $('[data-chat-form]', root);
      this.input = $('textarea', this.form);
      this.send = $('button[type="submit"]', this.form);
      this.suggest = $('[data-chat-suggest]', root);
      this.busy = false;

      this.form.addEventListener('submit', (e) => { e.preventDefault(); this.submit(this.input.value); });
      this.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.submit(this.input.value); }
      });
      this.input.addEventListener('input', () => {
        this.input.style.height = 'auto';
        this.input.style.height = Math.min(120, this.input.scrollHeight) + 'px';
      });
      $$('[data-suggest]', root).forEach((b) => b.addEventListener('click', () => this.submit(b.textContent.trim())));
    }

    scroll() { this.log.scrollTop = this.log.scrollHeight; }

    clear() {
      $$('.msg:not(.msg--intro)', this.log).forEach((m) => m.remove());
      if (this.suggest) this.suggest.hidden = false;
    }

    renderUser(text) {
      const el = document.createElement('div');
      el.className = 'msg msg--user';
      el.innerHTML = '<div class="bubble"><p></p></div>';
      $('p', el).textContent = text;
      this.log.appendChild(el);
      this.scroll();
    }

    renderAssistant({ text, actions = [], cards = [] }, extraClass = '') {
      const el = document.createElement('div');
      el.className = `msg msg--assistant ${extraClass}`.trim();
      if (actions.length) {
        const wrap = document.createElement('div');
        wrap.className = 'actions';
        actions.forEach((a) => {
          const chip = document.createElement('span');
          chip.className = 'action' + (a.error ? ' is-error' : '');
          chip.innerHTML = `${a.error ? '⚠' : '✦'} <b></b> <span></span>`;
          $('b', chip).textContent = a.label || a.tool;
          $('span', chip).textContent = a.summary ? `— ${a.summary}` : '';
          chip.title = a.tool;
          wrap.appendChild(chip);
        });
        el.appendChild(wrap);
      }
      const bubble = document.createElement('div');
      bubble.className = 'bubble';
      bubble.innerHTML = markdown(text);
      el.appendChild(bubble);
      if (cards.length) {
        const strip = document.createElement('div');
        strip.className = 'minicards';
        cards.slice(0, 8).forEach((c) => {
          const href = safeHref(c.url);
          const a = document.createElement('a');
          a.className = 'minicard';
          a.href = href || '#';
          a.innerHTML = '<img alt="" loading="lazy"><span></span><b></b>';
          $('img', a).src = safeHref(c.image) || '';
          $('span', a).textContent = c.name;
          $('b', a).textContent = c.in_stock ? c.price : 'Sold out';
          strip.appendChild(a);
        });
        el.appendChild(strip);
      }
      this.log.appendChild(el);
      this.scroll();
    }

    renderError(message) {
      this.renderAssistant({ text: message }, 'msg--error');
    }

    renderEntry(entry) {
      if (entry.role === 'user') this.renderUser(entry.text);
      else this.renderAssistant(entry);
    }

    async submit(raw) {
      const message = String(raw || '').trim();
      if (!message || this.busy) return;
      this.busy = true;
      this.send.disabled = true;
      this.input.value = '';
      this.input.style.height = '';
      if (this.suggest) this.suggest.hidden = true;
      this.renderUser(message);

      const typing = document.createElement('div');
      typing.className = 'msg msg--assistant';
      typing.innerHTML = '<div class="typing"><i></i><i></i><i></i> plotting…</div>';
      this.log.appendChild(typing);
      this.scroll();

      try {
        const data = await api(this.endpoint, { method: 'POST', body: { message, page: this.page } });
        typing.remove();
        if (data.ok) {
          this.renderAssistant({ text: data.reply, actions: data.actions || [], cards: data.cards || [] });
        } else {
          this.renderError(data.error || 'Something went wrong.');
        }
        if (typeof data.cart_count === 'number') setCartCount(data.cart_count);
      } catch (err) {
        typing.remove();
        this.renderError(err.message);
      } finally {
        this.busy = false;
        this.send.disabled = false;
        this.input.focus();
      }
    }

    async reset() {
      if (!this.resetUrl) return;
      try { await api(this.resetUrl, { method: 'POST', body: {} }); } catch { /* ignore */ }
      this.clear();
    }
  }

  /* ---------- Rook widget (storefront) ---------- */
  const rook = $('[data-rook]');
  if (rook) {
    const panel = $('#rook-panel');
    const fab = $('.rook-fab', rook);
    const chat = new Chat(panel, { endpoint: rook.dataset.endpoint, resetUrl: rook.dataset.reset, page: location.pathname });
    let historyLoaded = false;

    async function loadHistory() {
      if (historyLoaded) return;
      historyLoaded = true;
      try {
        const data = await api(rook.dataset.history);
        (data.transcript || []).forEach((entry) => chat.renderEntry(entry));
        if ((data.transcript || []).length && chat.suggest) chat.suggest.hidden = true;
      } catch { /* history is optional */ }
    }

    function setOpen(open) {
      panel.hidden = !open;
      rook.classList.toggle('is-open', open);
      fab.setAttribute('aria-expanded', String(open));
      try { sessionStorage.setItem('rook-open', open ? '1' : '0'); } catch { /* storage blocked */ }
      if (open) { loadHistory(); setTimeout(() => chat.input.focus(), 60); }
    }

    $$('[data-rook-toggle]', rook).forEach((b) => b.addEventListener('click', () => setOpen(panel.hidden)));
    $('[data-rook-reset]', rook)?.addEventListener('click', () => chat.reset());
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) setOpen(false); });

    // Any button with data-rook-open opens the widget, optionally sending a prompt.
    $$('[data-rook-open]').forEach((btn) => btn.addEventListener('click', async () => {
      setOpen(true);
      await loadHistory();
      if (btn.dataset.rookPrompt) chat.submit(btn.dataset.rookPrompt);
    }));

    // Re-open after navigation on larger screens; on phones the panel would cover the page.
    try {
      if (sessionStorage.getItem('rook-open') === '1' && window.matchMedia('(min-width: 861px)').matches) setOpen(true);
    } catch { /* storage blocked */ }
  }

  /* ---------- Rook Intel console (admin) ---------- */
  const intel = $('[data-chat]');
  if (intel) {
    const chat = new Chat(intel, { endpoint: intel.dataset.endpoint, resetUrl: intel.dataset.reset });
    // Server-rendered transcript placeholders -> real messages.
    $$('[data-entry]', intel).forEach((node) => {
      try { chat.renderEntry(JSON.parse(node.dataset.entry)); } catch { /* skip bad entry */ }
      node.remove();
    });
    if ($$('.msg:not(.msg--intro)', intel).length && chat.suggest) chat.suggest.hidden = true;
    $('[data-chat-reset]', intel)?.addEventListener('click', () => chat.reset());
    if (intel.dataset.autoask) chat.submit(intel.dataset.autoask);
  }

  /* ---------- AI product copywriter (admin product form) ---------- */
  $$('[data-ai-describe]').forEach((btn) => btn.addEventListener('click', async () => {
    const form = btn.closest('form');
    const name = $('[data-ai-name]', form)?.value.trim();
    const status = $('[data-ai-status]', form);
    const target = $('[data-ai-target]', form);
    if (!name) { status.textContent = 'Enter a product name first.'; return; }
    btn.disabled = true;
    status.textContent = 'Drafting…';
    try {
      const data = await api(btn.dataset.aiDescribe, {
        method: 'POST',
        body: {
          name,
          category_id: Number($('[data-ai-category]', form)?.value) || null,
          tagline: $('[data-ai-tagline]', form)?.value.trim() || '',
        },
      });
      if (data.ok) {
        target.value = data.text;
        status.textContent = data.mode === 'nim' ? 'Drafted by Nemotron — review before saving.' : 'Offline template used (no API key).';
      } else {
        status.textContent = data.error;
      }
    } catch (err) {
      status.textContent = err.message;
    } finally {
      btn.disabled = false;
    }
  }));
})();
