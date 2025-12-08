<?php
/**
 * PDF TTC Only - About page
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files
$langs->loadLangs(array('admin', 'pdfttconly@pdfttconly'));

// Security check
if (!$user->admin) {
    accessforbidden();
}

/*
 * View
 */

$page_name = "PdfTtcOnlyAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PdfTtcOnlySetup"), $linkback, 'title_setup');

// Configuration header
$head = array();
$h = 0;
$head[$h][0] = dol_buildpath('/pdfttconly/setup.php', 1);
$head[$h][1] = $langs->trans("Settings");
$head[$h][2] = 'settings';
$h++;
$head[$h][0] = dol_buildpath('/pdfttconly/about.php', 1);
$head[$h][1] = $langs->trans("About");
$head[$h][2] = 'about';
$h++;

print dol_get_fiche_head($head, 'about', $langs->trans("PdfTtcOnlySetup"), -1, 'pdf');

print '<div class="fichecenter">';

print '<table class="border centpercent tableforfield">';

print '<tr><td class="titlefield">'.$langs->trans("ModuleName").'</td>';
print '<td>PDF TTC Only</td></tr>';

print '<tr><td>'.$langs->trans("Version").'</td>';
print '<td>1.0.0</td></tr>';

print '<tr><td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("PdfTtcOnlyDesc").'</td></tr>';

print '<tr><td>'.$langs->trans("Author").'</td>';
print '<td>Custom Development</td></tr>';

print '</table>';

print '</div>';

print '<br>';

print '<div class="fichecenter">';
print '<div class="info">';
print '<strong>Modèles PDF disponibles :</strong><br><br>';
print '<ul>';
print '<li><strong>azur_ttc</strong> - Pour les devis (basé sur Azur)</li>';
print '<li><strong>einstein_ttc</strong> - Pour les commandes (basé sur Einstein)</li>';
print '<li><strong>espadon_ttc</strong> - Pour les expéditions/BL (basé sur Espadon)</li>';
print '</ul>';
print '</div>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
