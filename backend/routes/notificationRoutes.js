const express = require('express');
const router = express.Router();
const notifController = require('../controllers/notificationController');
const { authenticate } = require('../middleware/auth');

// Toutes les routes sont protégées
router.use(authenticate);

router.get('/', notifController.getNotifications);
router.patch('/:id/read', notifController.markAsRead);
router.patch('/read-all', notifController.markAllAsRead);
router.delete('/:id', notifController.deleteNotification);

module.exports = router;