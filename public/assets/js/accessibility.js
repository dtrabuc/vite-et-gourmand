/*
 * accessibility.js : panneau « Accessibilité » et infobulles d'aide.
 *
 * Réglages proposés (gardés sur l'appareil dans le localStorage) :
 *  - taille du texte (4 niveaux)
 *  - contraste renforcé (déficience visuelle)
 *  - lecture facilitée : plus d'espace entre lettres, mots et lignes (dyslexie, troubles cognitifs)
 *  - liens soulignés
 *  - animations réduites
 * Les classes sont posées sur <html> (voir components/accessibility.css).
 */
(function () {
  'use strict';

  var root = document.documentElement;
  var STORAGE_KEY = 'vg-a11y';
  var TOGGLES = ['contrast', 'readable', 'links', 'motion'];
  var TEXT_LABELS = ['taille normale', 'texte agrandi', 'texte grand', 'texte très grand'];

  function load() {
    try {
      return JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null') || {};
    } catch (e) {
      return {};
    }
  }

  function save(settings) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
    } catch (e) {
      // stockage indisponible : le réglage marche quand même jusqu'à la fin de la visite
    }
  }

  // état actuel lu sur <html> (déjà posé par a11y-init.js)
  function current() {
    var settings = { text: 0 };
    for (var level = 1; level <= 3; level++) {
      if (root.classList.contains('a11y-text-' + level)) {
        settings.text = level;
      }
    }
    TOGGLES.forEach(function (option) {
      settings[option] = root.classList.contains('a11y-' + option);
    });
    return settings;
  }

  function apply(settings) {
    for (var level = 1; level <= 3; level++) {
      root.classList.toggle('a11y-text-' + level, settings.text === level);
    }
    TOGGLES.forEach(function (option) {
      root.classList.toggle('a11y-' + option, Boolean(settings[option]));
    });
    refreshButtons(settings);
  }

  // met à jour aria-pressed pour que les lecteurs d'écran annoncent l'état des boutons
  function refreshButtons(settings) {
    document.querySelectorAll('[data-a11y-text]').forEach(function (button) {
      button.setAttribute('aria-pressed', String(Number(button.dataset.a11yText) === settings.text));
    });
    document.querySelectorAll('[data-a11y-toggle]').forEach(function (button) {
      button.setAttribute('aria-pressed', String(Boolean(settings[button.dataset.a11yToggle])));
    });
  }

  function announce(message) {
    var status = document.getElementById('a11yStatus');
    if (status) {
      status.textContent = '';
      window.setTimeout(function () { status.textContent = message; }, 50);
    }
  }

  function initPanel() {
    var panel = document.getElementById('a11yPanel');
    if (!panel) {
      return;
    }

    refreshButtons(current());

    panel.addEventListener('click', function (event) {
      var button = event.target.closest('button');
      if (!button || !panel.contains(button)) {
        return;
      }

      var settings = current();

      if (button.dataset.a11yText !== undefined) {
        settings.text = Number(button.dataset.a11yText);
        announce('Taille du texte : ' + TEXT_LABELS[settings.text] + '.');
      } else if (button.dataset.a11yToggle) {
        var option = button.dataset.a11yToggle;
        settings[option] = !settings[option];
        announce(button.querySelector('.a11y-option__title').textContent + (settings[option] ? ' activé.' : ' désactivé.'));
      } else if (button.hasAttribute('data-a11y-reset')) {
        settings = { text: 0, contrast: false, readable: false, links: false, motion: false };
        announce('Réglages d’accessibilité remis à zéro.');
      } else {
        return;
      }

      apply(settings);
      save(settings);
    });
  }

  // infobulles : au survol, au focus clavier et au toucher (le toucher donne le focus au bouton)
  function initTooltips() {
    if (!window.bootstrap || !window.bootstrap.Tooltip) {
      return; // sans Bootstrap JS, le texte caché du bouton reste lu par les lecteurs d'écran
    }

    // écran tactile (pas de survol) : la bulle s'ouvre au toucher ou au clavier,
    // car un toucher ne donne pas le focus au bouton (Safari iOS)
    var touchScreen = window.matchMedia && window.matchMedia('(hover: none)').matches;
    var tips = document.querySelectorAll('[data-bs-toggle="tooltip"]');

    tips.forEach(function (element) {
      window.bootstrap.Tooltip.getOrCreateInstance(element, { trigger: touchScreen ? 'click focus' : 'hover focus' });
    });

    // toucher ailleurs ferme les bulles ouvertes
    if (touchScreen) {
      document.addEventListener('click', function (event) {
        tips.forEach(function (element) {
          if (!element.contains(event.target)) {
            var tooltip = window.bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
              tooltip.hide();
            }
          }
        });
      });
    }

    // Échap ferme les infobulles ouvertes sans bouger la souris ni le focus (WCAG 1.4.13)
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
          var tooltip = window.bootstrap.Tooltip.getInstance(element);
          if (tooltip) {
            tooltip.hide();
          }
        });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initPanel();
    initTooltips();
  });
})();
