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
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the festival
    //
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.flags, "
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
            'fields'=>array('name', 'permalink', 'flags', 'start_date', 'end_date', 'primary_image_id', 'description', 
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
        . "customers.display_name, "
        . "adjudicators.sig_image_id "
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
            'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name', 'sig_image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.324', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0800) ) {
        $strsql .= "divisions.adjudicator_id, ";
    } else {
        $strsql .= "ssections.adjudicator1_id AS adjudicator_id, ";
    }
    $strsql .= "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "IFNULL(DATE_FORMAT(divisions.division_date, '%M %D, %Y'), '') AS timeslot_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.groupname AS timeslot_groupname, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, ";
//    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
//    if( ($festival['flags']&0x80) == 0x80 ) {
    if( isset($festival['certificates-include-pronouns']) && $festival['certificates-include-pronouns'] == 'yes' ) {
        $strsql .= "registrations.pn_private_name AS display_name, ";
    } else {
        $strsql .= "registrations.private_name AS display_name, ";
    }
    $strsql .= "registrations.title1, "
        . "registrations.participation, "
        . "registrations.mark, "
        . "registrations.flags, "
        . "registrations.placement, "
        . "registrations.level, "
        . "IFNULL(classes.code, '') AS class_code, "
        . "IFNULL(classes.name, '') AS class_name, "
        . "IFNULL(categories.name, '') AS category_name, "
        . "IFNULL(sections.name, '') AS syllabus_section_name, "
        . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
        . "IFNULL(registrations.competitor3_id, 0) AS competitor3_id, "
        . "IFNULL(registrations.competitor4_id, 0) AS competitor4_id, "
        . "IFNULL(registrations.competitor5_id, 0) AS competitor5_id, "
        . "IFNULL(competitors.num_people, 1) AS num_people "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
            . "registrations.competitor1_id = competitors.id "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id " 
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id " 
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
        $strsql .= "AND registrations.participation = 0 ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.division_date, divisions.name, slot_time, registrations.timeslot_sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 
                )),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'adjudicator_id',
                )),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'groupname'=>'timeslot_groupname',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 'description', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'num_people',
                'title1', 
                'class_code', 'class_name', 'category_name', 'syllabus_section_name',
                'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id', 
                'participation', 'mark', 'flags', 'placement', 'level', 'timeslot_date_text',
                )),
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
        . "certificates.participation, "
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
            'fields'=>array('id', 'festival_id', 'name', 'image_id', 'orientation', 'section_id', 'min_score', 'participation')),
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
        if( $cert['participation'] == 40 ) {
            $virtual_plus_cert = $cert;
        } elseif( $cert['participation'] == 30 ) {
            $live_plus_cert = $cert;
        } elseif( $cert['participation'] == 20 ) {
            $virtual_cert = $cert;
        } elseif( $cert['participation'] == 10 ) {
            $live_cert = $cert;
        }
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
                    if( $reg['participation'] == 2 && isset($live_plus_cert) ) {
                        $certificate = $live_plus_cert;
                    } elseif( $reg['participation'] == 1 && isset($virtual_cert) ) {
                        $certificate = $virtual_cert;
                    } elseif( $reg['participation'] == 0 && isset($live_cert) ) {
                        $certificate = $live_cert;
                    }

                    //
                    // Check for best in class flag
                    //
//                    if( ($reg['flags']&0x10) == 0x10 ) {
//                        $reg['placement'] .= ' - Best in Class';
//                    }

                    $num_copies = 1;
                    if( $reg['num_people'] > 0 
                        && (!isset($festival['certificates-use-group-numpeople']) || !$festival['certificates-use-group-numpeople'] == 'yes')
                        ) {
                        $num_copies = $reg['num_people'];
                    }
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
                        if( !isset($certificate['fields']) ) {
                            continue;
                        }
                        foreach($certificate['fields'] as $fid => $field) {
                            if( $field['field'] == 'participant' ) {
                                $certificate['fields'][$fid]['text'] = $reg['name'];
                            }
                            elseif( $field['field'] == 'class' || $field['field'] == 'class-group' ) {
                                $class_name = $reg['class_name'];
                                if( isset($festival['certificates-class-format']) 
                                    && $festival['certificates-class-format'] == 'code-section-category-class' 
                                    ) {
                                    $class_name = $reg['class_code'] . ' - ' . $reg['syllabus_section_name'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name']; 
                                } elseif( isset($festival['certificates-class-format']) 
                                    && $festival['certificates-class-format'] == 'section-category-class' 
                                    ) {
                                    $class_name = $reg['syllabus_section_name'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name']; 
                                } elseif( isset($festival['certificates-class-format']) 
                                    && $festival['certificates-class-format'] == 'code-category-class' 
                                    ) {
                                    $class_name = $reg['class_code'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name']; 
                                } elseif( isset($festival['certificates-class-format']) 
                                    && $festival['certificates-class-format'] == 'category-class' 
                                    ) {
                                    $class_name = $reg['category_name'] . ' - ' . $reg['class_name']; 
                                } else {
                                    $class_name = $reg['class_name']; 
                                }
                                if( $field['field'] == 'class-group' && $timeslot['groupname'] != '' ) {
                                    $class_name .= ' - ' . $timeslot['groupname'];
                                }
//                                if( $field['field'] == 'class-group' && preg_match("/(Group\s+[0-9A-Z])/", $timeslot['name'], $m) ) {
//                                    $class_name .= ' - ' . $m[1];
//                                }
                                $certificate['fields'][$fid]['text'] = $class_name;
                            }
                            elseif( $field['field'] == 'title' ) {
                                $certificate['fields'][$fid]['text'] = $reg['title1'];
                            }
                            elseif( $field['field'] == 'timeslotdate' ) {
                                $certificate['fields'][$fid]['text'] = $reg['timeslot_date_text'];
                            }
                            elseif( $field['field'] == 'placement' ) {
                                $certificate['fields'][$fid]['text'] = $reg['placement'];
                            }
                            elseif( $field['field'] == 'adjudicator' && isset($adjudicators[$division['adjudicator_id']]['name']) ) {
                                $certificate['fields'][$fid]['text'] = $adjudicators[$division['adjudicator_id']]['name'];
                            }
                            elseif( $field['field'] == 'adjudicatorsig' && isset($adjudicators[$division['adjudicator_id']]['sig_image_id']) && $adjudicators[$division['adjudicator_id']]['sig_image_id'] > 0 ) {
                                $certificate['fields'][$fid]['image_id'] = $adjudicators[$division['adjudicator_id']]['sig_image_id'];
                            }
                            elseif( $field['field'] == 'adjudicatorsigorname' ) {
                                if( isset($adjudicators[$division['adjudicator_id']]['sig_image_id']) && $adjudicators[$division['adjudicator_id']]['sig_image_id'] > 0 ) {
                                    $certificate['fields'][$fid]['image_id'] = $adjudicators[$division['adjudicator_id']]['sig_image_id'];
                                } elseif( isset($adjudicators[$division['adjudicator_id']]['name']) ) {
                                    $certificate['fields'][$fid]['text'] = $adjudicators[$division['adjudicator_id']]['name'];
                                } else {
                                    $certificate['fields'][$fid]['text'] = '';
                                }
                            }
                            elseif( $field['field'] == 'text' ) {
                                $certificate['fields'][$fid]['text'] = str_replace('{_placement_}', $reg['placement'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_participant_}', $reg['name'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_title_}', $reg['title1'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_mark_}', $reg['mark'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_placement_}', $reg['placement'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_level_}', $reg['level'], $certificate['fields'][$fid]['text']);
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
        $rc['pdf']->Output($filename . '.pdf', 'I');
    }

    return array('stat'=>'exit');
}
?>
