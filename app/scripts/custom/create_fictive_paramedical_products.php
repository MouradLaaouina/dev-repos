<?php
#!/usr/bin/env php

/**
 * Script CLI pour créer des produits paramédicaux fictifs avec stocks dans les entrepôts.
 *
 * Usage :
 *   php create_fictive_paramedical_products.php
 *
 * Ce script crée des produits paramédicaux fictifs (matériel médical, consommables, etc.)
 * et attribue des stocks dans différents entrepôts.
 *
 * Pré-requis :
 *   - Les entrepôts doivent être créés (voir create_warehouses.php)
 *   - Les extrafields doivent être créés (voir create_product_extrafields.php)
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
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/** @var DoliDB $db */
/** @var Conf $conf */

global $db, $conf, $langs, $user;

$langs->load("products");
$langs->load("stocks");

// Récupérer l'utilisateur admin
$user = new User($db);
$defaultUserId = !empty($conf->global->MAIN_IMPORT_DEFAULT_USER) ? (int) $conf->global->MAIN_IMPORT_DEFAULT_USER : 1;
if ($user->fetch($defaultUserId) <= 0) {
    fwrite(STDERR, "Impossible de récupérer l'utilisateur ID $defaultUserId\n");
    exit(1);
}

/* Définition des produits paramédicaux fictifs */
$products = [
    // Matériel médical
    [
        'ref' => 'MED-TENSI-001',
        'label' => 'Tensiomètre électronique au bras',
        'description' => 'Tensiomètre numérique automatique pour mesure de la tension artérielle',
        'type' => 0, // Produit
        'price' => 450.00,
        'price_ttc' => 540.00,
        'tva_tx' => 20,
        'weight' => 0.5,
        'category' => 'Matériel Médical',
        'subcategory' => 'Appareils de mesure',
        'stock' => ['central' => 45, 'nord' => 20, 'sud' => 15],
    ],
    [
        'ref' => 'MED-THERM-002',
        'label' => 'Thermomètre infrarouge sans contact',
        'description' => 'Thermomètre médical infrarouge frontal et auriculaire',
        'type' => 0,
        'price' => 280.00,
        'price_ttc' => 336.00,
        'tva_tx' => 20,
        'weight' => 0.2,
        'category' => 'Matériel Médical',
        'subcategory' => 'Appareils de mesure',
        'stock' => ['central' => 120, 'nord' => 50, 'sud' => 30, 'rabat' => 40],
    ],
    [
        'ref' => 'MED-STETHO-003',
        'label' => 'Stéthoscope double pavillon',
        'description' => 'Stéthoscope professionnel en acier inoxydable',
        'type' => 0,
        'price' => 650.00,
        'price_ttc' => 780.00,
        'tva_tx' => 20,
        'weight' => 0.3,
        'category' => 'Matériel Médical',
        'subcategory' => 'Instruments diagnostic',
        'stock' => ['central' => 35, 'rabat' => 15],
    ],
    [
        'ref' => 'MED-GLUCOM-004',
        'label' => 'Glucomètre avec bandelettes (kit)',
        'description' => 'Lecteur de glycémie avec 50 bandelettes et stylo autopiqueur',
        'type' => 0,
        'price' => 320.00,
        'price_ttc' => 384.00,
        'tva_tx' => 20,
        'weight' => 0.4,
        'category' => 'Matériel Médical',
        'subcategory' => 'Appareils de mesure',
        'stock' => ['central' => 80, 'nord' => 30, 'sud' => 25, 'est' => 20],
    ],

    // Consommables médicaux
    [
        'ref' => 'CONS-MASQ-010',
        'label' => 'Masques chirurgicaux 3 plis (boîte de 50)',
        'description' => 'Masques à usage unique, Type II, norme EN14683',
        'type' => 0,
        'price' => 35.00,
        'price_ttc' => 42.00,
        'tva_tx' => 20,
        'weight' => 0.15,
        'category' => 'Consommables',
        'subcategory' => 'Protection individuelle',
        'stock' => ['central' => 500, 'nord' => 200, 'sud' => 150, 'est' => 100, 'rabat' => 180],
    ],
    [
        'ref' => 'CONS-GANT-011',
        'label' => 'Gants nitrile bleus (boîte de 100)',
        'description' => 'Gants d\'examen non stériles, sans poudre, taille M',
        'type' => 0,
        'price' => 45.00,
        'price_ttc' => 54.00,
        'tva_tx' => 20,
        'weight' => 0.6,
        'category' => 'Consommables',
        'subcategory' => 'Protection individuelle',
        'stock' => ['central' => 400, 'nord' => 150, 'sud' => 120, 'transit' => 80],
    ],
    [
        'ref' => 'CONS-SERING-012',
        'label' => 'Seringues 5ml (boîte de 100)',
        'description' => 'Seringues stériles à usage unique avec aiguille 21G',
        'type' => 0,
        'price' => 28.00,
        'price_ttc' => 33.60,
        'tva_tx' => 20,
        'weight' => 0.8,
        'category' => 'Consommables',
        'subcategory' => 'Injection',
        'stock' => ['central' => 600, 'nord' => 180, 'rabat' => 200],
    ],
    [
        'ref' => 'CONS-COMPR-013',
        'label' => 'Compresses stériles 10x10 cm (sachet de 10)',
        'description' => 'Compresses non tissées stériles pour pansements',
        'type' => 0,
        'price' => 12.00,
        'price_ttc' => 14.40,
        'tva_tx' => 20,
        'weight' => 0.05,
        'category' => 'Consommables',
        'subcategory' => 'Pansements',
        'stock' => ['central' => 800, 'nord' => 250, 'sud' => 200, 'transit' => 150],
    ],
    [
        'ref' => 'CONS-BANDE-014',
        'label' => 'Bande élastique 10cm x 4m',
        'description' => 'Bande de contention élastique réutilisable',
        'type' => 0,
        'price' => 18.00,
        'price_ttc' => 21.60,
        'tva_tx' => 20,
        'weight' => 0.08,
        'category' => 'Consommables',
        'subcategory' => 'Pansements',
        'stock' => ['central' => 350, 'nord' => 120, 'sud' => 80],
    ],

    // Dermocosmétique
    [
        'ref' => 'DERM-HYDR-020',
        'label' => 'Crème hydratante visage SPF30 (50ml)',
        'description' => 'Crème de jour hydratante avec protection solaire pour peaux sensibles',
        'type' => 0,
        'price' => 145.00,
        'price_ttc' => 174.00,
        'tva_tx' => 20,
        'weight' => 0.12,
        'category' => 'Dermocosmétique',
        'subcategory' => 'Soins visage',
        'stock' => ['central' => 200, 'sud' => 150, 'depot' => 80],
    ],
    [
        'ref' => 'DERM-SERU-021',
        'label' => 'Sérum anti-âge à la vitamine C (30ml)',
        'description' => 'Sérum concentré anti-rides et antioxydant',
        'type' => 0,
        'price' => 320.00,
        'price_ttc' => 384.00,
        'tva_tx' => 20,
        'weight' => 0.08,
        'category' => 'Dermocosmétique',
        'subcategory' => 'Soins visage',
        'stock' => ['central' => 120, 'sud' => 60, 'depot' => 40],
    ],
    [
        'ref' => 'DERM-NETT-022',
        'label' => 'Gel nettoyant purifiant (200ml)',
        'description' => 'Gel moussant pour peaux mixtes à grasses',
        'type' => 0,
        'price' => 95.00,
        'price_ttc' => 114.00,
        'tva_tx' => 20,
        'weight' => 0.25,
        'category' => 'Dermocosmétique',
        'subcategory' => 'Nettoyants',
        'stock' => ['central' => 180, 'nord' => 80, 'sud' => 100, 'depot' => 60],
    ],

    // Dispositifs médicaux
    [
        'ref' => 'DM-MULETTE-030',
        'label' => 'Béquilles sous-brachiales ajustables (paire)',
        'description' => 'Béquilles aluminium réglables en hauteur, charge max 120kg',
        'type' => 0,
        'price' => 380.00,
        'price_ttc' => 456.00,
        'tva_tx' => 20,
        'weight' => 1.8,
        'category' => 'Dispositifs Médicaux',
        'subcategory' => 'Aide à la mobilité',
        'stock' => ['central' => 40, 'nord' => 15, 'rabat' => 10],
    ],
    [
        'ref' => 'DM-FAUTEUIL-031',
        'label' => 'Fauteuil roulant pliant standard',
        'description' => 'Fauteuil roulant manuel pliant, largeur assise 45cm',
        'type' => 0,
        'price' => 2800.00,
        'price_ttc' => 3360.00,
        'tva_tx' => 20,
        'weight' => 18.5,
        'category' => 'Dispositifs Médicaux',
        'subcategory' => 'Aide à la mobilité',
        'stock' => ['central' => 25, 'nord' => 8, 'rabat' => 5],
    ],
    [
        'ref' => 'DM-ATTELLE-032',
        'label' => 'Attelle poignet droite avec stabilisation pouce',
        'description' => 'Orthèse de poignet thermoformable, taille M',
        'type' => 0,
        'price' => 220.00,
        'price_ttc' => 264.00,
        'tva_tx' => 20,
        'weight' => 0.15,
        'category' => 'Dispositifs Médicaux',
        'subcategory' => 'Orthopédie',
        'stock' => ['central' => 60, 'nord' => 20, 'est' => 15],
    ],

    // Hygiène
    [
        'ref' => 'HYG-GELHYDRO-040',
        'label' => 'Gel hydroalcoolique 500ml',
        'description' => 'Solution désinfectante pour les mains, 70% alcool',
        'type' => 0,
        'price' => 38.00,
        'price_ttc' => 45.60,
        'tva_tx' => 20,
        'weight' => 0.55,
        'category' => 'Hygiène',
        'subcategory' => 'Désinfection',
        'stock' => ['central' => 450, 'nord' => 180, 'sud' => 120, 'depot' => 100],
    ],
    [
        'ref' => 'HYG-SAVON-041',
        'label' => 'Savon liquide antiseptique 1L',
        'description' => 'Savon dermatologique à la chlorhexidine',
        'type' => 0,
        'price' => 52.00,
        'price_ttc' => 62.40,
        'tva_tx' => 20,
        'weight' => 1.05,
        'category' => 'Hygiène',
        'subcategory' => 'Nettoyants',
        'stock' => ['central' => 280, 'nord' => 100, 'transit' => 80],
    ],
];

/* Utils */
function fatal($m) { fwrite(STDERR,"[ERROR] $m\n"); exit(1); }
function info($m) { echo "[INFO] $m\n"; }
function done($m) { echo "[OK]   $m\n"; }

/* Récupérer les entrepôts */
function getWarehouses($db) {
    global $conf;
    $warehouses = [];

    // Utiliser le préfixe depuis la configuration
    $prefix = empty($conf->db->prefix) ? 'llx_' : $conf->db->prefix;

    // Note: le champ s'appelle 'ref' pour les entrepôts dans Dolibarr
    $sql = "SELECT rowid, ref as label FROM " . $prefix . "entrepot WHERE statut = 1";
    echo "  SQL: $sql\n\n";

    $resql = $db->query($sql);

    if (!$resql) {
        echo "  ✗ Erreur SQL: " . $db->lasterror() . "\n";
        return $warehouses;
    }

    if ($resql) {
        echo "  Entrepôts disponibles dans la base:\n";
        $allWarehouses = [];
        while ($obj = $db->fetch_object($resql)) {
            $allWarehouses[] = $obj;
            echo "    - ID: " . $obj->rowid . " | Label: " . $obj->label . "\n";
        }

        // Mapper les entrepôts aux clés
        foreach ($allWarehouses as $obj) {
            $key = '';
            if (stripos($obj->label, 'Central') !== false || stripos($obj->label, 'central') !== false) $key = 'central';
            elseif (stripos($obj->label, 'Nord') !== false || stripos($obj->label, 'Tanger') !== false) $key = 'nord';
            elseif (stripos($obj->label, 'Sud') !== false || stripos($obj->label, 'Marrakech') !== false) $key = 'sud';
            elseif (stripos($obj->label, 'Est') !== false || stripos($obj->label, 'Oujda') !== false) $key = 'est';
            elseif (stripos($obj->label, 'Rabat') !== false || stripos($obj->label, 'rabat') !== false) $key = 'rabat';
            elseif (stripos($obj->label, 'Transit') !== false || stripos($obj->label, 'Import') !== false) $key = 'transit';
            elseif (stripos($obj->label, 'Dépôt') !== false || stripos($obj->label, 'Depot') !== false || stripos($obj->label, 'Pharmacie') !== false) $key = 'depot';

            if ($key) {
                $warehouses[$key] = (int)$obj->rowid;
                echo "    ✓ Mappé '$obj->label' → clé '$key'\n";
            } else {
                echo "    ⚠ Entrepôt '$obj->label' non mappé (pas de mot-clé reconnu)\n";
            }
        }

        if (empty($warehouses)) {
            echo "\n  ℹ Aucun entrepôt ne correspond aux mots-clés attendus.\n";
            echo "  ℹ Utilisation de tous les entrepôts disponibles...\n\n";

            // Si aucun mapping, utiliser tous les entrepôts disponibles
            foreach ($allWarehouses as $i => $obj) {
                $genericKey = 'warehouse_' . ($i + 1);
                $warehouses[$genericKey] = (int)$obj->rowid;
            }

            // Ajouter des alias pour les clés standard
            if (count($allWarehouses) > 0) {
                $warehouses['central'] = (int)$allWarehouses[0]->rowid;
                if (count($allWarehouses) > 1) $warehouses['nord'] = (int)$allWarehouses[1]->rowid;
                if (count($allWarehouses) > 2) $warehouses['sud'] = (int)$allWarehouses[2]->rowid;
                if (count($allWarehouses) > 3) $warehouses['est'] = (int)$allWarehouses[3]->rowid;
                if (count($allWarehouses) > 4) $warehouses['rabat'] = (int)$allWarehouses[4]->rowid;
                if (count($allWarehouses) > 5) $warehouses['transit'] = (int)$allWarehouses[5]->rowid;
                if (count($allWarehouses) > 6) $warehouses['depot'] = (int)$allWarehouses[6]->rowid;
            }
        }
    }

    return $warehouses;
}

/* Récupérer ou créer une catégorie */
function getOrCreateCategory($db, $label, $type, $parentId = null) {
    global $user;

    // Chercher la catégorie existante
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "categorie WHERE label = '" . $db->escape($label) . "' AND type = " . (int)$type;
    if ($parentId !== null) {
        $sql .= " AND fk_parent = " . (int)$parentId;
    }
    $sql .= " LIMIT 1";

    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            return (int)$obj->rowid;
        }
    }

    // Créer la catégorie
    $cat = new Categorie($db);
    $cat->label = $label;
    $cat->type = $type;
    if ($parentId !== null) {
        $cat->fk_parent = $parentId;
    }

    $result = $cat->create($user);
    if ($result > 0) {
        return $cat->id;
    }

    return null;
}

/* Compteurs */
$totalCreated = 0;
$totalSkipped = 0;
$totalErrors = 0;
$totalStockMoves = 0;

echo "=================================================================\n";
echo "Création des produits paramédicaux fictifs\n";
echo "=================================================================\n\n";

// Récupérer les entrepôts
$warehouses = getWarehouses($db);
if (empty($warehouses)) {
    fatal("Aucun entrepôt trouvé. Veuillez d'abord exécuter create_warehouses.php");
}

info("Entrepôts trouvés: " . count($warehouses));

foreach ($products as $productData) {
    $ref = $productData['ref'];

    // Vérifier si le produit existe déjà
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product WHERE ref = '" . $db->escape($ref) . "' LIMIT 1";
    $resql = $db->query($sql);

    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            info("Produit '$ref' existe déjà (ID: $obj->rowid), ignoré");
            $totalSkipped++;
            continue;
        }
    }

    $db->begin();

    // Créer le produit
    $product = new Product($db);
    $product->ref = $productData['ref'];
    $product->label = $productData['label'];
    $product->description = $productData['description'];
    $product->type = $productData['type'];
    $product->price = $productData['price'];
    $product->price_ttc = $productData['price_ttc'];
    $product->tva_tx = $productData['tva_tx'];
    $product->weight = $productData['weight'];
    $product->status = 1; // En vente
    $product->status_buy = 1; // À l'achat

    $result = $product->create($user);

    if ($result > 0) {
        done("Produit créé: '$ref' (ID: $result)");
        $totalCreated++;

        // Gérer les catégories
        if (!empty($productData['category'])) {
            $catId = getOrCreateCategory($db, $productData['category'], 0);
            if ($catId > 0) {
                $product->setCategories([$catId]);
            }

            if (!empty($productData['subcategory'])) {
                $subCatId = getOrCreateCategory($db, $productData['subcategory'], 0, $catId);
                if ($subCatId > 0) {
                    $product->setCategories([$catId, $subCatId]);
                }
            }
        }

        // Ajouter des stocks dans les entrepôts
        if (!empty($productData['stock'])) {
            foreach ($productData['stock'] as $warehouseKey => $qty) {
                if (isset($warehouses[$warehouseKey]) && $qty > 0) {
                    $warehouseId = $warehouses[$warehouseKey];

                    // Créer un mouvement de stock (entrée)
                    $movementstock = new MouvementStock($db);
                    $movementstock->origin = null;
                    $movementstock->fk_origin = null;

                    $result_stock = $movementstock->_create(
                        $user,
                        $product->id,
                        $warehouseId,
                        $qty,
                        0, // Type: 0 = entrée manuelle
                        0, // Prix unitaire
                        'STOCK_INIT',
                        'Stock initial - Données fictives'
                    );

                    if ($result_stock > 0) {
                        $totalStockMoves++;
                        info("  + Stock ajouté: $qty unités dans entrepôt #$warehouseId");
                    } else {
                        fwrite(STDERR, "  ! Erreur ajout stock pour entrepôt #$warehouseId\n");
                    }
                }
            }
        }

        $db->commit();
    } else {
        $db->rollback();
        $errorMsg = $product->error ?: 'Erreur inconnue';
        if (!empty($product->errors)) {
            $errorMsg .= ' | ' . implode(' ; ', $product->errors);
        }
        fwrite(STDERR, "[ERROR] Échec création produit '$ref': $errorMsg\n");
        $totalErrors++;
    }
}

echo "\n=================================================================\n";
echo "RÉSUMÉ:\n";
echo "  - Produits créés: $totalCreated\n";
echo "  - Produits existants (ignorés): $totalSkipped\n";
echo "  - Mouvements de stock créés: $totalStockMoves\n";
echo "  - Erreurs: $totalErrors\n";
echo "=================================================================\n";

if ($totalCreated > 0) {
    echo "\n✓ Les produits paramédicaux fictifs ont été créés avec succès.\n";
    echo "Les stocks ont été affectés aux différents entrepôts.\n";
}

exit(0);
