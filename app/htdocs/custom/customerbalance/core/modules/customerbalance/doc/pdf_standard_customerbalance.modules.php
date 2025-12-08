<?php
/**
 * PDF Generator for Customer Financial Status
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

class pdf_standard_customerbalance
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string Error message
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var string Module name
     */
    public $name;

    /**
     * @var string Module description
     */
    public $description;

    /**
     * @var int Page width
     */
    public $page_largeur;

    /**
     * @var int Page height
     */
    public $page_hauteur;

    /**
     * @var array Format
     */
    public $format;

    /**
     * @var int Left margin
     */
    public $marge_gauche;

    /**
     * @var int Right margin
     */
    public $marge_droite;

    /**
     * @var int Top margin
     */
    public $marge_haute;

    /**
     * @var int Bottom margin
     */
    public $marge_basse;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = "standard_customerbalance";
        $this->description = $langs->trans('PDFCustomerBalance');

        // Page format
        $this->format = 'A4';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->marge_gauche = 10;
        $this->marge_droite = 10;
        $this->marge_haute = 10;
        $this->marge_basse = 10;
    }

    /**
     * Generate PDF file
     *
     * @param CustomerBalance $object Customer balance object
     * @param Translate $outputlangs Output language
     * @return int 1 if OK, 0 if KO
     */
    public function write_file($object, $outputlangs)
    {
        global $conf, $langs, $mysoc;

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }

        $outputlangs->loadLangs(array('customerbalance@customerbalance', 'main', 'companies', 'bills', 'dict'));

        // Definition of $dir and $file
        $dir = $conf->customerbalance->dir_output;
        if (!is_dir($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = $outputlangs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }

        $filename = 'etat_financier_'.$object->socid.'_'.dol_sanitizeFileName($object->thirdparty->name).'_'.date('Ymd_His').'.pdf';
        $file = $dir.'/'.$filename;

        // Create PDF
        $pdf = pdf_getInstance($this->format);
        $pdf->SetAutoPageBreak(1, 0);

        $heightforinfotot = 50;
        $heightforfreetext = 0;
        $heightforfooter = $this->marge_basse + 8;

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        $pdf->Open();
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetTitle($outputlangs->convToOutputCharset($outputlangs->transnoentities("FinancialStatus").' - '.$object->thirdparty->name));
        $pdf->SetSubject($outputlangs->convToOutputCharset($outputlangs->transnoentities("FinancialStatus")));
        $pdf->SetCreator("Dolibarr ".DOL_VERSION);
        $pdf->SetAuthor($outputlangs->convToOutputCharset($mysoc->name));
        $pdf->SetKeywords($outputlangs->convToOutputCharset($object->thirdparty->name));

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

        // New page
        $pdf->AddPage();
        $pagenb = 1;

        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $posy = $this->marge_haute;

        // Header
        $posy = $this->_pagehead($pdf, $object, $outputlangs, $posy);

        // Summary section
        $posy = $this->_printSummary($pdf, $object, $outputlangs, $posy);

        // Outstanding section
        $posy = $this->_printOutstanding($pdf, $object, $outputlangs, $posy);

        // Unpaid invoices
        $posy = $this->_printUnpaidInvoices($pdf, $object, $outputlangs, $posy, $pagenb);

        // Recent payments
        $posy = $this->_printRecentPayments($pdf, $object, $outputlangs, $posy, $pagenb);

        // Credit notes
        $posy = $this->_printCreditNotes($pdf, $object, $outputlangs, $posy, $pagenb);

        // Customer returns
        if (isModEnabled('customerreturn')) {
            $posy = $this->_printCustomerReturns($pdf, $object, $outputlangs, $posy, $pagenb);
        }

        // Footer
        $this->_pagefoot($pdf, $object, $outputlangs, $pagenb);

        // Close and output PDF
        $pdf->Close();

        $pdf->Output($file, 'F');

        dolChmod($file);

        return 1;
    }

    /**
     * Page header
     */
    protected function _pagehead(&$pdf, $object, $outputlangs, $posy)
    {
        global $conf, $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Logo
        $logo = $conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
        if ($mysoc->logo && is_readable($logo)) {
            $height = pdf_getHeightForLogo($logo);
            $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);
            $posy += $height + 5;
        } else {
            $pdf->SetFont('', 'B', $default_font_size + 3);
            $pdf->SetXY($this->marge_gauche, $posy);
            $pdf->MultiCell(100, 5, $outputlangs->convToOutputCharset($mysoc->name), 0, 'L');
            $posy += 10;
        }

        // Title
        $pdf->SetFont('', 'B', $default_font_size + 4);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 8, $outputlangs->transnoentities("FinancialStatus"), 0, 'C');
        $posy += 10;

        // Customer name
        $pdf->SetFont('', 'B', $default_font_size + 2);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6, $outputlangs->convToOutputCharset($object->thirdparty->name), 0, 'C');
        $posy += 8;

        // Customer code and date
        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 5,
            $outputlangs->transnoentities("CustomerCode").': '.$object->thirdparty->code_client.' - '.
            $outputlangs->transnoentities("Date").': '.dol_print_date(dol_now(), 'day'), 0, 'C');
        $posy += 10;

        // Separator line
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line($this->marge_gauche, $posy, $this->page_largeur - $this->marge_droite, $posy);
        $posy += 5;

        return $posy;
    }

    /**
     * Print financial summary
     */
    protected function _printSummary(&$pdf, $object, $outputlangs, $posy)
    {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $summary = $object->getSummary();

        // Section title
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6,
            $outputlangs->transnoentities("FinancialSummary"), 0, 'L', true);
        $posy += 8;

        $col1 = $this->marge_gauche;
        $col2 = 120;
        $lineHeight = 5;

        $pdf->SetFont('', '', $default_font_size - 1);

        // Total invoiced
        $pdf->SetXY($col1, $posy);
        $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("TotalInvoiced").' ('.$summary['nb_invoices'].' '.$outputlangs->transnoentities("Invoices").')', 0, 'L');
        $pdf->SetXY($col2, $posy);
        $pdf->MultiCell(70, $lineHeight, price($summary['total_invoiced'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
        $posy += $lineHeight;

        // Total paid
        $pdf->SetTextColor(0, 128, 0);
        $pdf->SetXY($col1, $posy);
        $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("TotalPaid").' ('.$summary['nb_payments'].' '.$outputlangs->transnoentities("Payments").')', 0, 'L');
        $pdf->SetXY($col2, $posy);
        $pdf->MultiCell(70, $lineHeight, price($summary['total_paid'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
        $posy += $lineHeight;

        // Total credit notes
        $pdf->SetTextColor(255, 140, 0);
        $pdf->SetXY($col1, $posy);
        $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("TotalCreditNotes").' ('.$summary['nb_creditnotes'].')', 0, 'L');
        $pdf->SetXY($col2, $posy);
        $pdf->MultiCell(70, $lineHeight, price($summary['total_creditnotes'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
        $posy += $lineHeight;

        // Total returns
        if (isModEnabled('customerreturn') && $summary['nb_returns'] > 0) {
            $pdf->SetXY($col1, $posy);
            $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("TotalReturns").' ('.$summary['nb_returns'].')', 0, 'L');
            $pdf->SetXY($col2, $posy);
            $pdf->MultiCell(70, $lineHeight, price($summary['total_returns'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
            $posy += $lineHeight;
        }

        $pdf->SetTextColor(0, 0, 0);

        // Separator
        $posy += 2;
        $pdf->Line($col1, $posy, $this->page_largeur - $this->marge_droite, $posy);
        $posy += 2;

        // Balance
        $pdf->SetFont('', 'B', $default_font_size);
        if ($summary['balance'] > 0) {
            $pdf->SetTextColor(255, 0, 0);
        } else {
            $pdf->SetTextColor(0, 128, 0);
        }
        $pdf->SetXY($col1, $posy);
        $pdf->MultiCell(100, 6, $outputlangs->transnoentities("Balance").' ('.$outputlangs->transnoentities("RemainToPay").')', 0, 'L');
        $pdf->SetXY($col2, $posy);
        $pdf->MultiCell(70, 6, price($summary['balance'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
        $posy += 10;

        $pdf->SetTextColor(0, 0, 0);

        return $posy;
    }

    /**
     * Print outstanding section
     */
    protected function _printOutstanding(&$pdf, $object, $outputlangs, $posy)
    {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $summary = $object->getSummary();

        // Section title
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6,
            $outputlangs->transnoentities("OutstandingBill"), 0, 'L', true);
        $posy += 8;

        $col1 = $this->marge_gauche;
        $col2 = 120;
        $lineHeight = 5;

        $pdf->SetFont('', '', $default_font_size - 1);

        // Outstanding limit
        $pdf->SetXY($col1, $posy);
        $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("OutstandingLimit"), 0, 'L');
        $pdf->SetXY($col2, $posy);
        if ($summary['outstanding'] > 0) {
            $pdf->MultiCell(70, $lineHeight, price($summary['outstanding'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
        } else {
            $pdf->MultiCell(70, $lineHeight, $outputlangs->transnoentities("NoLimit"), 0, 'R');
        }
        $posy += $lineHeight;

        // Outstanding used
        $pdf->SetXY($col1, $posy);
        $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("OutstandingUsed"), 0, 'L');
        $pdf->SetXY($col2, $posy);
        $pdf->MultiCell(70, $lineHeight, price($summary['outstanding_used'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
        $posy += $lineHeight;

        // Outstanding available
        if ($summary['outstanding'] > 0) {
            if ($summary['outstanding_available'] >= 0) {
                $pdf->SetTextColor(0, 128, 0);
            } else {
                $pdf->SetTextColor(255, 0, 0);
            }
            $pdf->SetXY($col1, $posy);
            $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("OutstandingAvailable"), 0, 'L');
            $pdf->SetXY($col2, $posy);
            $pdf->MultiCell(70, $lineHeight, price($summary['outstanding_available'], 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
            $posy += $lineHeight;

            // Usage percentage
            $percent = min(100, ($summary['outstanding_used'] / $summary['outstanding']) * 100);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($col1, $posy);
            $pdf->MultiCell(100, $lineHeight, $outputlangs->transnoentities("Usage"), 0, 'L');
            $pdf->SetXY($col2, $posy);
            $pdf->MultiCell(70, $lineHeight, round($percent, 1).'%', 0, 'R');
            $posy += $lineHeight;
        }

        $pdf->SetTextColor(0, 0, 0);
        $posy += 5;

        return $posy;
    }

    /**
     * Print unpaid invoices table
     */
    protected function _printUnpaidInvoices(&$pdf, $object, $outputlangs, $posy, &$pagenb)
    {
        global $conf;

        $unpaidInvoices = $object->getUnpaidInvoices();
        if (empty($unpaidInvoices)) {
            return $posy;
        }

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Check if we need a new page
        if ($posy > 240) {
            $pdf->AddPage();
            $pagenb++;
            $posy = $this->marge_haute;
        }

        // Section title
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6,
            $outputlangs->transnoentities("UnpaidInvoices").' ('.count($unpaidInvoices).')', 0, 'L', true);
        $posy += 7;

        // Table header
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->SetFillColor(240, 240, 240);

        $cols = array(
            array('x' => $this->marge_gauche, 'w' => 30, 'label' => $outputlangs->transnoentities("Ref")),
            array('x' => 40, 'w' => 25, 'label' => $outputlangs->transnoentities("Date")),
            array('x' => 65, 'w' => 25, 'label' => $outputlangs->transnoentities("DateDue")),
            array('x' => 90, 'w' => 35, 'label' => $outputlangs->transnoentities("AmountTTC")),
            array('x' => 125, 'w' => 35, 'label' => $outputlangs->transnoentities("AlreadyPaid")),
            array('x' => 160, 'w' => 40, 'label' => $outputlangs->transnoentities("RemainderToPay"))
        );

        foreach ($cols as $col) {
            $pdf->SetXY($col['x'], $posy);
            $pdf->MultiCell($col['w'], 5, $col['label'], 1, 'C', true);
        }
        $posy += 5;

        // Table data
        $pdf->SetFont('', '', $default_font_size - 2);
        $now = dol_now();

        foreach ($unpaidInvoices as $invoice) {
            if ($posy > 270) {
                $pdf->AddPage();
                $pagenb++;
                $posy = $this->marge_haute;
            }

            $isOverdue = ($invoice['date_due'] && $invoice['date_due'] < $now);

            $pdf->SetXY($cols[0]['x'], $posy);
            $pdf->MultiCell($cols[0]['w'], 5, $invoice['ref'], 1, 'L');

            $pdf->SetXY($cols[1]['x'], $posy);
            $pdf->MultiCell($cols[1]['w'], 5, dol_print_date($invoice['date'], 'day'), 1, 'C');

            $pdf->SetXY($cols[2]['x'], $posy);
            $datedue = $invoice['date_due'] ? dol_print_date($invoice['date_due'], 'day') : '';
            $pdf->MultiCell($cols[2]['w'], 5, $datedue, 1, 'C');

            $pdf->SetXY($cols[3]['x'], $posy);
            $pdf->MultiCell($cols[3]['w'], 5, price($invoice['total_ttc'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');

            $pdf->SetTextColor(0, 128, 0);
            $pdf->SetXY($cols[4]['x'], $posy);
            $pdf->MultiCell($cols[4]['w'], 5, price($invoice['paid'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');

            if ($isOverdue) {
                $pdf->SetTextColor(255, 0, 0);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->SetXY($cols[5]['x'], $posy);
            $pdf->MultiCell($cols[5]['w'], 5, price($invoice['remain'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');

            $pdf->SetTextColor(0, 0, 0);
            $posy += 5;
        }

        $posy += 5;
        return $posy;
    }

    /**
     * Print recent payments
     */
    protected function _printRecentPayments(&$pdf, $object, $outputlangs, $posy, &$pagenb)
    {
        global $conf;

        if (empty($object->payments)) {
            return $posy;
        }

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Check if we need a new page
        if ($posy > 240) {
            $pdf->AddPage();
            $pagenb++;
            $posy = $this->marge_haute;
        }

        // Section title
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6,
            $outputlangs->transnoentities("RecentPayments").' ('.count($object->payments).')', 0, 'L', true);
        $posy += 7;

        // Table header
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->SetFillColor(240, 240, 240);

        $cols = array(
            array('x' => $this->marge_gauche, 'w' => 40, 'label' => $outputlangs->transnoentities("Ref")),
            array('x' => 50, 'w' => 30, 'label' => $outputlangs->transnoentities("Date")),
            array('x' => 80, 'w' => 50, 'label' => $outputlangs->transnoentities("PaymentMode")),
            array('x' => 130, 'w' => 30, 'label' => $outputlangs->transnoentities("Numero")),
            array('x' => 160, 'w' => 40, 'label' => $outputlangs->transnoentities("Amount"))
        );

        foreach ($cols as $col) {
            $pdf->SetXY($col['x'], $posy);
            $pdf->MultiCell($col['w'], 5, $col['label'], 1, 'C', true);
        }
        $posy += 5;

        // Table data (max 10 payments)
        $pdf->SetFont('', '', $default_font_size - 2);
        $maxPayments = 10;
        $i = 0;

        foreach ($object->payments as $payment) {
            if ($i >= $maxPayments) {
                break;
            }
            if ($posy > 270) {
                $pdf->AddPage();
                $pagenb++;
                $posy = $this->marge_haute;
            }

            $pdf->SetXY($cols[0]['x'], $posy);
            $pdf->MultiCell($cols[0]['w'], 5, $payment['ref'], 1, 'L');

            $pdf->SetXY($cols[1]['x'], $posy);
            $pdf->MultiCell($cols[1]['w'], 5, dol_print_date($payment['date'], 'day'), 1, 'C');

            $pdf->SetXY($cols[2]['x'], $posy);
            $pdf->MultiCell($cols[2]['w'], 5, $payment['payment_label'], 1, 'L');

            $pdf->SetXY($cols[3]['x'], $posy);
            $pdf->MultiCell($cols[3]['w'], 5, $payment['num_paiement'], 1, 'C');

            $pdf->SetTextColor(0, 128, 0);
            $pdf->SetXY($cols[4]['x'], $posy);
            $pdf->MultiCell($cols[4]['w'], 5, price($payment['amount'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');
            $pdf->SetTextColor(0, 0, 0);

            $posy += 5;
            $i++;
        }

        $posy += 5;
        return $posy;
    }

    /**
     * Print credit notes
     */
    protected function _printCreditNotes(&$pdf, $object, $outputlangs, $posy, &$pagenb)
    {
        global $conf;

        if (empty($object->creditnotes)) {
            return $posy;
        }

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Check if we need a new page
        if ($posy > 250) {
            $pdf->AddPage();
            $pagenb++;
            $posy = $this->marge_haute;
        }

        // Section title
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6,
            $outputlangs->transnoentities("CreditNotes").' ('.count($object->creditnotes).')', 0, 'L', true);
        $posy += 7;

        // Table header
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->SetFillColor(240, 240, 240);

        $cols = array(
            array('x' => $this->marge_gauche, 'w' => 50, 'label' => $outputlangs->transnoentities("Ref")),
            array('x' => 60, 'w' => 40, 'label' => $outputlangs->transnoentities("Date")),
            array('x' => 100, 'w' => 50, 'label' => $outputlangs->transnoentities("AmountHT")),
            array('x' => 150, 'w' => 50, 'label' => $outputlangs->transnoentities("AmountTTC"))
        );

        foreach ($cols as $col) {
            $pdf->SetXY($col['x'], $posy);
            $pdf->MultiCell($col['w'], 5, $col['label'], 1, 'C', true);
        }
        $posy += 5;

        // Table data
        $pdf->SetFont('', '', $default_font_size - 2);

        foreach ($object->creditnotes as $creditnote) {
            if ($posy > 270) {
                $pdf->AddPage();
                $pagenb++;
                $posy = $this->marge_haute;
            }

            $pdf->SetXY($cols[0]['x'], $posy);
            $pdf->MultiCell($cols[0]['w'], 5, $creditnote['ref'], 1, 'L');

            $pdf->SetXY($cols[1]['x'], $posy);
            $pdf->MultiCell($cols[1]['w'], 5, dol_print_date($creditnote['date'], 'day'), 1, 'C');

            $pdf->SetXY($cols[2]['x'], $posy);
            $pdf->MultiCell($cols[2]['w'], 5, price($creditnote['total_ht'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');

            $pdf->SetTextColor(255, 140, 0);
            $pdf->SetXY($cols[3]['x'], $posy);
            $pdf->MultiCell($cols[3]['w'], 5, price($creditnote['total_ttc'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');
            $pdf->SetTextColor(0, 0, 0);

            $posy += 5;
        }

        $posy += 5;
        return $posy;
    }

    /**
     * Print customer returns
     */
    protected function _printCustomerReturns(&$pdf, $object, $outputlangs, $posy, &$pagenb)
    {
        global $conf;

        if (empty($object->customerreturns)) {
            return $posy;
        }

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Check if we need a new page
        if ($posy > 250) {
            $pdf->AddPage();
            $pagenb++;
            $posy = $this->marge_haute;
        }

        // Section title
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6,
            $outputlangs->transnoentities("CustomerReturns").' ('.count($object->customerreturns).')', 0, 'L', true);
        $posy += 7;

        // Table header
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->SetFillColor(240, 240, 240);

        $cols = array(
            array('x' => $this->marge_gauche, 'w' => 50, 'label' => $outputlangs->transnoentities("Ref")),
            array('x' => 60, 'w' => 40, 'label' => $outputlangs->transnoentities("Date")),
            array('x' => 100, 'w' => 50, 'label' => $outputlangs->transnoentities("AmountHT")),
            array('x' => 150, 'w' => 50, 'label' => $outputlangs->transnoentities("AmountTTC"))
        );

        foreach ($cols as $col) {
            $pdf->SetXY($col['x'], $posy);
            $pdf->MultiCell($col['w'], 5, $col['label'], 1, 'C', true);
        }
        $posy += 5;

        // Table data
        $pdf->SetFont('', '', $default_font_size - 2);

        foreach ($object->customerreturns as $return) {
            if ($posy > 270) {
                $pdf->AddPage();
                $pagenb++;
                $posy = $this->marge_haute;
            }

            $pdf->SetXY($cols[0]['x'], $posy);
            $pdf->MultiCell($cols[0]['w'], 5, $return['ref'], 1, 'L');

            $pdf->SetXY($cols[1]['x'], $posy);
            $pdf->MultiCell($cols[1]['w'], 5, dol_print_date($return['date'], 'day'), 1, 'C');

            $pdf->SetXY($cols[2]['x'], $posy);
            $pdf->MultiCell($cols[2]['w'], 5, price($return['total_ht'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');

            $pdf->SetTextColor(255, 140, 0);
            $pdf->SetXY($cols[3]['x'], $posy);
            $pdf->MultiCell($cols[3]['w'], 5, price($return['total_ttc'], 0, $outputlangs, 1, -1, -1, $conf->currency), 1, 'R');
            $pdf->SetTextColor(0, 0, 0);

            $posy += 5;
        }

        $posy += 5;
        return $posy;
    }

    /**
     * Page footer
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs, $pagenb)
    {
        global $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $pdf->SetFont('', '', $default_font_size - 2);
        $pdf->SetTextColor(128, 128, 128);

        $posy = $this->page_hauteur - $this->marge_basse - 5;

        // Date generation
        $pdf->SetXY($this->marge_gauche, $posy);
        $pdf->MultiCell(80, 4, $outputlangs->transnoentities("GeneratedOn").' '.dol_print_date(dol_now(), 'dayhour'), 0, 'L');

        // Page number
        $pdf->SetXY($this->page_largeur - $this->marge_droite - 30, $posy);
        $pdf->MultiCell(30, 4, $pagenb.'/'.$pdf->getAliasNbPages(), 0, 'R');

        $pdf->SetTextColor(0, 0, 0);
    }
}
