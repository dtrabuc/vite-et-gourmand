const env = require('./env');

const corsOptions = {
  origin: function (origin, callback) {
    const allowedOrigins = env.cors.origin === '*' 
      ? ['*'] 
      : env.cors.origin.split(',').map(o => o.trim());

    if (!origin || allowedOrigins.includes('*') || allowedOrigins.includes(origin)) {
      callback(null, true);
    } else {
      callback(new Error('Non autorisé par la politique CORS'));
    }
  },
  credentials: env.cors.credentials,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: [
    'Content-Type',
    'Authorization',
    'X-Requested-With',
    'Accept',
    'Origin'
  ],
  exposedHeaders: ['X-Total-Count', 'X-Page-Count'],
  maxAge: 86400 // 24 heures
};

module.exports = corsOptions;