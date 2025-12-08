<?php
/**
 * PDF generation for Customer Return (Bon de retour)
 * Shows unit price with category discount if applicable
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
dol_include_once('/customerreturn/core/modules/customerreturn/modules_customerreturn.php');

/**
 * Class to generate PDF for Customer Return
 */
class pdf_standard_customerreturn extends ModelePDFCustomerReturn
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string Model name
     */
    public $name;

    /**
     * @var string Model description
     */
    public $description;

    /**
     * @var int Save the name of generated file as the main doc
     */
    public $update_main_doc_field;

    /**
     * @var string Document type
     */
    public $type;

    /**
     * @var string Version
     */
    public $version = 'dolibarr';

    /**
     * @var int Page width
     */
    public $page_largeur;

    /**
     * @var int Page height
     */
    public $page_hauteur;

    /**
     * @var array Page format
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
     * @var Societe Sender company
     */
    public $emetteur;

    // Column positions
    public $posxdesc;
    public $posxqty;
    public $posxup;
    public $posxdiscount;
    public $posxtotalht;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $mysoc;

        $this->db = $db;
        $this->name = "standard_customerreturn";
        $this->description = $langs->trans("PDFStandardCustomerReturn");
        $this->update_main_doc_field = 1;

        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
        $this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
        $this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
        $this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

        // Column positions
        $this->posxdesc = $this->marge_gauche + 1;
        $this->posxqty = 105;
        $this->posxup = 120;
        $this->posxdiscount = 145;
        $this->posxtotalht = 165;

        if ($mysoc === null) {
            dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.', LOG_ERR);
            return;
        }

        $this->emetteur = $mysoc;
        if (!$this->emetteur->country_code) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2);
        }
    }

    /**
     * Get customer discount for a product using categorydiscount module
     *
     * @param int $socid Customer ID
     * @param int $productId Product ID
     * @return float Discount percentage (0 if no discount)
     */
    private function getCustomerDiscount($socid, $productId)
    {
        global $conf;

        if (!isModEnabled('categorydiscount')) {
            return 0;
        }

        $discountFile = DOL_DOCUMENT_ROOT.'/custom/categorydiscount/class/categorydiscountrule.class.php';
        if (!file_exists($discountFile)) {
            return 0;
        }

        require_once $discountFile;

        $discountRule = new CategoryDiscountRule($this->db);
        $rule = $discountRule->fetchBestRuleForProduct($socid, $productId, $conf->entity);

        if ($rule && !empty($rule['discount_percent'])) {
            return (float) $rule['discount_percent'];
        }

        return 0;
    }

    /**
     * Generate PDF document
     *
     * @param CustomerReturn $object Object to generate
     * @param Translate $outputlangs Language object
     * @param string $srctemplatepath Source template path
     * @param int $hidedetails Hide details
     * @param int $hidedesc Hide description
     * @param int $hideref Hide ref
     * @return int 1 if OK, <=0 if KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $conf, $langs, $hookmanager;

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }

        $outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "customerreturn@customerreturn"));

        // Fetch third party
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $societe = new Societe($this->db);
        $societe->fetch($object->fk_soc);
        $object->thirdparty = $societe;

        $nblines = count($object->lines);

        // Define output directory
        if (!empty($conf->customerreturn->dir_output)) {
            $dir = $conf->customerreturn->dir_output;
        } else {
            $dir = DOL_DATA_ROOT.'/customerreturn';
        }
        if (!is_dir($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }

        if ($object->specimen) {
            $file = $dir."/SPECIMEN.pdf";
        } else {
            $objectref = dol_sanitizeFileName($object->ref);
            $subdir = $dir."/".$objectref;
            if (!is_dir($subdir)) {
                dol_mkdir($subdir);
            }
            $file = $subdir."/".$objectref.".pdf";
        }

        if (file_exists($dir) || dol_mkdir($dir) >= 0) {
            // Create PDF
            $pdf = pdf_getInstance($this->format);
            $default_font_size = pdf_getPDFFontSize($outputlangs);
            $heightforinfotot = 40;
            $heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5);
            $heightforfooter = $this->marge_basse + 12;

            $pdf->setAutoPageBreak(true, 0);

            if (class_exists('TCPDF')) {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }

            $pdf->SetFont(pdf_getPDFFont($outputlangs));

            $pdf->Open();
            $pagenb = 0;
            $pdf->SetDrawColor(128, 128, 128);

            $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
            $pdf->SetSubject($outputlangs->transnoentities("CustomerReturn"));
            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
            $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref));

            if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
                $pdf->SetCompression(false);
            }

            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

            // New page
            $pdf->AddPage();
            $pagenb++;

            $this->_pagehead($pdf, $object, 1, $outputlangs);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell(0, 3, '');
            $pdf->SetTextColor(0, 0, 0);

            $tab_top = 90;
            $tab_top_newpage = 42;
            $tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext - $heightforinfotot;

            // Table header
            $this->_tableau($pdf, $tab_top, $tab_height, 0, $outputlangs, 0, 0);

            $nexY = $tab_top + 7;

            // Lines
            for ($i = 0; $i < $nblines; $i++) {
                $curY = $nexY;
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetTextColor(0, 0, 0);

                // Product description
                $pdf->SetXY($this->posxdesc, $curY);
                $txt = '';
                if ($object->lines[$i]->fk_product > 0) {
                    $txt = $object->lines[$i]->product_ref.' - '.$object->lines[$i]->product_label;
                } else {
                    $txt = $object->lines[$i]->label;
                }
                if (!empty($object->lines[$i]->description)) {
                    $txt .= "\n".$object->lines[$i]->description;
                }
                if (!empty($object->lines[$i]->reason)) {
                    $txt .= "\n".$outputlangs->trans('Reason').': '.$object->lines[$i]->reason;
                }
                $pdf->MultiCell($this->posxqty - $this->posxdesc - 2, 3, $txt, 0, 'L');

                $posYAfterDescription = $pdf->GetY();

                // Qty
                $pdf->SetXY($this->posxqty, $curY);
                $pdf->MultiCell($this->posxup - $this->posxqty - 1, 3, $object->lines[$i]->qty, 0, 'C');

                // Unit price
                $unitPrice = $object->lines[$i]->subprice;
                $pdf->SetXY($this->posxup, $curY);
                $pdf->MultiCell($this->posxdiscount - $this->posxup - 1, 3, price($unitPrice, 0, $outputlangs), 0, 'R');

                // Discount (from categorydiscount)
                $discount = 0;
                if ($object->lines[$i]->fk_product > 0) {
                    $discount = $this->getCustomerDiscount($object->fk_soc, $object->lines[$i]->fk_product);
                }
                $pdf->SetXY($this->posxdiscount, $curY);
                if ($discount > 0) {
                    $pdf->SetTextColor(255, 0, 0);
                    $pdf->MultiCell($this->posxtotalht - $this->posxdiscount - 1, 3, '-'.price($discount, 0, $outputlangs).'%', 0, 'C');
                    $pdf->SetTextColor(0, 0, 0);
                } else {
                    $pdf->MultiCell($this->posxtotalht - $this->posxdiscount - 1, 3, '-', 0, 'C');
                }

                // Total HT (with discount applied)
                $totalLine = $object->lines[$i]->total_ht;
                if ($discount > 0) {
                    $totalLine = $unitPrice * $object->lines[$i]->qty * (1 - $discount / 100);
                }
                $pdf->SetXY($this->posxtotalht, $curY);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxtotalht, 3, price($totalLine, 0, $outputlangs), 0, 'R');

                $nexY = max($posYAfterDescription, $pdf->GetY()) + 2;

                // Check page break
                if ($nexY > ($this->page_hauteur - $heightforfooter - $heightforfreetext - $heightforinfotot)) {
                    $this->_pagefoot($pdf, $object, $outputlangs);
                    $pdf->AddPage();
                    $pagenb++;
                    $this->_pagehead($pdf, $object, 0, $outputlangs);
                    $this->_tableau($pdf, $tab_top_newpage, $tab_height, 0, $outputlangs, 1, 0);
                    $nexY = $tab_top_newpage + 7;
                }
            }

            // Totals section
            $posy = $nexY + 5;
            $this->_tableau_tot($pdf, $object, $posy, $outputlangs);

            // Footer
            $this->_pagefoot($pdf, $object, $outputlangs);

            $pdf->Close();
            $pdf->Output($file, 'F');

            dolChmod($file);

            $this->result = array('fullpath' => $file);

            return 1;
        } else {
            $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
            return 0;
        }
    }

    /**
     * Show totals table (same style as Dolibarr invoices)
     *
     * @param TCPDF $pdf PDF object
     * @param CustomerReturn $object Customer return object
     * @param float $posy Y position
     * @param Translate $outputlangs Output language
     * @return float New Y position
     */
    protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs)
    {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $tab2_top = $posy;
        $tab2_hl = 4;  // Same as invoices
        $pdf->SetFont('', '', $default_font_size - 1);

        // Position du bloc totaux (same as invoices)
        $col1x = 120;
        $col2x = 170;
        if ($this->page_largeur < 210) { // To work with US executive format
            $col1x -= 15;
            $col2x -= 10;
        }
        $largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

        // Recalculate total with discounts
        $totalHT = 0;
        $totalTVA = 0;
        $totalTTC = 0;
        $tva_array = array();

        foreach ($object->lines as $line) {
            $discount = 0;
            if ($line->fk_product > 0) {
                $discount = $this->getCustomerDiscount($object->fk_soc, $line->fk_product);
            }

            if ($discount > 0) {
                $lineHT = $line->subprice * $line->qty * (1 - $discount / 100);
            } else {
                $lineHT = $line->total_ht;
            }
            $tvaRate = $line->tva_tx;
            $lineTVA = $lineHT * $tvaRate / 100;
            $totalHT += $lineHT;
            $totalTVA += $lineTVA;

            // Group TVA by rate
            if (!isset($tva_array[$tvaRate])) {
                $tva_array[$tvaRate] = 0;
            }
            $tva_array[$tvaRate] += $lineTVA;
        }
        $totalTTC = $totalHT + $totalTVA;

        $useborder = 0;
        $index = 0;

        // Total HT
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), $useborder, 'L', true);
        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($totalHT, 0, $outputlangs), $useborder, 'R', true);

        // Show TVA by rate (like invoices)
        $pdf->SetFillColor(248, 248, 248);
        foreach ($tva_array as $tvaRate => $tvaAmount) {
            if ($tvaRate != 0) {
                $index++;
                $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT").' '.vatrate($tvaRate, true), $useborder, 'L', true);
                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaAmount, 0, $outputlangs), $useborder, 'R', true);
            }
        }

        // Total TTC with grey background (same as invoices)
        $index++;
        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', true);
        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($totalTTC, 0, $outputlangs), $useborder, 'R', true);

        // Reset text color
        $pdf->SetTextColor(0, 0, 0);

        return ($tab2_top + ($tab2_hl * ($index + 1)) + 2);
    }

    /**
     * Show table header for lines
     *
     * @param TCPDF $pdf PDF object
     * @param float $tab_top Tab top position
     * @param float $tab_height Tab height
     * @param float $nexY Next Y position
     * @param Translate $outputlangs Output language
     * @param int $hidetop Hide top
     * @param int $hidebottom Hide bottom
     * @return void
     */
    protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
    {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 2);

        // Draw rectangle
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height);

        // Column headers
        if (empty($hidetop)) {
            $pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5);

            $pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
            $pdf->MultiCell($this->posxqty - $this->posxdesc, 2, $outputlangs->transnoentities("Description"), '', 'L');

            $pdf->SetXY($this->posxqty - 1, $tab_top + 1);
            $pdf->MultiCell($this->posxup - $this->posxqty, 2, $outputlangs->transnoentities("Qty"), '', 'C');

            $pdf->SetXY($this->posxup - 1, $tab_top + 1);
            $pdf->MultiCell($this->posxdiscount - $this->posxup, 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');

            $pdf->SetXY($this->posxdiscount - 1, $tab_top + 1);
            $pdf->MultiCell($this->posxtotalht - $this->posxdiscount, 2, $outputlangs->transnoentities("Discount"), '', 'C');

            $pdf->SetXY($this->posxtotalht - 1, $tab_top + 1);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxtotalht, 2, $outputlangs->transnoentities("TotalHTShort"), '', 'C');
        }

        // Vertical lines
        $pdf->line($this->posxqty - 1, $tab_top, $this->posxqty - 1, $tab_top + $tab_height);
        $pdf->line($this->posxup - 1, $tab_top, $this->posxup - 1, $tab_top + $tab_height);
        $pdf->line($this->posxdiscount - 1, $tab_top, $this->posxdiscount - 1, $tab_top + $tab_height);
        $pdf->line($this->posxtotalht - 1, $tab_top, $this->posxtotalht - 1, $tab_top + $tab_height);
    }

    /**
     * Show page header
     *
     * @param TCPDF $pdf PDF object
     * @param CustomerReturn $object Customer return object
     * @param int $showaddress Show address
     * @param Translate $outputlangs Output language
     * @return float Top shift
     */
    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
    {
        global $conf, $langs;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;
        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        if ($this->emetteur->logo) {
            $logodir = $conf->mycompany->dir_output;
            if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                $logodir = $conf->mycompany->multidir_output[$object->entity];
            }
            if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
                $logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
            } else {
                $logo = $logodir.'/logos/'.$this->emetteur->logo;
            }
            if (is_readable($logo)) {
                $height = pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
        }

        $pdf->SetFont('', 'B', $default_font_size + 2);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $title = $outputlangs->transnoentities("CustomerReturn");
        $pdf->MultiCell($w, 4, $title, '', 'R');

        $pdf->SetFont('', '', $default_font_size + 1);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell($w, 4, $outputlangs->transnoentities("Ref")." : ".$object->ref, '', 'R');

        // Date
        $posy += 4;
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell($w, 4, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->date_return ? $object->date_return : $object->datec, "day", false, $outputlangs, true), '', 'R');

        // Customer code
        if (!empty($object->thirdparty->code_client)) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
        }

        $top_shift = 0;

        if ($showaddress) {
            // Sender
            $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

            $posy = 42;
            $posx = $this->marge_gauche;
            $hautcadre = 40;
            $widthrecbox = 82;

            // Sender frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx, $posy - 5);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Sender"), 0, 'L');
            $pdf->SetXY($posx, $posy);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre, 'F');
            $pdf->SetTextColor(0, 0, 60);
            $pdf->SetFillColor(255, 255, 255);

            // Sender name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
            $posy = $pdf->getY();

            // Sender info
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

            // Recipient
            $carac_client_name = pdfBuildThirdpartyName($object->thirdparty, $outputlangs);
            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, null, 0, 'target', $object);

            $widthrecbox = 100;
            $posy = 42;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;

            // Recipient frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + 2, $posy - 5);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Recipient"), 0, 'L');
            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre, 'D');

            // Recipient name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

            $posy = $pdf->getY();

            // Recipient info
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);

        return $top_shift;
    }

    /**
     * Show page footer
     *
     * @param TCPDF $pdf PDF object
     * @param CustomerReturn $object Customer return object
     * @param Translate $outputlangs Output language
     * @param int $hidefreetext Hide free text
     * @return int Height of footer
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        $showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
        return pdf_pagefoot($pdf, $outputlangs, 'CUSTOMERRETURN_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur);
    }
}
