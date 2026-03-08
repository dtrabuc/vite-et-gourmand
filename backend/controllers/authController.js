const jwt = require('jsonwebtoken');
const { User } = require('../models/postgresql');
const Log = require('../models/mongodb/Log');
const env = require('../config/env');
const { AppError } = require('../middleware/errorHandler');

const RESERVED_USERNAMES = new Set(['admin', 'administrator', 'moderator', 'root', 'staff', 'support']);
const generateToken = (user) => jwt.sign({ id: user.id, email: user.email, role: user.role }, env.jwt.secret, { expiresIn: env.jwt.expiresIn });

exports.register = async (req, res, next) => {
  try {
    const { username, email, password, firstName, lastName, role } = req.body;
    if (role && role !== 'user') throw new AppError('La création d’un compte administrateur est réservée au back-office.', 403);
    if (RESERVED_USERNAMES.has(String(username || '').trim().toLowerCase())) throw new AppError('Ce nom d’utilisateur est réservé.', 400);
    if (await User.findOne({ where: { email } })) throw new AppError('Cet email est déjà utilisé', 409);
    if (await User.findOne({ where: { username } })) throw new AppError('Ce nom d’utilisateur est déjà utilisé', 409);

    const user = await User.create({ username, email, password, firstName, lastName, role: 'user' });
    const token = generateToken(user);
    await Log.createLog({ level: 'info', action: 'USER_REGISTER', message: `Nouvel utilisateur inscrit: ${email}`, userId: user.id, ip: req.ip, userAgent: req.get('User-Agent') });
    res.status(201).json({ success: true, message: 'Inscription réussie', data: { user: user.toSafeObject(), token } });
  } catch (error) { next(error); }
};

exports.login = async (req, res, next) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) throw new AppError('Email et mot de passe requis', 400);
    const user = await User.scope('withPassword').findOne({ where: { email } });
    if (!user || !(await user.comparePassword(password))) {
      await Log.createLog({ level: 'warn', action: 'LOGIN_FAILED', message: `Tentative de connexion échouée: ${email}`, ip: req.ip, userAgent: req.get('User-Agent') });
      throw new AppError('Email ou mot de passe incorrect', 401);
    }
    if (!user.isActive) throw new AppError('Compte désactivé', 403);
    await user.update({ lastLoginAt: new Date() });
    const token = generateToken(user);
    await Log.createLog({ level: 'info', action: 'USER_LOGIN', message: `Connexion réussie: ${email}`, userId: user.id, ip: req.ip, userAgent: req.get('User-Agent') });
    res.json({ success: true, message: 'Connexion réussie', data: { user: user.toSafeObject(), token } });
  } catch (error) { next(error); }
};

exports.getProfile = async (req, res, next) => {
  try {
    const user = await User.findByPk(req.user.id, { include: ['orders'] });
    if (!user) throw new AppError('Utilisateur non trouvé', 404);
    res.json({ success: true, data: { user } });
  } catch (error) { next(error); }
};

exports.updateProfile = async (req, res, next) => {
  try {
    const { firstName, lastName, username } = req.body;
    const user = await User.findByPk(req.user.id);
    if (!user) throw new AppError('Utilisateur non trouvé', 404);
    if (username && RESERVED_USERNAMES.has(String(username).trim().toLowerCase())) throw new AppError('Ce nom d’utilisateur est réservé.', 400);
    await user.update({ firstName, lastName, username });
    await Log.createLog({ level: 'info', action: 'PROFILE_UPDATE', message: `Profil mis à jour: ${user.email}`, userId: user.id });
    res.json({ success: true, message: 'Profil mis à jour', data: { user } });
  } catch (error) { next(error); }
};

exports.changePassword = async (req, res, next) => {
  try {
    const { currentPassword, newPassword } = req.body;
    const user = await User.scope('withPassword').findByPk(req.user.id);
    if (!(await user.comparePassword(currentPassword))) throw new AppError('Mot de passe actuel incorrect', 400);
    await user.update({ password: newPassword });
    await Log.createLog({ level: 'info', action: 'PASSWORD_CHANGE', message: `Mot de passe changé: ${user.email}`, userId: user.id });
    res.json({ success: true, message: 'Mot de passe modifié avec succès' });
  } catch (error) { next(error); }
};
