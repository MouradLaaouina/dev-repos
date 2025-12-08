<?php
/**
 * Order Payment List
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/orderpayment/class/orderpayment.class.php');

// Load translation files
$langs->loadLangs(array('bills', 'companies', 'orders', 'orderpayment@orderpayment'));

// Security check
if (!$user->hasRight('orderpayment', 'read')) {
    accessforbidden();
}

// Get parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'orderpaymentlist';

// Search parameters
$search_ref = GETPOST('search_ref', 'alpha');
$search_socid = GETPOST('search_socid', 'int');
$search_status = GETPOST('search_status', 'int');
$search_date_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_date_end = dol_mktime(23, 59, 59, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));

// Sorting
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (!$sortfield) {
    $sortfield = 'op.datep';
}
if (!$sortorder) {
    $sortorder = 'DESC';
}

// Pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');
if (empty($page) || $page < 0) {
    $page = 0;
}
$offset = $limit * $page;

// Initialize objects
$form = new Form($db);
$formcompany = new FormCompany($db);
$object = new OrderPayment($db);

// Build SQL query
$sql = "SELECT op.rowid, op.ref, op.fk_soc, op.amount, op.datep, op.status,";
$sql .= " op.fk_mode_reglement, op.num_payment, op.datec,";
$sql .= " s.rowid as socid, s.nom as socname, s.email, s.client";
$sql .= " FROM ".$db->prefix()."order_payment as op";
$sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = op.fk_soc";
$sql .= " WHERE op.entity IN (".getEntity('commande').")";

// Search filters
if ($search_ref) {
    $sql .= natural_search('op.ref', $search_ref);
}
if ($search_socid > 0) {
    $sql .= " AND op.fk_soc = ".((int) $search_socid);
}
if ($search_status !== '' && $search_status >= 0) {
    $sql .= " AND op.status = ".((int) $search_status);
}
if ($search_date_start) {
    $sql .= " AND op.datep >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
    $sql .= " AND op.datep <= '".$db->idate($search_date_end)."'";
}

// Count total
$sqlcount = preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) as total FROM', $sql);
$sqlcount = preg_replace('/LEFT JOIN.*WHERE/', 'WHERE', $sqlcount);
$resqlcount = $db->query($sqlcount);
$nbtotalofrecords = 0;
if ($resqlcount) {
    $objcount = $db->fetch_object($resqlcount);
    $nbtotalofrecords = $objcount->total;
}

// Add order by
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

// Execute query
$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Actions
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
    $search_ref = '';
    $search_socid = '';
    $search_status = '';
    $search_date_start = '';
    $search_date_end = '';
    $toselect = array();
}

/*
 * View
 */

$title = $langs->trans('OrderPaymentList');
$help_url = '';

llxHeader('', $title, $help_url);

// New button
$newcardbutton = '';
if ($user->hasRight('orderpayment', 'write')) {
    $newcardbutton = dolGetButtonTitle($langs->trans('NewOrderPayment'), '', 'fa fa-plus-circle', dol_buildpath('/orderpayment/card.php?action=create', 1));
}

print load_fiche_titre($langs->trans("OrderPaymentList"), $newcardbutton, 'payment');

// Search form
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="formulaire">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Header row
print '<tr class="liste_titre_filter">';

// Ref
print '<td class="liste_titre">';
print '<input type="text" class="flat maxwidth75" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';

// Date
print '<td class="liste_titre center">';
print '<div class="nowrap">';
print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
print '</div>';
print '<div class="nowrap">';
print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
print '</div>';
print '</td>';

// Thirdparty
print '<td class="liste_titre">';
print $form->select_company($search_socid, 'search_socid', '', 'SelectThirdParty', 1, 0, array(), 0, 'minwidth200 maxwidth300');
print '</td>';

// Payment mode
print '<td class="liste_titre"></td>';

// Amount
print '<td class="liste_titre right"></td>';

// Status
print '<td class="liste_titre center">';
$arrayofstatus = array(
    '' => '',
    OrderPayment::STATUS_VALIDATED => $langs->trans('OrderPaymentValidated'),
    OrderPayment::STATUS_CANCELLED => $langs->trans('OrderPaymentCancelled')
);
print $form->selectarray('search_status', $arrayofstatus, $search_status, 0, 0, 0, '', 1);
print '</td>';

// Actions
print '<td class="liste_titre center maxwidthsearch">';
print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("RemoveFilter"), 'searchclear.png', '', '', 1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';

print '</tr>';

// Column headers
print '<tr class="liste_titre">';
print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'op.ref', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('Date', $_SERVER['PHP_SELF'], 'op.datep', '', '', 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('ThirdParty', $_SERVER['PHP_SELF'], 's.nom', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('PaymentMode', $_SERVER['PHP_SELF'], 'op.fk_mode_reglement', '', '', '', $sortfield, $sortorder);
print_liste_field_titre('Amount', $_SERVER['PHP_SELF'], 'op.amount', '', '', 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('Status', $_SERVER['PHP_SELF'], 'op.status', '', '', 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('', $_SERVER['PHP_SELF'], '', '', '', 'class="center"', $sortfield, $sortorder);
print '</tr>';

// Data rows
if ($num > 0) {
    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $objectstatic = new OrderPayment($db);
            $objectstatic->id = $obj->rowid;
            $objectstatic->ref = $obj->ref;
            $objectstatic->status = $obj->status;

            $thirdpartystatic = new Societe($db);
            $thirdpartystatic->id = $obj->socid;
            $thirdpartystatic->name = $obj->socname;
            $thirdpartystatic->client = $obj->client;

            print '<tr class="oddeven">';

            // Ref
            print '<td class="nowraponall">';
            print $objectstatic->getNomUrl(1);
            print '</td>';

            // Date
            print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';

            // Thirdparty
            print '<td class="tdoverflowmax200">';
            if ($obj->socid > 0) {
                print $thirdpartystatic->getNomUrl(1, 'customer');
            }
            print '</td>';

            // Payment mode
            print '<td>';
            if ($obj->fk_mode_reglement > 0) {
                $sqlmode = "SELECT code, libelle FROM ".$db->prefix()."c_paiement WHERE id = ".((int) $obj->fk_mode_reglement);
                $resmode = $db->query($sqlmode);
                if ($resmode && $db->num_rows($resmode)) {
                    $objmode = $db->fetch_object($resmode);
                    $paymentmode = $langs->trans("PaymentType".$objmode->code) != "PaymentType".$objmode->code ? $langs->trans("PaymentType".$objmode->code) : $objmode->libelle;
                    print dol_escape_htmltag($paymentmode);
                }
            }
            print '</td>';

            // Amount
            print '<td class="right nowraponall">'.price($obj->amount, 0, $langs, 1, -1, -1, $conf->currency).'</td>';

            // Status
            print '<td class="center">'.$objectstatic->getLibStatut(5).'</td>';

            // Actions
            print '<td class="center nowraponall">';
            if ($user->hasRight('orderpayment', 'write')) {
                print '<a class="editfielda" href="'.dol_buildpath('/orderpayment/card.php', 1).'?id='.$obj->rowid.'">'.img_picto($langs->trans("View"), 'eye').'</a>';
            }
            print '</td>';

            print '</tr>';
        }
        $i++;
    }
} else {
    print '<tr class="oddeven"><td colspan="7" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print '</table>';
print '</div>';

// Navigation
print_barre_liste('', $page, $_SERVER['PHP_SELF'], '', $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit);

print '</form>';

$db->free($resql);

llxFooter();
$db->close();
