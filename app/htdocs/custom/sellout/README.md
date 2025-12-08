Sellout module
==============

Log ventes sellout (ventes terrain hors flux Dolibarr) dans une table dédiée et expose des endpoints REST sans impacter les commandes/factures ni les statistiques natives.

Activation
----------
- Activer le module `Sellout` dans Dolibarr (`Configuration > Modules/Applications`).
- Attribuer les droits `sellout -> read` et `sellout -> write` à l'utilisateur ou au token API concerné.
- La table `llx_sellout_sale` est créée automatiquement à l'activation.

Endpoints API (via `/api/index.php`)
------------------------------------
- `POST sellout/sales` : enregistre une vente.
  - Requis : `thirdparty_id`, `product_id`, `qty` (>0).
  - Optionnel : `unit_price`, `currency_code` (par défaut monnaie Dolibarr), `sale_date` (timestamp ou ISO, défaut = maintenant), `salesrep_id` (défaut = utilisateur API), `source` (<=50c), `note`, `location_label`, `location_latitude` (-90..90), `location_longitude` (-180..180).
  - Réponse : enregistrement créé (timestamps + versions ISO).
- `GET sellout/sales/{id}` : récupère un enregistrement.
- `GET sellout/sales` : liste avec filtres `socid`, `product_id`, `salesrep_id`, `date_from`, `date_to`, `page`, `limit` (max 500).

Sécurité/portée
---------------
- Contrôle des accès sur les tiers via `_checkAccessToResource`.
- Si l'utilisateur API ne peut pas voir tous les clients, le filtre `salesrep_id` bascule par défaut sur l'utilisateur courant.
- Les données restent isolées dans `llx_sellout_sale` (aucun mouvement de stock, devis, commandes ou factures).
