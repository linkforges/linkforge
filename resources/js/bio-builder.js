/*
 | LinkForge Bio builder. Tabbed editor with a live iframe preview rendered by
 | the same Blade view as the public page (one render path). Maintains blocks,
 | social links, design and settings as JS state, serialised into hidden inputs.
 */
const root = document.getElementById('bio-builder');
if (root) init(root);

function init(root) {
    const $ = (s) => root.querySelector(s);
    const $$ = (s) => Array.from(root.querySelectorAll(s));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const initial = JSON.parse(document.getElementById('bio-initial')?.textContent || '{}');
    let blocks = Array.isArray(initial.blocks) ? initial.blocks : [];
    let social = Array.isArray(initial.social) ? initial.social : [];

    // ---- image upload ----------------------------------------------------
    function pickFile(cb, accept = 'image/*') {
        const inp = document.createElement('input');
        inp.type = 'file';
        inp.accept = accept;
        inp.addEventListener('change', () => { if (inp.files[0]) cb(inp.files[0]); });
        inp.click();
    }
    async function uploadImage(file) {
        const fd = new FormData();
        fd.append('image', file);
        try {
            const res = await fetch(root.dataset.uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, body: fd });
            if (res.ok) return (await res.json()).url;
        } catch { /* ignore */ }
        return null;
    }
    async function uploadFile(file) {
        const fd = new FormData();
        fd.append('file', file);
        try {
            const res = await fetch(root.dataset.uploadFileUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, body: fd });
            if (res.ok) return (await res.json()).url;
        } catch { /* ignore */ }
        return null;
    }

    // ---- tabs ------------------------------------------------------------
    $$('[data-tab-btn]').forEach((btn) => btn.addEventListener('click', () => {
        const tab = btn.dataset.tabBtn;
        $$('[data-tab-btn]').forEach((b) => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
        $$('[data-tab]').forEach((p) => p.classList.toggle('hidden', p.dataset.tab !== tab));
    }));

    // ---- design / settings gather ---------------------------------------
    const activeAttr = (sel, attr) => ($(`[${sel}][aria-pressed="true"]`)?.getAttribute(attr)) || null;

    function gatherDesign() {
        const bgType = activeAttr('data-bgtype', 'data-bgtype') || 'color';
        const bg = bgType === 'gradient'
            ? { type: 'gradient', gradStart: $('#bio-grad-start').value, gradStop: $('#bio-grad-stop').value, gradAngle: +$('#bio-grad-angle').value }
            : bgType === 'image'
                ? { type: 'image', image: $('#bio-bg-image').value }
                : { type: 'color', color: $('#bio-bg-color').value };
        return {
            headerLayout: activeAttr('data-header', 'data-header') || 'classic',
            font: $('#bio-font').value,
            textColor: $('#bio-textcolor').value,
            bg,
            button: {
                color: $('#bio-btn-color').value,
                textColor: $('#bio-btn-textcolor').value,
                style: $('#bio-btn-style').value,
                shape: $('#bio-btn-shape').value,
                shadow: $('#bio-btn-shadow').value,
                frosted: $('#bio-btn-frosted').checked,
            },
        };
    }

    function gatherSettings() {
        return {
            description: $('#bio-description').value,
            avatar: { display: $('#bio-avatar-display').checked, style: $('#bio-avatar-style').value, image: $('#bio-avatar-image').value },
            verified: $('#bio-verified').checked,
            sensitive: $('#bio-sensitive')?.checked || false,
            hide_branding: $('#bio-hide-branding')?.checked || false,
            social_position: $('#bio-social-position').value,
            seo: { title: $('#bio-seo-title').value, description: $('#bio-seo-desc').value, image: $('#bio-seo-image').value },
        };
    }

    function applyDesign(d) {
        const setBtn = (sel, val) => $$(`[${sel}]`).forEach((b) => b.setAttribute('aria-pressed', b.getAttribute(sel) === val ? 'true' : 'false'));
        setBtn('data-header', d.headerLayout || 'classic');
        $('#bio-font').value = d.font || 'jakarta';
        $('#bio-textcolor').value = d.textColor || '#0f172a';
        const bg = d.bg || { type: 'color', color: '#f8fafc' };
        setBtn('data-bgtype', bg.type || 'color');
        toggleBgFields(bg.type || 'color');
        if (bg.color) $('#bio-bg-color').value = bg.color;
        if (bg.gradStart) $('#bio-grad-start').value = bg.gradStart;
        if (bg.gradStop) $('#bio-grad-stop').value = bg.gradStop;
        if (bg.gradAngle != null) $('#bio-grad-angle').value = bg.gradAngle;
        if (bg.image) $('#bio-bg-image').value = bg.image;
        const b = d.button || {};
        if (b.color) $('#bio-btn-color').value = b.color;
        if (b.textColor) $('#bio-btn-textcolor').value = b.textColor;
        if (b.style) $('#bio-btn-style').value = b.style;
        if (b.shape) $('#bio-btn-shape').value = b.shape;
        if (b.shadow) $('#bio-btn-shadow').value = b.shadow;
        $('#bio-btn-frosted').checked = !!b.frosted;
        sync();
    }

    function toggleBgFields(type) {
        $('[data-bg-color-fields]')?.classList.toggle('hidden', type !== 'color');
        $('[data-bg-gradient-fields]')?.classList.toggle('hidden', type !== 'gradient');
        $('[data-bg-image-fields]')?.classList.toggle('hidden', type !== 'image');
    }

    // ---- blocks repeater -------------------------------------------------
    const blockFields = {
        link: [['label', 'text', 'Button label'], ['url', 'url', 'https://']],
        featured: [['label', 'text', 'Title'], ['url', 'url', 'Destination URL'], ['image', 'url', 'Image URL']],
        heading: [['text', 'text', 'Heading text']],
        text: [['text', 'textarea', 'Paragraph text']],
        image: [['url', 'url', 'Image URL'], ['label', 'text', 'Alt text (optional)']],
        divider: [],
        phone: [['label', 'text', 'Button label (optional)'], ['phone', 'tel', 'Phone number']],
        email: [['label', 'text', 'Button label (optional)'], ['email', 'email', 'Email address']],
        whatsapp: [['label', 'text', 'Button label (optional)'], ['phone', 'tel', 'Phone (with country code)'], ['message', 'text', 'Pre-filled message (optional)']],
        video: [['url', 'url', 'YouTube or Vimeo URL']],
        embed: [['url', 'url', 'Spotify, Apple Music, SoundCloud, Calendly, Typeform URL']],
        map: [['query', 'text', 'Address or place name']],
        countdown: [['label', 'text', 'Label (optional)'], ['date', 'datetime-local', 'Target date & time']],
        faq: [['label', 'text', 'Heading (optional)'], ['text', 'textarea', 'One per line:  Question | Answer']],
        product: [['label', 'text', 'Product name'], ['price', 'text', 'Price (e.g. $29)'], ['image', 'url', 'Image URL'], ['url', 'url', 'Buy / link URL'], ['text', 'textarea', 'Short description']],
        newsletter: [['label', 'text', 'Heading (e.g. Join my list)'], ['text', 'textarea', 'Short description'], ['button', 'text', 'Button label (default: Subscribe)']],
        contact: [['label', 'text', 'Heading (e.g. Get in touch)'], ['text', 'textarea', 'Short description'], ['button', 'text', 'Button label (default: Send message)']],
        rss: [['label', 'text', 'Heading (optional)'], ['url', 'url', 'RSS or Atom feed URL'], ['count', 'number', 'Items to show (default 5)']],
        tagline: [['text', 'textarea', 'A short headline or quote']],
        html: [['html', 'textarea', 'Custom HTML (scripts and unsafe tags are removed)']],
        vcard: [['label', 'text', 'Full name'], ['org', 'text', 'Company (optional)'], ['title', 'text', 'Job title (optional)'], ['phone', 'text', 'Phone'], ['email', 'text', 'Email'], ['url', 'url', 'Website (optional)']],
        carousel: [['text', 'textarea', 'One image URL per line']],
        chat: [['provider', 'select:tawkto|Tawk.to,tidio|Tidio,intercom|Intercom', 'Provider'], ['id', 'text', 'Property / widget ID']],
        paypal: [['username', 'text', 'PayPal.Me username'], ['price', 'text', 'Amount (optional)'], ['label', 'text', 'Button label (default: Pay with PayPal)']],
        audio: [['label', 'text', 'Title (optional)'], ['url', 'url', 'Audio file URL']],
        pdf: [['label', 'text', 'Button label (default: View PDF)'], ['url', 'url', 'PDF file URL']],
        videofile: [['url', 'url', 'Video file URL']],
        spacer: [['size', 'select:sm|Small,md|Medium,lg|Large', 'Height']],
        gallery: [['text', 'textarea', 'One image URL per line (2-column grid)']],
        apps: [['ios', 'url', 'App Store URL'], ['android', 'url', 'Google Play URL']],
        testimonial: [['text', 'textarea', 'Quote'], ['label', 'text', 'Author name'], ['image', 'url', 'Author photo URL (optional)']],
    };
    const blockLabel = { link: 'Link', featured: 'Featured link', heading: 'Heading', text: 'Text', image: 'Image', divider: 'Divider', phone: 'Phone', email: 'Email', whatsapp: 'WhatsApp', video: 'Video', embed: 'Embed', map: 'Map', countdown: 'Countdown', faq: 'FAQ', product: 'Product', newsletter: 'Newsletter', contact: 'Contact form', rss: 'RSS feed', tagline: 'Tagline', html: 'HTML', vcard: 'vCard', carousel: 'Carousel', chat: 'Live chat', paypal: 'PayPal', audio: 'Audio', pdf: 'PDF', videofile: 'Video file', spacer: 'Spacer', gallery: 'Gallery', apps: 'App buttons', testimonial: 'Testimonial' };
    const fileBlocks = ['audio', 'pdf', 'videofile'];

    function renderBlocks() {
        const wrap = $('#bio-blocks-list');
        wrap.innerHTML = '';
        if (!blocks.length) {
            wrap.innerHTML = '<p class="text-sm text-slate-400">No blocks yet. Add one below.</p>';
            return;
        }
        blocks.forEach((blk, i) => {
            const card = document.createElement('div');
            card.className = 'rounded-xl border border-slate-200 bg-white p-3';
            const fields = (blockFields[blk.type] || blockFields.link).map(([key, type, ph]) => {
                const val = (blk[key] || '').replace(/"/g, '&quot;');
                if (type === 'textarea') return `<textarea data-block-index="${i}" data-block-field="${key}" rows="2" class="lf-input mt-2" placeholder="${ph}">${blk[key] || ''}</textarea>`;
                if (type.startsWith('select:')) {
                    const opts = type.slice(7).split(',').map((o) => { const [v, l] = o.split('|'); return `<option value="${v}" ${blk[key] === v ? 'selected' : ''}>${l || v}</option>`; }).join('');
                    return `<select data-block-index="${i}" data-block-field="${key}" class="lf-input mt-2">${opts}</select>`;
                }
                return `<input data-block-index="${i}" data-block-field="${key}" type="${type}" value="${val}" class="lf-input mt-2" placeholder="${ph}">`;
            }).join('');
            const uploadBtn = blk.type === 'image'
                ? `<button type="button" data-upload-block="${i}" class="mt-2 text-sm font-medium text-brand-600 hover:text-brand-700">Upload image instead</button>`
                : fileBlocks.includes(blk.type)
                    ? `<button type="button" data-upload-file="${i}" class="mt-2 text-sm font-medium text-brand-600 hover:text-brand-700">Upload file instead</button>`
                    : '';
            card.innerHTML = `<div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-400">${blockLabel[blk.type] || 'Link'}</span><span class="flex items-center gap-1">
                <button type="button" data-block-up="${i}" class="rounded p-1 text-slate-400 hover:bg-slate-100" title="Move up">&uarr;</button>
                <button type="button" data-block-down="${i}" class="rounded p-1 text-slate-400 hover:bg-slate-100" title="Move down">&darr;</button>
                <button type="button" data-block-del="${i}" class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Remove">&times;</button>
            </span></div>${fields}${uploadBtn}`;
            wrap.appendChild(card);
        });
    }

    const socialOptions = JSON.parse(document.getElementById('bio-social-options-data')?.textContent || '[]');
    const socialIcon = (key) => (socialOptions.find((o) => o.key === key) || {}).icon || '';

    function renderSocial() {
        const wrap = $('#bio-social-list');
        wrap.innerHTML = '';
        social.forEach((s, i) => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            const opts = socialOptions.map((o) => `<option value="${o.key}" ${o.key === s.platform ? 'selected' : ''}>${o.label}</option>`).join('');
            row.innerHTML = `<img src="${socialIcon(s.platform)}" alt="" class="h-5 w-5 shrink-0">
                <select data-social-index="${i}" data-social-field="platform" class="lf-input w-36">${opts}</select>
                <input data-social-index="${i}" data-social-field="url" type="url" value="${(s.url || '').replace(/"/g, '&quot;')}" class="lf-input flex-1" placeholder="https://">
                <button type="button" data-social-del="${i}" class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Remove">&times;</button>`;
            wrap.appendChild(row);
        });
    }

    // ---- searchable social picker ---------------------------------------
    function renderSocialPicker(filter) {
        const box = $('#bio-social-options');
        if (!box) return;
        const q = (filter || '').trim().toLowerCase();
        const matches = socialOptions.filter((o) => o.label.toLowerCase().includes(q) || o.key.includes(q));
        box.innerHTML = matches.length
            ? matches.map((o) => `<button type="button" data-social-pick="${o.key}" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-slate-700 transition hover:bg-brand-50"><img src="${o.icon}" alt="" class="h-4 w-4 shrink-0">${o.label}</button>`).join('')
            : '<p class="px-2.5 py-3 text-sm text-slate-400">No platforms found.</p>';
    }
    function openSocialPicker() {
        renderSocialPicker('');
        $('#bio-social-panel')?.classList.remove('hidden');
        const s = $('#bio-social-search'); if (s) { s.value = ''; s.focus(); }
    }
    function closeSocialPicker() { $('#bio-social-panel')?.classList.add('hidden'); }

    // ---- serialise + preview --------------------------------------------
    function sync() {
        $('#bio-field-design').value = JSON.stringify(gatherDesign());
        $('#bio-field-settings').value = JSON.stringify(gatherSettings());
        $('#bio-field-social').value = JSON.stringify(social);
        $('#bio-field-blocks').value = JSON.stringify(blocks);
        schedulePreview();
    }

    let timer;
    const schedulePreview = () => { clearTimeout(timer); timer = setTimeout(preview, 350); };

    async function preview() {
        const body = new URLSearchParams({
            _token: csrf,
            slug: $('#bio-slug')?.value || 'preview',
            title: $('#bio-title')?.value || '',
            design: $('#bio-field-design').value,
            settings: $('#bio-field-settings').value,
            social: $('#bio-field-social').value,
            blocks: $('#bio-field-blocks').value,
        });
        try {
            const res = await fetch(root.dataset.previewUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' }, body });
            if (res.ok) $('#bio-preview-frame').srcdoc = await res.text();
        } catch { /* ignore */ }
    }

    // ---- events ----------------------------------------------------------
    // design/settings controls
    root.addEventListener('input', (e) => {
        if (e.target.closest('[data-design-pane], [data-settings-pane], #bio-title')) sync();
    });
    root.addEventListener('change', (e) => {
        if (e.target.closest('[data-design-pane], [data-settings-pane]')) sync();
    });

    $$('[data-header]').forEach((b) => b.addEventListener('click', () => { $$('[data-header]').forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false')); sync(); }));
    $$('[data-bgtype]').forEach((b) => b.addEventListener('click', () => { $$('[data-bgtype]').forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false')); toggleBgFields(b.dataset.bgtype); sync(); }));

    // templates
    const templates = JSON.parse(document.getElementById('bio-templates')?.textContent || '[]');
    $$('[data-template-index]').forEach((b) => b.addEventListener('click', () => { const t = templates[+b.dataset.templateIndex]; if (t) applyDesign(t); }));

    // block list (delegated input)
    const onBlockField = (e) => {
        const idx = e.target.dataset.blockIndex;
        if (idx == null) return;
        blocks[idx][e.target.dataset.blockField] = e.target.value;
        sync();
    };
    $('#bio-blocks-list').addEventListener('input', onBlockField);
    $('#bio-blocks-list').addEventListener('change', onBlockField); // selects
    $('#bio-blocks-list').addEventListener('click', (e) => {
        const t = e.target.closest('button');
        if (!t) return;
        if (t.dataset.blockDel != null) { blocks.splice(+t.dataset.blockDel, 1); renderBlocks(); sync(); }
        else if (t.dataset.blockUp != null) { const i = +t.dataset.blockUp; if (i > 0) { [blocks[i - 1], blocks[i]] = [blocks[i], blocks[i - 1]]; renderBlocks(); sync(); } }
        else if (t.dataset.blockDown != null) { const i = +t.dataset.blockDown; if (i < blocks.length - 1) { [blocks[i + 1], blocks[i]] = [blocks[i], blocks[i + 1]]; renderBlocks(); sync(); } }
        else if (t.dataset.uploadBlock != null) { const i = +t.dataset.uploadBlock; pickFile(async (file) => { const url = await uploadImage(file); if (url) { blocks[i].url = url; renderBlocks(); sync(); } }); }
        else if (t.dataset.uploadFile != null) { const i = +t.dataset.uploadFile; pickFile(async (file) => { const url = await uploadFile(file); if (url) { blocks[i].url = url; renderBlocks(); sync(); } }, 'audio/*,video/*,application/pdf'); }
    });
    $$('[data-add-block]').forEach((b) => b.addEventListener('click', () => { blocks.push({ type: b.dataset.addBlock, label: '', url: '', text: '' }); renderBlocks(); sync(); }));

    // social list
    $('#bio-social-list').addEventListener('input', (e) => { const idx = e.target.dataset.socialIndex; if (idx == null) return; social[idx][e.target.dataset.socialField] = e.target.value; sync(); });
    $('#bio-social-list').addEventListener('change', (e) => {
        const idx = e.target.dataset.socialIndex;
        if (idx == null) return;
        social[idx][e.target.dataset.socialField] = e.target.value;
        if (e.target.dataset.socialField === 'platform') renderSocial(); // refresh the row icon
        sync();
    });
    $('#bio-social-list').addEventListener('click', (e) => { const t = e.target.closest('button'); if (t?.dataset.socialDel != null) { social.splice(+t.dataset.socialDel, 1); renderSocial(); sync(); } });

    // searchable platform picker
    $('#bio-social-toggle')?.addEventListener('click', (e) => {
        e.stopPropagation();
        $('#bio-social-panel')?.classList.contains('hidden') ? openSocialPicker() : closeSocialPicker();
    });
    $('#bio-social-search')?.addEventListener('input', (e) => renderSocialPicker(e.target.value));
    $('#bio-social-options')?.addEventListener('click', (e) => {
        const t = e.target.closest('[data-social-pick]');
        if (!t) return;
        social.push({ platform: t.dataset.socialPick, url: '' });
        closeSocialPicker();
        renderSocial();
        sync();
    });
    document.addEventListener('click', (e) => { if (! e.target.closest('#bio-social-picker')) closeSocialPicker(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSocialPicker(); });

    // upload buttons for avatar / background (URL inputs get an Upload affordance)
    root.addEventListener('click', (e) => {
        const t = e.target.closest('[data-upload-target]');
        if (!t) return;
        e.preventDefault();
        pickFile(async (file) => {
            const url = await uploadImage(file);
            if (url) { const f = document.getElementById(t.dataset.uploadTarget); if (f) { f.value = url; sync(); } }
        });
    });

    renderBlocks();
    renderSocial();
    sync();
}
