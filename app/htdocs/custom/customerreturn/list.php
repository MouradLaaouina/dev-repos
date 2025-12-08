<?php
/**
 * Customer Returns List
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/customerreturn/class/customerreturn.class.php');

// Load translations
$langs->loadLangs(array('customerreturn@customerreturn', 'products', 'companies'));

// Security check
$result = restrictedArea($user, 'customerreturn');

// Get parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'customerreturnlist';

$search_ref = GETPOST('search_ref', 'alpha');
$search_socid = GETPOSTINT('search_socid');
$search_deliveryman = GETPOSTINT('search_deliveryman');
$search_status = GETPOST('search_status', 'intcomma');
$search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
$search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0) {
    $page = 0;
}
$offset = $limit * $page;

if (!$sortfield) {
    $sortfield = 'c.datec';
}
if (!$sortorder) {
    $sortorder = 'DESC';
}

// Initialize objects
$form = new Form($db);
$formcompany = new FormCompany($db);
$object = new CustomerReturn($db);

$hookmanager->initHooks(array('customerreturnlist'));

// Permissions
$permissiontoread = $user->hasRight('customerreturn', 'read');
$permissiontoadd = $user->hasRight('customerreturn', 'write');
$permissiontodelete = $user->hasRight('customerreturn', 'delete');


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
    $search_ref = '';
    $search_socid = 0;
    $search_deliveryman = 0;
    $search_status = '';
    $search_date_start = '';
    $search_date_end = '';
    $toselect = array();
}


/*
 * View
 */

$title = $langs->trans('CustomerReturnsList');
llxHeader('', $title);

// Build SQL query
$sql = "SELECT c.rowid, c.ref, c.fk_soc, c.fk_deliveryman, c.datec, c.date_return,";
$sql .= " c.total_ht, c.total_ttc, c.fk_statut,";
$sql .= " s.nom as socname, s.client,";
$sql .= " u.lastname as deliveryman_lastname, u.firstname as deliveryman_firstname";
$sql .= " FROM ".$db->prefix()."customerreturn as c";
$sql .= " LEFT JOIN ".$db->prefix()."societe as s ON c.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".$db->prefix()."user as u ON c.fk_deliveryman = u.rowid";
$sql .= " WHERE c.entity = ".((int) $conf->entity);

if ($search_ref) {
    $sql .= natural_search('c.ref', $search_ref);
}
if ($search_socid > 0) {
    $sql .= " AND c.fk_soc = ".((int) $search_socid);
}
if ($search_deliveryman > 0) {
    $sql .= " AND c.fk_deliveryman = ".((int) $search_deliveryman);
}
if ($search_status !== '' && $search_status >= 0) {
    $sql .= " AND c.fk_statut = ".((int) $search_status);
}
if ($search_date_start) {
    $sql .= " AND c.date_return >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
    $sql .= " AND c.date_return <= '".$db->idate($search_date_end)."'";
}

// Count total
$sqlcount = preg_replace('/SELECT .*? FROM/', 'SELECT COUNT(*) as nb FROM', $sql);
$sqlcount = preg_replace('/LEFT JOIN.*?WHERE/', ' WHERE', $sqlcount);
$resqlcount = $db->query($sqlcount);
$nbtotalofrecords = 0;
if ($resqlcount) {
    $objcount = $db->fetch_object($resqlcount);
    $nbtotalofrecords = $objcount->nb;
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Build array of results
$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if ($search_ref) {
    $param .= '&search_ref='.urlencode($search_ref);
}
if ($search_socid > 0) {
    $param .= '&search_socid='.$search_socid;
}
if ($search_deliveryman > 0) {
    $param .= '&search_deliveryman='.$search_deliveryman;
}
if ($search_status !== '' && $search_status >= 0) {
    $param .= '&search_status='.$search_status;
}

// List header
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="formfilter">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton = '';
if ($permissiontoadd) {
    $newcardbutton = '<a class="butActionNew" href="'.dol_buildpath('/custom/customerreturn/card.php', 1).'?action=create"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span> '.$langs->trans('NewCustomerReturn').'</a>';
}

print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'dollyrevert', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Search fields
print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($contextpage ? ' liste'.$contextpage : '').'">';

// Header
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_ref" value="'.$search_ref.'"></td>';
print '<td class="liste_titre">';
print $form->select_company($search_socid, 'search_socid', '', 'SelectThirdParty', 1, 0, null, 0, 'minwidth200');
print '</td>';
print '<td class="liste_titre">';
print $form->select_dolusers($search_deliveryman, 'search_deliveryman', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'minwidth150');
print '</td>';
print '<td class="liste_titre center">';
print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, '', 1, 0);
print ' - ';
print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, '', 1, 0);
print '</td>';
print '<td class="liste_titre right"></td>';
print '<td class="liste_titre right"></td>';
print '<td class="liste_titre center">';
$arrayofstatus = array(
    '' => '',
    '0' => $langs->trans('Draft'),
    '1' => $langs->trans('Validated'),
    '2' => $langs->trans('Closed'),
    '9' => $langs->trans('Canceled')
);
print $form->selectarray('search_status', $arrayofstatus, $search_status, 0, 0, 0, '', 1);
print '</td>';
print '<td class="liste_titre center maxwidthsearch">';
print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto('', 'search.png', '', 0, 1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto('', 'searchclear.png', '', 0, 1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';
print '</tr>';

// Column titles
print '<tr class="liste_titre">';
print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'c.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Customer', $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Deliveryman', $_SERVER['PHP_SELF'], 'u.lastname', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('DateReturn', $_SERVER['PHP_SELF'], 'c.date_return', '', $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre('TotalHT', $_SERVER['PHP_SELF'], 'c.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('TotalTTC', $_SERVER['PHP_SELF'], 'c.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('Status', $_SERVER['PHP_SELF'], 'c.fk_statut', '', $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre('', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center maxwidthsearch ');
print '</tr>';

// List rows
$i = 0;
$totalht = 0;
$totalttc = 0;

while ($i < min($num, $limit)) {
    $obj = $db->fetch_object($resql);

    $customerreturn = new CustomerReturn($db);
    $customerreturn->id = $obj->rowid;
    $customerreturn->ref = $obj->ref;
    $customerreturn->fk_soc = $obj->fk_soc;
    $customerreturn->fk_statut = $obj->fk_statut;
    $customerreturn->statut = $obj->fk_statut;
    $customerreturn->status = $obj->fk_statut;

    print '<tr class="oddeven">';

    // Ref
    print '<td class="nowraponall">';
    print $customerreturn->getNomUrl(1);
    print '</td>';

    // Customer
    print '<td>';
    $societe = new Societe($db);
    $societe->id = $obj->fk_soc;
    $societe->name = $obj->socname;
    $societe->client = $obj->client;
    print $societe->getNomUrl(1, 'customer');
    print '</td>';

    // Deliveryman
    print '<td>';
    if ($obj->fk_deliveryman > 0) {
        $deliveryman = new User($db);
        $deliveryman->id = $obj->fk_deliveryman;
        $deliveryman->lastname = $obj->deliveryman_lastname;
        $deliveryman->firstname = $obj->deliveryman_firstname;
        print $deliveryman->getNomUrl(1);
    }
    print '</td>';

    // Date return
    print '<td class="center">'.dol_print_date($db->jdate($obj->date_return), 'day').'</td>';

    // Total HT
    print '<td class="right">'.price($obj->total_ht).'</td>';
    $totalht += $obj->total_ht;

    // Total TTC
    print '<td class="right">'.price($obj->total_ttc).'</td>';
    $totalttc += $obj->total_ttc;

    // Status
    print '<td class="center">'.$customerreturn->getLibStatut(5).'</td>';

    // Action column
    print '<td class="center"></td>';

    print '</tr>';

    $i++;
}

// Totals
if ($num > 0) {
    print '<tr class="liste_total">';
    print '<td>'.$langs->trans('Total').'</td>';
    print '<td></td>';
    print '<td></td>';
    print '<td></td>';
    print '<td class="right">'.price($totalht).'</td>';
    print '<td class="right">'.price($totalttc).'</td>';
    print '<td></td>';
    print '<td></td>';
    print '</tr>';
}

// No results
if ($num == 0) {
    print '<tr><td colspan="8" class="opacitymedium">'.$langs->trans('NoRecordFound').'</td></tr>';
}

print '</table>';
print '</div>';
print '</form>';

$db->free($resql);

llxFooter();
$db->close();
