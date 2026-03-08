const jwt = require('jsonwebtoken');
const { User, Product, Order } = require('../models/postgresql');
const Comment = require('../models/mongodb/Comment');
const Notification = require('../models/mongodb/Notification');
const Log = require('../models/mongodb/Log');
const env = require('../config/env');
const { AppError } = require('../middleware/errorHandler');

const generateToken = (user) => jwt.sign(
  { id: user.id, email: user.email, role: user.role },
  env.jwt.secret,
  { expiresIn: env.jwt.expiresIn }
);

exports.login = async (req, res, next) => {
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      throw new AppError('Email et mot de passe requis', 400);
    }

    const user = await User.scope('withPassword').findOne({ where: { email } });

    if (!user || !(await user.comparePassword(password))) {
      throw new AppError('Identifiants administrateur invalides', 401);
    }

    if (user.role !== 'admin') {
      throw new AppError('Accès réservé à l\'administration', 403);
    }

    await user.update({ lastLoginAt: new Date() });

    await Log.createLog({
      level: 'info',
      action: 'ADMIN_LOGIN',
      message: `Connexion admin: ${email}`,
      userId: user.id,
      ip: req.ip,
      userAgent: req.get('User-Agent')
    });

    res.json({
      success: true,
      message: 'Connexion administrateur réussie',
      data: {
        token: generateToken(user),
        user: user.toSafeObject()
      }
    });
  } catch (error) {
    next(error);
  }
};

exports.getDashboard = async (req, res, next) => {
  try {
    const [usersCount, menusCount, ordersCount, pendingOrders, visibleReviews, notificationsCount] = await Promise.all([
      User.count(),
      Product.count({ where: { isMenu: true } }),
      Order.count(),
      Order.count({ where: { status: 'pending' } }),
      Comment.countDocuments({ isVisible: true }),
      Notification.countDocuments()
    ]);

    const [recentOrders, latestReviews, latestLogs] = await Promise.all([
      Order.findAll({
        limit: 5,
        order: [['createdAt', 'DESC']],
        include: [{ model: User, as: 'customer', attributes: ['firstName', 'lastName', 'email'] }]
      }),
      Comment.find({}).sort({ createdAt: -1 }).limit(5).lean(),
      Log.find({}).sort({ createdAt: -1 }).limit(8).lean()
    ]);

    res.json({
      success: true,
      data: {
        stats: {
          usersCount,
          menusCount,
          ordersCount,
          pendingOrders,
          visibleReviews,
          notificationsCount
        },
        recentOrders,
        latestReviews,
        latestLogs
      }
    });
  } catch (error) {
    next(error);
  }
};
