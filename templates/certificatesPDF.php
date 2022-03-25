<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// NOTE: the background required should be opened in Preview on Mac, and Exported to PNG 300 dpi (NO ALPHA)!!!
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_certificatesPDF(&$ciniki, $tnid, $args) {

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.105', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.106', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the festival settings
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.140', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    foreach($rc['settings'] as $k => $v) {
        $festival[$k] = $v;
    }

    //
    // Load adjudicators 
    //
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.festival_id, "
        . "adjudicators.customer_id, "
        . "customers.display_name "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.173', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    if( isset($args['registration_id']) && $args['registration_id'] > 0 ) {
        $strsql = "SELECT 1 AS section_id, "
            . "'' AS section_name, "
            . "1 AS division_id, "
            . "'' division_name, "
            . "'' AS division_date_text, "
            . "1 AS timeslot_id, "
            . "'' AS timeslot_name, "
            . "'' AS slot_time_text, "
            . "classes.id AS class1_id, "
            . "0 AS class2_id, "
            . "0 AS class3_id, "
            . "'' AS class1_name, "
            . "'' AS class2_name, "
            . "'' AS class3_name, "
            . "'' AS description, "
            . "registrations.id AS reg_id, "
            . "registrations.display_name, "
            . "registrations.public_name, "
            . "registrations.title, "
            . "classes.name AS class_name, "
            . "sections.adjudicator1_id, "
            . "sections.adjudicator2_id, "
            . "sections.adjudicator3_id, "
            . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
            . "IFNULL(registrations.competitor3_id, 0) AS competitor3_id, "
            . "IFNULL(registrations.competitor4_id, 0) AS competitor4_id, "
            . "IFNULL(registrations.competitor5_id, 0) AS competitor5_id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "("
                    . "registrations.class_id = timeslots.class1_id "
                    . "OR registrations.class_id = timeslots.class2_id "
                    . "OR registrations.class_id = timeslots.class3_id "
                    . ") "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "timeslots.sdivision_id = divisions.id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                . "divisions.ssection_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "";
    } else {
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "sections.adjudicator1_id, "
            . "sections.adjudicator2_id, "
            . "sections.adjudicator3_id, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
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
            . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
            . "IFNULL(registrations.competitor3_id, 0) AS competitor3_id, "
            . "IFNULL(registrations.competitor4_id, 0) AS competitor4_id, "
            . "IFNULL(registrations.competitor5_id, 0) AS competitor5_id "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id " 
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id " 
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
                . "timeslots.class1_id = class1.id " 
                . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
                . "timeslots.class3_id = class2.id " 
                . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
                . "timeslots.class3_id = class3.id " 
                . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "(timeslots.class1_id = registrations.class_id "  
                    . "OR timeslots.class2_id = registrations.class_id "
                    . "OR timeslots.class3_id = registrations.class_id "
                    . ") "
                . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
        }
        $strsql .= "ORDER BY divisions.division_date, division_id, slot_time "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator1_id')),
        array('container'=>'divisions', 'fname'=>'division_id', 'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text')),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name')),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title', 'class_name', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id')),
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

//    $font_script = TCPDF_FONTS::addTTFfont('/home/andrew/Downloads/scriptina.ttf', 'TrueTypeUnicode', '', 96);
//    error_log(print_r($font_script, true));

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Certificates');
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
                    $num_copies = 1;
                    if( $reg['competitor2_id'] > 0 ) {
                        $num_copies++;
                    }
                    if( $reg['competitor3_id'] > 0 ) {
                        $num_copies++;
                    }
                    if( $reg['competitor4_id'] > 0 ) {
                        $num_copies++;
                    }
                    if( $reg['competitor5_id'] > 0 ) {
                        $num_copies++;
                    }
//                    $pdf->SetDrawColor(0,0,0);
//                    $pdf->setFillColor(230,230,230);
                    for($i=0;$i<$num_copies;$i++) {
                        $pdf->AddPage();
                        $pdf->SetCellPaddings(1, 0, 1, 0);
                        $pdf->Image($ciniki['config']['core']['modules_dir'] . '/musicfestivals/templates/certificate.png', 0, 0, 279, 216, '', '', '', false, 300, '', false, false, 0);
                        $pdf->setPageMark();
                        $pdf->setFont('vidaloka', 'B', 28);
                        $pdf->setXY(30, 115);
                        $lh = $pdf->getNumLines($reg['name'], 155) * 12;
                        //$pdf->MultiCell(155, $lh, $reg['name'], $border, 'L', 0, 0, '', '');
                        $pdf->MultiCell(219, $lh, $reg['name'], 0, 'C', 0, 0, '', '');
                        $pdf->setXY(30, 150);
                        $pdf->setFont('opensans', '', 18);
                        $lh = $pdf->getNumLines($reg['class_name'], 219) * 12;
                        //$pdf->MultiCell(155, $lh, $reg['class_name'], $border, 'L', 0, 0, '', '');
                        $pdf->MultiCell(219, $lh, $reg['class_name'], 0, 'C', 0, 0, '', '');
                        if( isset($festival['president-name']) && $festival['president-name'] != '' ) {
                            $pdf->SetDrawColor(232);
                            $pdf->SetLineWidth(0.1);
                            $pdf->setXY(87, 179);
                            $pdf->setFont('scriptina', '', '28');
                            $pdf->MultiCell(90, $lh, $festival['president-name'], 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', true);
                            if( isset($adjudicators[$section['adjudicator1_id']]['name']) ) {
                                $pdf->setXY(190, 179);
                                $pdf->MultiCell(80, $lh, $adjudicators[$section['adjudicator1_id']]['name'], 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', true);
                            }
                            $pdf->setFont('helvetica');
                        }
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
