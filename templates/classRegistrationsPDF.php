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
function ciniki_musicfestivals_templates_classRegistrationsPDF(&$ciniki, $tnid, $args) {

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
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.start_date, "
        . "ciniki_musicfestivals.end_date, "
        . "ciniki_musicfestivals.primary_image_id, "
        . "ciniki_musicfestivals.description, "
        . "ciniki_musicfestivals.document_logo_id, "
        . "ciniki_musicfestivals.document_header_msg, "
        . "ciniki_musicfestivals.document_footer_msg "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.186', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.108', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT classes.id AS class_id, "
        . "classes.sequence AS cla_seq, "
        . "categories.sequence AS cat_seq, "
        . "sections.sequence AS sec_seq, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.perf_time1, "
        . "registrations.title2, "
        . "registrations.perf_time2, "
        . "registrations.title3, "
        . "registrations.perf_time3, "
        . "registrations.notes, "
        . "competitors.id AS competitor_id, "
        . "competitors.notes AS competitor_notes "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
            . "(registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sec_seq, cat_seq, cla_seq, class_code, class_name, reg_id, competitor_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'cat_seq', 'sec_seq', 'code'=>'class_code', 'name'=>'class_name'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 
                'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3', 'notes',
            )),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'notes'=>'competitor_notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $classes = $rc['classes'];
        foreach($classes as $cid => $class) {
            $classes[$cid]['num_reg'] = count($class['registrations']);
        }
    } else {
        $classes = array();
    }

/*    usort($classes, function($a, $b) {
        if( $a['sec_seq'] == $b['sec_seq'] ) {
            if( $a['cat_seq'] == $b['cat_seq'] ) {
                if( $a['code'] == $b['code'] ) {
                    strcasecmp($a['code'], $b['code']);
                }
            }
        }
        if( $a['cat_seq'] == $b['cat_seq'] ) {
        if( $a['num_reg'] == $b['num_reg'] ) {
            strcasecmp($a['code'], $b['code']);
        }
        return ($a['num_reg'] < $b['num_reg'] ? 1 : -1);
    }); */

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
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, 0, $this->header_height-13, 'JPEG', '', 'L', 2, '150');
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
    $pdf->footer_msg = '';

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
    $pdf->SetTitle($festival['name'] . ' - Schedule');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $filename = 'class_registrations';

    $pdf->AddPage();
    //
    // Go through the sections, divisions and classes
    //
    $cw = array(25, 5, 150);
    $w = array(10, 55, 90, 25);
    $nw = array(20, 160);
    $lh = 6;
    $border = 'T';
    foreach($classes as $class) {
        if( $pdf->getY() > ($pdf->getPageHeight() - 60 ) ) {
            $pdf->AddPage();
        }
        $pdf->SetFont('', 'B', 14);
        $pdf->Cell($cw[0] + $cw[1] + $cw[2], $lh, $class['code'] . ' - ' . $class['name'], $border, 1, 'L', 0);
//        $pdf->Cell($cw[1], $lh, '', $border, 0, 'R', 0);
//        $pdf->Cell($cw[2], $lh, $class['name'], $border, 1, 'L', 0);
        $pdf->SetFont('', '', 12);
        foreach($class['registrations'] as $reg) {
            $notes = $reg['notes'];
            if( isset($reg['competitors']) ) {
                foreach($reg['competitors'] as $competitor) {
                    if( $competitor['notes'] != '' ) {
                        $notes .= ($notes != '' ? "\n" : '') . $competitor['notes'];
                    }
                }
            }
            if( $reg['title2'] != '' ) {
                $reg['title1'] .= "<br/>" . $reg['title2'];
                $reg['perf_time1'] .= "<br/>" . $reg['perf_time2'];
            }
            if( $reg['title3'] != '' ) {
                $reg['title1'] .= "<br/>" . $reg['title3'];
                $reg['perf_time1'] .= "<br/>" . $reg['perf_time3'];
            }
            $d_height = $pdf->getStringHeight($w[1], $reg['name']);
            if( $pdf->getStringHeight($w[2], $reg['title1']) > $d_height ) {
                $d_height = $pdf->getStringHeight($w[2], $reg['title1']);
            }
            $n_height = 0;
            if( $notes != '' ) {
                $n_height = $pdf->getStringHeight($nw[1], $notes);
            }

            if( $pdf->getY() > ($pdf->getPageHeight() - 30 - $d_height - $n_height) ) {
                $pdf->AddPage();
                $pdf->SetFont('', 'B', 14);
                $pdf->Cell($cw[0], $lh, $class['code'], $border, 0, 'R', 0);
                $pdf->Cell($cw[1], $lh, '', $border, 0, 'L', 0);
                $pdf->Cell($cw[2], $lh, $class['name'] . ' (continued...)', $border, 1, 'L', 0);
                $pdf->SetFont('', '', 12);
            }
   
            $pdf->writeHTMLCell($w[0], $d_height, '', '', '', '', 0, false, true, 'L', 1);
            $pdf->writeHTMLCell($w[1], $d_height, '', '', $reg['name'], 0, 0, false, true, 'L', 1);
            $pdf->writeHTMLCell($w[2], $d_height, '', '', $reg['title1'], 0, 0, false, true, 'L', 1);
            $pdf->writeHTMLCell($w[3], $d_height, '', '', $reg['perf_time1'], 0, 1, false, true, 'L', 1);

            if( $notes != '' ) {
                $pdf->writeHTMLCell($nw[0], $n_height, '', '', '', 0, 0, false, true, 'L', 1);
                $pdf->writeHTMLCell($nw[1], $n_height, '', '', '**' . preg_replace("/\n/", "<br/>", $notes), 0, 1, false, true, 'L', 1);
            }
        }
        $pdf->Ln();
    }
    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
