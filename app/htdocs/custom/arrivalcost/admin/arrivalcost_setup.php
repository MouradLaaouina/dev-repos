<?php
// phpcs:ignorefile
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

dol_include_once('/arrivalcost/class/arrivalcost.class.php');

$langs->loadLangs(array('admin', 'arrivalcost@arrivalcost'));

if (!$user->admin && empty($user->rights->arrivalcost->write)) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'alpha');

$form = new Form($db);
$helper = new ArrivalCost($db);

if ($action === 'setmode') {
    if ($mode !== 'amount' && $mode !== 'qty') {
        $mode = 'amount';
    }
    dolibarr_set_const($db, 'ARRIVALCOST_DEFAULT_MODE', $mode, 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
}

if ($action === 'rebuild_extrafields') {
    $helper->ensureLineExtraFields();
    setEventMessages($langs->trans('ArrivalCostExtrafieldsRebuilt'), null, 'mesgs');
}

$currentMode = getDolGlobalString('ARRIVALCOST_DEFAULT_MODE', 'amount');

$help_url = '';
$page_name = 'ArrivalCostSetup';

llxHeader('', $langs->trans($page_name), $help_url);

print load_fiche_titre($langs->trans($page_name), '', 'generic');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setmode">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans('Parameter').'</th><th>'.$langs->trans('Value').'</th></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans('ArrivalCostDefaultMode').'</td>';
print '<td>';
print '<select name="mode" class="flat minwidth200">';
print '<option value="amount"'.($currentMode === 'amount' ? ' selected' : '').'>'.$langs->trans('ArrivalCostModeAmount').'</option>';
print '<option value="qty"'.($currentMode === 'qty' ? ' selected' : '').'>'.$langs->trans('ArrivalCostModeQty').'</option>';
print '</select> ';
print '<input type="submit" class="button small" value="'.$langs->trans('Save').'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="rebuild_extrafields">';
print '<div class="opacitymedium">'.$langs->trans('ArrivalCostRebuildHelp').'</div>';
print '<input type="submit" class="button" value="'.$langs->trans('ArrivalCostRebuildExtrafields').'">';
print '</form>';

llxFooter();
$db->close();
