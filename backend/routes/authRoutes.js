const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');
const { authenticate } = require('../middleware/auth');
const { validate, schemas } = require('../middleware/validation');

// Routes publiques
router.post(
  '/register',
  validate(schemas.register),
  authController.register
);

router.post(
  '/login',
  validate(schemas.login),
  authController.login
);

// Routes protégées
router.get('/profile', authenticate, authController.getProfile);
router.put('/profile', authenticate, authController.updateProfile);
router.put(
  '/change-password',
  authenticate,
  validate(schemas.changePassword),
  authController.changePassword
);

module.exports = router;