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
    // Make sure competitors where passed in arguments
    //
    if( !isset($args['teachers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.345', 'msg'=>"No teachers specified"));
    }
    $teachers = $args['teachers'];

    //
    // Make sure competitors where passed in arguments
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
    $strsql = "SELECT s.id AS section_id, "
        . "s.name AS section_name, "
        . "s.live_end_dt, "
        . "s.virtual_end_dt, "
        . "s.edit_end_dt, "
        . "s.upload_end_dt, "
        . "ca.name AS category_name, "
        . "cl.id AS class_id, "
        . "cl.uuid AS class_uuid, "
        . "cl.code AS class_code, "
        . "cl.name AS class_name, "
        . "CONCAT_WS(' - ', s.name, cl.code, cl.name) AS sectionclassname, "
        . "cl.flags AS class_flags, "
        . "cl.earlybird_fee, "
        . "cl.fee, "
        . "cl.virtual_fee "
        . "FROM ciniki_musicfestival_sections AS s "
        . "INNER JOIN ciniki_musicfestival_categories AS ca ON ("
            . "s.id = ca.section_id "
            . "AND ca.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS cl ON ("
            . "ca.id = cl.category_id "
            . "AND cl.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE s.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND s.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (s.flags&0x01) = 0 "
        . "ORDER BY s.sequence, s.name, ca.sequence, ca.name, cl.sequence, cl.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'live_end_dt', 'virtual_end_dt', 'edit_end_dt', 'upload_end_dt'),
            ),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'uuid'=>'class_uuid', 'category_name', 'code'=>'class_code', 
                'name'=>'class_name', 'sectionclassname', 'flags'=>'class_flags', 'earlybird_fee', 'fee', 'vfee' => 'virtual_fee'),
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
    $classes_2t = array();  // Class id's with 2 title & times
    $classes_2to = array();  // Class id's with 2 title & times
    $classes_3t = array();  // Class id's with 3 title & times
    $classes_3to = array();  // Class id's with 3 title & times
    $live_prices = array();
    $virtual_prices = array();
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
            if( $section['edit_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_edit_dt = new DateTime($section['edit_end_dt'], new DateTimezone('UTC'));
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
        $sections[$sid]['edit'] = $section_edit;
        $sections[$sid]['upload'] = $section_upload;
        if( isset($section['classes']) ) {
            foreach($section['classes'] as $cid => $section_class) {
                if( ($section_class['flags']&0x10) == 0x10 ) {
                    $classes_2c[] = $cid;
                }
                if( ($section_class['flags']&0x20) == 0x20 ) {
                    $classes_3c[] = $cid;
                }
                if( ($section_class['flags']&0x1000) == 0x1000 ) {
                    $classes_2t[] = $cid;
                }
                if( ($section_class['flags']&0x2000) == 0x2000 ) {
                    $classes_2to[] = $cid;
                }
                if( ($section_class['flags']&0x4000) == 0x4000 ) {
                    $classes_3t[] = $cid;
                }
                if( ($section_class['flags']&0x8000) == 0x8000 ) {
                    $classes_3to[] = $cid;
                }
                if( $section_class['code'] != '' ) {
                    $sections[$sid]['classes'][$cid]['codename'] = $section_class['code'] . ' - ' . $section_class['name'];
                }
                if( isset($_GET['cl']) && $_GET['cl'] == $section_class['uuid'] ) {
                    $selected_sid = $sid;
                    $selected_cid = $cid;
                }
                //
                // Check for valid sections and options when not in view mode for the form.
                //
                if( $args['display'] != 'view' ) {
                    //
                    // Virtual option(0x02) and virtual pricing(0x04) set for festival 
                    //
                    if( ($festival['flags']&0x06) == 0x06 ) {
                        if( $festival['earlybird'] == 'yes' && $section_live == 'yes' && $section_class['earlybird_fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['earlybird_fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['earlybird_fee'];
                        } elseif( $festival['live'] == 'yes' && $section_live == 'yes' && $section_class['fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                        } elseif( $festival['live'] == 'sections' && $section_live == 'yes' && $section_class['fee'] > 0 ) {
                            $live_prices[$cid] = '$' . number_format($section_class['fee'], 2);
                            $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                        }
                        if( $festival['virtual'] == 'yes' && $section_virtual == 'yes' && $section_class['vfee'] > 0 ) {
                            $virtual_prices[$cid] = '$' . number_format($section_class['vfee'], 2);
                            $sections[$sid]['classes'][$cid]['virtual_fee'] = $section_class['vfee'];
                        } elseif( $festival['virtual'] == 'sections' && $section_virtual == 'yes' && $section_class['vfee'] > 0 ) {
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
    if( isset($_POST['f-section']) && $_POST['f-section'] > 0 ) {
        $selected_section = $sections[$_POST['f-section']];
        if( isset($_POST["f-section-{$_POST['f-section']}-class"]) ) {
            $selected_class = $sections[$_POST['f-section']]['classes'][$_POST["f-section-{$_POST['f-section']}-class"]];
            $comp_required = 1;
            $titles_required = 1;
            if( ($selected_class['flags']&0x10) == 0x10 ) {
                $comp_required = 2;
            }
            if( ($selected_class['flags']&0x20) == 0x20 ) {
                $comp_required = 3;
            }
            if( ($selected_class['flags']&0x1000) == 0x1000 ) {
                $titles_required = 2;
            }
            if( ($selected_class['flags']&0x4000) == 0x4000 ) {
                $titles_required = 3;
            }
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
        'section' => array(
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
            ),
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

    //
    // Add child information 
    //
    for($i = 1; $i <= 3; $i++) {
        $class = ($i > 1 ? 'hidden' : '');
        if( isset($selected_class) && $i == 2 && (($selected_class['flags']&0x10) == 0x10 || ($selected_class['flags']&0x20) == 0x20) ) {
            $class = '';
        }
        elseif( isset($selected_class) && $i == 3 && (($selected_class['flags']&0x20) == 0x20) ) {
            $class = '';
        }
/*        if( $i > 1 ) {
            $fields["competitor{$i}_newline"] = array(
                'id' => "competitor{$i}_newline",
                'ftype' => 'newline',
                );
        } */
        $comp_id = isset($_POST["f-competitor{$i}_id"]) && $_POST["f-competitor{$i}_id"] > -1 ? $_POST["f-competitor{$i}_id"] : (isset($registration["competitor{$i}_id"]) ? $registration["competitor{$i}_id"] : 0);
        $fields["competitor{$i}_id"] = array(
            'id' => "competitor{$i}_id",
            'ftype' => 'select',
            'size' => 'large',
            'class' => $class,
            'required' => ($i < 3 ? 'yes' : 'no'),
            'label' => "Competitor {$i}",
            'onchange' => "competitorSelected({$i})",
            'options' => $competitors,
            'value' => $comp_id,
            );
        $fields["competitor{$i}_id"]['options']['add'] = array(
            'id' => '-1',
            'name' => 'Add Competitor',
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
            'value' => 'Edit Competitor',
            'href' => "javascript: competitorEdit({$i});",
            ); */
    }
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
    }

    $fields['line-a'] = array(
        'ftype' => 'line',
        );

    //
    // Check if virtual performance option is available
    //
    $participation = isset($registration['participation']) ? $registration['participation'] : -1;
    if( isset($_POST['f-participation']) && $_POST['f-participation'] == 0 ) {
        $participation = 0;
    }
    if( ($festival['flags']&0x02) == 0x02 ) {
        $fields['participation'] = array(
            'id' => 'participation',
            'label' => 'I would like to participate',
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
                && isset($selected_class['live_fee']) && $selected_class['live_fee'] > 0 
                ) {
                $fields['participation']['options'][0] .= ' - $' . number_format($selected_class['live_fee'], 2);
            } else {
                unset($fields['participation']['options'][-1]);
                unset($fields['participation']['options'][0]);
                $participation = 1;
            }
            if( isset($festival['virtual']) && $festival['virtual'] != 'no' 
                && isset($selected_class['virtual_fee']) && $selected_class['virtual_fee'] > 0 
                ) {
                $fields['participation']['options'][1] .= ' - $' . number_format($selected_class['virtual_fee'], 2);
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
        }
    }

    $fields['line-b'] = array(
        'ftype' => 'line',
        );

    //
    // Add performing titles
    //
    for($i = 1; $i <= 3; $i++ ) {
        $class = ($i > 1 ? 'hidden' : '');
        $required = 'yes';
        if( isset($selected_class) && $i == 2 && (($selected_class['flags']&0x1000) == 0x1000 || ($selected_class['flags']&0x4000) == 0x4000) ) {
            $class = '';
        }
        elseif( isset($selected_class) && $i == 3 && (($selected_class['flags']&0x4000) == 0x4000) ) {
            $class = '';
        }
        if( isset($selected_class) && $i == 2 && ($selected_class['flags']&0x2000) == 0x2000 ) {
            $required = 'no';
        }
        if( isset($selected_class) && $i == 3 && ($selected_class['flags']&0x8000) == 0x8000 ) {
            $required = 'no';
        }
        $video_class = $class;
        $music_class = $class;
        if( $participation < 1 ) {
            $video_class = 'hidden';
            $music_class = 'hidden';
        }

        $title = 'Title & Composer e.g. Prelude op.39, no.19 (D. Kabalevsky)';
        if( $i == 2 ) {
            $title = '2nd Title & Composer';
        } elseif( $i == 3 ) {
            $title = '3rd Title & Composer';
        }
        $fields["title{$i}"] = array(
            'id' => "title{$i}",
            'ftype' => 'text',
            'flex-basis' => '28em',
            'class' => $class,
            'required' => $required,
            'size' => 'medium',
            'label' => $title,
            'value' => isset($_POST["f-title{$i}"]) ? $_POST["f-title{$i}"] : (isset($registration["title{$i}"]) ? $registration["title{$i}"] : ''),
            );
        $fields["perf_time{$i}"] = array(
            'id' => "perf_time{$i}",
            'flex-basis' => '8em',
            'required' => $required,
            'class' => $class,
            'ftype' => 'text',
            'size' => 'tiny',
            'label' => 'Time',
            'value' => isset($_POST["f-perf_time{$i}"]) ? $_POST["f-perf_time{$i}"] : (isset($registration["perf_time{$i}"]) ? $registration["perf_time{$i}"] : ''),
            );
        $fields["video_url{$i}"] = array(
            'id' => "video_url{$i}",
            'ftype' => 'text',
            'flex-basis' => '19em',
            'class' => $video_class,
            'required' => 'no',
            'size' => 'medium',
            'label' => 'Video URL',
            'value' => isset($_POST["f-video_url{$i}"]) ? $_POST["f-video_url{$i}"] : (isset($registration["video_url{$i}"]) ? $registration["video_url{$i}"] : ''),
            );
        $fields["music_orgfilename{$i}"] = array(
            'id' => "music_orgfilename{$i}",
            'flex-basis' => '19em',
            'required' => 'no',
            'class' => $music_class,
            'ftype' => 'file',
            'size' => 'medium',
            'storage_suffix' => "music{$i}",
            'accept' => 'application/pdf',
            'label' => 'Music (PDF)',
            'value' => isset($_POST["f-music_orgfilename{$i}"]) ? $_POST["f-music_orgfilename{$i}"] : (isset($registration["music_orgfilename{$i}"]) ? $registration["music_orgfilename{$i}"] : ''),
            );
    }

    $fields['line-c'] = array(
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
    if( ($festival['flags']&0x06) == 0x06 ) {
        $js_prices .=  "var clslp=" . json_encode($live_prices) . ";" // live prices
            . "var clsvp=" . json_encode($virtual_prices) . ";" // virtual prices
            . "";
        $js_set_prices .= ""
            . "var s=C.gE('f-participation');"
            . "var v=s.value;"
            . "s.options.length=0;"
            . "s.appendChild(new Option('Please choose how you will participate',-1));"
            . "if(clslp[c]!=null){"
                . "s.appendChild(new Option('in person on a date to be scheduled - '+clslp[c], 0,0,(v==0?1:0)));"
            . "}"
            . "if(clsvp[c]!=null){"
                . "s.appendChild(new Option('virtually and submit a video - '+clsvp[c], 1,0,(v==1?1:0)));"
            . "}"
            . "";
    }

    $js = ""
        . "var sids=[" . implode(',', array_keys($sections)) . "];"
        . "var cls2c=[" . implode(',', $classes_2c) . "];" // 2 competitor classes (duets)
        . "var cls3c=[" . implode(',', $classes_3c) . "];" // 3 competitor classes (trios)
        . "var cls2t=[" . implode(',', $classes_2t) . "];" // 2 title & times
        . "var cls2to=[" . implode(',', $classes_2to) . "];" // 2 title & times
        . "var cls3t=[" . implode(',', $classes_3t) . "];" // 3 title & times
        . "var cls3to=[" . implode(',', $classes_3to) . "];" // 3 title & times
        . "var video=1;"
        . "var music=1;"
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
            . "music=c;"
            . "console.log(c);"
            . "sectionSelected();"
        . "};"
        . "function classSelected(sid){"
            . "console.log('selected: ' + video);"
            . "var c=C.gE('f-section-'+sid+'-class').value;"
            . "if(video==1){"
                . "C.rC(C.gE('f-video_url1').parentNode,'hidden');"
            . "}else{"
                . "C.aC(C.gE('f-video_url1').parentNode,'hidden');"
            . "}"
            . "if(music==1){"
                . "C.rC(C.gE('f-music_orgfilename1').parentNode,'hidden');"
            . "}else{"
                . "C.aC(C.gE('f-music_orgfilename1').parentNode,'hidden');"
            . "}"
            . "if(c!=null){"
                . "if(cls3c.indexOf(parseInt(c))>=0){"
                    . "C.rC(C.gE('f-competitor2_id').parentNode,'hidden');"
                    . "C.rC(C.gE('f-competitor3_id').parentNode,'hidden');"
                . "}else if(cls2c.indexOf(parseInt(c))>=0){"
                    . "C.rC(C.gE('f-competitor2_id').parentNode,'hidden');"
                    . "C.aC(C.gE('f-competitor3_id').parentNode,'hidden');"
                . "}else{"
                    . "C.aC(C.gE('f-competitor2_id').parentNode,'hidden');"
                    . "C.aC(C.gE('f-competitor3_id').parentNode,'hidden');"
                . "}"
                . "if(cls3t.indexOf(parseInt(c))>=0){"
                    . "C.rC(C.gE('f-title2').parentNode,'hidden');"
                    . "C.rC(C.gE('f-perf_time2').parentNode,'hidden');"
                    . "if(video==1){"
                        . "C.rC(C.gE('f-video_url2').parentNode,'hidden');"
                    . "}else{"
                        . "C.aC(C.gE('f-video_url2').parentNode,'hidden');"
                    . "}"
                    . "if(music==1){"
                        . "C.rC(C.gE('f-music_orgfilename2').parentNode,'hidden');"
                    . "}else{"
                        . "C.aC(C.gE('f-music_orgfilename2').parentNode,'hidden');"
                    . "}"
                    . "C.rC(C.gE('f-title3').parentNode,'hidden');"
                    . "C.rC(C.gE('f-perf_time3').parentNode,'hidden');"
                    . "if(video==1){"
                        . "C.rC(C.gE('f-video_url3').parentNode,'hidden');"
                    . "}else{"
                        . "C.aC(C.gE('f-video_url3').parentNode,'hidden');"
                    . "}"
                    . "if(music==1){"
                        . "C.rC(C.gE('f-music_orgfilename3').parentNode,'hidden');"
                    . "}else{"
                        . "C.aC(C.gE('f-music_orgfilename3').parentNode,'hidden');"
                    . "}"
                . "}else if(cls2t.indexOf(parseInt(c))>=0){"
                    . "C.rC(C.gE('f-title2').parentNode,'hidden');"
                    . "C.rC(C.gE('f-perf_time2').parentNode,'hidden');"
                    . "if(video==1){"
                        . "C.rC(C.gE('f-video_url2').parentNode,'hidden');"
                    . "}else{"
                        . "C.aC(C.gE('f-video_url2').parentNode,'hidden');"
                    . "}"
                    . "if(music==1){"
                        . "C.rC(C.gE('f-music_orgfilename2').parentNode,'hidden');"
                    . "}else{"
                        . "C.aC(C.gE('f-music_orgfilename2').parentNode,'hidden');"
                    . "}"
                    . "C.aC(C.gE('f-title3').parentNode,'hidden');"
                    . "C.aC(C.gE('f-perf_time3').parentNode,'hidden');"
                    . "C.aC(C.gE('f-video_url3').parentNode,'hidden');"
                    . "C.aC(C.gE('f-music_orgfilename3').parentNode,'hidden');"
                . "}else{"
                    . "C.aC(C.gE('f-title2').parentNode,'hidden');"
                    . "C.aC(C.gE('f-perf_time2').parentNode,'hidden');"
                    . "C.aC(C.gE('f-video_url2').parentNode,'hidden');"
                    . "C.aC(C.gE('f-music_orgfilename2').parentNode,'hidden');"
                    . "C.aC(C.gE('f-title3').parentNode,'hidden');"
                    . "C.aC(C.gE('f-perf_time3').parentNode,'hidden');"
                    . "C.aC(C.gE('f-video_url3').parentNode,'hidden');"
                    . "C.aC(C.gE('f-music_orgfilename3').parentNode,'hidden');"
                . "}"
                . "if(cls2to.indexOf(parseInt(c))>=0){"
                    . "C.rC(C.gE('f-title2').parentNode.children[0],'required');"
                    . "C.rC(C.gE('f-perf_time2').parentNode.children[0],'required');"
                . "}else{"
                    . "C.aC(C.gE('f-title2').parentNode.children[0],'required');"
                    . "C.aC(C.gE('f-perf_time2').parentNode.children[0],'required');"
                . "}"
                . "if(cls3to.indexOf(parseInt(c))>=0){"
                    . "C.rC(C.gE('f-title3').parentNode.children[0],'required');"
                    . "C.rC(C.gE('f-perf_time3').parentNode.children[0],'required');"
                . "}else{"
                    . "C.aC(C.gE('f-title3').parentNode.children[0],'required');"
                    . "C.aC(C.gE('f-perf_time3').parentNode.children[0],'required');"
                . "}"
                . $js_set_prices
            . "}"
        . "};"
        . "function competitorSelected(c) {"
            . "var t=C.gE('f-competitor'+c+'_id').value;"
//            . "var e=C.gE('f-competitor'+c+'_edit');"
            . "if(t==-1){"
                . "C.gE('f-action').value='addcompetitor';"
                . "var f=C.gE('addregform');"
                . "f.action='{$request['ssl_domain_base_url']}/account/musicfestivalcompetitors?add=yes';"
                . "f.submit();"
//            . "}else if(t>0){"
//                . "C.rC(e.parentNode,'hidden');"
//            . "}else{"
//                . "C.aC(e.parentNode,'hidden');"
            . "}"
        . "};"
        . "function competitorEdit(c){"
            . "var t=C.gE('f-competitor'+c+'_id').value;"
            . "if(t>0){"
                . "C.gE('f-action').value='editcompetitor';"
                . "var f=C.gE('addregform');"
                . "var i=C.aE('input','f-competitor_id','hidden');"
                . "console.log(t);"
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
        . "}; ";

    $rsp = array('stat'=>'ok', 'fields'=>$fields, 'js'=>$js, 'sections'=>$sections);
    if( isset($selected_section) ) {
        $rsp['selected_section'] = $selected_section;
    }
    if( isset($selected_class) ) {
        $rsp['selected_class'] = $selected_class;
    }
    return $rsp;
}
?>
