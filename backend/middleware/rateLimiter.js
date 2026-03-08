const rateLimit = require('express-rate-limit');
const { AppError } = require('./errorHandler');

// ============================================
// Rate limiter général
// ============================================
const globalLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100, // 100 requêtes par fenêtre
  message: {
    success: false,
    message: 'Trop de requêtes, veuillez réessayer dans 15 minutes'
  },
  standardHeaders: true,
  legacyHeaders: false,
  handler: (req, res, next, options) => {
    next(new AppError(options.message.message, 429));
  }
});

// ============================================
// Rate limiter strict pour l'authentification
// ============================================
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 10, // 10 tentatives maximum
  message: {
    success: false,
    message: 'Trop de tentatives de connexion, réessayez dans 15 minutes'
  },
  standardHeaders: true,
  legacyHeaders: false,
  skipSuccessfulRequests: true
});

// ============================================
// Rate limiter pour la création de ressources
// ============================================
const createLimiter = rateLimit({
  windowMs: 60 * 60 * 1000, // 1 heure
  max: 50, // 50 créations par heure
  message: {
    success: false,
    message: 'Limite de création atteinte, réessayez plus tard'
  }
});

module.exports = {
  globalLimiter,
  authLimiter,
  createLimiter
};