/**
 * Champs téléphone avec liste des pays (intl-tel-input v29).
 *
 * S'applique à tous les input[data-phone-input] de la page.
 * Ça aide juste à la saisie et ça évite les erreurs bêtes : c'est le PHP
 * (PhoneValidator) qui valide vraiment le numéro.
 *
 * Chargé en module (type="module"), donc pas de variable globale.
 */
import intlTelInput from '/assets/vendor/intl-tel-input/29.5.3/js/intlTelInput.mjs';
import fr from '/assets/vendor/intl-tel-input/29.5.3/js/locale/fr.js';

const UTILS_URL = '/assets/vendor/intl-tel-input/29.5.3/js/utils.js';

// mêmes pays que PhoneValidator::ALLOWED_REGIONS côté PHP
const COUNTRIES = ['fr'];

// un message en français pour chaque code renvoyé par getValidationError()
const MESSAGES = {
  IS_POSSIBLE: 'Ce numéro de téléphone n’existe pas.',
  INVALID_COUNTRY_CODE: 'L’indicatif du pays n’est pas reconnu.',
  TOO_SHORT: 'Le numéro est trop court.',
  TOO_LONG: 'Le numéro est trop long.',
  IS_POSSIBLE_LOCAL_ONLY: 'Ce numéro est incomplet : ajoutez l’indicatif régional.',
  INVALID_LENGTH: 'La longueur du numéro ne correspond pas au pays.'
};
const COUNTRY_NOT_ALLOWED = 'Seuls les numéros français sont acceptés.';
const INVALID_NUMBER = 'Numéro de téléphone invalide.';

// la zone d'erreur est la div indiquée par aria-describedby (créée dans la vue)
const feedbackFor = (input) => {
  const ids = (input.getAttribute('aria-describedby') || '').split(/\s+/);
  return ids.map((id) => document.getElementById(id)).find((el) => el?.classList.contains('invalid-feedback')) || null;
};

const showError = (input, message) => {
  const feedback = feedbackFor(input);
  input.classList.toggle('is-invalid', message !== '');
  input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
  if (feedback) {
    feedback.textContent = message;
    // le champ est enveloppé par .iti, donc le sélecteur Bootstrap "~ .invalid-feedback" ne marche plus
    feedback.classList.toggle('d-block', message !== '');
  }
};

const setupPhoneInput = (input) => {
  const fieldName = input.getAttribute('name');
  if (!fieldName) return;

  const initialValue = input.value.trim();
  let utilsReady = false;
  let iti;

  // le champ visible perd son name : c'est le champ caché (hiddenInputs) qui envoie le numéro en E.164
  input.removeAttribute('name');

  try {
    iti = intlTelInput(input, {
      loadUtils: () => import(UTILS_URL),
      onlyCountries: COUNTRIES,
      countryOrder: COUNTRIES,
      initialCountry: 'fr',
      hiddenInputs: () => ({ phone: fieldName }),
      uiTranslations: fr,
      countryNameLocale: 'fr'
    });
  } catch (error) {
    // si la lib plante, on remet le champ comme avant et le formulaire marche quand même
    input.setAttribute('name', fieldName);
    return;
  }

  // numéro déjà enregistré (profil) ou renvoyé après une erreur PHP
  if (initialValue !== '') {
    iti.setNumber(initialValue);
  }

  // si le serveur a déjà mis une erreur, on la laisse visible
  const serverFeedback = feedbackFor(input);
  if (serverFeedback && serverFeedback.textContent.trim() !== '') {
    showError(input, serverFeedback.textContent.trim());
  }

  iti.promise.then(() => { utilsReady = true; }).catch(() => { utilsReady = false; });

  // renvoie le message d'erreur, '' si c'est bon (ou si on ne peut pas encore juger)
  const errorMessage = () => {
    if (input.value.trim() === '' || !utilsReady) return '';

    const country = iti.getSelectedCountry();
    if (!country || !COUNTRIES.includes(country.iso2)) return COUNTRY_NOT_ALLOWED;

    if (iti.isValidNumber()) return '';

    const code = iti.getValidationError();
    return MESSAGES[code] || INVALID_NUMBER;
  };

  input.addEventListener('blur', () => showError(input, errorMessage()));
  input.addEventListener('input', () => {
    if (input.classList.contains('is-invalid')) showError(input, '');
  });
  input.addEventListener('countrychange', () => {
    if (input.value.trim() !== '') showError(input, errorMessage());
  });

  const form = input.form;
  if (!form) return;

  // ce listener passe après celui de la lib, qui remplit déjà le champ caché
  form.addEventListener('submit', (event) => {
    const message = errorMessage();

    if (message !== '') {
      // on met le focus seulement sur le premier champ en erreur du formulaire
      if (!event.defaultPrevented) input.focus();
      event.preventDefault();
      showError(input, message);
      return;
    }

    // utils pas chargé : getNumber() a échoué, on envoie la saisie brute et le PHP tranche
    const hidden = form.querySelector('input[type="hidden"][name="' + fieldName + '"]');
    if (hidden && hidden.value === '') {
      hidden.value = input.value.trim();
    }
  });
};

document.querySelectorAll('input[data-phone-input]').forEach(setupPhoneInput);
