const jwt = require('jsonwebtoken');
const { User } = require('../models/postgresql');
const env = require('../config/env');
const { AppError } = require('./errorHandler');

// ============================================
// Middleware d'authentification
// ============================================
const authenticate = async (req, res, next) => {
  try {
    // Extraire le token du header Authorization
    const authHeader = req.headers.authorization;
    
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      throw new AppError('Token d\'authentification manquant', 401);
    }

    const token = authHeader.split(' ')[1];

    // Vérifier et décoder le token
    const decoded = jwt.verify(token, env.jwt.secret);

    // Récupérer l'utilisateur
    const user = await User.findByPk(decoded.id);

    if (!user) {
      throw new AppError('Utilisateur non trouvé', 401);
    }

    if (!user.isActive) {
      throw new AppError('Compte désactivé', 403);
    }

    // Attacher l'utilisateur à la requête
    req.user = {
      id: user.id,
      email: user.email,
      username: user.username,
      role: user.role
    };

    next();
  } catch (error) {
    if (error.name === 'JsonWebTokenError') {
      return next(new AppError('Token invalide', 401));
    }
    if (error.name === 'TokenExpiredError') {
      return next(new AppError('Token expiré', 401));
    }
    next(error);
  }
};

// ============================================
// Middleware d'autorisation par rôle
// ============================================
const authorize = (...roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return next(new AppError('Authentification requise', 401));
    }

    if (!roles.includes(req.user.role)) {
      return next(new AppError(
        `Rôle "${req.user.role}" non autorisé pour cette action`,
        403
      ));
    }

    next();
  };
};

// ============================================
// Middleware optionnel (ne bloque pas si pas de token)
// ============================================
const optionalAuth = async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization;
    
    if (authHeader && authHeader.startsWith('Bearer ')) {
      const token = authHeader.split(' ')[1];
      const decoded = jwt.verify(token, env.jwt.secret);
      const user = await User.findByPk(decoded.id);
      
      if (user && user.isActive) {
        req.user = {
          id: user.id,
          email: user.email,
          username: user.username,
          role: user.role
        };
      }
    }
  } catch (error) {
    // Ignorer les erreurs de token en mode optionnel
  }
  
  next();
};

module.exports = { authenticate, authorize, optionalAuth };