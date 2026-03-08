const mongoose = require('mongoose');

const logSchema = new mongoose.Schema({
  level: {
    type: String,
    enum: ['info', 'warn', 'error', 'debug'],
    default: 'info',
    index: true
  },
  action: {
    type: String,
    required: true,
    index: true
  },
  message: {
    type: String,
    required: true
  },
  userId: {
    type: String,
    index: true,
    default: null
  },
  metadata: {
    type: mongoose.Schema.Types.Mixed,
    default: {}
  },
  ip: {
    type: String,
    default: null
  },
  userAgent: {
    type: String,
    default: null
  },
  endpoint: {
    type: String,
    default: null
  },
  method: {
    type: String,
    enum: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    default: null
  },
  statusCode: {
    type: Number,
    default: null
  },
  responseTime: {
    type: Number, // en millisecondes
    default: null
  }
}, {
  timestamps: true,
  collection: 'logs'
});

// Index composé pour les recherches fréquentes
logSchema.index({ createdAt: -1 });
logSchema.index({ userId: 1, action: 1, createdAt: -1 });
logSchema.index({ level: 1, createdAt: -1 });

// TTL Index - Suppression auto après 90 jours
logSchema.index({ createdAt: 1 }, { expireAfterSeconds: 7776000 });

// Méthode statique pour créer un log facilement
logSchema.statics.createLog = async function(data) {
  try {
    return await this.create(data);
  } catch (error) {
    console.error('Erreur création log:', error.message);
  }
};

// Méthode statique pour récupérer les logs d'un utilisateur
logSchema.statics.getUserLogs = async function(userId, options = {}) {
  const { page = 1, limit = 50, level, action } = options;
  const query = { userId };
  
  if (level) query.level = level;
  if (action) query.action = action;

  return this.find(query)
    .sort({ createdAt: -1 })
    .skip((page - 1) * limit)
    .limit(limit)
    .lean();
};

module.exports = mongoose.model('Log', logSchema);