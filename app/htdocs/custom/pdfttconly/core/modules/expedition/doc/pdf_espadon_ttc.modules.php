<?php
/**
 * PDF Espadon TTC Only - Shipment template showing only TTC amounts
 * Based on pdf_espadon but displays only TTC (no HT, no VAT breakdown)
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/doc/pdf_espadon.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

/**
 * Class to generate PDF shipment with TTC only
 */
class pdf_espadon_ttc extends pdf_espadon
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->name = "espadon_ttc";
        $this->description = "Modèle Espadon - TTC uniquement (sans HT ni TVA)";
    }

    /**
     * Define Array Column Field - TTC version
     * Override to show TTC headers instead of HT and force price columns to be visible
     *
     * @param Expedition    $object         common object
     * @param Translate     $outputlangs    langs
     * @param int           $hidedetails    Do not show line details
     * @param int           $hidedesc       Do not show desc
     * @param int           $hideref        Do not show ref
     * @return void
     */
    public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        // Call parent to get default column definitions
        parent::defineColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

        // Load our custom lang file for TTC labels
        $outputlangs->load('pdfttconly@pdfttconly');

        // Override subprice column to show TTC and FORCE it to be visible
        if (isset($this->cols['subprice'])) {
            $this->cols['subprice']['status'] = true; // Force visible
            $this->cols['subprice']['title']['textkey'] = 'PriceUTTC';
            $this->cols['subprice']['title']['label'] = $outputlangs->transnoentities('PriceUTTC');
        }

        // Override totalexcltax column to show TTC and FORCE it to be visible
        if (isset($this->cols['totalexcltax'])) {
            $this->cols['totalexcltax']['status'] = true; // Force visible
            $this->cols['totalexcltax']['title']['textkey'] = 'TotalTTCShort';
            $this->cols['totalexcltax']['title']['label'] = $outputlangs->transnoentities('TotalTTCShort');
        }
    }

    /**
     * Function to build pdf onto disk - TTC version
     * Override to display TTC prices instead of HT
     *
     * @param  Expedition   $object             Object shipping to generate
     * @param  Translate    $outputlangs        Lang output object
     * @param  string       $srctemplatepath    Full path of source filename
     * @param  int          $hidedetails        Do not show line details
     * @param  int          $hidedesc           Do not show desc
     * @param  int          $hideref            Do not show ref
     * @return int                              1 if OK, <=0 if KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $conf, $langs, $hookmanager;

        $object->fetch_thirdparty();

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        if (getDolGlobalString('MAIN_USE_FPDF')) {
            $outputlangs->charset_output = 'ISO-8859-1';
        }

        // Load traductions files required by page
        $outputlangs->loadLangs(array("main", "bills", "orders", "products", "dict", "companies", "other", "propal", "deliveries", "sendings", "productbatch", "compta"));
        $outputlangs->load('pdfttconly@pdfttconly');

        // Show Draft Watermark
        if ($object->statut == $object::STATUS_DRAFT && (getDolGlobalString('SHIPPING_DRAFT_WATERMARK'))) {
            $this->watermark = getDolGlobalString('SHIPPING_DRAFT_WATERMARK');
        }

        global $outputlangsbis;
        $outputlangsbis = null;
        if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
            $outputlangsbis = new Translate('', $conf);
            $outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
            $outputlangsbis->loadLangs(array("main", "bills", "orders", "products", "dict", "companies", "other", "propal", "deliveries", "sendings", "productbatch", "compta"));
            $outputlangsbis->load('pdfttconly@pdfttconly');
        }

        $nblines = count($object->lines);

        // Loop on each lines to detect if there is at least one image to show
        $realpatharray = array();
        $this->atleastonephoto = false;
        if (getDolGlobalString('MAIN_GENERATE_SHIPMENT_WITH_PICTURE')) {
            $objphoto = new Product($this->db);

            for ($i = 0; $i < $nblines; $i++) {
                if (empty($object->lines[$i]->fk_product)) {
                    continue;
                }

                $objphoto->fetch($object->lines[$i]->fk_product);

                if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
                    $pdir = get_exdir($object->lines[$i]->fk_product, 2, 0, 0, $objphoto, 'product').$object->lines[$i]->fk_product."/photos/";
                    $dir = $conf->product->dir_output.'/'.$pdir;
                } else {
                    $pdir = get_exdir(0, 0, 0, 0, $objphoto, 'product');
                    $dir = $conf->product->dir_output.'/'.$pdir;
                }

                $realpath = '';

                $arephoto = false;
                foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
                    if (!getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES')) {
                        if ($obj['photo_vignette']) {
                            $filename = $obj['photo_vignette'];
                        } else {
                            $filename = $obj['photo'];
                        }
                    } else {
                        $filename = $obj['photo'];
                    }

                    $realpath = $dir.$filename;
                    $arephoto = true;
                    $this->atleastonephoto = true;
                    break;
                }

                if ($realpath && $arephoto) {
                    $realpatharray[$i] = $realpath;
                }
            }
        }

        if (count($realpatharray) == 0) {
            $this->posxpicture = $this->posxweightvol;
        }

        if ($conf->expedition->dir_output) {
            // Definition of $dir and $file
            if ($object->specimen) {
                $dir = $conf->expedition->dir_output."/sending";
                $file = $dir."/SPECIMEN.pdf";
            } else {
                $expref = dol_sanitizeFileName($object->ref);
                $dir = $conf->expedition->dir_output."/sending/".$expref;
                $file = $dir."/".$expref.".pdf";
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

                $nblines = is_array($object->lines) ? count($object->lines) : 0;

                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);
                $heightforinfotot = 8;
                $heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5);
                $heightforfooter = $this->marge_basse + 8;
                if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS')) {
                    $heightforfooter += 6;
                }
                $pdf->setAutoPageBreak(true, 0);

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                if (!getDolGlobalString('MAIN_DISABLE_FPDI') && getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("Shipment"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Shipment"));
                if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
                    $pdf->SetCompression(false);
                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

                // New page
                $pdf->AddPage();
                if (!empty($tplidx)) {
                    $pdf->useTemplate($tplidx);
                }
                $pagenb++;
                $top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 90;
                $tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);

                $tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

                $this->posxdesc = $this->marge_gauche + 1;

                // Incoterm
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
                        $height_incoterms += 4;
                    }
                }

                // Displays notes
                $notetoshow = empty($object->note_public) ? '' : $object->note_public;

                $extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
                if (!empty($extranote)) {
                    $notetoshow = dol_concatdesc($notetoshow, $extranote);
                }

                if (!empty($notetoshow) || !empty($object->tracking_number)) {
                    $tab_top -= 2;
                    $tab_topbeforetrackingnumber = $tab_top;

                    // Tracking number
                    if (!empty($object->tracking_number)) {
                        $height_trackingnumber = 4;

                        $pdf->SetFont('', 'B', $default_font_size - 2);
                        $pdf->writeHTMLCell(60, $height_trackingnumber, $this->posxdesc - 1, $tab_top - 1, $outputlangs->transnoentities("TrackingNumber") . " : " . $object->tracking_number, 0, 1, false, true, 'L');
                        $tab_top_alt = $pdf->GetY();

                        $object->getUrlTrackingStatus($object->tracking_number);
                        if (!empty($object->tracking_url)) {
                            if ($object->shipping_method_id > 0) {
                                $code = $outputlangs->getLabelFromKey($this->db, (string) $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
                                $label = '';
                                if ($object->tracking_url != $object->tracking_number) {
                                    $label .= $outputlangs->trans("LinkToTrackYourPackage")."<br>";
                                }
                                $label .= $outputlangs->trans("SendingMethod").": ".$outputlangs->trans("SendingMethod".strtoupper($code));
                                if ($object->tracking_url != $object->tracking_number) {
                                    $label .= " : ";
                                    $label .= $object->tracking_url;
                                }

                                $height_trackingnumber += 4;
                                $pdf->SetFont('', 'B', $default_font_size - 2);
                                $pdf->writeHTMLCell(60, $height_trackingnumber, $this->posxdesc - 1, $tab_top_alt, $label, 0, 1, false, true, 'L');
                            }
                        }
                        $tab_top = $pdf->GetY();
                    }

                    // Notes
                    $pagenb = $pdf->getPage();
                    if (!empty($notetoshow) || !empty($object->tracking_number)) {
                        $tab_top -= 1;

                        $tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
                        $pageposbeforenote = $pagenb;

                        $substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
                        complete_substitutions_array($substitutionarray, $outputlangs, $object);
                        $notetoshow = make_substitutions((string) $notetoshow, $substitutionarray, $outputlangs);
                        $notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

                        $pdf->startTransaction();

                        $pdf->SetFont('', '', $default_font_size - 1);
                        $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                        $pageposafternote = $pdf->getPage();
                        $posyafter = $pdf->GetY();

                        if ($pageposafternote > $pageposbeforenote) {
                            $pdf->rollbackTransaction(true);

                            while ($pagenb < $pageposafternote) {
                                $pdf->AddPage();
                                $pagenb++;
                                if (!empty($tplidx)) {
                                    $pdf->useTemplate($tplidx);
                                }
                                if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                    $this->_pagehead($pdf, $object, 0, $outputlangs);
                                }
                                $pdf->setTopMargin($tab_top_newpage);
                                $pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext);
                            }

                            $pdf->setPage($pageposbeforenote);
                            $pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext);
                            $pdf->SetFont('', '', $default_font_size - 1);
                            $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                            $pageposafternote = $pdf->getPage();

                            $posyafter = $pdf->GetY();

                            if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
                                $pdf->AddPage('', '', true);
                                $pagenb++;
                                $pageposafternote++;
                                $pdf->setPage($pageposafternote);
                                $pdf->setTopMargin($tab_top_newpage);
                                $pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext);
                            }

                            $i = $pageposbeforenote;
                            while ($i < $pageposafternote) {
                                $pdf->setPage($i);

                                $pdf->SetDrawColor(128, 128, 128);
                                if ($i > $pageposbeforenote) {
                                    if (empty($height_trackingnumber)) {
                                        $height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
                                    } else {
                                        $height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter) + $height_trackingnumber + 1;
                                        $tab_top_newpage = $tab_topbeforetrackingnumber;
                                    }
                                    $pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 2, $this->corner_radius, '1234', 'D');
                                } else {
                                    if (empty($height_trackingnumber)) {
                                        $height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
                                    } else {
                                        $height_note = $this->page_hauteur - ($tab_top + $heightforfooter) + $height_trackingnumber + 1;
                                        $tab_top = $tab_topbeforetrackingnumber;
                                    }
                                    $pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 2, $this->corner_radius, '1234', 'D');
                                }

                                $pdf->setPageOrientation('', true, 0);
                                $this->_pagefoot($pdf, $object, $outputlangs, 1);

                                $i++;
                            }

                            $pdf->setPage($pageposafternote);
                            if (!empty($tplidx)) {
                                $pdf->useTemplate($tplidx);
                            }
                            if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                $this->_pagehead($pdf, $object, 0, $outputlangs);
                            }
                            $height_note = $posyafter - $tab_top_newpage;
                            $pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1, $this->corner_radius, '1234', 'D');
                        } else {
                            $pdf->commitTransaction();
                            $posyafter = $pdf->GetY();
                            if (empty($height_trackingnumber)) {
                                $height_note = $posyafter - $tab_top + 1;
                            } else {
                                $height_note = $posyafter - $tab_top + $height_trackingnumber + 1;
                                $tab_top = $tab_topbeforetrackingnumber;
                            }
                            $pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 2, $this->corner_radius, '1234', 'D');

                            if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
                                $pdf->AddPage('', '', true);
                                $pagenb++;
                                $pageposafternote++;
                                $pdf->setPage($pageposafternote);
                                if (!empty($tplidx)) {
                                    $pdf->useTemplate($tplidx);
                                }
                                if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                    $this->_pagehead($pdf, $object, 0, $outputlangs);
                                }

                                $posyafter = $tab_top_newpage;
                            }
                        }

                        $tab_height -= $height_note;
                        $tab_top = $posyafter + 6;
                    } else {
                        $height_note = 0;
                    }
                }

                // Show barcode
                $height_barcode = 0;
                if (isModEnabled('barcode') && getDolGlobalString('BARCODE_ON_SHIPPING_PDF')) {
                    require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/doc/tcpdfbarcode.modules.php';

                    $encoding = 'QRCODE';
                    $module = new modTcpdfbarcode();
                    $barcode_path = '';
                    $result = 0;
                    if ($module->encodingIsSupported($encoding)) {
                        $result = $module->writeBarCode($object->ref, $encoding);

                        $newcode = $object->ref;
                        if (!preg_match('/^\w+$/', $newcode) || dol_strlen($newcode) > 32) {
                            $newcode = dol_hash($newcode, 'md5');
                        }
                        $barcode_path = $conf->barcode->dir_temp . '/barcode_' . $newcode . '_' . $encoding . '.png';
                    }

                    if ($result > 0) {
                        $tab_top -= 2;

                        $pdf->Image($barcode_path, $this->marge_gauche, $tab_top, 20, 20);

                        $nexY = $pdf->GetY();
                        $height_barcode = 20;

                        $tab_top += 22;
                    } else {
                        $this->error = 'Failed to generate barcode';
                    }
                }

                // Use new auto column system
                $this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

                // Table simulation to know the height of the title line
                $pdf->startTransaction();
                $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs);
                $pdf->rollbackTransaction(true);

                $nexY = $tab_top + $this->tabTitleHeight;

                // Loop on each lines
                $pageposbeforeprintlines = $pdf->getPage();
                $pagenb = $pageposbeforeprintlines;
                for ($i = 0; $i < $nblines; $i++) {
                    $curY = $nexY;

                    $sub_options = $object->lines[$i]->extraparams["subtotal"] ?? array();

                    if (($curY + 6) > ($this->page_hauteur - $heightforfooter) || isset($sub_options['titleforcepagebreak']) && !($pdf->getNumPages() == 1 && $curY == $tab_top + $this->tabTitleHeight)) {
                        $pdf->AddPage();
                        if (!empty($tplidx)) {
                            $pdf->useTemplate($tplidx);
                        }

                        $pdf->setPage($pdf->getNumPages());
                        $nexY = $curY = $tab_top_newpage;
                    }

                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->SetTextColor(0, 0, 0);

                    $imglinesize = array();
                    if (!empty($realpatharray[$i])) {
                        $imglinesize = pdf_getSizeForImage($realpatharray[$i]);
                    }

                    $pdf->setTopMargin($tab_top_newpage);
                    $pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext + $heightforinfotot);
                    $pageposbefore = $pdf->getPage();

                    $showpricebeforepagebreak = 1;
                    $posYAfterImage = 0;
                    $posYAfterDescription = 0;
                    $heightforsignature = 0;

                    if ($this->getColumnStatus('photo')) {
                        if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {
                            $pdf->AddPage('', '', true);
                            if (!empty($tplidx)) {
                                $pdf->useTemplate($tplidx);
                            }
                            $pdf->setPage($pageposbefore + 1);

                            $curY = $tab_top_newpage;

                            if (getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
                                $showpricebeforepagebreak = 1;
                            } else {
                                $showpricebeforepagebreak = 0;
                            }
                        }

                        if (!empty($this->cols['photo']) && isset($imglinesize['width']) && isset($imglinesize['height'])) {
                            $pdf->Image($realpatharray[$i], $this->getColumnContentXStart('photo'), $curY + 1, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);
                            $posYAfterImage = $curY + $imglinesize['height'];
                        }
                    }

                    // Description of product line
                    if ($this->getColumnStatus('desc')) {
                        if ($object->lines[$i]->special_code == SUBTOTALS_SPECIAL_CODE) {
                            $bg_color = colorStringToArray(getDolGlobalString("SUBTOTAL_BACK_COLOR_LEVEL_".abs($object->lines[$i]->qty)));
                            $pdf->SetFillColor($bg_color[0], $bg_color[1], $bg_color[2]);
                            $pdf->SetXY($pdf->GetX() + 1, $curY);
                            $pdf->MultiCell($this->page_largeur - $this->marge_droite  - $this->marge_gauche - 2, 6, '', 0, '', true);
                            $previous_align = array();
                            $previous_align['align'] = $this->cols['desc']['content']['align'];
                            if ($object->lines[$i]->qty < 0) {
                                $langs->load("subtotals");
                                $object->lines[$i]->desc = $langs->trans("SubtotalOf", $object->lines[$i]->desc);
                                if ($previous_align['align'] == 'L') {
                                    $this->cols['desc']['content']['align'] = 'R';
                                } elseif ($previous_align['align'] == 'R') {
                                    $this->cols['desc']['content']['align'] = 'L';
                                }
                            }
                            $this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
                            $this->setAfterColsLinePositionsData('desc', $pdf->GetY(), $pdf->getPage());
                            $this->cols['desc']['content']['align'] = $previous_align['align'];
                        } else {
                            $pdf->startTransaction();

                            $this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

                            $pageposafter = $pdf->getPage();
                            if ($pageposafter > $pageposbefore) {
                                $pdf->rollbackTransaction(true);

                                $this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

                                $pageposafter = $pdf->getPage();
                                $posyafter = $pdf->GetY();
                                if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {
                                    if ($i == ($nblines - 1)) {
                                        $pdf->AddPage('', '', true);
                                        if (!empty($tplidx)) {
                                            $pdf->useTemplate($tplidx);
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
                        }
                    }

                    $nexY = max($pdf->GetY(), $posYAfterImage);
                    $pageposafter = $pdf->getPage();

                    $pdf->setPage($pageposbefore);
                    $pdf->setTopMargin($this->marge_haute);
                    $pdf->setPageOrientation('', true, 0);

                    if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
                        $pdf->setPage($pageposafter);
                        $curY = $tab_top_newpage;
                    }

                    if ($pageposafter > $pageposbefore) {
                        $pdf->setPage($pageposafter);
                        $curY = $tab_top_newpage;
                    }

                    $pdf->SetFont('', '', $default_font_size - 1);

                    // # of line
                    if ($this->getColumnStatus('position')) {
                        $this->printStdColumnContent($pdf, $curY, 'position', (string) ($i + 1));
                    }

                    // weight
                    $weighttxt = '';
                    if (empty($object->lines[$i]->fk_product_type) && $object->lines[$i]->weight && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $weighttxt = round($object->lines[$i]->weight * $object->lines[$i]->qty_shipped, getDolGlobalInt('SHIPMENT_ROUND_WEIGHT_ON_PDF', 5)).' '.measuringUnitString(0, "weight", $object->lines[$i]->weight_units, 1);
                    }
                    $voltxt = '';
                    if (empty($object->lines[$i]->fk_product_type) && $object->lines[$i]->volume && !getDolGlobalString('SHIPPING_PDF_HIDE_VOLUME') && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $voltxt = round($object->lines[$i]->volume * $object->lines[$i]->qty_shipped, getDolGlobalInt('SHIPMENT_ROUND_VOLUME_ON_PDF', 5)).' '.measuringUnitString(0, "volume", $object->lines[$i]->volume_units ? $object->lines[$i]->volume_units : 0, 1);
                    }

                    // weight and volume
                    if ($this->getColumnStatus('weight') && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $this->printStdColumnContent($pdf, $curY, 'weight', $weighttxt.(($weighttxt && $voltxt) ? '<br>' : '').$voltxt);
                        $nexY = max($pdf->GetY(), $nexY);
                    }

                    if ($this->getColumnStatus('qty_asked') && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $this->printStdColumnContent($pdf, $curY, 'qty_asked', (string) $object->lines[$i]->qty_asked);
                        $nexY = max($pdf->GetY(), $nexY);
                    }

                    if ($this->getColumnStatus('unit_order') && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $this->printStdColumnContent($pdf, $curY, 'unit_order', measuringUnitString((int) $object->lines[$i]->fk_unit));
                        $nexY = max($pdf->GetY(), $nexY);
                    }

                    if ($this->getColumnStatus('qty_shipped') && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $this->printStdColumnContent($pdf, $curY, 'qty_shipped', (string) $object->lines[$i]->qty_shipped);
                        $nexY = max($pdf->GetY(), $nexY);
                    }

                    // =====================================================
                    // TTC VERSION: Display price with tax instead of HT
                    // Get values from linked order line to include discounts
                    // =====================================================
                    $subprice_ttc = 0;
                    $total_line_ttc = 0;

                    // Try to get the order line for accurate pricing with discounts
                    if (!empty($object->lines[$i]->fk_origin_line)) {
                        require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
                        $orderline = new OrderLine($this->db);
                        if ($orderline->fetch($object->lines[$i]->fk_origin_line) > 0) {
                            // Use order line values which include discounts
                            $subprice_ht = $orderline->subprice;
                            $remise = $orderline->remise_percent;
                            $tva_tx = $orderline->tva_tx;
                            $qty = $object->lines[$i]->qty_shipped;

                            // Calculate unit price TTC after discount
                            $subprice_after_discount = $subprice_ht * (1 - ($remise / 100));
                            $subprice_ttc = $subprice_after_discount * (1 + ($tva_tx / 100));

                            // Calculate line total TTC
                            $total_line_ttc = $subprice_ttc * $qty;
                        }
                    }

                    // Fallback: calculate from expedition line if order line not found
                    if ($subprice_ttc == 0 && !empty($object->lines[$i]->subprice)) {
                        $subprice_ht = $object->lines[$i]->subprice;
                        $tva_tx = $object->lines[$i]->tva_tx;
                        $qty = $object->lines[$i]->qty_shipped;
                        $subprice_ttc = $subprice_ht * (1 + ($tva_tx / 100));
                        $total_line_ttc = $subprice_ttc * $qty;
                    }

                    if ($this->getColumnStatus('subprice') && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $this->printStdColumnContent($pdf, $curY, 'subprice', price($subprice_ttc, 0, $outputlangs));
                        $nexY = max($pdf->GetY(), $nexY);
                    }

                    if ($this->getColumnStatus('totalexcltax') && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        $this->printStdColumnContent($pdf, $curY, 'totalexcltax', price($total_line_ttc, 0, $outputlangs));
                        $nexY = max($pdf->GetY(), $nexY);
                    }

                    // Extrafields
                    if (!empty($object->lines[$i]->array_options) && $object->lines[$i]->special_code != SUBTOTALS_SPECIAL_CODE) {
                        foreach ($object->lines[$i]->array_options as $extrafieldColKey => $extrafieldValue) {
                            if ($this->getColumnStatus($extrafieldColKey)) {
                                $extrafieldValue = $this->getExtrafieldContent($object->lines[$i], $extrafieldColKey, $outputlangs);
                                $this->printStdColumnContent($pdf, $curY, $extrafieldColKey, $extrafieldValue);
                                $nexY = max($pdf->GetY(), $nexY);
                            }
                        }
                    }

                    $parameters = array(
                        'object' => $object,
                        'i' => $i,
                        'pdf' => & $pdf,
                        'curY' => & $curY,
                        'nexY' => & $nexY,
                        'outputlangs' => $outputlangs,
                        'hidedetails' => $hidedetails
                    );
                    $reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this);

                    // Add line
                    if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
                        $pdf->setPage($pageposafter);
                        $pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
                        $pdf->line($this->marge_gauche, $nexY, $this->page_largeur - $this->marge_droite, $nexY);
                        $pdf->SetLineStyle(array('dash' => 0));
                    }

                    // Detect if some page were added automatically and output _tableau for past pages
                    while ($pagenb < $pageposafter) {
                        $pdf->setPage($pagenb);
                        if ($pagenb == $pageposbeforeprintlines) {
                            $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
                        } else {
                            $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
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
                            $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
                        } else {
                            $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
                        }
                        $this->_pagefoot($pdf, $object, $outputlangs, 1);
                        // New page
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
                    $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
                    $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                } else {
                    $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
                    $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                }

                // Display total area - TTC version
                $posy = $this->_tableau_tot($pdf, $object, 0, $bottomlasttab, $outputlangs);

                // Pagefoot
                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                $pdf->Close();

                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
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
            $this->error = $langs->transnoentities("ErrorConstantNotDefined", "EXP_OUTPUTDIR");
            return 0;
        }
    }

    /**
     * Show total to pay - TTC ONLY version
     *
     * @param TCPDF $pdf Object PDF
     * @param Expedition $object Object shipment
     * @param float $deja_regle Already paid amount
     * @param float $posy Position Y
     * @param Translate $outputlangs Output language
     * @return float Position Y
     */
    protected function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
    {
        global $conf, $mysoc;

        $sign = 1;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $tab2_top = $posy;
        $tab2_hl = 4;
        $pdf->SetFont('', 'B', $default_font_size - 1);

        // Total table
        $col1x = $this->posxweightvol - 50;
        $col2x = $this->posxweightvol;
        if (!getDolGlobalString('SHIPPING_PDF_HIDE_ORDERED')) {
            $largcol2 = ($this->posxqtyordered - $this->posxweightvol);
        } else {
            $largcol2 = ($this->posxqtytoship - $this->posxweightvol);
        }

        $useborder = 0;
        $index = 0;

        $totalWeighttoshow = '';
        $totalVolumetoshow = '';

        // Load dim data
        $tmparray = $object->getTotalWeightVolume();
        $totalWeight = $tmparray['weight'];
        $totalVolume = $tmparray['volume'];
        $totalOrdered = $tmparray['ordered'];
        $totalToShip = $tmparray['toship'];
        if ($object->trueWidth && $object->trueHeight && $object->trueDepth) {
            $object->trueVolume = price(((float) $object->trueWidth * (float) $object->trueHeight * (float) $object->trueDepth), 0, $outputlangs, 0, 0);
            $object->volume_units = (float) $object->size_units * 3;
        }

        if (!empty($totalWeight)) {
            $totalWeighttoshow = showDimensionInBestUnit($totalWeight, 0, "weight", $outputlangs, -1, 'no', 1);
        }
        if (!empty($totalVolume) && !getDolGlobalString('SHIPPING_PDF_HIDE_VOLUME')) {
            $totalVolumetoshow = showDimensionInBestUnit($totalVolume, 0, "volume", $outputlangs, -1, 'no', 1);
        }
        if (!empty($object->trueWeight)) {
            $totalWeighttoshow = showDimensionInBestUnit($object->trueWeight, (int) $object->weight_units, "weight", $outputlangs);
        }
        if (!empty($object->trueVolume) && !getDolGlobalString('SHIPPING_PDF_HIDE_VOLUME')) {
            if ($object->volume_units < 50) {
                $totalVolumetoshow = showDimensionInBestUnit($object->trueVolume, $object->volume_units, "volume", $outputlangs);
            } else {
                $totalVolumetoshow =  price($object->trueVolume, 0, $outputlangs, 0, 0).' '.measuringUnitString(0, "volume", $object->volume_units);
            }
        }

        if ($this->getColumnStatus('desc')) {
            $this->printStdColumnContent($pdf, $tab2_top, 'desc', $outputlangs->transnoentities("Total"));
        }

        if ($this->getColumnStatus('weight')) {
            if ($totalWeighttoshow) {
                $this->printStdColumnContent($pdf, $tab2_top, 'weight', $totalWeighttoshow);
                $index++;
            }

            if ($totalVolumetoshow) {
                $y = $tab2_top + ($tab2_hl * $index);
                $this->printStdColumnContent($pdf, $y, 'weight', $totalVolumetoshow);
            }
        }

        if ($this->getColumnStatus('qty_asked') && $totalOrdered) {
            $this->printStdColumnContent($pdf, $tab2_top, 'qty_asked', (string) $totalOrdered);
        }

        if ($this->getColumnStatus('qty_shipped') && $totalToShip) {
            $this->printStdColumnContent($pdf, $tab2_top, 'qty_shipped', $totalToShip);
        }

        // =====================================================
        // TTC VERSION: Get total TTC from linked order
        // =====================================================
        $total_ttc = 0;

        // Try to get total from linked order (includes discounts)
        if (!empty($object->origin) && $object->origin == 'commande' && !empty($object->origin_id)) {
            require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $order = new Commande($this->db);
            if ($order->fetch($object->origin_id) > 0) {
                $total_ttc = $order->total_ttc;
            }
        } elseif (!empty($object->linkedObjectsIds['commande'])) {
            // Alternative: check linkedObjectsIds
            require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $order = new Commande($this->db);
            foreach ($object->linkedObjectsIds['commande'] as $orderid) {
                if ($order->fetch($orderid) > 0) {
                    $total_ttc = $order->total_ttc;
                    break;
                }
            }
        }

        if ($this->getColumnStatus('subprice')) {
            $this->printStdColumnContent($pdf, $tab2_top, 'subprice', '');
        }

        if ($this->getColumnStatus('totalexcltax') && $total_ttc > 0) {
            $this->printStdColumnContent($pdf, $tab2_top, 'totalexcltax', price($total_ttc, 0, $outputlangs));
        }

        $pdf->SetTextColor(0, 0, 0);

        return ($tab2_top + ($tab2_hl * $index));
    }
}
