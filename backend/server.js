const app = require('./app');
const env = require('./config/env');
const { connectPostgreSQL, connectMongoDB } = require('./config/database');
const { seedApplicationData } = require('./services/seedService');

const startServer = async () => {
  try {
    console.log('Connexion aux bases de données.');

    await Promise.all([
      connectPostgreSQL(),
      connectMongoDB()
    ]);

    await seedApplicationData();

    const server = app.listen(env.port, () => {
      console.log(`API démarrée sur http://localhost:${env.port}`);
    });

    const gracefulShutdown = async (signal) => {
      console.log(` Signal ${signal} reçu. Arrêt en cours.`);
      server.close(async () => {
        try {
          const { sequelize } = require('./config/database');
          const mongoose = require('mongoose');
          await sequelize.close();
          await mongoose.connection.close();
          process.exit(0);
        } catch (error) {
          console.error('Erreur lors de l\'arrêt:', error);
          process.exit(1);
        }
      });
    };

    process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
    process.on('SIGINT', () => gracefulShutdown('SIGINT'));
    process.on('unhandledRejection', (reason) => console.error('Unhandled Rejection:', reason));
    process.on('uncaughtException', (error) => {
      console.error('Uncaught Exception:', error);
      gracefulShutdown('UNCAUGHT_EXCEPTION');
    });
  } catch (error) {
    console.error('Erreur au démarrage:', error);
    process.exit(1);
  }
};

startServer();
