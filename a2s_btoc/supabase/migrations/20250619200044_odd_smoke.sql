/*
  # Ajouter le champ code_agence à la table contacts

  1. Nouveau champ
    - Ajouter code_agence à la table contacts pour tracer l'agence de chaque commande
    
  2. Commentaire
    - Expliquer l'utilisation du champ pour le suivi des commandes par agence
*/

-- Ajouter le champ code_agence à la table contacts
ALTER TABLE contacts 
ADD COLUMN IF NOT EXISTS code_agence text;

-- Ajouter un commentaire pour expliquer le nouveau champ
COMMENT ON COLUMN contacts.code_agence IS 'Code de l''agence de l''utilisateur pour le suivi des commandes';