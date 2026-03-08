// ============================================
// Helpers et fonctions utilitaires
// ============================================

/**
 * Pagination helper - génère les métadonnées de pagination
 */
const paginate = (totalItems, currentPage = 1, pageSize = 10) => {
  const totalPages = Math.ceil(totalItems / pageSize);
  const page = Math.max(1, Math.min(currentPage, totalPages));
  const offset = (page - 1) * pageSize;

  return {
    offset,
    limit: pageSize,
    pagination: {
      total: totalItems,
      page,
      pages: totalPages,
      limit: pageSize,
      hasNext: page < totalPages,
      hasPrev: page > 1
    }
  };
};

/**
 * Slugify - convertir une chaîne en slug URL
 */
const slugify = (text) => {
  return text
    .toString()
    .normalize('NFD')
    .replace(/[\\u0300-\\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)+/g, '');
};

/**
 * Générer un ID aléatoire
 */
const generateId = (prefix = '', length = 8) => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = prefix ? `${prefix}_` : '';
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
};

/**
 * Filtrer les champs nuls/undefined d'un objet
 */
const cleanObject = (obj) => {
  return Object.fromEntries(
    Object.entries(obj).filter(([_, v]) => v != null)
  );
};

/**
 * Attendre X millisecondes (utile pour les tests)
 */
const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

/**
 * Formater une date en format lisible
 */
const formatDate = (date, locale = 'fr-FR') => {
  return new Intl.DateTimeFormat(locale, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date));
};

module.exports = {
  paginate,
  slugify,
  generateId,
  cleanObject,
  sleep,
  formatDate
};