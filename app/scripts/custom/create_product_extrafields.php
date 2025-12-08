<?php
/**
 * Script CLI pour créer automatiquement les extrafields nécessaires pour l'import des articles.
 *
 * Usage :
 *   php create_product_extrafields.php
 *
 * Ce script crée tous les champs supplémentaires (extrafields) nécessaires pour l'import
 * des articles depuis le fichier Excel.
 *
 * Pré-requis :
 *   - Lancer ce script en contexte Dolibarr (CLI) avec PHP CLI.
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en CLI.\n");
    exit(1);
}

if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

require_once '/var/www/html/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/** @var DoliDB $db */
/** @var Conf $conf */

global $db, $conf, $langs;

$langs->load("admin");
$langs->load("products");

$usercli = new User($db);
$defaultUserId = !empty($conf->global->MAIN_IMPORT_DEFAULT_USER) ? (int) $conf->global->MAIN_IMPORT_DEFAULT_USER : 1;
if ($usercli->fetch($defaultUserId) <= 0) {
    fwrite(STDERR, "Impossible de récupérer l'utilisateur ID $defaultUserId\n");
    exit(1);
}

echo "=================================================================\n";
echo "Création des extrafields pour les produits\n";
echo "=================================================================\n\n";

$extrafields = new ExtraFields($db);

// Définition de tous les extrafields à créer
// Format: [code, label, type, size, required, position, params]
$fieldsToCreate = [
    // Quantités
    ['qte_reservation', 'Quantité de réservation', 'double', '', 0, 100, ''],
    ['qte1', 'Quantité 1', 'double', '', 0, 101, ''],
    ['qte_consignation', 'Quantité consignation', 'double', '', 0, 102, ''],
    ['qte_deposition', 'Quantité déposition', 'double', '', 0, 103, ''],
    ['qte_cmd_client', 'Qté commande client en instance', 'double', '', 0, 104, ''],
    ['qte_cmd_fournisseur', 'Qté commande fournisseur en instance', 'double', '', 0, 105, ''],
    ['qte_entree', 'Quantité entrée', 'double', '', 0, 106, ''],
    ['qte_sortie', 'Quantité sortie', 'double', '', 0, 107, ''],
    
    // Stock et valeurs
    ['stock_initial', 'Stock initial', 'double', '', 0, 200, ''],
    ['stock_comptable', 'Stock comptable', 'double', '', 0, 201, ''],
    ['stock_negatif', 'Acceptation stock négatif', 'boolean', '', 0, 202, ''],
    
    // Valeurs financières
    ['valeur_stock_initial', 'Valeur stock initial', 'price', '', 0, 300, ''],
    ['valeur_entree', 'Valeur entrée', 'price', '', 0, 301, ''],
    ['valeur_sortie', 'Valeur sortie', 'price', '', 0, 302, ''],
    ['valeur_stock', 'Valeur stock', 'price', '', 0, 303, ''],
    ['total_valeur_stock_initial', 'Total valeur stock initial', 'price', '', 0, 304, ''],
    ['total_valeur_entrees', 'Total valeur des entrées', 'price', '', 0, 305, ''],
    ['total_valeur_sorties', 'Total valeur des sorties', 'price', '', 0, 306, ''],
    ['total_valeur_stock', 'Total valeur stock', 'price', '', 0, 307, ''],
    ['pmp_comptable', 'PMP comptable', 'price', '', 0, 308, ''],
    ['prix_barre', 'Prix barré', 'price', '', 0, 309, ''],
    ['prix_net', 'Prix net', 'price', '', 0, 310, ''],
    
    // Unités et mesures
    ['unite_vente', 'Unité de vente', 'varchar', '50', 0, 400, ''],
    ['unite_achat', 'Unité d\'achat', 'varchar', '50', 0, 401, ''],
    
    // Caractéristiques produit
    ['article_cache', 'Article caché', 'boolean', '', 0, 500, ''],
    ['matiere_premiere', 'Produit matière première', 'boolean', '', 0, 501, ''],
    ['produit_compose', 'Produit composé', 'boolean', '', 0, 502, ''],
    ['recharge_restauration', 'Recharge restauration', 'boolean', '', 0, 503, ''],
    ['abonnement', 'Abonnement', 'boolean', '', 0, 504, ''],
    
    // Informations complémentaires
    ['emplacement', 'Emplacement', 'varchar', '100', 0, 600, ''],
    ['ntypeart', 'Type article', 'varchar', '50', 0, 601, ''],
    ['plafond_remise', 'Plafond remise', 'double', '', 0, 602, ''],
    ['type_le', 'Type (1=local, 2=étranger)', 'varchar', '10', 0, 603, ''],
    
    // Dates
    ['date_entree', 'Date d\'entrée', 'date', '', 0, 700, ''],
    ['date_sortie', 'Date de sortie', 'date', '', 0, 701, ''],
    ['date_image', 'Date image', 'date', '', 0, 702, ''],
    
    // Synchronisation PrestaShop
    ['article_importe', 'Article importé pour sync PrestaShop', 'boolean', '', 0, 800, ''],
    ['id_sync', 'ID synchronisation PrestaShop', 'varchar', '50', 0, 801, ''],
    
    // ===== IMPORTATION (A2S-54) =====
    ['statut_importation', 'Statut d\'importation', 'select', '', 0, 900, 'En attente|Autorisé|Refusé|En révision'],
    ['numero_dossier_import', 'Numéro de dossier d\'importation', 'varchar', '50', 0, 901, ''],
    ['date_fin_autorisation', 'Date fin d\'autorisation', 'date', '', 0, 905, ''],
    ['notes_conditions_import', 'Notes/Conditions d\'importation', 'text', '', 0, 903, ''],
];

$created = 0;
$skipped = 0;
$errors = 0;

echo "Création des extrafields...\n\n";

foreach ($fieldsToCreate as $field) {
    list($code, $label, $type, $size, $required, $position, $params) = $field;

    // Vérifier si le champ existe déjà
    $extrafields->fetch_name_optionals_label('product');

    if (isset($extrafields->attributes['product']['label'][$code])) {
        echo "  ⊙ $code : déjà existant, ignoré\n";
        $skipped++;
        continue;
    }

    // Créer le champ
    $arrayofparameters = '';
    if (!empty($params)) {
        if ($type === 'select') {
            // Pour les champs select, convertir la chaîne en tableau d'options
            $arrayofparameters = array('options' => array_combine(
                explode('|', $params),
                explode('|', $params)
            ));
        } elseif (is_array($params)) {
            $arrayofparameters = json_encode($params);
        }
    }

    $result = $extrafields->addExtraField(
        $code,
        $label,
        $type,
        $position,
        $size,
        'product',
        0,  // unique
        $required,
        '',  // default_value
        $arrayofparameters,
        0,  // alwayseditable
        '',  // perms
        '1',  // list (1 = visible dans les listes)
        '',  // help
        0,  // computed
        0,  // entity
        0,  // langfile
        '1',  // enabled (chaîne '1' pour toujours actif)
        0,  // totalizable
        1,  // printable (1 = visible dans les impressions)
        '',  // validate
        0   // css
    );

    if ($result > 0) {
        echo "  ✓ $code : créé avec succès\n";
        $created++;
    } else {
        echo "  ✗ $code : ERREUR - " . $extrafields->error . "\n";
        $errors++;
    }
}

echo "\n=================================================================\n";
echo "Résumé :\n";
echo "  - Champs créés : $created\n";
echo "  - Champs existants (ignorés) : $skipped\n";
echo "  - Erreurs : $errors\n";
echo "=================================================================\n";

if ($errors > 0) {
    echo "\n⚠ Certains champs n'ont pas pu être créés. Vérifiez les erreurs ci-dessus.\n";
    exit(1);
}

// Rechargement du cache des extrafields
if ($created > 0) {
    echo "\nRechargement du cache des extrafields...\n";
    $extrafields->fetch_name_optionals_label('product', true); // Force reload
    echo "✓ Cache rechargé\n";
}

echo "\n✓ Tous les extrafields ont été créés ou existent déjà.\n";
echo "\n📋 IMPORTANT : Pour que les champs soient visibles dans l'interface :\n";
echo "  1. Videz le cache du navigateur (Ctrl+Shift+R)\n";
echo "  2. Allez dans Configuration > Modules/Applications\n";
echo "  3. Cliquez sur l'icône 'Configurer' du module Produits/Services\n";
echo "  4. Onglet 'Attributs supplémentaires' pour vérifier la visibilité\n";
echo "\nVous pouvez maintenant lancer l'import des articles.\n";

exit(0);
