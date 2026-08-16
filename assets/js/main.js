(function () {
  "use strict";

  var reducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  /* Always open the homepage at its hero, unless a specific anchor was requested. */
  if (!window.location.hash) {
    if ("scrollRestoration" in history) {
      history.scrollRestoration = "manual";
    }

    window.scrollTo(0, 0);
  }

  /* ---------------------------------------------------------
     Header scroll state
  --------------------------------------------------------- */
  var header =
    document.querySelector(".site-header") ||
    document.querySelector(".site-header-wrap");

  var lastScrollY = window.scrollY;
  function updateHeader() {
    if (!header) return;
    var currentY = window.scrollY;
    header.classList.toggle("is-scrolled", currentY > 16);
    if (currentY > lastScrollY && currentY > 120) {
      header.classList.add("is-hidden");
    } else {
      header.classList.remove("is-hidden");
    }
    lastScrollY = currentY;
  }

  updateHeader();

  window.addEventListener("scroll", updateHeader, {
    passive: true
  });


  /* ---------------------------------------------------------
     Scroll progress
  --------------------------------------------------------- */
  var scrollProgressTarget = document.querySelector(".site-header-wrap");

  function updateScrollProgress() {
    if (!scrollProgressTarget) return;

    var documentHeight =
      document.documentElement.scrollHeight - window.innerHeight;

    if (documentHeight <= 0) {
      scrollProgressTarget.style.setProperty("--scroll-progress", "0%");
      return;
    }

    var progress = Math.min(
      Math.max(window.scrollY / documentHeight, 0),
      1
    );

    scrollProgressTarget.style.setProperty("--scroll-progress", (progress * 100).toFixed(2) + "%");
  }

  updateScrollProgress();

  window.addEventListener("scroll", updateScrollProgress, {
    passive: true
  });

  window.addEventListener("resize", updateScrollProgress, {
    passive: true
  });


  /* ---------------------------------------------------------
     Mobile menu
     Compatible with modern OpsXpress header
  --------------------------------------------------------- */
  var menuButton = document.querySelector(".modern-menu-toggle");
  var mobileMenu = document.getElementById("mobile-menu");
  var closeButton = document.querySelector(".mobile-menu-close");

  function closeMenu(restoreFocus) {
    if (!menuButton || !mobileMenu) return;

    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute(
      "aria-label",
      "Open navigation"
    );

    mobileMenu.classList.remove("is-open");
    document.body.classList.remove("menu-open");

    if (restoreFocus) {
      menuButton.focus();
    }
  }

  function openMenu() {
    if (!menuButton || !mobileMenu) return;

    menuButton.setAttribute("aria-expanded", "true");
    menuButton.setAttribute(
      "aria-label",
      "Close navigation"
    );

    mobileMenu.classList.add("is-open");
    document.body.classList.add("menu-open");

    var firstLink = mobileMenu.querySelector("a");

    if (firstLink) {
      setTimeout(function () {
        firstLink.focus();
      }, 150);
    }
  }

  if (menuButton && mobileMenu) {

    /* Always start closed */
    closeMenu(false);

    /* Open / close menu */
    menuButton.querySelector("a") && menuButton.querySelector("a").addEventListener("click", function (event) {
      event.preventDefault();

      var isOpen =
        menuButton.getAttribute("aria-expanded") === "true";

      if (isOpen) {
        closeMenu(false);
      } else {
        openMenu();
      }
    });

    /* Dedicated close button */
    if (closeButton) {
      closeButton.addEventListener("click", function () {
        closeMenu(true);
      });
    }

    /* Close when navigation link is clicked */
    mobileMenu.addEventListener("click", function (event) {
      var link = event.target.closest("a");

      if (link) {
        closeMenu(false);
      }
    });

    /* Escape closes menu */
    document.addEventListener("keydown", function (event) {
      if (
        event.key === "Escape" &&
        mobileMenu.classList.contains("is-open")
      ) {
        closeMenu(true);
      }
    });

    /* Close menu when resizing to desktop */
    window.addEventListener("resize", function () {
      if (
        window.innerWidth > 900 &&
        mobileMenu.classList.contains("is-open")
      ) {
        closeMenu(false);
      }
    });
  }


  /* ---------------------------------------------------------
     Desktop navigation current-page state
  --------------------------------------------------------- */
  document
    .querySelectorAll(".modern-nav a")
    .forEach(function (link) {
      try {
        var linkUrl = new URL(link.href, window.location.origin);
        var currentUrl = new URL(window.location.href);

        var samePath =
          linkUrl.origin === currentUrl.origin &&
          linkUrl.pathname.replace(/\/$/, "") ===
            currentUrl.pathname.replace(/\/$/, "");

        if (samePath) {
          link.setAttribute("aria-current", "page");
          var item = link.closest(".wp-block-navigation-item");
          if (item) item.classList.add("current-menu-item");
        }
      } catch (error) {
        /* Ignore malformed/external navigation URLs. */
      }
    });


  /* ---------------------------------------------------------
     Current year
  --------------------------------------------------------- */
  document
    .querySelectorAll("[data-current-year]")
    .forEach(function (element) {
      element.textContent = String(
        new Date().getFullYear()
      );
    });


  /* ---------------------------------------------------------
     Reveal on scroll
     (.reveal + .delay-* classes)
  --------------------------------------------------------- */
  var revealElements =
    document.querySelectorAll(".reveal");

  if (
    reducedMotion ||
    !("IntersectionObserver" in window)
  ) {

    revealElements.forEach(function (element) {
      element.classList.add("is-visible");
    });

  } else {

    var revealObserver =
      new IntersectionObserver(
        function (entries, observer) {

          entries.forEach(function (entry) {

            if (!entry.isIntersecting) return;

            entry.target.classList.add(
              "is-visible"
            );

            observer.unobserve(entry.target);
          });

        },
        {
          threshold: 0.12,
          rootMargin: "0px 0px -40px 0px"
        }
      );

    revealElements.forEach(function (element) {
      revealObserver.observe(element);
    });
  }


  /* ---------------------------------------------------------
     Services tabs
     ARIA is applied at runtime so the markup
     stays convertible to core blocks.
  --------------------------------------------------------- */
  document
    .querySelectorAll(".services-tabs")
    .forEach(function (tabs, groupIndex) {

      var list =
        tabs.querySelector(".tab-list");

      var buttons = Array.prototype.slice.call(
        tabs.querySelectorAll(".tab-button")
      );

      var panels = Array.prototype.slice.call(
        tabs.querySelectorAll(".tab-panel")
      );

      if (
        !list ||
        !buttons.length ||
        buttons.length !== panels.length
      ) {
        return;
      }

      list.setAttribute(
        "role",
        "tablist"
      );

      buttons.forEach(function (
        button,
        index
      ) {

        var tabId =
          "ox-tab-" +
          groupIndex +
          "-" +
          index;

        var panelId =
          "ox-panel-" +
          groupIndex +
          "-" +
          index;

        button.id = tabId;

        button.setAttribute(
          "role",
          "tab"
        );

        button.setAttribute(
          "type",
          "button"
        );

        button.setAttribute(
          "aria-controls",
          panelId
        );

        panels[index].id = panelId;

        panels[index].setAttribute(
          "role",
          "tabpanel"
        );

        panels[index].setAttribute(
          "aria-labelledby",
          tabId
        );
      });


      function activate(index, moveFocus) {

        buttons.forEach(function (
          button,
          i
        ) {

          var active = i === index;

          button.classList.toggle(
            "is-active",
            active
          );

          button.setAttribute(
            "aria-selected",
            String(active)
          );

          button.tabIndex =
            active ? 0 : -1;

          panels[i].classList.toggle(
            "is-active",
            active
          );

          panels[i].hidden = !active;
        });

        if (moveFocus) {
          buttons[index].focus();
        }
      }


      buttons.forEach(function (
        button,
        index
      ) {

        button.addEventListener(
          "click",
          function () {
            activate(index, false);
          }
        );


        button.addEventListener(
          "keydown",
          function (event) {

            var next = null;

            if (
              event.key === "ArrowRight"
            ) {
              next =
                (index + 1) %
                buttons.length;
            }

            if (
              event.key === "ArrowLeft"
            ) {
              next =
                (index - 1 +
                  buttons.length) %
                buttons.length;
            }

            if (
              event.key === "Home"
            ) {
              next = 0;
            }

            if (
              event.key === "End"
            ) {
              next =
                buttons.length - 1;
            }

            if (next === null) {
              return;
            }

            event.preventDefault();

            activate(next, true);
          }
        );
      });


      var initial =
        buttons.findIndex(function (
          button
        ) {
          return button.classList.contains(
            "is-active"
          );
        });


      activate(
        initial > -1 ? initial : 0,
        false
      );
    });


  /* ---------------------------------------------------------
     Accordion
     Core Details blocks
     One open at a time
  --------------------------------------------------------- */
  document
    .querySelectorAll(".accordion")
    .forEach(function (accordion) {

      var items =
        Array.prototype.slice.call(
          accordion.querySelectorAll(
            "details"
          )
        );

      items.forEach(function (item) {

        item.addEventListener(
          "toggle",
          function () {

            if (!item.open) return;

            items.forEach(
              function (other) {

                if (other !== item) {
                  other.open = false;
                }

              }
            );
          }
        );
      });
    });


  /* ---------------------------------------------------------
     Counters
     Target value is read from the element's
     own text, e.g. "99.9%", "<5 min",
     "24/7", "42%".
  --------------------------------------------------------- */
  var counterPattern =
    /^(\D*?)(\d+(?:\.\d+)?)(.*)$/;


  function animateCounter(element) {

    if (
      element.dataset.counterDone ===
      "true"
    ) {
      return;
    }

    var match =
      counterPattern.exec(
        element.textContent.trim()
      );

    if (!match) return;

    element.dataset.counterDone = "true";

    var prefix = match[1];
    var raw = match[2];
    var suffix = match[3];

    var target =
      parseFloat(raw);

    var decimals =
      raw.indexOf(".") > -1
        ? raw.split(".")[1].length
        : 0;


    function render(value) {

      element.textContent =
        prefix +
        value.toFixed(decimals) +
        suffix;
    }


    if (
      reducedMotion ||
      !isFinite(target)
    ) {

      render(target);

      return;
    }


    var duration = 1400;

    var startedAt =
      performance.now();


    function frame(now) {

      var progress =
        Math.min(
          (now - startedAt) /
            duration,
          1
        );

      var eased =
        1 -
        Math.pow(
          1 - progress,
          4
        );


      render(
        target * eased
      );


      if (progress < 1) {
        window.requestAnimationFrame(
          frame
        );
      }
    }


    window.requestAnimationFrame(
      frame
    );
  }


  var counters =
    document.querySelectorAll(
      ".counter"
    );


  if (
    !("IntersectionObserver" in window)
  ) {

    counters.forEach(
      animateCounter
    );

  } else {

    var counterObserver =
      new IntersectionObserver(
        function (
          entries,
          observer
        ) {

          entries.forEach(
            function (entry) {

              if (
                !entry.isIntersecting
              ) {
                return;
              }

              animateCounter(
                entry.target
              );

              observer.unobserve(
                entry.target
              );
            }
          );

        },
        {
          threshold: 0.45
        }
      );


    counters.forEach(
      function (counter) {
        counterObserver.observe(
          counter
        );
      }
    );
  }


  /* ---------------------------------------------------------
     Cursor glow on interactive cards
  --------------------------------------------------------- */
  if (
    !reducedMotion &&
    window.matchMedia(
      "(pointer: fine)"
    ).matches
  ) {

    document
      .querySelectorAll(
        ".interactive-card"
      )
      .forEach(function (card) {

        card.addEventListener(
          "pointermove",
          function (event) {

            var rect =
              card.getBoundingClientRect();

            card.style.setProperty(
              "--mx",
              event.clientX -
                rect.left +
                "px"
            );

            card.style.setProperty(
              "--my",
              event.clientY -
                rect.top +
                "px"
            );
          }
        );
      });
  }


  /* ---------------------------------------------------------
     Header CTA / navigation micro interactions
  --------------------------------------------------------- */
  if (
    !reducedMotion &&
    window.matchMedia(
      "(pointer: fine)"
    ).matches
  ) {

    document
      .querySelectorAll(
        ".header-cta, .modern-menu-toggle"
      )
      .forEach(function (element) {

        element.addEventListener(
          "pointerenter",
          function () {
            element.classList.add(
              "is-hovering"
            );
          }
        );

        element.addEventListener(
          "pointerleave",
          function () {
            element.classList.remove(
              "is-hovering"
            );
          }
        );
      });
  }


  /* ---------------------------------------------------------
     Smooth anchor navigation
  --------------------------------------------------------- */
  document
    .querySelectorAll(
      'a[href^="#"]'
    )
    .forEach(function (link) {

      link.addEventListener(
        "click",
        function (event) {

          var targetId =
            link.getAttribute("href");

          if (
            !targetId ||
            targetId === "#"
          ) {
            return;
          }

          var target =
            document.querySelector(
              targetId
            );

          if (!target) return;

          event.preventDefault();

          target.scrollIntoView({
            behavior: reducedMotion
              ? "auto"
              : "smooth",
            block: "start"
          });

          history.pushState(
            null,
            "",
            targetId
          );
        }
      );
    });


  /* ---------------------------------------------------------
     Magnetic buttons
     Small premium movement on desktop
  --------------------------------------------------------- */
  if (
    !reducedMotion &&
    window.matchMedia(
      "(pointer: fine)"
    ).matches
  ) {

    document
      .querySelectorAll(
        ".magnetic"
      )
      .forEach(function (element) {

        element.addEventListener(
          "pointermove",
          function (event) {

            var rect =
              element.getBoundingClientRect();

            var x =
              event.clientX -
              (rect.left +
                rect.width / 2);

            var y =
              event.clientY -
              (rect.top +
                rect.height / 2);

            var strength = 0.12;

            element.style.transform =
              "translate(" +
              x * strength +
              "px, " +
              y * strength +
              "px)";
          }
        );


        element.addEventListener(
          "pointerleave",
          function () {

            element.style.transform =
              "";
          }
        );
      });
  }

})();
