<?php
/**
 * Assign deliveryman to shipment
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/deliverymen/class/deliverymenshipment.class.php');

$langs->loadLangs(array('deliverymen@deliverymen', 'sendings', 'companies'));

// Get parameters
$id = GETPOSTINT('id'); // Expedition ID
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Security check
if (!isModEnabled('deliverymen')) {
    accessforbidden('Module not enabled');
}
if (!$user->hasRight('deliverymen', 'assign')) {
    accessforbidden('Not enough permissions');
}

// Load expedition
$expedition = new Expedition($db);
if ($expedition->fetch($id) <= 0) {
    dol_print_error($db, 'Expedition not found');
    exit;
}

// Load current assignment if exists
$assignment = new DeliverymenShipment($db);
$hasAssignment = ($assignment->fetchByExpedition($id) > 0);

$form = new Form($db);

/*
 * Actions
 */

if ($action == 'assign' && GETPOSTISSET('fk_user_deliveryman')) {
    $fk_user_deliveryman = GETPOSTINT('fk_user_deliveryman');
    $delivery_date = dol_mktime(0, 0, 0, GETPOSTINT('delivery_datemonth'), GETPOSTINT('delivery_dateday'), GETPOSTINT('delivery_dateyear'));
    $note = GETPOST('note_delivery', 'restricthtml');

    if ($fk_user_deliveryman > 0) {
        $assignment->fk_expedition = $id;
        $assignment->fk_user_deliveryman = $fk_user_deliveryman;
        $assignment->delivery_date = $delivery_date;
        $assignment->note_delivery = $note;

        $result = $assignment->create($user);
        if ($result > 0) {
            setEventMessages($langs->trans('DeliverymanAssigned'), null, 'mesgs');
            header('Location: '.DOL_URL_ROOT.'/expedition/card.php?id='.$id);
            exit;
        } else {
            setEventMessages($assignment->error, null, 'errors');
        }
    } else {
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Deliveryman')), null, 'errors');
    }
}

if ($action == 'confirm_remove' && $confirm == 'yes') {
    if ($hasAssignment) {
        $result = $assignment->delete($user);
        if ($result > 0) {
            setEventMessages($langs->trans('DeliverymanRemoved'), null, 'mesgs');
            header('Location: '.DOL_URL_ROOT.'/expedition/card.php?id='.$id);
            exit;
        } else {
            setEventMessages($assignment->error, null, 'errors');
        }
    }
}

if ($action == 'setstatus' && GETPOSTISSET('status')) {
    $status = GETPOST('status', 'alpha');
    $note = GETPOST('note_status', 'restricthtml');

    if ($hasAssignment) {
        $result = $assignment->setStatus($user, $status, $note);
        if ($result > 0) {
            setEventMessages($langs->trans('StatusUpdated'), null, 'mesgs');
            header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        } else {
            setEventMessages($assignment->error, null, 'errors');
        }
    }
}

/*
 * View
 */

$title = $langs->trans('AssignDeliveryman');
llxHeader('', $title);

// Confirmation dialog
if ($action == 'remove') {
    print $form->formconfirm(
        $_SERVER['PHP_SELF'].'?id='.$id,
        $langs->trans('RemoveAssignment'),
        $langs->trans('ConfirmRemoveAssignment'),
        'confirm_remove',
        array(),
        'yes',
        1
    );
}

// Shipment info banner
print load_fiche_titre($title, '', 'dolly');

print '<div class="fichecenter">';

// Expedition summary
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
print '<td>'.$expedition->getNomUrl(1).'</td></tr>';

print '<tr><td>'.$langs->trans("Customer").'</td>';
print '<td>';
$expedition->fetch_thirdparty();
print $expedition->thirdparty->getNomUrl(1);
print '</td></tr>';

print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
print '<td>'.dol_print_date($expedition->date_delivery, 'day').'</td></tr>';

print '<tr><td>'.$langs->trans("Address").'</td>';
print '<td>'.$expedition->thirdparty->address.'<br>'.$expedition->thirdparty->zip.' '.$expedition->thirdparty->town.'</td></tr>';

print '</table>';
print '</div>';

print '<br>';

// Assignment form
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="assign">';

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

print load_fiche_titre($langs->trans("DeliveryAssignment"), '', '');

print '<table class="border centpercent">';

// Deliveryman selection
print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Deliveryman").'</td><td>';

// Get all deliverymen
$deliverymen = DeliverymenShipment::getAllDeliverymen($db);
$options = array();
foreach ($deliverymen as $dm) {
    $options[$dm['id']] = $dm['fullname'] ? $dm['fullname'] : $dm['login'];
}

$selected = $hasAssignment ? $assignment->fk_user_deliveryman : 0;
print $form->selectarray('fk_user_deliveryman', $options, $selected, 1, 0, 0, '', 0, 0, 0, '', 'minwidth200');
print '</td></tr>';

// Delivery date
print '<tr><td>'.$langs->trans("DeliveryDate").'</td><td>';
$defaultDate = $hasAssignment && $assignment->delivery_date ? $assignment->delivery_date : ($expedition->date_delivery ? $expedition->date_delivery : dol_now());
print $form->selectDate($defaultDate, 'delivery_date', 0, 0, 0, 'assign', 1, 1);
print '</td></tr>';

// Notes
print '<tr><td>'.$langs->trans("Note").'</td><td>';
print '<textarea name="note_delivery" class="flat minwidth300" rows="3">'.($hasAssignment ? $assignment->note_delivery : '').'</textarea>';
print '</td></tr>';

print '</table>';

print '</div>';

// Current status (if assigned)
if ($hasAssignment) {
    print '<div class="fichehalfright">';

    print load_fiche_titre($langs->trans("CurrentStatus"), '', '');

    print '<table class="border centpercent">';

    // Current deliveryman
    $currentDeliveryman = new User($db);
    $currentDeliveryman->fetch($assignment->fk_user_deliveryman);
    print '<tr><td class="titlefield">'.$langs->trans("CurrentDeliveryman").'</td>';
    print '<td>'.$currentDeliveryman->getFullName($langs).'</td></tr>';

    // Assignment date
    print '<tr><td>'.$langs->trans("AssignmentDate").'</td>';
    print '<td>'.dol_print_date($assignment->date_assignment, 'dayhour').'</td></tr>';

    // Status
    print '<tr><td>'.$langs->trans("Status").'</td>';
    print '<td>'.DeliverymenShipment::getStatusBadge($assignment->delivery_status).'</td></tr>';

    if ($assignment->date_delivery_done) {
        print '<tr><td>'.$langs->trans("DeliveryDoneDate").'</td>';
        print '<td>'.dol_print_date($assignment->date_delivery_done, 'dayhour').'</td></tr>';
    }

    print '</table>';

    // Quick status change
    print '<br>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ChangeStatus").'</td></tr>';
    print '<tr><td colspan="2">';

    $statuses = array(
        DeliverymenShipment::STATUS_PENDING => $langs->trans('StatusPending'),
        DeliverymenShipment::STATUS_IN_PROGRESS => $langs->trans('StatusInProgress'),
        DeliverymenShipment::STATUS_DELIVERED => $langs->trans('StatusDelivered'),
        DeliverymenShipment::STATUS_FAILED => $langs->trans('StatusFailed')
    );

    foreach ($statuses as $code => $label) {
        $class = ($assignment->delivery_status == $code) ? 'butActionRefused' : 'butAction';
        if ($assignment->delivery_status != $code) {
            print '<a class="'.$class.' small" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=setstatus&status='.$code.'&token='.newToken().'">'.$label.'</a> ';
        } else {
            print '<span class="'.$class.' small">'.$label.'</span> ';
        }
    }

    print '</td></tr>';
    print '</table>';

    print '</div>';
}

print '</div>';

print '<div class="center" style="margin-top: 20px;">';
print '<input type="submit" class="button button-save" value="'.$langs->trans('Save').'">';
print ' &nbsp; ';
print '<a class="button button-cancel" href="'.DOL_URL_ROOT.'/expedition/card.php?id='.$id.'">'.$langs->trans('Cancel').'</a>';

if ($hasAssignment) {
    print ' &nbsp; ';
    print '<a class="button button-delete" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=remove&token='.newToken().'">'.$langs->trans('RemoveAssignment').'</a>';
}

print '</div>';

print '</form>';

llxFooter();
$db->close();
