<?php
/* Sellout listing and summary */

// Load Dolibarr environment (robust include for custom modules)
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

// Permissions
if (empty($user->rights->sellout->read)) {
    accessforbidden();
}

$langs->loadLangs(array('companies', 'products'));

$form = new Form($db);
$formcompany = new FormCompany($db);

$socid = GETPOST('socid', 'int');
$salesrepId = GETPOST('salesrep_id', 'int');

$limit = 200;

// Fetch detailed sales
$sql = "SELECT so.rowid, so.sale_date, so.qty, so.unit_price, so.currency_code, so.note,";
$sql .= " so.fk_soc, so.fk_product, so.fk_user,";
$sql .= " s.nom as socname, s.code_client,";
$sql .= " p.ref as pref, p.label as plabel,";
$sql .= " u.firstname, u.lastname, u.login";
$sql .= " FROM ".$db->prefix()."sellout_sale as so";
$sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = so.fk_soc";
$sql .= " LEFT JOIN ".$db->prefix()."product as p ON p.rowid = so.fk_product";
$sql .= " LEFT JOIN ".$db->prefix()."user as u ON u.rowid = so.fk_user";
$sql .= " WHERE so.entity IN (".getEntity('sellout_sale').")";
if ($socid > 0) {
    $sql .= " AND so.fk_soc = ".((int) $socid);
}
if ($salesrepId > 0) {
    $sql .= " AND so.fk_user = ".((int) $salesrepId);
}
$sql .= " ORDER BY so.sale_date DESC, so.rowid DESC";
$sql .= $db->plimit($limit, 0);

$resql = $db->query($sql);
$rows = array();
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $rows[] = $obj;
    }
}

// Summary per customer/product
$sumSql = "SELECT so.fk_soc, s.nom as socname, so.fk_product, p.ref as pref, p.label as plabel,";
$sumSql .= " SUM(so.qty) as total_qty, SUM(so.qty * so.unit_price) as total_amount,";
$sumSql .= " MIN(so.sale_date) as first_date, MAX(so.sale_date) as last_date";
$sumSql .= " FROM ".$db->prefix()."sellout_sale as so";
$sumSql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = so.fk_soc";
$sumSql .= " LEFT JOIN ".$db->prefix()."product as p ON p.rowid = so.fk_product";
$sumSql .= " WHERE so.entity IN (".getEntity('sellout_sale').")";
if ($socid > 0) {
    $sumSql .= " AND so.fk_soc = ".((int) $socid);
}
if ($salesrepId > 0) {
    $sumSql .= " AND so.fk_user = ".((int) $salesrepId);
}
$sumSql .= " GROUP BY so.fk_soc, s.nom, so.fk_product, p.ref, p.label";
$sumSql .= " ORDER BY s.nom, p.ref";
$sumSql .= $db->plimit($limit, 0);

$resSum = $db->query($sumSql);
$summary = array();
if ($resSum) {
    while ($obj = $db->fetch_object($resSum)) {
        $summary[] = $obj;
    }
}

$help_url = '';
$title = 'Sellout';

llxHeader('', $title, $help_url);

print load_fiche_titre($title, '', 'generic');

// Filters
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<div class="fichecenter">';
print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Customer").'</th>';
print '<th>'.$langs->trans("SalesRepresentative").'</th>';
print '<th class="center">'.$langs->trans("Search").'</th>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>'.$formcompany->select_company($socid, 'socid', 's.fournisseur = 0', 1, 0, 0, array(), 0, 0, 0, 'minwidth200').'</td>';
print '<td>'.$form->select_dolusers($salesrepId, 'salesrep_id', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200').'</td>';
print '<td class="center"><input type="submit" class="button" value="'.$langs->trans("Refresh").'"></td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<h3>'.$langs->trans("List").' ('.$limit.')</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Date").'</th>';
print '<th>'.$langs->trans("Customer").'</th>';
print '<th>'.$langs->trans("SalesRepresentative").'</th>';
print '<th>'.$langs->trans("Product").'</th>';
print '<th class="right">'.$langs->trans("Qty").'</th>';
print '<th class="right">'.$langs->trans("UnitPriceHT").'</th>';
print '</tr>';
if (empty($rows)) {
    print '<tr class="oddeven"><td colspan="6">'.$langs->trans("None").'</td></tr>';
} else {
    foreach ($rows as $obj) {
        $customer = $obj->socname ? $obj->socname : $obj->fk_soc;
        $prod = $obj->pref ? $obj->pref.' - '.$obj->plabel : $obj->fk_product;
        $sales = trim($obj->firstname.' '.$obj->lastname);
        if (empty($sales)) {
            $sales = $obj->login;
        }
        print '<tr class="oddeven">';
        print '<td>'.dol_print_date($db->jdate($obj->sale_date), 'dayhour').'</td>';
        print '<td>'.$customer.'</td>';
        print '<td>'.$sales.'</td>';
        print '<td>'.$prod.'</td>';
        print '<td class="right">'.price($obj->qty, 0, $langs, 0, 0, 0, '').'</td>';
        print '<td class="right">'.price($obj->unit_price, 0, $langs, 0, 0, 0, $obj->currency_code).'</td>';
        print '</tr>';
    }
}
print '</table>';
print '</div>';

print '<div class="fichehalfright">';
print '<h3>'.$langs->trans("Summary").'</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Customer").'</th>';
print '<th>'.$langs->trans("Product").'</th>';
print '<th class="right">'.$langs->trans("Qty").'</th>';
print '<th class="right">'.$langs->trans("AmountHT").'</th>';
print '<th>'.$langs->trans("DateStart").'</th>';
print '<th>'.$langs->trans("DateEnd").'</th>';
print '</tr>';
if (empty($summary)) {
    print '<tr class="oddeven"><td colspan="6">'.$langs->trans("None").'</td></tr>';
} else {
    foreach ($summary as $obj) {
        $customer = $obj->socname ? $obj->socname : $obj->fk_soc;
        $prod = $obj->pref ? $obj->pref.' - '.$obj->plabel : $obj->fk_product;
        print '<tr class="oddeven">';
        print '<td>'.$customer.'</td>';
        print '<td>'.$prod.'</td>';
        print '<td class="right">'.price($obj->total_qty, 0, $langs, 0, 0, 0, '').'</td>';
        print '<td class="right">'.price($obj->total_amount, 0, $langs, 0, 0, 0, '').'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->first_date), 'day').'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->last_date), 'day').'</td>';
        print '</tr>';
    }
}
print '</table>';
print '</div>';
print '</div>';

llxFooter();
$db->close();
