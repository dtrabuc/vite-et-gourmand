// ============================================
// Classe d'erreur personnalisée
// ============================================
class AppError extends Error {
  constructor(message, statusCode = 500, errors = null) {
    super(message);
    this.statusCode = statusCode;
    this.status = statusCode >= 400 && statusCode < 500 ? 'fail' : 'error';
    this.isOperational = true;
    this.errors = errors;

    Error.captureStackTrace(this, this.constructor);
  }
}

// ============================================
// Gestionnaire d'erreurs global
// ============================================
const errorHandler = (err, req, res, next) => {
  let error = { ...err, message: err.message };

  // Log en développement
  if (process.env.NODE_ENV === 'development') {
    console.error('🔴 ERROR:', {
      message: err.message,
      stack: err.stack,
      statusCode: err.statusCode
    });
  }

  // Erreur de validation Sequelize
  if (err.name === 'SequelizeValidationError') {
    const errors = err.errors.map(e => ({
      field: e.path,
      message: e.message
    }));
    error = new AppError('Erreur de validation', 422, errors);
  }

  // Erreur de contrainte unique Sequelize
  if (err.name === 'SequelizeUniqueConstraintError') {
    const errors = err.errors.map(e => ({
      field: e.path,
      message: e.message
    }));
    error = new AppError('Données en double', 409, errors);
  }

  // Erreur de clé étrangère Sequelize
  if (err.name === 'SequelizeForeignKeyConstraintError') {
    error = new AppError('Référence invalide', 400);
  }

  // Erreur de validation Mongoose
  if (err.name === 'ValidationError' && err.errors) {
    const errors = Object.values(err.errors).map(e => ({
      field: e.path,
      message: e.message
    }));
    error = new AppError('Erreur de validation', 422, errors);
  }

  // Erreur de cast MongoDB (ID invalide)
  if (err.name === 'CastError') {
    error = new AppError(`Ressource non trouvée (ID invalide)`, 400);
  }

  // Erreur de duplicate MongoDB
  if (err.code === 11000) {
    const field = Object.keys(err.keyValue)[0];
    error = new AppError(`Valeur en double pour "${field}"`, 409);
  }

  // Réponse d'erreur
  const statusCode = error.statusCode || 500;
  const response = {
    success: false,
    status: error.status || 'error',
    message: error.message || 'Erreur interne du serveur'
  };

  if (error.errors) {
    response.errors = error.errors;
  }

  if (process.env.NODE_ENV === 'development') {
    response.stack = err.stack;
  }

  res.status(statusCode).json(response);
};

// ============================================
// Gestionnaire de routes non trouvées
// ============================================
const notFound = (req, res, next) => {
  next(new AppError(`Route non trouvée: ${req.method} ${req.originalUrl}`, 404));
};

module.exports = { AppError, errorHandler, notFound };