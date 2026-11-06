-- À exécuter une fois sur une base existante avant de déployer cette version.
ALTER TABLE `users`
  ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- Les inscriptions encore en attente sous l'ancien système ont déjà choisi leur mot de passe.
-- On les active et on leur impose de le remplacer à la première connexion.
UPDATE `users`
SET `is_active` = 1,
    `must_change_password` = 1
WHERE `role` = 'user'
  AND `is_active` = 0
  AND `registration_token_hash` IS NOT NULL;

ALTER TABLE `users`
  DROP INDEX `idx_users_registration_token`,
  DROP COLUMN `registration_token_hash`,
  DROP COLUMN `registration_token_expires_at`;
