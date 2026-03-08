const { DataTypes, Model } = require('sequelize');
const { sequelize } = require('../../config/database');

class Order extends Model {
  calculateTotal() {
    if (this.OrderItems) {
      return this.OrderItems.reduce((total, item) => total + (item.price * item.quantity), 0);
    }
    return this.totalAmount;
  }
}

Order.init({
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true
  },
  orderNumber: {
    type: DataTypes.STRING(50),
    unique: true,
    field: 'order_number'
  },
  status: {
    type: DataTypes.ENUM(
      'pending', 'confirmed', 'processing',
      'shipped', 'delivered', 'cancelled', 'refunded'
    ),
    defaultValue: 'pending'
  },
  totalAmount: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false,
    defaultValue: 0,
    field: 'total_amount'
  },
  shippingAddress: {
    type: DataTypes.JSONB,
    allowNull: true,
    field: 'shipping_address'
  },
  orderDetails: {
    type: DataTypes.JSONB,
    allowNull: false,
    defaultValue: {},
    field: 'order_details'
  },
  paymentMethod: {
    type: DataTypes.STRING(50),
    allowNull: true,
    field: 'payment_method'
  },
  paymentStatus: {
    type: DataTypes.ENUM('pending', 'paid', 'failed', 'refunded'),
    defaultValue: 'pending',
    field: 'payment_status'
  },
  notes: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  userId: {
    type: DataTypes.UUID,
    allowNull: false,
    field: 'user_id',
    references: {
      model: 'users',
      key: 'id'
    }
  }
}, {
  sequelize,
  modelName: 'Order',
  tableName: 'orders',
  timestamps: true,
  underscored: true,
  hooks: {
    beforeCreate: (order) => {
      const timestamp = Date.now().toString(36).toUpperCase();
      const random = Math.random().toString(36).slice(2, 7).toUpperCase();
      order.orderNumber = `ORD-${timestamp}-${random}`;
    }
  }
});

class OrderItem extends Model {}

OrderItem.init({
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true
  },
  quantity: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 1,
    validate: { min: 1 }
  },
  price: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  orderId: {
    type: DataTypes.UUID,
    field: 'order_id',
    references: {
      model: 'orders',
      key: 'id'
    }
  },
  productId: {
    type: DataTypes.UUID,
    field: 'product_id',
    references: {
      model: 'products',
      key: 'id'
    }
  }
}, {
  sequelize,
  modelName: 'OrderItem',
  tableName: 'order_items',
  timestamps: true,
  underscored: true
});

module.exports = { Order, OrderItem };
