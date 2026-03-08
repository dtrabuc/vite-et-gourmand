const mongoose = require('mongoose');

const replySchema = new mongoose.Schema({
  userId: {
    type: String,
    required: true
  },
  username: {
    type: String,
    required: true
  },
  content: {
    type: String,
    required: true,
    maxlength: [1000, 'La réponse ne peut pas dépasser 1000 caractères']
  },
  createdAt: {
    type: Date,
    default: Date.now
  }
});

const commentSchema = new mongoose.Schema({
  productId: {
    type: String,
    required: true,
    index: true
  },
  userId: {
    type: String,
    required: true,
    index: true
  },
  username: {
    type: String,
    required: true
  },
  content: {
    type: String,
    required: [true, 'Le contenu du commentaire est requis'],
    maxlength: [2000, 'Le commentaire ne peut pas dépasser 2000 caractères']
  },
  rating: {
    type: Number,
    min: [1, 'La note minimum est 1'],
    max: [5, 'La note maximum est 5'],
    required: true
  },
  replies: [replySchema],
  likes: {
    type: Number,
    default: 0
  },
  likedBy: [{
    type: String
  }],
  isVerifiedPurchase: {
    type: Boolean,
    default: false
  },
  isVisible: {
    type: Boolean,
    default: true
  }
}, {
  timestamps: true,
  collection: 'comments'
});

// Index pour les recherches
commentSchema.index({ productId: 1, createdAt: -1 });
commentSchema.index({ userId: 1, createdAt: -1 });
commentSchema.index({ rating: 1 });

// Virtual pour le nombre de réponses
commentSchema.virtual('replyCount').get(function() {
  return this.replies ? this.replies.length : 0;
});

// Méthode statique : moyenne des notes d'un produit
commentSchema.statics.getAverageRating = async function(productId) {
  const result = await this.aggregate([
    { $match: { productId, isVisible: true } },
    {
      $group: {
        _id: '$productId',
        averageRating: { $avg: '$rating' },
        totalReviews: { $sum: 1 }
      }
    }
  ]);
  return result[0] || { averageRating: 0, totalReviews: 0 };
};

// Méthode statique : commentaires paginés
commentSchema.statics.getPaginatedComments = async function(
  productId, 
  options = {}
) {
  const { page = 1, limit = 10, sortBy = 'createdAt', order = -1 } = options;
  
  const [comments, total] = await Promise.all([
    this.find({ productId, isVisible: true })
      .sort({ [sortBy]: order })
      .skip((page - 1) * limit)
      .limit(limit)
      .lean(),
    this.countDocuments({ productId, isVisible: true })
  ]);

  return {
    comments,
    pagination: {
      total,
      page,
      pages: Math.ceil(total / limit),
      limit
    }
  };
};

commentSchema.set('toJSON', { virtuals: true });

module.exports = mongoose.model('Comment', commentSchema);