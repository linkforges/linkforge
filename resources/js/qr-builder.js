import QRCodeStyling from 'qr-code-styling';
import { jsPDF } from 'jspdf';

/*
 | LinkForge QR builder. Drives a live, fully styled QR preview (dots, eyes,
 | gradients, logo, frames + "Scan me" CTA) and serialises the full state into
 | hidden inputs for saving. One SVG-composition path feeds both the preview and
 | every export format, so what you see is exactly what downloads.
 */
const QR_SIZE = 1000;

const root = document.getElementById('qr-builder');
if (root) init(root);

// Saved-thumbnail rendering on the QR index grid.
const thumbs = document.querySelectorAll('.qr-thumb[data-config]');
if (thumbs.length) renderThumbs(thumbs);

const b64 = (str) => btoa(unescape(encodeURIComponent(str)));
const xml = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const FONTS = { sans: 'Helvetica, Arial, sans-serif', serif: 'Georgia, "Times New Roman", serif', mono: 'ui-monospace, Menlo, monospace' };

function fillFromDesign(d) {
    return d.gradient
        ? { gradient: { type: d.gradType || 'linear', rotation: ((d.gradRotation || 0) * Math.PI) / 180, colorStops: [{ offset: 0, color: d.gradStart || '#10b981' }, { offset: 1, color: d.gradStop || '#0f766e' }] } }
        : { color: d.fg || '#0f172a' };
}

function qrOptions(data, d) {
    return {
        width: QR_SIZE, height: QR_SIZE, type: 'svg', data: data || ' ', margin: d.margin ?? 8,
        qrOptions: { errorCorrectionLevel: d.ecc || 'Q' },
        dotsOptions: { type: d.dotsType || 'square', ...fillFromDesign(d) },
        backgroundOptions: { color: d.transparent ? 'transparent' : (d.bg || '#ffffff') },
        cornersSquareOptions: { type: d.eyeFrameType || 'square', color: d.eyeCustom ? d.eyeFrameColor : undefined },
        cornersDotOptions: { type: d.eyeBallType || 'square', color: d.eyeCustom ? d.eyeBallColor : undefined },
        image: d.logo || undefined,
        imageOptions: { hideBackgroundDots: d.hideBgDots ?? true, imageSize: d.logoSize ?? 0.3, margin: d.logoMargin ?? 6, crossOrigin: 'anonymous' },
    };
}

/** Wrap a rendered QR SVG string in a frame. Returns {svg, w, h}. */
function compose(qrSvg, d) {
    const S = QR_SIZE;
    const frame = d.frameType || 'none';
    if (frame === 'none') return { svg: qrSvg, w: S, h: S };

    const enc = b64(qrSvg);
    const fc = d.frameColor || '#0f172a';
    const tc = d.textColor || '#ffffff';
    const font = FONTS[d.frameFont] || FONTS.sans;
    const cta = xml((d.cta || 'SCAN ME').trim() || 'SCAN ME');
    const img = (x, y, s) => `<image x="${x}" y="${y}" width="${s}" height="${s}" href="data:image/svg+xml;base64,${enc}"/>`;
    const P = 70;
    let w, h, body;

    if (frame === 'box') {
        w = S + 2 * P; h = S + 2 * P;
        body = `<rect x="8" y="8" width="${w - 16}" height="${h - 16}" rx="56" fill="#ffffff"/>${img(P, P, S)}<rect x="8" y="8" width="${w - 16}" height="${h - 16}" rx="56" fill="none" stroke="${fc}" stroke-width="16"/>`;
    } else if (frame === 'top' || frame === 'bottom') {
        const B = 210; w = S + 2 * P; h = S + 2 * P + B;
        const barY = frame === 'top' ? 8 : h - B; const qrY = frame === 'top' ? B + P - 8 : P;
        body = `<defs><clipPath id="fc"><rect x="8" y="8" width="${w - 16}" height="${h - 16}" rx="56"/></clipPath></defs>`
            + `<g clip-path="url(#fc)"><rect x="0" y="0" width="${w}" height="${h}" fill="#ffffff"/>`
            + `<rect x="0" y="${barY}" width="${w}" height="${B}" fill="${fc}"/>`
            + `<text x="${w / 2}" y="${barY + B / 2}" font-family="${font}" font-size="104" font-weight="700" fill="${tc}" text-anchor="middle" dominant-baseline="central" letter-spacing="2">${cta}</text>`
            + img(P, qrY, S) + `</g>`
            + `<rect x="8" y="8" width="${w - 16}" height="${h - 16}" rx="56" fill="none" stroke="${fc}" stroke-width="16"/>`;
    } else if (frame === 'phone') {
        const sideM = 150, topM = 150, botM = 250; w = S + 2 * sideM; h = S + topM + botM;
        body = `<rect x="20" y="20" width="${w - 40}" height="${h - 40}" rx="120" fill="${fc}"/>`
            + `<rect x="${sideM - 30}" y="${topM - 30}" width="${S + 60}" height="${S + 60}" rx="36" fill="#ffffff"/>`
            + `<rect x="${w / 2 - 90}" y="60" width="180" height="34" rx="17" fill="#ffffff" opacity="0.5"/>`
            + img(sideM, topM, S)
            + `<text x="${w / 2}" y="${topM + S + botM / 2}" font-family="${font}" font-size="104" font-weight="700" fill="${tc}" text-anchor="middle" dominant-baseline="central" letter-spacing="2">${cta}</text>`;
    } else { // badge
        const B = 200; w = S + 2 * P; h = S + 2 * P + B;
        body = `<rect x="0" y="0" width="${w}" height="${h}" rx="64" fill="#ffffff"/>`
            + `<rect x="0" y="0" width="${w}" height="${h}" rx="64" fill="none" stroke="${fc}" stroke-width="6" opacity="0.18"/>`
            + img(P, P, S)
            + `<text x="${w / 2}" y="${S + 2 * P + B / 2 - 10}" font-family="${font}" font-size="96" font-weight="700" fill="${fc}" text-anchor="middle" dominant-baseline="central" letter-spacing="2">${cta}</text>`;
    }

    return { svg: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">${body}</svg>`, w, h };
}

function blobToDataURL(blob) {
    return new Promise((res) => { const r = new FileReader(); r.onload = () => res(r.result); r.readAsDataURL(blob); });
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

async function rasterize(svgStr, w, h, ext) {
    const scale = 2;
    const img = new Image();
    await new Promise((res, rej) => { img.onload = res; img.onerror = rej; img.src = 'data:image/svg+xml;base64,' + b64(svgStr); });
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(w * scale); canvas.height = Math.round(h * scale);
    const ctx = canvas.getContext('2d');
    if (ext === 'jpeg') { ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height); }
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    const mime = { png: 'image/png', jpeg: 'image/jpeg', webp: 'image/webp' }[ext] || 'image/png';
    return await new Promise((res) => canvas.toBlob(res, mime, 0.95));
}

function init(root) {
    const $ = (s) => root.querySelector(s);
    const $$ = (s) => Array.from(root.querySelectorAll(s));
    const state = { logo: root.dataset.logo || '', composed: '', dims: { w: QR_SIZE, h: QR_SIZE } };

    const qr = new QRCodeStyling(qrOptions(root.dataset.content || 'https://example.com', {}));
    const src = document.createElement('div');
    src.style.cssText = 'position:absolute;left:-99999px;top:0;width:1000px;height:1000px';
    document.body.appendChild(src);
    qr.append(src);

    const enc = encodeURIComponent;
    const esc = (v) => String(v ?? '').replace(/([\\;,:"])/g, '\\$1');

    function encode(type, d) {
        switch (type) {
            case 'link': return d.url || '';
            case 'text': return d.text || '';
            case 'email': { const q = [d.subject && `subject=${enc(d.subject)}`, d.body && `body=${enc(d.body)}`].filter(Boolean).join('&'); return `mailto:${d.email || ''}${q ? '?' + q : ''}`; }
            case 'phone': return `tel:${d.phone || ''}`;
            case 'sms': return `SMSTO:${d.phone || ''}:${d.message || ''}`;
            case 'whatsapp': return `https://wa.me/${(d.phone || '').replace(/[^0-9]/g, '')}${d.message ? '?text=' + enc(d.message) : ''}`;
            case 'wifi': return `WIFI:T:${d.encryption || 'WPA'};S:${esc(d.ssid)};P:${esc(d.password)};${d.hidden ? 'H:true;' : ''};`;
            case 'geo': return `geo:${d.lat || 0},${d.lng || 0}`;
            case 'crypto': return `${d.coin || 'bitcoin'}:${d.address || ''}${d.amount ? '?amount=' + d.amount : ''}`;
            case 'vcard': return ['BEGIN:VCARD', 'VERSION:3.0', `N:${d.last_name || ''};${d.first_name || ''}`, `FN:${[d.first_name, d.last_name].filter(Boolean).join(' ')}`, d.org && `ORG:${d.org}`, d.title && `TITLE:${d.title}`, d.phone && `TEL;TYPE=CELL:${d.phone}`, d.email && `EMAIL:${d.email}`, d.website && `URL:${d.website}`, d.address && `ADR:;;${d.address}`, 'END:VCARD'].filter(Boolean).join('\n');
            case 'event': return ['BEGIN:VEVENT', d.summary && `SUMMARY:${d.summary}`, d.location && `LOCATION:${d.location}`, d.start && `DTSTART:${d.start.replace(/[-:]/g, '')}00`, d.end && `DTEND:${d.end.replace(/[-:]/g, '')}00`, 'END:VEVENT'].filter(Boolean).join('\n');
            default: return d.url || '';
        }
    }

    function activeType() { return $('#qr-type').value; }

    function gatherData() {
        const group = root.querySelector(`[data-fields="${activeType()}"]`);
        const data = {};
        if (group) group.querySelectorAll('[data-field]').forEach((el) => { data[el.dataset.field] = el.type === 'checkbox' ? el.checked : el.value; });
        return data;
    }

    function gatherDesign() {
        return {
            ecc: $('#qr-ecc').value, margin: +$('#qr-margin').value,
            dotsType: $('#qr-dots').value, eyeFrameType: $('#qr-eyeframe').value, eyeBallType: $('#qr-eyeball').value,
            gradient: $('#qr-gradient').checked, fg: $('#qr-fg').value,
            gradType: $('#qr-grad-type').value, gradStart: $('#qr-grad-start').value, gradStop: $('#qr-grad-stop').value, gradRotation: +$('#qr-grad-rotation').value,
            transparent: $('#qr-transparent').checked, bg: $('#qr-bg').value,
            eyeCustom: $('#qr-eye-custom').checked, eyeFrameColor: $('#qr-eyeframe-color').value, eyeBallColor: $('#qr-eyeball-color').value,
            logoSize: +$('#qr-logo-size').value, logoMargin: +$('#qr-logo-margin').value, hideBgDots: $('#qr-hidebg').checked,
            frameType: $('#qr-frame').value, cta: $('#qr-cta').value, frameFont: $('#qr-frame-font').value,
            frameColor: $('#qr-frame-color').value, textColor: $('#qr-text-color').value,
        };
    }

    let token = 0;
    async function render() {
        const my = ++token;
        const type = activeType();
        const data = gatherData();
        const dz = gatherDesign();
        const str = encode(type, data) || ' ';

        $('#qr-field-content').value = str;
        $('#qr-field-data').value = JSON.stringify(data);
        $('#qr-field-design').value = JSON.stringify({ ...dz, logo: state.logo });

        qr.update(qrOptions(str, { ...dz, logo: state.logo }));
        const blob = await qr.getRawData('svg');
        if (my !== token) return; // a newer render superseded this one
        const qrSvg = typeof blob === 'string' ? blob : await blob.text();
        const out = compose(qrSvg, { ...dz, logo: state.logo });
        state.composed = out.svg; state.dims = { w: out.w, h: out.h };

        const preview = $('#qr-preview');
        preview.innerHTML = out.svg;
        const svg = preview.querySelector('svg');
        if (svg) { svg.removeAttribute('width'); svg.removeAttribute('height'); svg.style.width = '100%'; svg.style.maxWidth = '320px'; svg.style.height = 'auto'; }
    }

    let timer;
    const schedule = () => { clearTimeout(timer); timer = setTimeout(render, 110); };

    root.addEventListener('input', schedule);
    root.addEventListener('change', schedule);

    $$('[data-type-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            $('#qr-type').value = btn.dataset.typeBtn;
            $$('[data-fields]').forEach((g) => g.classList.toggle('hidden', g.dataset.fields !== btn.dataset.typeBtn));
            $$('[data-type-btn]').forEach((x) => x.setAttribute('aria-pressed', x === btn ? 'true' : 'false'));
            const dyn = root.querySelector('[data-dynamic-wrap]');
            if (dyn) dyn.classList.toggle('hidden', btn.dataset.typeBtn !== 'link');
            render();
        });
    });

    $('#qr-logo-file')?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => { state.logo = reader.result; render(); };
        reader.readAsDataURL(file);
    });
    $('#qr-logo-clear')?.addEventListener('click', () => { state.logo = ''; if ($('#qr-logo-file')) $('#qr-logo-file').value = ''; render(); });

    $$('[data-logo-preset]').forEach((b) => {
        b.addEventListener('click', async () => {
            const slug = b.dataset.logoPreset;
            if (!slug) { state.logo = ''; render(); return; }
            try {
                const txt = await (await fetch(`/vendor/social/${slug}.svg`)).text();
                state.logo = 'data:image/svg+xml;base64,' + b64(txt);
                render();
            } catch { /* ignore fetch failure */ }
        });
    });

    $$('[data-export]').forEach((b) => {
        b.addEventListener('click', async (e) => {
            e.preventDefault();
            const ext = b.dataset.export;
            const name = ($('#qr-name')?.value || 'qrcode').replace(/[^a-z0-9_-]+/gi, '-');
            await render();
            if (ext === 'svg') { downloadBlob(new Blob([state.composed], { type: 'image/svg+xml' }), name + '.svg'); return; }
            if (ext === 'pdf') {
                const png = await rasterize(state.composed, state.dims.w, state.dims.h, 'png');
                const dataUrl = await blobToDataURL(png);
                const scale = 400 / Math.max(state.dims.w, state.dims.h);
                const pw = state.dims.w * scale, ph = state.dims.h * scale;
                const pdf = new jsPDF({ orientation: pw > ph ? 'l' : 'p', unit: 'pt', format: [pw, ph] });
                pdf.addImage(dataUrl, 'PNG', 0, 0, pw, ph);
                pdf.save(name + '.pdf');
                return;
            }
            const blob = await rasterize(state.composed, state.dims.w, state.dims.h, ext);
            downloadBlob(blob, `${name}.${ext === 'jpeg' ? 'jpg' : ext}`);
        });
    });

    // ---- design templates ----------------------------------------------
    function applyDesign(d) {
        const set = (id, v) => { const el = $(id); if (!el || v === undefined || v === null) return; if (el.type === 'checkbox') el.checked = !!v; else el.value = v; };
        set('#qr-ecc', d.ecc); set('#qr-margin', d.margin);
        set('#qr-dots', d.dotsType); set('#qr-eyeframe', d.eyeFrameType); set('#qr-eyeball', d.eyeBallType);
        if ('gradient' in d) $('#qr-gradient').checked = !!d.gradient;
        set('#qr-fg', d.fg); set('#qr-grad-type', d.gradType); set('#qr-grad-start', d.gradStart); set('#qr-grad-stop', d.gradStop); set('#qr-grad-rotation', d.gradRotation);
        if ('transparent' in d) $('#qr-transparent').checked = !!d.transparent;
        set('#qr-bg', d.bg);
        if ('eyeCustom' in d) $('#qr-eye-custom').checked = !!d.eyeCustom;
        set('#qr-eyeframe-color', d.eyeFrameColor); set('#qr-eyeball-color', d.eyeBallColor);
        set('#qr-logo-size', d.logoSize); set('#qr-logo-margin', d.logoMargin);
        if ('hideBgDots' in d) $('#qr-hidebg').checked = d.hideBgDots !== false;
        set('#qr-frame', d.frameType); set('#qr-cta', d.cta); set('#qr-frame-font', d.frameFont);
        set('#qr-frame-color', d.frameColor); set('#qr-text-color', d.textColor);
        if ('logo' in d) state.logo = d.logo || '';
        render();
    }

    const tplCard = $('#qr-templates-card');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function wireChip(span) {
        const apply = span.querySelector('[data-template-design]');
        const del = span.querySelector('[data-template-delete]');
        apply?.addEventListener('click', () => { try { applyDesign(JSON.parse(apply.dataset.templateDesign)); } catch { /* ignore */ } });
        del?.addEventListener('click', async () => {
            try { await fetch(`${tplCard.dataset.destroyBase}/${del.dataset.templateDelete}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }); span.remove(); } catch { /* ignore */ }
        });
    }
    if (tplCard) {
        $$('#qr-templates .group').forEach(wireChip);
        $('#qr-save-template')?.addEventListener('click', async () => {
            const name = (prompt('Template name') || '').trim();
            if (!name) return;
            await render();
            try {
                const res = await fetch(tplCard.dataset.storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ name, design: $('#qr-field-design').value }),
                });
                if (!res.ok) return;
                const tpl = await res.json();
                $('#qr-templates [data-templates-empty]')?.remove();
                const span = document.createElement('span');
                span.className = 'group relative inline-flex';
                const designAttr = JSON.stringify(tpl.design).replace(/'/g, '&#39;');
                const safeName = (tpl.name || '').replace(/</g, '&lt;');
                span.innerHTML = `<button type="button" data-template-design='${designAttr}' class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-brand-400 hover:bg-brand-50">${safeName}</button><button type="button" data-template-delete="${tpl.id}" title="Delete template" class="absolute -right-1.5 -top-1.5 hidden h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[11px] leading-none text-white group-hover:flex">&times;</button>`;
                $('#qr-templates').appendChild(span);
                wireChip(span);
            } catch { /* ignore */ }
        });
    }

    render();
}

function renderThumbs(nodes) {
    nodes.forEach((node) => {
        let cfg;
        try { cfg = JSON.parse(node.dataset.config); } catch { return; }
        const d = cfg.design || {};
        const data = (d._short || cfg.content || ' ') || ' ';
        const opts = qrOptions(data, d);
        opts.width = 128; opts.height = 128;
        new QRCodeStyling(opts).append(node);
    });
}
