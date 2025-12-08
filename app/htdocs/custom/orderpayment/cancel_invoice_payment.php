<?php
/**
 * Cancel Invoice Payment - Allows canceling invoice payments with returned check option
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
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
if (isModEnabled('bank')) {
    require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

// Load translation files
$langs->loadLangs(array('bills', 'companies', 'banks', 'orderpayment@orderpayment'));

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
if (!$user->hasRight('facture', 'paiement')) {
    accessforbidden();
}

// Initialize objects
$form = new Form($db);
$object = new Paiement($db);

// Load payment
if ($id > 0) {
    $result = $object->fetch($id);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }
}

// Get bank info directly from database (more reliable)
$fk_bank = 0;
$fk_account = 0;
$payment_amount = 0;
$payment_type_code = '';

$sql = "SELECT p.rowid, p.ref, p.amount, p.fk_bank, p.fk_paiement, p.datep, p.num_paiement,";
$sql .= " c.code as paiement_code";
$sql .= " FROM ".$db->prefix()."paiement as p";
$sql .= " LEFT JOIN ".$db->prefix()."c_paiement as c ON c.id = p.fk_paiement";
$sql .= " WHERE p.rowid = ".((int) $id);
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql)) {
    $objp = $db->fetch_object($resql);
    $fk_bank = $objp->fk_bank;
    $payment_amount = $objp->amount;
    $payment_type_code = $objp->paiement_code;
}

// Get bank account from bank line
if ($fk_bank > 0) {
    $sql = "SELECT fk_account FROM ".$db->prefix()."bank WHERE rowid = ".((int) $fk_bank);
    $resbank = $db->query($sql);
    if ($resbank && $db->num_rows($resbank)) {
        $objbank = $db->fetch_object($resbank);
        $fk_account = $objbank->fk_account;
    }
}

/*
 * Actions
 */

// Cancel payment (simple)
if ($action == 'confirm_cancel' && $confirm == 'yes') {
    $error = 0;
    $cancel_reason = GETPOST('cancel_reason', 'alpha');

    $db->begin();

    // Get linked invoices before canceling
    $sql = "SELECT pf.fk_facture, pf.amount FROM ".$db->prefix()."paiement_facture pf WHERE pf.fk_paiement = ".((int) $object->id);
    $resql = $db->query($sql);
    $linkedInvoices = array();
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $linkedInvoices[] = array('id' => $obj->fk_facture, 'amount' => $obj->amount);
        }
    }

    // Update payment status
    $sql = "UPDATE ".$db->prefix()."paiement SET";
    $sql .= " statut = 2,"; // 2 = cancelled
    $sql .= " fk_user_cancel = ".((int) $user->id).",";
    $sql .= " date_cancel = '".$db->idate(dol_now())."',";
    $sql .= " cancel_reason = ".($cancel_reason ? "'".$db->escape($cancel_reason)."'" : "NULL").",";
    $sql .= " is_returned_check = 0";
    $sql .= " WHERE rowid = ".((int) $object->id);

    $resql = $db->query($sql);
    if (!$resql) {
        $error++;
        setEventMessages($db->lasterror(), null, 'errors');
    }

    // Reverse bank entry if exists
    if (!$error && $fk_bank > 0 && $fk_account > 0 && isModEnabled('bank')) {
        $acc = new Account($db);
        $acc->fetch($fk_account);

        // Add reverse entry (negative amount)
        $bank_line_id = $acc->addline(
            dol_now(),
            $payment_type_code,
            'Annulation paiement - '.$object->ref,
            -$payment_amount, // Negative amount to reverse
            '',
            '',
            $user
        );

        if ($bank_line_id < 0) {
            $error++;
            setEventMessages($acc->error, $acc->errors, 'errors');
        } else {
            // Link the reverse entry to this payment
            $acc->add_url_line($bank_line_id, $object->id, DOL_URL_ROOT.'/compta/paiement/card.php?id=', '(paiement)', 'payment');
        }
    }

    // Update invoice payment status - reopen invoices
    if (!$error) {
        foreach ($linkedInvoices as $inv) {
            $invoice = new Facture($db);
            $invoice->fetch($inv['id']);

            // Get total already paid on this invoice (excluding cancelled payments)
            $sql_paid = "SELECT SUM(pf.amount) as total_paid";
            $sql_paid .= " FROM ".$db->prefix()."paiement_facture pf";
            $sql_paid .= " JOIN ".$db->prefix()."paiement p ON p.rowid = pf.fk_paiement";
            $sql_paid .= " WHERE pf.fk_facture = ".((int) $inv['id']);
            $sql_paid .= " AND p.statut != 2"; // Exclude cancelled payments
            $res_paid = $db->query($sql_paid);
            $total_paid = 0;
            if ($res_paid) {
                $obj_paid = $db->fetch_object($res_paid);
                $total_paid = (float) ($obj_paid->total_paid ?? 0);
            }

            // Update invoice status based on remaining payments
            if ($total_paid <= 0) {
                // No more payments - set to unpaid (status 1 = validated but unpaid)
                $sql_upd = "UPDATE ".$db->prefix()."facture SET fk_statut = 1, paye = 0 WHERE rowid = ".((int) $inv['id']);
                $db->query($sql_upd);
            } elseif ($total_paid < $invoice->total_ttc) {
                // Partial payment - set to started (status 2)
                $sql_upd = "UPDATE ".$db->prefix()."facture SET fk_statut = 2, paye = 0 WHERE rowid = ".((int) $inv['id']);
                $db->query($sql_upd);
            }
            // If still fully paid, keep status as is
        }
    }

    if (!$error) {
        $db->commit();
        setEventMessages($langs->trans("PaymentCanceled"), null, 'mesgs');
        header("Location: ".DOL_URL_ROOT."/compta/paiement/card.php?id=".$object->id);
        exit;
    } else {
        $db->rollback();
    }
}

// Mark as returned check
if ($action == 'confirm_returned_check' && $confirm == 'yes') {
    $error = 0;
    $cancel_reason = GETPOST('cancel_reason', 'alpha');

    $db->begin();

    // Get linked invoices
    $sql = "SELECT pf.fk_facture, pf.amount FROM ".$db->prefix()."paiement_facture pf WHERE pf.fk_paiement = ".((int) $object->id);
    $resql = $db->query($sql);
    $linkedInvoices = array();
    $socid = 0;
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $linkedInvoices[] = array('id' => $obj->fk_facture, 'amount' => $obj->amount);
            if ($socid == 0) {
                $tmpinv = new Facture($db);
                $tmpinv->fetch($obj->fk_facture);
                $socid = $tmpinv->socid;
            }
        }
    }

    // Update payment status with returned check flag
    $sql = "UPDATE ".$db->prefix()."paiement SET";
    $sql .= " statut = 2,"; // 2 = cancelled
    $sql .= " fk_user_cancel = ".((int) $user->id).",";
    $sql .= " date_cancel = '".$db->idate(dol_now())."',";
    $sql .= " cancel_reason = ".($cancel_reason ? "'".$db->escape($cancel_reason)."'" : "NULL").",";
    $sql .= " is_returned_check = 1";
    $sql .= " WHERE rowid = ".((int) $object->id);

    $resql = $db->query($sql);
    if (!$resql) {
        $error++;
        setEventMessages($db->lasterror(), null, 'errors');
    }

    // Reverse bank entry if exists
    if (!$error && $fk_bank > 0 && $fk_account > 0 && isModEnabled('bank')) {
        $acc = new Account($db);
        $acc->fetch($fk_account);

        // Add reverse entry with returned check label
        $bank_line_id = $acc->addline(
            dol_now(),
            $payment_type_code,
            'Chèque impayé - '.$object->ref,
            -$payment_amount, // Negative amount to reverse
            '',
            '',
            $user
        );

        if ($bank_line_id < 0) {
            $error++;
            setEventMessages($acc->error, $acc->errors, 'errors');
        } else {
            // Link the reverse entry
            $acc->add_url_line($bank_line_id, $object->id, DOL_URL_ROOT.'/compta/paiement/card.php?id=', '(paiement)', 'payment');
            // Link to thirdparty
            if ($socid > 0) {
                $acc->add_url_line($bank_line_id, $socid, DOL_URL_ROOT.'/societe/card.php?socid=', '', 'company');
            }
        }
    }

    // Update invoice payment status - reopen invoices
    if (!$error) {
        foreach ($linkedInvoices as $inv) {
            $invoice = new Facture($db);
            $invoice->fetch($inv['id']);

            // Get total already paid on this invoice (excluding cancelled payments)
            $sql_paid = "SELECT SUM(pf.amount) as total_paid";
            $sql_paid .= " FROM ".$db->prefix()."paiement_facture pf";
            $sql_paid .= " JOIN ".$db->prefix()."paiement p ON p.rowid = pf.fk_paiement";
            $sql_paid .= " WHERE pf.fk_facture = ".((int) $inv['id']);
            $sql_paid .= " AND p.statut != 2"; // Exclude cancelled payments
            $res_paid = $db->query($sql_paid);
            $total_paid = 0;
            if ($res_paid) {
                $obj_paid = $db->fetch_object($res_paid);
                $total_paid = (float) ($obj_paid->total_paid ?? 0);
            }

            // Update invoice status based on remaining payments
            if ($total_paid <= 0) {
                // No more payments - set to unpaid (status 1 = validated but unpaid)
                $sql_upd = "UPDATE ".$db->prefix()."facture SET fk_statut = 1, paye = 0 WHERE rowid = ".((int) $inv['id']);
                $db->query($sql_upd);
            } elseif ($total_paid < $invoice->total_ttc) {
                // Partial payment - set to started (status 2)
                $sql_upd = "UPDATE ".$db->prefix()."facture SET fk_statut = 2, paye = 0 WHERE rowid = ".((int) $inv['id']);
                $db->query($sql_upd);
            }
        }
    }

    if (!$error) {
        $db->commit();
        setEventMessages($langs->trans("PaymentCanceled").' - '.$langs->trans("ReturnedCheck"), null, 'mesgs');
        header("Location: ".DOL_URL_ROOT."/compta/paiement/card.php?id=".$object->id);
        exit;
    } else {
        $db->rollback();
    }
}

/*
 * View
 */

$title = $langs->trans('CancelPayment');
llxHeader('', $title);

if ($object->id > 0) {
    // Check if already cancelled
    $sql = "SELECT statut, is_returned_check FROM ".$db->prefix()."paiement WHERE rowid = ".((int) $object->id);
    $resstat = $db->query($sql);
    $currentStatus = 0;
    $isReturnedCheck = 0;
    if ($resstat) {
        $objstat = $db->fetch_object($resstat);
        $currentStatus = $objstat->statut;
        $isReturnedCheck = $objstat->is_returned_check;
    }

    if ($currentStatus == 2) {
        // Already cancelled
        print '<div class="warning">'.$langs->trans("PaymentAlreadyCancelled");
        if ($isReturnedCheck) {
            print ' ('.$langs->trans("ReturnedCheck").')';
        }
        print '</div>';
        print '<br><a href="'.DOL_URL_ROOT.'/compta/paiement/card.php?id='.$object->id.'" class="button">'.$langs->trans("Back").'</a>';
    } else {
        // Confirmation dialogs
        if ($action == 'cancel') {
            $formquestion = array(
                array('type' => 'text', 'name' => 'cancel_reason', 'label' => $langs->trans("CancelReason"), 'value' => '', 'size' => 60)
            );
            print $form->formconfirm(
                $_SERVER['PHP_SELF'].'?id='.$object->id,
                $langs->trans('CancelPayment'),
                $langs->trans('ConfirmCancelOrderPayment'),
                'confirm_cancel',
                $formquestion,
                0,
                1
            );
        }

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

        // Payment info
        print load_fiche_titre($langs->trans("CancelPayment"), '', 'payment');

        print '<div class="fichecenter">';
        print '<table class="border centpercent tableforfield">';

        // Ref
        print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
        print '<td>'.$object->ref.'</td></tr>';

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td>'.dol_print_date($object->datepaye, 'day').'</td></tr>';

        // Amount
        print '<tr><td>'.$langs->trans("Amount").'</td>';
        print '<td><strong>'.price($payment_amount, 0, $langs, 1, -1, -1, $conf->currency).'</strong></td></tr>';

        // Payment mode
        print '<tr><td>'.$langs->trans("PaymentMode").'</td>';
        print '<td>'.$langs->trans("PaymentType".$payment_type_code).'</td></tr>';

        // Num payment
        if (!empty($object->num_paiement)) {
            print '<tr><td>'.$langs->trans("Numero").'</td>';
            print '<td>'.$object->num_paiement.'</td></tr>';
        }

        // Bank account
        if ($fk_account > 0 && isModEnabled('bank')) {
            $acc = new Account($db);
            $acc->fetch($fk_account);
            print '<tr><td>'.$langs->trans("BankAccount").'</td>';
            print '<td>'.$acc->getNomUrl(1).'</td></tr>';
        }

        // Linked invoices
        print '<tr><td>'.$langs->trans("Invoices").'</td><td>';
        $sql = "SELECT f.rowid, f.ref, pf.amount FROM ".$db->prefix()."paiement_facture pf";
        $sql .= " JOIN ".$db->prefix()."facture f ON f.rowid = pf.fk_facture";
        $sql .= " WHERE pf.fk_paiement = ".((int) $object->id);
        $resinv = $db->query($sql);
        if ($resinv) {
            $invoices = array();
            while ($objinv = $db->fetch_object($resinv)) {
                $inv = new Facture($db);
                $inv->fetch($objinv->rowid);
                $invoices[] = $inv->getNomUrl(1).' ('.price($objinv->amount, 0, $langs, 1, -1, -1, $conf->currency).')';
            }
            print implode('<br>', $invoices);
        }
        print '</td></tr>';

        print '</table>';
        print '</div>';

        // Action buttons
        if ($action != 'cancel' && $action != 'returned_check') {
            print '<div class="tabsAction">';
            print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=cancel&token='.newToken().'">'.$langs->trans("CancelPayment").'</a>';
            print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=returned_check&token='.newToken().'">'.$langs->trans("MarkAsReturnedCheck").'</a>';
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/paiement/card.php?id='.$object->id.'">'.$langs->trans("Back").'</a>';
            print '</div>';
        }
    }
} else {
    print '<div class="error">'.$langs->trans("PaymentNotFound").'</div>';
}

llxFooter();
$db->close();
