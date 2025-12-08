<?php
/**
 * List of returned/unpaid checks from order payments
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$langs->loadLangs(array('customercheck@customercheck', 'companies', 'bills'));

// Security check
if (!isModEnabled('customercheck')) {
    accessforbidden('Module not enabled');
}

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'returnedcheckslist';

$search_ref = GETPOST('search_ref', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_socid = GETPOSTINT('search_socid');
$search_sanction = GETPOST('search_sanction', 'int');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0) $page = 0;
$offset = $limit * $page;
if (!$sortfield) $sortfield = 'op.datep';
if (!$sortorder) $sortorder = 'DESC';

// Initialize objects
$form = new Form($db);

/*
 * Actions
 */

// Lift sanction
if ($action == 'confirm_liftsanction' && $confirm == 'yes') {
    $id = GETPOSTINT('id');
    if ($user->hasRight('customercheck', 'liftsanction')) {
        $note = GETPOST('note_sanction', 'restricthtml');

        $sql = "UPDATE ".$db->prefix()."order_payment SET";
        $sql .= " sanction_lifted = 1,";
        $sql .= " date_sanction_lifted = '".$db->idate(dol_now())."',";
        $sql .= " fk_user_sanction_lifted = ".((int) $user->id).",";
        $sql .= " note_sanction = ".($note ? "'".$db->escape($note)."'" : "NULL");
        $sql .= " WHERE rowid = ".((int) $id);

        if ($db->query($sql)) {
            setEventMessages($langs->trans('SanctionLifted'), null, 'mesgs');
        } else {
            setEventMessages($db->lasterror(), null, 'errors');
        }
    } else {
        setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
    }
}

// Reactivate sanction
if ($action == 'confirm_reactivate' && $confirm == 'yes') {
    $id = GETPOSTINT('id');
    if ($user->hasRight('customercheck', 'liftsanction')) {
        $sql = "UPDATE ".$db->prefix()."order_payment SET";
        $sql .= " sanction_lifted = 0,";
        $sql .= " date_sanction_lifted = NULL,";
        $sql .= " fk_user_sanction_lifted = NULL,";
        $sql .= " note_sanction = NULL";
        $sql .= " WHERE rowid = ".((int) $id);

        if ($db->query($sql)) {
            setEventMessages($langs->trans('SanctionReactivated'), null, 'mesgs');
        } else {
            setEventMessages($db->lasterror(), null, 'errors');
        }
    } else {
        setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
    }
}

/*
 * View
 */

$title = $langs->trans('ReturnedChecksList');
llxHeader('', $title);

// Confirmation dialogs
if ($action == 'liftsanction') {
    $id = GETPOSTINT('id');
    // Get payment ref
    $sqlref = "SELECT ref FROM ".$db->prefix()."order_payment WHERE rowid = ".((int) $id);
    $resref = $db->query($sqlref);
    $objref = $db->fetch_object($resref);
    $ref = $objref ? $objref->ref : $id;

    $formquestion = array(
        array('type' => 'text', 'name' => 'note_sanction', 'label' => $langs->trans('NoteForLiftingSanction'), 'value' => '', 'size' => 60)
    );
    print $form->formconfirm(
        $_SERVER['PHP_SELF'].'?id='.$id,
        $langs->trans('LiftSanction'),
        $langs->trans('ConfirmLiftSanction', $ref),
        'confirm_liftsanction',
        $formquestion,
        'yes',
        1
    );
}

if ($action == 'reactivate') {
    $id = GETPOSTINT('id');
    // Get payment ref
    $sqlref = "SELECT ref FROM ".$db->prefix()."order_payment WHERE rowid = ".((int) $id);
    $resref = $db->query($sqlref);
    $objref = $db->fetch_object($resref);
    $ref = $objref ? $objref->ref : $id;

    print $form->formconfirm(
        $_SERVER['PHP_SELF'].'?id='.$id,
        $langs->trans('ReactivateSanction'),
        $langs->trans('ConfirmReactivateSanction', $ref),
        'confirm_reactivate',
        array(),
        'yes',
        1
    );
}

// Build SQL query - get returned checks from order_payment table
$sql = "SELECT op.rowid, op.ref, op.fk_soc, op.amount, op.num_payment, op.datep,";
$sql .= " op.cancel_reason, op.is_returned_check, op.sanction_lifted,";
$sql .= " op.date_sanction_lifted, op.fk_user_sanction_lifted, op.note_sanction,";
$sql .= " s.nom as company_name, s.rowid as socid";
$sql .= " FROM ".$db->prefix()."order_payment as op";
$sql .= " LEFT JOIN ".$db->prefix()."societe as s ON op.fk_soc = s.rowid";
$sql .= " WHERE op.entity = ".((int) $conf->entity);
$sql .= " AND op.is_returned_check = 1";

if ($search_ref) {
    $sql .= natural_search('op.ref', $search_ref);
}
if ($search_company) {
    $sql .= natural_search('s.nom', $search_company);
}
if ($search_socid > 0) {
    $sql .= " AND op.fk_soc = ".((int) $search_socid);
}
if ($search_sanction !== '' && $search_sanction >= 0) {
    // search_sanction: 1 = active (sanction_lifted=0), 0 = lifted (sanction_lifted=1)
    $sql .= " AND op.sanction_lifted = ".($search_sanction == '1' ? 0 : 1);
}

// Count total
$sqlcount = "SELECT COUNT(*) as total FROM ".$db->prefix()."order_payment as op";
$sqlcount .= " LEFT JOIN ".$db->prefix()."societe as s ON op.fk_soc = s.rowid";
$sqlcount .= " WHERE op.entity = ".((int) $conf->entity);
$sqlcount .= " AND op.is_returned_check = 1";
if ($search_ref) {
    $sqlcount .= natural_search('op.ref', $search_ref);
}
if ($search_company) {
    $sqlcount .= natural_search('s.nom', $search_company);
}
if ($search_socid > 0) {
    $sqlcount .= " AND op.fk_soc = ".((int) $search_socid);
}
if ($search_sanction !== '' && $search_sanction >= 0) {
    $sqlcount .= " AND op.sanction_lifted = ".($search_sanction == '1' ? 0 : 1);
}

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

// List
$param = '';
if ($search_ref) $param .= '&search_ref='.urlencode($search_ref);
if ($search_company) $param .= '&search_company='.urlencode($search_company);
if ($search_sanction !== '') $param .= '&search_sanction='.$search_sanction;

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="formlist">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $totalofrecords, 'warning', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';

// Header
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat maxwidth150" name="search_company" value="'.dol_escape_htmltag($search_company).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center">';
print '<select class="flat" name="search_sanction">';
print '<option value="">--</option>';
print '<option value="1"'.($search_sanction === '1' ? ' selected' : '').'>'.$langs->trans('SanctionActive').'</option>';
print '<option value="0"'.($search_sanction === '0' ? ' selected' : '').'>'.$langs->trans('SanctionLifted').'</option>';
print '</select>';
print '</td>';
print '<td class="liste_titre center">';
print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans('Search'), 'search.png', '', 0, 1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans('Search')).'" title="'.dol_escape_htmltag($langs->trans('Search')).'">';
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'op.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Customer', $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Amount', $_SERVER['PHP_SELF'], 'op.amount', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('CheckNumber', $_SERVER['PHP_SELF'], 'op.num_payment', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Date', $_SERVER['PHP_SELF'], 'op.datep', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Reason', $_SERVER['PHP_SELF'], 'op.cancel_reason', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('SanctionStatus', $_SERVER['PHP_SELF'], 'op.sanction_lifted', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Actions', $_SERVER['PHP_SELF'], '', '', $param, 'class="center"', $sortfield, $sortorder);
print '</tr>';

$i = 0;
while ($i < min($num, $limit)) {
    $obj = $db->fetch_object($resql);

    print '<tr class="oddeven">';

    // Ref
    print '<td>';
    print '<a href="'.dol_buildpath('/custom/orderpayment/card.php', 1).'?id='.$obj->rowid.'">'.$obj->ref.'</a>';
    print '</td>';

    // Customer
    print '<td>';
    $societe = new Societe($db);
    $societe->id = $obj->socid;
    $societe->name = $obj->company_name;
    print $societe->getNomUrl(1);
    print '</td>';

    // Amount
    print '<td class="right">'.price($obj->amount, 0, $langs, 1, -1, -1, $conf->currency).'</td>';

    // Check number
    print '<td>'.$obj->num_payment.'</td>';

    // Date
    print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';

    // Reason
    print '<td>'.$obj->cancel_reason.'</td>';

    // Sanction status
    print '<td class="center">';
    if (!$obj->sanction_lifted) {
        print '<span class="badge badge-status4 badge-status">'.$langs->trans('SanctionActive').'</span>';
    } else {
        print '<span class="badge badge-status8 badge-status">'.$langs->trans('SanctionLifted').'</span>';
        if ($obj->date_sanction_lifted) {
            print '<br><small>'.dol_print_date($db->jdate($obj->date_sanction_lifted), 'dayhour').'</small>';
        }
        if ($obj->note_sanction) {
            print '<br><small class="opacitymedium">'.dol_escape_htmltag($obj->note_sanction).'</small>';
        }
    }
    print '</td>';

    // Actions
    print '<td class="center nowraponall">';
    if ($user->hasRight('customercheck', 'liftsanction')) {
        if (!$obj->sanction_lifted) {
            print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=liftsanction&id='.$obj->rowid.'&token='.newToken().'" title="'.$langs->trans('LiftSanction').'">';
            print img_picto($langs->trans('LiftSanction'), 'unlock');
            print '</a>';
        } else {
            print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=reactivate&id='.$obj->rowid.'&token='.newToken().'" title="'.$langs->trans('ReactivateSanction').'">';
            print img_picto($langs->trans('ReactivateSanction'), 'lock');
            print '</a>';
        }
    }
    print '</td>';

    print '</tr>';

    $i++;
}

if ($num == 0) {
    print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('NoRecordFound').'</td></tr>';
}

print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
