<?php
/**
 * PDF Azur TTC Only - Proposal template showing only TTC amounts
 * Based on pdf_azur but displays only TTC (no HT, no VAT breakdown)
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/doc/pdf_azur.modules.php';

/**
 * Class to generate PDF proposal with TTC only
 */
class pdf_azur_ttc extends pdf_azur
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->name = "azur_ttc";
        $this->description = "Modèle Azur - TTC uniquement (sans HT ni TVA)";
    }

    /**
     * Function to build pdf onto disk - TTC ONLY version
     * This method overrides the parent to display TTC values instead of HT
     *
     * @param Propal $object Object source to build document
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

        dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        if (getDolGlobalString('MAIN_USE_FPDF')) {
            $outputlangs->charset_output = 'ISO-8859-1';
        }

        $langfiles = array("main", "dict", "companies", "bills", "propal", "products", "compta", "pdfttconly@pdfttconly");
        $outputlangs->loadLangs($langfiles);

        global $outputlangsbis;
        $outputlangsbis = null;
        if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
            $outputlangsbis = new Translate('', $conf);
            $outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
            $outputlangsbis->loadLangs($langfiles);
        }

        if ($object->statut == $object::STATUS_DRAFT && getDolGlobalString('PROPALE_DRAFT_WATERMARK')) {
            $this->watermark = getDolGlobalString('PROPALE_DRAFT_WATERMARK');
        }

        $nblines = count($object->lines);

        $realpatharray = array();
        $this->atleastonephoto = false;
        if (getDolGlobalString('MAIN_GENERATE_PROPOSALS_WITH_PICTURE')) {
            $objphoto = new Product($this->db);
            for ($i = 0; $i < $nblines; $i++) {
                if (empty($object->lines[$i]->fk_product)) {
                    continue;
                }
                $objphoto->fetch($object->lines[$i]->fk_product);
                $pdir = array();
                if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
                    $pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
                    $pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
                } else {
                    $pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product');
                    $pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
                }
                $arephoto = false;
                $realpath = '';
                foreach ($pdir as $midir) {
                    if (!$arephoto) {
                        $dir = ($conf->entity != $objphoto->entity) ? $conf->product->multidir_output[$objphoto->entity].'/'.$midir : $conf->product->dir_output.'/'.$midir;
                        foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
                            $filename = (!getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES') && $obj['photo_vignette']) ? $obj['photo_vignette'] : $obj['photo'];
                            $realpath = $dir.$filename;
                            $arephoto = true;
                            $this->atleastonephoto = true;
                        }
                    }
                }
                if ($realpath && $arephoto) {
                    $realpatharray[$i] = $realpath;
                }
            }
        }

        if (count($realpatharray) == 0) {
            $this->posxpicture = $this->posxtva;
        }

        if ($conf->propal->multidir_output[$conf->entity]) {
            $object->fetch_thirdparty();
            $deja_regle = 0;

            if ($object->specimen) {
                $dir = $conf->propal->multidir_output[$conf->entity];
                $file = $dir."/SPECIMEN.pdf";
            } else {
                $objectref = dol_sanitizeFileName($object->ref);
                $dir = $conf->propal->multidir_output[$object->entity]."/".$objectref;
                $file = $dir."/".$objectref.".pdf";
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

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
                    $logodir = !empty($conf->mycompany->multidir_output[$object->entity]) ? $conf->mycompany->multidir_output[$object->entity] : $conf->mycompany->dir_output;
                    $pagecount = $pdf->setSourceFile($logodir.'/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);
                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("PdfCommercialProposalTitle"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfCommercialProposalTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
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

                $heightforinfotot = 40;
                $heightforsignature = !getDolGlobalString('PROPAL_DISABLE_SIGNATURE') ? (pdfGetHeightForHtmlContent($pdf, $outputlangs->transnoentities("ProposalCustomerSignature")) + 10) : 0;
                $heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5);
                $heightforfooter = $this->marge_basse + 8;
                if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS')) {
                    $heightforfooter += 6;
                }

                $top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 90 + $top_shift;
                $tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);

                // Incoterms
                if (isModEnabled('incoterm')) {
                    $desc_incoterms = $object->getIncotermsForPDF();
                    if ($desc_incoterms) {
                        $tab_top -= 2;
                        $pdf->SetFont('', '', $default_font_size - 1);
                        $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
                        $nexY = $pdf->GetY();
                        $pdf->SetDrawColor(192, 192, 192);
                        $pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $nexY - $tab_top + 3, $this->corner_radius, '1234', 'D');
                        $tab_top = $nexY + 6;
                    }
                }

                // Notes
                $notetoshow = empty($object->note_public) ? '' : $object->note_public;
                if (getDolGlobalString('MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE') && is_object($object->thirdparty)) {
                    $salereparray = $object->thirdparty->getSalesRepresentatives($user);
                    if (!empty($salereparray[0]['id'])) {
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
                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $nexY - $tab_top + 2, $this->corner_radius, '1234', 'D');
                    $tab_top = $nexY + 6;
                }

                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;

                // Loop on each lines
                for ($i = 0; $i < $nblines; $i++) {
                    $curY = $nexY;
                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->SetTextColor(0, 0, 0);

                    $imglinesize = array();
                    if (!empty($realpatharray[$i])) {
                        $imglinesize = pdf_getSizeForImage($realpatharray[$i]);
                    }

                    $pdf->setTopMargin($tab_top_newpage);
                    $pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot);
                    $pageposbefore = $pdf->getPage();

                    $showpricebeforepagebreak = 1;
                    $posYAfterImage = 0;

                    // Image handling
                    if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {
                        $pdf->AddPage('', '', true);
                        if (!empty($tplidx)) {
                            $pdf->useTemplate($tplidx);
                        }
                        if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                            $this->_pagehead($pdf, $object, 0, $outputlangs);
                        }
                        $pdf->setPage($pageposbefore + 1);
                        $curY = $tab_top_newpage;
                        $showpricebeforepagebreak = getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE') ? 1 : 0;
                    }

                    if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
                        $curX = $this->posxpicture - 1;
                        $pdf->Image($realpatharray[$i], $curX + (($this->posxtva - $this->posxpicture - $imglinesize['width']) / 2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);
                        $posYAfterImage = $curY + $imglinesize['height'];
                    }

                    // Description
                    $curX = $this->posxdesc - 1;
                    $pdf->startTransaction();
                    pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX, 3, $curX, $curY, $hideref, $hidedesc);
                    $pageposafter = $pdf->getPage();
                    if ($pageposafter > $pageposbefore) {
                        $pdf->rollbackTransaction(true);
                        $pdf->setPageOrientation('', true, $heightforfooter);
                        pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX, 3, $curX, $curY, $hideref, $hidedesc);
                        $pageposafter = $pdf->getPage();
                        $posyafter = $pdf->GetY();
                        if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot)) && $i == ($nblines - 1)) {
                            $pdf->AddPage('', '', true);
                            if (!empty($tplidx)) {
                                $pdf->useTemplate($tplidx);
                            }
                            if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                $this->_pagehead($pdf, $object, 0, $outputlangs);
                            }
                            $pdf->setPage($pageposafter + 1);
                        } else {
                            $showpricebeforepagebreak = getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE') ? 1 : 0;
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

                    // TTC ONLY: Skip VAT Rate column

                    // Unit price TTC (instead of HT)
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

                    // Discount
                    if ($object->lines[$i]->remise_percent) {
                        $pdf->SetXY($this->posxdiscount - 2, $curY);
                        $remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
                        $pdf->MultiCell($this->postotalht - $this->posxdiscount + 2, 3, $remise_percent, 0, 'R');
                    }

                    // Total TTC line (instead of HT)
                    $total_incl_tax = pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails);
                    $pdf->SetXY($this->postotalht, $curY);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 3, $total_incl_tax, 0, 'R', false);

                    // Collect TVA for internal calculations
                    $tvaligne = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? $object->lines[$i]->multicurrency_total_tva : $object->lines[$i]->total_tva;
                    $vatrate = (string) $object->lines[$i]->tva_tx;
                    if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
                        $vatrate .= '*';
                    }
                    if (!isset($this->tva[$vatrate])) {
                        $this->tva[$vatrate] = 0;
                    }
                    $this->tva[$vatrate] += $tvaligne;

                    if ($posYAfterImage > $posYAfterDescription) {
                        $nexY = $posYAfterImage;
                    }

                    if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
                        $pdf->setPage($pageposafter);
                        $pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
                        $pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
                        $pdf->SetLineStyle(array('dash' => 0));
                    }

                    $nexY += 2;

                    // Multi-page handling
                    while ($pagenb < $pageposafter) {
                        $pdf->setPage($pagenb);
                        $this->_tableau($pdf, ($pagenb == 1 ? $tab_top : $tab_top_newpage), $this->page_hauteur - ($pagenb == 1 ? $tab_top : $tab_top_newpage) - $heightforfooter, 0, $outputlangs, ($pagenb == 1 ? 0 : 1), 1, $object->multicurrency_code);
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
                        $this->_tableau($pdf, ($pagenb == 1 ? $tab_top : $tab_top_newpage), $this->page_hauteur - ($pagenb == 1 ? $tab_top : $tab_top_newpage) - $heightforfooter, 0, $outputlangs, ($pagenb == 1 ? 0 : 1), 1, $object->multicurrency_code);
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
                $this->_tableau($pdf, ($pagenb == 1 ? $tab_top : $tab_top_newpage), $this->page_hauteur - ($pagenb == 1 ? $tab_top : $tab_top_newpage) - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter, 0, $outputlangs, ($pagenb == 1 ? 0 : 1), 0, $object->multicurrency_code);
                $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter + 1;

                $posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);
                $posy = $this->_tableau_tot($pdf, $object, 0, $bottomlasttab, $outputlangs);

                if (!getDolGlobalString('PROPAL_DISABLE_SIGNATURE')) {
                    $posy = $this->_signature_area($pdf, $object, $posy, $outputlangs);
                    $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfCommercialProposalTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name)." PAGESIGN=".$pdf->getPage());
                }

                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                $pdf->Close();
                $pdf->Output($file, 'F');

                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
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
            $this->error = $langs->transnoentities("ErrorConstantNotDefined", "PROP_OUTPUTDIR");
            return 0;
        }
    }

    /**
     * Show table for lines - TTC ONLY version
     */
    protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
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

        if (getDolGlobalString('MAIN_GENERATE_PROPOSALS_WITH_PICTURE')) {
            $pdf->line($this->posxpicture - 1, $tab_top, $this->posxpicture - 1, $tab_top + $tab_height);
        }

        // NO VAT COLUMN

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
        if (empty($hidetop) && $this->atleastonediscount) {
            $pdf->SetXY($this->posxdiscount - 1, $tab_top + 1);
            $pdf->MultiCell($this->postotalht - $this->posxdiscount + 1, 2, $outputlangs->transnoentities("ReductionShort"), '', 'C');
        }
        if ($this->atleastonediscount) {
            $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
        }
        if (empty($hidetop)) {
            $pdf->SetXY($this->postotalht - 1, $tab_top + 1);
            $pdf->MultiCell(30, 2, $outputlangs->transnoentities("TotalTTCShort"), '', 'C');
        }
    }

    /**
     * Show total to pay - TTC ONLY version
     */
    protected function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis = null)
    {
        global $conf;

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
        $index = 0;

        $total_ttc = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetFont('', 'B', $default_font_size - 1);
        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("TotalTTC") : ''), 0, 'L', true);
        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs), 0, 'R', true);

        $pdf->SetFont('', '', $default_font_size - 1);
        $index++;

        if ($deja_regle > 0) {
            $index++;
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', true);
            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs), 0, 'R', true);

            $index++;
            $resteapayer = $total_ttc - $deja_regle;
            $pdf->SetFillColor(224, 224, 224);
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->SetFont('', 'B', $default_font_size - 1);
            $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), 0, 'L', true);
            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), 0, 'R', true);
            $pdf->SetFont('', '', $default_font_size - 1);
        }

        return ($tab2_top + ($tab2_hl * $index));
    }
}
