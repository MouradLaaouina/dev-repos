<?php
/**
 * PDF Einstein TTC Only - Order template showing only TTC amounts
 * Based on pdf_einstein but displays only TTC (no HT, no VAT breakdown)
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/doc/pdf_einstein.modules.php';

/**
 * Class to generate PDF order with TTC only
 */
class pdf_einstein_ttc extends pdf_einstein
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->name = "einstein_ttc";
        $this->description = "Modèle Einstein - TTC uniquement (sans HT ni TVA)";
    }

    /**
     * Function to build pdf onto disk - TTC ONLY version
     *
     * @param Commande $object Object source to build document
     * @param Translate $outputlangs Lang output object
     * @param string $srctemplatepath Full path of source filename for generator using a template file
     * @param int $hidedetails Do not show line details
     * @param int $hidedesc Do not show desc
     * @param int $hideref Do not show ref
     * @return int 1 if OK, <=0 if KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        if (getDolGlobalString('MAIN_USE_FPDF')) {
            $outputlangs->charset_output = 'ISO-8859-1';
        }

        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries", "compta", "pdfttconly@pdfttconly"));

        if ($object->statut == $object::STATUS_DRAFT && getDolGlobalString('COMMANDE_DRAFT_WATERMARK')) {
            $this->watermark = getDolGlobalString('COMMANDE_DRAFT_WATERMARK');
        }

        global $outputlangsbis;
        $outputlangsbis = null;
        if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
            $outputlangsbis = new Translate('', $conf);
            $outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
            $outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries", "compta"));
        }

        $nblines = count($object->lines);

        if ($conf->commande->multidir_output[$conf->entity]) {
            $object->fetch_thirdparty();

            $deja_regle = 0;

            if ($object->specimen) {
                $dir = $conf->commande->multidir_output[$conf->entity];
                $file = $dir."/SPECIMEN.pdf";
            } else {
                $suffix = '';
                if (getDolGlobalString('PROFORMA_PDF_WITH_SUFFIX')) {
                    $suffix = (GETPOST('model') == 'proforma') ? getDolGlobalString('PROFORMA_PDF_WITH_SUFFIX') : '';
                    $suffix = dol_sanitizeFileName($suffix);
                }

                $objectref = dol_sanitizeFileName($object->ref);
                $dir = $conf->commande->multidir_output[$object->entity]."/".$objectref;
                $file = $dir."/".$objectref.$suffix.".pdf";
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

                $nblines = count($object->lines);

                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);
                $pdf->setAutoPageBreak(true, 0);

                $heightforinfotot = 40;
                $heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5);
                $heightforfooter = $this->marge_basse + 8;
                if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS')) {
                    $heightforfooter += 6;
                }

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
                    $logodir = $conf->mycompany->dir_output;
                    if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                        $logodir = $conf->mycompany->multidir_output[$object->entity];
                    }
                    $pagecount = $pdf->setSourceFile($logodir.'/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("PdfOrderTitle"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfOrderTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
                if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
                    $pdf->SetCompression(false);
                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

                for ($i = 0; $i < $nblines; $i++) {
                    if ($object->lines[$i]->remise_percent) {
                        $this->atleastonediscount++;
                    }
                }
                if (empty($this->atleastonediscount)) {
                    $delta = ($this->postotalht - $this->posxdiscount);
                    $this->posxpicture += $delta;
                    $this->posxtva += $delta;
                    $this->posxup += $delta;
                    $this->posxqty += $delta;
                    $this->posxunit += $delta;
                    $this->posxdiscount += $delta;
                }

                $pdf->AddPage();
                if (!empty($tplidx)) {
                    $pdf->useTemplate($tplidx);
                }
                $pagenb++;
                $pagehead = $this->_pagehead($pdf, $object, 1, $outputlangs, (is_object($outputlangsbis) ? $outputlangsbis : null));
                $top_shift = $pagehead['top_shift'];
                $shipp_shift = $pagehead['shipp_shift'];
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 90 + $top_shift + $shipp_shift;
                $tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);

                $height_incoterms = 0;
                if (isModEnabled('incoterm')) {
                    $desc_incoterms = $object->getIncotermsForPDF();
                    if ($desc_incoterms) {
                        $tab_top -= 2;

                        $pdf->SetFont('', '', $default_font_size - 1);
                        $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
                        $nexY = $pdf->GetY();
                        $height_incoterms = $nexY - $tab_top;

                        $pdf->SetDrawColor(192, 192, 192);
                        $pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_incoterms + 3, $this->corner_radius, '1234', 'D');

                        $tab_top = $nexY + 6;
                    }
                }

                $notetoshow = empty($object->note_public) ? '' : $object->note_public;
                if (getDolGlobalString('MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE')) {
                    if (is_object($object->thirdparty)) {
                        $salereparray = $object->thirdparty->getSalesRepresentatives($user);
                        $salerepobj = new User($this->db);
                        $salerepobj->fetch($salereparray[0]['id']);
                        if (!empty($salerepobj->signature)) {
                            $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
                        }
                    }
                }
                $extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
                if (!empty($extranote)) {
                    $notetoshow = dol_concatdesc((string) $notetoshow, $extranote);
                }

                if ($notetoshow) {
                    $tab_top -= 2;

                    $substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
                    complete_substitutions_array($substitutionarray, $outputlangs, $object);
                    $notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
                    $notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($notetoshow), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note = $nexY - $tab_top;

                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1, $this->corner_radius, '1234', 'D');

                    $tab_top = $nexY + 6;
                }

                $iniY = $tab_top + 7;
                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;

                // Loop on each lines
                for ($i = 0; $i < $nblines; $i++) {
                    $curY = $nexY;
                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->SetTextColor(0, 0, 0);

                    $pdf->setTopMargin($tab_top_newpage);
                    $pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext + $heightforinfotot);
                    $pageposbefore = $pdf->getPage();

                    $curX = $this->posxdesc - 1;

                    $showpricebeforepagebreak = 1;

                    $pdf->startTransaction();
                    pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxtva - $curX, 3, $curX, $curY, $hideref, $hidedesc);
                    $pageposafter = $pdf->getPage();
                    if ($pageposafter > $pageposbefore) {
                        $pdf->rollbackTransaction(true);
                        $pageposafter = $pageposbefore;
                        $pdf->setPageOrientation('', true, $heightforfooter);
                        pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxtva - $curX, 4, $curX, $curY, $hideref, $hidedesc);
                        $pageposafter = $pdf->getPage();
                        $posyafter = $pdf->GetY();
                        if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {
                            if ($i == ($nblines - 1)) {
                                $pdf->AddPage('', '', true);
                                if (!empty($tplidx)) {
                                    $pdf->useTemplate($tplidx);
                                }
                                if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                    $this->_pagehead($pdf, $object, 0, $outputlangs);
                                }
                                $pdf->setPage($pageposafter + 1);
                            }
                        } else {
                            if (getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
                                $showpricebeforepagebreak = 1;
                            } else {
                                $showpricebeforepagebreak = 0;
                            }
                        }
                    } else {
                        $pdf->commitTransaction();
                    }
                    $posYAfterDescription = $pdf->GetY();

                    $nexY = $pdf->GetY();
                    $pageposafter = $pdf->getPage();

                    $pdf->setPage($pageposbefore);
                    $pdf->setTopMargin($this->marge_haute);
                    $pdf->setPageOrientation('', true, 0);

                    if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
                        $pdf->setPage($pageposafter);
                        $curY = $tab_top_newpage;
                    }

                    $pdf->SetFont('', '', $default_font_size - 1);

                    // TTC ONLY: Skip VAT Rate column - don't display it
                    // (We comment out the VAT display)

                    // Unit price TTC (instead of HT) - Using pdf_getlineupwithtax
                    $up_incl_tax = pdf_getlineupwithtax($object, $i, $outputlangs, $hidedetails);
                    $pdf->SetXY($this->posxup, $curY);
                    $pdf->MultiCell($this->posxqty - $this->posxup - 0.8, 3, $up_incl_tax, 0, 'R', false);

                    // Quantity
                    $qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
                    $pdf->SetXY($this->posxqty, $curY);
                    $pdf->MultiCell($this->posxunit - $this->posxqty - 0.8, 4, $qty, 0, 'R');

                    // Unit
                    if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
                        $unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
                        $pdf->SetXY($this->posxunit, $curY);
                        $pdf->MultiCell($this->posxdiscount - $this->posxunit - 0.8, 4, $unit, 0, 'L');
                    }

                    // Discount on line
                    $pdf->SetXY($this->posxdiscount, $curY);
                    if ($object->lines[$i]->remise_percent) {
                        $pdf->SetXY($this->posxdiscount - 2, $curY);
                        $remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
                        $pdf->MultiCell($this->postotalht - $this->posxdiscount + 2, 3, $remise_percent, 0, 'R');
                    }

                    // Total TTC line (instead of HT) - Using pdf_getlinetotalwithtax
                    $total_incl_tax = pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails);
                    $pdf->SetXY($this->postotalht, $curY);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 3, $total_incl_tax, 0, 'R', false);

                    // We still need to collect TVA data for internal calculations (even if not displayed)
                    if (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) {
                        $tvaligne = $object->lines[$i]->multicurrency_total_tva;
                    } else {
                        $tvaligne = $object->lines[$i]->total_tva;
                    }

                    $localtax1ligne = $object->lines[$i]->total_localtax1;
                    $localtax2ligne = $object->lines[$i]->total_localtax2;
                    $localtax1_rate = $object->lines[$i]->localtax1_tx;
                    $localtax2_rate = $object->lines[$i]->localtax2_tx;
                    $localtax1_type = $object->lines[$i]->localtax1_type;
                    $localtax2_type = $object->lines[$i]->localtax2_type;

                    $vatrate = (string) $object->lines[$i]->tva_tx;

                    if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '')
                    && (!empty($localtax1_rate) || !empty($localtax2_rate))) {
                        $localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
                        $localtax1_type = isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
                        $localtax2_type = isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
                    }

                    if ($localtax1_type && $localtax1ligne != 0) {
                        if (empty($this->localtax1[$localtax1_type][$localtax1_rate])) {
                            $this->localtax1[$localtax1_type][$localtax1_rate] = $localtax1ligne;
                        } else {
                            $this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
                        }
                    }
                    if ($localtax2_type && $localtax2ligne != 0) {
                        if (empty($this->localtax2[$localtax2_type][$localtax2_rate])) {
                            $this->localtax2[$localtax2_type][$localtax2_rate] = $localtax2ligne;
                        } else {
                            $this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;
                        }
                    }

                    if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
                        $vatrate .= '*';
                    }

                    if (!isset($this->tva[$vatrate])) {
                        $this->tva[$vatrate] = 0;
                    }
                    $this->tva[$vatrate] += $tvaligne;
                    $vatcode = $object->lines[$i]->vat_src_code;
                    if (empty($this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'])) {
                        $this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] = 0;
                    }
                    $this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')] = array('vatrate' => $vatrate, 'vatcode' => $vatcode, 'amount' => $this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] + $tvaligne);

                    if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
                        $pdf->setPage($pageposafter);
                        $pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
                        $pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
                        $pdf->SetLineStyle(array('dash' => 0));
                    }

                    $nexY += 2;

                    while ($pagenb < $pageposafter) {
                        $pdf->setPage($pagenb);
                        if ($pagenb == 1) {
                            $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
                        } else {
                            $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
                        }
                        $this->_pagefoot($pdf, $object, $outputlangs, 1);
                        $pagenb++;
                        $pdf->setPage($pagenb);
                        $pdf->setPageOrientation('', true, 0);
                        if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                            $this->_pagehead($pdf, $object, 0, $outputlangs);
                        }
                        if (!empty($tplidx)) {
                            $pdf->useTemplate($tplidx);
                        }
                    }
                    if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
                        if ($pagenb == 1) {
                            $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
                        } else {
                            $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
                        }
                        $this->_pagefoot($pdf, $object, $outputlangs, 1);
                        $pdf->AddPage();
                        if (!empty($tplidx)) {
                            $pdf->useTemplate($tplidx);
                        }
                        $pagenb++;
                        if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                            $this->_pagehead($pdf, $object, 0, $outputlangs);
                        }
                    }
                }

                // Show square
                if ($pagenb == 1) {
                    $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
                } else {
                    $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
                }
                $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

                // Display infos area
                $posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

                // Display total zone
                $posy = $this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

                // Pied de page
                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                // Add terms to sale
                if (getDolGlobalString("MAIN_INFO_ORDER_TERMSOFSALE") && getDolGlobalInt('MAIN_PDF_ADD_TERMSOFSALE_ORDER')) {
                    $termsofsalefilename = getDolGlobalString('MAIN_INFO_ORDER_TERMSOFSALE');
                    $termsofsale = $conf->order->dir_output.'/'.$termsofsalefilename;
                    if (!empty($conf->order->multidir_output[$conf->entity])) {
                        $termsofsale = $conf->order->multidir_output[$conf->entity].'/'.$termsofsalefilename;
                    }
                    if (file_exists($termsofsale) && is_readable($termsofsale)) {
                        $pagecount = $pdf->setSourceFile($termsofsale);
                        for ($i = 1; $i <= $pagecount; $i++) {
                            $tplIdx = $pdf->importPage($i);
                            if ($tplIdx !== false) {
                                $s = $pdf->getTemplatesize($tplIdx);
                                $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                                $pdf->useTemplate($tplIdx);
                            } else {
                                setEventMessages(null, array($termsofsale.' cannot be added, probably protected PDF'), 'warnings');
                            }
                        }
                    }
                }

                $pdf->Close();

                $pdf->Output($file, 'F');

                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);
                if ($reshook < 0) {
                    $this->error = $hookmanager->error;
                    $this->errors = $hookmanager->errors;
                }

                dolChmod($file);

                $this->result = array('fullpath' => $file);

                return 1;
            } else {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->transnoentities("ErrorConstantNotDefined", "COMMANDE_OUTPUTDIR");
            return 0;
        }
    }

    /**
     * Show table for lines - TTC ONLY version
     * Hides VAT column and shows TTC instead of HT
     *
     * @param TCPDF $pdf Object PDF
     * @param int $tab_top Tab top position
     * @param int $tab_height Tab height
     * @param int $nexY Next Y position
     * @param Translate $outputlangs Output language
     * @param int $hidetop Hide top
     * @param int $hidebottom Hide bottom
     * @param string $currency Currency
     * @param Translate $outputlangsbis Second output language
     * @return void
     */
    protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
    {
        global $conf;

        $hidebottom = 0;
        if ($hidetop) {
            $hidetop = -1;
        }

        $currency = !empty($currency) ? $currency : $conf->currency;
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 2);

        if (empty($hidetop)) {
            $titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
            $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
            $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

            if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
                $pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, $this->corner_radius, '1001', 'F', array(), explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
            }
        }

        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetFont('', '', $default_font_size - 1);

        $this->printRoundedRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $this->corner_radius, $hidetop, $hidebottom, 'D');

        if (empty($hidetop)) {
            $pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5);

            $pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
            $pdf->MultiCell(108, 2, $outputlangs->transnoentities("Designation"), '', 'L');
        }

        if (getDolGlobalString('MAIN_GENERATE_ORDERS_WITH_PICTURE')) {
            $pdf->line($this->posxpicture - 1, $tab_top, $this->posxpicture - 1, $tab_top + $tab_height);
        }

        // NO VAT COLUMN - Skip it entirely for TTC only mode

        // Price Unit TTC (instead of HT)
        $pdf->line($this->posxup - 1, $tab_top, $this->posxup - 1, $tab_top + $tab_height);
        if (empty($hidetop)) {
            $pdf->SetXY($this->posxup - 1, $tab_top + 1);
            $pdf->MultiCell($this->posxqty - $this->posxup - 1, 2, $outputlangs->transnoentities("PriceUTTC"), '', 'C');
        }

        $pdf->line($this->posxqty - 1, $tab_top, $this->posxqty - 1, $tab_top + $tab_height);
        if (empty($hidetop)) {
            $pdf->SetXY($this->posxqty - 1, $tab_top + 1);
            $pdf->MultiCell($this->posxunit - $this->posxqty - 1, 2, $outputlangs->transnoentities("Qty"), '', 'C');
        }

        if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
            $pdf->line($this->posxunit - 1, $tab_top, $this->posxunit - 1, $tab_top + $tab_height);
            if (empty($hidetop)) {
                $pdf->SetXY($this->posxunit - 1, $tab_top + 1);
                $pdf->MultiCell($this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("Unit"), '', 'C');
            }
        }

        $pdf->line($this->posxdiscount - 1, $tab_top, $this->posxdiscount - 1, $tab_top + $tab_height);
        if (empty($hidetop)) {
            if ($this->atleastonediscount) {
                $pdf->SetXY($this->posxdiscount - 1, $tab_top + 1);
                $pdf->MultiCell($this->postotalht - $this->posxdiscount + 1, 2, $outputlangs->transnoentities("ReductionShort"), '', 'C');
            }
        }
        if ($this->atleastonediscount) {
            $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
        }
        if (empty($hidetop)) {
            $pdf->SetXY($this->postotalht - 1, $tab_top + 1);
            // Show "Total TTC" instead of "Total HT"
            $pdf->MultiCell(30, 2, $outputlangs->transnoentities("TotalTTCShort"), '', 'C');
        }
    }

    /**
     * Show total to pay - TTC ONLY version
     *
     * @param TCPDF $pdf Object PDF
     * @param Commande $object Object order
     * @param int $deja_regle Already paid amount
     * @param int $posy Position Y
     * @param Translate $outputlangs Output language
     * @param Translate $outputlangsbis Second output language
     * @return int Position Y
     */
    protected function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis = null)
    {
        global $conf, $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $tab2_top = $posy;
        $tab2_hl = 4;
        $pdf->SetFont('', '', $default_font_size - 1);

        $col1x = 120;
        $col2x = 170;
        if ($this->page_largeur < 210) {
            $col2x -= 20;
        }
        $largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

        $useborder = 0;
        $index = 0;

        // Get Total TTC
        $total_ttc = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

        // Display only TTC - with highlighted background
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetFont('', 'B', $default_font_size - 1);
        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("TotalTTC") : ''), $useborder, 'L', true);

        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs), $useborder, 'R', true);

        $pdf->SetFont('', '', $default_font_size - 1);
        $index++;

        // Already paid (if any)
        if ($deja_regle > 0) {
            $index++;
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("AlreadyPaid") : ''), 0, 'L', true);
            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs), 0, 'R', true);

            // Remains to pay
            $index++;
            $resteapayer = $total_ttc - $deja_regle;
            $pdf->SetFillColor(224, 224, 224);
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->SetFont('', 'B', $default_font_size - 1);
            $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("RemainderToPay") : ''), $useborder, 'L', true);
            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', true);
            $pdf->SetFont('', '', $default_font_size - 1);
        }

        return ($tab2_top + ($tab2_hl * $index));
    }
}
