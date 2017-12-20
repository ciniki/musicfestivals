<?php
//
// Description
// -----------
// This function will process the registrations page for online music festival registrations.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get music festival request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_musicfestivals_web_processRequestRegistrations(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.121', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Check there is a festival setup
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.122', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // This function does not build a page, just provides an array of blocks
    //
    $blocks = array();

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
    // Check to make sure the customer is logged in, otherwise redirect to login page
    //
    if( !isset($ciniki['session']['customer']['id']) ) {
        $blocks[] = array(
            'type' => 'login', 
            'section' => 'login',
            'register' => 'yes',
            'redirect' => $args['base_url'],        // Redirect back to registrations page
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Check if customer is setup for the music festival this year
    //
    $strsql = "SELECT id, ctype "
        . "FROM ciniki_musicfestival_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.123', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    $customer_type = 0;
    if( !isset($rc['customer']['ctype']) || $rc['customer']['ctype'] == 0 ) {
        //
        // Check if customer type was submitted
        //
        if( isset($_GET['ctype']) && in_array($_GET['ctype'], array(10,20,30)) ) {
            //
            // Add the customer to the musicfestival
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.customer', array(
                'festival_id' => $args['festival_id'],
                'customer_id' => $ciniki['session']['customer']['id'],
                'ctype' => $_GET['ctype'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.124', 'msg'=>'Unable to add the customer', 'err'=>$rc['err']));
            }
            $customer_type = $_GET['ctype'];
        } 
        
        //
        // Ask the customer what type they are
        //
        else {
            $blocks[] = array('type'=>'content', 'content'=>'In order to better serve you, we need to know who you are.');
            $blocks[] = array('type'=>'decisionbuttons',
                'buttons'=>array(
                    array('label' => 'I am a Parent registering my Children',
                        'url' => $args['base_url'] . "?ctype=10",
                        ),
                    array('label' => 'I am a Teacher registering my Students',
                        'url' => $args['base_url'] . "?ctype=20",
                        ),
                    array('label' => 'I am an Adult registering Myself',
                        'url' => $args['base_url'] . "?ctype=30",
                        ),
                    ));
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
    } else {
        $customer_type = $rc['customer']['ctype'];
    }

    //
    // Setup language based on customer type
    //
    $s_competitor = '';
    $p_competitor = '';
    if( $customer_type == 10 ) {
        $s_competitor = 'Child';
        $p_competitor = 'Children';
    } elseif( $customer_type == 20 ) {
        $s_competitor = 'Student';
        $p_competitor = 'Students';
    }

    //
    // Load the customers registrations
    //
    $strsql = "SELECT r.id, r.uuid, "
        . "r.teacher_customer_id, r.billing_customer_id, r.rtype, r.status, r.status AS status_text, "
        . "r.display_name, r.public_name, "
        . "r.competitor1_id, r.competitor2_id, r.competitor3_id, r.competitor4_id, r.competitor5_id, "
        . "r.class_id, r.timeslot_id, r.title, r.perf_time, r.fee, r.payment_type, r.notes, "
        . "c.code AS class_code, c.name AS class_name, c.flags AS class_flags "
        . "FROM ciniki_musicfestival_registrations AS r "
        . "LEFT JOIN ciniki_musicfestival_classes AS c ON ("
            . "r.class_id = c.id "
            . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE r.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND r.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "AND r.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY r.status, r.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'status_text',
                'display_name', 'public_name', 'competitor1_id', 'competitor2_id', 'competitor3_id', 
                'competitor4_id', 'competitor5_id', 'class_id', 'timeslot_id', 'title', 'perf_time', 
                'fee', 'payment_type', 'notes',
                'class_code', 'class_name', 'class_flags'),
            'maps'=>array('status_text'=>$maps['registration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.126', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
    foreach($registrations as $rid => $reg) {
        $registrations[$rid]['fee_display'] = '$' . number_format($reg['fee'], 2);
        $registrations[$rid]['edit-url'] = $args['base_url'] . '?r=' . $reg['uuid'];
    }

    //
    // Load the competitors
    //
    $strsql = "SELECT c.id, c.uuid, "
        . "c.name, c.parent, c.address, c.city, c.province, c.postal, "
        . "c.phone_home, c.phone_cell, c.email, c.age, c.study_level, c.instrument, c.notes "
        . "FROM ciniki_musicfestival_competitors AS c "
        . "WHERE c.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND c.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'name', 'parent', 'address', 'city', 'province', 'postal', 
                'phone_home', 'phone_cell', 'email', 'age', 'study_level', 'instrument', 'notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.125', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Load the classes
    //
    $strsql = "SELECT s.id AS section_id, "
        . "s.name AS section_name, "
        . "ca.name AS category_name, "
        . "cl.id AS class_id, "
        . "cl.code AS class_code, "
        . "cl.name AS class_name, "
        . "cl.flags AS class_flags, "
        . "cl.fee AS class_fee "
        . "FROM ciniki_musicfestival_sections AS s "
        . "LEFT JOIN ciniki_musicfestival_categories AS ca ON ("
            . "s.id = ca.section_id "
            . "AND ca.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS cl ON ("
            . "ca.id = cl.category_id "
            . "AND cl.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE s.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND s.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY s.sequence, s.name, ca.sequence, ca.name, cl.sequence, cl.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
        array('container'=>'classes', 'fname'=>'class_id', 'fields'=>array('id'=>'class_id', 
            'category_name'=>'category_name', 'code'=>'class_code', 'name'=>'class_name', 'flags'=>'class_flags', 'fee'=>'class_fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.80', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    $class = null;
    if( isset($_POST['section']) && $_POST['section'] > 0 && isset($_POST["section-{$_POST['section']}-class"]) ) {
        $class = $sections[$_POST['section']]['classes'][$_POST["section-{$_POST['section']}-class"]];
    } elseif( isset($_GET['cl']) && $_GET['cl'] > 0 ) {
        foreach($sections as $sid => $section) {
            if( isset($section['classes'][$_GET['cl']]) ) {
                $class = $section['classes'][$_GET['cl']];
            }
        }
    }

    //
    // Decide what should be displayed
    //
    $display = 'registration-list';
    if( isset($_GET['r']) && $_GET['r'] != '' ) {
        $display = 'registration-form';
        $registration_id = 0;
        $err_msg = '';
        //
        // Check for an update
        //
        if( $_GET['r'] != 'new' ) {
            foreach($registrations as $registration) {
                if( $registration['uuid'] == $_GET['r'] ) {
                    $registration_id = $registration['id'];

                    //
                    // Check if delete registration
                    //
                    if( isset($_POST['delete']) && $_POST['delete'] == "Delete" ) {
                        $blocks[] = array('type'=>'formmessage', 'level' => 'error',
                            'message' => 'Are you sure you want to delete this registration? Please click the Confirm button below.',
                            );
                        break;
                    }
                    elseif( isset($_POST['delete']) && $_POST['delete'] == "Confirm" ) {
                        if( $registration['status'] != 5 ) {
                            $blocks[] = array('type'=>'formmessage', 'level' => 'error',
                                'message' => 'This registration cannot be deleted online. Please contact us for help.',
                                );
                            unset($_POST['delete']);
                        } else {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration_id, $registration['uuid'], 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.81', 'msg'=>"I'm sorry, we were unable to remove that registration. You will need to contact us to have it removed.", 'err'=>$rc['err']));
                            }
                            header('Location: ' . $args['base_url']);
                            exit;
                        }
                        break;
                    }

                    //
                    // Check for updates
                    //
                    $update_args = array();
                    if( isset($class) && $class != null ) {
                        if( $class['id'] != $registration['class_id'] ) {
                            $update_args['class_id'] = $class['id'];
                        }
                        
                        //
                        // Check if for new competitors
                        //
                        for($i = 1; $i <= 3; $i++) {
                            $field = "competitor{$i}_id";
                            //
                            // Check if competitor2 or 3 is allowed, if not should be set to zero in the database.
                            //
                            if( ($i == 2 && ($class['flags']&0x10) == 0) || ($i == 3 && ($class['flags']&0x20) == 0) ) {
                                if( $registration[$field] != 0 ) {
                                    $update_args[$field] = 0;
                                }
                                //
                                // Skip to next field, don't do any more processing
                                continue;
                            }
                            if( isset($_POST[$field]) ) {
                                if( $_POST[$field] == 'new' ) {
                                    if( !isset($_POST["name{$i}"]) || $_POST["name{$i}"] == '' ) {
                                        $err_msg = 'You must specified a name for the new ' . $s_competitor;
                                        break;
                                    }
                                    //
                                    // Add new competitor
                                    //
                                    $new_competitor = array(
                                        'festival_id' => $args['festival_id'],
                                        'billing_customer_id' => $ciniki['session']['customer']['id'],
                                        'name' => $_POST["name{$i}"],
                                        'parent' => (isset($_POST["parent{$i}"]) ? $_POST["parent{$i}"] : ''),
                                        'address' => (isset($_POST["address{$i}"]) ? $_POST["address{$i}"] : ''),
                                        'city' => (isset($_POST["city{$i}"]) ? $_POST["city{$i}"] : ''),
                                        'province' => (isset($_POST["province{$i}"]) ? $_POST["province{$i}"] : ''),
                                        'postal' => (isset($_POST["postal{$i}"]) ? $_POST["postal{$i}"] : ''),
                                        'phone_home' => (isset($_POST["phone_home{$i}"]) ? $_POST["phone_home{$i}"] : ''),
                                        'phone_cell' => (isset($_POST["phone_cell{$i}"]) ? $_POST["phone_cell{$i}"] : ''),
                                        'email' => (isset($_POST["email{$i}"]) ? $_POST["email{$i}"] : ''),
                                        'age' => (isset($_POST["age{$i}"]) ? $_POST["age{$i}"] : ''),
                                        'study_level' => (isset($_POST["study_level{$i}"]) ? $_POST["study_level{$i}"] : ''),
                                        'instrument' => (isset($_POST["instrument{$i}"]) ? $_POST["instrument{$i}"] : ''),
                                        'notes' => (isset($_POST["notes{$i}"]) ? $_POST["notes{$i}"] : ''),
                                        );
                                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $new_competitor, 0x04);
                                    if( $rc['stat'] != 'ok' ) {   
                                        $err_msg = "Unable to add new {$s_competitor}. Please try again, or contact us for help.";
                                        error_log(print_r($rc['err'], true));
                                    } else {
                                        $update_args[$field] = $rc['id'];
                                        $competitors[$rc['id']] = $new_competitor;
                                    }
                                } elseif( $_POST[$field] != $registration[$field] ) {
                                    $update_args[$field] = $_POST[$field];
                                }
                            }
                        }
                    }
                    foreach(['title', 'perf_time'] as $field) {
                        if( isset($_POST[$field]) && $_POST[$field] != $registration[$field] ) {
                            $update_args[$field] = $_POST[$field];
                        }
                    }
                    if( $err_msg == '' && count($update_args) > 0 ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration',
                            $registration_id, $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $err_msg = "We had an error saving the registration. Please try again, or contact us for help.";
                        } else {
                            //
                            // Update the display_name for the registration
                            //
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
                            $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $tnid, $registration_id);
                            if( $rc['stat'] != 'ok' ) {
                                error_log('Unable to update registration name');
                            }
                            if( !isset($_POST['ceditid']) || $_POST['ceditid'] == '' ) {
                                header('Location: ' . $args['base_url']);
                                exit;
                            }
                        }
                    } elseif( isset($_POST['save']) ) {
                        if( !isset($_POST['ceditid']) || $_POST['ceditid'] == '' ) {
                            header('Location: ' . $args['base_url']);
                            exit;
                        }
                    }
                }
            }
        } 
        //
        // Check if a new registration has been submitted
        //
        elseif( isset($_POST['section']) && isset($_POST['title']) ) {
            //
            // Check required fields were submitted
            //
            error_log($_POST['competitor1_id']);
            if( $class == null ) {
                $err_msg = "You must pick a class to register for.";
            } elseif( !isset($_POST['competitor1_id']) || $_POST['competitor1_id'] == '' || $_POST['competitor1_id'] == '0' ) {
                $err_msg = "You must choose a {$s_competitor} or add a new {$s_competitor}.";
            } elseif( isset($_POST['competitor1_id']) && $_POST['competitor1_id'] == 'new'
                && (!isset($_POST['name1']) || $_POST['name1'] == '' )
                ) {
                $err_msg = "You must enter a competitors name";
            }
            //
            // Check for new competitors
            //
            $registration = array(
                'festival_id' => $args['festival_id'],
                'teacher_customer_id' => ($customer_type == 20 ? $ciniki['session']['customer']['id'] : 0),
                'billing_customer_id' => $ciniki['session']['customer']['id'],
                'rtype' => 30,
                'status' => 5,
                'invoice_id' => 0,
                'display_name' => '',
                'public_name' => '',
                'competitor1_id' => (isset($_POST['competitor1_id']) ? $_POST['competitor1_id'] : 0),
                'competitor2_id' => (isset($_POST['competitor2_id']) ? $_POST['competitor2_id'] : 0),
                'competitor3_id' => (isset($_POST['competitor3_id']) ? $_POST['competitor3_id'] : 0),
                'competitor4_id' => (isset($_POST['competitor4_id']) ? $_POST['competitor4_id'] : 0),
                'competitor5_id' => (isset($_POST['competitor5_id']) ? $_POST['competitor5_id'] : 0),
                'class_id' => $class['id'],
                'timeslot_id' => 0,
                'title' => (isset($_POST['title']) ? $_POST['title'] : ''),
                'perf_time' => (isset($_POST['perf_time']) ? $_POST['perf_time'] : ''),
                'fee' => $class['fee'],
                'payment_type' => 0,
                'notes' => (isset($_POST['notes']) ? $_POST['notes'] : ''),
                );
            if( ($class['flags']&0x20) == 0x20 ) {
                $registration['rtype'] = 60;
            } elseif( ($class['flags']&0x10) == 0x10 ) {
                $registration['rtype'] = 50;
            }

            if( $err_msg == '' ) {
                for($i = 1; $i <= 3; $i++) {
                    $field = "competitor{$i}_id";
                    //
                    // Skip competitor2, 3 if not required for class
                    //
                    if( ($i == 2 && ($class['flags']&0x10) == 0) || ($i == 3 && ($class['flags']&0x20) == 0) ) {
                        continue;
                    }
                    if( isset($_POST[$field]) ) {
                        if( $_POST[$field] == 'new' ) {
                            if( !isset($_POST["name{$i}"]) || $_POST["name{$i}"] == '' ) {
                                $err_msg = 'You must specified a name for the new ' . $s_competitor;
                                break;
                            }
                            //
                            // Add new competitor
                            //
                            $new_competitor = array(
                                'festival_id' => $args['festival_id'],
                                'billing_customer_id' => $ciniki['session']['customer']['id'],
                                'name' => $_POST["name{$i}"],
                                'parent' => (isset($_POST["parent{$i}"]) ? $_POST["parent{$i}"] : ''),
                                'address' => (isset($_POST["address{$i}"]) ? $_POST["address{$i}"] : ''),
                                'city' => (isset($_POST["city{$i}"]) ? $_POST["city{$i}"] : ''),
                                'province' => (isset($_POST["province{$i}"]) ? $_POST["province{$i}"] : ''),
                                'postal' => (isset($_POST["postal{$i}"]) ? $_POST["postal{$i}"] : ''),
                                'phone_home' => (isset($_POST["phone_home{$i}"]) ? $_POST["phone_home{$i}"] : ''),
                                'phone_cell' => (isset($_POST["phone_cell{$i}"]) ? $_POST["phone_cell{$i}"] : ''),
                                'email' => (isset($_POST["email{$i}"]) ? $_POST["email{$i}"] : ''),
                                'age' => (isset($_POST["age{$i}"]) ? $_POST["age{$i}"] : ''),
                                'study_level' => (isset($_POST["study_level{$i}"]) ? $_POST["study_level{$i}"] : ''),
                                'instrument' => (isset($_POST["instrument{$i}"]) ? $_POST["instrument{$i}"] : ''),
                                'notes' => (isset($_POST["notes{$i}"]) ? $_POST["notes{$i}"] : ''),
                                );
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $new_competitor, 0x04);
                            if( $rc['stat'] != 'ok' ) {   
                                $err_msg = "Unable to add new {$s_competitor}. Please try again, or contact us for help.";
                                error_log(print_r($rc['err'], true));
                            } else {
                                $registration[$field] = $rc['id'];
                            }
                        }
                    }
                }
            }
            //
            // Add the registration
            //
            if( $err_msg == '' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration, 0x04);
                if( $rc['stat'] != 'ok' ) {   
                    $err_msg = "I'm sorry, we had a problem saving your registration. Please try again or contact us for help.";
                    error_log(print_r($rc['err'], true));
                } else {
                    //
                    // Update the display_name for the registration
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
                    $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $tnid, $rc['id']);
                    if( $rc['stat'] != 'ok' ) {
                        error_log('Unable to update registration name');
                    }

                    //
                    // The registration was added, now redirect to the registration list
                    //
                    header("Location: " . $args['base_url']);
                    exit;
                }
            }
        }

        //
        // Check if error produced
        //
        if( $err_msg != '' ) {
            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$err_msg);
        }
    }
/*    if( isset($_GET['c']) && $_GET['c'] != '' ) {
        $display = 'competitor-form';
        $competitor_id = 0;
        if( $_GET['c'] != 'new' ) {
            foreach($competitors as $competitor) {
                if( $competitor['uuid'] == $_GET['c'] ) {
                    $competitor_id = $competitor['id'];
                }
            }
        }
    } */
    //
    // Check if a competitor edit has been requested. The registration form will have been saved above
    // so any changes to title, perf_time will be saved before editing the competitor details.
    //
    if( isset($_POST['ceditid']) && $_POST['ceditid'] != '' ) {
        $display = 'competitor-form';
        $competitor_id = 0;
        if( $_POST['ceditid'] != 'new' ) {
            foreach($competitors as $competitor) {
                if( $competitor['uuid'] == $_POST['ceditid'] ) {
                    $competitor_id = $competitor['id'];

                    //
                    // Check for updates
                    //
                    $update_args = array();
                    foreach(['name', 'parent', 'address', 'city', 'postal', 'phone_home', 'phone_cell', 'email', 'age', 'study_level', 'instrument', 'notes'] as $field) {
                        if( isset($_POST[$field]) && $_POST[$field] != $competitor[$field] ) {
                            $update_args[$field] = $_POST[$field];
                        }
                    }
                    if( count($update_args) > 0 ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.competitor',
                            $competitor_id, $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $err_msg = "We had an error saving the changes. Please try again, or contact us for help.";
                        } else {
                            //
                            // Update the display_name for the registration
                            //
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'competitorUpdateNames');
                            $rc = ciniki_musicfestivals_competitorUpdateNames($ciniki, $tnid, $args['festival_id'], $competitor_id);
                            if( $rc['stat'] != 'ok' ) {
                                error_log('Unable to update competitor name');
                            }
                            if( isset($_POST['r']) && $_POST['r'] != '' ) {
                                header('Location: ' . $args['base_url'] . '?r=' . $_POST['r']);
                            } else {
                                header('Location: ' . $args['base_url']);
                            }
                            exit;
                        }
                    } elseif( isset($_POST['save']) ) {
                        if( isset($_POST['r']) && $_POST['r'] != '' ) {
                            header('Location: ' . $args['base_url'] . '?r=' . $_POST['r']);
                        } else {
                            header('Location: ' . $args['base_url']);
                        }
                        exit;
                    }
                }
            }
        }
    }
/*    if( isset($_POST['competitor1_id']) && $_POST['competitor1_id'] == 'new' ) {
        $display = 'competitor-form';
        $competitor_id = 0;
        print_r($_POST);
    } */

    //
    // Display the list of registrations
    //
    if( $display == 'registration-list' ) {
        $blocks[] = array('type'=>'foldingtable',
            'section' => 'registration-list',
            'headers' => 'yes',
            'folded-labels' => 'yes',
            'editable' => 'yes',
            'add' => array('label' => '+ Add Registration', 'url' => $args['base_url'] . '?r=new'),
            'columns' => array( 
                array('label'=>$s_competitor . '(s)', 'field'=>'display_name', 'class'=>''),
                array('label'=>'Class', 'field'=>'class_code', 'fold'=>'yes', 'class'=>''),
                array('label'=>'Title', 'field'=>'title', 'fold'=>'yes', 'class'=>''),
                array('label'=>'Fee', 'field'=>'fee_display', 'fold'=>'yes', 'class'=>''),
                array('label'=>'Status', 'field'=>'status_text', 'fold'=>'yes', 'class'=>''),
                ),
            'rows' => $registrations,
            );
    }
    
    //
    // Display the registration form for new registration or edit registration
    //
    if( $display == 'registration-form' ) {
        $form = "<form id='registration-form' class='registration-form medium' action='' method='POST'>";
        if( isset($registrations[$registration_id]) ) {
            $registration = $registrations[$registration_id];
        } else {
            $registration = array(
                'status' => 0,
                'class_id' => 0,
                'competitor1_id' => 0,
                'competitor2_id' => 0,
                'competitor3_id' => 0,
                'title' => '',
                'perf_time' => '',
                );
        }

        //
        // The basic structure of the registration form
        //
        $form_sections = array(
            'cedit' => array(
                'label' => '',
                'visible' => 'no',
                'type' => 'hidden',
                'fields' => array(
                    'ceditid' => array('type' => 'hidden', 'value' => ''),
                    ),
                ),
            'class' => array(
                'label' => 'Class',
                'fields' => array(
                    'section' => array('type'=>'select', 
                        'label'=>array('title'=>'Section', 'class'=>'hidden'),
                        'size' => 'small', 
                        'options'=>array(),
                        ),
                    // More dropdown fields added here for each section of classes
                    ),
                ),
            'competitor1' => array(
                'label' => $s_competitor,
                'visible' => 'yes',
                'fields' => array(),
                ),
            'competitor2' => array(
                'label' => $s_competitor . ' 2',
                'visible' => 'no',
                'fields' => array(),
                ),
            'competitor3' => array(
                'label' => $s_competitor . ' 3',
                'visible' => 'no',
                'fields' => array(),
                ),
            'performance' => array(
                'label' => 'Performing',
                'fields' => array(
                    'title' => array('type'=>'text', 
                        'label' => array('title'=>'Title & Composer'), 
                        'size' => 'large',
                        'value' => (isset($_POST['title']) ? $_POST['title'] : $registration['title'])),
                    'perf_time' => array('type'=>'text', 
                        'label' => array('title'=>'Performance Time'), 
                        'size' => 'small',
                        'value' => (isset($_POST['perf_time']) ? $_POST['perf_time'] : $registration['perf_time'])),
                    ),
                ),
            ); 

        for($i = 1; $i <= 3; $i++) {
            //
            // FIXME: Prefill the address, parent from customer details
            //
            $competitor = array(
                'name' => (isset($_POST["name{$i}"]) ? $_POST["name{$i}"] : ''),
                'parent' => (isset($_POST["parent{$i}"]) ? $_POST["parent{$i}"] : ''),
                'address' => (isset($_POST["address{$i}"]) ? $_POST["address{$i}"] :''), 
                'city' => (isset($_POST["city{$i}"]) ? $_POST["city{$i}"] :''), 
                'province' => (isset($_POST["province{$i}"]) ? $_POST["province{$i}"] :''), 
                'postal' => (isset($_POST["postal{$i}"]) ? $_POST["postal{$i}"] :''), 
                'phone_home' => (isset($_POST["phone_home{$i}"]) ? $_POST["phone_home{$i}"] :''), 
                'phone_cell' => (isset($_POST["phone_cell{$i}"]) ? $_POST["phone_cell{$i}"] :''), 
                'email' => (isset($_POST["email{$i}"]) ? $_POST["email{$i}"] :''), 
                'age' => (isset($_POST["age{$i}"]) ? $_POST["age{$i}"] :''), 
                'study_level' => (isset($_POST["study_level{$i}"]) ? $_POST["study_level{$i}"] :''), 
                'instrument' => (isset($_POST["instrument{$i}"]) ? $_POST["instrument{$i}"] :''), 
                'notes' => (isset($_POST["notes{$i}"]) ? $_POST["notes{$i}"] :''), 
                );
//            if( isset($competitors[$registration["competitor{$i}_id"]]) ) {
//                $competitor = $competitors[$registration["competitor{$i}_id"]];
//            }

            $form_sections["competitor{$i}"]['fields'] = array(
                "competitor{$i}_id" => array('type'=>'select', 
                    'label' => array('title'=>$s_competitor, 'class'=>'hidden'),
                    'size' => 'full', 
                    'options' => array(),
                    'details' => array(),
                    ),
                "name{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Name'), 'size'=>'medium', 'value'=>$competitor['name']),
                "parent{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Parent'), 'size'=>'medium', 'value'=>$competitor['parent']),
                "address{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Address'), 'size'=>'full', 'value'=>$competitor['address']),
                "city{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'City'), 'size'=>'medium', 'value'=>$competitor['city']),
                "province{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Province'), 'size'=>'small', 'value'=>$competitor['province']),
                "postal{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Postal'), 'size'=>'small', 'value'=>$competitor['postal']),
                "phone_home{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Home Phone'), 'size'=>'small', 'value'=>$competitor['phone_home']),
                "phone_cell{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Cell Phone'), 'size'=>'small', 'value'=>$competitor['phone_cell']),
                "email{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Email'), 'size'=>'medium', 'value'=>$competitor['phone_cell']),
                "age{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Age'), 'size'=>'small', 'value'=>$competitor['age']),
                "study_level{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Current Level of Study/Method book'), 'size'=>'medium', 'value'=>$competitor['study_level']),
                "instrument{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Instrument'), 'size'=>'small', 'value'=>$competitor['instrument']),
                "notes{$i}"=>array('type'=>'text', 'visible'=>'no', 'label'=>array('title'=>'Notes'), 'size'=>'full', 'value'=>$competitor['notes']),
                );
        }
        
        //
        // Build the drop down lists for section and classes
        //
        $section_ids = array();
        $selected_section_id = 0;
        $selected_flags = 0;
        $class_flags = array();
        foreach($sections as $section) {
            //
            // Set the default section
            //
            if( $selected_section_id == 0 ) {
                $selected_section_id = $section['id'];
            }
            $section_ids[] = $section['id'];
            //
            // Add the section classes array
            //
            $class_field = "section-{$section['id']}-class";
            $form_sections['class']['fields'][$class_field] = array(
                'type' => 'select', 
                'label' => 'Class',
                'visible' => 'no',
                'size' => 'large', 
                'options' => array(),
                );

            foreach($section['classes'] as $class) {
                $class_flags[$class['id']] = $class['flags'];
                if( $class['id'] == $registration['class_id'] ) {
                    $selected_section_id = $section['id'];
                    $selected_flags = $class['flags'];
                    $form_sections['class']['fields'][$class_field]['options'][] = array(
                        'value' => $class['id'],
                        'selected' => 'yes',
                        'label' => $class['code'] . ' - ' . $class['name'],
                        );
                    $form_sections['class']['fields'][$class_field]['visible'] = 'yes';
                    if( ($class['flags']&0x10) == 0x10 ) {
                        $form_sections['competitor2']['visible'] = 'yes';
                    }
                    if( ($class['flags']&0x20) == 0x20 ) {
                        $form_sections['competitor3']['visible'] = 'yes';
                    }
                } else {
                    $form_sections['class']['fields'][$class_field]['options'][] = array(
                        'value' => $class['id'],
                        'label' => $class['code'] . ' - ' . $class['name'],
                        );
                }
            }
            //
            // Add the section option
            //
            $form_sections['class']['fields']['section']['options'][] = array(
                'value' => $section['id'],
                'selected' => ($selected_section_id == $section['id'] ? 'yes' : 'no'),
                'visible' => 'yes',
                'label' => $section['name'],
                'labelclass' => 'hidden',
                );
        }
        $form_sections['class']['fields']["section-{$selected_section_id}-class"]['visible'] = 'yes';

        //
        // Competitors
        //
        $curdetails = '';
        for($i = 1; $i <= 3; $i++) {
            $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['options'][] = array('value'=>'', 'label'=>'');
            foreach($competitors as $competitor) {
                $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['options'][] = array(
                    'value' => $competitor['id'],
                    'selected' => ($registration["competitor{$i}_id"] == $competitor['id'] ? 'yes' : 'no'),
                    'label' => $competitor['name'],
                    );
                if( $registration["competitor{$i}_id"] == $competitor['id'] ) {
                    $curdetails .= "competitor{$i}_id:'{$competitor['id']}',";
                }
                //
                // The extra details to be displayed below the drop down
                //
                $address = $competitor['address'];
                $address .= $competitor['city'] != '' ? ($address != '' ? ', ' : '') . $competitor['city'] : '';
                $address .= $competitor['province'] != '' ? ($address != '' ? ', ' : '') . $competitor['province'] : '';
                $address .= $competitor['postal'] != '' ? ($address != '' ? ', ' : '') . $competitor['postal'] : '';
                $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['details'][$competitor['id']] = array(
                    array('label'=>'Parent', 'value'=>$competitor['parent'], 'size'=>'full'),
                    array('label'=>'Address', 'value'=>$address, 'size'=>'full'),
                    array('label'=>'Home Phone', 'value'=>$competitor['phone_home'], 'size'=>'full'),
                    array('label'=>'Cell Phone', 'value'=>$competitor['phone_cell'], 'size'=>'full'),
                    array('label'=>'Email', 'value'=>$competitor['email'], 'size'=>'full'),
                    array('label'=>'Age', 'value'=>$competitor['age'], 'size'=>'full'),
                    array('label'=>'Study/Level', 'value'=>$competitor['study_level'], 'size'=>'full'),
                    array('label'=>'Instrument', 'value'=>$competitor['instrument'], 'size'=>'full'),
//                    array('type'=>'button', 'label'=>'Edit ' . $s_competitor, 'url'=>$args['base_url'] . "?r=" . $registration['uuid'] . "&c=" . $competitor['uuid']),
                    array('type'=>'button', 'label'=>'Edit ' . $s_competitor, 'url'=>'javascript: editComp("' . $competitor['uuid'] . '");'),
                    );
            }
            $form_sections["competitor{$i}"]['fields']["competitor{$i}_id"]['options'][] = array('value'=>'new', 'label'=>'Add ' . $s_competitor);
        }

        $buttons = array(
            'save' => array('type'=>'submit', 'label'=>'Save'),
            );
        if( $registration['status'] == 5 && !isset($_POST['delete']) ) {
            $buttons['delete'] = array('type'=>'submit', 'class'=>'delete', 'label'=>'Delete');
        } elseif( $registration['status'] == 5 && isset($_POST['delete']) && $_POST['delete'] == 'Delete' ) {
            $buttons['delete'] = array('type'=>'submit', 'class'=>'delete', 'label'=>'Confirm');
        }

        $blocks[] = array('type' => 'registrationform', 
            'id' => 'mrf',
            'action' => '',
            'method' => 'POST',
            'sections' => $form_sections,
            'onchange' => 'regFormUpdate()',
            'buttons' => $buttons,
            'javascript' => ""
                . "var cflags = " . json_encode($class_flags) . ";"
                . "var sids = [" . implode(',', $section_ids) . "];"
                . "var cfields = ['name','parent','address','city','province','postal','phone_home','phone_cell','email','age','study_level','instrument','notes'];"
                . "var curdetails = {{$curdetails}};"
                . "function regFormUpdate() {"
                    //
                    // Check if competitor should be added
                    //
                    . "var s=document.getElementById('section').value;"
                    . "var c=0;"
                    . "for(var i in sids){"
                        . "var e=document.getElementById('section-' + sids[i] + '-class-wrap');"
                        . "if(s==sids[i]){"
                            . "var v=document.getElementById('section-' + sids[i] + '-class');"
                            . "c=v.value;"
                            . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                        . "}else{"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                    . "}"
                    //
                    // Check how many competitors there should be
                    //
                    . "if(cflags[c] != null){"
                        . "var e=document.getElementById('competitor2');"
                        . "if( (cflags[c]&0x10) == 0x10 ) {"
                            . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                        . "}else{"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                        . "var e=document.getElementById('competitor3');"
                        . "if( (cflags[c]&0x20) == 0x20 ) {"
                            . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                        . "}else{"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                    . "}"
                    . "var f=document.getElementById('mrf');"
                    . "var c1=document.getElementById('competitor1_id');"
                    . "var c2=document.getElementById('competitor2_id');"
                    . "var c3=document.getElementById('competitor3_id');"
                    . "if(c1.value=='new'){"
                        . "showCompetitorAdd(1);"
                        . "hideCompetitorDetails(1);"
                    . "}else{"
                        . "hideCompetitorAdd(1);"
                        . "if(c1.value>0){"
                            . "showCompetitorDetails(1,c1.value);"
                        . "}else{"
                            . "hideCompetitorDetails(1);"
                        . "}"
                    . "}"
                    . "if(c2.value=='new'){"
                        . "showCompetitorAdd(2);"
                        . "hideCompetitorDetails(2);"
                    . "}else{"
                        . "hideCompetitorAdd(2);"
                        . "if(c2.value>0){"
                            . "showCompetitorDetails(2,c2.value);"
                        . "}"
                    . "}"
                    . "if(c3.value=='new'){"
                        . "showCompetitorAdd(3);"
                        . "hideCompetitorDetails(3);"
                    . "}else{"
                        . "hideCompetitorAdd(3);"
                        . "if(c3.value>0){"
                            . "showCompetitorDetails(3,c3.value);"
                        . "}"
                    . "}"
                . "};"
                . "function showCompetitorAdd(n){"
                    . "for(var i in cfields){"
                        . "var e=document.getElementById(cfields[i] + n + '-wrap');"
                        . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                    . "}"
                . "};"
                . "function hideCompetitorAdd(n){"
                    . "for(var i in cfields){"
                        . "var e=document.getElementById(cfields[i] + n + '-wrap');"
                        . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                    . "}"
                . "};"
                . "function hideCompetitorDetails(n){"
                    . "if( curdetails['competitor'+n+'_id'] != null ) {"
                        . "var e=document.getElementById('competitor' + n + '_id-details-' + curdetails['competitor'+n+'_id']);"
                        . "if(e!=null){"
                            . "e.classList.contains('hidden') ? '' : e.classList.add('hidden');"
                        . "}"
                    . "}"
                . "}"
                . "function showCompetitorDetails(n,i){"
                    . "hideCompetitorDetails(n);"
                    . "curdetails['competitor'+n+'_id'] = i;"
                    . "var e=document.getElementById('competitor' + n + '_id-details-' + i);"
                    . "if(e!=null){"
                        . "e.classList.contains('hidden') ? e.classList.remove('hidden') : '';"
                    . "}"
                . "}"
                . "function editComp(n){"
                    . "var e=document.getElementById('ceditid');"
                    . "e.value=n;"
                    . "document.getElementById('mrf').submit();"
                . "}"
                . "",
            );
    }

    //
    // Display the competitor form to add/edit a competitors details.
    //
    if( $display == 'competitor-form' ) {
        $competitor = $competitors[$competitor_id];
        $form_sections = array(
            'cedit' => array(
                'label' => '',
                'visible' => 'no',
                'type' => 'hidden',
                'fields' => array(
                    'ceditid' => array('type' => 'hidden', 'value' => $_POST['ceditid']),
                    'r' => array('type' => 'hidden', 'value' => $_GET['r']),
                    ),
                ),
            'competitor' => array(
                'label' => $s_competitor,
                'visible' => 'yes',
                'fields' => array(
                    'name'=>array('type'=>'text',
                        'label'=>array('title'=>'Name'), 'size'=>'medium', 'value'=>$competitor['name']),
                    'parent'=>array('type'=>'text',
                        'label'=>array('title'=>'Parent'), 'size'=>'medium', 'value'=>$competitor['parent']),
                    'address'=>array('type'=>'text',
                        'label'=>array('title'=>'Address'), 'size'=>'full', 'value'=>$competitor['address']),
                    'city'=>array('type'=>'text',
                        'label'=>array('title'=>'City'), 'size'=>'medium', 'value'=>$competitor['city']),
                    'province'=>array('type'=>'text',
                        'label'=>array('title'=>'Province'), 'size'=>'small', 'value'=>$competitor['province']),
                    'postal'=>array('type'=>'text',
                        'label'=>array('title'=>'Postal'), 'size'=>'small', 'value'=>$competitor['postal']),
                    'phone_home'=>array('type'=>'text',
                        'label'=>array('title'=>'Home Phone'), 'size'=>'small', 'value'=>$competitor['phone_home']),
                    'phone_cell'=>array('type'=>'text',
                        'label'=>array('title'=>'Cell Phone'), 'size'=>'small', 'value'=>$competitor['phone_cell']),
                    'email'=>array('type'=>'text',
                        'label'=>array('title'=>'Email'), 'size'=>'medium', 'value'=>$competitor['email']),
                    'age'=>array('type'=>'text',
                        'label'=>array('title'=>'Age'), 'size'=>'small', 'value'=>$competitor['age']),
                    'study_level'=>array('type'=>'text',
                        'label'=>array('title'=>'Current Level of Study/Method book'), 'size'=>'medium', 'value'=>$competitor['study_level']),
                    'instrument'=>array('type'=>'text',
                        'label'=>array('title'=>'Instrument'), 'size'=>'small', 'value'=>$competitor['instrument']),
                    'notes'=>array('type'=>'text',
                        'label'=>array('title'=>'Notes'), 'size'=>'full', 'value'=>$competitor['notes']),
                    ),
                ),
            ); 
        $blocks[] = array('type' => 'registrationform', 
            'id' => 'mcf',
            'action' => '',
            'method' => 'POST',
            'sections' => $form_sections,
            'buttons' => array(
                'save' => array('type'=>'submit', 'label'=>'Save'),
                ),
            'javascript' => ""
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
