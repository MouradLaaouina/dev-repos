<?php
/**
 * Script CLI pour importer des articles/produits Dolibarr à partir d'un fichier Excel.
 *
 * Usage :
 *   php import_articles_from_excel.php /chemin/vers/articles.xls /chemin/vers/articles_mapping_dolibarr.xlsx
 *
 * Le script lit le classeur de données (premier argument) et utilise le classeur de
 * mapping (deuxième argument) pour déterminer à quelle colonne Dolibarr correspond
 * chaque colonne Excel.
 *
 * Pré-requis :
 *   - Lancer ce script en contexte Dolibarr (CLI) avec PHP CLI.
 *   - L'extension PHPSpreadsheet doit être disponible (via composer require phpoffice/phpspreadsheet).
 *   - Les extrafields référencés dans le mapping doivent exister sur les produits.
 *   - Les catégories (familles/sous-familles) doivent être créées au préalable.
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en CLI.\n");
    exit(1);
}

if ($argc < 3) {
    fwrite(STDERR, "Usage: php import_articles_from_excel.php <fichier_donnees.xls> <fichier_mapping.xlsx>\n");
    exit(1);
}

$dataFile = $argv[1];
$mappingFile = $argv[2];

if (!is_readable($dataFile)) {
    fwrite(STDERR, "Fichier de données introuvable: $dataFile\n");
    exit(1);
}
if (!is_readable($mappingFile)) {
    fwrite(STDERR, "Fichier de mapping introuvable: $mappingFile\n");
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
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

require_once __DIR__ . '/vendor/autoload.php';

/** @var DoliDB $db */
/** @var Conf $conf */

global $db, $conf, $langs;

$langs->load("products");

$usercli = new User($db);
$defaultUserId = !empty($conf->global->MAIN_IMPORT_DEFAULT_USER) ? (int) $conf->global->MAIN_IMPORT_DEFAULT_USER : 1;
if ($usercli->fetch($defaultUserId) <= 0) {
    fwrite(STDERR, "Impossible de récupérer l'utilisateur ID $defaultUserId\n");
    exit(1);
}

$startTime = microtime(true);
echo 'Fichier données : ' . $dataFile . "\n";
echo 'Fichier mapping : ' . $mappingFile . "\n";
echo "Chargement du mapping...\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

$mappingSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($mappingFile);
$mappingSheet = $mappingSpreadsheet->getActiveSheet();
echo 'Mapping chargé.' . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

echo 'Chargement du fichier de données...' . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();
$dataReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($dataFile);
if (method_exists($dataReader, 'setReadDataOnly')) {
    $dataReader->setReadDataOnly(true);
}
if (method_exists($dataReader, 'setReadEmptyCells')) {
    $dataReader->setReadEmptyCells(false);
}
$dataSpreadsheet = $dataReader->load($dataFile);
$dataSheet = $dataSpreadsheet->getActiveSheet();
echo 'Fichier de données chargé.' . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

// Lecture du mapping
$mapping = [];
$highestMapRow = (int) $mappingSheet->getHighestRow();
for ($row = 3; $row <= $highestMapRow; $row++) {
    $sourceLabel = trim((string) $mappingSheet->getCell('B' . $row)->getValue());
    $tableName = trim((string) $mappingSheet->getCell('D' . $row)->getValue());
    $fieldName = trim((string) $mappingSheet->getCell('E' . $row)->getValue());
    if (!$sourceLabel || !$tableName || !$fieldName) {
        continue;
    }
    $normalizedLabel = normalizeLabel($sourceLabel);
    if ($normalizedLabel === '') {
        continue;
    }
    $mapping[$normalizedLabel] = [
        'table' => strtolower($tableName),
        'field' => strtolower($fieldName),
        'source' => $sourceLabel,
    ];
}

if (empty($mapping)) {
    fwrite(STDERR, "Aucun mapping valide trouvé dans $mappingFile\n");
    exit(1);
}

echo 'Nombre de colonnes mappées : ' . count($mapping) . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

// Lecture des en-têtes
$headerRow = 1;
$highestColumn = $dataSheet->getHighestColumn();
$headers = [];
foreach ($dataSheet->rangeToArray('A' . $headerRow . ':' . $highestColumn . $headerRow, null, true, false) as $row) {
    foreach ($row as $idx => $value) {
        $headers[$idx] = trim((string) $value);
    }
}

if (empty($headers)) {
    fwrite(STDERR, "Impossible de lire l'entête du fichier de données.\n");
    exit(1);
}

$rowsImported = 0;
$rowsUpdated = 0;
$rowsSkipped = 0;

$highestDataRow = (int) $dataSheet->getHighestDataRow();
$totalRows = max(0, $highestDataRow - $headerRow);
echo 'Nombre de lignes à analyser : ' . $totalRows . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

$unknownHeaders = [];
$categoriesCache = []; // Cache pour les catégories

for ($row = $headerRow + 1; $row <= $highestDataRow; $row++) {
    $rowValues = $dataSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false);
    if (empty($rowValues) || !is_array($rowValues[0])) {
        continue;
    }
    $rowData = $rowValues[0];
    $processed = $row - $headerRow;

    // Organiser les données par table
    $productFields = [];
    $extraFields = [];
    $categoriesFamille = [];
    $categoriesSousFamille = [];
    $imageFields = [];

    foreach ($rowData as $idx => $value) {
        $header = isset($headers[$idx]) ? (string) $headers[$idx] : '';
        $normalizedHeader = normalizeLabel($header);
        if ($normalizedHeader === '' || !isset($mapping[$normalizedHeader])) {
            if ($header !== '') {
                $unknownHeaders[$header] = true;
            }
            continue;
        }

        $map = $mapping[$normalizedHeader];
        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1) . $row;
        $cell = $dataSheet->getCell($cellCoordinate);
        $formattedValue = formatCellValue($value, $cell);

        switch ($map['table']) {
            case 'llx_product':
                $productFields[$map['field']] = $formattedValue;
                break;
            case 'extrafield':
                $extraFields['options_' . $map['field']] = $formattedValue;
                break;
            case 'llx_categorie':
                // Déterminer si c'est famille ou sous-famille
                if (stripos($map['source'], 'famille') !== false && stripos($map['source'], 'sous') === false) {
                    $categoriesFamille[] = $formattedValue;
                } elseif (stripos($map['source'], 'sous') !== false) {
                    $categoriesSousFamille[] = $formattedValue;
                }
                break;
            case 'image':
                $imageFields[$map['field']] = $formattedValue;
                break;
            // Les autres tables (llx_product_price, llx_product_stock, etc.) seront traitées plus tard
        }
    }

    // Vérifier que nous avons au moins une référence
    $ref = trim($productFields['ref'] ?? '');
    $label = trim($productFields['label'] ?? '');

    if (empty($ref)) {
        $rowsSkipped++;
        echo sprintf("Ligne %d ignorée: référence produit vide\n", $processed);
        if (function_exists('ob_flush')) { ob_flush(); }
        flush();
        logProgress($processed, $totalRows, $rowsImported, $rowsSkipped);
        continue;
    }

    // Vérifier si le produit existe déjà
    $existingProduct = productExists($db, $ref);

    if ($existingProduct) {
        // Mise à jour du produit existant
        $product = new Product($db);
        if ($product->fetch($existingProduct) > 0) {
            $db->begin();

            // Mise à jour des champs
            foreach ($productFields as $field => $value) {
                // Ne pas modifier la référence
                if ($field === 'ref') {
                    continue;
                }
                // Ignorer les valeurs vides (mais pas zéro)
                if ($value === '' || $value === null) {
                    continue;
                }
                // Valider les clés étrangères
                if ($field === 'fk_unit' && !validateForeignKey($db, 'llx_c_units', 'rowid', $value)) {
                    continue; // Ignorer si l'unité n'existe pas
                }
                $product->$field = $value;
            }

            if (!empty($extraFields)) {
                $product->array_options = array_merge($product->array_options ?? [], $extraFields);
            }

            $result = $product->update($product->id, $usercli, 1, 'update');
            if ($result > 0) {
                // Gérer les catégories
                handleCategories($db, $product->id, $categoriesFamille, $categoriesSousFamille, $categoriesCache);

                $db->commit();
                $rowsUpdated++;
            } else {
                $db->rollback();
                $errorMsg = $product->error ?: 'Erreur inconnue';
                fwrite(STDERR, "Echec mise à jour produit (ligne $row, ref: $ref): $errorMsg\n");
                $rowsSkipped++;
            }
        }
    } else {
        // Création d'un nouveau produit
        $product = new Product($db);
        $product->ref = $ref;
        $product->label = $label ?: $ref;
        $product->status = 1; // En vente par défaut
        $product->status_buy = 1; // À l'achat par défaut

        foreach ($productFields as $field => $value) {
            // Ignorer les valeurs vides (mais pas zéro)
            if ($value === '' || $value === null) {
                continue;
            }
            // Valider les clés étrangères
            if ($field === 'fk_unit' && !validateForeignKey($db, 'llx_c_units', 'rowid', $value)) {
                continue; // Ignorer si l'unité n'existe pas
            }
            $product->$field = $value;
        }

        if (!empty($extraFields)) {
            $product->array_options = $extraFields;
        }

        $db->begin();
        $result = $product->create($usercli);

        if ($result > 0) {
            // Gérer les catégories
            handleCategories($db, $product->id, $categoriesFamille, $categoriesSousFamille, $categoriesCache);

            $db->commit();
            $rowsImported++;
        } else {
            $db->rollback();
            $errorMsg = $product->error ?: 'Erreur inconnue';
            if (!empty($product->errors)) {
                $errorMsg .= ' | ' . implode(' ; ', array_unique($product->errors));
            }
            $dbError = $db->lasterror();
            if ($dbError) {
                $errorMsg .= ' | DB: ' . $dbError;
            }
            fwrite(STDERR, "Echec création produit (ligne $row, ref: $ref): $errorMsg\n");
            $rowsSkipped++;
        }
    }

    logProgress($processed, $totalRows, $rowsImported + $rowsUpdated, $rowsSkipped);
}

if (!empty($unknownHeaders)) {
    echo 'Colonnes ignorées (non mappées) : ' . implode(', ', array_keys($unknownHeaders)) . "\n";
    if (function_exists('ob_flush')) { ob_flush(); }
    flush();
}

$duration = microtime(true) - $startTime;
echo sprintf("Import terminé en %.2f s. Produits créés: %d ; mis à jour: %d ; lignes ignorées: %d\n",
    $duration, $rowsImported, $rowsUpdated, $rowsSkipped);
if (function_exists('ob_flush')) { ob_flush(); }
flush();

/**
 * Normalise les libellés (casse et espaces) pour faire correspondre entêtes et mapping.
 */
function normalizeLabel($value): string
{
    $value = (string) $value;
    $value = str_replace(["\xC2\xA0", "\t", "\r", "\n"], ' ', $value);
    $value = str_replace(['_', '-'], ' ', $value);
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $value = mb_strtolower($value, 'UTF-8');
    } else {
        $value = strtolower($value);
    }

    return preg_replace('/\s+/', ' ', $value);
}

/**
 * Retourne l'ID du produit s'il existe déjà (par référence).
 */
function productExists(DoliDB $db, string $ref): ?int
{
    if ($ref === '') {
        return null;
    }

    $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . "product WHERE ref = '" . $db->escape($ref) . "' LIMIT 1";
    $resql = $db->query($sql);
    if (!$resql) {
        fwrite(STDERR, "Erreur SQL: " . $db->lasterror() . "\n");
        return null;
    }

    $obj = $db->fetch_object($resql);
    $db->free($resql);

    return $obj ? (int) $obj->rowid : null;
}

/**
 * Gère l'association des catégories (familles et sous-familles) à un produit.
 */
function handleCategories(DoliDB $db, int $productId, array $familles, array $sousFamilles, array &$cache): void
{
    global $usercli;

    // Traiter les familles (catégories parentes)
    foreach ($familles as $familleLabel) {
        $familleLabel = trim($familleLabel);
        if (empty($familleLabel)) {
            continue;
        }

        $catId = getOrCreateCategory($db, $familleLabel, 0, null, $cache, $usercli);
        if ($catId > 0) {
            associateProductToCategory($db, $productId, $catId);
        }
    }

    // Traiter les sous-familles
    foreach ($sousFamilles as $sousFamilleLabel) {
        $sousFamilleLabel = trim($sousFamilleLabel);
        if (empty($sousFamilleLabel)) {
            continue;
        }

        // Si on a une famille parente, l'utiliser
        $parentId = null;
        if (!empty($familles[0])) {
            $parentId = getOrCreateCategory($db, trim($familles[0]), 0, null, $cache, $usercli);
        }

        $catId = getOrCreateCategory($db, $sousFamilleLabel, 0, $parentId, $cache, $usercli);
        if ($catId > 0) {
            associateProductToCategory($db, $productId, $catId);
        }
    }
}

/**
 * Récupère ou crée une catégorie.
 */
function getOrCreateCategory(DoliDB $db, string $label, int $type, ?int $parentId, array &$cache, User $user): ?int
{
    $cacheKey = $label . '_' . $type . '_' . ($parentId ?? 0);

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    // Chercher la catégorie existante
    $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . "categorie WHERE label = '" . $db->escape($label) . "' AND type = " . $type;
    if ($parentId !== null) {
        $sql .= " AND fk_parent = " . (int) $parentId;
    }
    $sql .= " LIMIT 1";

    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $db->free($resql);
        if ($obj) {
            $cache[$cacheKey] = (int) $obj->rowid;
            return (int) $obj->rowid;
        }
    }

    // Créer la catégorie si elle n'existe pas
    $cat = new Categorie($db);
    $cat->label = $label;
    $cat->type = $type;
    if ($parentId !== null) {
        $cat->fk_parent = $parentId;
    }

    $result = $cat->create($user);
    if ($result > 0) {
        $cache[$cacheKey] = $cat->id;
        return $cat->id;
    }

    return null;
}

/**
 * Associe un produit à une catégorie.
 */
function associateProductToCategory(DoliDB $db, int $productId, int $categoryId): bool
{
    // Vérifier si l'association existe déjà
    $sql = 'SELECT COUNT(*) as nb FROM ' . MAIN_DB_PREFIX . "categorie_product WHERE fk_categorie = " . (int) $categoryId . " AND fk_product = " . (int) $productId;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $db->free($resql);
        if ($obj && $obj->nb > 0) {
            return true; // Déjà associé
        }
    }

    // Créer l'association
    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . (int) $categoryId . ", " . (int) $productId . ")";
    $resql = $db->query($sql);

    return (bool) $resql;
}

/**
 * Affiche la progression de l'import.
 */
function logProgress(int $processed, int $total, int $imported, int $skipped): void
{
    if ($total <= 0 || $processed <= 0) {
        return;
    }

    if (($processed % 50) !== 0 && $processed !== $total) {
        return;
    }

    echo sprintf("Progression : %d/%d (créés/màj : %d, ignorés : %d)\n", $processed, $total, $imported, $skipped);
    if (function_exists('ob_flush')) {
        ob_flush();
    }
    flush();
}

/**
 * Formatte la valeur lue dans la cellule Excel en chaîne manipulable.
 */
function formatCellValue($rawValue, ?\PhpOffice\PhpSpreadsheet\Cell\Cell $cell = null)
{
    if ($rawValue === null) {
        return '';
    }

    if ($cell && is_numeric($rawValue) && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawValue);
        return $date ? $date->format('Y-m-d H:i:s') : '';
    }

    if (is_float($rawValue)) {
        // Pour les champs numériques comme prix, poids, stock, on garde les décimales
        return (string) $rawValue;
    }

    if (is_bool($rawValue)) {
        return $rawValue ? '1' : '0';
    }

    return trim((string) $rawValue);
}

/**
 * Valide qu'une clé étrangère existe dans la table référencée.
 */
function validateForeignKey(DoliDB $db, string $table, string $field, $value): bool
{
    if ($value === '' || $value === null) {
        return false;
    }

    $sql = 'SELECT COUNT(*) as nb FROM ' . MAIN_DB_PREFIX . $table . " WHERE " . $field . " = '" . $db->escape($value) . "'";
    $resql = $db->query($sql);

    if (!$resql) {
        return false;
    }

    $obj = $db->fetch_object($resql);
    $db->free($resql);

    return $obj && $obj->nb > 0;
}
