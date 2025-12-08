# 🚀 Démarrage Rapide - Données Fictives A2S

## En 3 minutes chrono ⏱️

### Étape 1: Connexion au conteneur Docker
```bash
docker exec -it a2s-dolibarr-app bash
```

### Étape 2: Aller dans le répertoire des scripts
```bash
cd /var/www/html/scripts/custom
```

### Étape 3: Exécuter le script automatique
```bash
./setup_fictive_data.sh
```

**C'est tout ! 🎉**

Le script va créer automatiquement:
- ✅ 7 entrepôts
- ✅ 17 produits paramédicaux avec stocks
- ✅ 14 clients (B2B + B2C)
- ✅ 11 contacts professionnels

---

## Vérification

Pour vérifier que tout s'est bien passé:

```bash
php verify_fictive_data.php
```

---

## Créer des commandes de test (optionnel)

```bash
php create_fictive_orders.php --count=10
```

---

## Accès à Dolibarr

1. Ouvrez votre navigateur: `http://localhost:8080`
2. Connectez-vous avec vos identifiants
3. Vérifiez:
   - **Menu Produits/Services** → Voir les 17 produits
   - **Menu Tiers** → Voir les 14 clients
   - **Menu Stocks** → Voir les 7 entrepôts

---

## 🎯 Tests rapides à faire

### Test 1: Commande pharmacie (5 min)
```
Client: Pharmacie Al Amal (CL-PHAR-001)
Action: Créer une commande de masques et gants
Remise: 5% automatique
Délai: 30 jours
```

### Test 2: Vente particulier (2 min)
```
Client: El Fassi Amina (CL-PART-100)
Action: Vendre des produits dermocosmétiques
Paiement: Immédiat
```

### Test 3: Consultation stock (1 min)
```
Menu: Stocks → Mouvements de stock
Vérifier: Les stocks dans différents entrepôts
```

---

## 📚 Documentation complète

- **Guide utilisateur**: `GUIDE_DONNEES_FICTIVES.md`
- **Documentation technique**: `README_FICTIVE_DATA.md`

---

## ⚠️ En cas de problème

### Le script ne s'exécute pas
```bash
chmod +x setup_fictive_data.sh
./setup_fictive_data.sh
```

### Erreur de connexion à la base
```bash
# Vérifier que Dolibarr est démarré
docker ps | grep dolibarr
```

### Données déjà créées
Les scripts sont intelligents ! Ils ignorent les données existantes. Vous pouvez les réexécuter sans risque.

---

## 🆘 Support

Si vous avez des problèmes:
1. Vérifiez les logs: `tail -f /var/www/html/documents/dolibarr.log`
2. Relancez le script de vérification: `php verify_fictive_data.php`
3. Consultez la documentation complète

---

**Version**: 1.0
**Date**: Janvier 2025
**Temps d'installation**: ~3 minutes
**Prêt à tester A2S ! 🚀**
