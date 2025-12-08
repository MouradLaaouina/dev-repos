<?php
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

global $langs, $user, $conf;

$langs->loadLangs(array('admin', 'clientshadow@clientshadow'));

if (!$user->admin && empty($user->rights->clientshadow->manage)) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$socid = GETPOST('socid', 'int');

dol_include_once('/clientshadow/class/clientshadow.class.php');
$helper = new ClientShadow($db);
$form = new Form($db);

if ($action == 'set_default_type' && !GETPOST('cancel', 'alpha')) {
    $value = GETPOST('clientshadow_default_type', 'alpha');
    dolibarr_set_const($db, 'CLIENTSHADOW_DEFAULT_TYPE', $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'convert' && $socid > 0) {
    if (empty($user->rights->clientshadow->convert)) {
        setEventMessages($langs->trans('ClientShadowNoRights'), null, 'errors');
    } else {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $thirdparty = new Societe($db);
        if ($thirdparty->fetch($socid) > 0) {
            $helper->convertToTypeA($thirdparty, $user);
            setEventMessages($langs->trans('ClientShadowConverted'), null, 'mesgs');
        }
    }
}

llxHeader('', $langs->trans('ClientShadowOptions'));

print load_fiche_titre($langs->trans('ClientShadowOptions'));

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_default_type">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans('ClientShadowDefaultType').'</th><th>&nbsp;</th></tr>';
print '<tr class="oddeven">';
print '<td>';
print '<select name="clientshadow_default_type">';
$current = !empty($conf->global->CLIENTSHADOW_DEFAULT_TYPE) ? $conf->global->CLIENTSHADOW_DEFAULT_TYPE : 'A';
foreach (array('A', 'B') as $type) {
    print '<option value="'.$type.'"'.($current == $type ? ' selected' : '').'>'.$langs->trans('clientshadowType'.$type).'</option>';
}
print '</select>';
print '</td>';
print '<td><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></td>';
print '</tr>';
print '</table>';
print '</form>';

// Conversion form
print '<br>';
print load_fiche_titre($langs->trans('ClientShadowConversion'));
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="convert">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans("ThirdParty").'</th><th>&nbsp;</th></tr>';
print '<tr class="oddeven">';
print '<td>';
print $form->select_company($socid, 'socid', '', 1, 0, 0, array(), 0, 'minwidth300');
print '</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans("ClientShadowConvertButton").'"></td>';
print '</tr>';
print '</table>';
print '</form>';

llxFooter();

$db->close();
