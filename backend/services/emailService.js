const nodemailer = require('nodemailer');

class EmailService {
  constructor() {
    this.transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST || 'smtp.gmail.com',
      port: parseInt(process.env.SMTP_PORT, 10) || 587,
      secure: false,
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASSWORD
      }
    });
  }

  async sendEmail({ to, subject, html, text }) {
    try {
      const mailOptions = {
        from: `"${process.env.APP_NAME || 'Backend MVC'}" <${process.env.SMTP_USER}>`,
        to,
        subject,
        html,
        text
      };

      const info = await this.transporter.sendMail(mailOptions);
      console.log('📧 Email envoyé:', info.messageId);
      return info;
    } catch (error) {
      console.error('❌ Erreur envoi email:', error.message);
      throw error;
    }
  }

  // Templates d'emails
  async sendWelcomeEmail(user) {
    return this.sendEmail({
      to: user.email,
      subject: 'Bienvenue !',
      html: `
        <h1>Bienvenue ${user.firstName || user.username} !</h1>
        <p>Votre compte a été créé avec succès.</p>
        <p>Commencez dès maintenant à explorer notre plateforme.</p>
      `
    });
  }

  async sendOrderConfirmation(user, order) {
    return this.sendEmail({
      to: user.email,
      subject: `Commande ${order.orderNumber} confirmée`,
      html: `
        <h1>Commande confirmée</h1>
        <p>Votre commande <strong>${order.orderNumber}</strong> a été confirmée.</p>
        <p>Montant total: <strong>${order.totalAmount} €</strong></p>
        <p>Statut: ${order.status}</p>
      `
    });
  }

  async sendPasswordResetEmail(user, resetToken) {
    const resetUrl = `${process.env.FRONTEND_URL}/reset-password?token=${resetToken}`;
    return this.sendEmail({
      to: user.email,
      subject: 'Réinitialisation de mot de passe',
      html: `
        <h1>Réinitialisation du mot de passe</h1>
        <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe:</p>
        <a href="${resetUrl}">Réinitialiser mon mot de passe</a>
        <p>Ce lien expire dans 1 heure.</p>
        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
      `
    });
  }
}

module.exports = new EmailService();