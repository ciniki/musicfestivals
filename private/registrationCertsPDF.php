<?php
//
// Description
// ===========
// This function will return the certificate for a registration
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
function ciniki_musicfestivals_registrationCertsPDF($ciniki, $tnid, $args) {

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
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        . "customers.display_name, "
        . "adjudicators.sig_image_id "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name', 'sig_image_id')),
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
        . "timeslots.name AS timeslot_name, "
        . "'' AS slot_time_text, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.mark, "
        . "registrations.flags, "
        . "registrations.placement, "
        . "registrations.level, "
        . "registrations.participation, "
        . "IFNULL(classes.code, '') AS class_code, "
        . "IFNULL(classes.name, '') AS class_name, "
        . "IFNULL(categories.name, '') AS category_name, "
        . "IFNULL(sections.name, '') AS syllabus_section_name, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql .= "divisions.adjudicator_id, ";
    } else {
        $strsql .= "ssections.adjudicator1_id AS adjudicator_id, ";
    }
    $strsql .= "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
        . "IFNULL(registrations.competitor3_id, 0) AS competitor3_id, "
        . "IFNULL(registrations.competitor4_id, 0) AS competitor4_id, "
        . "IFNULL(registrations.competitor5_id, 0) AS competitor5_id "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id " 
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id " 
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
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
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title'=>'title1', 'class_name', 
                'class_code', 'class_name', 'category_name', 'syllabus_section_name',
                'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id', 
                'participation', 'mark', 'flags', 'placement', 'level', 'division_date_text',
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
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE certificates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND certificates.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY certificates.section_id, certificates.participation DESC, certificates.min_score DESC, certificates.name "
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.296', 'msg'=>'Unable to load certificates', 'err'=>$rc['err']));
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
                    if( isset($args['single']) && $args['single'] == 'yes' ) {
                        $num_copies = 1;
                    }
                    for($i=0;$i<$num_copies;$i++) {
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
                                if( $field['field'] == 'class-group' && preg_match("/(Group\s+[0-9A-Z])/", $timeslot['name'], $m) ) {
                                    $class_name .= ' - ' . $m[1];
                                }
                                $certificate['fields'][$fid]['text'] = $class_name;
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
                                $certificate['fields'][$fid]['text'] = str_replace('{_title_}', $reg['title'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_mark_}', $reg['mark'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_placement_}', $reg['placement'], $certificate['fields'][$fid]['text']);
                                $certificate['fields'][$fid]['text'] = str_replace('{_level_}', $reg['level'], $certificate['fields'][$fid]['text']);
                            }
                        }
                        $certificates[] = $certificate;

                        //
                        // Check if Best in Class flag set and add second certificate
                        //
                        if( ($reg['flags']&0x10) == 0x10 ) {
                            $org_certificate = $default_cert;
                            if( $reg['participation'] == 2 && isset($live_plus_cert) ) {
                                $org_certificate = $live_plus_cert;
                            } elseif( $reg['participation'] == 1 && isset($virtual_cert) ) {
                                $org_certificate = $virtual_cert;
                            } elseif( $reg['participation'] == 0 && isset($live_cert) ) {
                                $org_certificate = $live_cert;
                            }
                            foreach($certificate['fields'] as $fid => $field) {
                                if( $field['field'] == 'placement' ) {
                                    $certificate['fields'][$fid]['text'] = 'Best in Class';
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
                                    $certificate['fields'][$fid]['text'] = $class_name;
                                }
                                elseif( $field['field'] == 'text' ) {
                                    $certificate['fields'][$fid]['text'] = str_replace('{_placement_}', 'Best in Class', $org_certificate['fields'][$fid]['text']);
                                }
                            }
                            $certificates[] = $certificate;
                        }
                    }
                }
            }
        }
    }

    //
    // Run the template
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'certificatesPDF');
    return ciniki_musicfestivals_templates_certificatesPDF($ciniki, $tnid, array(
        'festival_id' => $args['festival_id'],
        'certificates' => $certificates,
        ));
}
?>
