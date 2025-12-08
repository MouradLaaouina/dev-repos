# Guide du Centre d'Appel

## Introduction

Le module Centre d'Appel est conçu pour les agents de l'équipe d'appel (code_agence: 000002) afin de gérer efficacement les interactions téléphoniques avec les clients. Ce guide explique comment utiliser les fonctionnalités du centre d'appel intégrées au CRM.

## Accès au Centre d'Appel

L'accès au Centre d'Appel est limité aux utilisateurs suivants:
- Administrateurs (rôle: `admin`)
- Agents avec code d'agence `000002` (équipe Centre d'appel)

Pour accéder au Centre d'Appel:
1. Connectez-vous avec vos identifiants
2. Cliquez sur "Centre d'appel" dans le menu latéral

## Fonctionnalités principales

### 1. Tableau de bord des leads

Le tableau de bord affiche tous les leads assignés à l'agent avec:
- Date d'achat
- Nom du client
- Numéro de téléphone
- Agent commercial d'origine
- Produit acheté
- Dernier statut d'appel
- Actions disponibles

### 2. Appels directs via MicroSIP

Pour appeler un client:
1. Cliquez sur le bouton téléphone <Phone /> à côté du lead
2. MicroSIP s'ouvrira automatiquement avec le numéro pré-rempli
3. Effectuez votre appel

**Configuration requise**: MicroSIP doit être installé et configuré sur votre ordinateur.

### 3. Messages WhatsApp

Pour envoyer un message WhatsApp:
1. Cliquez sur le bouton WhatsApp <MessageCircle /> à côté du lead
2. WhatsApp Web s'ouvrira avec le numéro pré-rempli
3. Rédigez et envoyez votre message

### 4. Formulaire de suivi d'appel

Après chaque appel, remplissez le formulaire de suivi:
1. Cliquez sur le bouton formulaire <FileText /> à côté du lead
2. Remplissez les champs obligatoires:
   - **Statut de l'appel**: À rappeler, Pas intéressé(e), Commande, Ne réponds jamais, Faux numéro, Intéressé(e)
   - **Niveau de satisfaction**: Notez de 1 à 5 étoiles
   - **Intéressé(e) par le produit**: Oui, Non, Peut-être
   - **Date d'appel**: Date et heure de l'appel effectué
   - **Date du prochain appel**: Obligatoire si le statut est "À rappeler"
   - **Heure du prochain appel**: Obligatoire si le statut est "À rappeler"
   - **Notes**: Informations supplémentaires (optionnel)

### 5. Filtrage des leads

Vous pouvez filtrer les leads par:
- Recherche textuelle (nom, téléphone, agent)
- Statut d'appel

## Statistiques

Le tableau de bord affiche des statistiques clés:
- Nombre total de leads à traiter
- Nombre total d'appels effectués
- Nombre d'appels effectués aujourd'hui
- Satisfaction moyenne des clients
- Nombre de commandes confirmées

## Bonnes pratiques

1. **Toujours remplir le formulaire de suivi** après chaque appel
2. **Planifier les rappels** à des heures appropriées
3. **Noter précisément le niveau de satisfaction** du client
4. **Ajouter des notes détaillées** pour faciliter le suivi
5. **Vérifier régulièrement** les leads "À rappeler"

## Dépannage

### Problèmes courants

1. **MicroSIP ne s'ouvre pas**
   - Vérifiez que MicroSIP est installé et configuré correctement
   - Assurez-vous que le protocole `sip:` est associé à MicroSIP

2. **WhatsApp ne s'ouvre pas**
   - Vérifiez que vous êtes connecté à WhatsApp Web dans votre navigateur
   - Assurez-vous que le numéro est au format international

3. **Formulaire de suivi non sauvegardé**
   - Vérifiez que tous les champs obligatoires sont remplis
   - Vérifiez votre connexion internet

## Support

Pour toute question ou problème technique, contactez l'administrateur système.