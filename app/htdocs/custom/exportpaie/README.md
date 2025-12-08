# Module Export Paie pour Dolibarr

## Description

Module Dolibarr permettant l'export des données de paie vers les organismes compétents marocains (CNSS et CIMR) selon les cahiers des charges officiels.

## Fonctionnalités

### Export CNSS (e-BDS)

- Génération de fichiers BDS (Bordereau De Salaires) conformes au cahier des charges CNSS
- Import de fichier préétabli fourni par la CNSS
- Support des déclarations principales et complémentaires
- Gestion des 7 types d'enregistrements (B00 à B06)
- Format : texte ASCII/ANSI, 260 caractères par ligne
- Périodicité : mensuelle

### Export CIMR

- Génération de fichiers de déclaration CIMR trimestriels
- Support de 3 régimes :
  - **Al Kamil** : Régime complet
  - **Al Mounassib** : Régime pour PME
  - **TCNSS** : Système à 2 tranches (≤18000 DH et >18000 DH)
- Format : texte fixe, 190 caractères par ligne, 22 champs
- Périodicité : trimestrielle (T1, T2, T3, T4)

## Prérequis

- Dolibarr 22.0 ou supérieur
- Module PaieDolibarr activé et configuré
- PHP 7.4 ou supérieur
- Extension PHP mb_string

## Installation

1. Copier le dossier `exportpaie` dans le répertoire `htdocs/custom/` de votre installation Dolibarr

2. Se connecter à Dolibarr en tant qu'administrateur

3. Aller dans **Accueil → Configuration → Modules/Applications**

4. Rechercher "Export Paie" et activer le module

5. Configurer le module via **RH → Export Paie → Configuration**

## Configuration

### Configuration CNSS

- **Numéro d'affilié CNSS** : Votre numéro d'affiliation à la CNSS (7 chiffres)
- **Plafond CNSS** : Montant du plafond CNSS (par défaut 6000 DH)

### Configuration CIMR

- **Numéro d'adhérent CIMR** : Votre numéro d'adhésion à la CIMR (6 chiffres)
- **Régime par défaut** : Al Kamil, Al Mounassib ou TCNSS
- **Catégorie par défaut** : Catégorie CIMR (0-99)
- **Taux de cotisation par défaut** : De 3% à 12%

## Utilisation

### Export CNSS

1. Aller dans **RH → Export Paie → Export CNSS**

2. (Optionnel) Importer le fichier préétabli fourni par la CNSS
   - Le préétabli contient les allocations familiales et la liste des salariés connus

3. Sélectionner la période (année et mois)

4. Cocher "Déclaration complémentaire" si nécessaire

5. Cliquer sur "Générer le fichier BDS"

6. Le fichier est généré et téléchargé automatiquement

**Format du nom de fichier :**
- Déclaration principale : `DS_NNNNNNN_AAAAMM.txt`
- Déclaration complémentaire : `DSC1_NNNNNNN_AAAAMM.txt`

### Export CIMR

1. Aller dans **RH → Export Paie → Export CIMR**

2. Sélectionner la période (année et trimestre)

3. Cliquer sur "Prévisualiser" pour voir les données avant export

4. Vérifier les données dans le tableau récapitulatif

5. Cliquer sur "Générer le fichier CIMR"

6. Le fichier est généré et téléchargé automatiquement

**Format du nom de fichier :** `CIMR_NNNNNN_TN_AAAA.txt`

## Formats de fichiers

### Format CNSS (BDS)

Le fichier BDS contient 7 types d'enregistrements, chacun sur une ligne de 260 caractères :

- **B00** : En-tête (informations sur l'employeur)
- **B01** : Totaux récapitulatifs
- **B02** : Détail des salariés
- **B03** : Total intermédiaire
- **B04** : Salariés entrants
- **B05** : Total des entrants
- **B06** : Fin de fichier

### Format CIMR

Le fichier CIMR contient une ligne par salarié (ou 2 lignes pour TCNSS), chaque ligne comportant 190 caractères répartis en 22 champs :

1. Code (1 car) : 2=actif, 7=sorti
2. N° adhérent (6 car)
3. Code régime (1 car) : 1=Al Kamil, 2=Al Mounassib, 3=TCNSS
4. Matricule CIMR (10 car)
5. N° CNIE (8 car)
6. ... (18 champs au total)
22. Année (4 car)

## Structure des données

Le module utilise les données du module PaieDolibarr :

- Table `llx_paiedolibarr_paies` : données de paie mensuelles
- Table `llx_user` : informations des salariés
- Table `llx_user_extrafields` : champs personnalisés (matricules CNSS/CIMR, etc.)

### Champs extrafields requis

Pour un fonctionnement optimal, les champs suivants doivent être configurés dans les extrafields des utilisateurs :

- `matricule_cnss` : Matricule CNSS (10 chiffres)
- `matricule_cimr` : Matricule CIMR (10 caractères)
- `num_cnie` : Numéro CNIE (8 caractères)
- `regime_cimr` : Régime CIMR (AL_KAMIL, AL_MOUNASSIB, TCNSS)
- `categorie_cimr` : Catégorie CIMR (0-99)
- `taux_cimr` : Taux de cotisation CIMR (format: 0600 pour 6%)

## Dépannage

### Problème : "Aucune donnée à exporter"

- Vérifier que des fiches de paie existent pour la période sélectionnée
- Vérifier que les salariés ont des matricules CNSS/CIMR renseignés

### Problème : "Configuration incomplète"

- Vérifier que le numéro d'affilié CNSS ou d'adhérent CIMR est configuré
- Aller dans Configuration pour renseigner les paramètres manquants

### Problème : "Format de fichier invalide"

- Vérifier l'encodage du fichier (doit être ANSI/Windows-1252 pour CNSS)
- Vérifier que les longueurs de ligne sont correctes (260 pour CNSS, 190 pour CIMR)

### Problème : Caractères spéciaux mal affichés

- Le module utilise l'encodage Windows-1252 pour CNSS
- Les caractères accentués sont automatiquement convertis
- Utiliser la fonction `sanitizeForExport()` si nécessaire

## Support technique

Pour toute question ou problème :

- Consulter la page "À propos" du module dans Dolibarr
- Vérifier la documentation des cahiers des charges CNSS et CIMR
- Contacter Utopios : contact@utopios.ma

## Licence

**Licence payante - Tous droits réservés**

L'utilisation de ce module nécessite un accord commercial avec Utopios.

Pour obtenir une licence ou plus d'informations :
- Email : contact@utopios.ma
- Web : https://www.utopios.ma

## Crédits

- Développé par **Utopios**
- Compatible avec Dolibarr 22.0+
- Conforme aux cahiers des charges CNSS e-BDS v1 et CIMR Structure 190

## Changelog

### Version 1.0 (2024)

- Version initiale
- Export CNSS (e-BDS) avec gestion complète des 7 types d'enregistrements
- Export CIMR avec support des 3 régimes
- Import de préétabli CNSS
- Interface de configuration
- Prévisualisation des données avant export
- Historique des exports (table optionnelle)
- Bibliothèque de fonctions helper
- CSS et JavaScript pour interface utilisateur améliorée
