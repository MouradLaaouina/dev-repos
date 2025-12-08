# Scripts de Création de Données Fictives pour Dolibarr 22

Ce document décrit les scripts permettant de créer des données fictives pour tester le système A2S avec des produits paramédicaux, des entrepôts et des clients.

## 📋 Scripts Disponibles

### 1. `create_warehouses.php`
Crée des entrepôts fictifs pour la gestion des stocks.

**Entrepôts créés:**
- Entrepôt Central Casablanca (principal)
- Entrepôt Nord Tanger
- Entrepôt Sud Marrakech
- Entrepôt Est Oujda
- Entrepôt Rabat-Salé
- Entrepôt Transit Import
- Dépôt Pharmacie Centrale

### 2. `create_fictive_paramedical_products.php`
Crée des produits paramédicaux fictifs avec stocks répartis dans les entrepôts.

**Catégories de produits:**
- **Matériel Médical**: Tensiomètres, thermomètres, stéthoscopes, glucomètres
- **Consommables Médicaux**: Masques, gants, seringues, compresses, bandes
- **Dermocosmétique**: Crèmes hydratantes, sérums, gels nettoyants
- **Dispositifs Médicaux**: Béquilles, fauteuils roulants, attelles
- **Hygiène**: Gel hydroalcoolique, savon antiseptique

**Caractéristiques:**
- Références uniques (ex: MED-TENSI-001)
- Prix HT et TTC avec TVA à 20%
- Stocks répartis dans plusieurs entrepôts
- Catégories et sous-catégories automatiques
- Poids pour calcul de livraison

### 3. `create_fictive_clients.php`
Crée des clients fictifs avec différents profils et niveaux de configuration.

**Types de clients B2B:**

| Type | Nombre | Remise | Délai paiement | Créance max |
|------|--------|--------|----------------|-------------|
| **Pharmacies** | 3 | 3-8% | 30-45 jours | 30k-100k DH |
| **Cliniques** | 2 | 10-12% | 45-60 jours | 150k-300k DH |
| **Hôpitaux** | 2 | 15-18% | 60-90 jours | 800k-1M DH |
| **Distributeurs** | 1 | 20% | 45 jours | 500k DH |
| **Cabinets/Labos** | 2 | 5-7% | 30 jours | 20k-40k DH |

**Types de clients B2C:**
- Particuliers (3 clients)
- Paiement immédiat
- Pas de créance autorisée
- Remise 0-2%

**Modes de paiement:**
- Virement bancaire
- Chèque
- Espèces
- Carte bancaire

## 🚀 Utilisation

### Ordre d'exécution recommandé:

```bash
# 1. Créer les entrepôts
cd /var/www/html/scripts/custom
php create_warehouses.php

# 2. Créer les extrafields produits (si pas déjà fait)
php create_product_extrafields.php

# 3. Créer les produits paramédicaux avec stocks
php create_fictive_paramedical_products.php

# 4. Créer les clients fictifs
php create_fictive_clients.php
```

### Exécution depuis Docker:

```bash
# Depuis le répertoire du projet
docker exec -it a2s-dolibarr-app bash

# Puis dans le conteneur
cd /var/www/html/scripts/custom
php create_warehouses.php
php create_fictive_paramedical_products.php
php create_fictive_clients.php
```

### Réinitialisation:

Si vous souhaitez recréer les données:
- Les scripts vérifient l'existence avant de créer
- Pour supprimer et recréer: supprimez manuellement via l'interface Dolibarr ou directement en base

## 📊 Données Créées

### Statistiques:

- **7 entrepôts** répartis dans tout le Maroc
- **17 produits paramédicaux** avec stocks variés
- **14 clients** (11 B2B + 3 B2C)
- **11 contacts** associés aux clients B2B
- **Mouvements de stock** automatiques pour chaque produit/entrepôt

### Valeur totale du stock:
Environ **500 000 DH** de stock réparti sur les entrepôts

### Créances autorisées totales:
Environ **3 700 000 DH** pour l'ensemble des clients B2B

## 🎯 Cas d'usage pour tests

### Test 1: Commande pharmacie
- Client: Pharmacie Al Amal (CL-PHAR-001)
- Créance: 50 000 DH
- Remise: 5%
- Délai: 30 jours
- Commander des masques, gants, thermomètres

### Test 2: Commande hôpital (gros volume)
- Client: CHU Ibn Rochd (CL-HOP-020)
- Créance: 1 000 000 DH
- Remise: 18%
- Délai: 90 jours
- Commander matériel médical en grande quantité

### Test 3: Vente particulier
- Client: El Fassi Amina (CL-PART-100)
- Paiement immédiat
- Pas de créance
- Produits dermocosmétiques

### Test 4: Client en difficulté
- Client: Pharmacie du Sud (CL-PHAR-999)
- Créance limitée: 10 000 DH
- Tester les blocages de commande si dépassement

### Test 5: Gestion multi-entrepôts
- Vérifier les stocks dans différents entrepôts
- Tester les transferts entre entrepôts
- Simuler des ruptures de stock

## 🔧 Configuration requise

- Dolibarr 22 ou supérieur
- Module Stock activé
- Module Tiers (Sociétés) activé
- Module Produits/Services activé
- Droits d'administration

## 📝 Notes

1. **Données fictives**: Toutes les données sont fictives (noms, adresses, emails)
2. **TVA**: Tous les produits ont une TVA de 20% (Maroc)
3. **Devise**: Dirham marocain (MAD/DH)
4. **Codes clients**: Préfixés par type (CL-PHAR, CL-CLIN, CL-HOP, etc.)
5. **Références produits**: Préfixées par catégorie (MED-, CONS-, DERM-, DM-, HYG-)

## 🐛 Dépannage

### Erreur: "Impossible de récupérer l'utilisateur"
→ Vérifiez que l'utilisateur admin ID 1 existe

### Erreur: "Aucun entrepôt trouvé"
→ Exécutez d'abord `create_warehouses.php`

### Erreur: "Champ extrafield introuvable"
→ Exécutez d'abord `create_product_extrafields.php`

### Produits créés sans stock
→ Vérifiez que le module Stock est activé dans Dolibarr

## 📞 Support

Pour toute question ou problème:
1. Vérifiez les logs: `/var/www/html/documents/dolibarr.log`
2. Vérifiez les erreurs PHP dans les logs du conteneur
3. Consultez la documentation Dolibarr: https://wiki.dolibarr.org

## ⚡ Prochaines étapes

Après avoir créé les données fictives:
1. Créer des propositions commerciales
2. Générer des commandes clients
3. Créer des factures
4. Tester les mouvements de stock
5. Vérifier les créances et encours clients
6. Tester les rappels de paiement

---

**Version**: 1.0
**Date**: 2025-01
**Compatibilité**: Dolibarr 22+
