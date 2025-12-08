<?php
/**
 * PDF TTC Only - Setup page
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

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

if ($action == 'update') {
    dolibarr_set_const($db, 'PDFTTCONLY_ENABLED_PROPAL', GETPOST('PDFTTCONLY_ENABLED_PROPAL', 'int'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PDFTTCONLY_ENABLED_ORDER', GETPOST('PDFTTCONLY_ENABLED_ORDER', 'int'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PDFTTCONLY_ENABLED_SHIPMENT', GETPOST('PDFTTCONLY_ENABLED_SHIPMENT', 'int'), 'chaine', 0, '', $conf->entity);

    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

/*
 * View
 */

$page_name = "PdfTtcOnlySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

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

print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, 'pdf');

// Info box
print '<div class="info">';
print $langs->trans("PdfTtcOnlyInfo");
print '</div>';
print '<br>';

// How to use
print '<div class="opacitymedium">';
print '<strong>'.$langs->trans("HowToUse").':</strong><br>';
print $langs->trans("HowToUseInfo").'<br>';
print '<ul>';
print '<li>'.$langs->trans("ModelProposal").'</li>';
print '<li>'.$langs->trans("ModelOrder").'</li>';
print '<li>'.$langs->trans("ModelShipment").'</li>';
print '</ul>';
print '</div>';
print '<br>';

// Setup form
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="center">'.$langs->trans("Value").'</td>';
print '</tr>';

// Enable for proposals
print '<tr class="oddeven">';
print '<td>'.$langs->trans("EnableForProposal").'</td>';
print '<td class="center">';
print $form->selectyesno('PDFTTCONLY_ENABLED_PROPAL', getDolGlobalString('PDFTTCONLY_ENABLED_PROPAL', '1'), 1);
print '</td>';
print '</tr>';

// Enable for orders
print '<tr class="oddeven">';
print '<td>'.$langs->trans("EnableForOrder").'</td>';
print '<td class="center">';
print $form->selectyesno('PDFTTCONLY_ENABLED_ORDER', getDolGlobalString('PDFTTCONLY_ENABLED_ORDER', '1'), 1);
print '</td>';
print '</tr>';

// Enable for shipments
print '<tr class="oddeven">';
print '<td>'.$langs->trans("EnableForShipment").'</td>';
print '<td class="center">';
print $form->selectyesno('PDFTTCONLY_ENABLED_SHIPMENT', getDolGlobalString('PDFTTCONLY_ENABLED_SHIPMENT', '1'), 1);
print '</td>';
print '</tr>';

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

// Links to configure PDF models
print '<br>';
print '<div class="info">';
print '<strong>'.$langs->trans("DirectLinks").':</strong><br>';
print '<ul>';
print '<li><a href="'.DOL_URL_ROOT.'/admin/propal.php">'.$langs->trans("ProposalsSetup").'</a></li>';
print '<li><a href="'.DOL_URL_ROOT.'/admin/commande.php">'.$langs->trans("OrdersSetup").'</a></li>';
print '<li><a href="'.DOL_URL_ROOT.'/admin/expedition.php">'.$langs->trans("SendingsSetup").'</a></li>';
print '</ul>';
print '</div>';

llxFooter();
$db->close();
