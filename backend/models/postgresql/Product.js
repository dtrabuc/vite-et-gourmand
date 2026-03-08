const { DataTypes, Model } = require('sequelize');
const { sequelize } = require('../../config/database');

class Product extends Model {
  getDiscountedPrice(discountPercent) {
    return Number(this.price) * (1 - discountPercent / 100);
  }

  isAvailable() {
    return this.stock > 0 && this.isActive;
  }
}

Product.init({
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true
  },
  name: {
    type: DataTypes.STRING(200),
    allowNull: false,
    validate: {
      notEmpty: { msg: 'Le nom du produit est requis' },
      len: { args: [2, 200], msg: 'Le nom doit contenir entre 2 et 200 caractères' }
    }
  },
  slug: {
    type: DataTypes.STRING(250),
    unique: true
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  price: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false,
    validate: {
      min: { args: [0], msg: 'Le prix ne peut pas être négatif' }
    }
  },
  stock: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
    validate: {
      min: { args: [0], msg: 'Le stock ne peut pas être négatif' }
    }
  },
  category: {
    type: DataTypes.STRING(100),
    allowNull: true
  },
  productType: {
    type: DataTypes.ENUM('menu', 'alacarte', 'boisson', 'digestif'),
    allowNull: false,
    defaultValue: 'menu',
    field: 'product_type'
  },
  theme: {
    type: DataTypes.STRING(100),
    allowNull: true
  },
  regime: {
    type: DataTypes.STRING(100),
    allowNull: true
  },
  personsMin: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 1,
    field: 'persons_min'
  },
  conditions: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  dishes: {
    type: DataTypes.JSONB,
    allowNull: false,
    defaultValue: { entrees: [], plats: [], desserts: [], boissons: [] }
  },
  metadata: {
    type: DataTypes.JSONB,
    allowNull: false,
    defaultValue: {}
  },
  tags: {
    type: DataTypes.JSONB,
    allowNull: false,
    defaultValue: []
  },
  isMenu: {
    type: DataTypes.BOOLEAN,
    allowNull: false,
    defaultValue: true,
    field: 'is_menu'
  },
  imageUrl: {
    type: DataTypes.STRING(500),
    allowNull: true,
    field: 'image_url'
  },
  isActive: {
    type: DataTypes.BOOLEAN,
    defaultValue: true,
    field: 'is_active'
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
  modelName: 'Product',
  tableName: 'products',
  timestamps: true,
  underscored: true,
  hooks: {
    beforeValidate: (product) => {
      if (product.name && !product.slug) {
        product.slug = product.name
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/(^-|-$)/g, '');
      }
    }
  }
});

module.exports = Product;
