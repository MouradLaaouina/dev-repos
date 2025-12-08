<?php 
#!/usr/bin/env php

/**
 * Script CLI pour créer des entrepôts fictifs dans Dolibarr.
 *
 * Usage :
 *   php create_warehouses.php
 *
 * Ce script crée plusieurs entrepôts fictifs pour gérer les stocks de produits paramédicaux.
 */

if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit(1);
}

require_once '/var/www/html/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/** @var DoliDB $db */
/** @var Conf $conf */

global $db, $conf, $langs, $user;

$langs->load("stocks");

// Récupérer l'utilisateur admin
$user = new User($db);
$defaultUserId = !empty($conf->global->MAIN_IMPORT_DEFAULT_USER) ? (int) $conf->global->MAIN_IMPORT_DEFAULT_USER : 1;
if ($user->fetch($defaultUserId) <= 0) {
    fwrite(STDERR, "Impossible de récupérer l'utilisateur ID $defaultUserId\n");
    exit(1);
}

/* Définition des entrepôts fictifs */
$warehouses = [
    [
        'label' => 'Entrepôt Central Casablanca',
        'description' => 'Entrepôt principal pour le stockage des produits paramédicaux - Zone industrielle Aïn Sebaâ',
        'statut' => 1,
        'address' => 'Zone Industrielle Aïn Sebaâ',
        'zip' => '20250',
        'town' => 'Casablanca',
        'country_id' => 12, // Maroc
        'phone' => '+212 522 123 456',
    ],
    [
        'label' => 'Entrepôt Nord Tanger',
        'description' => 'Entrepôt secondaire pour le stockage et la distribution dans le nord du pays',
        'statut' => 1,
        'address' => 'Zone Franche de Tanger',
        'zip' => '90000',
        'town' => 'Tanger',
        'country_id' => 12,
        'phone' => '+212 539 123 456',
    ],
    [
        'label' => 'Entrepôt Sud Marrakech',
        'description' => 'Entrepôt pour la région sud - Produits dermocosmétiques',
        'statut' => 1,
        'address' => 'Quartier Industriel Sidi Ghanem',
        'zip' => '40000',
        'town' => 'Marrakech',
        'country_id' => 12,
        'phone' => '+212 524 123 456',
    ],
    [
        'label' => 'Entrepôt Est Oujda',
        'description' => 'Entrepôt pour la région orientale - Distribution transfrontalière',
        'statut' => 1,
        'address' => 'Boulevard Mohammed V',
        'zip' => '60000',
        'town' => 'Oujda',
        'country_id' => 12,
        'phone' => '+212 536 123 456',
    ],
    [
        'label' => 'Entrepôt Rabat-Salé',
        'description' => 'Entrepôt régional pour les commandes gouvernementales et institutionnelles',
        'statut' => 1,
        'address' => 'Technopolis Rabat-Shore',
        'zip' => '11000',
        'town' => 'Rabat',
        'country_id' => 12,
        'phone' => '+212 537 123 456',
    ],
    [
        'label' => 'Entrepôt Transit Import',
        'description' => 'Entrepôt de transit pour les produits importés - Contrôle qualité',
        'statut' => 1,
        'address' => 'Port de Casablanca',
        'zip' => '20000',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 987 654',
    ],
    [
        'label' => 'Dépôt Pharmacie Centrale',
        'description' => 'Petit dépôt pour les produits à rotation rapide',
        'statut' => 1,
        'address' => 'Avenue Hassan II',
        'zip' => '20100',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 333 444',
    ],
];

/* Utils */
function fatal($m) { fwrite(STDERR,"[ERROR] $m\n"); exit(1); }
function info($m) { echo "[INFO] $m\n"; }
function done($m) { echo "[OK]   $m\n"; }

/* Compteurs */
$totalCreated = 0;
$totalSkipped = 0;
$totalErrors = 0;

echo "=================================================================\n";
echo "Création des entrepôts fictifs\n";
echo "=================================================================\n\n";

$db->begin();

foreach ($warehouses as $warehouseData) {
    $label = $warehouseData['label'];

    // Vérifier si l'entrepôt existe déjà (le champ s'appelle 'ref' pour les entrepôts)
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "entrepot WHERE ref = '" . $db->escape($label) . "' LIMIT 1";
    $resql = $db->query($sql);

    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            info("Entrepôt '$label' existe déjà (ID: $obj->rowid), ignoré");
            $totalSkipped++;
            continue;
        }
    }

    // Créer l'entrepôt
    $warehouse = new Entrepot($db);
    $warehouse->ref = $warehouseData['label'];  // Le champ s'appelle 'ref' dans Dolibarr
    $warehouse->label = $warehouseData['label']; // On met aussi label pour compatibilité
    $warehouse->description = $warehouseData['description'];
    $warehouse->statut = $warehouseData['statut'];
    $warehouse->address = $warehouseData['address'];
    $warehouse->zip = $warehouseData['zip'];
    $warehouse->town = $warehouseData['town'];
    $warehouse->country_id = $warehouseData['country_id'];
    $warehouse->phone = $warehouseData['phone'];

    $result = $warehouse->create($user);

    if ($result > 0) {
        done("Entrepôt créé: '$label' (ID: $result)");
        $totalCreated++;
    } else {
        $errorMsg = $warehouse->error ?: 'Erreur inconnue';
        if (!empty($warehouse->errors)) {
            $errorMsg .= ' | ' . implode(' ; ', $warehouse->errors);
        }
        fwrite(STDERR, "[ERROR] Échec création entrepôt '$label': $errorMsg\n");
        $totalErrors++;
    }
}

if ($totalErrors > 0) {
    $db->rollback();
    fatal("Des erreurs sont survenues. Transaction annulée.");
} else {
    $db->commit();
    done("Transaction validée.");
}

echo "\n=================================================================\n";
echo "RÉSUMÉ:\n";
echo "  - Entrepôts créés: $totalCreated\n";
echo "  - Entrepôts existants (ignorés): $totalSkipped\n";
echo "  - Erreurs: $totalErrors\n";
echo "=================================================================\n";

if ($totalCreated > 0) {
    echo "\n✓ Les entrepôts ont été créés avec succès.\n";
    echo "Vous pouvez maintenant créer des produits et associer des stocks à ces entrepôts.\n";
}

exit(0);
