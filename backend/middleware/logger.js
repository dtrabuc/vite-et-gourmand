const Log = require('../models/mongodb/Log');

// ============================================
// Middleware de logging des requêtes HTTP
// ============================================
const requestLogger = async (req, res, next) => {
  const startTime = Date.now();

  // Intercepter la fin de la réponse
  const originalEnd = res.end;
  res.end = function(...args) {
    const responseTime = Date.now() - startTime;
    const statusCode = res.statusCode;

    // Déterminer le niveau de log
    let level = 'info';
    if (statusCode >= 400 && statusCode < 500) level = 'warn';
    if (statusCode >= 500) level = 'error';

    // Créer le log de manière asynchrone (non bloquant)
    Log.createLog({
      level,
      action: 'HTTP_REQUEST',
      message: `${req.method} ${req.originalUrl} ${statusCode}`,
      userId: req.user?.id || null,
      ip: req.ip || req.connection.remoteAddress,
      userAgent: req.get('User-Agent'),
      endpoint: req.originalUrl,
      method: req.method,
      statusCode,
      responseTime,
      metadata: {
        query: req.query,
        params: req.params,
        body: sanitizeBody(req.body)
      }
    }).catch(err => {
      console.error('Erreur logging:', err.message);
    });

    // Appeler la méthode originale
    originalEnd.apply(res, args);
  };

  next();
};

// Nettoyer le body pour ne pas logger les données sensibles
const sanitizeBody = (body) => {
  if (!body) return {};
  const sanitized = { ...body };
  const sensitiveFields = ['password', 'currentPassword', 'newPassword', 'token', 'secret'];
  
  sensitiveFields.forEach(field => {
    if (sanitized[field]) {
      sanitized[field] = '[REDACTED]';
    }
  });

  return sanitized;
};

module.exports = { requestLogger };