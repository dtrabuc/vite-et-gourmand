const { Op } = require('sequelize');
const { Product, User } = require('../models/postgresql');
const Comment = require('../models/mongodb/Comment');
const Log = require('../models/mongodb/Log');
const { AppError } = require('../middleware/errorHandler');

// ============================================
// GET /api/products
// ============================================
exports.getAllProducts = async (req, res, next) => {
  try {
    const {
      page = 1,
      limit = 10,
      search,
      category,
      minPrice,
      maxPrice,
      sortBy = 'createdAt',
      order = 'DESC'
    } = req.query;

    const offset = (page - 1) * limit;
    const where = { isActive: true };

    // Filtres dynamiques
    if (search) {
      where[Op.or] = [
        { name: { [Op.iLike]: `%${search}%` } },
        { description: { [Op.iLike]: `%${search}%` } }
      ];
    }
    if (category) where.category = category;
    if (minPrice) where.price = { ...where.price, [Op.gte]: minPrice };
    if (maxPrice) where.price = { ...where.price, [Op.lte]: maxPrice };

    const { rows: products, count: total } = await Product.findAndCountAll({
      where,
      include: [{
        model: User,
        as: 'owner',
        attributes: ['id', 'username', 'email']
      }],
      order: [[sortBy, order.toUpperCase()]],
      limit: parseInt(limit),
      offset: parseInt(offset)
    });

    // Enrichir avec les notes MongoDB
    const enrichedProducts = await Promise.all(
      products.map(async (product) => {
        const rating = await Comment.getAverageRating(product.id);
        return {
          ...product.toJSON(),
          averageRating: rating.averageRating,
          totalReviews: rating.totalReviews
        };
      })
    );

    res.json({
      success: true,
      data: {
        products: enrichedProducts,
        pagination: {
          total,
          page: parseInt(page),
          pages: Math.ceil(total / limit),
          limit: parseInt(limit)
        }
      }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// GET /api/products/:id
// ============================================
exports.getProductById = async (req, res, next) => {
  try {
    const product = await Product.findByPk(req.params.id, {
      include: [{
        model: User,
        as: 'owner',
        attributes: ['id', 'username']
      }]
    });

    if (!product) {
      throw new AppError('Produit non trouvé', 404);
    }

    // Récupérer les commentaires depuis MongoDB
    const { comments, pagination } = await Comment.getPaginatedComments(
      product.id,
      { page: 1, limit: 5 }
    );
    const rating = await Comment.getAverageRating(product.id);

    res.json({
      success: true,
      data: {
        product: {
          ...product.toJSON(),
          averageRating: rating.averageRating,
          totalReviews: rating.totalReviews,
          recentComments: comments,
          commentsPagination: pagination
        }
      }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// POST /api/products
// ============================================
exports.createProduct = async (req, res, next) => {
  try {
    const { name, description, price, stock, category, imageUrl } = req.body;

    const product = await Product.create({
      name,
      description,
      price,
      stock,
      category,
      imageUrl,
      userId: req.user.id
    });

    await Log.createLog({
      level: 'info',
      action: 'PRODUCT_CREATE',
      message: `Nouveau produit créé: ${name}`,
      userId: req.user.id,
      metadata: { productId: product.id }
    });

    res.status(201).json({
      success: true,
      message: 'Produit créé avec succès',
      data: { product }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// PUT /api/products/:id
// ============================================
exports.updateProduct = async (req, res, next) => {
  try {
    const product = await Product.findByPk(req.params.id);

    if (!product) {
      throw new AppError('Produit non trouvé', 404);
    }

    // Vérifier la propriété (sauf admin)
    if (product.userId !== req.user.id && req.user.role !== 'admin') {
      throw new AppError('Non autorisé à modifier ce produit', 403);
    }

    const updatedProduct = await product.update(req.body);

    await Log.createLog({
      level: 'info',
      action: 'PRODUCT_UPDATE',
      message: `Produit mis à jour: ${product.name}`,
      userId: req.user.id,
      metadata: { productId: product.id, changes: req.body }
    });

    res.json({
      success: true,
      message: 'Produit mis à jour',
      data: { product: updatedProduct }
    });
  } catch (error) {
    next(error);
  }
};

// ============================================
// DELETE /api/products/:id
// ============================================
exports.deleteProduct = async (req, res, next) => {
  try {
    const product = await Product.findByPk(req.params.id);

    if (!product) {
      throw new AppError('Produit non trouvé', 404);
    }

    if (product.userId !== req.user.id && req.user.role !== 'admin') {
      throw new AppError('Non autorisé à supprimer ce produit', 403);
    }

    await product.destroy();

    await Log.createLog({
      level: 'info',
      action: 'PRODUCT_DELETE',
      message: `Produit supprimé: ${product.name}`,
      userId: req.user.id,
      metadata: { productId: product.id }
    });

    res.json({
      success: true,
      message: 'Produit supprimé avec succès'
    });
  } catch (error) {
    next(error);
  }
};