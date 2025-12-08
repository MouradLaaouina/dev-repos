<?php
/**
 * List of delivery assignments
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/deliverymen/class/deliverymenshipment.class.php');

$langs->loadLangs(array('deliverymen@deliverymen', 'sendings', 'companies'));

// Security check
if (!isModEnabled('deliverymen')) {
    accessforbidden('Module not enabled');
}
if (!$user->hasRight('deliverymen', 'read')) {
    accessforbidden('Not enough permissions');
}

// Get parameters
$action = GETPOST('action', 'aZ09');
$search_deliveryman = GETPOSTINT('search_deliveryman');
$search_status = GETPOST('search_status', 'alpha');
$search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
$search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));
$search_ref = GETPOST('search_ref', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0) $page = 0;
$offset = $limit * $page;
if (!$sortfield) $sortfield = 'ds.delivery_date';
if (!$sortorder) $sortorder = 'DESC';

$form = new Form($db);

/*
 * View
 */

$title = $langs->trans('DeliveryAssignmentsList');
llxHeader('', $title);

// Build SQL query
$sql = "SELECT ds.rowid, ds.fk_expedition, ds.fk_user_deliveryman, ds.delivery_date,";
$sql .= " ds.delivery_status, ds.date_assignment, ds.date_delivery_done,";
$sql .= " e.ref as expedition_ref, e.fk_soc,";
$sql .= " s.nom as thirdparty_name,";
$sql .= " u.lastname, u.firstname, u.login";
$sql .= " FROM ".$db->prefix()."deliverymen_shipment as ds";
$sql .= " LEFT JOIN ".$db->prefix()."expedition as e ON e.rowid = ds.fk_expedition";
$sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = e.fk_soc";
$sql .= " LEFT JOIN ".$db->prefix()."user as u ON u.rowid = ds.fk_user_deliveryman";
$sql .= " WHERE ds.entity = ".((int) $conf->entity);

if ($search_deliveryman > 0) {
    $sql .= " AND ds.fk_user_deliveryman = ".((int) $search_deliveryman);
}
if ($search_status) {
    $sql .= " AND ds.delivery_status = '".$db->escape($search_status)."'";
}
if ($search_date_start) {
    $sql .= " AND ds.delivery_date >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
    $sql .= " AND ds.delivery_date <= '".$db->idate($search_date_end)."'";
}
if ($search_ref) {
    $sql .= natural_search('e.ref', $search_ref);
}

// Count total
$sqlcount = preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) as total FROM', $sql);
$resqlcount = $db->query($sqlcount);
$totalofrecords = 0;
if ($resqlcount) {
    $objcount = $db->fetch_object($resqlcount);
    $totalofrecords = $objcount->total;
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Build params for links
$param = '';
if ($search_deliveryman > 0) $param .= '&search_deliveryman='.$search_deliveryman;
if ($search_status) $param .= '&search_status='.urlencode($search_status);
if ($search_ref) $param .= '&search_ref='.urlencode($search_ref);
if ($search_date_start) {
    $param .= '&search_date_startday='.GETPOSTINT('search_date_startday');
    $param .= '&search_date_startmonth='.GETPOSTINT('search_date_startmonth');
    $param .= '&search_date_startyear='.GETPOSTINT('search_date_startyear');
}
if ($search_date_end) {
    $param .= '&search_date_endday='.GETPOSTINT('search_date_endday');
    $param .= '&search_date_endmonth='.GETPOSTINT('search_date_endmonth');
    $param .= '&search_date_endyear='.GETPOSTINT('search_date_endyear');
}

// Buttons
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('DailyReport'), '', 'fa fa-calendar', dol_buildpath('/deliverymen/daily_report.php', 1), '', $user->hasRight('deliverymen', 'read'));

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="formlist">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $totalofrecords, 'dolly', 0, $newcardbutton, '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';

// Filters row
print '<tr class="liste_titre_filter">';

// Ref
print '<td class="liste_titre">';
print '<input type="text" class="flat maxwidth100" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';

// Deliveryman
print '<td class="liste_titre">';
$deliverymen = DeliverymenShipment::getAllDeliverymen($db);
$options = array('' => '');
foreach ($deliverymen as $dm) {
    $options[$dm['id']] = $dm['fullname'] ? $dm['fullname'] : $dm['login'];
}
print $form->selectarray('search_deliveryman', $options, $search_deliveryman, 0, 0, 0, '', 0, 0, 0, '', 'minwidth150');
print '</td>';

// Customer
print '<td class="liste_titre"></td>';

// Delivery date
print '<td class="liste_titre center">';
print '<div class="nowrap">';
print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
print '</div>';
print '<div class="nowrap">';
print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('To'));
print '</div>';
print '</td>';

// Status
print '<td class="liste_titre center">';
$statuses = array(
    '' => '',
    DeliverymenShipment::STATUS_PENDING => $langs->trans('StatusPending'),
    DeliverymenShipment::STATUS_IN_PROGRESS => $langs->trans('StatusInProgress'),
    DeliverymenShipment::STATUS_DELIVERED => $langs->trans('StatusDelivered'),
    DeliverymenShipment::STATUS_FAILED => $langs->trans('StatusFailed')
);
print $form->selectarray('search_status', $statuses, $search_status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
print '</td>';

// Actions
print '<td class="liste_titre center">';
print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans('Search'), 'search.png', '', 0, 1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans('Search')).'" title="'.dol_escape_htmltag($langs->trans('Search')).'">';
print '</td>';

print '</tr>';

// Header row
print '<tr class="liste_titre">';
print_liste_field_titre('Shipment', $_SERVER['PHP_SELF'], 'e.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Deliveryman', $_SERVER['PHP_SELF'], 'u.lastname', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Customer', $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('DeliveryDate', $_SERVER['PHP_SELF'], 'ds.delivery_date', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Status', $_SERVER['PHP_SELF'], 'ds.delivery_status', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Actions', $_SERVER['PHP_SELF'], '', '', $param, 'class="center"', $sortfield, $sortorder);
print '</tr>';

$i = 0;
while ($i < min($num, $limit)) {
    $obj = $db->fetch_object($resql);

    print '<tr class="oddeven">';

    // Shipment ref
    print '<td>';
    $expedition = new Expedition($db);
    $expedition->id = $obj->fk_expedition;
    $expedition->ref = $obj->expedition_ref;
    print $expedition->getNomUrl(1);
    print '</td>';

    // Deliveryman
    print '<td>';
    $fullname = trim($obj->firstname.' '.$obj->lastname);
    print $fullname ? $fullname : $obj->login;
    print '</td>';

    // Customer
    print '<td>';
    $societe = new Societe($db);
    $societe->id = $obj->fk_soc;
    $societe->name = $obj->thirdparty_name;
    print $societe->getNomUrl(1);
    print '</td>';

    // Delivery date
    print '<td class="center">'.dol_print_date($db->jdate($obj->delivery_date), 'day').'</td>';

    // Status
    print '<td class="center">'.DeliverymenShipment::getStatusBadge($obj->delivery_status).'</td>';

    // Actions
    print '<td class="center nowraponall">';
    print '<a href="'.dol_buildpath('/deliverymen/assign.php', 1).'?id='.$obj->fk_expedition.'" title="'.$langs->trans("Modify").'">';
    print img_picto($langs->trans("Modify"), 'edit');
    print '</a>';
    print '</td>';

    print '</tr>';

    $i++;
}

if ($num == 0) {
    print '<tr><td colspan="6" class="opacitymedium center">'.$langs->trans('NoRecordFound').'</td></tr>';
}

print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
