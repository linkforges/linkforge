/* =====================================================================
   LinkForge - Documentation behavior. Vanilla JS, no dependencies.
   Works offline from file:// (no fetch, no modules).
   ===================================================================== */
(function () {
  'use strict';

  /* ---------- Theme ---------- */
  var root = document.documentElement;
  try {
    var saved = localStorage.getItem('lf-doc-theme');
    if (saved) root.setAttribute('data-theme', saved);
    else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      root.setAttribute('data-theme', 'dark');
    }
  } catch (e) {}

  function bind(id, fn) { var el = document.getElementById(id); if (el) el.addEventListener('click', fn); }

  bind('themeToggle', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('lf-doc-theme', next); } catch (e) {}
  });

  /* ---------- Mobile nav ---------- */
  function closeNav() { document.body.classList.remove('nav-open'); }
  bind('navToggle', function () { document.body.classList.toggle('nav-open'); });
  bind('scrim', closeNav);

  /* ---------- Collapsible groups ---------- */
  document.querySelectorAll('.nav__grouptitle').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.parentElement.classList.toggle('collapsed'); });
  });

  /* ---------- Sidebar links: close drawer + set active on click ---------- */
  var navLinks = Array.prototype.slice.call(document.querySelectorAll('.nav__links a'));
  navLinks.forEach(function (a) {
    a.addEventListener('click', function () {
      if (window.innerWidth <= 1020) setTimeout(closeNav, 80);
    });
  });

  /* ---------- Search filter ---------- */
  var search = document.getElementById('navSearch');
  var emptyMsg = document.getElementById('navEmpty');
  if (search) {
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      var anyVisible = false;
      document.querySelectorAll('.nav__group').forEach(function (group) {
        var links = group.querySelectorAll('.nav__links a');
        var groupHas = false;
        links.forEach(function (a) {
          var hit = !q || a.textContent.toLowerCase().indexOf(q) !== -1;
          a.classList.toggle('nav__hidden', !hit);
          if (hit) { groupHas = true; anyVisible = true; }
        });
        group.classList.toggle('nav__hidden', !groupHas);
        if (q) group.classList.remove('collapsed');
      });
      if (emptyMsg) emptyMsg.style.display = anyVisible ? 'none' : 'block';
    });
  }

  /* ---------- Copy buttons ---------- */
  document.querySelectorAll('.codeblock').forEach(function (block) {
    var pre = block.querySelector('pre');
    if (!pre) return;
    var btn = document.createElement('button');
    btn.className = 'copy'; btn.type = 'button'; btn.textContent = 'Copy';
    btn.addEventListener('click', function () {
      var text = pre.innerText;
      var done = function () { btn.textContent = 'Copied'; btn.classList.add('done'); setTimeout(function () { btn.textContent = 'Copy'; btn.classList.remove('done'); }, 1600); };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () { fallback(text); done(); });
      } else { fallback(text); done(); }
    });
    block.appendChild(btn);
  });
  function fallback(text) {
    var ta = document.createElement('textarea'); ta.value = text;
    ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); } catch (e) {} document.body.removeChild(ta);
  }

  /* ---------- Auto-load real screenshots when present ----------
     Drop an image into images/ named after each figure (see DOC-SCREENSHOTS.md):
     figure id="shot-dashboard"  ->  images/dashboard.png (or .jpg / .webp).
     Until a file exists, the styled placeholder stays. No HTML editing needed. */
  document.querySelectorAll('figure[id^="shot-"]').forEach(function (fig) {
    var name = fig.id.replace(/^shot-/, '');
    var capEl = fig.querySelector('.shot__cap');
    var alt = capEl ? capEl.textContent.trim() : name;
    var exts = ['png', 'jpg', 'jpeg', 'webp'];
    var i = 0;
    (function tryNext() {
      if (i >= exts.length) return;           // none found - keep the placeholder
      var src = 'images/' + name + '.' + exts[i++];
      var probe = new Image();
      probe.onload = function () {
        fig.innerHTML = '';
        var img = document.createElement('img');
        img.src = src; img.alt = alt; img.loading = 'lazy';
        fig.appendChild(img);
        var fc = document.createElement('figcaption');
        fc.textContent = alt;
        fig.appendChild(fc);
      };
      probe.onerror = tryNext;
      probe.src = src;
    })();
  });

  /* ---------- Scrollspy ---------- */
  var sections = Array.prototype.slice.call(document.querySelectorAll('section.block[id], section.sec-wrap[id]'));
  var byId = {};
  navLinks.forEach(function (a) {
    var href = a.getAttribute('href') || '';
    if (href.charAt(0) === '#') byId[href.slice(1)] = a;
  });
  function spy() {
    var pos = window.scrollY + 120;
    var current = null;
    for (var i = 0; i < sections.length; i++) {
      if (sections[i].offsetTop <= pos) current = sections[i].id;
    }
    navLinks.forEach(function (a) { a.classList.remove('active'); });
    if (current && byId[current]) {
      byId[current].classList.add('active');
      var grp = byId[current].closest('.nav__group');
      if (grp) grp.classList.remove('collapsed');
    }
    var btt = document.getElementById('backTop');
    if (btt) btt.classList.toggle('show', window.scrollY > 600);
  }
  var ticking = false;
  window.addEventListener('scroll', function () {
    if (!ticking) { window.requestAnimationFrame(function () { spy(); ticking = false; }); ticking = true; }
  });
  bind('backTop', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  spy();
})();
