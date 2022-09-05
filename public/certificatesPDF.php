<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
// 
function ciniki_musicfestivals_certificatesPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'schedulesection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'names'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Format'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.certificatesPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

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
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.321', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.322', 'msg'=>'Unable to find Festival'));
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.323', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.324', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
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
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
            . "timeslots.class3_id = class2.id " 
            . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "(timeslots.class1_id = registrations.class_id "  
                . "OR timeslots.class2_id = registrations.class_id "
                . "OR timeslots.class3_id = registrations.class_id "
                . ") "
            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    $strsql .= "ORDER BY divisions.division_date, division_id, slot_time "
        . "";
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
    // Load the available certificates
    //
    $strsql = "SELECT certificates.id, "
        . "certificates.festival_id, "
        . "certificates.name, "
        . "certificates.image_id, "
        . "certificates.section_id, "
        . "certificates.min_score, "
        . "fields.id AS field_id, "
        . "fields.name AS field_name, "
        . "fields.field, "
        . "fields.xpos, "
        . "fields.ypos, "
        . "fields.width, "
        . "fields.height, "
        . "fields.font, "
        . "fields.size, "
        . "fields.style, "
        . "fields.align, "
        . "fields.valign, "
        . "fields.color, "
        . "fields.bgcolor, "
        . "fields.text "
        . "FROM ciniki_musicfestival_certificates AS certificates "
        . "LEFT JOIN ciniki_musicfestival_certificate_fields AS fields ON ("
            . "certificates.id = fields.certificate_id "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE certificates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND certificates.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY certificates.section_id, certificates.min_score DESC, certificates.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'certificates', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'image_id', 'section_id', 'min_score')),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array(
                'id'=>'field_id', 'name'=>'field_name', 'field',
                'xpos', 'ypos', 'width', 'height', 'font', 'size', 'style', 'align', 'valign', 'color', 
                'bgcolor', 'text'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.320', 'msg'=>'Unable to load certificates', 'err'=>$rc['err']));
    }
    $avail_certs = isset($rc['certificates']) ? $rc['certificates'] : array();

    $default_cert = null;
    foreach($avail_certs as $cert) {
        $default_cert = $cert;
    }

    $filename = 'certificates';

    //
    // Go through the sections, divisions and classes
    //
    $border = '';
    $certificates = array();
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
                    //
                    // FIXME: Check appropriate certificate, currently only using the default
                    //
                    $certificate = $default_cert;

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
//                    $pdf->setFillColor(255,255,255);
                    for($i=0;$i<$num_copies;$i++) {
                        foreach($certificate['fields'] as $fid => $field) {
                            if( $field['field'] == 'participant' ) {
                                $certificate['fields'][$fid]['text'] = $reg['name'];
                            }
                            elseif( $field['field'] == 'class' ) {
                                $certificate['fields'][$fid]['text'] = $reg['class_name'];
                            }
                            elseif( $field['field'] == 'adjudicator' && isset($adjudicators[$section['adjudicator1_id']]['name']) ) {
                                $certificate['fields'][$fid]['text'] = $adjudicators[$section['adjudicator1_id']]['name'];
                            }
                        }
                        $certificates[] = $certificate;
/*                        $pdf->AddPage();
                        $pdf->SetCellPaddings(1, 0, 1, 0);
                        $pdf->Image($ciniki['config']['core']['modules_dir'] . '/musicfestivals/templates/certificate.png', 0, 0, 279, 216, '', '', '', false, 300, '', false, false, 0);
                        $pdf->setPageMark();
                        $pdf->setFont('vidaloka', 'B', 28);
                        $pdf->setXY(30, 115);
                        $lh = $pdf->getNumLines($reg['name'], 155) * 12;
                        //$pdf->MultiCell(155, $lh, $reg['name'], $border, 'L', 0, 0, '', '');
                        $pdf->MultiCell(219, $lh, $reg['name'], 0, 'C', 1, 0, '', '');
                        $pdf->setXY(30, 150);
                        $pdf->setFont('helvetica', '', 18);
                        $lh = $pdf->getNumLines($reg['class_name'], 219) * 12;
                        //$pdf->MultiCell(155, $lh, $reg['class_name'], $border, 'L', 0, 0, '', '');
                        $pdf->MultiCell(219, $lh, $reg['class_name'], 0, 'C', 1, 0, '', '');
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
*/
                    }
                }
            }
        }
    }

    //
    // Run the template
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'certificatesPDF');
    $rc = ciniki_musicfestivals_templates_certificatesPDF($ciniki, $args['tnid'], array(
        'festival_id' => $args['festival_id'],
        'certificates' => $certificates,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Return the pdf
    //
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($filename . '.pdf', 'D');
    }

    return array('stat'=>'exit');
}
?>
