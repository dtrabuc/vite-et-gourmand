const { User, Product } = require('../models/postgresql');
const Comment = require('../models/mongodb/Comment');
const seedData = require('../data/seedData');
const bcrypt = require("bcryptjs");

async function ensureAdmin() {

  // Delete existing admin if exists
  await User.destroy({ where: { username: "admin" } });

  return User.create({
    username: "admin",
    email: "admin@test.com",
    password: "Admin1234!", // Plain text, will be hashed by hook
    role: "admin",
    isActive: true
  });
}

function slugify(value) {
  return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

async function ensureUser(userData) {
  const existing = await User.scope('withPassword').findOne({ where: { email: userData.email } });
  if (existing) {
    if (existing.role !== userData.role) await existing.update({ role: userData.role });
    return existing;
  }
  return User.create(userData);
}

async function seedApplicationData() {
  const owner = await ensureUser(seedData.ownerUser);
  await ensureUser(seedData.demoUser);

  for (const product of seedData.menus) {
    const slug = slugify(product.name);
    const existing = await Product.findOne({ where: { slug } });
    if (!existing) {
      await Product.create({ ...product, slug, isMenu: product.productType === 'menu', userId: owner.id });
    }
  }

  const commentsCount = await Comment.countDocuments();
  if (commentsCount === 0) {
    const products = await Product.findAll({ where: { productType: 'menu' }, order: [['createdAt', 'ASC']], limit: 3 });
    const comments = seedData.reviews.map((review, index) => ({
      productId: products[index % products.length]?.id || 'homepage',
      userId: `seed-user-${index + 1}`,
      username: review.username,
      content: review.content,
      rating: review.rating,
      isVisible: true,
      isVerifiedPurchase: true
    }));
    if (comments.length) await Comment.insertMany(comments);
  }
}

module.exports = { seedApplicationData, slugify };
