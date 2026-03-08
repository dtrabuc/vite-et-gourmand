const express = require('express');
const router = express.Router();
const orderController = require('../controllers/orderController');
const { authenticate, authorize } = require('../middleware/auth');
const { validate, schemas } = require('../middleware/validation');

// Toutes les routes sont protégées
router.use(authenticate);

router.post(
  '/',
  validate(schemas.createOrder),
  orderController.createOrder
);

router.get('/', orderController.getUserOrders);

router.patch(
  '/:id/status',
  authorize('admin', 'moderator'),
  validate(schemas.updateOrderStatus),
  orderController.updateOrderStatus
);

module.exports = router;