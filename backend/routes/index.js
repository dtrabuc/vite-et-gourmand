const express = require('express');
const router = express.Router();

const authRoutes = require('./authRoutes');
const adminRoutes = require('./adminRoutes');
const productRoutes = require('./productRoutes');
const orderRoutes = require('./orderRoutes');
const notificationRoutes = require('./notificationRoutes');
const commentRoutes = require('./commentRoutes');
const publicRoutes = require('./publicRoutes');

router.use('/auth', authRoutes);
router.use('/admin', adminRoutes);
router.use('/products', productRoutes);
router.use('/orders', orderRoutes);
router.use('/notifications', notificationRoutes);
router.use('/comments', commentRoutes);
router.use('/public', publicRoutes);

router.get('/health', (req, res) => {
  res.json({
    success: true,
    message: 'API opérationnelle',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV
  });
});

module.exports = router;
