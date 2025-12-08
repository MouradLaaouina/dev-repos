<?php
#!/usr/bin/env php

/**
 * Script CLI pour créer des commandes clients fictives.
 *
 * Usage :
 *   php create_fictive_orders.php [--count=N]
 *
 * Ce script crée des commandes fictives pour tester le workflow complet:
 *   - Commandes de différents montants
 *   - Différents clients (B2B et B2C)
 *   - Différents statuts (brouillon, validée, en cours)
 *   - Respect des créances autorisées
 *
 * Pré-requis :
 *   - Les clients doivent être créés (voir create_fictive_clients.php)
 *   - Les produits doivent être créés (voir create_fictive_paramedical_products.php)
 */

if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit(1);
}

// Parse arguments
$orderCount = 5; // Nombre par défaut de commandes à créer
if ($argc > 1) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--count=') === 0) {
            $orderCount = (int)substr($arg, 8);
        }
    }
}

require_once '/var/www/html/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/** @var DoliDB $db */
/** @var Conf $conf */

global $db, $conf, $langs, $user;

$langs->load("orders");
$langs->load("products");

// Récupérer l'utilisateur admin
$user = new User($db);
$defaultUserId = !empty($conf->global->MAIN_IMPORT_DEFAULT_USER) ? (int) $conf->global->MAIN_IMPORT_DEFAULT_USER : 1;
if ($user->fetch($defaultUserId) <= 0) {
    fwrite(STDERR, "Impossible de récupérer l'utilisateur ID $defaultUserId\n");
    exit(1);
}

/* Utils */
function fatal($m) { fwrite(STDERR,"[ERROR] $m\n"); exit(1); }
function info($m) { echo "[INFO] $m\n"; }
function done($m) { echo "[OK]   $m\n"; }

/* Récupérer les clients */
function getClients($db) {
    $clients = [];
    $sql = "SELECT rowid, nom as name, code_client, client, outstanding_limit, remise_percent
            FROM " . MAIN_DB_PREFIX . "societe
            WHERE client IN (1, 3)
            ORDER BY rowid
            LIMIT 20";
    $resql = $db->query($sql);

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $clients[] = [
                'id' => (int)$obj->rowid,
                'name' => $obj->name,
                'code' => $obj->code_client,
                'outstanding_limit' => (float)$obj->outstanding_limit,
                'remise_percent' => (float)$obj->remise_percent,
            ];
        }
    }

    return $clients;
}

/* Récupérer les produits disponibles */
function getProducts($db) {
    $products = [];
    $sql = "SELECT p.rowid, p.ref, p.label, p.price, p.tva_tx
            FROM " . MAIN_DB_PREFIX . "product p
            WHERE p.tosell = 1
            ORDER BY p.rowid
            LIMIT 50";
    $resql = $db->query($sql);

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $products[] = [
                'id' => (int)$obj->rowid,
                'ref' => $obj->ref,
                'label' => $obj->label,
                'price' => (float)$obj->price,
                'tva_tx' => (float)$obj->tva_tx,
            ];
        }
    }

    return $products;
}

/* Scénarios de commandes */
$orderScenarios = [
    [
        'type' => 'small',
        'description' => 'Petite commande pharmacie',
        'products_count' => [2, 4],
        'quantities' => [5, 20],
        'status' => 1, // Validée
    ],
    [
        'type' => 'medium',
        'description' => 'Commande moyenne clinique',
        'products_count' => [4, 8],
        'quantities' => [10, 50],
        'status' => 1,
    ],
    [
        'type' => 'large',
        'description' => 'Grosse commande hôpital',
        'products_count' => [8, 15],
        'quantities' => [50, 200],
        'status' => 1,
    ],
    [
        'type' => 'draft',
        'description' => 'Commande en brouillon',
        'products_count' => [2, 5],
        'quantities' => [5, 30],
        'status' => 0, // Brouillon
    ],
    [
        'type' => 'urgent',
        'description' => 'Commande urgente',
        'products_count' => [3, 6],
        'quantities' => [20, 100],
        'status' => 1,
        'note' => '⚡ COMMANDE URGENTE - Livraison prioritaire',
    ],
];

/* Compteurs */
$totalCreated = 0;
$totalErrors = 0;
$totalAmount = 0;

echo "=================================================================\n";
echo "Création de commandes fictives\n";
echo "=================================================================\n\n";

// Récupérer les clients et produits
$clients = getClients($db);
$products = getProducts($db);

if (empty($clients)) {
    fatal("Aucun client trouvé. Veuillez d'abord exécuter create_fictive_clients.php");
}

if (empty($products)) {
    fatal("Aucun produit trouvé. Veuillez d'abord exécuter create_fictive_paramedical_products.php");
}

info("Clients disponibles: " . count($clients));
info("Produits disponibles: " . count($products));
info("Commandes à créer: " . $orderCount);
echo "\n";

for ($i = 0; $i < $orderCount; $i++) {
    // Choisir un scénario aléatoire
    $scenario = $orderScenarios[array_rand($orderScenarios)];

    // Choisir un client aléatoire
    $client = $clients[array_rand($clients)];

    $db->begin();

    // Créer la commande
    $order = new Commande($db);
    $order->socid = $client['id'];
    $order->date = time();
    $order->cond_reglement_id = 2; // 30 jours par défaut
    $order->mode_reglement_id = 2; // Virement

    // Note si définie dans le scénario
    if (isset($scenario['note'])) {
        $order->note_private = $scenario['note'];
    }

    // Créer la commande
    $orderId = $order->create($user);

    if ($orderId > 0) {
        // Ajouter des lignes de produits
        $productsCount = rand($scenario['products_count'][0], $scenario['products_count'][1]);
        $selectedProducts = array_rand($products, min($productsCount, count($products)));

        if (!is_array($selectedProducts)) {
            $selectedProducts = [$selectedProducts];
        }

        $orderTotal = 0;

        foreach ($selectedProducts as $productIndex) {
            $product = $products[$productIndex];
            $quantity = rand($scenario['quantities'][0], $scenario['quantities'][1]);

            // Calculer le prix avec remise client
            $price = $product['price'];
            if ($client['remise_percent'] > 0) {
                $price = $price * (1 - ($client['remise_percent'] / 100));
            }

            $result = $order->addline(
                $product['label'],
                $price,
                $quantity,
                $product['tva_tx'],
                0, // localtax1_tx
                0, // localtax2_tx
                $product['id'],
                0, // remise_percent (déjà appliquée sur le prix)
                0, // info_bits
                0, // fk_remise_except
                'HT',
                0, // pu_ttc
                0, // date_start
                0, // date_end
                0, // type
                -1, // rang
                0, // special_code
                0, // fk_parent_line
                0, // fk_fournprice
                0, // pa_ht
                '', // label
                [], // array_options
                0, // fk_unit
                '', // origin
                0, // origin_id
                0, // multicurrency_subprice
                0 // ref_ext
            );

            if ($result < 0) {
                fwrite(STDERR, "  ! Erreur ajout ligne produit: " . $order->error . "\n");
            } else {
                $orderTotal += $price * $quantity;
            }
        }

        // Valider la commande si le scénario le demande
        if ($scenario['status'] == 1) {
            $result = $order->valid($user);
            if ($result < 0) {
                fwrite(STDERR, "  ! Erreur validation commande: " . $order->error . "\n");
            }
        }

        $db->commit();

        $statusText = $scenario['status'] == 1 ? 'Validée' : 'Brouillon';
        done("Commande #" . ($i + 1) . " créée: {$scenario['description']} - Client: {$client['name']} - Montant: " . number_format($orderTotal, 2) . " DH HT - Statut: $statusText");

        $totalCreated++;
        $totalAmount += $orderTotal;
    } else {
        $db->rollback();
        $errorMsg = $order->error ?: 'Erreur inconnue';
        if (!empty($order->errors)) {
            $errorMsg .= ' | ' . implode(' ; ', $order->errors);
        }
        fwrite(STDERR, "[ERROR] Échec création commande pour client {$client['name']}: $errorMsg\n");
        $totalErrors++;
    }
}

echo "\n=================================================================\n";
echo "RÉSUMÉ:\n";
echo "  - Commandes créées: $totalCreated\n";
echo "  - Montant total: " . number_format($totalAmount, 2) . " DH HT\n";
echo "  - Montant moyen: " . number_format($totalCreated > 0 ? $totalAmount / $totalCreated : 0, 2) . " DH HT\n";
echo "  - Erreurs: $totalErrors\n";
echo "=================================================================\n";

if ($totalCreated > 0) {
    echo "\n✓ Les commandes fictives ont été créées avec succès.\n";
    echo "\nProchaines étapes:\n";
    echo "  1. Consulter les commandes dans Dolibarr (Menu Commercial > Commandes)\n";
    echo "  2. Expédier les commandes\n";
    echo "  3. Créer les factures associées\n";
    echo "  4. Enregistrer les paiements\n";
    echo "  5. Vérifier les mouvements de stock\n";
}

exit(0);
