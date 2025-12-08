<?php
#!/usr/bin/env php
<?php
/**
 * Script de vérification des données fictives créées.
 *
 * Usage :
 *   php verify_fictive_data.php
 *
 * Ce script vérifie que toutes les données fictives ont été correctement créées:
 *   - Entrepôts
 *   - Produits
 *   - Clients
 *   - Stocks
 *   - Catégories
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

require_once '/var/www/html/master.inc.php';

/** @var DoliDB $db */
global $db, $conf;

/* Utils */
function printHeader($title) {
    echo "\n";
    echo str_repeat("=", 70) . "\n";
    echo "  $title\n";
    echo str_repeat("=", 70) . "\n";
}

function printSection($title) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "  $title\n";
    echo str_repeat("-", 70) . "\n";
}

function printSuccess($msg) {
    echo "  ✓ " . $msg . "\n";
}

function printWarning($msg) {
    echo "  ⚠ " . $msg . "\n";
}

function printError($msg) {
    echo "  ✗ " . $msg . "\n";
}

function printInfo($label, $value) {
    echo "  " . str_pad($label, 40) . ": " . $value . "\n";
}

/* Vérifications */
$issues = 0;
$warnings = 0;

printHeader("VÉRIFICATION DES DONNÉES FICTIVES A2S");
echo "Date: " . date('Y-m-d H:i:s') . "\n";

// 1. Vérifier les entrepôts
printSection("1. ENTREPÔTS");

$sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "entrepot WHERE statut = 1";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $nbWarehouses = (int)$obj->nb;
    printInfo("Nombre d'entrepôts actifs", $nbWarehouses);

    if ($nbWarehouses >= 7) {
        printSuccess("Entrepôts créés correctement");
    } elseif ($nbWarehouses > 0) {
        printWarning("Seulement $nbWarehouses entrepôts trouvés (attendu: 7)");
        $warnings++;
    } else {
        printError("Aucun entrepôt trouvé !");
        $issues++;
    }

    // Liste des entrepôts
    $sql = "SELECT label, statut FROM " . MAIN_DB_PREFIX . "entrepot ORDER BY rowid";
    $resql = $db->query($sql);
    if ($resql) {
        echo "\n  Entrepôts trouvés:\n";
        while ($obj = $db->fetch_object($resql)) {
            $status = $obj->statut == 1 ? '✓' : '✗';
            echo "    $status " . $obj->label . "\n";
        }
    }
}

// 2. Vérifier les produits
printSection("2. PRODUITS PARAMÉDICAUX");

$sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "product WHERE ref LIKE 'MED-%' OR ref LIKE 'CONS-%' OR ref LIKE 'DERM-%' OR ref LIKE 'DM-%' OR ref LIKE 'HYG-%'";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $nbProducts = (int)$obj->nb;
    printInfo("Nombre de produits fictifs", $nbProducts);

    if ($nbProducts >= 17) {
        printSuccess("Produits créés correctement");
    } elseif ($nbProducts > 0) {
        printWarning("Seulement $nbProducts produits trouvés (attendu: 17)");
        $warnings++;
    } else {
        printError("Aucun produit fictif trouvé !");
        $issues++;
    }

    // Détails par catégorie
    $categories = [
        'MED-' => 'Matériel Médical',
        'CONS-' => 'Consommables',
        'DERM-' => 'Dermocosmétique',
        'DM-' => 'Dispositifs Médicaux',
        'HYG-' => 'Hygiène',
    ];

    echo "\n  Répartition par catégorie:\n";
    foreach ($categories as $prefix => $label) {
        $sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "product WHERE ref LIKE '" . $prefix . "%'";
        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            echo "    " . str_pad($label, 25) . ": " . $obj->nb . " produits\n";
        }
    }

    // Valeur totale du stock
    $sql = "SELECT SUM(p.price * ps.reel) as total_value
            FROM " . MAIN_DB_PREFIX . "product p
            INNER JOIN " . MAIN_DB_PREFIX . "product_stock ps ON p.rowid = ps.fk_product
            WHERE (p.ref LIKE 'MED-%' OR p.ref LIKE 'CONS-%' OR p.ref LIKE 'DERM-%' OR p.ref LIKE 'DM-%' OR p.ref LIKE 'HYG-%')";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $totalValue = (float)$obj->total_value;
        echo "\n";
        printInfo("Valeur totale du stock", number_format($totalValue, 2) . " DH HT");
    }
}

// 3. Vérifier les stocks
printSection("3. STOCKS");

$sql = "SELECT COUNT(DISTINCT ps.fk_product) as nb_products,
               COUNT(DISTINCT ps.fk_entrepot) as nb_warehouses,
               SUM(ps.reel) as total_qty
        FROM " . MAIN_DB_PREFIX . "product_stock ps
        INNER JOIN " . MAIN_DB_PREFIX . "product p ON ps.fk_product = p.rowid
        WHERE (p.ref LIKE 'MED-%' OR p.ref LIKE 'CONS-%' OR p.ref LIKE 'DERM-%' OR p.ref LIKE 'DM-%' OR p.ref LIKE 'HYG-%')
        AND ps.reel > 0";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    printInfo("Produits avec stock", $obj->nb_products);
    printInfo("Entrepôts utilisés", $obj->nb_warehouses);
    printInfo("Quantité totale en stock", number_format($obj->total_qty, 0) . " unités");

    if ($obj->total_qty > 0) {
        printSuccess("Stocks créés correctement");
    } else {
        printWarning("Aucun stock trouvé pour les produits fictifs");
        $warnings++;
    }

    // Top 5 des produits avec le plus de stock
    echo "\n  Top 5 des produits en stock:\n";
    $sql = "SELECT p.ref, p.label, SUM(ps.reel) as total
            FROM " . MAIN_DB_PREFIX . "product_stock ps
            INNER JOIN " . MAIN_DB_PREFIX . "product p ON ps.fk_product = p.rowid
            WHERE (p.ref LIKE 'MED-%' OR p.ref LIKE 'CONS-%' OR p.ref LIKE 'DERM-%' OR p.ref LIKE 'DM-%' OR p.ref LIKE 'HYG-%')
            GROUP BY p.rowid
            ORDER BY total DESC
            LIMIT 5";
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            echo "    " . str_pad($obj->ref, 20) . ": " . number_format($obj->total, 0) . " unités\n";
        }
    }
}

// 4. Vérifier les clients
printSection("4. CLIENTS");

$sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "societe WHERE code_client LIKE 'CL-%' AND client IN (1, 3)";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $nbClients = (int)$obj->nb;
    printInfo("Nombre de clients fictifs", $nbClients);

    if ($nbClients >= 14) {
        printSuccess("Clients créés correctement");
    } elseif ($nbClients > 0) {
        printWarning("Seulement $nbClients clients trouvés (attendu: 14)");
        $warnings++;
    } else {
        printError("Aucun client fictif trouvé !");
        $issues++;
    }

    // Répartition B2B / B2C
    echo "\n  Répartition par type:\n";

    // B2B
    $sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "societe WHERE code_client LIKE 'CL-%' AND client IN (1, 3) AND typent_id != 8";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        echo "    Clients B2B (professionnels)  : " . $obj->nb . " clients\n";
    }

    // B2C
    $sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "societe WHERE code_client LIKE 'CL-%' AND client IN (1, 3) AND typent_id = 8";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        echo "    Clients B2C (particuliers)    : " . $obj->nb . " clients\n";
    }

    // Répartition par type de client B2B
    echo "\n  Détail clients B2B:\n";
    $types = [
        'CL-PHAR-%' => 'Pharmacies',
        'CL-CLIN-%' => 'Cliniques/Centres médicaux',
        'CL-HOP-%' => 'Hôpitaux',
        'CL-DIST-%' => 'Distributeurs',
        'CL-CAB-%' => 'Cabinets',
        'CL-LAB-%' => 'Laboratoires',
    ];

    foreach ($types as $prefix => $label) {
        $sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "societe WHERE code_client LIKE '" . $prefix . "'";
        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj->nb > 0) {
                echo "    " . str_pad($label, 30) . ": " . $obj->nb . " client(s)\n";
            }
        }
    }

    // Créances autorisées totales
    $sql = "SELECT SUM(outstanding_limit) as total_limit
            FROM " . MAIN_DB_PREFIX . "societe
            WHERE code_client LIKE 'CL-%' AND client IN (1, 3)";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $totalLimit = (float)$obj->total_limit;
        echo "\n";
        printInfo("Total créances autorisées", number_format($totalLimit, 0) . " DH");
    }

    // Top 5 clients par créance autorisée
    echo "\n  Top 5 clients par créance autorisée:\n";
    $sql = "SELECT nom, code_client, outstanding_limit
            FROM " . MAIN_DB_PREFIX . "societe
            WHERE code_client LIKE 'CL-%' AND client IN (1, 3)
            ORDER BY outstanding_limit DESC
            LIMIT 5";
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            echo "    " . str_pad($obj->code_client, 15) . ": " . number_format($obj->outstanding_limit, 0) . " DH\n";
        }
    }
}

// 5. Vérifier les contacts
printSection("5. CONTACTS");

$sql = "SELECT COUNT(*) as nb
        FROM " . MAIN_DB_PREFIX . "socpeople sp
        INNER JOIN " . MAIN_DB_PREFIX . "societe s ON sp.fk_soc = s.rowid
        WHERE s.code_client LIKE 'CL-%'";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $nbContacts = (int)$obj->nb;
    printInfo("Nombre de contacts", $nbContacts);

    if ($nbContacts > 0) {
        printSuccess("Contacts créés pour les clients B2B");
    } else {
        printWarning("Aucun contact trouvé pour les clients");
        $warnings++;
    }
}

// 6. Vérifier les catégories
printSection("6. CATÉGORIES PRODUITS");

$sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "categorie WHERE type = 0";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    printInfo("Nombre de catégories produits", $obj->nb);

    if ($obj->nb > 0) {
        printSuccess("Catégories créées");

        // Liste des catégories principales
        echo "\n  Catégories principales:\n";
        $sql = "SELECT label, (SELECT COUNT(*) FROM " . MAIN_DB_PREFIX . "categorie_product cp WHERE cp.fk_categorie = c.rowid) as nb_products
                FROM " . MAIN_DB_PREFIX . "categorie c
                WHERE type = 0 AND (fk_parent IS NULL OR fk_parent = 0)
                ORDER BY label";
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                echo "    " . str_pad($obj->label, 30) . ": " . $obj->nb_products . " produit(s)\n";
            }
        }
    } else {
        printWarning("Aucune catégorie produit trouvée");
        $warnings++;
    }
}

// 7. Vérifier les commandes (si créées)
printSection("7. COMMANDES (optionnel)");

$sql = "SELECT COUNT(*) as nb
        FROM " . MAIN_DB_PREFIX . "commande c
        INNER JOIN " . MAIN_DB_PREFIX . "societe s ON c.fk_soc = s.rowid
        WHERE s.code_client LIKE 'CL-%'";
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $nbOrders = (int)$obj->nb;
    printInfo("Nombre de commandes", $nbOrders);

    if ($nbOrders > 0) {
        printSuccess("Commandes de test créées");

        // Statistiques commandes
        $sql = "SELECT
                    COUNT(*) as nb,
                    SUM(c.total_ht) as total_ht,
                    AVG(c.total_ht) as avg_ht
                FROM " . MAIN_DB_PREFIX . "commande c
                INNER JOIN " . MAIN_DB_PREFIX . "societe s ON c.fk_soc = s.rowid
                WHERE s.code_client LIKE 'CL-%' AND c.fk_statut > 0";
        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            echo "\n";
            printInfo("Commandes validées", $obj->nb);
            printInfo("Montant total", number_format($obj->total_ht, 2) . " DH HT");
            printInfo("Panier moyen", number_format($obj->avg_ht, 2) . " DH HT");
        }
    } else {
        printInfo("Aucune commande", "Utilisez create_fictive_orders.php pour en créer");
    }
}

// RÉSUMÉ FINAL
printHeader("RÉSUMÉ DE LA VÉRIFICATION");

echo "\n";
if ($issues == 0 && $warnings == 0) {
    printSuccess("✓ TOUT EST OK ! Toutes les données fictives sont en place.");
    echo "\n";
    printInfo("Statut", "✓ PARFAIT");
} elseif ($issues == 0) {
    printWarning("⚠ ATTENTION : $warnings avertissement(s) détecté(s)");
    echo "\n";
    printInfo("Statut", "⚠ VÉRIFIER LES AVERTISSEMENTS");
} else {
    printError("✗ PROBLÈMES : $issues erreur(s) et $warnings avertissement(s)");
    echo "\n";
    printInfo("Statut", "✗ ACTION REQUISE");
}

echo "\n";
printInfo("Erreurs", $issues);
printInfo("Avertissements", $warnings);

echo "\n" . str_repeat("=", 70) . "\n";

if ($issues > 0) {
    echo "\n⚠ Des problèmes ont été détectés. Veuillez exécuter les scripts manquants:\n";
    echo "  1. php create_warehouses.php\n";
    echo "  2. php create_product_extrafields.php\n";
    echo "  3. php create_fictive_paramedical_products.php\n";
    echo "  4. php create_fictive_clients.php\n";
    echo "  5. php create_fictive_orders.php (optionnel)\n";
    echo "\nOU utilisez le script automatique:\n";
    echo "  ./setup_fictive_data.sh\n\n";
} else {
    echo "\n✓ Votre environnement de test A2S est prêt !\n";
    echo "\nProchaines étapes:\n";
    echo "  • Connectez-vous à Dolibarr\n";
    echo "  • Testez la création de commandes\n";
    echo "  • Vérifiez les stocks et mouvements\n";
    echo "  • Testez les créances clients\n";
    echo "  • Générez des factures\n\n";
}

exit($issues > 0 ? 1 : 0);
