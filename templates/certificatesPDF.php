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
function ciniki_musicfestivals_templates_certificatesPDF(&$ciniki, $business_id, $args) {

    //
    // Load the business details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
    $rc = ciniki_businesses_businessDetails($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $business_details = $rc['details'];
    } else {
        $business_details = array();
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
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
        . "WHERE ciniki_musicfestivals.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
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
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "IFNULL(class1.name, '') AS class1_name, "
        . "IFNULL(class2.name, '') AS class2_name, "
        . "IFNULL(class3.name, '') AS class3_name, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title, "
        . "IFNULL(classes.name, '') AS class_name, "
        . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id " 
            . "AND divisions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
            . "timeslots.class3_id = class2.id " 
            . "AND class2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "(timeslots.class1_id = registrations.class_id "  
                . "OR timeslots.class2_id = registrations.class_id "
                . "OR timeslots.class3_id = registrations.class_id "
                . ") "
            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
            . "AND registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE sections.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    $strsql .= "ORDER BY divisions.division_date, division_id, slot_time "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
        array('container'=>'divisions', 'fname'=>'division_id', 'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address')),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name')),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title', 'class_name', 'competitor2_id')),
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
    // Setup the PDF
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/tcpdf/tcpdf.php');
    class MYPDF extends TCPDF {
        public function Header() { }
        public function Footer() { }
    }
    $pdf = new TCPDF('L', PDF_UNIT, 'LETTER', true, 'ISO-8859-1', false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($business_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Comments');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    $filename = 'certificates';

    //
    // Go through the sections, divisions and classes
    //
    $border = '';
    foreach($sections as $section) {
        //
        // Start a new section
        //
        if( isset($args['schedulesection_id']) ) {
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $section['name']) . '_certificates';
        }

        //
        // Output the divisions
        //
        $newpage = 'yes';
        foreach($section['divisions'] as $division) {
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) ) {
                continue;
            }
            
            //
            // Output the timeslots
            //
            foreach($division['timeslots'] as $timeslot) {
                if( !isset($timeslot['registrations']) ) {
                    continue;
                }

                foreach($timeslot['registrations'] as $reg) {
                    $pdf->AddPage();

                    $pdf->SetCellPaddings(1, 0, 1, 0);
                    $pdf->Image($ciniki['config']['core']['modules_dir'] . '/musicfestivals/templates/certificate.png', 0, 0, 279, 216, '', '', '', false, 300, '', false, false, 0);

                    $pdf->setFont('', '', 18);

                    $pdf->setXY(100, 110);
                    $lh = $pdf->getNumLines($reg['name'], 155) * 12;
                    $pdf->MultiCell(155, $lh, $reg['name'], $border, 'L', 0, 0, '', '');

                    $pdf->setXY(100, 145);
                    $lh = $pdf->getNumLines($reg['class_name'], 155) * 12;
                    $pdf->MultiCell(155, $lh, $reg['class_name'], $border, 'L', 0, 0, '', '');
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
