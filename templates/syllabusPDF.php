<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_syllabusPDF(&$ciniki, $tnid, $args) {

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the festival
    //
    $strsql = "SELECT festivals.id, "
        . "festivals.name, "
        . "festivals.permalink, "
        . "festivals.flags, "
        . "festivals.start_date, "
        . "festivals.end_date, "
        . "festivals.earlybird_date, "
        . "festivals.live_date, "
        . "festivals.virtual_date, "
        . "festivals.primary_image_id, "
        . "festivals.description, "
        . "festivals.document_logo_id, "
        . "festivals.document_header_msg, "
        . "festivals.document_footer_msg "
        . "FROM ciniki_musicfestivals AS festivals "
        . "WHERE festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'flags',
                'start_date', 'end_date', 'primary_image_id', 'description', 
                'earlybird_date', 'live_date', 'virtual_date',
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.37', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.38', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the sections, categories and classes
    //
    $strsql = "SELECT classes.id, "
        . "classes.festival_id, "
        . "classes.category_id, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.synopsis AS section_synopsis, "
        . "sections.description AS section_description, "
        . "sections.live_description AS section_live_description, "
        . "sections.virtual_description AS section_virtual_description, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.code, "
        . "classes.name, "
        . "classes.permalink, "
        . "classes.sequence, "
        . "classes.synopsis as class_synopsis, "
        . "classes.flags, "
        . "classes.feeflags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id ";
    if( isset($args['groupname']) && $args['groupname'] != '' ) {
        $strsql .= "AND categories.groupname = '" . ciniki_core_dbQuote($ciniki, $args['groupname']) . "' ";
    }
        $strsql .= "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id ";
    if( isset($args['live-virtual']) && $args['live-virtual'] == 'live' ) {
        $strsql .= "AND classes.fee > 0 ";
    } elseif( isset($args['live-virtual']) && $args['live-virtual'] == 'virtual' ) {
        $strsql .= "AND classes.virtual_fee > 0 ";
    }
    $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (sections.flags&0x01) = 0 "  // Visible
        . "";
    if( isset($args['section_id']) && $args['section_id'] != '' && $args['section_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    } 
    if( isset($args['syllabus']) ) {
        $strsql .= "AND sections.syllabus = '" . ciniki_core_dbQuote($ciniki, $args['syllabus']) . "' ";
    }
    $strsql .= "ORDER BY sections.sequence, sections.name, "
            . "categories.sequence, categories.name, "
            . "classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('name'=>'section_name', 'synopsis'=>'section_synopsis', 'description'=>'section_description',
                'live_description'=>'section_live_description', 'virtual_description'=>'section_virtual_description',
                )),
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'feeflags',
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee', 'synopsis'=>'class_synopsis')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['sections']) ) {
        $sections = $rc['sections'];
    } else {
        $sections = array();
    }

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 15;
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $tenant_details = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $img_width = 0;
            if( $this->header_image != null ) {
                $height = $this->header_image->getImageHeight();
                $width = $this->header_image->getImageWidth();
                $image_ratio = $width/$height;
                $img_width = 60;
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-10, 'JPEG', '', 'L', 2, '150');
                }
            }

            $this->Ln(8);
            $this->SetFont('helvetica', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('helvetica', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('helvetica', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }

        //
        // Display list of classes
        //
        public function ClassesAddHeaders($w, $headers, $fields) {
            $this->SetFont('', 'B', '12');
            $lh = $this->getStringHeight($w[0], $headers[0]);
            foreach($headers as $i => $header) {
                if( $fields[$i] == 'earlybird_fee'
                    || $fields[$i] == 'fee'
                    || $fields[$i] == 'virtual_fee'
                    || $fields[$i] == 'earlybird_plus_fee'
                    || $fields[$i] == 'plus_fee'
                    ) {
                    $this->Cell($w[$i], $lh, $header, 1, 0, 'C', 1);
                } else {
                    $this->Cell($w[$i], $lh, $header, 1, 0, 'L', 1);
                }
            }
            $this->Ln($lh);
            $this->SetFont('', '', '12');
        }

        //
        // Display list of classes
        //
        public function ClassesAdd($w, $category, $headers, $fields) {

            $fill = 1;
            if( isset($headers[0]) && $headers[0] != '' ) {
                $this->ClassesAddHeaders($w, $headers, $fields);
                $fill = 0;
            }
            $this->SetFont('', '', '12');
            foreach($category['classes'] as $class) {
                $lh = 0;
                $lhs = 0;
                foreach($fields as $i => $field) {
                    if( $field == 'code_name_synopsis' ) {
                        $lh = $this->getStringHeight($w[$i], $class['code'] . ' - ' . $class['name']);
                        if( $class['synopsis'] != '' ) {
                            $this->setCellPaddings(2, 2, 2, 1);
                            $lh = $this->getStringHeight($w[$i], $class['code'] . ' - ' . $class['name']);
                            $indent = $this->getStringWidth($class['code'] . ' - ') + 2;
                            $this->setCellPaddings($indent, 0, 2, 2);
                            $lhs = $this->getStringHeight($w[$i], strip_tags($class['synopsis']));
                            $this->setCellPaddings(2, 2, 2, 2);
                        }
                    } elseif( $field == 'code_name' ) {
                        $lh = $this->getStringHeight($w[$i], $class['code'] . ' - ' . $class['name']);
                    }
                }
                if( $this->getY() > ($this->getPageHeight() - $lh - $lhs - 22) ) {
                    $this->AddPage();
                    $this->SetFont('', 'B', '18');
                    $this->MultiCell(180, 10, $category['name'] . ' (continued)', 0, 'L', 0, 1);
                    $this->SetFont('', '', '12');
                    if( isset($headers[0]) && $headers[0] != '' ) {
                        $this->ClassesAddHeaders($w, $headers, $fields);
                        $fill = 0;
                    }
                }
                
                foreach($fields as $i => $field) {
                    if( $field == 'code_name_synopsis' ) {
                        if( $class['synopsis'] != '' ) {
                            $x = $this->getX();
                            $y = $this->getY();
                            $this->setCellPaddings(2, 2, 2, 1);
                            $this->MultiCell($w[$i], $lh, $class['code'] . ' - ' . $class['name'], 'LT', 'L', $fill, 1);
                            $this->SetFont('', 'I', '12');
                            $this->setCellPaddings($indent, 0, 2, 2);
                            $this->writeHTMLCell($w[$i], $lhs, '', '', preg_replace("/\n/", '<br/>', $class['synopsis']), 'LB', 0, $fill);
                            $this->setCellPaddings(2, 2, 2, 2);
                            $this->SetFont('', '', '12');
                            $this->setY($y);
                            $this->setX($x+$w[$i]);
                        } else {
                            $this->MultiCell($w[$i], $lh, $class['code'] . ' - ' . $class['name'], 'LTB', 'L', $fill, 0);
                        }
                    } elseif( $field == 'code_name' ) {
                        $lh = $this->getStringHeight($w[$i], $class['code'] . ' - ' . $class['name']);
                    } else {
                        $this->setCellPaddings(2, 2, 3, 2);
                        $val = '$' . number_format($class[$field], 2);
                        if( $field == 'earlybird_fee' && ($class['feeflags']&0x01) == 0 ) {
                            $val = 'n/a';
                        }
                        elseif( $field == 'fee' && ($class['feeflags']&0x02) == 0 ) {
                            $val = 'n/a';
                        }
                        elseif( $field == 'virtual_fee' && ($class['feeflags']&0x08) == 0 ) {
                            $val = 'n/a';
                        }
                        elseif( $field == 'earlybird_plus_fee' && ($class['feeflags']&0x10) == 0 ) {
                            $val = 'n/a';
                        }
                        elseif( $field == 'plus_fee' && ($class['feeflags']&0x20) == 0 ) {
                            $val = 'n/a';
                        }
                        if( !isset($headers[0]) || $headers[0] == '' ) {
                            $this->MultiCell($w[$i], $lh+$lhs, $val, 'TRB', 'R', $fill, 0, '', '', true, 0, false, true, ($lh+$lhs), 'M');
                        } else {
                            $this->MultiCell($w[$i], $lh+$lhs, $val, 'TRBL', 'C', $fill, 0, '', '', true, 0, false, true, ($lh+$lhs), 'M');
                        }
                        $this->setCellPaddings(2, 2, 2, 2);
                    }
                }

                $this->Ln($lh+$lhs);
                $fill=!$fill;
            } 
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = $festival['document_footer_msg'];

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
    }

    //
    // Load the header image
    //
    if( isset($festival['document_logo_id']) && $festival['document_logo_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, $festival['document_logo_id'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Syllabus');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.1);

    //
    // Go through the sections, categories and classes
    //
    $w = array(30, 120, 30);
    foreach($sections as $section) {
        if( isset($args['live-virtual']) && $args['live-virtual'] == 'live' && $section['live_description'] != '' ) {
            $section['description'] = $section['live_description'];
        } elseif( isset($args['live-virtual']) && $args['live-virtual'] == 'virtual' && $section['virtual_description'] != '' ) {
            $section['description'] = $section['virtual_description'];
        }
        //
        // Start a new section
        //
        if( isset($args['groupname']) && $args['groupname'] != '' ) {
            $pdf->header_sub_title = $section['name'] . ' - ' . $args['groupname'] . ' Syllabus';
        } else {
            $pdf->header_sub_title = $section['name'] . ' Syllabus';
        }
        $pdf->AddPage();

        $pdf->SetFont('', 'B', '18');
        if( isset($args['groupname']) && $args['groupname'] != '' ) {
            $pdf->MultiCell(180, 5, $section['name'] . ' - ' . $args['groupname'], 0, 'L', 0, 1);
        } else {
            $pdf->MultiCell(180, 5, $section['name'], 0, 'L', 0, 1);
        }
        $pdf->SetFont('', '', '12');
        if( isset($section['description']) && $section['description'] != '' ) {
            $pdf->writeHTMLCell(180, '', '', '', preg_replace("/\n/", '<br/>', $section['description']), 0, 1);
        }

        //
        // Output the categories
        //
        $newpage = 'yes';
        foreach($section['categories'] as $category) {
            //
            // Check if enough room
            //
            $lh = 9;
            $description = '';
            if( $category['description'] != '' ) {
                $s_height = $pdf->getStringHeight(180, $category['description']);
                $description = $category['description'];
            } elseif( $category['synopsis'] != '' ) {
                $s_height = $pdf->getStringHeight(180, $category['synopsis']);
                $description = $category['synopsis'];
            } else {
                $s_height = 0;
            }

            $pdf->SetFont('', 'B', '18');
            $lh = $pdf->getStringHeight(180, $category['name']);

            //
            // Determine if new page should be started
            //
            if( $newpage == 'no' && $pdf->getY() > $pdf->getPageHeight() - 50 - $s_height - $lh) {
                $pdf->AddPage();
                $newpage = 'yes';
            } elseif( $newpage == 'no' ) {
                $pdf->Ln(4);
            }
            $newpage = 'no';

            $pdf->MultiCell(180, 5, $category['name'], 0, 'L', 0, 1);
            $pdf->SetFont('', '', '12');
            if( $description != '' ) {
                $pdf->writeHTMLCell(180, '', '', '', preg_replace("/\n/", '<br/>', $description), 0, 1);
            }
            $fill = 1;
            $pdf->Ln(3);
            
            //
            // Output the classes
            //

            //
            // Adjudication plus
            //
            if( ($festival['flags']&0x10) == 0x10 ) {
                $earlybird = 'no';
                if( ($festival['flags']&0x20) == 0x20 && $festival['earlybird_date'] != '0000-00-00 00:00:00' ) {
                    $earlybird_dt = new DateTime($festival['earlybird_date'], new DateTimezone('UTC'));
                    $now_dt = new DateTime('now', new DateTimezone('UTC'));
                    if( $now_dt < $earlybird_dt ) {
                        $earlybird = 'yes';
                    }
                }
                if( $earlybird == 'yes' ) {
                    $pdf->ClassesAdd([76, 24, 24, 32, 24], 
                        $category, 
                        ['Class', 'Earlybird', 'Regular', 'Earlybird Plus', 'Plus'], 
                        ['code_name_synopsis', 'earlybird_fee', 'fee', 'earlybird_plus_fee', 'plus_fee']
                        );
                } else {
                    $pdf->ClassesAdd([130, 25, 25], 
                        $category, 
                        ['Class', 'Regular', 'Plus'], 
                        ['code_name_synopsis', 'fee', 'plus_fee']);
                }
            }
            //
            // Earlybird & Virtual Fees
            //
            elseif( ($festival['flags']&0x04) == 0x04 && $festival['earlybird_date'] != '0000-00-00 00:00:00' ) {
                $pdf->ClassesAdd([105, 25, 25, 25], $category, ['Class', 'Earlybird', 'Live', 'Virtual'], ['code_name_synopsis', 'earlybird_fee', 'fee', 'virtual_fee']);

            } elseif( ($festival['flags']&0x04) == 0x04 && (!isset($args['live-virtual']) || !in_array($args['live-virtual'], ['live','virtual'])) ) {
                $pdf->ClassesAdd([130,25, 25], $category, ['Class', 'Live', 'Virtual'], ['code_name_synopsis', 'fee', 'virtual_fee']);
            } elseif( isset($args['live-virtual']) && in_array($args['live-virtual'], ['live','virtual']) ) {
                $headers = ['Class', 'Live'];
                $fields = ['code_name_synopsis', 'fee'];
                if( $args['live-virtual'] == 'virtual' ) {
                    $headers[1] = 'Virtual';
                    $fields[1] = 'Virtual';
                }
                $pdf->ClassesAdd([150,30], $category, $headers, $fields);

            } else {
                $pdf->ClassesAdd([150,30], $category, [], ['code_name_synopsis', 'fee']);
            }
        }
    }


    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
