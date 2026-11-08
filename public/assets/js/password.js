// Mots de passe : bouton afficher/masquer sur tous les champs, et générateur
// aléatoire sur le formulaire d'inscription.
(() => {
  const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
  const LOWER = 'abcdefghijkmnopqrstuvwxyz';
  const DIGITS = '23456789';
  const SPECIAL = '!@#$%*-_+?';
  const LENGTH = 16;

  // entier aléatoire dans [0, max[ sans biais (crypto, pas Math.random)
  const randomIndex = (max) => {
    const limit = Math.floor(0x100000000 / max) * max;
    const buffer = new Uint32Array(1);
    do {
      crypto.getRandomValues(buffer);
    } while (buffer[0] >= limit);
    return buffer[0] % max;
  };

  const pick = (chars) => chars[randomIndex(chars.length)];

  // au moins un caractère de chaque famille pour respecter PasswordPolicy côté PHP
  const generatePassword = () => {
    const all = UPPER + LOWER + DIGITS + SPECIAL;
    const chars = [pick(UPPER), pick(LOWER), pick(DIGITS), pick(SPECIAL)];
    while (chars.length < LENGTH) chars.push(pick(all));
    for (let i = chars.length - 1; i > 0; i--) {
      const j = randomIndex(i + 1);
      [chars[i], chars[j]] = [chars[j], chars[i]];
    }
    return chars.join('');
  };

  const setVisible = (input, button, visible) => {
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Masquer' : 'Afficher';
    button.setAttribute('aria-pressed', visible ? 'true' : 'false');
  };

  // entoure chaque champ mot de passe d'un input-group Bootstrap avec un bouton
  const addToggle = (input) => {
    if (input.dataset.toggleReady) return;
    input.dataset.toggleReady = '1';

    const group = document.createElement('div');
    group.className = 'input-group';
    input.parentNode.insertBefore(group, input);
    group.appendChild(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-secondary';
    button.setAttribute('aria-controls', input.id);
    button.setAttribute('aria-label', 'Afficher ou masquer le mot de passe');
    group.appendChild(button);
    setVisible(input, button, false);

    button.addEventListener('click', () => {
      setVisible(input, button, input.type === 'password');
      input.focus();
    });

    input.passwordToggle = (visible) => setVisible(input, button, visible);
  };

  const initGenerator = (box) => {
    const checkbox = box.querySelector('[data-generate-toggle]');
    const targets = box.querySelectorAll('[data-generated-target]');
    const actions = box.querySelector('[data-generator-actions]');
    const feedback = box.querySelector('[data-copy-feedback]');
    if (!checkbox || targets.length === 0) return;

    const fill = () => {
      const password = generatePassword();
      targets.forEach((input) => {
        input.value = password;
        input.readOnly = true;
        if (input.passwordToggle) input.passwordToggle(true);
      });
      if (feedback) feedback.textContent = '';
    };

    checkbox.addEventListener('change', () => {
      if (checkbox.checked) {
        fill();
        actions.hidden = false;
      } else {
        // retour au libre choix : on vide et on remasque
        targets.forEach((input) => {
          input.value = '';
          input.readOnly = false;
          if (input.passwordToggle) input.passwordToggle(false);
        });
        actions.hidden = true;
        targets[0].focus();
      }
    });

    box.querySelector('[data-regenerate]')?.addEventListener('click', fill);

    box.querySelector('[data-copy-password]')?.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(targets[0].value);
        feedback.textContent = 'Mot de passe copié.';
      } catch (e) {
        // presse-papiers indisponible (http sans localhost) : on sélectionne le texte
        targets[0].select();
        feedback.textContent = 'Copiez-le avec Ctrl+C.';
      }
    });
  };

  // contrôle simple de la confirmation avant envoi
  const initConfirm = (form) => {
    const password = form.querySelector('input[name="password"][data-generated-target]');
    const confirm = form.querySelector('input[name="password_confirm"]');
    if (!password || !confirm) return;
    const check = () => {
      confirm.setCustomValidity(
        confirm.value !== '' && confirm.value !== password.value
          ? 'Les mots de passe ne correspondent pas.'
          : ''
      );
    };
    password.addEventListener('input', check);
    confirm.addEventListener('input', check);
    form.addEventListener('submit', check);
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="password"]').forEach(addToggle);
    document.querySelectorAll('[data-password-generator]').forEach(initGenerator);
    document.querySelectorAll('form').forEach(initConfirm);
  });
})();
