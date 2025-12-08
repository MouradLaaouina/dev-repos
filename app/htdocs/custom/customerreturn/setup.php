<?php
/**
 * Customer Return Setup Page
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translations
$langs->loadLangs(array('admin', 'customerreturn@customerreturn'));

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'update') {
    $error = 0;

    $auto_credit = GETPOST('CUSTOMERRETURN_AUTO_CREDIT_NOTE', 'alpha');

    if (!$error) {
        $res = dolibarr_set_const($db, 'CUSTOMERRETURN_AUTO_CREDIT_NOTE', $auto_credit, 'chaine', 0, '', $conf->entity);
        if ($res <= 0) {
            $error++;
        }
    }

    if (!$error) {
        setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    } else {
        setEventMessages($langs->trans('Error'), null, 'errors');
    }
}


/*
 * View
 */

$page_name = 'CustomerreturnSetup';
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = array();
$head[0][0] = $_SERVER['PHP_SELF'];
$head[0][1] = $langs->trans('Settings');
$head[0][2] = 'settings';

print dol_get_fiche_head($head, 'settings', $langs->trans('CustomerReturn'), -1, 'dollyrevert');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Parameter').'</td>';
print '<td class="center" width="150">'.$langs->trans('Value').'</td>';
print '</tr>';

// Auto credit note
print '<tr class="oddeven">';
print '<td>'.$langs->trans('CUSTOMERRETURN_AUTO_CREDIT_NOTE').'</td>';
print '<td class="center">';
print $form->selectyesno('CUSTOMERRETURN_AUTO_CREDIT_NOTE', getDolGlobalString('CUSTOMERRETURN_AUTO_CREDIT_NOTE'), 1);
print '</td>';
print '</tr>';

print '</table>';

print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans('Save').'"></div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
