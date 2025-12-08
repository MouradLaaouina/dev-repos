/*
  # Ajouter le champ code_agence à la table users

  1. Nouveau champ
    - `code_agence` (text) - Code de l'agence de l'utilisateur
    
  2. Mise à jour
    - Ajouter le champ à la table users existante
*/

-- Ajouter le champ code_agence à la table users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS code_agence text;

-- Ajouter un commentaire pour expliquer le nouveau champ
COMMENT ON COLUMN users.code_agence IS 'Code de l''agence de l''utilisateur pour le suivi des commandes';