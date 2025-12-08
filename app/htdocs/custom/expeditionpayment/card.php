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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
dol_include_once('/expeditionpayment/class/expeditionpayment.class.php');

if (empty($user->rights->expeditionpayment->read)) {
    accessforbidden();
}

$langs->loadLangs(array('bills', 'companies', 'other', 'sendings'));

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$expedition = new Expedition($db);
$payment = new ExpeditionPayment($db);

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$expid = GETPOST('expid', 'int');
$prefillSocId = 0;

if ($expid > 0) {
    if ($expedition->fetch($expid) > 0) {
        $prefillSocId = $expedition->socid;
    }
}

if ($action === 'add' && !empty($user->rights->expeditionpayment->write)) {
    $amount = price2num(GETPOST('amount', 'alpha'), 'MT');
    $mode = GETPOST('mode', 'alpha');
    $num = GETPOST('num_payment', 'alpha');
    $note = GETPOST('note', 'restricthtml');
    $fk_soc = (int) GETPOST('fk_soc', 'int');
    $datep = dol_mktime(12, 0, 0, GETPOST('datepmonth', 'int'), GETPOST('datepday', 'int'), GETPOST('datepyear', 'int'));
    $linkedExp = (array) GETPOST('expeditions', 'array');

    $payment->fk_soc = $fk_soc;
    $payment->amount = $amount;
    $payment->datep = $datep;
    $payment->mode = $mode;
    $payment->num_payment = $num;
    $payment->note = $note;

    $totalLinked = 0;
    foreach ($linkedExp as $expId) {
        $expObj = new Expedition($db);
        if ($expObj->fetch((int) $expId) > 0) {
            $line = new stdClass();
            $line->fk_expedition = (int) $expId;
            $line->amount = $amount; // full amount if single; adjust below
            $payment->lines[] = $line;
            $fk_soc = $expObj->socid;
            $totalLinked++;
        }
    }
    if ($totalLinked > 1) {
        // split equally
        $split = $amount / $totalLinked;
        foreach ($payment->lines as $l) {
            $l->amount = $split;
        }
    }
    $payment->fk_soc = $fk_soc;

    $res = $payment->create($user);
    if ($res > 0) {
        setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$payment->id);
        exit;
    } else {
        setEventMessages($payment->error, $payment->errors, 'errors');
    }
}

if ($action === 'cancel' && $id > 0 && !empty($user->rights->expeditionpayment->cancel)) {
    if ($payment->fetch($id) > 0) {
        $res = $payment->cancel($user);
        if ($res > 0) {
            setEventMessages($langs->trans("Canceled"), null, 'mesgs');
        } else {
            setEventMessages($payment->error, $payment->errors, 'errors');
        }
    }
}

if ($id > 0) {
    $payment->fetch($id);
}

$help_url = '';
$title = $langs->trans('ExpeditionPaymentMenu');
llxHeader('', $title, $help_url);

print load_fiche_titre($title, '', 'payment');

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

if ($payment->id > 0) {
    print '<table class="border centpercent">';
    print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.dol_escape_htmltag($payment->ref).'</td></tr>';
    print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>'.$payment->fk_soc.'</td></tr>';
    print '<tr><td>'.$langs->trans("Date").'</td><td>'.dol_print_date($payment->datep, 'day').'</td></tr>';
    print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($payment->amount).'</td></tr>';
    print '<tr><td>'.$langs->trans("PaymentMode").'</td><td>'.dol_escape_htmltag($payment->mode).'</td></tr>';
    print '<tr><td>'.$langs->trans("Status").'</td><td>'.($payment->status == 9 ? $langs->trans("Canceled") : $langs->trans("Validated")).'</td></tr>';
    print '</table>';
    if ($payment->status != 9 && !empty($user->rights->expeditionpayment->cancel)) {
        $url = $_SERVER['PHP_SELF'].'?id='.$payment->id.'&action=cancel&token='.newToken();
        print '<br><a class="butActionDelete" href="'.$url.'">'.$langs->trans("Cancel").'</a>';
    }
}

print '</div>';

if (empty($payment->id) && !empty($user->rights->expeditionpayment->write)) {
    print '<div class="fichehalfright">';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border centpercent">';
    print '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$formcompany->select_company($prefillSocId, 'fk_soc', 's.fournisseur = 0', 1, 0, 0, array(), 0, 0, 0, 'minwidth200').'</td></tr>';
    print '<tr><td>'.$langs->trans("Date").'</td><td>'.$form->select_date('', 'datep', 0, 0, 0, '', 1, 1).'</td></tr>';
    print '<tr><td>'.$langs->trans("Amount").'</td><td><input class="flat" type="number" step="0.01" name="amount" required></td></tr>';
    print '<tr><td>'.$langs->trans("PaymentMode").'</td><td>'.$form->select_types_paiements('', 'mode', '1', 0, 1, 1, 0, 'minwidth150').'</td></tr>';
    print '<tr><td>'.$langs->trans("Numero").'</td><td><input class="flat" type="text" name="num_payment"></td></tr>';

    $explist = array();
    if ($expid > 0 && $prefillSocId > 0) {
        $explist[$expid] = $expedition->ref;
    } else {
        $sqlExp = "SELECT rowid, ref FROM ".$db->prefix()."expedition WHERE entity IN (".getEntity('expedition').") ORDER BY rowid DESC LIMIT 200";
        $resExp = $db->query($sqlExp);
        if ($resExp) {
            while ($obj = $db->fetch_object($resExp)) {
                $explist[$obj->rowid] = $obj->ref;
            }
        }
    }

    print '<tr><td>'.$langs->trans("Sendings").'</td><td>';
    print '<select name="expeditions[]" class="flat minwidth200" multiple size="5">';
    foreach ($explist as $k => $v) {
        $sel = ($expid > 0 && $expid == $k) ? ' selected' : '';
        print '<option value="'.((int) $k).'"'.$sel.'>'.dol_escape_htmltag($v).'</option>';
    }
    print '</select>';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Note").'</td><td><textarea class="flat quatrevingtpercent" name="note" rows="3"></textarea></td></tr>';

    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></div>';
    print '</form>';
    print '</div>';
}

print '</div>';

llxFooter();
$db->close();
