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
function ciniki_musicfestivals_templates_scheduleDivisionsWithdrawalsPDF(&$ciniki, $tnid, $args) {

    //
    // Make sure festival_id was passed in
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.727', 'msg'=>'No festival specified'));
    }

    //
    // Make sure schedule schedulesection_id was passed in
    //
    if( (!isset($args['schedulesection_id']) || $args['schedulesection_id'] <= 0) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.913', 'msg'=>'No section specified'));
    }

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "locations.id AS location_id, "
        . "locations.shortname AS location_shortname, "
        . "CONCAT_WS(' ', divisions.division_date, timeslots.slot_time) AS division_sort_key, "
        . "DATE_FORMAT(divisions.division_date, '%b %j') AS division_date_text, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, ";
    } else {
        $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
    }
    $strsql .= "timeslots.name AS timeslot_name, "
        . "timeslots.groupname AS timeslot_groupname, "
        . "registrations.id AS reg_id, "
        . "registrations.status, "
        . "registrations.private_name, "
        . "registrations.display_name, "
        . "registrations.participation, "
        . "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS timeslot_time "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "( "
                . "timeslots.id = registrations.timeslot_id "
                . "OR timeslots.id = registrations.finals_timeslot_id "
                . ") "
            . "AND registrations.status = 75 "   // Withdrawn
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['division_id']) && $args['division_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' ";
    }
    $strsql .= "GROUP BY timeslots.id, registrations.id ";
    $strsql .= "ORDER BY divisions.division_date, divisions.name, divisions.id, registrations.private_name ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'status', 'name'=>'display_name',
                'participation', 'timeslot_time', 
                'location_shortname', 'division_name', 'timeslot_name', 'timeslot_groupname', 'slot_time_text',
                )),
        ));
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

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
        public $header_sponsor_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_visible = 'yes';
        public $footer_image = null;
        public $footer_image_height = 0;
        public $footer_msg = '';
        public $tenant_details = array();
        public $continued_str = ' (continued...)';
        //public $cell_widths = [60,60,60];
        public $cell_widths = [70,89,24,60];

        public function Header() {
            if( $this->header_visible == 'yes' ) {
                //
                // Check if there is an image to be output in the header.   The image
                // will be displayed in a narrow box if the contact information is to
                // be displayed as well.  Otherwise, image is scaled to be 100% page width
                // but only to a maximum height of the header_height (set far below).
                //
                $img_width = 0;
                if( $this->header_image != null && $this->header_sponsor_image != null ) {
                    $width = $this->header_image->getImageWidth();
                    if( $width > 600 ) {
                        $this->header_image->scaleImage(600, 0);
                    }
                    $img_width = ($this->header_height-8)*2;
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 0, '150', '', false, false, 0, true);
                    $offset = $img_width - (($this->header_sponsor_image->getImageWidth()*($this->header_height-8))/$this->header_sponsor_image->getImageHeight());
                    $this->Image('@'.$this->header_sponsor_image->getImageBlob(), $this->left_margin + $img_width + (180-($img_width*2)) + $offset, 10, $img_width, $this->header_height-8, '', '', 'R', 0, '150', '', false, false, 0, true);
                    $this->SetY(10);
                    $this->SetX($this->left_margin + $img_width);
                    $this->SetFont('helvetica', 'B', 20);
                    $this->MultiCell(180-($img_width*2), 0, $this->header_title, 0, 'C', 0, 1);

                    $this->SetFont('helvetica', 'B', 14);
                    $this->setX($this->left_margin + $img_width);
                    $this->MultiCell(180-($img_width*2), 0, $this->header_sub_title, 0, 'C', 0, 1);

                    $this->SetFont('helvetica', 'B', 12);
                    $this->setX($this->left_margin + $img_width);
                    $this->MultiCell(180-($img_width*2), 0, $this->header_msg, 0, 'C', 0, 1);
            
                } else {
                    if( $this->header_image != null ) {
                        $height = $this->header_image->getImageHeight();
                        $width = $this->header_image->getImageWidth();
                        if( $width > 600 ) {
                            $this->header_image->scaleImage(600, 0);
                        }
                        $image_ratio = $width/$height;
                        $img_width = 50;
                        $available_ratio = $img_width/($this->header_height-8);
                        // Check if the ratio of the image will make it too large for the height,
                        // and scaled based on either height or width.
                        if( $available_ratio < $image_ratio ) {
                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150', '', false, false, 0);
   //                         $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 2, '150', '', false, false, 0, true);
                        } else {
                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', 2, '150');
                        }
                    }

                    $this->Ln(8);
                    $this->SetFont('helvetica', 'B', 20);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, 10, '', 0);
                    }
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(243-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(7);

                    $this->SetFont('helvetica', 'B', 14);
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(243-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(6);

                    $this->SetFont('helvetica', 'B', 12);
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(243-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(6);
                }
            } else {
                // No header
            }

        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            if( $this->footer_visible == 'yes' ) {
                if( $this->footer_image != null ) {
                    $this->SetY(-(15+$this->footer_image_height));
                    $this->Image('@'.$this->footer_image->getImageBlob(), $this->left_margin, '', 180, '', '', '', 'L', 2, '150', '', false, false, 0, true);
                    $this->Ln($this->footer_image_height);
                } else {
                    $this->SetY(-15);
                }
                $this->SetFont('helvetica', '', 10);
                $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->SetFont('helvetica', '', 10);
                $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            } else {
                // No footer
            }
        }

        public function TableHeader() {
            $this->SetFont('helvetica', 'B', 11);
            $this->MultiCell($this->cell_widths[0], 0, 'Location', 1, 'L', 1, 0);
            $this->MultiCell($this->cell_widths[1], 0, 'Division', 1, 'L', 1, 0);
            $this->MultiCell($this->cell_widths[2], 0, 'Time', 1, 'L', 1, 0);
            $this->MultiCell($this->cell_widths[3], 0, 'Competitors', 1, 'L', 1, 1);
        }

        public function Registration($reg) {
            $this->SetFont('helvetica', '', 11);
            $lh = $this->getStringHeight($this->cell_widths[0], $reg['location_shortname']);
            if( $this->getStringHeight($this->cell_widths[1], $reg['division_name']) > $lh ) {
                $lh = $this->getStringHeight($this->cell_widths[1], $reg['division_name']);
            }
            if( $this->getStringHeight($this->cell_widths[2], $reg['slot_time_text']) > $lh ) {
                $lh = $this->getStringHeight($this->cell_widths[2], $reg['slot_time_text']);
            }
            if( $this->getStringHeight($this->cell_widths[3], $reg['name']) > $lh ) {
                $lh = $this->getStringHeight($this->cell_widths[3], $reg['name']);
            }
            if( $this->getY() > $this->getPageHeight() - $lh - 20 ) {
                $this->AddPage();
                $this->TableHeader();
            }
            $this->SetFont('helvetica', '', 11);
            $this->MultiCell($this->cell_widths[0], $lh, $reg['location_shortname'], 1, 'L', 0, 0);
            $this->MultiCell($this->cell_widths[1], $lh, $reg['division_name'], 1, 'L', 0, 0);
            $this->MultiCell($this->cell_widths[2], $lh, $reg['slot_time_text'], 1, 'L', 0, 0);
            $this->MultiCell($this->cell_widths[3], $lh, $reg['name'], 1, 'L', 0, 1);
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = 'Withdrawals';
    $pdf->header_sub_title = '';
    $pdf->header_msg = '';
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
    $pdf->SetTitle($festival['name'] . ' - Withdrawals');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'BI', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $filename = 'Divisions Withdrawals';

    //
    // Go through the sections, divisions and classes
    //
    $prev_adjudicator_id = 0;
    foreach($sections as $section) {
        if( !isset($section['registrations']) ) {
            continue;
        }

        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'];
        if( isset($args['schedulesection_id']) ) {  
            $filename = $section['name'] . ' - Withdrawals';
        }
        $pdf->AddPage();
        $pdf->TableHeader();

        foreach($section['registrations'] as $reg) {
            $pdf->Registration($reg);
        }
    }

    //
    // output the pdf
    //
    if( isset($args['download']) && $args['download'] == 'yes' ) {
        $pdf->Output($filename . 'pdf', 'I');
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
