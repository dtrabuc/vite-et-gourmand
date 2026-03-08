const express = require('express');
const router = express.Router();
const productController = require('../controllers/productController');
const commentController = require('../controllers/commentController');
const { authenticate, authorize } = require('../middleware/auth');
const { validate, schemas } = require('../middleware/validation');

// Routes publiques
router.get('/', productController.getAllProducts);
router.get('/:id', productController.getProductById);

// Routes protégées
router.post(
  '/',
  authenticate,
  validate(schemas.createProduct),
  productController.createProduct
);

router.put(
  '/:id',
  authenticate,
  validate(schemas.updateProduct),
  productController.updateProduct
);

router.delete(
  '/:id',
  authenticate,
  productController.deleteProduct
);

// Routes commentaires imbriquées
router.get(
  '/:productId/comments',
  commentController.getProductComments
);

router.post(
  '/:productId/comments',
  authenticate,
  validate(schemas.createComment),
  commentController.createComment
);

module.exports = router;