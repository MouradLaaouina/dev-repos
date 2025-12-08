<?php
/**
 * Order Payment Card - Create and view payments
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
if (isModEnabled('bank')) {
    require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}
dol_include_once('/orderpayment/class/orderpayment.class.php');

// Load translation files
$langs->loadLangs(array('bills', 'companies', 'orders', 'banks', 'orderpayment@orderpayment'));

// Security check
if (!$user->hasRight('orderpayment', 'read')) {
    accessforbidden();
}

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$ordid = GETPOST('ordid', 'int');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');


// Initialize objects
$form = new Form($db);
$formcompany = new FormCompany($db);
$object = new OrderPayment($db);
$order = new Commande($db);
$thirdparty = new Societe($db);

// Initialize variables for create form
$prefillSocId = 0;
$prefillAmount = 0;
$prefillOrderRef = '';
$paidOrder = 0;
$orderTotalTTC = 0;

// If creating from an order
if ($ordid > 0 && ($action == 'create' || empty($action))) {
    if ($order->fetch($ordid) > 0) {
        $prefillSocId = $order->socid;
        $prefillOrderRef = $order->ref;
        $orderTotalTTC = $order->total_ttc;
        $paidOrder = $object->getSumPaymentsForOrder($ordid);
        $prefillAmount = price2num($order->total_ttc - $paidOrder, 'MT');
        if ($prefillAmount < 0) {
            $prefillAmount = 0;
        }
    }
}

/*
 * Actions
 */

// Create payment
if ($action == 'add' && $user->hasRight('orderpayment', 'write')) {
    $error = 0;

    $amount = price2num(GETPOST('amount', 'alpha'), 'MT');
    $fk_mode_reglement = GETPOST('fk_mode_reglement', 'int');
    $num_payment = GETPOST('num_payment', 'alpha');
    $fk_account = GETPOST('fk_account', 'int');
    $note_public = GETPOST('note_public', 'restricthtml');
    $note_private = GETPOST('note_private', 'restricthtml');
    $fk_soc = GETPOST('fk_soc', 'int');
    $datep = dol_mktime(12, 0, 0, GETPOST('datepmonth', 'int'), GETPOST('datepday', 'int'), GETPOST('datepyear', 'int'));
    $linkedOrders = GETPOST('orders', 'array');

    // Validation
    if (empty($amount) || $amount <= 0) {
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Amount')), null, 'errors');
        $error++;
    }
    if (empty($linkedOrders) || count($linkedOrders) == 0) {
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Orders')), null, 'errors');
        $error++;
    }
    if (empty($datep)) {
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Date')), null, 'errors');
        $error++;
    }

    if (!$error) {
        // Get thirdparty from first order if not set
        if (empty($fk_soc) && !empty($linkedOrders)) {
            $tmpOrder = new Commande($db);
            if ($tmpOrder->fetch((int) $linkedOrders[0]) > 0) {
                $fk_soc = $tmpOrder->socid;
            }
        }

        $object->fk_soc = $fk_soc;
        $object->amount = $amount;
        $object->datep = $datep;
        $object->fk_mode_reglement = $fk_mode_reglement;
        $object->num_payment = $num_payment;
        $object->fk_account = $fk_account;
        $object->note_public = $note_public;
        $object->note_private = $note_private;

        // Create lines - distribute amount among selected orders
        $nbOrders = count($linkedOrders);
        $amountPerOrder = $amount / $nbOrders;

        foreach ($linkedOrders as $oid) {
            $line = new stdClass();
            $line->fk_commande = (int) $oid;
            $line->amount = $amountPerOrder;
            $object->lines[] = $line;
        }

        $result = $object->create($user);
        if ($result > 0) {
            setEventMessages($langs->trans("OrderPaymentCreated"), null, 'mesgs');
            if (!empty($backtopage)) {
                header("Location: ".$backtopage);
            } else {
                header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
            }
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'create';
        }
    } else {
        $action = 'create';
    }
}

// Cancel payment (simple)
if ($action == 'confirm_cancel' && $confirm == 'yes' && $user->hasRight('orderpayment', 'cancel')) {
    $cancel_reason = GETPOST('cancel_reason', 'alpha');
    if ($object->fetch($id) > 0) {
        $result = $object->cancel($user, $cancel_reason, 0);
        if ($result > 0) {
            setEventMessages($langs->trans("OrderPaymentCanceled"), null, 'mesgs');
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}

// Mark as returned check
if ($action == 'confirm_returned_check' && $confirm == 'yes' && $user->hasRight('orderpayment', 'cancel')) {
    $cancel_reason = GETPOST('cancel_reason', 'alpha');
    if ($object->fetch($id) > 0) {
        $result = $object->cancel($user, $cancel_reason, 1); // 1 = returned check
        if ($result > 0) {
            setEventMessages($langs->trans("OrderPaymentCanceled"), null, 'mesgs');
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}

// Load object if id provided
if ($id > 0) {
    $object->fetch($id);
}

/*
 * View
 */

$title = $langs->trans('OrderPaymentCard');
$help_url = '';

llxHeader('', $title, $help_url);

// Create form
if ($action == 'create') {
    // Check permission
    if (!$user->hasRight('orderpayment', 'write')) {
        accessforbidden('You need write permission on orderpayment module');
    }

    print load_fiche_titre($langs->trans("CreateOrderPayment"), '', 'payment');

    // Warning if client has returned checks
    if ($prefillSocId > 0) {
        $nbReturnedChecks = $object->getReturnedChecksCount($prefillSocId);
        $totalReturnedChecks = $object->getReturnedChecksTotal($prefillSocId);
        if ($nbReturnedChecks > 0) {
            print '<div class="warning">';
            print img_warning().' '.sprintf($langs->trans("ReturnedCheckWarning"), $nbReturnedChecks, price($totalReturnedChecks, 0, $langs, 1, -1, -1, $conf->currency));
            print '</div><br>';
        }
    }

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    if ($ordid > 0) {
        print '<input type="hidden" name="ordid" value="'.$ordid.'">';
    }
    if (!empty($backtopage)) {
        print '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($backtopage).'">';
    }

    print dol_get_fiche_head(array(), '');

    print '<table class="border centpercent tableforfieldcreate">';

    // Thirdparty
    print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("ThirdParty").'</td><td>';
    print $form->select_company($prefillSocId, 'fk_soc', '', 'SelectThirdParty', 1, 0, array(), 0, 'minwidth300 maxwidth500');
    print '</td></tr>';

    // Date
    print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
    print $form->selectDate(dol_now(), 'datep', 0, 0, 0, '', 1, 1);
    print '</td></tr>';

    // Amount
    print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td>';
    print '<input class="flat maxwidth150" type="text" name="amount" value="'.($prefillAmount > 0 ? price($prefillAmount) : '').'">';
    print ' '.$conf->currency;
    print '</td></tr>';

    // Payment mode
    print '<tr><td>'.$langs->trans("PaymentMode").'</td><td>';
    print $form->select_types_paiements('', 'fk_mode_reglement', '', 0, 1, 0, 0, 1, 'minwidth200', 1);
    print '</td></tr>';

    // Payment number
    print '<tr><td>'.$langs->trans("Numero").'</td><td>';
    print '<input class="flat maxwidth200" type="text" name="num_payment" value="">';
    print '</td></tr>';

    // Bank account (if bank module enabled)
    if (isModEnabled('bank')) {
        print '<tr><td>'.$langs->trans("BankAccount").'</td><td>';
        print $form->select_comptes('', 'fk_account', 0, '', 1, '', 0, 'minwidth200', 1);
        print '<br><span class="opacitymedium small">'.$langs->trans("SelectBankAccount").'</span>';
        print '</td></tr>';
    }

    // Orders selection
    print '<tr><td class="fieldrequired tdtop">'.$langs->trans("LinkedOrders").'</td><td>';

    // Get available orders
    $sql = "SELECT c.rowid, c.ref, c.total_ttc, s.nom as socname";
    $sql .= " FROM ".$db->prefix()."commande as c";
    $sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = c.fk_soc";
    $sql .= " WHERE c.entity IN (".getEntity('commande').")";
    $sql .= " AND c.fk_statut > 0"; // Not draft
    if ($prefillSocId > 0) {
        $sql .= " AND c.fk_soc = ".((int) $prefillSocId);
    }
    $sql .= " ORDER BY c.date_commande DESC, c.rowid DESC";
    $sql .= " LIMIT 100";

    $resql = $db->query($sql);
    if ($resql) {
        print '<select name="orders[]" id="orders" class="flat minwidth400" multiple size="8">';
        while ($obj = $db->fetch_object($resql)) {
            $selected = ($ordid > 0 && $ordid == $obj->rowid) ? ' selected' : '';
            $orderPaid = $object->getSumPaymentsForOrder($obj->rowid);
            $label = $obj->ref.' - '.$obj->socname.' - '.price($obj->total_ttc).' '.$conf->currency;
            if ($orderPaid > 0) {
                $label .= ' ('.$langs->trans("AlreadyPaidOnOrder").': '.price($orderPaid).')';
            }
            print '<option value="'.$obj->rowid.'"'.$selected.'>'.dol_escape_htmltag($label).'</option>';
        }
        print '</select>';
        print '<br><span class="opacitymedium">'.$langs->trans("SelectOrders").'</span>';
        $db->free($resql);
    }
    print '</td></tr>';

    // Show order info if prefilled
    if ($ordid > 0 && $orderTotalTTC > 0) {
        print '<tr><td>'.$langs->trans("TotalOrderAmount").'</td><td>'.price($orderTotalTTC, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';
        print '<tr><td>'.$langs->trans("AlreadyPaidOnOrder").'</td><td>'.price($paidOrder, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';
        print '<tr><td>'.$langs->trans("RemainsToPay").'</td><td><strong>'.price($prefillAmount, 0, $langs, 1, -1, -1, $conf->currency).'</strong></td></tr>';
    }

    // Note public
    print '<tr><td class="tdtop">'.$langs->trans("NotePublic").'</td><td>';
    print '<textarea name="note_public" class="flat quatrevingtpercent" rows="3"></textarea>';
    print '</td></tr>';

    // Note private
    print '<tr><td class="tdtop">'.$langs->trans("NotePrivate").'</td><td>';
    print '<textarea name="note_private" class="flat quatrevingtpercent" rows="3"></textarea>';
    print '</td></tr>';

    print '</table>';

    print dol_get_fiche_end();

    print '<div class="center" style="margin-top: 20px;">';
    print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Create").'">';
    print ' &nbsp; &nbsp; ';
    print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onclick="javascript:history.go(-1)">';
    print '</div>';

    print '</form>';
}
// View existing payment
elseif ($object->id > 0) {
    // Confirmation dialog for cancel
    if ($action == 'cancel') {
        $formquestion = array(
            array('type' => 'text', 'name' => 'cancel_reason', 'label' => $langs->trans("CancelReason"), 'value' => '', 'size' => 60)
        );
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            $langs->trans('Cancel'),
            $langs->trans('ConfirmCancelOrderPayment'),
            'confirm_cancel',
            $formquestion,
            0,
            1
        );
    }

    // Confirmation dialog for returned check
    if ($action == 'returned_check') {
        $formquestion = array(
            array('type' => 'text', 'name' => 'cancel_reason', 'label' => $langs->trans("CancelReason"), 'value' => '', 'size' => 60)
        );
        print $form->formconfirm(
            $_SERVER['PHP_SELF'].'?id='.$object->id,
            $langs->trans('MarkAsReturnedCheck'),
            $langs->trans('ConfirmReturnedCheck'),
            'confirm_returned_check',
            $formquestion,
            0,
            1
        );
    }

    print dol_get_fiche_head(array(), '');

    // Load thirdparty
    if ($object->fk_soc > 0) {
        $thirdparty->fetch($object->fk_soc);
    }

    $linkback = '<a href="'.dol_buildpath('/orderpayment/list.php', 1).'?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent tableforfield">';

    // Ref
    print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
    print '<td>'.dol_escape_htmltag($object->ref).'</td></tr>';

    // Thirdparty
    print '<tr><td>'.$langs->trans("ThirdParty").'</td>';
    print '<td>'.($object->fk_soc > 0 ? $thirdparty->getNomUrl(1) : '').'</td></tr>';

    // Date
    print '<tr><td>'.$langs->trans("Date").'</td>';
    print '<td>'.dol_print_date($object->datep, 'day').'</td></tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Amount").'</td>';
    print '<td><strong>'.price($object->amount, 0, $langs, 1, -1, -1, $conf->currency).'</strong></td></tr>';

    // Payment mode
    print '<tr><td>'.$langs->trans("PaymentMode").'</td><td>';
    if ($object->fk_mode_reglement > 0) {
        $sql = "SELECT code, libelle FROM ".$db->prefix()."c_paiement WHERE id = ".((int) $object->fk_mode_reglement);
        $resmode = $db->query($sql);
        if ($resmode && $db->num_rows($resmode)) {
            $objmode = $db->fetch_object($resmode);
            $paymentmode = $langs->trans("PaymentType".$objmode->code) != "PaymentType".$objmode->code ? $langs->trans("PaymentType".$objmode->code) : $objmode->libelle;
            print dol_escape_htmltag($paymentmode);
        }
    }
    print '</td></tr>';

    // Payment number
    print '<tr><td>'.$langs->trans("Numero").'</td>';
    print '<td>'.dol_escape_htmltag($object->num_payment).'</td></tr>';

    // Bank account
    if (isModEnabled('bank') && $object->fk_account > 0) {
        $bankaccount = new Account($db);
        $bankaccount->fetch($object->fk_account);
        print '<tr><td>'.$langs->trans("BankAccount").'</td>';
        print '<td>'.$bankaccount->getNomUrl(1).'</td></tr>';
    }

    // Status
    print '<tr><td>'.$langs->trans("Status").'</td>';
    print '<td>'.$object->getLibStatut(4).'</td></tr>';

    // Linked orders
    if (!empty($object->lines)) {
        print '<tr><td>'.$langs->trans("LinkedOrders").'</td><td>';
        $links = array();
        foreach ($object->lines as $line) {
            $orderObj = new Commande($db);
            if ($orderObj->fetch($line->fk_commande) > 0) {
                $links[] = $orderObj->getNomUrl(1).' ('.price($line->amount, 0, $langs, 1, -1, -1, $conf->currency).')';
            }
        }
        print implode('<br>', $links);
        print '</td></tr>';
    }

    // Cancel reason
    if ($object->status == OrderPayment::STATUS_CANCELLED && !empty($object->cancel_reason)) {
        print '<tr><td>'.$langs->trans("CancelReason").'</td>';
        print '<td>'.dol_escape_htmltag($object->cancel_reason).'</td></tr>';
    }

    // Is returned check
    if ($object->is_returned_check) {
        print '<tr><td>'.$langs->trans("IsReturnedCheck").'</td>';
        print '<td><span class="badge badge-status8">'.$langs->trans("Yes").'</span></td></tr>';
    }

    // Note public
    if (!empty($object->note_public)) {
        print '<tr><td>'.$langs->trans("NotePublic").'</td>';
        print '<td>'.dol_htmlentitiesbr($object->note_public).'</td></tr>';
    }

    // Note private
    if (!empty($object->note_private)) {
        print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
        print '<td>'.dol_htmlentitiesbr($object->note_private).'</td></tr>';
    }

    // Creation info
    print '<tr><td>'.$langs->trans("DateCreation").'</td>';
    print '<td>'.dol_print_date($object->datec, 'dayhour').'</td></tr>';

    // Cancel info
    if ($object->status == OrderPayment::STATUS_CANCELLED && $object->date_cancel) {
        print '<tr><td>'.$langs->trans("DateCancellation").'</td>';
        print '<td>'.dol_print_date($object->date_cancel, 'dayhour').'</td></tr>';
    }

    print '</table>';

    print '</div>';

    print dol_get_fiche_end();

    // Action buttons
    print '<div class="tabsAction">';

    if ($object->status != OrderPayment::STATUS_CANCELLED && $user->hasRight('orderpayment', 'cancel')) {
        print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=cancel&token='.newToken().'">'.$langs->trans("Cancel").'</a>';
        print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=returned_check&token='.newToken().'">'.$langs->trans("MarkAsReturnedCheck").'</a>';
    }

    print '</div>';
}
// List view (default when no id)
else {
    header("Location: ".dol_buildpath('/orderpayment/list.php', 1));
    exit;
}

llxFooter();
$db->close();
