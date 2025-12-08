<?php
/**
 * Etat de livraison par livreur - avec export PDF/CSV
 * Format similaire à ATLASCOM
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
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/deliverymen/class/deliverymenshipment.class.php');

$langs->loadLangs(array('deliverymen@deliverymen', 'sendings', 'companies', 'orders', 'exports', 'bills'));

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

// Date range - start date
$search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
// Date range - end date
$search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));

// Defaults to today if not set
if (!$search_date_start && !$search_date_end) {
    $search_date_start = dol_mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $search_date_end = dol_mktime(23, 59, 59, date('m'), date('d'), date('Y'));
}

$form = new Form($db);

/*
 * Actions - Export
 */

if ($action == 'export_csv' || $action == 'export_pdf') {
    if ($search_deliveryman <= 0) {
        setEventMessages($langs->trans('ErrorSelectDeliveryman'), null, 'errors');
    } else {
        // Get deliveryman info
        $deliveryman = new User($db);
        $deliveryman->fetch($search_deliveryman);
        $deliverymanName = $deliveryman->getFullName($langs);

        // Get deliveries
        $deliveries = getDeliveriesWithDetails($db, $search_deliveryman, $search_date_start, $search_date_end);

        if ($action == 'export_csv') {
            exportCSV($deliveries, $deliverymanName, $search_date_start, $search_date_end);
        } else {
            exportPDF($deliveries, $deliverymanName, $search_date_start, $search_date_end);
        }
    }
}

/**
 * Get deliveries with full details
 */
function getDeliveriesWithDetails($db, $userid, $date_start, $date_end)
{
    global $conf, $langs;

    $deliveries = array();

    $sql = "SELECT ds.rowid, ds.fk_expedition, ds.delivery_date, ds.delivery_status,";
    $sql .= " ds.date_delivery_done, ds.note_delivery,";
    $sql .= " e.rowid as expedition_id, e.ref as expedition_ref, e.date_expedition, e.date_creation as expedition_date_creation,";
    $sql .= " e.fk_soc,";
    $sql .= " s.rowid as soc_id, s.code_client, s.nom as thirdparty_name, s.address, s.zip, s.town, s.phone,";
    $sql .= " c.rowid as order_id, c.ref as order_ref, c.total_ttc as order_total";
    $sql .= " FROM ".$db->prefix()."deliverymen_shipment as ds";
    $sql .= " LEFT JOIN ".$db->prefix()."expedition as e ON e.rowid = ds.fk_expedition";
    $sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = e.fk_soc";
    // Link expedition to order via element_element table
    $sql .= " LEFT JOIN ".$db->prefix()."element_element as el ON (el.fk_target = e.rowid AND el.targettype = 'shipping' AND el.sourcetype = 'commande')";
    $sql .= " LEFT JOIN ".$db->prefix()."commande as c ON c.rowid = el.fk_source";
    $sql .= " WHERE ds.fk_user_deliveryman = ".((int) $userid);
    $sql .= " AND ds.entity = ".((int) $conf->entity);

    if ($date_start) {
        $sql .= " AND ds.delivery_date >= '".dol_print_date($date_start, '%Y-%m-%d')."'";
    }
    if ($date_end) {
        $sql .= " AND ds.delivery_date <= '".dol_print_date($date_end, '%Y-%m-%d')."'";
    }

    $sql .= " ORDER BY ds.delivery_date ASC, e.ref ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            // Check if order is paid (via invoice payments)
            $isPaid = isOrderPaid($db, $obj->order_id);

            $deliveries[] = array(
                'id' => $obj->rowid,
                'expedition_id' => $obj->expedition_id,
                'expedition_ref' => $obj->expedition_ref,
                'expedition_date' => $db->jdate($obj->expedition_date_creation),
                'order_ref' => $obj->order_ref,
                'soc_id' => $obj->soc_id,
                'code_client' => $obj->code_client,
                'thirdparty_name' => $obj->thirdparty_name,
                'address' => $obj->address,
                'zip' => $obj->zip,
                'town' => $obj->town,
                'phone' => $obj->phone,
                'delivery_date' => $db->jdate($obj->delivery_date),
                'date_delivery_done' => $db->jdate($obj->date_delivery_done),
                'delivery_status' => $obj->delivery_status,
                'status_label' => DeliverymenShipment::getStatusLabel($obj->delivery_status),
                'note' => $obj->note_delivery,
                'order_total' => $obj->order_total,
                'is_paid' => $isPaid
            );
        }
    }

    return $deliveries;
}

/**
 * Check if order is paid (through order payments or invoices)
 */
function isOrderPaid($db, $order_id)
{
    if (empty($order_id)) return false;

    // Get order total
    $sql = "SELECT total_ttc FROM ".$db->prefix()."commande WHERE rowid = ".((int) $order_id);
    $resql = $db->query($sql);
    if (!$resql) return false;
    $objOrder = $db->fetch_object($resql);
    if (!$objOrder) return false;
    $orderTotal = $objOrder->total_ttc;

    // 1. Check order payments (from orderpayment module) if available
    if (isModEnabled('orderpayment')) {
        dol_include_once('/orderpayment/class/orderpayment.class.php');
        if (class_exists('OrderPayment')) {
            $orderpayment = new OrderPayment($db);
            $paidOnOrder = $orderpayment->getSumPaymentsForOrder($order_id);
            if ($paidOnOrder >= $orderTotal - 0.01) {
                return true;
            }
        }
    }

    // 2. Check if there's an invoice linked to this order and if it's paid
    $sql2 = "SELECT f.rowid, f.paye, f.total_ttc FROM ".$db->prefix()."facture as f";
    $sql2 .= " INNER JOIN ".$db->prefix()."element_element as el ON el.fk_target = f.rowid";
    $sql2 .= " WHERE el.sourcetype = 'commande' AND el.targettype = 'facture'";
    $sql2 .= " AND el.fk_source = ".((int) $order_id);

    $resql2 = $db->query($sql2);
    if ($resql2) {
        while ($objInv = $db->fetch_object($resql2)) {
            // If invoice is marked as paid
            if ($objInv->paye == 1) {
                return true;
            }
        }
    }

    // 3. Check payments on linked invoices
    $sql3 = "SELECT SUM(pf.amount) as total_paid FROM ".$db->prefix()."paiement_facture as pf";
    $sql3 .= " INNER JOIN ".$db->prefix()."facture as f ON f.rowid = pf.fk_facture";
    $sql3 .= " INNER JOIN ".$db->prefix()."element_element as el ON el.fk_target = f.rowid";
    $sql3 .= " WHERE el.sourcetype = 'commande' AND el.targettype = 'facture'";
    $sql3 .= " AND el.fk_source = ".((int) $order_id);

    $resql3 = $db->query($sql3);
    if ($resql3) {
        $objPaid = $db->fetch_object($resql3);
        if ($objPaid && $objPaid->total_paid >= $orderTotal - 0.01) {
            return true;
        }
    }

    return false;
}

/**
 * Export to CSV
 */
function exportCSV($deliveries, $deliverymanName, $date_start, $date_end)
{
    global $langs, $conf;

    $dateStr = dol_print_date($date_start, '%Y%m%d');
    if ($date_end && dol_print_date($date_end, '%Y%m%d') != $dateStr) {
        $dateStr .= '_'.dol_print_date($date_end, '%Y%m%d');
    }

    $filename = 'etat_livreur_'.dol_sanitizeFileName($deliverymanName).'_'.$dateStr.'.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $output = fopen('php://output', 'w');

    // BOM for Excel UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header row
    fputcsv($output, array(
        'Doc',
        'N° Bon',
        'Date',
        'Code',
        'Client',
        'Montant TTC',
        'Date Livraison',
        'Réglé'
    ), ';');

    // Deliveryman name row
    fputcsv($output, array($deliverymanName), ';');

    // Data
    $total = 0;
    foreach ($deliveries as $delivery) {
        fputcsv($output, array(
            'BL',
            $delivery['expedition_ref'],
            dol_print_date($delivery['expedition_date'], '%d/%m/%Y %H:%M'),
            $delivery['code_client'],
            $delivery['thirdparty_name'],
            number_format($delivery['order_total'], 2, ',', ' '),
            $delivery['date_delivery_done'] ? dol_print_date($delivery['date_delivery_done'], '%d/%m/%Y %H:%M') : '',
            $delivery['is_paid'] ? 'Oui' : ''
        ), ';');
        $total += $delivery['order_total'];
    }

    // Total row
    fputcsv($output, array('', '', '', '', 'TOTAL', number_format($total, 2, ',', ' '), '', ''), ';');

    fclose($output);
    exit;
}

/**
 * Export to PDF - Format ATLASCOM
 */
function exportPDF($deliveries, $deliverymanName, $date_start, $date_end)
{
    global $conf, $langs, $db, $mysoc;

    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

    $dateStr = dol_print_date($date_start, '%Y%m%d');
    if ($date_end && dol_print_date($date_end, '%Y%m%d') != $dateStr) {
        $dateStr .= '_'.dol_print_date($date_end, '%Y%m%d');
    }

    $filename = 'etat_livreur_'.dol_sanitizeFileName($deliverymanName).'_'.$dateStr.'.pdf';

    // Create PDF - Landscape A4
    $pdf = pdf_getInstance('', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Dolibarr');
    $pdf->SetAuthor($mysoc->name);
    $pdf->SetTitle('ETAT DE LIVRAISON PAR LIVREUR');
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 30);

    $pdf->AddPage('L'); // Landscape

    $pageWidth = $pdf->getPageWidth();
    $pageHeight = $pdf->getPageHeight();

    // Header - Company name left, Date right
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(140, 6, $mysoc->name, 0, 0, 'L');

    // City and date on right
    $cityDate = ($mysoc->town ? $mysoc->town : 'CASABLANCA').' le, '.dol_print_date($date_start, '%d/%m/%Y');
    if ($date_end && dol_print_date($date_end, '%d/%m/%Y') != dol_print_date($date_start, '%d/%m/%Y')) {
        $cityDate = ($mysoc->town ? $mysoc->town : 'CASABLANCA').' du '.dol_print_date($date_start, '%d/%m/%Y').' au '.dol_print_date($date_end, '%d/%m/%Y');
    }
    $pdf->Cell(0, 6, $cityDate, 0, 1, 'R');

    $pdf->Ln(5);

    // Title centered
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'E T A T   D E   L I V R A I S O N   P A R   L I V R E U R', 0, 1, 'C');

    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(0, 0, 0);

    // Column widths for landscape A4 (277mm total usable)
    $colWidths = array(
        'doc' => 15,
        'numbon' => 30,
        'date' => 40,
        'code' => 25,
        'client' => 70,
        'montant' => 30,
        'datelivr' => 40,
        'regle' => 27
    );

    // Header row
    $pdf->Cell($colWidths['doc'], 7, 'Doc', 1, 0, 'C');
    $pdf->Cell($colWidths['numbon'], 7, 'N° Bon', 1, 0, 'C');
    $pdf->Cell($colWidths['date'], 7, 'Date', 1, 0, 'C');
    $pdf->Cell($colWidths['code'], 7, 'Code', 1, 0, 'C');
    $pdf->Cell($colWidths['client'], 7, 'Client', 1, 0, 'C');
    $pdf->Cell($colWidths['montant'], 7, 'Montant TTC', 1, 0, 'C');
    $pdf->Cell($colWidths['datelivr'], 7, 'Date Livraison', 1, 0, 'C');
    $pdf->Cell($colWidths['regle'], 7, 'Réglé', 1, 1, 'C');

    // Deliveryman name row (bold, spanning)
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $totalWidth = array_sum($colWidths);
    $pdf->Cell($totalWidth, 6, strtoupper($deliverymanName), 1, 1, 'L', true);

    // Data rows
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $total = 0;
    $lineCount = 0;
    foreach ($deliveries as $delivery) {
        // Check if we need a new page
        if ($pdf->GetY() > $pageHeight - 50) {
            // Add visa boxes before new page
            addVisaBoxes($pdf, $total);

            $pdf->AddPage('L');
            $total = 0;

            // Repeat header on new page
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colWidths['doc'], 7, 'Doc', 1, 0, 'C');
            $pdf->Cell($colWidths['numbon'], 7, 'N° Bon', 1, 0, 'C');
            $pdf->Cell($colWidths['date'], 7, 'Date', 1, 0, 'C');
            $pdf->Cell($colWidths['code'], 7, 'Code', 1, 0, 'C');
            $pdf->Cell($colWidths['client'], 7, 'Client', 1, 0, 'C');
            $pdf->Cell($colWidths['montant'], 7, 'Montant TTC', 1, 0, 'C');
            $pdf->Cell($colWidths['datelivr'], 7, 'Date Livraison', 1, 0, 'C');
            $pdf->Cell($colWidths['regle'], 7, 'Réglé', 1, 1, 'C');

            // Deliveryman name row
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($totalWidth, 6, strtoupper($deliverymanName).' (suite)', 1, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(255, 255, 255);
        }

        $pdf->Cell($colWidths['doc'], 6, 'BL', 1, 0, 'C');
        $pdf->Cell($colWidths['numbon'], 6, $delivery['expedition_ref'], 1, 0, 'L');
        $pdf->Cell($colWidths['date'], 6, dol_print_date($delivery['expedition_date'], '%d/%m/%Y %H:%M'), 1, 0, 'C');
        $pdf->Cell($colWidths['code'], 6, $delivery['code_client'], 1, 0, 'C');
        $pdf->Cell($colWidths['client'], 6, dol_trunc($delivery['thirdparty_name'], 45), 1, 0, 'L');
        $pdf->Cell($colWidths['montant'], 6, number_format($delivery['order_total'], 2, ',', ' '), 1, 0, 'R');
        $pdf->Cell($colWidths['datelivr'], 6, $delivery['date_delivery_done'] ? dol_print_date($delivery['date_delivery_done'], '%d/%m/%Y %H:%M') : '', 1, 0, 'C');
        $pdf->Cell($colWidths['regle'], 6, $delivery['is_paid'] ? 'X' : '', 1, 1, 'C');

        $total += $delivery['order_total'];
        $lineCount++;
    }

    // Total row
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($colWidths['doc'] + $colWidths['numbon'] + $colWidths['date'] + $colWidths['code'] + $colWidths['client'], 7, 'TOTAL', 0, 0, 'R');
    $pdf->Cell($colWidths['montant'], 7, number_format($total, 2, ',', ' '), 0, 0, 'R');
    $pdf->Cell($colWidths['datelivr'] + $colWidths['regle'], 7, '', 0, 1);

    // Add visa boxes at bottom
    addVisaBoxes($pdf, $total);

    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Dolibarr - Page '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(), 0, 0, 'L');

    // Output
    $pdf->Output($filename, 'D');
    exit;
}

/**
 * Add visa boxes at bottom of PDF
 */
function addVisaBoxes(&$pdf, $total)
{
    $pageHeight = $pdf->getPageHeight();
    $boxWidth = 60;
    $boxHeight = 25;
    $startY = $pageHeight - 45;
    $margin = 10;
    $spacing = 9;

    $pdf->SetY($startY);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetDrawColor(0, 0, 0);

    // Horizontal line above boxes
    $pdf->Line($margin, $startY, $pdf->getPageWidth() - $margin, $startY);

    $pdf->Ln(3);

    $labels = array('VISA MAGASINIER', 'VISA CONTROLEUR', 'VISA LIVREUR', 'VISA COMPTABILITE');

    $startX = $margin;
    foreach ($labels as $label) {
        $pdf->SetXY($startX, $startY + 3);
        $pdf->Cell($boxWidth, 5, $label, 0, 0, 'C');
        $pdf->Rect($startX, $startY + 8, $boxWidth, $boxHeight);
        $startX += $boxWidth + $spacing;
    }
}

/*
 * View
 */

$title = $langs->trans('DailyReport');
llxHeader('', $title);

print load_fiche_titre($langs->trans('DailyReport').' - '.$langs->trans('Deliverymen'), '', 'calendar');

// Filter form
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="formfilter">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div class="fichecenter">';
print '<table class="border centpercent">';

// Deliveryman selection
print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Deliveryman").'</td><td>';
$deliverymen = DeliverymenShipment::getAllDeliverymen($db);
$options = array();
foreach ($deliverymen as $dm) {
    $options[$dm['id']] = $dm['fullname'] ? $dm['fullname'] : $dm['login'];
}
print $form->selectarray('search_deliveryman', $options, $search_deliveryman, 1, 0, 0, '', 0, 0, 0, '', 'minwidth200');
print '</td></tr>';

// Date range - start
print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, 'filter', 1, 1);
print '</td></tr>';

// Date range - end
print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, 'filter', 1, 1);
print '</td></tr>';

print '</table>';
print '</div>';

print '<div class="center" style="margin-top: 15px; margin-bottom: 15px;">';
print '<input type="submit" class="button" value="'.$langs->trans('Search').'">';
print ' &nbsp; ';
print '<button type="submit" class="button" name="action" value="export_csv">'.img_picto('', 'download').' '.$langs->trans('ExportCSV').'</button>';
print ' &nbsp; ';
print '<button type="submit" class="button" name="action" value="export_pdf">'.img_picto('', 'pdf').' '.$langs->trans('ExportPDF').'</button>';
print '</div>';

print '</form>';

// Display results if deliveryman selected
if ($search_deliveryman > 0) {
    $deliveryman = new User($db);
    $deliveryman->fetch($search_deliveryman);

    $deliveries = getDeliveriesWithDetails($db, $search_deliveryman, $search_date_start, $search_date_end);

    print '<br>';

    $titlePeriod = '';
    if ($search_date_start && $search_date_end) {
        if (dol_print_date($search_date_start, '%Y%m%d') == dol_print_date($search_date_end, '%Y%m%d')) {
            $titlePeriod = dol_print_date($search_date_start, 'daytext');
        } else {
            $titlePeriod = $langs->trans('From').' '.dol_print_date($search_date_start, 'day').' '.$langs->trans('To').' '.dol_print_date($search_date_end, 'day');
        }
    }

    print load_fiche_titre(
        $langs->trans('DeliveriesFor').' '.$deliveryman->getFullName($langs).($titlePeriod ? ' - '.$titlePeriod : ''),
        '',
        ''
    );

    if (count($deliveries) > 0) {
        print '<div class="div-table-responsive">';
        print '<table class="tagtable nobottomiftotal liste">';

        // Header
        print '<tr class="liste_titre">';
        print '<th>Doc</th>';
        print '<th>N° Bon</th>';
        print '<th>'.$langs->trans('Date').'</th>';
        print '<th>Code</th>';
        print '<th>'.$langs->trans('Customer').'</th>';
        print '<th class="right">'.$langs->trans('AmountTTC').'</th>';
        print '<th class="center">'.$langs->trans('DateDelivery').'</th>';
        print '<th class="center">'.$langs->trans('Paid').'</th>';
        print '</tr>';

        $total = 0;

        foreach ($deliveries as $delivery) {
            print '<tr class="oddeven">';

            // Doc type
            print '<td>BL</td>';

            // N° Bon (expedition ref)
            print '<td>';
            print '<a href="'.DOL_URL_ROOT.'/expedition/card.php?id='.$delivery['expedition_id'].'">'.$delivery['expedition_ref'].'</a>';
            print '</td>';

            // Date
            print '<td>'.dol_print_date($delivery['expedition_date'], 'dayhour').'</td>';

            // Code client
            print '<td>'.$delivery['code_client'].'</td>';

            // Client
            print '<td>';
            print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$delivery['soc_id'].'">'.$delivery['thirdparty_name'].'</a>';
            print '</td>';

            // Montant TTC
            print '<td class="right">'.price($delivery['order_total'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';

            // Date livraison
            print '<td class="center">';
            if ($delivery['date_delivery_done']) {
                print dol_print_date($delivery['date_delivery_done'], 'dayhour');
            }
            print '</td>';

            // Réglé
            print '<td class="center">';
            if ($delivery['is_paid']) {
                print img_picto($langs->trans('Paid'), 'tick');
            }
            print '</td>';

            print '</tr>';

            $total += $delivery['order_total'];
        }

        // Total row
        print '<tr class="liste_total">';
        print '<td colspan="5">'.$langs->trans('Total').' ('.count($deliveries).' '.$langs->trans('Deliveries').')</td>';
        print '<td class="right">'.price($total, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '<td colspan="2"></td>';
        print '</tr>';

        print '</table>';
        print '</div>';

    } else {
        print '<div class="opacitymedium center" style="padding: 20px;">';
        print $langs->trans('NoDeliveriesForThisDate');
        print '</div>';
    }
}

llxFooter();
$db->close();
