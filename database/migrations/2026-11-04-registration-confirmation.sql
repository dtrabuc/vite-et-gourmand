-- À exécuter une fois sur une base existante avant de déployer l'inscription
-- avec confirmation d'adresse e-mail.
ALTER TABLE `users`
  ADD COLUMN `registration_token_hash` CHAR(64) NULL AFTER `reset_token_expires_at`,
  ADD COLUMN `registration_token_expires_at` DATETIME NULL AFTER `registration_token_hash`,
  ADD KEY `idx_users_registration_token` (`registration_token_hash`);
