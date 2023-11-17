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
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.code, "
        . "classes.name, "
        . "classes.permalink, "
        . "classes.sequence, "
        . "classes.flags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id ";
    if( isset($args['live-virtual']) && $args['live-virtual'] == 'live' ) {
        $strsql .= "AND classes.fee > 0 ";
    } elseif( isset($args['live-virtual']) && $args['live-virtual'] == 'live' ) {
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
    $strsql .= "ORDER BY sections.sequence, sections.name, "
            . "categories.sequence, categories.name, "
            . "classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('name'=>'section_name', 'synopsis'=>'section_synopsis', 'description'=>'section_description')),
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 
                'earlybird_fee', 'fee', 'virtual_fee')),
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
            $this->SetFont('times', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('times', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('times', 'B', 12);
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
    $pdf->SetFont('times', 'BI', 10);
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
        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'] . ' Syllabus';
        $pdf->AddPage();

        if( isset($section['description']) && $section['description'] != '' ) {
            $pdf->SetFont('', 'B', '18');
            $pdf->MultiCell(180, 5, $section['name'], 0, 'L', 0, 1);
            $pdf->SetFont('', '', '12');
            $pdf->MultiCell(180, 5, $section['description'], 0, 'L', 0, 1);
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
//            $pdf->Cell(180, 10, $category['name'], 0, 0, 'L', 0);
            $lh = $pdf->getStringHeight(180, $category['name']);
            //
            // Determine if new page should be started
            //
            if( $pdf->getY() > $pdf->getPageHeight() - 50 - $s_height - $lh) {
                $pdf->AddPage();
                $newpage = 'yes';
            } elseif( $newpage == 'no' ) {
                $pdf->Ln(4);
            }
            $newpage = 'no';

            $pdf->MultiCell(180, 5, $category['name'], 0, 'L', 0, 1);
            $pdf->SetFont('', '', '12');
            if( $description != '' ) {
                $pdf->MultiCell(180, $lh, $description, 0, 'L', 0, 2);
//                $pdf->Ln(2);
            }
            $fill = 1;
//            $pdf->Cell($w[0], $lh, '', 0, 0, 'L', $fill);
//            $pdf->Cell($w[1], $lh, '', 0, 0, 'L', $fill);
//            $pdf->Cell($w[2], $lh, 'Fee', 0, 0, 'C', $fill);
//            $pdf->Ln();
            $pdf->Ln(3);
            
            //
            // Output the classes
            //
            $fill = 1;
            //
            // Earlybird & Virtual Fees
            //
            if( ($festival['flags']&0x04) && $festival['earlybird_date'] != '0000-00-00 00:00:00' ) {
                $w = array(105, 25, 25, 25);
                $pdf->SetFont('', 'B', '12');
                $lh = $pdf->getStringHeight($w[0], 'Class');
                $pdf->Cell($w[0], $lh, 'Class', 1, 0, 'L', $fill);
                $pdf->Cell($w[1], $lh, 'Earlybird', 1, 0, 'C', $fill);
                $pdf->Cell($w[2], $lh, 'Live', 1, 0, 'C', $fill);
                $pdf->Cell($w[3], $lh, 'Virtual', 1, 0, 'C', $fill);
                $pdf->Ln($lh);
                $pdf->SetFont('', '', '12');
                $fill = 0;
                foreach($category['classes'] as $class) {
                    $lh = $pdf->getStringHeight($w[0], $class['code'] . ' - ' . $class['name']);
                    if( $pdf->getY() > ($pdf->getPageHeight() - $lh - 22) ) {
                        $pdf->AddPage();
                        // Category
                        $pdf->SetFont('', 'B', '18');
                        //$pdf->Cell(180, 10, $category['name'] . ' (continued)', 0, 0, 'L', 0);
                        $pdf->MultiCell(180, 10, $category['name'] . ' (continued)', 0, 'L', 0, 1);
                        //$pdf->Ln(12);
                        // Headers
                        $pdf->SetFont('', 'B', '12');
                        $fill = 1;
                        $pdf->Cell($w[0], $lh, 'Class', 1, 0, 'L', $fill);
                        $pdf->Cell($w[1], $lh, 'Earlybird', 1, 0, 'C', $fill);
                        $pdf->Cell($w[2], $lh, 'Live', 1, 0, 'C', $fill);
                        $pdf->Cell($w[3], $lh, 'Virtual', 1, 0, 'C', $fill);
                        $pdf->Ln($lh);
                        $pdf->SetFont('', '', '12');
                        $fill = 0;
                    }
                    $pdf->MultiCell($w[0], $lh, $class['code'] . ' - ' . $class['name'], 1, 'L', $fill, 0);
                    $pdf->Cell($w[1], $lh, numfmt_format_currency($intl_currency_fmt, $class['earlybird_fee'], $intl_currency), 1, 0, 'C', $fill);
                    if( $class['fee'] > 0 ) {
                        $pdf->Cell($w[2], $lh, numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency), 1, 0, 'C', $fill);
                    } else {
                        $pdf->Cell($w[2], $lh, 'n/a', 1, 0, 'C', $fill);
                    }
                    if( $class['virtual_fee'] > 0 ) {
                        $pdf->Cell($w[3], $lh, numfmt_format_currency($intl_currency_fmt, $class['virtual_fee'], $intl_currency), 1, 0, 'C', $fill);
                    } else {
                        $pdf->Cell($w[3], $lh, 'n/a', 1, 0, 'C', $fill);
                    }
                    $pdf->Ln($lh);
                    $fill=!$fill;
                }

            } elseif( ($festival['flags']&0x04) && (!isset($args['live-virtual']) || !in_array($args['live-virtual'], ['live','virtual'])) ) {
                $w = array(130, 25, 25);
                $pdf->SetFont('', 'B', '12');
                $pdf->Cell($w[0], $lh, 'Class', 1, 0, 'L', $fill);
                $pdf->Cell($w[1], $lh, 'Live', 1, 0, 'C', $fill);
                $pdf->Cell($w[2], $lh, 'Virtual', 1, 0, 'C', $fill);
                $pdf->Ln($lh);
                $pdf->SetFont('', '', '12');
                $fill = 0;
                foreach($category['classes'] as $class) {
                    $lh = $pdf->getStringHeight($w[0], $class['code'] . ' - ' . $class['name']);
                    if( $pdf->getY() > ($pdf->getPageHeight() - $lh - 25) ) {
                        $pdf->AddPage();
                        // Category
                        $pdf->SetFont('', 'B', '18');
                        $pdf->MultiCell(180, 10, $category['name'] . ' (continued)', 0, 'L', 0, 1);
                        //$pdf->Cell(180, 10, $category['name'] . ' (continued)', 0, 0, 'L', 0);
                        //$pdf->Ln(12);
                        // Headers
                        $pdf->SetFont('', 'B', '12');
                        $fill = 1;
                        $pdf->Cell($w[0], $lh, 'Class', 1, 0, 'L', $fill);
                        $pdf->Cell($w[1], $lh, 'Live', 1, 0, 'C', $fill);
                        $pdf->Cell($w[2], $lh, 'Virtual', 1, 0, 'C', $fill);
                        $pdf->Ln($lh);
                        $pdf->SetFont('', '', '12');
                        $fill = 0;
                    }
                    $pdf->MultiCell($w[0], $lh, $class['code'] . ' - ' . $class['name'], 1, 'L', $fill, 0);
                    if( $class['fee'] > 0 ) {
                        $pdf->Cell($w[1], $lh, numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency), 1, 0, 'C', $fill);
                    } else {
                        $pdf->Cell($w[1], $lh, 'n/a', 1, 0, 'C', $fill);
                    }
                    if( $class['virtual_fee'] > 0 ) {
                        $pdf->Cell($w[2], $lh, numfmt_format_currency($intl_currency_fmt, $class['virtual_fee'], $intl_currency), 1, 0, 'C', $fill);
                    } else {
                        $pdf->Cell($w[2], $lh, 'n/a', 1, 0, 'C', $fill);
                    }
                    //$pdf->Cell($w[1], $lh, numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency), 1, 0, 'C', $fill);
                    //$pdf->Cell($w[2], $lh, numfmt_format_currency($intl_currency_fmt, $class['virtual_fee'], $intl_currency), 1, 0, 'C', $fill);
                    $pdf->Ln($lh);
                    $fill=!$fill;
                }
            } elseif( isset($args['live-virtual']) && in_array($args['live-virtual'], ['live','virtual']) ) {
                $w = array(150, 30);
                $lh = $pdf->getStringHeight($w[0], 'Class');
                $pdf->SetFont('', 'B', '12');
                $pdf->Cell($w[0], $lh, 'Class', 1, 0, 'L', $fill);
                if( $args['live-virtual'] == 'live' ) {
                    $pdf->Cell($w[1], $lh, 'Live', 1, 0, 'C', $fill);
                } elseif( $args['live-virtual'] == 'virtual' ) {
                    $pdf->Cell($w[1], $lh, 'Virtual', 1, 0, 'C', $fill);
                }
                $pdf->Ln($lh);
                $pdf->SetFont('', '', '12');
                $fill = 0;
                foreach($category['classes'] as $class) {
                    $lh = $pdf->getStringHeight($w[0], $class['code'] . ' - ' . $class['name']);
                    if( $pdf->getY() > $pdf->getPageHeight() - $lh - 20) {
                        $pdf->AddPage();
                        $pdf->SetFont('', 'B', '18');
                        $pdf->MultiCell(180, 10, $category['name'] . ' (continued)', 0, 'L', 0, 1);
                        $pdf->SetFont('', 'B', '12');
                        $fill = 1;
                        $pdf->Cell($w[0], $lh, 'Class', 1, 0, 'L', $fill);
                        if( $args['live-virtual'] == 'live' ) {
                            $pdf->Cell($w[1], $lh, 'Live', 1, 0, 'C', $fill);
                        } elseif( $args['live-virtual'] == 'virtual' ) {
                            $pdf->Cell($w[1], $lh, 'Virtual', 1, 0, 'C', $fill);
                        }
                        $pdf->Ln($lh);
                        $pdf->SetFont('', '', '12');
                        $fill = 0;
                    }
                    $pdf->MultiCell($w[0], $lh, $class['code'] . ' - ' . $class['name'], 1, 'L', $fill, 0);
                    if( $args['live-virtual'] == 'live' ) {
                        $pdf->MultiCell($w[1], $lh, '$' . number_format($class['fee'], 2), 1, 'C', $fill, 0);
                    } elseif( $args['live-virtual'] == 'virtual' ) {
                        $pdf->MultiCell($w[1], $lh, '$' . number_format($class['virtual_fee'], 2), 1, 'C', $fill, 0);
                    }
                    $pdf->Ln($lh);
                    $fill=!$fill;
                }
            } else {
                $w = array(30, 120, 30);
                foreach($category['classes'] as $class) {
                    $lh = $pdf->getStringHeight($w[1], $class['name']);
                    if( $pdf->getY() > $pdf->getPageHeight() - $lh - 20) {
                        $pdf->AddPage();
                        $pdf->SetFont('', 'B', '18');
                        $pdf->MultiCell(180, 10, $category['name'] . ' (continued)', 0, 'L', 0, 1);
                        //$pdf->Cell(180, 10, $category['name'] . ' (continued)', 0, 0, 'L', 0);
                        //$pdf->Ln(12);
                        $pdf->SetFont('', '', '12');
                    }
//                    $pdf->Cell($w[0], $lh, $class['code'], 'TLB', 0, 'L', $fill);
//                    $pdf->Cell($w[1], $lh, $class['name'], 'TB', 0, 'L', $fill);
//                    $pdf->Cell($w[2], $lh, numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency), 'TRB', 0, 'C', $fill);
                    $pdf->MultiCell($w[0], $lh, $class['code'], 'TLB', 'L', $fill, 0);
                    $pdf->MultiCell($w[1], $lh, $class['name'], 'TB', 'L', $fill, 0);
                    $pdf->MultiCell($w[2], $lh, number_format($class['fee'], 2), 'TRB', 'C', $fill, 0);
                    $pdf->Ln($lh);
                    $fill=!$fill;
                }
            }
        }
    }


    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
