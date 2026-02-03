<?php
//
// Description
// -----------
// This function will process the updates from a registration form submitted online
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_registrationFormUpdateProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1369', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Check for required fields
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1370', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    if( !isset($args['registration_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1371', 'msg'=>"No registration specified"));
    }
    $registration_id = $args['registration_id'];

    if( !isset($args['display']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1372', 'msg'=>"Internal Error"));
    }
    $display = $args['display'];

    if( isset($args['selected_class']) && $args['selected_class'] != null ) {
        $selected_class = $args['selected_class'];
    }

    if( !isset($args['fields']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1375', 'msg'=>"Internal Error"));
    }
    $fields = $args['fields'];

    if( isset($args['registration']) ) {
        $registration = $args['registration'];
    }

    //
    // Make sure customer type is passed
    //
    if( !isset($args['customer_type']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1373', 'msg'=>"No customer type specified"));
    }
    $customer_type = $args['customer_type'];

    //
    // Make sure customer specified
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == '' || $args['customer_id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1374', 'msg'=>"No customer specified"));
    }

    $errors = [];

    //
    // Check if not a duplicate entry for same class
    //
    if( isset($selected_class['flags']) && ($selected_class['flags']&0x02) == 0 ) {
        $competitor_ids = array();
        for( $i = 1; $i <= 5; $i++) {
            if( isset($_POST["f-competitor{$i}_id"]) 
                && is_numeric($_POST["f-competitor{$i}_id"]) 
                && $_POST["f-competitor{$i}_id"] > 0 
                ) {
                $competitor_ids[] = $_POST["f-competitor{$i}_id"];
            } elseif( isset($registration["competitor{$i}_id"]) 
                && is_numeric($registration["competitor{$i}_id"]) 
                && $registration["competitor{$i}_id"] > 0 
                ) {
                $competitor_ids[] = $registration["competitor{$i}_id"];
            }
        }
        if( count($competitor_ids) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $strsql = "SELECT COUNT(*) AS num_registrations "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND class_id = '" . ciniki_core_dbQuote($ciniki, $selected_class['id']) . "' "
                . "AND ("
                    . "registrations.competitor1_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
                    . "OR registrations.competitor2_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
                    . "OR registrations.competitor3_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
                    . "OR registrations.competitor4_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
                    . "OR registrations.competitor5_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
                    . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            if( isset($registration['registration_id']) && $registration['registration_id'] > 0 ) {
                $strsql .= "AND registrations.id <> '" . ciniki_core_dbQuote($ciniki, $registration['registration_id']) . "' ";
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.329', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $num_items = isset($rc['num']) ? $rc['num'] : '';
            if( $num_items > 0 ) {  
                $errors[] = array(
                    'msg' => 'You have already registered for this class.',
                    );
            } 
        }
    }

    //
    // Check if member is still open
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        if( !isset($args['selected_member']) ) {
            $errors[] = array(
                'msg' => "You must specify the recommending local festival.",
                );
        }
        elseif( (!isset($args['selected_member']['open']) || $args['selected_member']['open'] != 'yes')
            && $_POST['f-action'] != 'viewupdate'
            ) {
            $errors[] = array(
                'msg' => "Registrations are closed for " . $args['selected_member']['oname'] . ".",
                );
        }
    }
    if( !isset($selected_class) ) {
        $errors[] = array(
            'msg' => "Internal error, please contact us for help.",
            );
    }
   
    if( count($errors) == 0 ) {
        foreach($fields as $field) {
            if( $field['ftype'] == 'line'
                || ($field['id'] == 'parent' && $customer_type == 30)
//                    || ($selected_class['max_competitors'] < 4 && $field['id'] == 'competitor4_id')
//                    || ($selected_class['max_competitors'] < 3 && $field['id'] == 'competitor3_id')
//                    || ($selected_class['max_competitors'] < 2 && $field['id'] == 'competitor2_id')
//                    || (($selected_class['flags']&0x40) == 0 && $field['id'] == 'competitor4_id')
//                    || (($selected_class['flags']&0x20) == 0 && $field['id'] == 'competitor3_id')
//                    || (($selected_class['flags']&0x10) == 0 && $field['id'] == 'competitor2_id')
                || (($selected_class['flags']&0x04) == 0 && $field['id'] == 'instrument')
                || ($customer_type != 20 && $fields['teacher_customer_id'] != -1 && $field['id'] == 'teacher_email')
                ) {
                continue;
            }
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) 
                && $fields['accompanist_customer_id'] != -1 
                && $field['id'] == 'accompanist_email'
                ) {
                continue;
            }
            // 
            // For provincials, do not check when viewing a registration if movements is required
            //
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
                && isset($field['required']) && $field['required'] == 'yes' && $display == 'view' 
                && preg_match("/(composer|movements)/", $field['id'])
                && $festival['edit'] == 'no' 
                ) {
                $field['required'] = 'no';
            }
            if( isset($field['required']) && $field['required'] == 'yes' && $field['value'] < 0 && $field['id'] == 'participation' ) {
                $errors[] = array(
                    'msg' => 'You must specify how you want to participate.',
                    );
            }
            elseif( ($selected_class['flags']&0x400000) == 0x400000 && preg_match("/backtrack([0-9])/", $field['id'], $m) ) {
                if( $fields["backtrack_option{$m[1]}"]['value'] == 'on' 
                    && $field['value'] == '' 
                    && (!isset($_FILES["file-{$field['id']}"]['name']) || $_FILES["file-{$field['id']}"]['name'] == '') 
                    ) {
                    $errors[] = array(
                        'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                        );
                }
            }
            elseif( isset($field['required']) && $field['required'] == 'yes' && $field['ftype'] == 'file' ) {
                if( $field['value'] == '' && (!isset($_FILES["file-{$field['id']}"]['name']) || $_FILES["file-{$field['id']}"]['name'] == '') ) {
                    $errors[] = array(
                        'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                        );
                }
            }
            elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == 0 && $field['ftype'] == 'minsec' 
                ) {
                $errors[] = array(
                    'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                    );
            }
            elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == 0 && strncmp($field['id'], 'competitor', 10) == 0 ) {
                $errors[] = array(
                    'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                    );
            }
            elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == 0 && $field['ftype'] == 'minsec' ) {
                $errors[] = array(
                    'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                    );

            }
            elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == '' && $field['id'] != 'termstitle' ) {
                $errors[] = array(
                    'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                    );
            }
        }


        //
        // Check if teacher needs to be setup
        //
        if( $customer_type == 20 ) {
            $registration['teacher_customer_id'] = $request['session']['customer']['id'];
        }
        elseif( count($errors) == 0 && $fields['teacher_customer_id']['value'] == -1 ) {
            if( $fields['teacher_email']['value'] == '' ) {
                $errors[] = array(
                    'msg' => "You must specify your teacher's email.",
                    );
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'teacherCreate');
                $rc = ciniki_musicfestivals_wng_teacherCreate($ciniki, $tnid, $request, array());
                if( $rc['stat'] == 'ok' ) {
                    $teacher_added = 'yes';
                    $fields['teacher_customer_id']['value'] = $rc['teacher_customer_id'];
                }
                elseif( $rc['stat'] == '404' ) {
                    return $rc;
                }
                elseif( $rc['stat'] == 'error' ) {
                    $errors[] = array(
                        'msg' => $rc['err']['msg'],
                        );
                } else {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.370', 'msg'=>'Unable to create teacher', 'err'=>$rc['err']));
                }
            }
        }

        //
        // Check if accompanist needs to be setup
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) 
            && $fields['accompanist_customer_id']['value'] == -1 
            && count($errors) == 0 
            ) {
            if( $fields['accompanist_email']['value'] == '' ) {
                $errors[] = array(
                    'msg' => "You must specify your accompanist's email.",
                    );
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accompanistCreate');
                $rc = ciniki_musicfestivals_wng_accompanistCreate($ciniki, $tnid, $request, array());
                if( $rc['stat'] == 'ok' ) {
                    $accompanist_added = 'yes';
                    $fields['accompanist_customer_id']['value'] = $rc['accompanist_customer_id'];
                }
                elseif( $rc['stat'] == '404' ) {
                    return $rc;
                }
                elseif( $rc['stat'] == 'error' ) {
                    $errors[] = array(
                        'msg' => $rc['err']['msg'],
                        );
                } else {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.647', 'msg'=>'Unable to create accompanist', 'err'=>$rc['err']));
                }
            }
        }

        //
        // If there are errors, reset the teacher and accompanist fields if they were added
        //
        if( count($errors) > 0 && isset($teacher_added) && $teacher_added == 'yes' ) {
            $fields['teacher_customer_id']['value'] = -1;
            $fields['teacher_first']['class'] = '';
            $fields['teacher_last']['class'] = '';
            $fields['teacher_email']['class'] = '';
            $fields['teacher_phone']['class'] = '';
        }
        if( count($errors) > 0 && isset($accompanist_added) && $accompanist_added == 'yes' ) {
            $fields['accompanist_customer_id']['value'] = -1;
            $fields['accompanist_first']['class'] = '';
            $fields['accompanist_last']['class'] = '';
            $fields['accompanist_email']['class'] = '';
            $fields['accompanist_phone']['class'] = '';
        }
    }

    //
    // Check the cart still exists
    //
    if( count($errors) == 0 && isset($request['session']['cart']['sapos_id']) && $request['session']['cart']['sapos_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
        $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
        if( $rc['stat'] != 'ok' ) {
            //
            // Unable to load cart, create a new cart
            //
            error_log("WNG: Cart does not exist, creating new one");
            $request['session']['cart']['sapos_id'] = 0;
        }
    }
    //
    // If the cart doesn't exist, create one now
    //
    if( count($errors) == 0 
        && (!isset($request['session']['cart']['sapos_id']) || $request['session']['cart']['sapos_id'] == 0)
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCreate');
        $rc = ciniki_sapos_wng_cartCreate($ciniki, $tnid, $request, array());
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.310', 'msg'=>'Error opening cart', 'err'=>$rc['err']));
        }
    }


    //
    // If no errors, add/update the registration
    //
    if( $fields['registration_id']['value'] == 0 && count($errors) == 0 ) {
        //
        // Create the registration
        //
        $registration = array(
            'festival_id' => $festival['id'],
            'billing_customer_id' => $request['session']['customer']['id'],
            'teacher_customer_id' => ($customer_type == 20 ? $request['session']['customer']['id'] : $fields['teacher_customer_id']['value']),
            'accompanist_customer_id' => isset($fields['accompanist_customer_id']['value']) ? $fields['accompanist_customer_id']['value'] : 0,
            'member_id' => isset($fields['member_id']['value']) ? $fields['member_id']['value'] : 0,
            'rtype' => 30,
            'status' => 5,
            'flags' => 0,
            'invoice_id' => $request['session']['cart']['id'],
            'display_name' => '',
            'public_name' => '',
            'competitor1_id' => $fields['competitor1_id']['value'],
            'competitor2_id' => $fields['competitor2_id']['value'],
            'competitor3_id' => $fields['competitor3_id']['value'],
            'competitor4_id' => $fields['competitor4_id']['value'],
            'competitor5_id' => $fields['competitor5_id']['value'],
            'class_id' => $selected_class['id'],
            'timeslot_id' => 0,
            'instrument' => isset($fields['instrument']['value']) ? $fields['instrument']['value'] : '',
            'participation' => (isset($fields['participation']['value']) ? $fields['participation']['value'] : ''),
            'notes' => isset($fields['notes']['value']) ? $fields['notes']['value'] : '',
            );
        if( isset($args['selected_member']['id']) && $args['selected_member']['id'] > 0 ) {
            $registration['member_id'] = $args['selected_member']['id'];
        }
        for($i = 1; $i <= 8; $i++) {
            $registration["title{$i}"] = isset($fields["title{$i}"]['value']) ? $fields["title{$i}"]['value'] : '';
            $registration["composer{$i}"] = isset($fields["composer{$i}"]['value']) ? $fields["composer{$i}"]['value'] : '';
            $registration["movements{$i}"] = isset($fields["movements{$i}"]['value']) ? $fields["movements{$i}"]['value'] : '';
            $registration["perf_time{$i}"] = isset($fields["perf_time{$i}"]['value']) ? $fields["perf_time{$i}"]['value'] : '';
            $registration["video_url{$i}"] = isset($fields["video_url{$i}"]['value']) ? $fields["video_url{$i}"]['value'] : '';
        }
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
            $registration['accompanist_customer_id'] = $fields['accompanist_customer_id']['value'];
        }
//            if( isset($fields['teacher_share']['value']) && $fields['teacher_share']['value'] == 'on' ) {
//                $registration['flags'] |= 0x01;
//            }
//            if( isset($fields['accompanist_share']['value']) && $fields['accompanist_share']['value'] == 'on' ) {
//                $registration['flags'] |= 0x02;
//            }
        if( ($selected_class['flags']&0x20) == 0x20 ) {
            $registration['rtype'] = 60;
        } elseif( ($selected_class['flags']&0x10) == 0x10 ) {
            $registration['rtype'] = 50;
        }
        // Virtual pricing
        if( $festival['earlybird'] == 'yes' 
            && ($selected_class['feeflags']&0x01) == 0x01 
            && $selected_class['earlybird_fee'] > 0 
            ) {
            $registration['fee'] = $selected_class['earlybird_fee'];
        } else {
            $registration['fee'] = $selected_class['fee'];
        }
        if( ($festival['flags']&0x10) == 0x10 && $fields['participation']['value'] == 2 
            && ($selected_class['feeflags']&0x20) == 0x20     
            && $selected_class['plus_fee'] > 0 
            ) {
            $registration['fee'] = $selected_class['plus_fee'];
        }
        elseif( ($festival['flags']&0x04) == 0x04 && $fields['participation']['value'] == 1 
            && $selected_class['virtual_fee'] > 0 
            ) {
            $registration['fee'] = $selected_class['virtual_fee'];
        }

        // Setup the description for the invoice 
        $description = $selected_class['name'];
        if( $registration['participation'] == 1 ) {
            $description .= ' (Virtual)';
        } elseif( $registration['participation'] == 0 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
            $description .= ' (Live)';
        } elseif( $registration['participation'] == 2) {
            $description .= ' (Adjudication Plus)';
        }

        //
        // Get the UUID so it can be used for adding files
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.musicfestivals');
        if( $rc['stat'] != 'ok' ) {
            error_log('unable to get uuid');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.454', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $registration['uuid'] = $rc['uuid'];

        //
        // Check for music uploads
        // FIXME: Combine next 3 foreach into single foreach
        //
        foreach(['music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3', 'music_orgfilename4', 'music_orgfilename5', 'music_orgfilename6', 'music_orgfilename7', 'music_orgfilename8'] as $fid) {
            $field = $fields[$fid];
            if( isset($_POST["f-{$field['id']}"]) && $_POST["f-{$field['id']}"] != '' ) {
                if( isset($_FILES["file-{$field['id']}"]["name"]) 
                    && isset($_FILES["file-{$field['id']}"]["tmp_name"]) 
                    && file_exists($_FILES["file-{$field['id']}"]['tmp_name'])
                    ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
                    $rc = ciniki_core_storageFileAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', array(
                        'uuid' => $registration['uuid'] . '_' . $field['storage_suffix'],
                        'subdir' => 'files',
                        'binary_content' => file_get_contents($_FILES["file-{$field['id']}"]['tmp_name']),
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        error_log('unable to store file');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.715', 'msg'=>'Unable to store uploaded file', 'err'=>$rc['err']));
                    }
//                        $registration[$field['id']] = $field['value'];
                    $registration[$field['id']] = $_FILES["file-{$field['id']}"]['name'];
                }
            }
        }

        //
        // Check for backtrack uploads
        //
        for($i = 1; $i <= 8; $i++) {
            $fid = 'backtrack' . $i;
            $field = $fields[$fid];
            if( isset($_POST["f-{$field['id']}"]) && $_POST["f-{$field['id']}"] != '' ) {
                if( isset($_FILES["file-{$field['id']}"]["name"]) 
                    && isset($_FILES["file-{$field['id']}"]["tmp_name"]) 
                    && file_exists($_FILES["file-{$field['id']}"]['tmp_name'])
                    ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
                    $rc = ciniki_core_storageFileAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', array(
                        'uuid' => $registration['uuid'] . '_' . $field['storage_suffix'],
                        'subdir' => 'files',
                        'binary_content' => file_get_contents($_FILES["file-{$field['id']}"]['tmp_name']),
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        error_log('unable to store file');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.429', 'msg'=>'Unable to store uploaded file', 'err'=>$rc['err']));
                    }
//                        $registration[$field['id']] = $field['value'];
                    $registration[$field['id']] = $_FILES["file-{$field['id']}"]['name'];
                }
            }
            if( ($selected_class['flags']&0x400000) == 0x400000 
                && isset($fields["backtrack_option{$i}"]['value']) 
                && $fields["backtrack_option{$i}"]['value'] == 'on'
                ) {
                $registration['flags'] |= pow(2, ($i+7));
            }
        }
        //
        // Check for artwork uploads
        //
        foreach(['artwork1', 'artwork2', 'artwork3', 'artwork4', 'artwork5', 'artwork6', 'artwork7', 'artwork8'] as $fid) {
            $field = $fields[$fid];
            if( isset($_POST["f-{$field['id']}"]) && $_POST["f-{$field['id']}"] != '' ) {
                if( isset($_FILES["file-{$field['id']}"]["name"]) 
                    && isset($_FILES["file-{$field['id']}"]["tmp_name"]) 
                    && file_exists($_FILES["file-{$field['id']}"]['tmp_name'])
                    ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
                    $rc = ciniki_core_storageFileAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', array(
                        'uuid' => $registration['uuid'] . '_' . $field['storage_suffix'],
                        'subdir' => 'files',
                        'binary_content' => file_get_contents($_FILES["file-{$field['id']}"]['tmp_name']),
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        error_log('unable to store file');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.885', 'msg'=>'Unable to store uploaded file', 'err'=>$rc['err']));
                    }
//                        $registration[$field['id']] = $field['value'];
                    $registration[$field['id']] = $_FILES["file-{$field['id']}"]['name'];
                }
            }
        }
        //
        // Add the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.371', 'msg'=>'Unable to add the competitor', 'err'=>$rc['err']));
        }
        $registration_id = $rc['id'];

        //
        // Update the names
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
        $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $tnid, [
            'festival' => $festival,
            'registration_id' => $registration_id,
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.416', 'msg'=>'Unable to updated registration name', 'err'=>$rc['err']));
        }
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
            $registration['display_name'] = $rc['pn_display_name'];
            $registration['public_name'] = $rc['pn_public_name'];
            $registration['private_name'] = $rc['pn_private_name'];
        } else {
            $registration['display_name'] = $rc['display_name'];
            $registration['public_name'] = $rc['public_name'];
            $registration['private_name'] = $rc['private_name'];
        }

        //
        // Generate notes field for invoice
        //
        $notes = $registration['display_name'];
        $titles = '';
        for($i = 1; $i <= 8; $i++) {
            if( $registration["title{$i}"] != '' ) {
                $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
                if( isset($rc['title']) ) {
                    $registration["title{$i}"] = $rc['title'];
                }
                if( $titles != '' && $i > 1 ) {
                    if( strncmp($titles, '1', 1) != 0 ) {
                        $titles = "1. " . $titles . "\n{$i}. ";
                    } else {
                        $titles .= "\n{$i}. ";
                    }
                }
                $titles .= $registration["title{$i}"];
            }
        } 
        if( $titles != '' ) {
            $notes .= "\n" . $titles;
        }

        //
        // Add to the cart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemAdd');
        $rc = ciniki_sapos_wng_cartItemAdd($ciniki, $tnid, $request, array(
            'object' => 'ciniki.musicfestivals.registration',
            'object_id' => $registration_id,
            'price_id' => 0,
            'quantity' => 1,
            'flags' => 0x08,
            'code' => $selected_class['code'],
            'description' => $description,
            'unit_amount' => $registration['fee'],
            'unit_discount_amount' => 0,
            'unit_discount_percentage' => 0,
            'taxtype_id' => 0,
            'notes' => $notes,
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.309', 'msg'=>'Unable to add to cart', 'err'=>$rc['err']));
        }

        //
        // Make sure member late fee added if applicable
        //
        if( isset($args['selected_member']['latefee']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceMemberLateFeeUpdate');
            $rc = ciniki_musicfestivals_invoiceMemberLateFeeUpdate($ciniki, $tnid, $request['session']['cart']['id'], $args['selected_member']['latefee']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        //
        // Reload cart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
        $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.332', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
        }
       
        if( isset($request['session']['account-musicfestivals-registration-return-url']) ) {
            header("Location: {$request['session']['account-musicfestivals-registration-return-url']}");
            unset($request['session']['account-musicfestivals-registration-return-url']);
            return array('stat'=>'exit');
        }

        header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/registrations");
        return array('stat'=>'exit');
    }
    elseif( count($errors) == 0 ) {
        //
        // Check for updates to selected class
        //
        if( ($selected_class['flags']&0x20) == 0x20 ) {
            $registration['rtype'] = 60;
        } elseif( ($selected_class['flags']&0x10) == 0x10 ) {
            $registration['rtype'] = 50;
        } else {
            $registration['rtype'] = 30;
        }
        // Virtual pricing
        if( $display != 'view' ) {
            if( isset($selected_class['earlybird_fee']) && $festival['earlybird'] == 'yes' 
                && ($selected_class['feeflags']&0x01) == 0x01 
                && $selected_class['earlybird_fee'] > 0 
                ) {
                $new_fee = $selected_class['earlybird_fee'];
            } else {
                $new_fee = $selected_class['fee'];
            }
            if( ($festival['flags']&0x10) == 0x10 && $fields['participation']['value'] == 2 
                && ($selected_class['feeflags']&0x20) == 0x20 
                && $selected_class['plus_fee'] > 0 
                ) {
                $new_fee = $selected_class['plus_fee'];
            } 
            elseif( ($festival['flags']&0x04) == 0x04 
                && $fields['participation']['value'] == 1 
                && isset($selected_class['virtual_fee'])
                && $selected_class['virtual_fee'] > 0 
                ) {
                $new_fee = $selected_class['virtual_fee'];
            }
            elseif( ($festival['flags']&0x04) == 0x04 
                && $fields['participation']['value'] == 1 
                && isset($selected_class['vfee'])
                && $selected_class['vfee'] > 0 
                ) {
                $new_fee = $selected_class['vfee'];
            }
        }

        $update_args = array();
        $registration_flags = $registration['flags'];
        foreach($fields as $field) {
            if( $field['ftype'] == 'content' || $field['ftype'] == 'hidden' || $field['ftype'] == 'line' 
                || strncmp($field['id'], 'section', 7) == 0 
                || $field['id'] == 'teacher_first'
                || $field['id'] == 'teacher_last'
                || $field['id'] == 'teacher_email'
                || $field['id'] == 'teacher_phone'
                || $field['id'] == 'accompanist_first'
                || $field['id'] == 'accompanist_last'
                || $field['id'] == 'accompanist_email'
                || $field['id'] == 'accompanist_phone'
                || $field['ftype'] == 'button'
                || $field['ftype'] == 'newline'
                ) {
                continue;
            }
            if( strncmp($field['id'], 'section', 7) == 0 ) {
                continue;
            }
            if( $field['id'] == 'accompanist_customer_id'
                && (!isset($registration[$field['id']]) || $field['value'] != $registration[$field['id']])
                && isset($festival['edit-accompanist']) && $festival['edit-accompanist'] == 'yes'
                ) {
                $update_args[$field['id']] = $field['value'];
            }
            //
            // Skip fields when editing a pending or paid registration
            //
            if( isset($registration['status']) && $registration['status'] > 10 
                && !preg_match("/(title|composer|movements|perf_time|video_url|music_orgfilename|backtrack_option|backtrack|artwork)/", $field['id'])
                ) {
                continue;
            }

            if( $field['ftype'] == 'file' ) {
                if( isset($_POST["f-{$field['id']}"]) && $_POST["f-{$field['id']}"] != '' ) {
                    if( isset($_FILES["file-{$field['id']}"]["name"]) 
                        && isset($_FILES["file-{$field['id']}"]["tmp_name"]) 
                        && $_FILES["file-{$field['id']}"]["tmp_name"] != '' 
                        ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
                        $rc = ciniki_core_storageFileAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', array(
                            'uuid' => $registration['uuid'] . '_' . $field['storage_suffix'],
                            'subdir' => 'files',
                            'binary_content' => file_get_contents($_FILES["file-{$field['id']}"]['tmp_name']),
                            ));
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.428', 'msg'=>'Unable to store uploaded file', 'err'=>$rc['err']));
                        }
                        //$update_args[$field['id']] = $field['value'];
                        $update_args[$field['id']] = $_FILES["file-{$field['id']}"]['name'];
                    }
                }
            }
            elseif( $field['ftype'] == 'break' ) {
                // Do nothing
            }
            elseif( preg_match("/backtrack_option([0-9])/", $field['id'], $m) && ($selected_class['flags']&0x400000) == 0x400000 ) {
                $bit = pow(2, ($m[1]+7));
                if( $field['value'] == 'on' ) {
                    $registration_flags |= $bit;
                } else {
                    $registration_flags &= ~$bit;
                }
            }
            elseif( !isset($registration[$field['id']]) || $field['value'] != $registration[$field['id']] ) {
                $update_args[$field['id']] = $field['value'];
            } 
        }
        if( !isset($registration['status']) || $registration['status'] < 10 ) {
            if( $selected_class['id'] != $registration['class_id'] ) {
                $update_args['class_id'] = $selected_class['id'];
            }
            if( isset($new_fee) && $new_fee != $registration['fee'] ) {
                $update_args['fee'] = $new_fee;
                $registration['fee'] = $new_fee;
            }
        }
        if( $registration_flags != $registration['flags'] ) {
            $update_args['flags'] = $registration_flags;
            $registration['flags'] = $registration['flags'];
        }
        if( isset($update_args['participation']) ) {
            $registration['participation'] = $update_args['participation'];
        }

        //
        // Make sure the member late fee has been applied to the cart
        //
        if( isset($args['selected_member']['latefee']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceMemberLateFeeUpdate');
            $rc = ciniki_musicfestivals_invoiceMemberLateFeeUpdate($ciniki, $tnid, $registration['invoice_id'], $args['selected_member']['latefee']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        //
        // Update the registration
        //
        if( count($update_args) > 0 ) {
            //
            // Check if changes should be stored as a change request
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration_id, $update_args, 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.372', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
            }

            //
            // Check if any names need changing
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
            $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $tnid, [
                'festival' => $festival,
                'registration_id' => $registration_id,
                ]);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.311', 'msg'=>'Unable to updated registration name', 'err'=>$rc['err']));
            }
            //
            // Update registration values as they are needed when we update the cart below
            //
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                $registration['display_name'] = $rc['pn_display_name'];
                $registration['public_name'] = $rc['pn_public_name'];
                $registration['private_name'] = $rc['pn_private_name'];
            } else {
                $registration['display_name'] = $rc['display_name'];
                $registration['public_name'] = $rc['public_name'];
                $registration['private_name'] = $rc['private_name'];
            }
            for($i = 1; $i <= 8; $i++) {
                if( isset($update_args["title{$i}"]) ) {
                    $registration["title{$i}"] = $update_args["title{$i}"];
                }
                if( isset($update_args["composer{$i}"]) ) {
                    $registration["composer{$i}"] = $update_args["composer{$i}"];
                }
                if( isset($update_args["movements{$i}"]) ) {
                    $registration["movements{$i}"] = $update_args["movements{$i}"];
                }
            }

            //
            // Load the cart item
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
            $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $tnid, $registration['invoice_id'], 'ciniki.musicfestivals.registration', $registration_id);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.541', 'msg'=>'Unable to get invoice item', 'err'=>$rc['err']));
            }
            $item = $rc['item'];

            //
            // Check if anything changed in the cart
            //
            $update_item_args = array();
            $notes = $registration['display_name'];
            $titles = '';
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $registration, ['basicnumbers'=>'yes']);
            if( isset($rc['titles']) ) {
                $titles = $rc['titles'];
            } 
            if( $titles != '' ) {
                $notes .= "\n" . $titles;
            }

            if( $item['code'] != $selected_class['code'] ) {
                $update_item_args['code'] = $selected_class['code'];
            }
            $description = $selected_class['name'];
            if( $registration['participation'] == 1 ) {
                $description .= ' (Virtual)';
            } elseif( $registration['participation'] == 0 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
                $description .= ' (Live)';
            } elseif( $registration['participation'] == 2) {
                $description .= ' (Adjudication Plus)';
            }
            if( $item['description'] != $description ) {
                $update_item_args['description'] = $description;
            }
            if( $item['unit_amount'] != $registration['fee'] ) {
                $update_item_args['unit_amount'] = $registration['fee'];
            }
            if( $item['notes'] != $notes ) {
                $update_item_args['notes'] = $notes;
            }
            if( count($update_item_args) > 0 ) {
                $update_item_args['item_id'] = $item['id'];
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemUpdate');
                $rc = ciniki_sapos_wng_cartItemUpdate($ciniki, $tnid, $request, $update_item_args);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }

        if( isset($request['session']['account-musicfestivals-registration-return-url']) ) {
            header("Location: {$request['session']['account-musicfestivals-registration-return-url']}");
            unset($request['session']['account-musicfestivals-registration-return-url']);
            return array('stat'=>'exit');
        }

        header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/registrations");
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok', 'errors'=>$errors);
}
?>
