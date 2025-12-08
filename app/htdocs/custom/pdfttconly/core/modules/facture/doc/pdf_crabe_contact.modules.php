<?php
/**
 * PDF Crabe Contact Only - Invoice template showing only contact name when contact is selected
 * Based on pdf_crabe but displays only the contact name (without company address)
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/pdf_crabe.modules.php';

/**
 * Class to generate PDF invoice with contact name only
 */
class pdf_crabe_contact extends pdf_crabe
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->name = "crabe_contact";
        $this->description = "Modèle Crabe - Affiche uniquement le nom du contact";
    }

    /**
     * Show top header of page - CONTACT ONLY version
     * When a contact is selected, shows ONLY the contact name without company address
     *
     * @param TCPDF         $pdf            Object PDF
     * @param Facture       $object         Object to show
     * @param int           $showaddress    0=no, 1=yes
     * @param Translate     $outputlangs    Object lang for output
     * @param Translate     $outputlangsbis Object lang for output bis
     * @return float|int                    Return topshift value
     */
    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
    {
        global $conf, $langs;

        // Call grandparent method to avoid duplicating all the header code
        // We need to handle the address part ourselves

        $ltrdirection = 'L';
        if ($outputlangs->trans("DIRECTION") == 'rtl') {
            $ltrdirection = 'R';
        }

        // Load translation files
        $outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;

        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        if (!empty($this->emetteur->logo)) {
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
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
        }

        $pdf->SetDrawColor(128, 128, 128);

        $posx = $this->page_largeur - $w - $this->marge_droite;
        $posy = $this->marge_haute;

        $pdf->SetFont('', 'B', $default_font_size + 2);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);

        $title = $outputlangs->transnoentities("PdfInvoiceTitle");
        if ($object->type == 1) {
            $title = $outputlangs->transnoentities("InvoiceReplacement");
        }
        if ($object->type == 2) {
            $title = $outputlangs->transnoentities("InvoiceAvoir");
        }
        if ($object->type == 3) {
            $title = $outputlangs->transnoentities("InvoiceDeposit");
        }
        if ($object->type == 4) {
            $title = $outputlangs->transnoentities("InvoiceProForma");
        }
        if ($this->situationinvoice) {
            $title = $outputlangs->transnoentities("InvoiceSituation");
        }
        $pdf->MultiCell($w, 3, $title, '', 'R');

        $pdf->SetFont('', 'B', $default_font_size);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
        if ($object->statut == Facture::STATUS_DRAFT) {
            $pdf->SetTextColor(128, 0, 0);
            $textref .= ' - '.$outputlangs->transnoentities("NotValidated");
        }
        $pdf->MultiCell($w, 4, $textref, '', 'R');

        $posy += 3;
        $pdf->SetFont('', '', $default_font_size - 2);

        if ($object->ref_client) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefCustomer")." : ".dol_trunc($outputlangs->convToOutputCharset($object->ref_client), 65), '', 'R');
        }

        if (getDolGlobalString('PDF_SHOW_PROJECT_TITLE')) {
            $object->fetch_projet();
            if (!empty($object->project->ref)) {
                $posy += 3;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("Project")." : ".(empty($object->project->title) ? '' : $object->project->title), '', 'R');
            }
        }

        if (getDolGlobalString('PDF_SHOW_PROJECT')) {
            $object->fetch_projet();
            if (!empty($object->project->ref)) {
                $outputlangs->load("projects");
                $posy += 3;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefProject")." : ".(empty($object->project->ref) ? '' : $object->project->ref), '', 'R');
            }
        }

        $objectidnext = $object->getIdReplacingInvoice('validated');
        if ($object->type == 0 && $objectidnext) {
            $objectreplacing = new Facture($this->db);
            $objectreplacing->fetch($objectidnext);

            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
        }
        if ($object->type == 1) {
            $objectreplaced = new Facture($this->db);
            $objectreplaced->fetch($object->fk_facture_source);

            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
        }
        if ($object->type == 2 && !empty($object->fk_facture_source)) {
            $objectreplaced = new Facture($this->db);
            $objectreplaced->fetch($object->fk_facture_source);

            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("CorrsectInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
        }

        $posy += 4;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell($w, 3, $outputlangs->transnoentities("DateInvoice")." : ".dol_print_date($object->date, "day", false, $outputlangs), '', 'R');

        if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_CODE') && !empty($object->thirdparty->code_client)) {
            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
        }

        if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_ACCOUNTING_CODE') && !empty($object->thirdparty->code_compta_client)) {
            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerAccountancyCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_compta_client), '', 'R');
        }

        $posy += 1;

        $top_shift = 0;

        // Show list of linked objects
        $current_y = $pdf->getY();
        $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
        if ($current_y < $pdf->getY()) {
            $top_shift = $pdf->getY() - $current_y;
        }

        if ($showaddress) {
            // Sender properties
            $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

            // Show sender
            $posy = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
            $posy += $top_shift;
            $posx = $this->marge_gauche;
            if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
                $posx = $this->page_largeur - $this->marge_droite - 80;
            }

            $hautcadre = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
            $widthrecbox = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;

            // Show sender frame
            if (!getDolGlobalString('MAIN_PDF_NO_SENDER_FRAME')) {
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->SetXY($posx, $posy - 5);
                $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillFrom"), 0, $ltrdirection);
                $pdf->SetXY($posx, $posy);
                $pdf->SetFillColor(230, 230, 230);
                $pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, $this->corner_radius, '1234', 'F');
                $pdf->SetTextColor(0, 0, 60);
                $pdf->SetFillColor(255, 255, 255);
            }

            // Show sender name
            if (!getDolGlobalString('MAIN_PDF_HIDE_SENDER_NAME')) {
                $pdf->SetXY($posx + 2, $posy + 3);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $ltrdirection);
                $posy = $pdf->getY();
            }

            // Show sender information
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, $ltrdirection);


            // =====================================================
            // CONTACT ONLY VERSION: Show only contact name
            // =====================================================
            $usecontact = false;
            $arrayidcontact = $object->getIdContact('external', 'BILLING');
            if (count($arrayidcontact) > 0) {
                $usecontact = true;
                $result = $object->fetch_contact($arrayidcontact[0]);
            }

            // Build recipient name and address
            if ($usecontact && !empty($object->contact)) {
                // CONTACT SELECTED: Show ONLY the contact full name
                $carac_client_name = $object->contact->getFullName($outputlangs);
                // No address - only the name
                $carac_client = '';
            } else {
                // NO CONTACT: Show company name and full address as usual
                $thirdparty = $object->thirdparty;
                $carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
                $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'target', $object);
            }

            // Show recipient
            $widthrecbox = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 100;
            if ($this->page_largeur < 210) {
                $widthrecbox = 84;
            }
            $posy = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
            $posy += $top_shift;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
                $posx = $this->marge_gauche;
            }

            // Show recipient frame
            if (!getDolGlobalString('MAIN_PDF_NO_RECIPENT_FRAME')) {
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->SetXY($posx + 2, $posy - 5);
                $pdf->MultiCell($widthrecbox - 2, 5, $outputlangs->transnoentities("BillTo"), 0, $ltrdirection);
                $pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, $this->corner_radius, '1234', 'D');
            }

            // Show recipient name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox - 2, 2, $carac_client_name, 0, $ltrdirection);

            $posy = $pdf->getY();

            // Show recipient information (will be empty if contact is selected)
            if (!empty($carac_client)) {
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetXY($posx + 2, $posy);
                $pdf->MultiCell($widthrecbox - 2, 4, $carac_client, 0, $ltrdirection);
            }
        }

        $pdf->SetTextColor(0, 0, 0);

        return $top_shift;
    }
}
