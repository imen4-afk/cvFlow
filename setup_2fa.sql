-- Run this once in phpMyAdmin or MySQL CLI to add 2FA support
ALTER TABLE utilisateurs
  ADD COLUMN otp_code    VARCHAR(6)  NULL DEFAULT NULL,
  ADD COLUMN otp_expires DATETIME    NULL DEFAULT NULL;
