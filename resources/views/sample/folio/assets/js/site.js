/**
 * Shared interactivity layer for the converted Folio pages.
 * Wires up AOS scroll animations, Swiper carousels, animated counters,
 * mobile navigation, back-to-top, accordions, tabs, typed text and lightboxes.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function initAOS() {
    if (window.AOS) {
      AOS.init({ duration: 700, once: true, easing: 'ease-in-out', offset: 60 });
    }
  }

  function initMobileNav() {
    document.querySelectorAll('[data-mobile-toggle]').forEach(function (btn) {
      var menu = document.getElementById(btn.getAttribute('aria-controls'));
      if (!menu) return;
      btn.addEventListener('click', function () {
        var isOpen = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!isOpen));
        if (isOpen) {
          menu.style.maxHeight = '0px';
        } else {
          menu.style.maxHeight = menu.scrollHeight + 'px';
        }
        var openIcon = btn.querySelector('.icon-open');
        var closeIcon = btn.querySelector('.icon-close');
        if (openIcon && closeIcon) {
          openIcon.classList.toggle('hidden', !isOpen);
          closeIcon.classList.toggle('hidden', isOpen);
        }
      });
    });
  }

  function initBackToTop() {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;
    var toggle = function () {
      if (window.scrollY > 400) {
        btn.classList.remove('opacity-0', 'pointer-events-none');
      } else {
        btn.classList.add('opacity-0', 'pointer-events-none');
      }
    };
    window.addEventListener('scroll', toggle, { passive: true });
    toggle();
    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function initHeaderScroll() {
    document.querySelectorAll('[data-header]').forEach(function (header) {
      var toggle = function () {
        if (window.scrollY > 40) header.classList.add('shadow-md');
        else header.classList.remove('shadow-md');
      };
      window.addEventListener('scroll', toggle, { passive: true });
      toggle();
    });
  }

  function easeOutQuad(t) { return t * (2 - t); }

  function animateCounter(el) {
    var target = parseFloat(el.getAttribute('data-counter'));
    if (isNaN(target)) return;
    var prefix = el.getAttribute('data-prefix') || '';
    var suffix = el.getAttribute('data-suffix') || '';
    var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
    var duration = 1400;
    var start = null;

    function step(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var value = target * easeOutQuad(progress);
      el.textContent = prefix + value.toFixed(decimals) + suffix;
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = prefix + target.toFixed(decimals) + suffix;
    }
    requestAnimationFrame(step);
  }

  function initCounters() {
    var els = document.querySelectorAll('[data-counter]');
    if (!els.length) return;
    if (!('IntersectionObserver' in window)) {
      els.forEach(animateCounter);
      return;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });
    els.forEach(function (el) { observer.observe(el); });
  }

  function initSwipers() {
    if (!window.Swiper) return;
    document.querySelectorAll('.swiper').forEach(function (el) {
      var opts = {};
      try { opts = JSON.parse(el.getAttribute('data-swiper') || '{}'); } catch (e) {}
      var prev = el.querySelector('.swiper-button-prev-custom');
      var next = el.querySelector('.swiper-button-next-custom');
      var pagination = el.querySelector('.swiper-pagination');
      var config = Object.assign({
        slidesPerView: 1,
        spaceBetween: 24,
        navigation: (prev && next) ? { prevEl: prev, nextEl: next } : undefined,
        pagination: pagination ? { el: pagination, clickable: true } : undefined,
      }, opts);
      new Swiper(el, config);
    });
  }

  function initAccordions() {
    document.querySelectorAll('[data-accordion]').forEach(function (group) {
      var items = group.querySelectorAll('.acc-item');
      items.forEach(function (item) {
        var trigger = item.querySelector('.acc-trigger');
        var panel = item.querySelector('.acc-panel');
        if (!trigger || !panel) return;
        var isOpen = trigger.getAttribute('aria-expanded') === 'true';
        panel.style.maxHeight = isOpen ? panel.scrollHeight + 'px' : '0px';

        trigger.addEventListener('click', function () {
          var currentlyOpen = trigger.getAttribute('aria-expanded') === 'true';
          var exclusive = group.getAttribute('data-accordion') !== 'multi';
          if (exclusive) {
            items.forEach(function (other) {
              if (other === item) return;
              var otherTrigger = other.querySelector('.acc-trigger');
              var otherPanel = other.querySelector('.acc-panel');
              otherTrigger.setAttribute('aria-expanded', 'false');
              otherPanel.style.maxHeight = '0px';
              var otherIcon = otherTrigger.querySelector('.acc-icon');
              if (otherIcon) otherIcon.classList.remove('rotate-180');
            });
          }
          trigger.setAttribute('aria-expanded', String(!currentlyOpen));
          panel.style.maxHeight = currentlyOpen ? '0px' : panel.scrollHeight + 'px';
          var icon = trigger.querySelector('.acc-icon');
          if (icon) icon.classList.toggle('rotate-180', !currentlyOpen);
        });
      });

      window.addEventListener('resize', function () {
        items.forEach(function (item) {
          var trigger = item.querySelector('.acc-trigger');
          var panel = item.querySelector('.acc-panel');
          if (trigger && panel && trigger.getAttribute('aria-expanded') === 'true') {
            panel.style.maxHeight = panel.scrollHeight + 'px';
          }
        });
      });
    });
  }

  function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach(function (tabGroup) {
      var buttons = tabGroup.querySelectorAll('[data-tab-target]');
      var panels = tabGroup.querySelectorAll('[data-tab-panel]');
      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var target = btn.getAttribute('data-tab-target');
          buttons.forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
          });
          panels.forEach(function (p) {
            p.hidden = p.getAttribute('data-tab-panel') !== target;
          });
        });
      });
    });
  }

  function initTyped() {
    if (!window.Typed) return;
    document.querySelectorAll('[data-typed]').forEach(function (el) {
      var strings = [];
      try { strings = JSON.parse(el.getAttribute('data-typed')); } catch (e) { return; }
      new Typed(el, {
        strings: strings,
        typeSpeed: 60,
        backSpeed: 30,
        backDelay: 1200,
        loop: true,
        shuffle: false,
      });
    });
  }

  function initLightbox() {
    if (!window.GLightbox) return;
    GLightbox({ selector: '.glightbox', touchNavigation: true, loop: true });
  }

  ready(function () {
    initAOS();
    initMobileNav();
    initBackToTop();
    initHeaderScroll();
    initCounters();
    initSwipers();
    initAccordions();
    initTabs();
    initTyped();
    initLightbox();
  });
})();
