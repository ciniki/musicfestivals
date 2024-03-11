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
function ciniki_musicfestivals_registrationCertificatesPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registrations'),
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
    // Load registration
    //
    $strsql = "SELECT 1 AS section_id, "
        . "'' AS section_name, "
        . "1 AS division_id, "
        . "'' division_name, "
        . "DATE_FORMAT(divisions.division_date, '%M %D, %Y') AS division_date_text, "
        . "1 AS timeslot_id, "
        . "'' AS timeslot_name, "
        . "'' AS slot_time_text, "
        . "classes.id AS class1_id, "
        . "0 AS class2_id, "
        . "0 AS class3_id, "
        . "0 AS class4_id, "
        . "0 AS class5_id, "
        . "'' AS class1_name, "
        . "'' AS class2_name, "
        . "'' AS class3_name, "
        . "'' AS class4_name, "
        . "'' AS class5_name, "
        . "'' AS description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.placement, "
        . "registrations.level, "
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
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "("
                . "registrations.class_id = timeslots.class1_id "
                . "OR registrations.class_id = timeslots.class2_id "
                . "OR registrations.class_id = timeslots.class3_id "
                . "OR registrations.class_id = timeslots.class4_id "
                . "OR registrations.class_id = timeslots.class5_id "
                . ") "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
            . "divisions.ssection_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator1_id')),
        array('container'=>'divisions', 'fname'=>'division_id', 'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text')),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'class1_id', 'class2_id', 'class3_id', 'class4_id', 'class5_id', 'description', 'class1_name', 'class2_name', 'class3_name', 'class4_name', 'class5_name')),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title'=>'title1', 'class_name', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id', 'placement', 'level', 'division_date_text')),
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
        . "certificates.orientation, "
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
            'fields'=>array('id', 'festival_id', 'name', 'image_id', 'orientation', 'section_id', 'min_score')),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array(
                'id'=>'field_id', 'name'=>'field_name', 'field',
                'xpos', 'ypos', 'width', 'height', 'font', 'size', 'style', 'align', 'valign', 'color', 
                'bgcolor', 'text'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.296', 'msg'=>'Unable to load certificates', 'err'=>$rc['err']));
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
                    for($i=0;$i<$num_copies;$i++) {
                        foreach($certificate['fields'] as $fid => $field) {
                            if( $field['field'] == 'participant' ) {
                                $certificate['fields'][$fid]['text'] = $reg['name'];
                            }
                            elseif( $field['field'] == 'class' ) {
                                $certificate['fields'][$fid]['text'] = $reg['class_name'];
                            }
                            elseif( $field['field'] == 'title' ) {
                                $certificate['fields'][$fid]['text'] = $reg['title'];
                            }
                            elseif( $field['field'] == 'timeslotdate' ) {
                                $certificate['fields'][$fid]['text'] = $reg['division_date_text'];
                            }
                            elseif( $field['field'] == 'placement' ) {
                                $certificate['fields'][$fid]['text'] = $reg['placement'];
                            }
                            elseif( $field['field'] == 'level' ) {
                                $certificate['fields'][$fid]['text'] = $reg['level'];
                            }
                            elseif( $field['field'] == 'adjudicator' && isset($adjudicators[$section['adjudicator1_id']]['name']) ) {
                                $certificate['fields'][$fid]['text'] = $adjudicators[$section['adjudicator1_id']]['name'];
                            }
                        }
                        $certificates[] = $certificate;
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
        $rc['pdf']->Output($rc['filename'], 'I');
    }

    return array('stat'=>'exit');
}
?>
