const Joi = require('joi');
const { AppError } = require('./errorHandler');

const passwordRule = Joi.string().min(8).max(128)
  .pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/);

const schemas = {
  register: Joi.object({
    username: Joi.string().trim().min(3).max(50).required()
      .messages({
        'string.min': 'Le nom doit contenir au moins 3 caractères',
        'string.max': 'Le nom ne peut pas dépasser 50 caractères',
        'any.required': 'Le nom d\'utilisateur est requis'
      }),
    email: Joi.string().trim().email().required()
      .messages({
        'string.email': 'Email invalide',
        'any.required': 'L\'email est requis'
      }),
    password: passwordRule.required()
      .messages({
        'string.min': 'Le mot de passe doit contenir au moins 8 caractères',
        'string.pattern.base': 'Le mot de passe doit contenir une majuscule, une minuscule et un chiffre',
        'any.required': 'Le mot de passe est requis'
      }),
    firstName: Joi.string().trim().max(100).allow('', null).optional(),
    lastName: Joi.string().trim().max(100).allow('', null).optional()
  }).unknown(false),

  login: Joi.object({
    email: Joi.string().trim().required(),
    password: Joi.string().required()
  }).unknown(false),

  changePassword: Joi.object({
    currentPassword: Joi.string().required(),
    newPassword: passwordRule.required()
  }).unknown(false),

  createProduct: Joi.object({
    name: Joi.string().min(2).max(200).required(),
    description: Joi.string().max(5000).allow('', null).optional(),
    price: Joi.number().min(0).precision(2).required(),
    stock: Joi.number().integer().min(0).default(0),
    category: Joi.string().max(100).allow('', null).optional(),
    imageUrl: Joi.string().uri().max(500).allow('', null).optional()
  }).unknown(false),

  updateProduct: Joi.object({
    name: Joi.string().min(2).max(200).optional(),
    description: Joi.string().max(5000).allow('', null).optional(),
    price: Joi.number().min(0).precision(2).optional(),
    stock: Joi.number().integer().min(0).optional(),
    category: Joi.string().max(100).allow('', null).optional(),
    imageUrl: Joi.string().uri().max(500).allow('', null).optional(),
    isActive: Joi.boolean().optional()
  }).min(1).unknown(false),

  createOrder: Joi.object({
    items: Joi.array().items(
      Joi.object({
        productId: Joi.string().uuid().required(),
        quantity: Joi.number().integer().min(1).required()
      })
    ).min(1).required(),
    shippingAddress: Joi.object({
      street: Joi.string().required(),
      city: Joi.string().required(),
      zipCode: Joi.string().required(),
      country: Joi.string().required(),
      date: Joi.string().allow('', null).optional(),
      time: Joi.string().allow('', null).optional(),
      deliveryFee: Joi.number().min(0).optional()
    }).required(),
    paymentMethod: Joi.string().valid(
      'card', 'paypal', 'bank_transfer', 'on_quote'
    ).required(),
    notes: Joi.string().max(2000).allow('', null).optional(),
    orderDetails: Joi.object({
      serviceType: Joi.string().valid('menu', 'plat').optional(),
      modeService: Joi.string().valid('surPlace', 'aEmporter').optional(),
      guestCount: Joi.number().integer().min(1).optional(),
      entree: Joi.string().allow('', null).optional(),
      plat: Joi.string().allow('', null).optional(),
      dessert: Joi.string().allow('', null).optional(),
      boisson: Joi.string().allow('', null).optional(),
      digestif: Joi.string().allow('', null).optional(),
      baseUnitPrice: Joi.number().min(0).optional(),
      discounts: Joi.array().items(Joi.string()).optional()
    }).optional()
  }).unknown(false),

  updateOrderStatus: Joi.object({
    status: Joi.string().valid(
      'confirmed', 'processing', 'shipped',
      'delivered', 'cancelled', 'refunded'
    ).required()
  }).unknown(false),

  createComment: Joi.object({
    content: Joi.string().min(5).max(2000).required(),
    rating: Joi.number().integer().min(1).max(5).required()
  }).unknown(false)
};

const validate = (schema) => {
  return (req, res, next) => {
    const { error, value } = schema.validate(req.body, {
      abortEarly: false,
      stripUnknown: true
    });

    if (error) {
      const errors = error.details.map((detail) => ({
        field: detail.path.join('.'),
        message: detail.message
      }));

      return next(new AppError('Données invalides', 422, errors));
    }

    req.body = value;
    next();
  };
};

module.exports = { validate, schemas };
