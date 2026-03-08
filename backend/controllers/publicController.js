const { Op } = require('sequelize');
const { Product } = require('../models/postgresql');
const Comment = require('../models/mongodb/Comment');
const seedData = require('../data/seedData');

function formatProduct(product) {
  return {
    id: product.id,
    nom: product.name,
    categorie: product.category,
    typeProduit: product.productType,
    prix: Number(product.price),
    personnesMin: product.personsMin,
    image: product.imageUrl,
    description: product.description,
    tags: product.tags || [],
    theme: product.theme,
    regime: product.regime,
    conditions: product.conditions,
    plats: product.dishes || { entrees: [], plats: [], desserts: [], boissons: [] },
    stock: product.stock,
    metadata: product.metadata || {}
  };
}

exports.getMenus = async (req, res, next) => {
  try {
    const { categorie, theme, regime, maxPrice, minPrice, minPersons } = req.query;
    const where = { isActive: true, productType: 'menu' };
    if (categorie && categorie !== 'all') where.category = categorie;
    if (theme) where.theme = theme;
    if (regime) where.regime = regime;
    if (minPrice) where.price = { ...(where.price || {}), [Op.gte]: Number(minPrice) };
    if (maxPrice) where.price = { ...(where.price || {}), [Op.lte]: Number(maxPrice) };
    if (minPersons) where.personsMin = { [Op.gte]: Number(minPersons) };

    const menus = await Product.findAll({ where, order: [['price', 'ASC']] });
    res.json({ success: true, data: { menus: menus.map(formatProduct) } });
  } catch (error) {
    next(error);
  }
};

exports.getMenuById = async (req, res, next) => {
  try {
    const product = await Product.findOne({ where: { id: req.params.id, productType: 'menu' } });
    if (!product) return res.status(404).json({ success: false, message: 'Menu introuvable' });
    const rating = await Comment.getAverageRating(product.id);
    res.json({ success: true, data: { menu: { ...formatProduct(product), averageRating: rating.averageRating || 0, totalReviews: rating.totalReviews || 0 } } });
  } catch (error) {
    next(error);
  }
};

exports.getCatalog = async (req, res, next) => {
  try {
    const products = await Product.findAll({ where: { isActive: true }, order: [['productType', 'ASC'], ['category', 'ASC'], ['price', 'ASC']] });
    const catalog = { menus: [], aLaCarte: { classiques: [], vegan: [], sansPorc: [] }, boissons: seedData.beverages, digestifs: [] };

    for (const product of products) {
      const item = formatProduct(product);
      if (product.productType === 'menu') catalog.menus.push(item);
      else if (product.productType === 'alacarte') (catalog.aLaCarte[product.category] ||= []).push(item);
      else if (product.productType === 'digestif') catalog.digestifs = item.plats.boissons || [];
    }

    res.json({ success: true, data: catalog });
  } catch (error) {
    next(error);
  }
};

exports.getHomepageReviews = async (req, res, next) => {
  try {
    const comments = await Comment.find({ isVisible: true }).sort({ createdAt: -1 }).limit(6).lean();
    const avis = comments.map((comment, index) => ({ id: comment._id || index + 1, auteur: comment.username, note: comment.rating, commentaire: comment.content, date: new Date(comment.createdAt || Date.now()).toLocaleDateString('fr-FR') }));
    res.json({ success: true, data: { avis } });
  } catch (error) {
    next(error);
  }
};
