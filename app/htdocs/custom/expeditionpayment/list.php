<?php
// Load Dolibarr environment (robust include)
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
dol_include_once('/expeditionpayment/class/expeditionpayment.class.php');

if (empty($user->rights->expeditionpayment->read)) {
    accessforbidden();
}

$langs->loadLangs(array('bills', 'companies', 'other'));

$form = new Form($db);
$formcompany = new FormCompany($db);

$socid = GETPOST('socid', 'int');
$limit = 50;

$sql = "SELECT ep.rowid, ep.ref, ep.amount, ep.datep, ep.status, ep.mode, ep.num_payment, ep.note, s.nom as socname";
$sql .= " FROM ".$db->prefix()."expedition_payment as ep";
$sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = ep.fk_soc";
$sql .= " WHERE ep.entity IN (".getEntity('expedition_payment').")";
if ($socid > 0) {
    $sql .= " AND ep.fk_soc = ".((int) $socid);
}
$sql .= " ORDER BY ep.datep DESC";
$sql .= $db->plimit($limit, 0);

$resql = $db->query($sql);
$rows = array();
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $rows[] = $obj;
    }
}

$title = $langs->trans('Payments');
llxHeader('', $title);

print load_fiche_titre($langs->trans("ExpeditionPaymentMenu"), '', 'payment');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="noborder">';
print '<tr class="liste_titre"><th>'.$langs->trans("Customer").'</th><th class="center">'.$langs->trans("Search").'</th></tr>';
print '<tr class="oddeven">';
print '<td>'.$formcompany->select_company($socid, 'socid', 's.fournisseur = 0', 1, 0, 0, array(), 0, 0, 0, 'minwidth200').'</td>';
print '<td class="center"><input type="submit" class="button" value="'.$langs->trans("Refresh").'"></td>';
print '</tr>';
print '</table>';
print '</form>';

print '<br>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Ref").'</th>';
print '<th>'.$langs->trans("Date").'</th>';
print '<th>'.$langs->trans("Customer").'</th>';
print '<th class="right">'.$langs->trans("Amount").'</th>';
print '<th>'.$langs->trans("Status").'</th>';
print '</tr>';
if (empty($rows)) {
    print '<tr class="oddeven"><td colspan="5">'.$langs->trans("None").'</td></tr>';
} else {
    foreach ($rows as $obj) {
        $url = dol_buildpath('/expeditionpayment/card.php?id='.(int) $obj->rowid, 1);
        $status = $obj->status == 9 ? $langs->trans("Canceled") : $langs->trans("Validated");
        print '<tr class="oddeven">';
        print '<td><a href="'.$url.'">'.dol_escape_htmltag($obj->ref).'</a></td>';
        print '<td>'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';
        print '<td>'.dol_escape_htmltag($obj->socname).'</td>';
        print '<td class="right">'.price($obj->amount).'</td>';
        print '<td>'.$status.'</td>';
        print '</tr>';
    }
}
print '</table>';
print '</div>';

llxFooter();
$db->close();
