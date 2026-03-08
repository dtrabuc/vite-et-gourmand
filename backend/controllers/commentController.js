const Comment = require('../models/mongodb/Comment');
const { Product } = require('../models/postgresql');
const Log = require('../models/mongodb/Log');
const { AppError } = require('../middleware/errorHandler');

// ============================================
// GET /api/products/:productId/comments
// ============================================
exports.getProductComments = async (req, res, next) => {
  try {
    const { productId } = req.params;
    const { page = 1, limit = 10, sortBy = 'createdAt', order = -1 } = req.query;

    const result = await Comment.getPaginatedComments(productId, {
      page: parseInt(page),
      limit: parseInt(limit),
      sortBy,
      order: parseInt(order)
    });

    const rating = await Comment.getAverageRating(productId);

    res.json({
      success: true,
      data: {
        ...result,
        averageRating: rating.averageRating,
        totalReviews: rating.totalReviews
      }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// POST /api/products/:productId/comments
// ============================================
exports.createComment = async (req, res, next) => {
  try {
    const { productId } = req.params;
    const { content, rating } = req.body;

    // Vérifier que le produit existe (PostgreSQL)
    const product = await Product.findByPk(productId);
    if (!product) {
      throw new AppError('Produit non trouvé', 404);
    }

    // Vérifier si l'utilisateur a déjà commenté
    const existingComment = await Comment.findOne({
      productId,
      userId: req.user.id
    });
    if (existingComment) {
      throw new AppError('Vous avez déjà commenté ce produit', 409);
    }

    const comment = await Comment.create({
      productId,
      userId: req.user.id,
      username: req.user.username || req.user.email,
      content,
      rating
    });

    await Log.createLog({
      level: 'info',
      action: 'COMMENT_CREATE',
      message: `Commentaire créé pour le produit ${productId}`,
      userId: req.user.id,
      metadata: { commentId: comment._id, productId, rating }
    });

    res.status(201).json({
      success: true,
      message: 'Commentaire ajouté',
      data: { comment }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// POST /api/comments/:commentId/reply
// ============================================
exports.addReply = async (req, res, next) => {
  try {
    const { commentId } = req.params;
    const { content } = req.body;

    const comment = await Comment.findById(commentId);
    if (!comment) {
      throw new AppError('Commentaire non trouvé', 404);
    }

    comment.replies.push({
      userId: req.user.id,
      username: req.user.username || req.user.email,
      content
    });

    await comment.save();

    res.status(201).json({
      success: true,
      message: 'Réponse ajoutée',
      data: { comment }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// POST /api/comments/:commentId/like
// ============================================
exports.toggleLike = async (req, res, next) => {
  try {
    const { commentId } = req.params;
    const comment = await Comment.findById(commentId);

    if (!comment) {
      throw new AppError('Commentaire non trouvé', 404);
    }

    const userIndex = comment.likedBy.indexOf(req.user.id);
    if (userIndex === -1) {
      comment.likedBy.push(req.user.id);
      comment.likes += 1;
    } else {
      comment.likedBy.splice(userIndex, 1);
      comment.likes -= 1;
    }

    await comment.save();

    res.json({
      success: true,
      data: {
        likes: comment.likes,
        isLiked: userIndex === -1
      }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// DELETE /api/comments/:commentId
// ============================================
exports.deleteComment = async (req, res, next) => {
  try {
    const { commentId } = req.params;
    const comment = await Comment.findById(commentId);

    if (!comment) {
      throw new AppError('Commentaire non trouvé', 404);
    }

    if (comment.userId !== req.user.id && req.user.role !== 'admin') {
      throw new AppError('Non autorisé', 403);
    }

    await comment.deleteOne();

    res.json({
      success: true,
      message: 'Commentaire supprimé'
    });
  } catch (error) {
    next(error);
  }
};