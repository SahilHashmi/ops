(function () {
  "use strict";

  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------------------------------------------------------
     Header scroll state
  --------------------------------------------------------- */
  var header = document.querySelector(".site-header");

  function updateHeader() {
    if (!header) return;
    header.classList.toggle("is-scrolled", window.scrollY > 16);
  }

  updateHeader();
  window.addEventListener("scroll", updateHeader, { passive: true });

  /* ---------------------------------------------------------
     Mobile menu
  --------------------------------------------------------- */
  var menuButton = document.querySelector(".menu-toggle");
  var mobileMenu = document.getElementById("mobile-menu");

  function closeMenu(restoreFocus) {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "Open navigation");
    mobileMenu.classList.remove("is-open");
    document.body.classList.remove("menu-open");
    if (restoreFocus) menuButton.focus();
  }

  function openMenu() {
    if (!menuButton || !mobileMenu) return;
    menuButton.setAttribute("aria-expanded", "true");
    menuButton.setAttribute("aria-label", "Close navigation");
    mobileMenu.classList.add("is-open");
    document.body.classList.add("menu-open");
    var firstLink = mobileMenu.querySelector("a");
    if (firstLink) firstLink.focus();
  }

  if (menuButton && mobileMenu) {
    closeMenu(false);

    menuButton.addEventListener("click", function () {
      if (menuButton.getAttribute("aria-expanded") === "true") {
        closeMenu(false);
      } else {
        openMenu();
      }
    });

    mobileMenu.addEventListener("click", function (event) {
      if (event.target.closest("a")) closeMenu(false);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && mobileMenu.classList.contains("is-open")) {
        closeMenu(true);
      }
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 960 && mobileMenu.classList.contains("is-open")) {
        closeMenu(false);
      }
    });
  }

  /* ---------------------------------------------------------
     Current year
  --------------------------------------------------------- */
  document.querySelectorAll("[data-current-year]").forEach(function (element) {
    element.textContent = String(new Date().getFullYear());
  });

  /* ---------------------------------------------------------
     Reveal on scroll (.reveal + .delay-* classes)
  --------------------------------------------------------- */
  var revealElements = document.querySelectorAll(".reveal");

  if (reducedMotion || !("IntersectionObserver" in window)) {
    revealElements.forEach(function (element) {
      element.classList.add("is-visible");
    });
  } else {
    var revealObserver = new IntersectionObserver(
      function (entries, observer) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );

    revealElements.forEach(function (element) {
      revealObserver.observe(element);
    });
  }

  /* ---------------------------------------------------------
     Services tabs — ARIA is applied at runtime so the markup
     stays convertible to core blocks.
  --------------------------------------------------------- */
  document.querySelectorAll(".services-tabs").forEach(function (tabs, groupIndex) {
    var list = tabs.querySelector(".tab-list");
    var buttons = Array.prototype.slice.call(tabs.querySelectorAll(".tab-button"));
    var panels = Array.prototype.slice.call(tabs.querySelectorAll(".tab-panel"));

    if (!list || !buttons.length || buttons.length !== panels.length) return;

    list.setAttribute("role", "tablist");

    buttons.forEach(function (button, index) {
      var tabId = "ox-tab-" + groupIndex + "-" + index;
      var panelId = "ox-panel-" + groupIndex + "-" + index;

      button.id = tabId;
      button.setAttribute("role", "tab");
      button.setAttribute("type", "button");
      button.setAttribute("aria-controls", panelId);

      panels[index].id = panelId;
      panels[index].setAttribute("role", "tabpanel");
      panels[index].setAttribute("aria-labelledby", tabId);
    });

    function activate(index, moveFocus) {
      buttons.forEach(function (button, i) {
        var active = i === index;
        button.classList.toggle("is-active", active);
        button.setAttribute("aria-selected", String(active));
        button.tabIndex = active ? 0 : -1;

        panels[i].classList.toggle("is-active", active);
        panels[i].hidden = !active;
      });

      if (moveFocus) buttons[index].focus();
    }

    buttons.forEach(function (button, index) {
      button.addEventListener("click", function () {
        activate(index, false);
      });

      button.addEventListener("keydown", function (event) {
        var next = null;

        if (event.key === "ArrowRight") next = (index + 1) % buttons.length;
        if (event.key === "ArrowLeft") next = (index - 1 + buttons.length) % buttons.length;
        if (event.key === "Home") next = 0;
        if (event.key === "End") next = buttons.length - 1;

        if (next === null) return;

        event.preventDefault();
        activate(next, true);
      });
    });

    var initial = buttons.findIndex(function (button) {
      return button.classList.contains("is-active");
    });

    activate(initial > -1 ? initial : 0, false);
  });

  /* ---------------------------------------------------------
     Accordion — core Details blocks, one open at a time
  --------------------------------------------------------- */
  document.querySelectorAll(".accordion").forEach(function (accordion) {
    var items = Array.prototype.slice.call(accordion.querySelectorAll("details"));

    items.forEach(function (item) {
      item.addEventListener("toggle", function () {
        if (!item.open) return;
        items.forEach(function (other) {
          if (other !== item) other.open = false;
        });
      });
    });
  });

  /* ---------------------------------------------------------
     Counters — target value is read from the element's own
     text, e.g. "99.9%", "<5 min", "24/7", "42%".
  --------------------------------------------------------- */
  var counterPattern = /^(\D*?)(\d+(?:\.\d+)?)(.*)$/;

  function animateCounter(element) {
    if (element.dataset.counterDone === "true") return;

    var match = counterPattern.exec(element.textContent.trim());
    if (!match) return;

    element.dataset.counterDone = "true";

    var prefix = match[1];
    var raw = match[2];
    var suffix = match[3];
    var target = parseFloat(raw);
    var decimals = raw.indexOf(".") > -1 ? raw.split(".")[1].length : 0;

    function render(value) {
      element.textContent = prefix + value.toFixed(decimals) + suffix;
    }

    if (reducedMotion || !isFinite(target)) {
      render(target);
      return;
    }

    var duration = 1400;
    var startedAt = performance.now();

    function frame(now) {
      var progress = Math.min((now - startedAt) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 4);

      render(target * eased);

      if (progress < 1) window.requestAnimationFrame(frame);
    }

    window.requestAnimationFrame(frame);
  }

  var counters = document.querySelectorAll(".counter");

  if (!("IntersectionObserver" in window)) {
    counters.forEach(animateCounter);
  } else {
    var counterObserver = new IntersectionObserver(
      function (entries, observer) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.45 }
    );

    counters.forEach(function (counter) {
      counterObserver.observe(counter);
    });
  }

  /* ---------------------------------------------------------
     Cursor glow on interactive cards
  --------------------------------------------------------- */
  if (!reducedMotion && window.matchMedia("(pointer: fine)").matches) {
    document.querySelectorAll(".interactive-card").forEach(function (card) {
      card.addEventListener("pointermove", function (event) {
        var rect = card.getBoundingClientRect();
        card.style.setProperty("--mx", event.clientX - rect.left + "px");
        card.style.setProperty("--my", event.clientY - rect.top + "px");
      });
    });
  }
})();
