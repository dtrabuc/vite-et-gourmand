/*
 * a11y-init.js : chargé dans le <head>, AVANT l'affichage de la page.
 * Il remet les réglages d'accessibilité choisis par le visiteur (taille du texte,
 * contraste...) sur la balise <html>, pour éviter un « flash » de la page normale.
 * Le panneau et les infobulles sont gérés par accessibility.js.
 */
(function () {
  'use strict';

  var root = document.documentElement;
  var saved = null;

  try {
    saved = JSON.parse(window.localStorage.getItem('vg-a11y') || 'null');
  } catch (e) {
    saved = null; // stockage bloqué (navigation privée...) : on garde l'affichage normal
  }

  if (!saved) {
    saved = {};
    // si le système demande plus de contraste, on l'active par défaut
    if (window.matchMedia && window.matchMedia('(prefers-contrast: more)').matches) {
      saved.contrast = true;
    }
  }

  var level = parseInt(saved.text, 10);
  if (level >= 1 && level <= 3) {
    root.classList.add('a11y-text-' + level);
  }

  ['contrast', 'readable', 'links', 'motion'].forEach(function (option) {
    if (saved[option]) {
      root.classList.add('a11y-' + option);
    }
  });
})();
