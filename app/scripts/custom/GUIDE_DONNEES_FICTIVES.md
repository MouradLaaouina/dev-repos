# Guide Complet - Données Fictives pour A2S Dolibarr 22

## 📚 Vue d'ensemble

Ce guide explique comment utiliser les scripts de création de données fictives pour votre environnement de test A2S. Les scripts créent un environnement complet avec des produits paramédicaux, des entrepôts et des clients réalistes.

## 🎯 Objectif

Créer rapidement un environnement de test réaliste comprenant:
- **Entrepôts** multi-sites (Casablanca, Rabat, Tanger, Marrakech, Oujda)
- **Produits paramédicaux** avec stocks (matériel médical, consommables, dermocosmétique)
- **Clients B2B et B2C** avec différents profils (pharmacies, cliniques, hôpitaux, particuliers)
- **Commandes** fictives pour tester le workflow complet

## 📁 Scripts Disponibles

### Scripts Principaux

| Script | Description | Pré-requis |
|--------|-------------|------------|
| `create_warehouses.php` | Crée 7 entrepôts répartis au Maroc | Aucun |
| `create_product_extrafields.php` | Crée les champs personnalisés produits | Aucun |
| `create_fictive_paramedical_products.php` | Crée 17 produits avec stocks | Entrepôts |
| `create_fictive_clients.php` | Crée 14 clients (B2B + B2C) | Aucun |
| `create_fictive_orders.php` | Crée des commandes de test | Clients + Produits |

### Scripts Utilitaires

| Script | Description |
|--------|-------------|
| `setup_fictive_data.sh` | Script automatique pour tout créer en une fois |
| `README_FICTIVE_DATA.md` | Documentation technique détaillée |
| `GUIDE_DONNEES_FICTIVES.md` | Ce guide utilisateur |

## 🚀 Installation Rapide

### Méthode 1: Script automatique (Recommandé)

```bash
# Se connecter au conteneur Docker
docker exec -it a2s-dolibarr-app bash

# Aller dans le répertoire des scripts
cd /var/www/html/scripts/custom

# Exécuter le script d'installation
./setup_fictive_data.sh
```

Le script va automatiquement:
1. Créer les 7 entrepôts
2. Créer les extrafields produits
3. Créer les 17 produits paramédicaux
4. Créer les 14 clients
5. Afficher un résumé complet

### Méthode 2: Exécution manuelle

Si vous préférez contrôler chaque étape:

```bash
cd /var/www/html/scripts/custom

# 1. Créer les entrepôts
php create_warehouses.php

# 2. Créer les extrafields (si pas déjà fait)
php create_product_extrafields.php

# 3. Créer les produits avec stocks
php create_fictive_paramedical_products.php

# 4. Créer les clients
php create_fictive_clients.php

# 5. (Optionnel) Créer des commandes
php create_fictive_orders.php --count=10
```

## 📊 Données Créées en Détail

### 🏢 Entrepôts (7 sites)

| Nom | Ville | Utilisation |
|-----|-------|-------------|
| Entrepôt Central Casablanca | Casablanca | Principal - Tous produits |
| Entrepôt Nord Tanger | Tanger | Distribution nord |
| Entrepôt Sud Marrakech | Marrakech | Produits dermocosmétiques |
| Entrepôt Est Oujda | Oujda | Distribution est |
| Entrepôt Rabat-Salé | Rabat | Commandes institutionnelles |
| Entrepôt Transit Import | Casablanca | Produits importés |
| Dépôt Pharmacie Centrale | Casablanca | Rotation rapide |

### 🏥 Produits Paramédicaux (17 produits)

#### Matériel Médical (4 produits)
- **MED-TENSI-001**: Tensiomètre électronique (450 DH)
- **MED-THERM-002**: Thermomètre infrarouge (280 DH)
- **MED-STETHO-003**: Stéthoscope double pavillon (650 DH)
- **MED-GLUCOM-004**: Glucomètre avec kit (320 DH)

#### Consommables Médicaux (5 produits)
- **CONS-MASQ-010**: Masques chirurgicaux boîte 50 (35 DH)
- **CONS-GANT-011**: Gants nitrile boîte 100 (45 DH)
- **CONS-SERING-012**: Seringues 5ml boîte 100 (28 DH)
- **CONS-COMPR-013**: Compresses stériles sachet 10 (12 DH)
- **CONS-BANDE-014**: Bande élastique 10cm (18 DH)

#### Dermocosmétique (3 produits)
- **DERM-HYDR-020**: Crème hydratante SPF30 (145 DH)
- **DERM-SERU-021**: Sérum anti-âge vitamine C (320 DH)
- **DERM-NETT-022**: Gel nettoyant purifiant (95 DH)

#### Dispositifs Médicaux (3 produits)
- **DM-MULETTE-030**: Béquilles ajustables (380 DH)
- **DM-FAUTEUIL-031**: Fauteuil roulant pliant (2 800 DH)
- **DM-ATTELLE-032**: Attelle poignet (220 DH)

#### Hygiène (2 produits)
- **HYG-GELHYDRO-040**: Gel hydroalcoolique 500ml (38 DH)
- **HYG-SAVON-041**: Savon antiseptique 1L (52 DH)

### 👥 Clients (14 clients)

#### Pharmacies (3 clients B2B)
| Code | Nom | Remise | Créance Max | Délai |
|------|-----|--------|-------------|-------|
| CL-PHAR-001 | Pharmacie Al Amal | 5% | 50 000 DH | 30j |
| CL-PHAR-002 | Pharmacie Centrale Rabat | 8% | 100 000 DH | 45j |
| CL-PHAR-003 | Pharmacie du Nord | 3.5% | 30 000 DH | 30j |

#### Cliniques (2 clients B2B)
| Code | Nom | Remise | Créance Max | Délai |
|------|-----|--------|-------------|-------|
| CL-CLIN-010 | Clinique Al Amal Casa | 12% | 300 000 DH | 60j |
| CL-CLIN-011 | Centre Médical Agdal | 10% | 150 000 DH | 45j |

#### Hôpitaux (2 clients B2B - Gros volumes)
| Code | Nom | Remise | Créance Max | Délai |
|------|-----|--------|-------------|-------|
| CL-HOP-020 | CHU Ibn Rochd Casa | 18% | 1 000 000 DH | 90j |
| CL-HOP-021 | Hôpital Cheikh Khalifa | 15% | 800 000 DH | 60j |

#### Distributeurs (1 client B2B)
| Code | Nom | Remise | Créance Max | Délai |
|------|-----|--------|-------------|-------|
| CL-DIST-030 | Distri Pharma Maroc | 20% | 500 000 DH | 45j |

#### Petites Entreprises (2 clients B2B)
| Code | Nom | Remise | Créance Max | Délai |
|------|-----|--------|-------------|-------|
| CL-CAB-200 | Cabinet Dentaire Dr. Benjelloun | 5% | 20 000 DH | 30j |
| CL-LAB-201 | Laboratoire Bio Santé | 7% | 40 000 DH | 30j |

#### Particuliers (3 clients B2C)
| Code | Nom | Remise | Paiement |
|------|-----|--------|----------|
| CL-PART-100 | El Fassi Amina | 0% | Immédiat |
| CL-PART-101 | Benkirane Mohammed | 0% | Carte |
| CL-PART-102 | Tazi Samira | 2% | Carte |

#### Client Test (1 client B2B - Impayés)
| Code | Nom | Remise | Créance Max | Note |
|------|-----|--------|-------------|------|
| CL-PHAR-999 | Pharmacie du Sud | 3% | 10 000 DH | Créance limitée |

## 🧪 Scénarios de Test

### Test 1: Commande Pharmacie Standard
**Objectif**: Tester une commande B2B classique avec remise

```
Client: Pharmacie Al Amal (CL-PHAR-001)
Produits suggérés:
  - 50x Masques chirurgicaux (CONS-MASQ-010)
  - 20x Gants nitrile (CONS-GANT-011)
  - 10x Thermomètres (MED-THERM-002)

Montant estimé: 3 000 DH HT
Remise appliquée: 5% = 150 DH
Net à payer: 2 850 DH HT
Délai: 30 jours
```

**Vérifications**:
- ✓ Remise 5% appliquée automatiquement
- ✓ Créance disponible suffisante (50 000 DH)
- ✓ Condition de paiement 30 jours
- ✓ Stocks décomptés des entrepôts

### Test 2: Grosse Commande Hôpital
**Objectif**: Tester gros volume avec forte remise

```
Client: CHU Ibn Rochd (CL-HOP-020)
Produits suggérés:
  - 500x Masques (CONS-MASQ-010)
  - 300x Gants (CONS-GANT-011)
  - 100x Seringues (CONS-SERING-012)
  - 50x Thermomètres (MED-THERM-002)

Montant estimé: 50 000 DH HT
Remise appliquée: 18% = 9 000 DH
Net à payer: 41 000 DH HT
Délai: 90 jours
```

**Vérifications**:
- ✓ Remise 18% appliquée (gros client)
- ✓ Créance 1M DH largement suffisante
- ✓ Délai 90 jours (secteur public)
- ✓ Alerte si stocks insuffisants

### Test 3: Vente Particulier
**Objectif**: Tester vente B2C au comptant

```
Client: El Fassi Amina (CL-PART-100)
Produits suggérés:
  - 2x Crème hydratante (DERM-HYDR-020)
  - 1x Sérum anti-âge (DERM-SERU-021)
  - 1x Gel nettoyant (DERM-NETT-022)

Montant: 705 DH HT
Paiement: Immédiat (espèces ou carte)
```

**Vérifications**:
- ✓ Aucune remise
- ✓ Paiement immédiat obligatoire
- ✓ Pas de créance autorisée
- ✓ Facturation instantanée

### Test 4: Client avec Créance Limitée
**Objectif**: Tester blocage si dépassement créance

```
Client: Pharmacie du Sud (CL-PHAR-999)
Créance autorisée: 10 000 DH
Créance actuelle: 0 DH

Commande 1: 8 000 DH → ✓ OK
Commande 2: 5 000 DH → ✗ BLOQUÉ (total 13 000 > 10 000)
```

**Vérifications**:
- ✓ Système bloque si dépassement
- ✓ Message d'alerte clair
- ✓ Historique des créances visible

### Test 5: Multi-Entrepôts
**Objectif**: Tester gestion de stock sur plusieurs sites

```
Produit: Masques (CONS-MASQ-010)
Stocks:
  - Central Casa: 500 boîtes
  - Nord Tanger: 200 boîtes
  - Sud Marrakech: 150 boîtes

Commande: 800 boîtes
Actions:
  1. Préparation depuis Central (500)
  2. Préparation depuis Nord (200)
  3. Préparation depuis Sud (100)
  OU
  1. Transfert Nord → Central
  2. Préparation depuis Central uniquement
```

**Vérifications**:
- ✓ Stocks temps réel par entrepôt
- ✓ Possibilité de transferts
- ✓ Traçabilité des mouvements
- ✓ Alertes si rupture

## 📈 Créer des Commandes Fictives

Pour générer des commandes de test:

```bash
# Créer 5 commandes (par défaut)
php create_fictive_orders.php

# Créer 20 commandes
php create_fictive_orders.php --count=20
```

Les commandes créées incluent:
- Petites commandes pharmacie (2-4 produits)
- Commandes moyennes clinique (4-8 produits)
- Grosses commandes hôpital (8-15 produits)
- Commandes en brouillon
- Commandes urgentes

## 🔄 Workflow Complet à Tester

### 1. Réception Commande
```
Commande → Validation → Préparation
```

### 2. Expédition
```
Préparation → Bon de livraison → Décompte stock
```

### 3. Facturation
```
Livraison → Facture → Envoi client
```

### 4. Encaissement
```
Facture → Paiement → Lettrage → Mise à jour créance
```

### 5. Relances
```
Impayé → Relance automatique → Blocage si dépassement
```

## 🛠️ Options Avancées

### Recréer uniquement les produits

```bash
./setup_fictive_data.sh --skip-warehouses --skip-extrafields
```

### Nettoyer et recommencer

Pour supprimer toutes les données:
1. Via Dolibarr: Supprimer manuellement produits/clients
2. Via SQL (⚠️ DANGER):
```sql
-- NE PAS EXÉCUTER EN PRODUCTION !
DELETE FROM llx_product WHERE ref LIKE 'MED-%' OR ref LIKE 'CONS-%' OR ref LIKE 'DERM-%';
DELETE FROM llx_societe WHERE code_client LIKE 'CL-%';
DELETE FROM llx_entrepot WHERE label LIKE 'Entrepôt%';
```

## ❓ FAQ

### Q: Les scripts sont-ils réexécutables ?
**R**: Oui ! Les scripts vérifient si les données existent déjà et les ignorent.

### Q: Puis-je personnaliser les données ?
**R**: Oui ! Modifiez les tableaux PHP dans chaque script.

### Q: Les prix sont en quelle devise ?
**R**: Dirham marocain (MAD/DH).

### Q: Comment ajouter mes propres produits ?
**R**: Ajoutez-les dans le tableau `$products` de `create_fictive_paramedical_products.php`.

### Q: Les stocks sont-ils réalistes ?
**R**: Oui, les quantités sont adaptées à chaque type d'entrepôt.

### Q: Puis-je utiliser ces scripts en production ?
**R**: ⚠️ NON ! Ce sont des données fictives pour tests uniquement.

## 📞 Support

En cas de problème:

1. **Vérifier les logs**:
```bash
tail -f /var/www/html/documents/dolibarr.log
```

2. **Vérifier PHP**:
```bash
php -v
php -m | grep mysql
```

3. **Vérifier la base de données**:
```bash
mysql -u root -p dolibarr -e "SHOW TABLES;"
```

## 🎓 Ressources

- [Documentation Dolibarr](https://wiki.dolibarr.org)
- [API Dolibarr](https://www.dolibarr.org/api-explorer/)
- [Forum Dolibarr](https://www.dolibarr.fr/forum/)

## 📝 Changelog

**Version 1.0** (Janvier 2025)
- ✓ Création entrepôts
- ✓ Création produits paramédicaux
- ✓ Création clients B2B/B2C
- ✓ Création commandes fictives
- ✓ Script automatique
- ✓ Documentation complète

---

**Bon test avec A2S ! 🚀**

Pour toute question, consultez le fichier README_FICTIVE_DATA.md ou contactez l'équipe de développement.
