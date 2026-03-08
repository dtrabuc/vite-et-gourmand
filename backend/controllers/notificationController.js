const Notification = require('../models/mongodb/Notification');
const { AppError } = require('../middleware/errorHandler');

// ============================================
// GET /api/notifications
// ============================================
exports.getNotifications = async (req, res, next) => {
  try {
    const { page = 1, limit = 20, unreadOnly = false } = req.query;

    const result = await Notification.getUserNotifications(
      req.user.id,
      {
        page: parseInt(page),
        limit: parseInt(limit),
        unreadOnly: unreadOnly === 'true'
      }
    );

    const unreadCount = await Notification.getUnreadCount(req.user.id);

    res.json({
      success: true,
      data: {
        ...result,
        unreadCount
      }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// PATCH /api/notifications/:id/read
// ============================================
exports.markAsRead = async (req, res, next) => {
  try {
    const notification = await Notification.findOne({
      _id: req.params.id,
      userId: req.user.id
    });

    if (!notification) {
      throw new AppError('Notification non trouvée', 404);
    }

    await notification.markAsRead();

    res.json({
      success: true,
      message: 'Notification marquée comme lue'
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// PATCH /api/notifications/read-all
// ============================================
exports.markAllAsRead = async (req, res, next) => {
  try {
    const result = await Notification.markAllAsRead(req.user.id);

    res.json({
      success: true,
      message: `${result.modifiedCount} notifications marquées comme lues`
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// DELETE /api/notifications/:id
// ============================================
exports.deleteNotification = async (req, res, next) => {
  try {
    const result = await Notification.deleteOne({
      _id: req.params.id,
      userId: req.user.id
    });

    if (result.deletedCount === 0) {
      throw new AppError('Notification non trouvée', 404);
    }

    res.json({
      success: true,
      message: 'Notification supprimée'
    });
  } catch (error) {
    next(error);
  }
};