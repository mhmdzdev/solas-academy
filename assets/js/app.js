(function () {
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  // Site nav (mobile)
  var navBtn = $('[data-toggle-nav]');
  if (navBtn) navBtn.addEventListener('click', function () { $('#main-nav').classList.toggle('open'); });

  // Admin sidebar (mobile)
  var admBtn = $('[data-toggle-admin]');
  if (admBtn) admBtn.addEventListener('click', function () { $('.admin-side').classList.toggle('open'); });

  // Course drawer (mobile)
  var drawer = $('#course-drawer'), backdrop = $('#drawer-backdrop');
  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('open', open);
    backdrop.classList.toggle('open', open);
  }
  $$('[data-open-drawer]').forEach(function (b) { b.addEventListener('click', function () { setDrawer(true); }); });
  $$('[data-close-drawer]').forEach(function (b) { b.addEventListener('click', function () { setDrawer(false); }); });
  if (backdrop) backdrop.addEventListener('click', function () { setDrawer(false); });

  // Confirm dialogs
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });

  // Enroll form: "taken before?" logic
  var radios = $$('input[name="taken_before"]');
  if (radios.length) {
    var dateBox = $('#last-date-box'), dateInp = $('#last_taken_date'), submit = $('#enroll-submit');
    var update = function () {
      var v = (radios.filter(function (r) { return r.checked; })[0] || {}).value;
      if (v === 'yes') {
        dateBox.classList.remove('hidden'); dateInp.required = true;
        submit.classList.toggle('hidden', !dateInp.value);
      } else if (v === 'no') {
        dateBox.classList.add('hidden'); dateInp.required = false; submit.classList.remove('hidden');
      } else { submit.classList.add('hidden'); }
    };
    radios.forEach(function (r) { r.addEventListener('change', update); });
    if (dateInp) dateInp.addEventListener('input', update);
    update();
  }

  // Highlight active h2 in lesson TOC while scrolling
  var links = $$('.lesson-toc a[href^="#sec-"]');
  if (links.length && 'IntersectionObserver' in window) {
    var map = {};
    links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          links.forEach(function (a) { a.classList.remove('active'); });
          if (map[en.target.id]) map[en.target.id].classList.add('active');
        }
      });
    }, { rootMargin: '-10% 0px -80% 0px' });
    $$('.lesson-body h2[id]').forEach(function (h) { io.observe(h); });
  }
})();
