const express = require('express');
const router = express.Router();

const publicPages = [
  'index',
  'menus',
  'detail-menus',
  'commander',
  'login',
  'compte',
  'contact',
  'recap-commande'
];

router.get('/', (req, res) => res.render('index'));
router.get('/index.html', (req, res) => res.render('index'));

publicPages.filter((page) => page !== 'index').forEach((page) => {
  router.get(`/${page}.html`, (req, res) => res.render(page));
});

router.get('/admin', (req, res) => res.redirect('/admin/dashboard'));
router.get('/admin/login', (req, res) => res.render('admin/login'));
router.get('/admin/dashboard', (req, res) => res.render('admin/dashboard'));

module.exports = router;
