const express = require('express');
const router = express.Router();
const commentController = require('../controllers/commentController');
const { authenticate } = require('../middleware/auth');

// Répondre à un commentaire
router.post(
  '/:commentId/reply',
  authenticate,
  commentController.addReply
);

// Liker/Unliker un commentaire
router.post(
  '/:commentId/like',
  authenticate,
  commentController.toggleLike
);

// Supprimer un commentaire
router.delete(
  '/:commentId',
  authenticate,
  commentController.deleteComment
);

module.exports = router;