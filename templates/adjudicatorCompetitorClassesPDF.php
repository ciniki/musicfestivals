<?php
//
// Description
// ===========
// This function will produce a PDF that can be used by adjudicators for their list of competitors
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_adjudicatorCompetitorClassesPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

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

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load competitor list
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.first, "
        . "competitors.last, "
        . "competitors.name, "
        . "competitors.ctype, "
        . "classes.code AS codes "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND ("
                . "competitors.id = registrations.competitor1_id "
                . "OR competitors.id = registrations.competitor2_id "
                . "OR competitors.id = registrations.competitor3_id "
                . "OR competitors.id = registrations.competitor4_id "
                . "OR competitors.id = registrations.competitor5_id "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY competitors.id, codes "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'first', 'last', 'name', 'ctype', 'codes'),
            'dlists'=>array('codes'=>', '),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.918', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();
    foreach($competitors as $cid =>$competitor) {
        if( $competitor['ctype'] == 10 ) {
            $competitors[$cid]['name'] = $competitor['last'] . ', ' . $competitor['first'];
        }
    }
    
    //
    // Get the adjudicators and their registrations
    //
    $strsql = "SELECT adjudicators.id, "
        . "customers.display_name AS name, "
        . "registrations.id AS reg_id, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
        . "registrations.competitor5_id "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0800) ) {
        $strsql .= "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "adjudicators.id = divisions.adjudicator_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    } else {
        $strsql .= "INNER JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                . "adjudicators.id = ssections.adjudicator1_id "
                . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "ssections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") ";
    }
    $strsql .= "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['adjudicator_id']) && $args['adjudicator_id'] > 0 ) {
        $strsql .= "AND adjudicators.id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' ";
    }
    $strsql .= "ORDER BY customers.display_name, adjudicators.id, registrations.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'name',
            )), 
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.919', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Setup the class foreach competitor
    //
/*    foreach($adjudicators as $aid => $adjudicator) {
        foreach($adjudicator['registrations'] as $reg) {
            for($i = 1; $i <= 5; $i++) {
                if( $reg["competitor{$i}_id"] > 0 
                    && isset($competitors[$reg["competitor{$i}_id"]]) 
                    && !in_array($reg['class
                    ) {
                    
                }
            }
        }
    }
*/
    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 15;
        public $header_visible = 'yes';
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_visible = 'yes';
        public $footer_msg = '';
        public $tenant_details = array();

        public function Header() {
            if( $this->header_visible == 'yes' ) {
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
                    if( $width > 600 ) {
                        $this->header_image->scaleImage(600, 0);
                    }
                    $image_ratio = $width/$height;
                    $img_width = 60;
                    $available_ratio = $img_width/$this->header_height;
                    // Check if the ratio of the image will make it too large for the height,
                    // and scaled based on either height or width.
                    if( $available_ratio < $image_ratio ) {
//                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150');
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 2, '150', '', false, false, 0, true);
                    } else {
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', 2, '150');
                    }
                }

                $this->Ln(5);
                $this->SetFont('helvetica', 'B', 14);
                if( $img_width > 0 ) {
                    $this->Cell($img_width, 10, '', 0);
                }
                $this->setX($this->left_margin + $img_width);
//                $this->Cell(180-$img_width, 10, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->MultiCell(180-$img_width, 0, $this->header_title, 0, 'R', 0, 1);
//                $this->Ln(7);

                $this->SetFont('helvetica', '', 14);
                $this->setX($this->left_margin + $img_width);
                //$this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->MultiCell(180-$img_width, 0, $this->header_sub_title, 0, 'R', 0, 1);
//                $this->MultiCell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
//                $this->Ln(7);

                $this->SetFont('helvetica', '', 12);
                $this->setX($this->left_margin + $img_width);
                $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->Ln(6);
            } else {
                // No header
            }

        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            if( $this->footer_visible == 'yes' ) {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->SetFont('helvetica', '', 10);
                $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            } else {
                // No footer
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
    $pdf->header_msg = '';
    $pdf->footer_msg = '';

    if( !isset($args['footerdate']) || $args['footerdate'] == 'yes' ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $pdf->footer_msg = $dt->format("M j, Y");
    }

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
    // Check if header/footer should be hidden
    //
    if( isset($args['header']) && $args['header'] != 'yes' ) {
        $pdf->header_visible = 'no';
    }
    if( isset($args['footer']) && $args['footer'] != 'yes' ) {
        $pdf->footer_visible = 'no';
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Adjudicator ' . $festival['competitor-label-plural']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(220);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128);
    $pdf->SetLineWidth(0.1);
    $pdf->SetCellPaddings(2, 2, 2, 2);

    $filename = 'Adjudicator ' . $festival['competitor-label-plural'];

    //
    // Go through the sections, divisions and classes
    //
    $w = array(45, 45, 45, 45);
    foreach($adjudicators as $adjudicator) {
        if( count($adjudicators) == 1 ) {
            $filename .= ' - ' . $adjudicator['name'];
        }

        if( !isset($adjudicator['registrations']) ) {
            continue;
        }
        $competitor_list = [];
        foreach($adjudicator['registrations'] as $reg) {
            for($i = 1; $i <= 5; $i++) {
                if( $reg["competitor{$i}_id"] > 0 && isset($competitors[$reg["competitor{$i}_id"]]) ) {
                    $competitor_list[$reg["competitor{$i}_id"]] = $competitors[$reg["competitor{$i}_id"]];
                }
            }
        }
        uasort($competitor_list, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
            });

        //
        // Start a new section
        //
        $pdf->header_sub_title = $adjudicator['name'];
        $pdf->AddPage();
        $pdf->SetFont('', 'B', '10');
        $pdf->MultiCell($w[0], 0, 'Name', 1, 'L', 1, 0);
        $pdf->MultiCell($w[1], 0, 'Classes', 1, 'L', 1, 0);
        $pdf->MultiCell($w[2], 0, 'Marks', 1, 'L', 1, 0);
        $pdf->MultiCell($w[3], 0, 'Notes', 1, 'L', 1, 1);

        foreach($competitor_list as $competitor) {
            $h = $pdf->getStringHeight($w[0], $competitor['name']);
            if( $pdf->getStringHeight($w[1], $competitor['codes']) > $h ) {
                $h = $pdf->getStringHeight($w[1], $competitor['codes']);
            }
            
            if( $pdf->GetY() > ($pdf->getPageHeight() - $h - 22)) {
                $pdf->AddPage();
                $pdf->SetFont('', 'B', '10');
                $pdf->MultiCell($w[0], 0, 'Name', 1, 'L', 1, 0);
                $pdf->MultiCell($w[1], 0, 'Classes', 1, 'L', 1, 0);
                $pdf->MultiCell($w[2], 0, 'Marks', 1, 'L', 1, 0);
                $pdf->MultiCell($w[3], 0, 'Notes', 1, 'L', 1, 1);
            }

            $pdf->SetFont('', '', '10');
            $pdf->MultiCell($w[0], $h, $competitor['name'], 1, 'L', 0, 0);
            $pdf->MultiCell($w[1], $h, $competitor['codes'], 1, 'L', 0, 0);
            $pdf->MultiCell($w[2], $h, '', 1, 'L', 0, 0);
            $pdf->MultiCell($w[3], $h, '', 1, 'L', 0, 1);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
