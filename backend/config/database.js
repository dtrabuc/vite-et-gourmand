const { Sequelize } = require('sequelize');
const mongoose = require('mongoose');
require('dotenv').config();

// =======================
// Configuration PostgreSQL
// =======================
const sequelize = new Sequelize(
  process.env.PG_DATABASE,
  process.env.PG_USER,
  process.env.PG_PASSWORD,
  {
    host: process.env.PG_HOST,
    port: process.env.PG_PORT || 5432,
    dialect: 'postgres',
    logging: process.env.NODE_ENV === 'development' ? console.log : false,
    pool: {
      max: 10,
      min: 0,
      acquire: 30000,
      idle: 10000
    },
    define: {
      timestamps: true,
      underscored: true,
      freezeTableName: true
    }
  }
);

// =======================
// Connexion MongoDB
// =======================
const connectMongoDB = async () => {
  try {
    await mongoose.connect(process.env.MONGO_URI, {
      dbName: process.env.MONGO_DB_NAME || 'vite-et-gourmand'
    });
    console.log('MongoDB connecté avec succès');
  } catch (error) {
    console.error('❌ Erreur connexion MongoDB:', error.message);
    process.exit(1);
  }
};

// =======================
// Connexion PostgreSQL
// =======================
const connectPostgreSQL = async () => {
  try {
    await sequelize.authenticate();
    console.log('✅ PostgreSQL connecté avec succès');
    
    // Synchroniser les modèles en développement
    if (process.env.NODE_ENV === 'development') {
      await sequelize.sync({ alter: true });
      console.log('📦 Modèles PostgreSQL synchronisés');
    }
  } catch (error) {
    console.error('❌ Erreur connexion PostgreSQL:', error.message);
    process.exit(1);
  }
};

module.exports = {
  sequelize,
  connectMongoDB,
  connectPostgreSQL
};