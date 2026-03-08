require('dotenv').config();

const requiredEnvVars = [
  'PORT',
  'NODE_ENV',
  'PG_HOST',
  'PG_PORT',
  'PG_DATABASE',
  'PG_USER',
  'PG_PASSWORD',
  'MONGO_URI',
  'JWT_SECRET',
  'JWT_EXPIRES_IN'
];

// Vérification des variables requises
const missingVars = requiredEnvVars.filter(v => !process.env[v]);
if (missingVars.length > 0) {
  console.error('❌ Variables d\'environnement manquantes:', missingVars.join(', '));
  process.exit(1);
}

module.exports = {
  port: parseInt(process.env.PORT, 10) || 3000,
  nodeEnv: process.env.NODE_ENV || 'development',
  pg: {
    host: process.env.PG_HOST,
    port: parseInt(process.env.PG_PORT, 10) || 5432,
    database: process.env.PG_DATABASE,
    user: process.env.PG_USER,
    password: process.env.PG_PASSWORD
  },
  mongo: {
    uri: process.env.MONGO_URI,
    dbName: process.env.MONGO_DB_NAME || 'backend_mvc'
  },
  jwt: {
    secret: process.env.JWT_SECRET,
    expiresIn: process.env.JWT_EXPIRES_IN || '7d'
  },
  cors: {
    origin: process.env.CORS_ORIGIN || '*',
    credentials: true
  }
};