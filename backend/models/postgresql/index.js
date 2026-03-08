const User = require('./User');
const Product = require('./Product');
const { Order, OrderItem } = require('./Order');

// ============================================
// Associations / Relations
// ============================================

// User <-> Product (Un utilisateur a plusieurs produits)
User.hasMany(Product, {
  foreignKey: 'user_id',
  as: 'products',
  onDelete: 'CASCADE'
});
Product.belongsTo(User, {
  foreignKey: 'user_id',
  as: 'owner'
});

// User <-> Order (Un utilisateur a plusieurs commandes)
User.hasMany(Order, {
  foreignKey: 'user_id',
  as: 'orders',
  onDelete: 'CASCADE'
});
Order.belongsTo(User, {
  foreignKey: 'user_id',
  as: 'customer'
});

// Order <-> OrderItem <-> Product
Order.hasMany(OrderItem, {
  foreignKey: 'order_id',
  as: 'items',
  onDelete: 'CASCADE'
});
OrderItem.belongsTo(Order, {
  foreignKey: 'order_id'
});

Product.hasMany(OrderItem, {
  foreignKey: 'product_id',
  as: 'orderItems'
});
OrderItem.belongsTo(Product, {
  foreignKey: 'product_id',
  as: 'product'
});

module.exports = {
  User,
  Product,
  Order,
  OrderItem
};