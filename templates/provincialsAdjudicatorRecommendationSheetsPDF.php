<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_provincialsAdjudicatorRecommendationSheetsPDF(&$ciniki, $tnid, $args) {

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
    // Load the load festival and provincials festival info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsFestivalMemberLoad');
    $rc = ciniki_musicfestivals_provincialsFestivalMemberLoad($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $rc['member'];
    $provincials_tnid = $member['tnid'];

    //
    // Get the list of provincial classes and registrations
    //
    $strsql = "SELECT adjudicators.id, "
        . "customers.display_name AS adjudicator_name, "
        . "provincial_classes.code AS class_code, "
        . "provincial_classes.name AS class_name, "
        . "registrations.id AS registration_id, "
        . "registrations.private_name "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
            . "adjudicators.id = arefs.adjudicator_id "
            . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "arefs.object_id = divisions.id "
            . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS provincial_classes ON ("
            . "classes.provincials_code = provincial_classes.code "
            . "AND provincial_classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "AND provincial_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['adjudicator_id']) && $args['adjudicator_id'] > 0 ) {
        $strsql .= "AND adjudicators.id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' ";
    }
    $strsql .= "ORDER BY customers.display_name, provincial_classes.code, registrations.private_name ";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'name'=>'adjudicator_name'),
            ), 
        array('container'=>'classes', 'fname'=>'class_code', 
            'fields'=>array('code'=>'class_code', 'name'=>'class_name'),
            ), 
        array('container'=>'competitors', 'fname'=>'private_name', 
            'fields'=>array('private_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1385', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

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
        public $page_num = 1;

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
                    $available_ratio = $img_width/($this->header_height-8);
                    // Check if the ratio of the image will make it too large for the height,
                    // and scaled based on either height or width.
                    if( $available_ratio < $image_ratio ) {
//                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150');
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height, '', '', 'L', 2, '150', '', false, false, 0, true);
                    } else {
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-13, '', '', 'L', 2, '150');
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
                $this->Ln(2);

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
//                $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                $this->Cell(90, 10, 'Page ' . $this->getPageNumGroupAlias().'/'.$this->getPageGroupAlias(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
    $pdf->header_msg = 'Provincial Recommendation Worksheet';
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
    $pdf->SetTitle($festival['name'] . ' - Adjudicator');
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

    $filename = 'Adjudicator Recommendation Sheets';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(160, 20);
    foreach($adjudicators as $adjudicator) {
        if( count($adjudicators) == 1 ) {
            $filename .= ' - ' . $adjudicator['name'];
        }

        if( !isset($adjudicator['classes']) ) {
            continue;
        }

        //
        // Start a new section
        //
        $pdf->header_sub_title = $adjudicator['name'];
        $pdf->startPageGroup();
        $pdf->AddPage();
        $pdf->SetFont('', '0', '12');

        //
        // Add the intro
        //
        if( isset($festival['provincials-recommendation-sheets-intro']) 
            && $festival['provincials-recommendation-sheets-intro'] != '' 
            ) {
            $pdf->writeHTMLCell(180, '', '', '', $festival['provincials-recommendation-sheets-intro'], 0, 1, 0, true, 'L', true);
        }

        foreach($adjudicator['classes'] as $class) {

            if( !isset($class['competitors']) ) {
                continue;
            }
            $num_reg = count($class['competitors']);
            
            $pdf->SetFont('', 'B', '11');
            $name = $class['code'] . ' - ' . $class['name'];
            $lh = $pdf->getStringHeight($w[0], $name);

            if( $pdf->GetY() > ($pdf->getPageHeight() - $lh - (($num_reg > 7 ? 7 : $num_reg)*9) - 15) ) {
                $pdf->AddPage();
                $pdf->page_num++;
            }

            $pdf->MultiCell($w[0], $lh, $name, 0, 'L', 0, 0);
            $pdf->MultiCell($w[1], $lh, 'Mark', 0, 'C', 0, 1);


            $pdf->SetFont('', '', '11');
            for($i = 1; $i < 7; $i++) {
                if( $i > $num_reg ) {
                    break;
                }
                $name = '';
                switch($i) {
                    case 1: $name = '1st Recommendation'; break;
                    case 2: $name = '2nd Recommendation'; break;
                    case 3: $name = '3rd Recommendation'; break;
                    case 4: $name = '1st Alternate'; break;
                    case 5: $name = '2nd Alternate'; break;
                    case 6: $name = '3rd Alternate'; break;
                    case 7: $name = '4th Alternate'; break;
                }
                $pdf->MultiCell($w[0], 1, $name, 1, 'L', 0, 0);
                $pdf->MultiCell($w[1], 1, '', 1, 'L', 0, 1);
            }
            $pdf->Ln(3);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
