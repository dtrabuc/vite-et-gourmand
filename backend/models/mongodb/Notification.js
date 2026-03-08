const mongoose = require('mongoose');

const notificationSchema = new mongoose.Schema({
  userId: {
    type: String,
    required: true,
    index: true
  },
  type: {
    type: String,
    enum: [
      'order_created', 'order_updated', 'order_shipped',
      'order_delivered', 'payment_received', 'payment_failed',
      'comment_reply', 'product_review', 'system',
      'promotion', 'account_update'
    ],
    required: true
  },
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    required: true
  },
  data: {
    type: mongoose.Schema.Types.Mixed,
    default: {}
  },
  isRead: {
    type: Boolean,
    default: false,
    index: true
  },
  readAt: {
    type: Date,
    default: null
  },
  priority: {
    type: String,
    enum: ['low', 'medium', 'high', 'urgent'],
    default: 'medium'
  }
}, {
  timestamps: true,
  collection: 'notifications'
});

// Index composé
notificationSchema.index({ userId: 1, isRead: 1, createdAt: -1 });

// TTL - Suppression après 30 jours
notificationSchema.index({ createdAt: 1 }, { expireAfterSeconds: 2592000 });

// Marquer comme lu
notificationSchema.methods.markAsRead = async function() {
  this.isRead = true;
  this.readAt = new Date();
  return this.save();
};

// Notifications non lues d'un utilisateur
notificationSchema.statics.getUnreadCount = async function(userId) {
  return this.countDocuments({ userId, isRead: false });
};

// Récupérer les notifications paginées
notificationSchema.statics.getUserNotifications = async function(
  userId, 
  options = {}
) {
  const { page = 1, limit = 20, unreadOnly = false } = options;
  const query = { userId };
  if (unreadOnly) query.isRead = false;

  const [notifications, total] = await Promise.all([
    this.find(query)
      .sort({ createdAt: -1 })
      .skip((page - 1) * limit)
      .limit(limit)
      .lean(),
    this.countDocuments(query)
  ]);

  return {
    notifications,
    pagination: {
      total,
      page,
      pages: Math.ceil(total / limit),
      limit
    }
  };
};

// Marquer toutes les notifications comme lues
notificationSchema.statics.markAllAsRead = async function(userId) {
  return this.updateMany(
    { userId, isRead: false },
    { $set: { isRead: true, readAt: new Date() } }
  );
};

module.exports = mongoose.model('Notification', notificationSchema);