const express = require('express');
const router = express.Router();
const publicController = require('../controllers/publicController');

router.get('/menus', publicController.getMenus);
router.get('/menus/:id', publicController.getMenuById);
router.get('/catalog', publicController.getCatalog);
router.get('/reviews', publicController.getHomepageReviews);

module.exports = router;
