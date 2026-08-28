/* Shared front-end behaviour. Everything that decides *what* to show
   is done on the server — this file only handles interaction. */

/* ---- mobile nav ---- */
(function () {
  var burger = document.getElementById("burger");
  var nav = document.getElementById("nav");
  if (!burger || !nav) return;
  burger.addEventListener("click", function () {
    var open = nav.classList.toggle("open");
    burger.setAttribute("aria-expanded", open ? "true" : "false");
  });
})();

/* ---- vehicle gallery thumbnails ---- */
(function () {
  var thumbs = document.getElementById("thumbs");
  var main = document.getElementById("mainShot");
  if (!thumbs || !main) return;
  thumbs.addEventListener("click", function (e) {
    var b = e.target.closest("button");
    if (!b) return;
    main.src = b.dataset.src;
    thumbs.querySelectorAll("button").forEach(function (x) { x.classList.remove("on"); });
    b.classList.add("on");
  });
})();

/* ---- reveal on scroll ---- */
(function () {
  var items = document.querySelectorAll(".rv");
  if (!items.length) return;
  if (!("IntersectionObserver" in window)) {
    items.forEach(function (i) { i.classList.add("in"); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); }
    });
  }, { threshold: 0.12 });
  items.forEach(function (i) { io.observe(i); });
})();

/* ---- booking: highlight the chosen time slot ----
   CSS :has() already does this on new browsers; this keeps it working
   on older phones, which is exactly who books a garage from a driveway. */
(function () {
  var slots = document.querySelectorAll(".slot input[type=radio]");
  if (!slots.length) return;
  function paint() {
    slots.forEach(function (r) { r.closest(".slot").classList.toggle("is-on", r.checked); });
  }
  slots.forEach(function (r) { r.addEventListener("change", paint); });
  paint();
})();
