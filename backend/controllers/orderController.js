const { sequelize } = require('../config/database');
const { Order, OrderItem, Product } = require('../models/postgresql');
const Notification = require('../models/mongodb/Notification');
const Log = require('../models/mongodb/Log');
const { AppError } = require('../middleware/errorHandler');

function calculateOrderTotal(items, orderDetails = {}, shippingAddress = {}) {
  const guestCount = Number(orderDetails.guestCount || 1);
  let subtotal = items.reduce((sum, item) => sum + (Number(item.price) * Number(item.quantity)), 0);
  const reductions = [];
  if (orderDetails.digestif && orderDetails.modeService === 'surPlace') subtotal += 8 * guestCount;
  if (subtotal > 100) { subtotal *= 0.9; reductions.push('10% (total > 100€)'); }
  if (guestCount > 6) { subtotal *= 0.75; reductions.push('25% (> 6 personnes)'); }
  const city = String(shippingAddress.city || '').trim().toLowerCase();
  const deliveryFee = Number(shippingAddress.deliveryFee || 0) || (city && city !== 'bordeaux' ? 5 : 0);
  subtotal += deliveryFee;
  return { totalAmount: Number(subtotal.toFixed(2)), deliveryFee, reductions };
}

exports.createOrder = async (req, res, next) => {
  const transaction = await sequelize.transaction();
  try {
    const { items, shippingAddress, paymentMethod, notes, orderDetails = {} } = req.body;
    if (!items || items.length === 0) throw new AppError('La commande doit contenir au moins un article', 400);
    const orderItems = [];

    for (const item of items) {
      const product = await Product.findByPk(item.productId, { transaction });
      if (!product) throw new AppError(`Produit ${item.productId} non trouvé`, 404);
      if (product.stock < item.quantity) throw new AppError(`Stock insuffisant pour "${product.name}" (dispo: ${product.stock})`, 400);
      orderItems.push({ productId: product.id, quantity: item.quantity, price: Number(product.price), productName: product.name });
      await product.update({ stock: product.stock - item.quantity }, { transaction });
    }

    const pricing = calculateOrderTotal(orderItems, orderDetails, shippingAddress);
    const order = await Order.create({
      userId: req.user.id,
      totalAmount: pricing.totalAmount,
      shippingAddress: { ...shippingAddress, deliveryFee: pricing.deliveryFee },
      orderDetails: { ...orderDetails, guestCount: Number(orderDetails.guestCount || orderItems[0]?.quantity || 1), reductions: pricing.reductions },
      paymentMethod,
      notes
    }, { transaction });

    await OrderItem.bulkCreate(orderItems.map((item) => ({ orderId: order.id, productId: item.productId, quantity: item.quantity, price: item.price })), { transaction });
    await transaction.commit();

    await Notification.create({ userId: req.user.id, type: 'order_created', title: 'Commande créée', message: `Votre commande ${order.orderNumber} a été créée.`, data: { orderId: order.id, orderNumber: order.orderNumber } });
    await Log.createLog({ level: 'info', action: 'ORDER_CREATE', message: `Commande créée: ${order.orderNumber}`, userId: req.user.id, metadata: { orderId: order.id, totalAmount: pricing.totalAmount } });

    const fullOrder = await Order.findByPk(order.id, { include: [{ model: OrderItem, as: 'items', include: [{ model: Product, as: 'product' }] }] });
    res.status(201).json({ success: true, message: 'Commande créée avec succès', data: { order: fullOrder } });
  } catch (error) {
    await transaction.rollback();
    next(error);
  }
};

exports.getUserOrders = async (req, res, next) => {
  try {
    const { page = 1, limit = 10, status } = req.query;
    const where = { userId: req.user.id };
    if (status) where.status = status;
    const { rows: orders, count: total } = await Order.findAndCountAll({
      where,
      include: [{ model: OrderItem, as: 'items', include: [{ model: Product, as: 'product' }] }],
      order: [['createdAt', 'DESC']],
      limit: parseInt(limit, 10),
      offset: (parseInt(page, 10) - 1) * parseInt(limit, 10)
    });
    res.json({ success: true, data: { orders, pagination: { total, page: parseInt(page, 10), pages: Math.ceil(total / parseInt(limit, 10)), limit: parseInt(limit, 10) } } });
  } catch (error) { next(error); }
};

exports.updateOrderStatus = async (req, res, next) => {
  try {
    const { status } = req.body;
    const order = await Order.findByPk(req.params.id);
    if (!order) throw new AppError('Commande non trouvée', 404);
    const validTransitions = { pending: ['confirmed', 'cancelled'], confirmed: ['processing', 'cancelled'], processing: ['shipped', 'cancelled'], shipped: ['delivered'], delivered: ['refunded'], cancelled: [], refunded: [] };
    if (!validTransitions[order.status]?.includes(status)) throw new AppError(`Transition invalide: ${order.status} -> ${status}`, 400);
    const previousStatus = order.status;
    await order.update({ status });
    await Notification.create({ userId: order.userId, type: 'order_updated', title: 'Commande mise à jour', message: `Votre commande ${order.orderNumber} est "${status}".`, data: { orderId: order.id, newStatus: status }, priority: status === 'shipped' ? 'high' : 'medium' });
    await Log.createLog({ level: 'info', action: 'ORDER_STATUS_UPDATE', message: `Commande ${order.orderNumber}: ${status}`, userId: req.user.id, metadata: { orderId: order.id, oldStatus: previousStatus, newStatus: status } });
    res.json({ success: true, message: 'Statut de la commande mis à jour', data: { order } });
  } catch (error) { next(error); }
};
