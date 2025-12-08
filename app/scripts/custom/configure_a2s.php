<?php
#!/usr/bin/env php
/**
 * Script de configuration de la société Alliance Synergie Santé (A2S) dans Dolibarr
 */

if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit(1);
}

require_once '/var/www/html/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

echo "=== Configuration de la société Alliance Synergie Santé (A2S) ===\n";

// Récupérer le rowid du pays Maroc
echo "\n=== Détection du pays Maroc ===\n";
$sql_country = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_country WHERE code IN ('MA', 'MOR') OR label LIKE '%Maroc%' OR label LIKE '%Morocco%' LIMIT 1";
$resql_country = $db->query($sql_country);
$country_id = 0;

if ($resql_country && $db->num_rows($resql_country) > 0) {
    $obj_country = $db->fetch_object($resql_country);
    $country_id = $obj_country->rowid;
    echo "✓ Pays Maroc trouvé (ID: $country_id)\n";
    
    // Activer le pays Maroc
    $sql_activate = "UPDATE " . MAIN_DB_PREFIX . "c_country SET active = 1 WHERE rowid = " . $country_id;
    $db->query($sql_activate);
    echo "✓ Pays Maroc activé\n";
} else {
    echo "⚠ Pays Maroc non trouvé, utilisation de l'ID par défaut\n";
    $country_id = 149; // Valeur par défaut
}

// Informations de la société Alliance Synergie Santé
$company_config = array(
    // Informations générales
    'MAIN_INFO_SOCIETE_NOM'           => 'Alliance Synergie Santé',
    'MAIN_INFO_SOCIETE_ADDRESS'       => '145 avenue Hassan II, 1er étage, appt. 3, Parc la ligue arabe',
    'MAIN_INFO_SOCIETE_ZIP'           => '20000',
    'MAIN_INFO_SOCIETE_TOWN'          => 'Casablanca',
    'MAIN_INFO_SOCIETE_COUNTRY'       => $country_id,
    'MAIN_INFO_SOCIETE_STATE'         => '0',
    'MAIN_INFO_SOCIETE_TEL'           => '+212 5 22 23 73 50',
    'MAIN_INFO_SOCIETE_MAIL'          => 'contact@a2s.co.ma',
    'MAIN_INFO_SOCIETE_WEB'           => 'https://a2s.co.ma',
    
    // Capital et immatriculation
    'MAIN_INFO_SIREN'                 => '000029328000052', // ICE
    'MAIN_INFO_RCS'                   => 'RC 195653 Casablanca',
    'MAIN_INFO_SOCIETE_FORME_JURIDIQUE' => '3', // SARL
    
    // TVA et fiscal
    'MAIN_INFO_TVAINTRA'              => '', // IF à compléter si disponible
    
    // Devise
    'MAIN_MONNAIE'                    => 'MAD', // Dirham Marocain
    
    // Objet social
    'MAIN_INFO_SOCIETE_OBJECT'        => 'Société opérant dans le domaine de la santé, du bien-être et de la beauté. Spécialiste des soins de la peau. Promotion de produits pharmaceutiques de base et dermo-cosmétiques. Distribution de marques dermo-cosmétiques recommandées par les professionnels de santé.',
    
    // Notes
    'MAIN_INFO_SOCIETE_NOTE'          => 'Créée en 2009. Distributeur de 11 marques dermo-cosmétiques recommandées par les dermatologues. Au service des gynécologues, dermatologues et médecins esthétiques. Développement intégral des produits de la conception à la livraison en pharmacie.',
);

// Configurations supplémentaires spécifiques au Maroc
$additional_config = array(
    // Paramètres régionaux Maroc
    'MAIN_LANG_DEFAULT'               => 'fr_MA', // Français Maroc
    'MAIN_SIZE_LISTE_LIMIT'           => '25',
    
    // Format de date
    'MAIN_USE_HOUR_IN_DATE_RANGE'     => '1',
    
    // Numérotation des factures
    'FACTURE_ADDON'                   => 'mod_facture_terre',
    
    // TVA par défaut au Maroc (20% - standard)
    'MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS' => '20',
);

$configs_updated = 0;
$errors = 0;

// Mise à jour des constantes de configuration
foreach (array_merge($company_config, $additional_config) as $const_name => $const_value) {
    try {
        $result = dolibarr_set_const($db, $const_name, $const_value, 'chaine', 0, '', $conf->entity);
        if ($result > 0 || $result == 0) {
            echo "✓ Configuration: $const_name\n";
            $configs_updated++;
        } else {
            echo "✗ Erreur: $const_name\n";
            $errors++;
        }
    } catch (Exception $e) {
        echo "✗ Exception pour $const_name : " . $e->getMessage() . "\n";
        $errors++;
    }
}

// Configuration de la devise MAD (Dirham Marocain)
echo "\n=== Configuration de la devise MAD ===\n";
$sql = "SELECT code FROM " . MAIN_DB_PREFIX . "c_currencies WHERE code = 'MAD'";
$resql = $db->query($sql);
if ($resql) {
    if ($db->num_rows($resql) == 0) {
        $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "c_currencies (code, label, unicode, active) 
                       VALUES ('MAD', 'Dirham marocain', '[د.م.]', 1)";
        if ($db->query($sql_insert)) {
            echo "✓ Devise MAD ajoutée\n";
        } else {
            echo "✗ Erreur ajout devise MAD\n";
        }
    } else {
        echo "ℹ Devise MAD déjà présente\n";
        $sql_update = "UPDATE " . MAIN_DB_PREFIX . "c_currencies SET active = 1 WHERE code = 'MAD'";
        $db->query($sql_update);
        echo "✓ Devise MAD activée\n";
    }
}

// Configurer les taux de TVA marocains
echo "\n=== Configuration des taux de TVA Maroc ===\n";
$tva_rates = array(
    array('taux' => '20', 'code' => 'MA-20', 'label' => 'TVA 20% (Taux normal)'),
    array('taux' => '14', 'code' => 'MA-14', 'label' => 'TVA 14% (Taux intermédiaire)'),
    array('taux' => '10', 'code' => 'MA-10', 'label' => 'TVA 10% (Taux réduit)'),
    array('taux' => '7', 'code' => 'MA-7', 'label' => 'TVA 7% (Taux réduit)'),
    array('taux' => '0', 'code' => 'MA-0', 'label' => 'TVA 0% (Exonéré)'),
);

foreach ($tva_rates as $rate) {
    $sql_check = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_tva 
                  WHERE fk_pays = " . $country_id . " AND taux = " . $rate['taux'] . " AND code = '" . $db->escape($rate['code']) . "'";
    $resql = $db->query($sql_check);
    
    if ($resql && $db->num_rows($resql) == 0) {
        $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "c_tva 
                       (fk_pays, code, taux, localtax1, localtax2, localtax1_type, localtax2_type, recuperableonly, note, active) 
                       VALUES (" . $country_id . ", '" . $db->escape($rate['code']) . "', " . $rate['taux'] . ", 
                               0, 0, '0', '0', 0, '" . $db->escape($rate['label']) . "', 1)";
        
        if ($db->query($sql_insert)) {
            echo "✓ Taux TVA " . $rate['taux'] . "% ajouté\n";
        } else {
            echo "✗ Erreur ajout TVA " . $rate['taux'] . "%\n";
        }
    } else {
        echo "ℹ Taux TVA " . $rate['taux'] . "% déjà présent\n";
    }
}

// Résumé
echo "\n=== RÉSUMÉ ===\n";
echo "Pays Maroc ID: $country_id\n";
echo "Configurations mises à jour: $configs_updated\n";
echo "Erreurs: $errors\n";
echo "\n✓ Configuration de la société Alliance Synergie Santé (A2S) terminée\n";
echo "\nInformations configurées:\n";
echo "- Nom: Alliance Synergie Santé\n";
echo "- Adresse: 145 avenue Hassan II, Casablanca\n";
echo "- Téléphone: +212 5 22 23 73 50\n";
echo "- Site web: https://a2s.co.ma\n";
echo "- ICE: 000029328000052\n";
echo "- RC: 195653 Casablanca\n";
echo "- Pays: Maroc (ID: $country_id)\n";
echo "- Devise: MAD (Dirham marocain)\n";

?>