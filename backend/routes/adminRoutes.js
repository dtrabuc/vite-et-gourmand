const express = require('express');
const router = express.Router();
const adminController = require('../controllers/adminController');
const { authenticate, authorize } = require('../middleware/auth');
const { validate, schemas } = require('../middleware/validation');

router.post('/login', validate(schemas.login), adminController.login);
router.get('/dashboard', authenticate, authorize('admin'), adminController.getDashboard);

module.exports = router;
