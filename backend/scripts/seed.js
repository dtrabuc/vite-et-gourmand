const { connectPostgreSQL, connectMongoDB } = require('../config/database');
const { seedApplicationData } = require('../services/seedService');

(async () => {
  try {
    await connectPostgreSQL();
    await connectMongoDB();

    await seedApplicationData();

    console.log('Seed terminé.');
    process.exit(0);

  } catch (error) {

    console.error('Erreur de seed:', error);
    process.exit(1);
  }
})();