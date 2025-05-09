<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_registrationFormGenerate(&$ciniki, $tnid, &$request, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.341', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a festival was specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.342', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    //
    // Make sure competitors where passed in arguments
    //
    if( !isset($args['competitors']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.344', 'msg'=>"No competitors specified"));
    }
    $competitors = $args['competitors'];

    //
    // Make sure teachers where passed in arguments
    //
    if( !isset($args['teachers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.345', 'msg'=>"No teachers specified"));
    }
    $teachers = $args['teachers'];

    //
    // Make sure teachers where passed in arguments
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
        if( !isset($args['accompanists']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.591', 'msg'=>"No accompanists specified"));
        }
        $accompanists = $args['accompanists'];
    }

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        if( !isset($args['members']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.658', 'msg'=>"No accompanists specified"));
        }
        $members = $args['members'];
    }

    //
    // Make sure registration where passed in arguments
    //
    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.349', 'msg'=>"No registration specified"));
    }
    $registration = $args['registration'];

    //
    // Make sure customer type is passed
    //
    if( !isset($args['customer_type']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.350', 'msg'=>"No customer type specified"));
    }
    $customer_type = $args['customer_type'];

    //
    // Make sure customer specified
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == '' || $args['customer_id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.347', 'msg'=>"No customer specified"));
    }

    //
    // Load the sections and classes
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.flags AS section_flags, "
        . "sections.live_end_dt, "
        . "sections.virtual_end_dt, "
        . "sections.titles_end_dt, "
        . "sections.upload_end_dt, "
        . "sections.latefees_start_amount, "
        . "sections.latefees_daily_increase, "
        . "sections.latefees_days, "
        . "categories.name AS category_name, "
        . "classes.id AS class_id, "
        . "classes.uuid AS class_uuid, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "CONCAT_WS(' - ', sections.name, classes.code, classes.name) AS sectionclassname, "
        . "classes.flags AS class_flags, "
        . "classes.feeflags, "
        . "classes.titleflags, "
        . "classes.min_competitors, "
        . "classes.max_competitors, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee, "
        . "classes.options "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND (classes.flags&0x01) = 0x01 "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (sections.flags&0x01) = 0 "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'flags'=>'section_flags',
                'live_end_dt', 'virtual_end_dt', 'titles_end_dt', 'upload_end_dt',
                'latefees_start_amount', 'latefees_daily_increase', 'latefees_days',
                ),
            ),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'uuid'=>'class_uuid', 'category_name', 'code'=>'class_code', 
                'name'=>'class_name', 'sectionclassname', 'flags'=>'class_flags', 'feeflags', 'titleflags',
                    'min_competitors', 'max_competitors', 
                    'min_titles', 'max_titles', 
                    'earlybird_fee', 'fee', 
                    'vfee' => 'virtual_fee', 'earlybird_plus_fee', 'plus_fee', 'options',
                    ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.299', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();


    //
    // Build the list of classes and find selected class
    //
    $classes_2c = array();  // Class id's with 2 competitors
    $classes_3c = array();  // Class id's with 3 competitors
    $classes_4c = array();  // Class id's with 4 competitors
    $js_classes = array();  // Class array that will be in javascript: flags, min_titles, max_titles
    $live_prices = array();
    $plus_prices = array();
    $virtual_prices = array();
    $virtual_only = array();    // Used when virtual option but not virtual pricing
    $dt = new DateTime('now', new DateTimezone('UTC'));
    foreach($sections as $sid => $section) {
        // Set default to current festival
        $section_live = $festival['live'] == 'yes' ? 'yes' : 'no';
        $section_virtual = $festival['virtual'] == 'yes' ? 'yes' : 'no';
        $section_edit = $festival['edit'] == 'yes' ? 'yes' : 'no';
        $section_upload = $festival['upload'] == 'yes' ? 'yes' : 'no';
        if( ($festival['flags']&0x08) == 0x08 ) {
            if( $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
                if( $section_live_dt < $dt ) {
                    $section_live = 'no';
                } else {
                    $section_live = 'yes';
                }
            }
            if( $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
                if( $section_virtual_dt < $dt ) {
                    $section_virtual = 'no';
                } else {
                    $section_virtual = 'yes';
                }
            }
            if( $section['titles_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_edit_dt = new DateTime($section['titles_end_dt'], new DateTimezone('UTC'));
                if( $section_edit_dt < $dt ) {
                    $section_edit = 'no';
                } else {
                    $section_edit = 'yes';
                }
            }
            if( $section['upload_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_upload_dt = new DateTime($section['upload_end_dt'], new DateTimezone('UTC'));
                if( $section_upload_dt < $dt ) {
                    $section_upload = 'no';
                } else {
                    $section_upload = 'yes';
                }
            }
        }
        //
        // Check for latefees applying to section
        //
        if( $section_live == 'no' && ($section['flags']&0x30) > 0  && $section['latefees_days'] > 0 ) {
            if( ($festival['flags']&0x08) == 0x08 && $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
            } else {
                $section_live_dt = clone $festival['live_end_dt'];
            }
            $interval = $section_live_dt->diff($dt);
            $section_live_dt->add(new DateInterval("P{$section['latefees_days']}D"));
            if( $section_live_dt > $dt ) {      // is within latefees_days
                $section_live = 'yes';
                $sections[$sid]['live_days_past'] = $interval->format('%d');
                $sections[$sid]['live_latefees'] = $section['latefees_start_amount']
                    + ($section['latefees_daily_increase'] * $sections[$sid]['live_days_past']);
            }
        }
        if( $section_virtual == 'no' && ($festival['flags']&0x06) > 0 && ($section['flags']&0x30) > 0  && $section['latefees_days'] > 0 ) {
            if( ($festival['flags']&0x08) == 0x08 && $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
            } else {
                $section_virtual_dt = clone $festival['virtual_end_dt'];
            }
            $interval = $section_virtual_dt->diff($dt);
            $section_virtual_dt->add(new DateInterval("P{$section['latefees_days']}D"));
            if( $section_virtual_dt > $dt ) {      // is within latefees_days
                $section_virtual = 'yes';
                $sections[$sid]['virtual_days_past'] = $interval->format('%d');
                $sections[$sid]['virtual_latefees'] = $section['latefees_start_amount']
                    + ($section['latefees_daily_increase'] * $sections[$sid]['virtual_days_past']);
            }
        }

        $sections[$sid]['edit'] = $section_edit;
        $sections[$sid]['upload'] = $section_upload;
        if( isset($section['classes']) ) {
            foreach($section['classes'] as $cid => $section_class) {
                $js_classes[$cid] = array(
                    'f' => $section_class['flags'],
                    'ff' => $section_class['feeflags'],
                    'tf' => $section_class['titleflags'],
                    'mic' => $section_class['min_competitors'],
                    'mac' => $section_class['max_competitors'],
                    'mit' => $section_class['min_titles'],
                    'mat' => $section_class['max_titles'],
                    );
                if( ($section_class['flags']&0x10) == 0x10 ) {
                    $js_classes[$cid]['opt'] = [];
                    if( isset($section_class['options']) && $section_class['options'] != '' ) {
                        $js_classes[$cid]['opt'] = json_decode($section_class['options'], true);
                    }
                }
                //
                // Check syllabus class name format
                //
                if( $section_class['code'] != '' ) {
                    if( ($festival['flags']&0x0100) == 0x0100 ) {
                        $sections[$sid]['classes'][$cid]['codename'] = $section_class['code'] . ' - ' . $section_class['category_name'] . ' - ' . $section_class['name'];
                    } else {
                        $sections[$sid]['classes'][$cid]['codename'] = $section_class['code'] . ' - ' . $section_class['name'];
                    }
                }
                elseif( ($festival['flags']&0x0100) == 0x0100 ) {
                    $section['classes'][$cid]['name'] = $section_class['category_name'] . ' - ' . $section_class['name'];
                }
//                if( ($section_class['flags']&0x04) == 0x04 ) {
//                    $classes_instrument[] = $cid;
//                }
                // Check if class is NOT group/ensemble and 2,3,4 competitors enabled
/*                if( ($section_class['flags']&0x8010) == 0x10 ) {
                    $classes_2c[] = $cid;
                }
                if( ($section_class['flags']&0x8020) == 0x20 ) {
                    $classes_3c[] = $cid;
                }
                if( ($section_class['flags']&0x8040) == 0x40 ) { 
                    $classes_4c[] = $cid;
                } */
//                $classes_min_titles[$cid] = $section_class['min_titles'];
//                $classes_max_titles[$cid] = $section_class['max_titles'];
                if( isset($_GET['cl']) && $_GET['cl'] == $section_class['uuid'] ) {
                    $selected_sid = $sid;
                    $selected_cid = $cid;
                }
                //
                // Check for valid sections and options when not in view mode for the form.
                //
                if( $args['display'] != 'view' ) {
                    // Adjudication Plus
                    if( ($festival['flags']&0x10) == 0x10 ) {
                        if( $festival['earlybird'] == 'yes' && $section_live == 'yes' && $section_class['earlybird_fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['earlybird_fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['earlybird_fee'];
                            if( ($section_class['feeflags']&0x10) == 0x10 ) { 
                                $plus_prices[$cid] = '$' . number_format($section_class['earlybird_plus_fee'], 2);
                                $sections[$sid]['classes'][$cid]['plus_fee'] = $section_class['earlybird_plus_fee'];
                            }
                        } elseif( $festival['live'] == 'yes' && $section_live == 'yes' && $section_class['fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                            if( ($section_class['feeflags']&0x20) == 0x20 ) { 
                                $plus_prices[$cid] = '$' . number_format($section_class['plus_fee'], 2);
                                $sections[$sid]['classes'][$cid]['plus_fee'] = $section_class['plus_fee'];
                            }
                        }
                    }
                    // Virtual option(0x02) and virtual pricing(0x04) set for festival 
                    else if( ($festival['flags']&0x06) == 0x06 ) {
                        if( $festival['earlybird'] == 'yes' && $section_live == 'yes' && ($section_class['feeflags']&0x01) == 0x01 ) { //&& $section_class['earlybird_fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['earlybird_fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['earlybird_fee'];
                        } elseif( $festival['live'] == 'yes' && $section_live == 'yes' && ($section_class['feeflags']&0x02) == 0x02 ) { //&& $section_class['fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                        } elseif( $festival['live'] == 'sections' && $section_live == 'yes' && ($section_class['feeflags']&0x02) == 0x02 ) { //$section_class['fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                        }
                        if( $festival['virtual'] == 'yes' && $section_virtual == 'yes' && ($section_class['feeflags']&0x08) == 0x08 ) { //&& $section_class['vfee'] > 0 ) {
                            $virtual_prices[$cid] = '$' . number_format($section_class['vfee'], 2);
                            $sections[$sid]['classes'][$cid]['virtual_fee'] = $section_class['vfee'];
                        } elseif( $festival['virtual'] == 'sections' && $section_virtual == 'yes' && ($section_class['feeflags']&0x08) == 0x08 ) { //&& $section_class['vfee'] > 0 ) {
                            $virtual_prices[$cid] = '$' . number_format($section_class['vfee'], 2);
                            $sections[$sid]['classes'][$cid]['virtual_fee'] = $section_class['vfee'];
                        }
                        //
                        // Check to see if class is still available for registration
                        //
                        if( !isset($sections[$sid]['classes'][$cid]['live_fee'])
                            && !isset($sections[$sid]['classes'][$cid]['virtual_fee'])
                            && $args['display'] != 'view' 
                            ) {
                            unset($sections[$sid]['classes'][$cid]);
                        }
                    }
                    //
                    // Only virtual option set, with same pricing
                    //
                    elseif( ($festival['flags']&0x06) == 0x02 ) {
                        if( ($sections[$sid]['classes'][$cid]['flags']&0x20) == 0x20 ) {
                            $virtual_only[$cid] = 1;
                        }
    /*                    if( $festival['earlybird'] == 'yes' && $section_class['earlybird_fee'] > 0 ) {
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['earlybird_fee'];
                        } else {
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                        }
                        if( $festival['virtual'] == 'yes' && $section_class['vfee'] > 0 ) {
                            $sections[$sid]['classes'][$cid]['virtual_fee'] = $section_class['vfee'];
                        } */
                        //
                        // Check to see if class is still available for registration
                        //
    /*                    if( !isset($sections[$sid]['classes'][$cid]['live_fee'])
                            && !isset($sections[$sid]['classes'][$cid]['virtual_fee'])
                            ) {
                            unset($sections[$sid]['classes'][$cid]);
                        } */
                        if( ($festival['flags']&0x08) == 0x08 && $section_live == 'no' && $section_virtual == 'no' ) {
                            unset($sections[$sid]['classes'][$cid]);
                        }
                    }
                    //
                    // Section end dates and no virtual option or pricing
                    //
                    elseif( ($festival['flags']&0x08) == 0x08 ) {
                        if( $section_live == 'no' ) {
                            unset($sections[$sid]['classes'][$cid]);
                        }
                    }
                } 
            }
        }
    }
    foreach($sections as $sid => $section) {
        if( count($section['classes']) == 0 ) {
            unset($sections[$sid]);
        }
    }
    if( isset($selected_sid) && isset($sections[$selected_sid]) ) {
        $selected_section = $sections[$selected_sid];
        if( isset($selected_cid) && isset($sections[$selected_sid]['classes'][$selected_cid]) ) {
            $selected_class = $sections[$selected_sid]['classes'][$selected_cid];
        }
    }

    //
    // Check for different class submitted in form
    //
    if( isset($_POST['f-section']) && is_numeric($_POST['f-section']) && $_POST['f-section'] > 0 ) {
        $selected_section = $sections[$_POST['f-section']];
        if( isset($_POST["f-section-{$_POST['f-section']}-class"]) ) {
            $selected_class = $sections[$_POST['f-section']]['classes'][$_POST["f-section-{$_POST['f-section']}-class"]];
// Dead code, variables not used anywhere, removed Nov 24, 2023
//            $comp_required = 1;
//            $titles_required = 1;
//            if( ($selected_class['flags']&0x10) == 0x10 ) {
//                $comp_required = 2;
//            }
//            if( ($selected_class['flags']&0x20) == 0x20 ) {
//                $comp_required = 3;
//            }
        }
    }
    elseif( isset($registration['class_id']) ) {
        foreach($sections as $sid => $section) {
            if( isset($section['classes'][$registration['class_id']]) ) {
                $selected_section = $section;
                $selected_class = $section['classes'][$registration['class_id']];
                break;
            }
        }
    }
    if( isset($_POST['f-member_id']) && is_numeric($_POST['f-member_id']) && $_POST['f-member_id'] > 0 ) {
        if( isset($members[$_POST['f-member_id']]) ) {
            $selected_member = $members[$_POST['f-member_id']];
        } 
    }
    elseif( isset($registration['member_id']) && $registration['member_id'] > 0 ) {
        if( isset($members[$registration['member_id']]) ) {
            $selected_member = $members[$registration['member_id']];
        } 
    }

    //
    // Select the first section and class if nothing selected
    //
    if( !isset($selected_section) ) {
        foreach($sections as $section) {
            $selected_section = $section;
            foreach($section['classes'] as $class) {
                $selected_class = $class;
                break;
            }
            break;
        }
    }
    if( isset($selected_class['options']) && $selected_class['options'] != '' ) {
        $selected_class['options'] = json_decode($selected_class['options'], true);
    }


    //
    // Setup the fields for the form
    //
    $fields = array(
        'registration_id' => array(
            'id' => 'registration_id',
            'label' => '',
            'ftype' => 'hidden',
            'value' => (isset($_POST['f-registration_id']) ? $_POST['f-registration_id'] : (isset($registration['registration_id']) ? $registration['registration_id'] : 0)),
            ),
        'action' => array(
            'id' => 'action',
            'label' => '',
            'ftype' => 'hidden',
            'value' => 'update',
            ),
        );

    //
    // Add member festivals to dropdown
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $js_members = array();
        $dt = new DateTime('now', new DateTimezone('UTC'));
        foreach($members as $mid => $member) {
            $js_members[$member['id']] = array('s'=>0);  // Closed
            if( isset($member['open']) && $member['open'] == 'yes' ) {
                $js_members[$member['id']]['s'] = 1;
                if( isset($member['latefee']) ) {
                    $js_members[$member['id']]['l'] = $member['latefee'];
                }
                
            }
        }
        array_unshift($members, array(
            'id' => 0,
            'name' => 'Choose the local festival you competed in and recommended you',
            'reg_start_dt' => '',
            'reg_start_dt' => '',
            ));

        $fields['member_id'] = array(
            'id' => 'member_id',
            'ftype' => 'select',
//            'label' => 'Recommending Local Festival',
            'label' => 'Which festival invited you?',
            'description' => 'You must choose the local festival where you competed and who recommended you to provincials.',
            'blank' => 'no',
            'size' => 'large',
            'required' => 'yes',
            'options' => $members, 
            'value' => (isset($_POST['f-member_id']) ? $_POST['f-member_id'] : (isset($registration['member_id']) ? $registration['member_id'] : 0)),
            'onchange' => 'memberSelected()',
            );
        $fields['member_break'] = array(
            'id' => 'member_break',
            'ftype' => 'break',
            'class' => 'member-break' . ($args['display'] == 'view' ? '' : ' hidden'),
            );
        if( $fields['member_id']['value'] > 0 
            && isset($js_members[$fields['member_id']['value']]) 
            && $js_members[$fields['member_id']['value']]['s'] == 1
            ) {
            $fields['member_break']['class'] = 'member-break';  
        }
    }

    //
    // Check if the selected class is from a section that should not be allowed to change class
    //
    if( isset($selected_section) && ($selected_section['flags']&0x04) == 0x04 && isset($selected_class)) {
        $fields['section'] = array(
            'id' => 'section',
            'ftype' => 'hidden',
            'value' => $selected_section['id'],
            );
        $fields["section-{$selected_section['id']}-classes"] = array(
            'id' => "section-{$selected_section['id']}-class",
            'ftype' => 'hidden',
            'value' => $selected_class['id'],
            );
        $fields['class_name'] = array(
            'id' => 'class_name',
            'ftype' => 'content',
            'label' => 'Class',
            'description' => $selected_section['name'] . ' - ' . $selected_class['name'],
            );
    } else {
        //
        // Remove any hidden from registration form classes
        //
        foreach($sections as $sid => $section) {
            if( ($section['flags']&0x04) == 0x04 ) {
                unset($sections[$sid]);
            }
        }

        $fields['section'] = array(
            'id' => 'section',
            'ftype' => 'select',
            'label' => 'Section',
            'blank' => 'no',
            'size' => 'small',
            'required' => 'yes',
            'flex-basis' => '10em',
            'onchange' => 'sectionSelected()',
            'options' => $sections,
            'value' => (isset($selected_section) ? $selected_section['id'] : (isset($registration['section']) ? $registration['section'] : '')),
            );

        //
        // Add the classes for each section
        //
        foreach($sections as $sid => $section) {
            if( isset($section['classes']) ) {
                $fields["section-{$sid}-classes"] = array(
                    'id' => "section-{$sid}-class",
                    'ftype' => 'select',
                    'label' => 'Class',
                    'required' => 'yes',
                    'class' => isset($selected_section['id']) && $selected_section['id'] == $sid ? '' : 'hidden',
                    'blank' => 'no',
                    'size' => 'medium',
                    'flex-basis' => '32em',
                    'options' => $section['classes'],
                    'option-id-field' => 'id',
                    'option-value-field' => 'codename',
                    'onchange' => "classSelected({$sid})",
                    'value' => isset($selected_class['id']) ? $selected_class['id'] : '',
                    );
            }
        }
    }

    //
    // Add competitor information 
    //
    for($i = 1; $i <= 4; $i++) {
        $required = 'no';
        if( isset($selected_class) && $i <= $selected_class['max_competitors'] ) {
            $class = '';
            if( $i <= $selected_class['min_competitors'] ) {
                $required = 'yes';
            }
        } else {
            $class = 'hidden';
        }
        $prefix = '';
        if( $i == 1 && isset($selected_class) && $selected_class['max_competitors'] > 1 ) {
            $prefix = '1st ';
        } elseif( $i == 2 ) {
            $prefix = '2nd ';
        } elseif( $i == 3 ) {
            $prefix = '3rd ';
        } elseif( $i == 4 ) {
            $prefix = '4th ';
        }
        if( isset($selected_class) ) {
            $label = $prefix . (($selected_class['flags']&0x8000) == 0x8000 ? 'Group/Ensemble' : $festival['competitor-label-singular']);
        } else {
            $label = $festival['competitor-label-singular'];
        }
        $comp_id = isset($_POST["f-competitor{$i}_id"]) && ($_POST["f-competitor{$i}_id"] == '' || $_POST["f-competitor{$i}_id"] > -1) ? $_POST["f-competitor{$i}_id"] : (isset($registration["competitor{$i}_id"]) ? $registration["competitor{$i}_id"] : 0);
        $fields["competitor{$i}_id"] = array(
            'id' => "competitor{$i}_id",
            'ftype' => 'select',
            'size' => 'large',
            'class' => $class,
            'required' => $required,
            'label' => $label,
            'onchange' => "competitorSelected({$i})",
            'options' => $competitors,
            'value' => $comp_id,
            );
        $fields["competitor{$i}_id"]['options']['addindividual'] = array(
            'id' => '-1',
            'class' => (isset($selected_class['flags']) && ($selected_class['flags']&0xC000) == 0x8000 ? 'hidden' : ''),
            'name' => 'Add Individual ' . $festival['competitor-label-singular'],
            );
        $fields["competitor{$i}_id"]['options']['addgroup'] = array(
            'id' => '-2',
            'class' => (isset($selected_class['flags']) && ($selected_class['flags']&0xC000) == 0x4000 ? 'hidden' : ''),
            'name' => 'Add Group/Ensemble',
            );
            //
            // DO NOT ADD EDIT BUTTON
            // It will confuse customers and think to change competitor they just change name 
            // but will change all registrations for that competitor.
            // 
/*        $fields["competitor{$i}_edit"] = array(
            'id' => "competitor{$i}_edit",
            'label' => '',
            'ftype' => 'button',
            'size' => 'tiny',
            'class' => ($comp_id > 0 ? '' : $class),
            'value' => "Edit {$festival['competitor-label-singular']}",
            'href' => "javascript: competitorEdit({$i});",
            ); */
    }

    //
    // Add the instrument field
    //
    $fields["instrument"] = array(
        'id' => "instrument",
        'ftype' => 'text',
        'class' => (isset($selected_class) && ($selected_class['flags']&0x04) == 0x04 ? '' : 'hidden'),
        'required' => 'yes',
        'size' => 'large',
        'label' => "{$festival['competitor-label-singular']} Instrument",
        'value' => isset($_POST["f-instrument"]) ? $_POST["f-instrument"] : (isset($registration["instrument"]) ? $registration["instrument"] : ''),
        );

    //
    // Add teacher
    //
    if( $customer_type != 20 ) {
        $fields["teacher_customer_id"] = array(
            'id' => "teacher_customer_id",
            'ftype' => 'select',
            'size' => 'large',
            'label' => "Teacher",
            'blank-label' => 'No Teacher',
            'onchange' => "teacherSelected()",
            'options' => $teachers,
            'complex_options' => array(
                'value' => 'id',
                'name' => 'name',
                ),
            'value' => isset($_POST["f-teacher_customer_id"]) ? $_POST["f-teacher_customer_id"] : (isset($registration["teacher_customer_id"]) ? $registration["teacher_customer_id"] : 0),
            );
        $fields['teacher_name'] = array(
            'id' => 'teacher_name',
            'label' => 'Teacher Name',
            'ftype' => 'text',
            'size' => 'large',
            'class' => isset($_POST['f-teacher_customer_id']) && $_POST['f-teacher_customer_id'] == -1 ? '' : 'hidden',
            'value' => isset($_POST['f-teacher_name']) ? $_POST['f-teacher_name'] : '',
            );
        $fields['teacher_email'] = array(
            'id' => 'teacher_email',
            'label' => 'Teacher Email',
            'ftype' => 'text',
            'size' => 'medium',
            'required' => 'yes',
            'class' => isset($_POST['f-teacher_customer_id']) && $_POST['f-teacher_customer_id'] == -1 ? '' : 'hidden',
            'value' => isset($_POST['f-teacher_email']) ? $_POST['f-teacher_email'] : '',
            );
        $fields['teacher_phone'] = array(
            'id' => 'teacher_phone',
            'label' => 'Teacher Phone',
            'ftype' => 'text',
            'size' => 'medium',
            'class' => isset($_POST['f-teacher_customer_id']) && $_POST['f-teacher_customer_id'] == -1 ? '' : 'hidden',
            'value' => isset($_POST['f-teacher_phone']) ? $_POST['f-teacher_phone'] : '',
            );
        $fields["teacher_customer_id"]['options']['add'] = array(
            'id' => '-1',
            'name' => 'Add Teacher',
            );
/*        $fields['teacher_share'] = array(
            'id' => 'teacher_share',
            'label' => 'Share registration with Teacher',
            'ftype' => 'checkbox',
            'size' => 'medium',
            'class' => isset($_POST['f-teacher_customer_id']) && $_POST['f-teacher_customer_id'] != 0 && $_POST['f-teacher_customer_id'] != '' ? '' : (isset($registration['teacher_customer_id']) && $registration['teacher_customer_id'] != 0 ? '' : 'hidden'),
            'value' => isset($_POST["f-teacher_share"]) ? $_POST["f-teacher_share"] : (isset($registration["teacher_share"]) ? $registration["teacher_share"] : 'on'),
            );
        if( isset($_POST["f-teacher_share"]) ) {
            $fields['teacher_share']['value'] = $_POST["f-teacher_share"];
        } elseif( isset($_POST["f-teacher_customer_id"]) ) {
            $fields['teacher_share']['value'] = 'off';
        } */
    }

    //
    // Add the accompanist field
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
        $fields["accompanist_customer_id"] = array(
            'id' => "accompanist_customer_id",
            'ftype' => 'select',
            'class' => (isset($selected_class) && ($selected_class['flags']&0x3000) > 0 ? '' : 'hidden'),
            'size' => 'large',
            'required' => (isset($selected_class) && ($selected_class['flags']&0x1000) > 0 ? 'yes' : 'no'),
            'label' => "Accompanist",
            'blank-label' => 'No Accompanist',
            'onchange' => "accompanistSelected()",
            'options' => $accompanists,
            'complex_options' => array(
                'value' => 'id',
                'name' => 'name',
                ),
            'value' => isset($_POST["f-accompanist_customer_id"]) ? $_POST["f-accompanist_customer_id"] : (isset($registration["accompanist_customer_id"]) ? $registration["accompanist_customer_id"] : 0),
            );
        $fields['accompanist_name'] = array(
            'id' => 'accompanist_name',
            'label' => 'Accompanist Name',
            'ftype' => 'text',
            'size' => 'large',
            'class' => isset($_POST['f-accompanist_customer_id']) && $_POST['f-accompanist_customer_id'] == -1 ? '' : 'hidden',
            'value' => isset($_POST['f-accompanist_name']) ? $_POST['f-accompanist_name'] : '',
            );
        $fields['accompanist_email'] = array(
            'id' => 'accompanist_email',
            'label' => 'Accompanist Email',
            'ftype' => 'text',
            'size' => 'medium',
            'required' => 'yes',
            'class' => isset($_POST['f-accompanist_customer_id']) && $_POST['f-accompanist_customer_id'] == -1 ? '' : 'hidden',
            'value' => isset($_POST['f-accompanist_email']) ? $_POST['f-accompanist_email'] : '',
            );
        $fields['accompanist_phone'] = array(
            'id' => 'accompanist_phone',
            'label' => 'Accompanist Phone',
            'ftype' => 'text',
            'size' => 'medium',
            'class' => isset($_POST['f-accompanist_customer_id']) && $_POST['f-accompanist_customer_id'] == -1 ? '' : 'hidden',
            'value' => isset($_POST['f-accompanist_phone']) ? $_POST['f-accompanist_phone'] : '',
            );
        $fields["accompanist_customer_id"]['options']['add'] = array(
            'id' => '-1',
            'name' => 'Add Accompanist',
            );
/*        $fields['accompanist_share'] = array(
            'id' => 'accompanist_share',
            'label' => 'Share registration with Accompanist',
            'ftype' => 'checkbox',
            'size' => 'medium',
            'class' => isset($_POST['f-accompanist_customer_id']) && $_POST['f-accompanist_customer_id'] != 0 && $_POST['f-accompanist_customer_id'] != '' ? '' : (isset($registration['accompanist_customer_id']) && $registration['accompanist_customer_id'] != 0 ? '' : 'hidden'),
            'value' => isset($_POST["f-accompanist_share"]) ? $_POST["f-accompanist_share"] : (isset($registration["accompanist_share"]) ? $registration["accompanist_share"] : 'on'),
            );
        if( isset($_POST["f-accompanist_share"]) ) {
            $fields['accompanist_share']['value'] = $_POST["f-accompanist_share"];
        } elseif( isset($_POST["f-accompanist_customer_id"]) ) {
            $fields['accompanist_share']['value'] = 'off';
        } */
    }

    //
    // Check if virtual performance option is available
    //
    $participation = isset($registration['participation']) ? $registration['participation'] : -1;
    if( isset($_POST['f-participation']) && $_POST['f-participation'] == 0 ) {
        $participation = 0;
    } elseif( isset($_POST['f-participation']) && $_POST['f-participation'] == 1 ) {
        $participation = 1;
    } elseif( isset($_POST['f-participation']) && $_POST['f-participation'] == 2 ) {
        $participation = 2;
    }
    // FIXME: May need a Adjudication Plus and Virtual flag check first
    // Virtual
    if( ($festival['flags']&0x02) == 0x02 ) {
        $fields['line-participation'] = array(
            'id' => 'line-participation',
            'ftype' => 'line',
            );
        $fields['participation'] = array(
            'id' => 'participation',
            'label' => isset($festival['registration-participation-label']) && $festival['registration-participation-label'] != '' ? $festival['registration-participation-label'] : 'I would like to participate',
            'onchange' => 'participationSelected()',
            'ftype' => 'select',
            'blank' => 'no',
            'required' => 'yes',
            'size' => 'large',
            'options' => array(
                '-1' => 'Please choose how you will participate',
                '0' => 'in person on a date to be scheduled',
                '1' => 'virtually and submit a video',
                ),
            'value' => isset($_POST['f-participation']) ? $_POST['f-participation'] : (isset($registration['participation']) ? $registration['participation'] : -1),
            );
        //
        // Setup pricing for virtual option with separate virtual pricing
        //
        if( ($festival['flags']&0x06) == 0x06 ) {
            if( isset($festival['live']) && $festival['live'] != 'no' 
                && isset($selected_class['feeflags']) && ($selected_class['feeflags']&0x02) == 0x02
                && isset($selected_class['live_fee']) 
//                && $selected_class['live_fee'] > 0 
                ) {
                $fields['participation']['options'][0] .= ': $' . number_format($selected_class['live_fee'], 2);
                if( isset($selected_member['latefee']) ) {
                    $fields['participation']['options'][0] .= ' + $' . $selected_member['latefee'] . ' late fee';
                }
            } else {
                unset($fields['participation']['options'][-1]);
                unset($fields['participation']['options'][0]);
//                $participation = 0;
            }
            if( isset($festival['virtual']) && $festival['virtual'] != 'no' 
                && isset($selected_class['feeflags']) && ($selected_class['feeflags']&0x08) == 0x08
                && isset($selected_class['virtual_fee']) 
//                && $selected_class['virtual_fee'] > 0 
                ) {
                $fields['participation']['options'][1] .= ': $' . number_format($selected_class['virtual_fee'], 2);
                if( isset($selected_member['latefee']) ) {
                    $fields['participation']['options'][1] .= ' + $' . $selected_member['latefee'] . ' late fee';
                }
            } else {
                if( isset($fields['participation']['options'][-1]) ) {
                    unset($fields['participation']['options'][-1]);
                }
                unset($fields['participation']['options'][1]);
            }
        }
        // 
        // Check if both options still available
        //
        elseif( ($festival['flags']&0x06) == 0x02 ) {
            if( $festival['live'] == 'no' && $festival['earlybird'] == 'no' ) {
                unset($fields['participation']['options'][-1]);
                unset($fields['participation']['options'][0]);
            }
            // Check if virtual only class
            if( isset($selected_class['flags']) && ($selected_class['flags']&0x20) == 0x20 ) {
                $fields['participation']['value'] = 1;
                $fields['participation']['class'] = 'hidden';
                $fields['line-participation']['class'] = 'hidden';
            }
        }
        //
        // Check if selected class should hide participation
        //
        if( ($festival['flags']&0x06) == 0x06 
            && isset($selected_class['feeflags']) && ($selected_class['feeflags']&0x0a) != 0x0a 
            ) {
            $fields['line-participation']['class'] = 'hidden';
            $fields['participation']['class'] = 'hidden';
        }
    }
    // Adjudication Plus
    elseif( ($festival['flags']&0x10) == 0x10 ) {
        $fields['line-participation'] = array(
            'id' => 'line-participation',
            'ftype' => 'line',
            );
        $fields['participation'] = array(
            'id' => 'participation',
            'label' => 'Adjudication Level',
            'onchange' => 'participationSelected()',
            'ftype' => 'select',
            'blank' => 'no',
            'required' => 'yes',
            'size' => 'large',
            'options' => array(
                '-2' => 'Please select regular or plus for your adjudication',
                '0' => 'Regular Adjudication',
                '2' => 'Adjudication Plus',
                ),
            'value' => isset($_POST['f-participation']) ? $_POST['f-participation'] : (isset($registration['participation']) ? $registration['participation'] : -2),
            );
        //
        // Setup pricing for virtual option with separate virtual pricing
        //
        $fields['participation']['options'][0] .= ': $' . number_format($selected_class['fee'], 2);
        $fields['participation']['options'][2] .= ': $' . number_format($selected_class['plus_fee'], 2);
        if( ($selected_class['feeflags']&0x22) != 0x22 ) {
            unset($fields['participation']['options'][-2]);
            $fields['line-participation']['class'] = 'hidden';
            $fields['participation']['class'] = 'hidden';
            if( ($selected_class['feeflags']&0x02) == 0 ) {
                unset($fields['participation']['options'][0]);
            }
            if( ($selected_class['feeflags']&0x20) == 0 ) {
                unset($fields['participation']['options'][2]);
            }
        }
    }

    //
    // Add performing titles
    //
    for($i = 1; $i <= 8; $i++ ) {
        $css_class = '';
        $css_class = ($i > 0 ? 'hidden' : '');
        $required = 'yes';
        if( isset($selected_class) && $i <= $selected_class['max_titles'] ) {
            $css_class = '';
        }
        if( isset($selected_class) && $i > $selected_class['min_titles'] ) {
            $required = 'no';
        }
        elseif( !isset($selected_class) && $i > 1 ) {
            $required = 'no';
            $css_class = '';
        }
        $video_class = $css_class;
        $music_class = $css_class;
        $backtrack_class = 'hidden';
        $artwork_class = 'hidden';
        if( $participation != 1 ) {
            $video_class = 'hidden';
            $music_class = (($festival['flags']&0x0200) == 0x0200 ? $css_class : 'hidden');
            $backtrack_class = 'hidden';
        }
        if( isset($selected_class['flags']) && ($selected_class['flags']&0x020000) > 0 ) {
            $video_class = 'hidden';
        }
        if( isset($selected_class['flags']) && ($selected_class['flags']&0x200000) > 0 ) {
            $music_class = 'hidden';
        }
        if( isset($selected_class['flags']) && ($selected_class['flags']&0x03000000) > 0 ) {
            $backtrack_class = $css_class;
        }
        if( isset($selected_class['titleflags']) && ($selected_class['titleflags']&0x0300) > 0 ) {
            $artwork_class = $css_class;
        }
        $movements_required = $required;

        //
        // Setup the title prefix
        //
        $prefix = '1st';
        if( $i == 2 ) {
            $prefix = '2nd';
        } elseif( $i == 3 ) {
            $prefix = '3rd';
        } elseif( $i > 3 ) {
            $prefix = $i . 'th';
        }

        $fields["line-title-{$i}"] = array(
            'id' => "line-title-{$i}",
            'ftype' => 'line',
            'class' => $css_class,
            );
        $title = 'Title';
        $fields["title{$i}"] = array(
            'id' => "title{$i}",
            'ftype' => 'text',
            'flex-basis' => '50%',
            'class' => $css_class,
            'required' => $required,
            'size' => 'small',
            'label' => "{$prefix} " . (isset($festival['registration-title-label']) && $festival['registration-title-label'] != '' ? $festival['registration-title-label'] : "Title"),
            'value' => isset($_POST["f-title{$i}"]) ? $_POST["f-title{$i}"] : (isset($registration["title{$i}"]) ? $registration["title{$i}"] : ''),
            );
        if( isset($selected_class) && ($selected_class['flags']&0x10) == 0x10 && isset($selected_class['options']["title{$i}"]) ) {
            $fields["title{$i}"]['value'] = $selected_class['options']["title{$i}"];
            $fields["title{$i}"]['editable'] = 'no';
            $fields["title{$i}"]['disabled'] = 'yes';
        } elseif( isset($_POST["f-title{$i}"]) ) {
            $fields["title{$i}"]['value'] = $_POST["f-title{$i}"];
        } elseif( isset($registration["title{$i}"]) ) {
            $fields["title{$i}"]['value'] = $registration["title{$i}"];
        }

        $fields["movements{$i}"] = array(
            'id' => "movements{$i}",
            'ftype' => 'text',
            'flex-basis' => '50%',
            'class' => $css_class,
            'required' => $required,
            'size' => 'small',
            'label' => (isset($festival['registration-movements-label']) && $festival['registration-movements-label'] != '' ? $festival['registration-movements-label'] : "Movements/Musical"),
            'error_label' => "{$prefix} " . (isset($festival['registration-movements-label']) && $festival['registration-movements-label'] != '' ? $festival['registration-movements-label'] : "Movements/Musical"),
            'value' => isset($_POST["f-movements{$i}"]) ? $_POST["f-movements{$i}"] : (isset($registration["movements{$i}"]) ? $registration["movements{$i}"] : ''),
            );
        if( !isset($selected_class) || ($selected_class['flags']&0x0C000000) == 0 ) {
            $fields["movements{$i}"]['required'] = 'no';
            $fields["movements{$i}"]['class'] = 'hidden';
        }
        elseif( $required == 'yes' && ($selected_class['flags']&0x08000000) == 0x08000000 ) {
            $fields["movements{$i}"]['required'] = 'no';
        }
        if( isset($selected_class) && ($selected_class['flags']&0x10) == 0x10 && isset($selected_class['options']["movements{$i}"]) ) {
            $fields["movements{$i}"]['value'] = $selected_class['options']["movements{$i}"];
            $fields["movements{$i}"]['editable'] = 'no';
            $fields["movements{$i}"]['disabled'] = 'yes';
        } elseif( isset($_POST["f-movements{$i}"]) ) {
            $fields["movements{$i}"]['value'] = $_POST["f-movements{$i}"];
        } elseif( isset($registration["movements{$i}"]) ) {
            $fields["movements{$i}"]['value'] = $registration["movements{$i}"];
        }

        $fields["composer{$i}"] = array(
            'id' => "composer{$i}",
            'ftype' => 'text',
            'flex-basis' => '50%',
            'class' => $css_class,
            'required' => $required,
            'size' => 'small',
            'label' => (isset($festival['registration-composer-label']) && $festival['registration-composer-label'] != '' ? $festival['registration-composer-label'] : "Composer"),
            'error_label' => "{$prefix} " . (isset($festival['registration-composer-label']) && $festival['registration-composer-label'] != '' ? $festival['registration-composer-label'] : "Composer"),
            'value' => isset($_POST["f-composer{$i}"]) ? $_POST["f-composer{$i}"] : (isset($registration["composer{$i}"]) ? $registration["composer{$i}"] : ''),
            );
        if( !isset($selected_class) || ($selected_class['flags']&0x30000000) == 0 ) {
            $fields["composer{$i}"]['required'] = 'no';
            $fields["composer{$i}"]['class'] = 'hidden';
        }
        elseif( ($selected_class['flags']&0x20000000) == 0x20000000 ) {
            $fields["composer{$i}"]['required'] = 'no';
        }
        if( isset($selected_class) && ($selected_class['flags']&0x10) == 0x10 && isset($selected_class['options']["composer{$i}"]) ) {
            $fields["composer{$i}"]['value'] = $selected_class['options']["composer{$i}"];
            $fields["composer{$i}"]['editable'] = 'no';
            $fields["composer{$i}"]['disabled'] = 'yes';
        } elseif( isset($_POST["f-composer{$i}"]) ) {
            $fields["composer{$i}"]['value'] = $_POST["f-composer{$i}"];
        } elseif( isset($registration["composer{$i}"]) ) {
            $fields["composer{$i}"]['value'] = $registration["composer{$i}"];
        }

/*            $fields["title{$i}"] = array(
                'id' => "title{$i}",
                'ftype' => 'text',
//                'flex-basis' => '28em',
                'class' => $css_class,
                'required' => $required,
                'size' => 'medium',
                'label' => (isset($festival['registration-title-label']) && $festival['registration-title-label'] != '' ? $festival['registration-title-label'] : $prefix . ' ' . $title),
                'error_label' => "{$prefix} " . (isset($festival['registration-title-label']) && $festival['registration-title-label'] != '' ? $festival['registration-title-label'] : $prefix . ' ' . $title),
                'value' => isset($_POST["f-title{$i}"]) ? $_POST["f-title{$i}"] : (isset($registration["title{$i}"]) ? $registration["title{$i}"] : ''),
                );
        } */

        if( isset($selected_class) && ($selected_class['flags']&0x10) == 0x10 && isset($selected_class['options']["perf_time{$i}"]) ) {
            $perf_time = $selected_class['options']["perf_time{$i}"];
        } elseif( isset($_POST["f-perf_time{$i}-min"]) || isset($_POST["f-perf_time{$i}-sec"]) ) {
            if( isset($_POST["f-perf_time{$i}-min"]) ) {
                $perf_time = $_POST["f-perf_time{$i}-min"]*60;
            }
            if( isset($_POST["f-perf_time{$i}-sec"]) ) {
                $perf_time += $_POST["f-perf_time{$i}-sec"];
            }
        } elseif( isset($registration["perf_time{$i}"]) ) {
            $perf_time = $registration["perf_time{$i}"];
        } else {
            $perf_time = 0;
        }
        if( $perf_time == '' ) {
            $perf_time = 0;
        }
//            $perf_time = (isset($_POST["f-perf_time{$i}-min"]) ? ($_POST["f-perf_time{$i}-min"]*60) : (isset($registration["perf_time{$i}"]) ? (intval($registration["perf_time{$i}"]/60)*60) : 0))
//                + (isset($_POST["f-perf_time{$i}-sec"]) ? $_POST["f-perf_time{$i}-sec"] : (isset($registration["perf_time{$i}"]) ? ($registration["perf_time{$i}"] % 60) :0));
//        }
        $fields["perf_time{$i}"] = array(
            'id' => "perf_time{$i}",
            'required' => $required,
            'class' => $css_class,
            'seconds' => (isset($festival['registration-length-format']) && $festival['registration-length-format'] == 'minonly' ? 'no' : 'yes'),
            'ftype' => 'minsec',
            'second-interval' => 5,
            'max-minutes' => 45,
            'flex-basis' => (isset($festival['registration-length-format']) && $festival['registration-length-format'] == 'minonly' ? '5rem' : '17rem'),
            'size' => (isset($festival['registration-length-format']) && $festival['registration-length-format'] == 'minonly' ? 'tiny' : 'small'),
            'label' => (isset($festival['registration-length-label']) && $festival['registration-length-label'] != '' ? $festival['registration-length-label'] : 'Piece Length'),
            'error_label' => "{$prefix} " . (isset($festival['registration-length-label']) && $festival['registration-length-label'] != '' ? $festival['registration-length-label'] : 'Piece Length'),
            'value' => $perf_time,
            );
        // Fixed titles have now time field
        if( isset($selected_class) && ($selected_class['flags']&0x10) == 0x10 && isset($selected_class['options']["perf_time{$i}"]) && $perf_time > 0 ) {
            $fields["perf_time{$i}"]['disabled'] = 'yes';
        }
        // Artwork has no time field
        if( isset($selected_class) && ($selected_class['titleflags']&0x0300) > 0 ) {
            $fields["perf_time{$i}"]['class'] = 'hidden';
            $fields["perf_time{$i}"]['required'] = 'no';
        }

        $fields["video_url{$i}"] = array(
            'id' => "video_url{$i}",
            'ftype' => 'text',
//            'flex-basis' => '19em',
            'required' => 'no',
            'class' => $video_class,
            'required' => 'no',
            'size' => 'medium',
            'label' => 'YouTube Video URL',
            'error_label' => $prefix . ' YouTube Video URL',
            'value' => isset($_POST["f-video_url{$i}"]) ? $_POST["f-video_url{$i}"] : (isset($registration["video_url{$i}"]) ? $registration["video_url{$i}"] : ''),
            );
        if( $participation == 1 && isset($selected_class['flags']) && ($selected_class['flags']&0x010000) > 0 
            && $selected_class['min_titles'] >= $i
            ) {
            $fields["video_url{$i}"]['required'] = 'yes';
        }
        $fields["music_orgfilename{$i}"] = array(
            'id' => "music_orgfilename{$i}",
//            'flex-basis' => '19em',
            'required' => 'no',
            'class' => $music_class,
            'ftype' => 'file',
            'size' => 'medium',
            'storage_suffix' => "music{$i}",
            'accept' => 'application/pdf',
            'label' => 'Music (PDF)',
            'error_label' => $prefix . ' Music (PDF)',
//            'value' => isset($_POST["f-music_orgfilename{$i}"]) ? $_POST["f-music_orgfilename{$i}"] : (isset($registration["music_orgfilename{$i}"]) ? $registration["music_orgfilename{$i}"] : ''),
            'value' => (isset($registration["music_orgfilename{$i}"]) ? $registration["music_orgfilename{$i}"] : ''),
            );
        if( $participation == 1 && isset($selected_class['flags']) && ($selected_class['flags']&0x100000) > 0 
            && $selected_class['min_titles'] >= $i 
            ) {
            $fields["music_orgfilename{$i}"]['required'] = 'yes';
        }
        $fields["backtrack{$i}"] = array(
            'id' => "backtrack{$i}",
//            'flex-basis' => '19em',
            'required' => 'no',
//            'required' => isset($selected_class['flags']) && ($selected_class['flags']&0x01000000) > 0 ? 'yes' : 'no',
            'class' => $backtrack_class,
            'ftype' => 'file',
            'size' => 'medium',
            'storage_suffix' => "backtrack{$i}",
            'accept' => 'audio/mpeg',
            'label' => 'Backtrack (MP3)',
            'error_label' => "{$prefix} Backtrack (MP3)",
//            'value' => isset($_POST["f-backtrack{$i}"]) ? $_POST["f-backtrack{$i}"] : (isset($registration["backtrack{$i}"]) ? $registration["backtrack{$i}"] : ''),
            'value' => (isset($registration["backtrack{$i}"]) ? $registration["backtrack{$i}"] : ''),
            );
        if( $participation == 1 && isset($selected_class['flags']) && ($selected_class['flags']&0x01000000) > 0 
            && $selected_class['min_titles'] >= $i 
            ) {
            $fields["backtrack{$i}"]['required'] = 'yes';
        }
        $fields["artwork{$i}"] = array(
            'id' => "artwork{$i}",
            'required' => 'no',
            'class' => $artwork_class,
            'ftype' => 'file',
            'size' => 'large',
            'storage_suffix' => "artwork{$i}",
//            'accept' => 'image/jpeg',
            'label' => 'Artwork (PNG/JPG/PDF)',
            'error_label' => "{$prefix} Artwork (PNG/JPG)",
            'value' => (isset($registration["artwork{$i}"]) ? $registration["artwork{$i}"] : ''),
            );
        if( isset($selected_class['titleflags']) && ($selected_class['titleflags']&0x0100) > 0 
            && $selected_class['min_titles'] >= $i 
            ) {
            $fields["artwork{$i}"]['required'] = 'yes';
        } 
    }

    $fields['line-notes'] = array(
        'ftype' => 'line',
        );

    //
    // Add notes field
    //
    $fields['notes'] = array(
        'id' => 'notes',
        'label' => 'Registration Notes',
        'ftype' => 'textarea',
        'size' => 'tiny',
        'class' => '',
        'value' => (isset($_POST['f-notes']) ? trim($_POST['f-notes']) : (isset($registration['notes']) ? $registration['notes'] :'')),
        );

    //
    // Setup the Javascript for updating the form as fields change
    //
    $js_prices = '';
    $js_set_prices = '';
    if( ($festival['flags']&0x10) == 0x10 ) {
        $js_prices .=  "var clslp=" . json_encode($live_prices) . ";" // live prices
            . "var clspp=" . json_encode($plus_prices) . ";" // plus prices
            . "";
        $js_set_prices .= ""
            . "var s=C.gE('f-participation');"
            . "var v=s.value;"
            . "s.options.length=0;"
            . "s.appendChild(new Option('Please select regular or plus for your adjudication',-1));"
            . "if(clslp[c]!=null){"
                . "s.appendChild(new Option('Regular Adjudication - '+clslp[c], 0,0,(v==0?1:0)));"
            . "}"
            . "if(clspp[c]!=null){"
                . "s.appendChild(new Option('Adjudication Plus - '+clspp[c], 2,0,(v==2?1:0)));"
            . "}"
            . "";
    }
    elseif( ($festival['flags']&0x06) == 0x06 ) {
        $js_prices .=  "var clslp=" . json_encode($live_prices) . ";" // live prices
            . "var clsvp=" . json_encode($virtual_prices) . ";" // virtual prices
            . "";
        $js_set_prices .= 'var latefee="";';
        if( isset($js_members) ) {
            $js_set_prices .= "var mid=C.gE('f-member_id').value;"
                . "if(members[mid]!=null&&members[mid]['l']!=null){"
                    . "latefee=' + $'+members[mid].l+' late fee';"
                . "}";
        }
        $js_set_prices .= ""
            . "var s=C.gE('f-participation');"
            . "var v=s.value;"
            . "s.options.length=0;"
            . "s.appendChild(new Option('Please choose how you will participate',-1));"
            . "if(clslp[c]!=null){"
                . "if(clsvp[c]==null){"
                    . "v=0;"
                . "}"
                . "s.appendChild(new Option('in person on a date to be scheduled - '+clslp[c]+latefee, 0,0,(v==0?1:0)));"
            . "}"
            . "if(clsvp[c]!=null){"
                . "if(clslp[c]==null){"
                    . "v=1;"
                . "}"
                . "s.appendChild(new Option('virtually and submit a video - '+clsvp[c]+latefee, 1,0,(v==1?1:0)));"
            . "}"
            . "var sl=C.gE('f-line-participation');"
            . "if(classes[c]!=null&&(classes[c].ff&0x0a)!=0x0a){"
                . "s.parentNode.classList.add('hidden');"
                . "sl.classList.add('hidden');"
            . "}else{"
                . "s.parentNode.classList.remove('hidden');"
                . "sl.classList.remove('hidden');"
            . "}"
            . "video=v;"
            . "music=" . (($festival['flags']&0x0200) == 0x0200 ? '1' : 'v') . ";"
            . "";
    } elseif( ($festival['flags']&0x06) == 0x02 ) {
        $js_prices .=  "var vo=" . json_encode($virtual_only) . ";"; // Virtual only classes
        $js_set_prices .= ""
            . "var s=C.gE('f-participation');"
            . "var v=s.value;"
            . "var sl=C.gE('f-line-participation');"
            . "if(vo[c]!=null){"
                . "s.parentNode.classList.add('hidden');"
                . "sl.classList.add('hidden');"
                . "s.value=1;"
            . "}else{"
                . "s.parentNode.classList.remove('hidden');"
                . "sl.classList.remove('hidden');"
            . "}"
            . ""; 
    }

    $js = ""
        . "var sids=[" . implode(',', array_keys($sections)) . "];"
        . "var classes=" . json_encode($js_classes) . ";";
        if( isset($js_members) ) {
            $js .= "var members=" . json_encode($js_members) . ";";
        }
    $js .= "var video=0;"
        . "var music=" . (($festival['flags']&0x0200) == 0x0200 ? '1' : '0') . ";"
        . $js_prices
        . "function sectionSelected(){"
            . "var s=C.gE('f-section').value;"
            . "for(var i in sids){"
                . "var e=C.gE('f-section-'+sids[i]+'-class');"
                . "if(s==sids[i]){"
                    . "C.rC(e.parentNode,'hidden');"
                    . "classSelected(sids[i]);"
                . "}else{"
                    . "C.aC(e.parentNode,'hidden');"
                . "}"
            . "}"
        . "};"
        . "function participationSelected(){"
            . "var c=C.gE('f-participation').value;"
            // Live 
            . "video=c;"
            . "music=" . (($festival['flags']&0x0200) == 0x0200 ? '1' : 'c') . ";"
            . "sectionSelected();"
        . "};"
        . "function memberSelected(){"
            . "var v=C.gE('f-member_id').value;"
            . "if(members[v]!=null&&members[v]['s']==1){"
                . "sectionSelected();"
                . "C.rC(C.gE('member_break'),'hidden');"
                . "C.rC(C.gE('addregform_submit_buttons'),'hidden');"
            . "}else{"
                . "C.aC(C.gE('member_break'),'hidden');"
                . "C.aC(C.gE('addregform_submit_buttons'),'hidden');"
            . "}"
        . "};"
        . "function classSelected(sid){"
            . "var c=C.gE('f-section-'+sid+'-class').value;"
            . $js_set_prices
            . "if(c!=null&&classes[c]!=null){"
                . "for(var i=1;i<=4;i++){"
                    . "if(i<=classes[c].mac){"        // Less than max num competitors
                        . "C.rC(C.gE('f-competitor'+i+'_id').parentNode,'hidden');"
                    . "}else{"
                        . "C.aC(C.gE('f-competitor'+i+'_id').parentNode,'hidden');"
                    . "}"
                    . "if(i<=classes[c].mic){"        // Less than min num competitors
                        . "C.aC(C.gE('f-competitor'+i+'_id').parentNode,'required');"
                    . "}else{"
                        . "C.rC(C.gE('f-competitor'+i+'_id').parentNode,'required');"
                    . "}"
                . "}"
                . "if((classes[c].f&0x8000)==0x8000){"
                    . "if(classes[c].mac>1){"
                        . "C.gE('f-competitor1_id').parentNode.firstChild.innerHTML = '1st Group/Ensemble';"
                        . "C.gE('f-competitor2_id').parentNode.firstChild.innerHTML = '2nd Group/Ensemble';"
                        . "C.gE('f-competitor3_id').parentNode.firstChild.innerHTML = '3rd Group/Ensemble';"
                        . "C.gE('f-competitor4_id').parentNode.firstChild.innerHTML = '4th Group/Ensemble';"
                    . "}else{"
                        . "C.gE('f-competitor1_id').parentNode.firstChild.innerHTML = 'Group/Ensemble';"
                    . "}"
                . "}else{"
                    . "if(classes[c].mac>1){"
                        . "C.gE('f-competitor1_id').parentNode.firstChild.innerHTML = '1st {$festival['competitor-label-singular']}';"
                        . "C.gE('f-competitor2_id').parentNode.firstChild.innerHTML = '2nd {$festival['competitor-label-singular']}';"
                        . "C.gE('f-competitor3_id').parentNode.firstChild.innerHTML = '3rd {$festival['competitor-label-singular']}';"
                        . "C.gE('f-competitor4_id').parentNode.firstChild.innerHTML = '4th {$festival['competitor-label-singular']}';"
                    . "}else{"
                        . "C.gE('f-competitor1_id').parentNode.firstChild.innerHTML = '{$festival['competitor-label-singular']}';"
                    . "}" 
                . "}" 
                . "if((classes[c].f&0x04)==0x04){"
                    . "C.rC(C.gE('f-instrument').parentNode,'hidden');"
                . "}else{"
                    . "C.aC(C.gE('f-instrument').parentNode,'hidden');"
                . "}"
                . "if((classes[c].f&0xC000)==0x4000){"
                    . "C.aC(C.gE('f-competitor1_id').lastChild,'hidden');"
                    . "C.aC(C.gE('f-competitor2_id').lastChild,'hidden');"
                    . "C.aC(C.gE('f-competitor3_id').lastChild,'hidden');"
                . "}else{"
                    . "C.rC(C.gE('f-competitor1_id').lastChild,'hidden');"
                    . "C.rC(C.gE('f-competitor2_id').lastChild,'hidden');"
                    . "C.rC(C.gE('f-competitor3_id').lastChild,'hidden');"
                . "}" 
                . "if((classes[c].f&0xC000)==0x8000){"
                    . "C.aC(C.gE('f-competitor1_id').lastChild.previousSibling,'hidden');"
                    . "C.aC(C.gE('f-competitor2_id').lastChild.previousSibling,'hidden');"
                    . "C.aC(C.gE('f-competitor3_id').lastChild.previousSibling,'hidden');"
                . "}else{"
                    . "C.rC(C.gE('f-competitor1_id').lastChild.previousSibling,'hidden');"
                    . "C.rC(C.gE('f-competitor2_id').lastChild.previousSibling,'hidden');"
                    . "C.rC(C.gE('f-competitor3_id').lastChild.previousSibling,'hidden');"
                . "}"; 
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
                $js .= "if((classes[c].f&0x3000)>0){"
                    . "C.rC(C.gE('f-accompanist_customer_id').parentNode,'hidden');"
                    . "if((classes[c].f&0x1000)>0){"
                        . "C.aC(C.gE('f-accompanist_customer_id').parentNode,'required');"
                    . "}else{"
                        . "C.rC(C.gE('f-accompanist_customer_id').parentNode,'required');"
                    . "}"
                . "}else{"
                    . "C.aC(C.gE('f-accompanist_customer_id').parentNode,'hidden');"
                . "}";
            }
            // Setup titles
            $js .= "for(var i=1;i<=8;i++){"
                    . "if(i<=classes[c].mat){"        // Less than max num titles
                        . "C.rC(C.gE('f-line-title-'+i),'hidden');"
                        . "C.rC(C.gE('f-title'+i).parentNode,'hidden');"
                        . "C.rC(C.gE('f-perf_time'+i+'-min').parentNode.parentNode,'hidden');"
                        . "if(classes[c]!=null&&(classes[c].f&0x0C000000)>0){"
                            . "C.rC(C.gE('f-movements'+i).parentNode,'hidden');"
                        . "}else{"
                            . "C.aC(C.gE('f-movements'+i).parentNode,'hidden');"
                        . "}"
                        . "if(classes[c]!=null&&(classes[c].f&0x30000000)>0){"
                            . "C.rC(C.gE('f-composer'+i).parentNode,'hidden');"
                        . "}else{"
                            . "C.aC(C.gE('f-composer'+i).parentNode,'hidden');"
                        . "}"
                        . "if(i<=classes[c].mit){"
                            . "C.aC(C.gE('f-title'+i).parentNode,'required');"
                            . "C.aC(C.gE('f-perf_time'+i+'-min').parentNode.parentNode,'required');"
                            . "if(classes[c]!=null&&(classes[c].f&0x04000000)>0){"
                                . "C.aC(C.gE('f-movements'+i).parentNode,'required');"
                            . "}else{"
                                . "C.rC(C.gE('f-movements'+i).parentNode,'required');"
                            . "}"
                            . "if(classes[c]!=null&&(classes[c].f&0x10000000)>0){"
                                . "C.aC(C.gE('f-composer'+i).parentNode,'required');"
                            . "}else{"
                                . "C.rC(C.gE('f-composer'+i).parentNode,'required');"
                            . "}"
                        . "}else{"
                            . "C.rC(C.gE('f-title'+i).parentNode,'required');"
                            . "C.rC(C.gE('f-perf_time'+i+'-min').parentNode.parentNode,'required');"
                            . "C.rC(C.gE('f-composer'+i).parentNode,'required');"
                            . "C.rC(C.gE('f-movements'+i).parentNode,'required');"
                        . "}"
                        . "if((classes[c].f&0x010000)==0x010000){"
                            . "C.aC(C.gE('f-video_url'+i).parentNode,'required');"
                        . "}else{"
                            . "C.rC(C.gE('f-video_url'+i).parentNode,'required');"
                        . "}"
                        . "if(video==1&&(classes[c]==null||(classes[c].f&0x020000)==0)){"
                            . "C.rC(C.gE('f-video_url'+i).parentNode,'hidden');"
                        . "}else{"
                            . "C.aC(C.gE('f-video_url'+i).parentNode,'hidden');"
                        . "}"
                        . "if((classes[c].f&0x100000)==0x100000){"
                            . "C.aC(C.gE('f-music_orgfilename'+i).parentNode,'required');"
                        . "}else{"
                            . "C.rC(C.gE('f-music_orgfilename'+i).parentNode,'required');"
                        . "}"
                        . "if(music==1&&(classes[c]==null||(classes[c].f&0x200000)==0)){"
                            . "C.rC(C.gE('f-music_orgfilename'+i).parentNode,'hidden');"
                        . "}else{"
                            . "C.aC(C.gE('f-music_orgfilename'+i).parentNode,'hidden');"
                        . "}"
                        . "if((classes[c].f&0x01000000)==0x01000000){"
                            . "C.aC(C.gE('f-backtrack'+i).parentNode,'required');"
                        . "}else{"
                            . "C.rC(C.gE('f-backtrack'+i).parentNode,'required');"
                        . "}"
                        . "if((classes[c].f&0x03000000)>0){"
                            . "C.rC(C.gE('f-backtrack'+i).parentNode,'hidden');"
                        . "}else{"
                            . "C.aC(C.gE('f-backtrack'+i).parentNode,'hidden');"
                        . "}"
                        . "if((classes[c].tf&0x0100)==0x0100){"
                            . "C.aC(C.gE('f-artwork'+i).parentNode,'required');"
                        . "}else{"
                            . "C.rC(C.gE('f-artwork'+i).parentNode,'required');"
                        . "}"
                        . "if((classes[c].tf&0x0300)>0){"
                            . "C.rC(C.gE('f-artwork'+i).parentNode,'hidden');"
                            . "C.aC(C.gE('f-perf_time'+i+'-min').parentNode.parentNode,'hidden');"
                        . "}else{"
                            . "C.aC(C.gE('f-artwork'+i).parentNode,'hidden');"
                            . "C.rC(C.gE('f-perf_time'+i+'-min').parentNode.parentNode,'hidden');"
                        . "}"
                    . "}else{"
                        . "C.aC(C.gE('f-line-title-'+i),'hidden');"
                        . "C.aC(C.gE('f-title'+i).parentNode,'hidden');"
                        . "C.aC(C.gE('f-perf_time'+i+'-min').parentNode.parentNode,'hidden');"
                        . "C.aC(C.gE('f-composer'+i).parentNode,'hidden');"
                        . "C.aC(C.gE('f-movements'+i).parentNode,'hidden');"
                        . "C.aC(C.gE('f-video_url'+i).parentNode,'hidden');"
                        . "C.aC(C.gE('f-music_orgfilename'+i).parentNode,'hidden');"
                        . "C.aC(C.gE('f-backtrack'+i).parentNode,'hidden');"
                        . "C.aC(C.gE('f-artwork'+i).parentNode,'hidden');"
                    . "}"
                    . "C.gE('f-title'+i).readOnly=false;"
                    . "C.gE('f-movements'+i).readOnly=false;"
                    . "C.gE('f-composer'+i).readOnly=false;"
                    . "C.gE('f-title'+i).disabled=false;"
                    . "C.gE('f-movements'+i).disabled=false;"
                    . "C.gE('f-composer'+i).disabled=false;"
                    . "C.gE('f-perf_time'+i+'-min').disabled=false;"
                    . "if(C.gE('f-perf_time'+i+'-sec')!=null){"
                        . "C.gE('f-perf_time'+i+'-sec').disabled=false;"
                    . "}"
                    . "if(classes[c]!=null&&(classes[c].f&0x10)==0x10&&classes[c].opt!=null){"
                        . "if(classes[c].opt['title'+i]!=null&&classes[c].opt['title'+i]!=''){"
                            . "C.gE('f-title'+i).value=classes[c].opt['title'+i];"
                            . "C.gE('f-title'+i).readOnly=true;"
                            . "C.gE('f-title'+i).disabled=true;"
                        . "}" 
                        . "if(classes[c].opt['movements'+i]!=null&&classes[c].opt['movements'+i]!=''){"
                            . "C.gE('f-movements'+i).value=classes[c].opt['movements'+i];"
                            . "C.gE('f-movements'+i).readOnly=true;"
                            . "C.gE('f-movements'+i).disabled=true;"
                        . "}"
                        . "if(classes[c].opt['composer'+i]!=null&&classes[c].opt['composer'+i]!=''){"
                            . "C.gE('f-composer'+i).value=classes[c].opt['composer'+i];"
                            . "C.gE('f-composer'+i).readOnly=true;"
                            . "C.gE('f-composer'+i).disabled=true;"
                        . "}"
                        . "if(classes[c].opt['perf_time'+i]!=null&&classes[c].opt['perf_time'+i]!=''){"
                            . "var n=parseInt(classes[c].opt['perf_time'+i]);"
                            . "if(n>0){"
                                . "C.gE('f-perf_time'+i+'-min').value=Math.floor(n/60);"
                                . "C.gE('f-perf_time'+i+'-min').disabled=true;"
                                . "C.gE('f-perf_time'+i+'-sec').value=Math.ceil((n%60)/5)*5;"
                                . "C.gE('f-perf_time'+i+'-sec').disabled=true;"
                            . "}"
                        . "}"
                    . "}"
                . "}" // End of processing titles
            . "}"
        . "};"
        . "function competitorSelected(c) {"
            . "var t=C.gE('f-competitor'+c+'_id').value;"
//            . "var e=C.gE('f-competitor'+c+'_edit');"
            . "if(t==-1){"
                . "C.gE('f-action').value='addcompetitor';"
                . "var f=C.gE('addregform');"
                . "f.action='{$request['ssl_domain_base_url']}/account/musicfestivalcompetitors?add=individual';"
                . "f.submit();"
            . "} "
            . "else if(t==-2){"
                . "C.gE('f-action').value='addcompetitor';"
                . "var f=C.gE('addregform');"
                . "f.action='{$request['ssl_domain_base_url']}/account/musicfestivalcompetitors?add=group';"
                . "f.submit();"
            . "}"
        . "};"
        . "function competitorEdit(c){"
            . "var t=C.gE('f-competitor'+c+'_id').value;"
            . "if(t>0){"
                . "C.gE('f-action').value='editcompetitor';"
                . "var f=C.gE('addregform');"
                . "var i=C.aE('input','f-competitor_id','hidden');"
                . "i.setAttribute('name','f-competitor_id');"
                . "i.setAttribute('value',t);"
                . "f.appendChild(i);"
                . "f.action='{$request['ssl_domain_base_url']}/account/musicfestivalcompetitors';"
                . "f.submit();"
            . "}"
        . "};"
        . "function formCancel(){"
            . "var f=C.gE('addregform');"
            . "C.gE('f-action').value='cancel';"
            . "f.submit();"
        . "};"
        . "function formSubmit(){"
            . "var f=C.gE('addregform');"
            . "C.gE('f-action').value='update';"
            . "f.submit();"
        . "};"
        . "function teacherSelected(){"
            . "var t=C.gE('f-teacher_customer_id').value;"
            . "if(t==-1){"
                . "C.rC(C.gE('f-teacher_name').parentNode,'hidden');"
                . "C.rC(C.gE('f-teacher_phone').parentNode,'hidden');"
                . "C.rC(C.gE('f-teacher_email').parentNode,'hidden');"
            . "}else{"
                . "C.aC(C.gE('f-teacher_name').parentNode,'hidden');"
                . "C.aC(C.gE('f-teacher_phone').parentNode,'hidden');"
                . "C.aC(C.gE('f-teacher_email').parentNode,'hidden');"
            . "}"
        . "}; "
        . "function accompanistSelected(){"
            . "var t=C.gE('f-accompanist_customer_id').value;"
            . "if(t==-1){"
                . "C.rC(C.gE('f-accompanist_name').parentNode,'hidden');"
                . "C.rC(C.gE('f-accompanist_phone').parentNode,'hidden');"
                . "C.rC(C.gE('f-accompanist_email').parentNode,'hidden');"
            . "}else{"
                . "C.aC(C.gE('f-accompanist_name').parentNode,'hidden');"
                . "C.aC(C.gE('f-accompanist_phone').parentNode,'hidden');"
                . "C.aC(C.gE('f-accompanist_email').parentNode,'hidden');"
            . "}"
        . "}; ";

    $rsp = array('stat'=>'ok', 'fields'=>$fields, 'js'=>$js, 'sections'=>$sections);
    if( isset($selected_section) ) {
        $rsp['selected_section'] = $selected_section;
    }
    if( isset($selected_class) ) {
        $rsp['selected_class'] = $selected_class;
    }
    if( isset($selected_member) ) {
        $rsp['selected_member'] = $selected_member;
    }
    return $rsp;
}
?>
