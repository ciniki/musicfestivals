<?php
//
// Description
// ===========
// This method will return the PDF of the schedule with timings
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_scheduleTimingsPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    //
    // Make sure festival_id was passed in
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.727', 'msg'=>'No festival specified'));
    }

    //
    // Make sure schedule ssection_id was passed in
    //
    if( (!isset($args['ssection_id']) || $args['ssection_id'] <= 0) ) {
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
    // Check if the registrations are passed in args
    //
    if( !isset($args['registrations']) ) {
        //
        // Load the customers registrations
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.uuid, "
            . "registrations.teacher_customer_id, "
            . "registrations.billing_customer_id, "
            . "registrations.rtype, "
            . "registrations.status, "
            . "registrations.status AS status_text, ";
        if( isset($festival['waiver-name-status']) && $festival['waiver-name-status'] != 'off' ) {
            $strsql .= "registrations.private_name AS display_name, ";
        } else {
            $strsql .= "registrations.display_name, ";
        }
        $strsql .= "registrations.public_name, "
            . "registrations.competitor1_id, "
//            . "competitors.name, "
//            . "competitors.parent, "
//            . "customers.display_name AS parent_name, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id, "
            . "registrations.class_id, "
            . "registrations.timeslot_id, "
            . "registrations.title1, "
            . "registrations.composer1, "
            . "registrations.movements1, "
            . "registrations.perf_time1, "
            . "registrations.title2, "
            . "registrations.composer2, "
            . "registrations.movements2, "
            . "registrations.perf_time2, "
            . "registrations.title3, "
            . "registrations.composer3, "
            . "registrations.movements3, "
            . "registrations.perf_time3, "
            . "registrations.title4, "
            . "registrations.composer4, "
            . "registrations.movements4, "
            . "registrations.perf_time4, "
            . "registrations.title5, "
            . "registrations.composer5, "
            . "registrations.movements5, "
            . "registrations.perf_time5, "
            . "registrations.title6, "
            . "registrations.composer6, "
            . "registrations.movements6, "
            . "registrations.perf_time6, "
            . "registrations.title7, "
            . "registrations.composer7, "
            . "registrations.movements7, "
            . "registrations.perf_time7, "
            . "registrations.title8, "
            . "registrations.composer8, "
            . "registrations.movements8, "
            . "registrations.perf_time8, "
            . "registrations.participation, "
            . "registrations.fee, "
            . "registrations.notes, "
            . "sections.name AS section_name, "
            . "categories.name AS category_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "classes.flags AS class_flags, ";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
            $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, ";
        } else {
            $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
        }
        $strsql .= "DATE_FORMAT(divisions.division_date, '%b %e') AS division_date_text, "
            . "locations.name AS location_name, "
            . "locations.address1 AS location_address, "
            . "locations.city AS location_city, "
            . "ssections.flags AS section_flags "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "registrations.timeslot_id = timeslots.id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "timeslots.sdivision_id = divisions.id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                . "divisions.location_id = locations.id "
                . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                . "divisions.ssection_id = ssections.id "
                . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
//            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
//                . "registrations.competitor1_id = competitors.id "
//                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//                . ") "
//            . "LEFT JOIN ciniki_customers AS customers ON ("
//                . "registrations.billing_customer_id = customers.id "
//                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
        if( isset($args['paidonly']) && $args['paidonly'] == 'yes' ) {
            $strsql .= "AND registrations.status = 50 ";
        }
        $strsql .= "AND ("
                . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "OR ("
                    . "registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
                if( isset($args['shared']) && $args['shared'] == 'yes' ) {
                    $strsql .= "AND (registrations.flags&0x01) = 0x01 ";
                }
            $strsql .= ") "
                . "OR ("
                    . "registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
                if( isset($args['shared']) && $args['shared'] == 'yes' ) {
                    $strsql .= "AND (registrations.flags&0x02) = 0x02 ";
                }
            $strsql .= ") "
                . ") ";
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
            $strsql .= "ORDER BY divisions.division_date, registrations.timeslot_time, registrations.display_name, registrations.status, registrations.display_name ";
        } else {
            $strsql .= "ORDER BY divisions.division_date, timeslots.slot_time, registrations.display_name, registrations.status, registrations.display_name ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'status_text',
                    'display_name', 'public_name', 'competitor1_id',
                    'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id', 'class_id', 'timeslot_id', 
                    'title1', 'composer1', 'movements1', 'perf_time1', 
                    'title2', 'composer2', 'movements2', 'perf_time2', 
                    'title3', 'composer3', 'movements3', 'perf_time3', 
                    'title4', 'composer4', 'movements4', 'perf_time4', 
                    'title5', 'composer5', 'movements5', 'perf_time5', 
                    'title6', 'composer6', 'movements6', 'perf_time6', 
                    'title7', 'composer7', 'movements7', 'perf_time7', 
                    'title8', 'composer8', 'movements8', 'perf_time8', 
                    'participation', 'fee', 'notes',
                    'section_name', 'category_name',
                    'class_code', 'class_name', 'class_flags',
                    'slot_time_text', 'section_flags', 'division_date_text', 'location_name', 'location_address', 'location_city',
                    ),
                'maps'=>array('status_text'=>$maps['registration']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.126', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
    } else {
        $registrations = $args['registrations'];
    }

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
        public $fill = 0;

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

        public function labelValue($w1, $label, $w2, $value) {
            $lh = 12;
            $border = 'TLRB';
            $lh = $this->getStringHeight($w2, $value);
            $this->SetFont('helvetica', 'B', 12);
            //$this->MultiCell($w1, $lh, $label, $border, 'R', $this->fill, 0);
            $this->MultiCell($w1, $lh, $label, $border, 'R', 1, 0);
            $this->SetFont('helvetica', '', 12);
//            $this->MultiCell($w2, $lh, $value, $border, 'L', $this->fill, 1);
            $this->MultiCell($w2, $lh, $value, $border, 'L', 0, 1);
            $this->fill = !$this->fill;
        }
        public function labelValue2($w1, $l1, $w2, $v1, $w3, $l2, $w4, $v2) {
            $lh = 12;
            $border = 'TLRB';
            $lh = $this->getStringHeight($w2, $v1);
            $lh2 = $this->getStringHeight($w4, $v2);
            if( $lh2 > $lh ) {
                $lh = $lh2;
            }
            $this->SetFont('helvetica', 'B', 12);
            $this->MultiCell($w1, $lh, $l1, $border, 'R', 1, 0);
            $this->SetFont('helvetica', '', 12);
            $this->MultiCell($w2, $lh, $v1, $border, 'L', 0, 0);
            $this->SetFont('helvetica', 'B', 12);
            $this->MultiCell($w3, $lh, $l2, $border, 'R', 1, 0);
            $this->SetFont('helvetica', '', 12);
            $this->MultiCell($w4, $lh, $v2, $border, 'L', 0, 1);
            $this->fill = !$this->fill;
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
//    $pdf->footer_msg = $festival['document_footer_msg'];

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
    $pdf->SetTitle($festival['name'] . ' - Registrations');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.15);

    $filename = 'registrations';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(42, 40, 98);
    $border = '';

    //
    // List the registrations
    //
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(180, 8, 'Schedule of Registrations', 'B', 0, 'L', 0);
    $pdf->Ln();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(224);
    $border = 1;
    $pdf->Cell($w[0], 0, 'Date/Time', $border, 0, 'L', 1);
    $pdf->Cell($w[1], 0, $festival['competitor-label-singular'], $border, 0, 'L', 1);
    $pdf->Cell($w[2], 0, 'Class', $border, 1, 'L', 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetFillColor(242);
    $fill = 1;
    $border = 1;
    $total = 0;
    foreach($registrations as $registration) {
        $pdf->SetFont('arialunicodems', '', 12);
        if( ($festival['flags']&0x0100) == 0x0100 ) {
            $description = $registration['class_code'] . ' - ' . $registration['section_name'] . ' - ' . $registration['category_name'] . ' - ' . $registration['class_name'];
        } else {
            $description = $registration['class_code'] . ' - ' . $registration['class_name'];
        }
        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $registration, ['basicnumbers'=>'yes']);
        if( isset($rc['titles']) && $rc['titles'] != '' ) {
            $description .= "\n" . $rc['titles'];
        }

        $lh = $pdf->getStringHeight($w[2], $description);

        $registration['schedule'] = '';
        if( $registration['participation'] == 1 ) {
            $registration['schedule'] = 'Virtual';
        } 
        elseif( ($registration['section_flags']&0x01) == 0x01 ) {
            if( $registration['slot_time_text'] != '' ) {
                $registration['schedule'] = $registration['division_date_text'] . ' @ ' . $registration['slot_time_text'];
            }
            if( $registration['location_name'] != '' ) {
                $registration['schedule'] .= "\n" . $registration['location_name'];
            }
            if( $registration['location_address'] != '' && $registration['location_city'] != '' ) {
                $registration['schedule'] .= "\n" . $registration['location_address'] . ', ' . $registration['location_city'];
            }
            elseif( $registration['location_address'] != '' ) {
                $registration['schedule'] .= "\n" . $registration['location_address'];
            }
            elseif( $registration['location_city'] != '' ) {
                $registration['schedule'] .= "\n" . $registration['location_city'];
            }
            if( $pdf->getStringHeight($w[0], $registration['schedule']) > $lh ) {
                $lh = $pdf->getStringHeight($w[0], $registration['schedule']);
            }
        }

        if( $pdf->getY() > $pdf->getPageHeight() - 30 - $lh ) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(180, 8, 'Schedule of Registrations (continued...)', 'B', 0, 'L', 0);
            $pdf->Ln();
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetFillColor(224);
            $border = 1;
            $pdf->Cell($w[0], 0, 'Date/Time', $border, 0, 'L', 1);
            $pdf->Cell($w[1], 0, $festival['competitor-label-singular'], $border, 0, 'L', 1);
            $pdf->Cell($w[2], 0, 'Class', $border, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->SetFillColor(242);
        }
        $pdf->SetFont('arialunicodems', '', 12);
        $pdf->MultiCell($w[0], $lh, $registration['schedule'], $border, 'L', $fill, 0);
        $pdf->MultiCell($w[1], $lh, $registration['display_name'], $border, 'L', $fill, 0);
        $pdf->MultiCell($w[2], $lh, $description, $border, 'L', $fill, 1);

        $fill = !$fill;
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
