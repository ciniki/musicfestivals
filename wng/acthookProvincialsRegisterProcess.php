<?php
//
// Description
// -----------
// This function will process the registration from a local festival into provincials.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_acthookProvincialsRegisterProcess(&$ciniki, $tnid, &$request) {

    $blocks = [];

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/ahk/musicfestival/register';
    $display = 'list';
    $errors = array();

    if( !isset($request['uri_split'][$request['cur_uri_pos']]) ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid request',
            ]]);
    }

    $entry_uuid = $request['uri_split'][$request['cur_uri_pos']];
    if( $entry_uuid == '' ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid request',
            ]]);
    }

    if( (isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel')
        || (isset($_POST['f-action']) && $_POST['f-action'] == 'cancel')
        ) {
        if( isset($request['session']['musicfestival-registration']) ) {
            unset($request['session']['musicfestival-registration']);
        }
        header("Location: {$request['ssl_domain_base_url']}/account");
        return array('stat'=>'exit');
    }

    //
    // Check to make sure the link has entry and registration uuid
    //
    if( !isset($request['uri_split'][($request['cur_uri_pos']+1)]) 
        || trim($request['uri_split'][$request['cur_uri_pos']]) == ''
        || trim($request['uri_split'][($request['cur_uri_pos']+1)]) == ''
        ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid Request',
            ]]);
    }
    $entry_uuid = $request['uri_split'][$request['cur_uri_pos']];
    $registration_uuid = $request['uri_split'][($request['cur_uri_pos']+1)];
    $base_url .= '/' . $entry_uuid . '/' . $registration_uuid;

    //
    // Make sure festival is a provincials festival
    //
    if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid request, unable to register for this festival.',
            ]]);
    }

    //
    // Load the current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'No active festival.',
            ]]);
    }
    $festival = $rc['festival'];

    //
    // Make sure they are signed in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'accountLoginProcess');
        $rc = ciniki_wng_accountLoginProcess($ciniki, $tnid, $request, array(
            'create-account' => 'simple',
            'return-url' => $request['base_url'] . '/' . implode('/', $request['uri_split']),
            ));

        if( $rc['stat'] == 'ok' && !isset($_GET['signup-success']) ) {
            array_unshift($rc['blocks'], array(
                'sequence' => 1,
                'type' => 'text',
                'class' => 'form-intro aligncenter',
                'title' => 'Registration',
                'content' => 'If you have participated in provincials last year, you can use your existing login. If not please create a new account.',
                ));
        }
        return $rc;
    }

    //
    // Load the recommendation, the registration from the local festival and the competitors
    //
    $strsql = "SELECT entries.id, "
        . "entries.status, "
        . "entries.position, "
        . "entries.name, "
        . "entries.mark, "
        . "entries.notes, "
        . "entries.class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "recommendations.id AS recommendation_id, "
        . "recommendations.member_id, "
        . "recommendations.status AS recommendation_status, "
        . "members.name AS member_name, "
        . "members.flags AS member_flags, "
        . "mfest.reg_start_dt, "
        . "mfest.reg_end_dt, "
        . "mfest.latedays, "
        . "localreg.id AS local_reg_id, "
        . "localreg.tnid AS local_tnid, "
        . "localreg.festival_id AS local_festival_id, "
        . "localreg.display_name AS local_display_name, "
        . "localreg.teacher_customer_id, "
        . "localreg.competitor1_id, "
        . "localreg.competitor2_id, "
        . "localreg.competitor3_id, "
        . "localreg.competitor4_id, "
        . "localreg.competitor5_id, "
        . "localreg.title1, "
        . "localreg.composer1, "
        . "localreg.movements1, "
        . "localreg.perf_time1, "
        . "localreg.title2, "
        . "localreg.composer2, "
        . "localreg.movements2, "
        . "localreg.perf_time2, "
        . "localreg.title3, "
        . "localreg.composer3, "
        . "localreg.movements3, "
        . "localreg.perf_time3, "
        . "localreg.title4, "
        . "localreg.composer4, "
        . "localreg.movements4, "
        . "localreg.perf_time4, "
        . "localreg.title5, "
        . "localreg.composer5, "
        . "localreg.movements5, "
        . "localreg.perf_time5, "
        . "localreg.title6, "
        . "localreg.composer6, "
        . "localreg.movements6, "
        . "localreg.perf_time6, "
        . "localreg.title7, "
        . "localreg.composer7, "
        . "localreg.movements7, "
        . "localreg.perf_time7, "
        . "localreg.title8, "
        . "localreg.composer8, "
        . "localreg.movements8, "
        . "localreg.perf_time8, "
        . "localclasses.code AS local_class_code, "
        . "localclasses.name AS local_class_name, "
        . "localcategories.name AS local_category_name, "
        . "localsections.name AS local_section_name "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
            . "entries.recommendation_id = recommendations.id "
            . "AND recommendations.status = 50 "
            . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestivals_members AS members ON ("
            . "recommendations.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_members AS mfest ON ("
            . "members.id = mfest.member_id "
            . "AND mfest.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND mfest.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ( "
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS localreg ON ("
            . "entries.local_reg_id = localreg.id "
            . "AND members.member_tnid = localreg.tnid "
            . "AND localreg.uuid = '" . ciniki_core_dbQuote($ciniki, $registration_uuid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS localclasses ON ("
            . "localreg.class_id = localclasses.id "
            . "AND localreg.tnid = localclasses.tnid "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS localcategories ON ("
            . "localclasses.category_id = localcategories.id "
            . "AND localclasses.tnid = localcategories.tnid "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS localsections ON ("
            . "localcategories.section_id = localsections.id "
            . "AND localcategories.tnid = localsections.tnid "
            . ") "
        . "WHERE entries.uuid = '" . ciniki_core_dbQuote($ciniki, $entry_uuid) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1287', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
    }
    if( !isset($rc['entry']) ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid Request Link',
            ]]);
    }
    $entry = $rc['entry'];

    $selected_member = [
        'id' => $entry['member_id'],
        'name' => $entry['member_name'],
        'flags' => $entry['member_flags'],
        'reg_start_dt' => $entry['reg_start_dt'],
        'reg_end_dt' => $entry['reg_end_dt'],
        'latedays' => $entry['latedays'],
        ];
    if( $selected_member['reg_start_dt'] == '' ) {
        $selected_member['name'] .= ' - Not yet open';
    } else {
        $dt = new DateTime('now', new DateTimezone('UTC'));
        $sdt = new DateTime($selected_member['reg_start_dt'], new DateTimezone('UTC'));
        $edt = new DateTime($selected_member['reg_end_dt'], new DateTimezone('UTC'));
        if( $dt < $sdt ) {
            $selected_member['name'] .= ' - Not yet open';
        } elseif( $dt > $edt ) {
            $diff = $dt->diff($edt);
            if( $diff->days < 1 && $selected_member['latedays'] >= 1 ) {
                $selected_member['name'] .= ' - Late fee $25';
                $selected_member['open'] = 'yes';
                $selected_member['latefee'] = 25;
            } elseif( $diff->days < 2 && $selected_member['latedays'] >= 2 ) {
                $selected_member['name'] .= ' - Late fee $50';
                $selected_member['open'] = 'yes';
                $selected_member['latefee'] = 50;
            } elseif( $diff->days < 3 && $selected_member['latedays'] >= 3 ) {
                $selected_member['name'] .= ' - Late fee $75';
                $selected_member['open'] = 'yes';
                $selected_member['latefee'] = 75;
            } else {
                $selected_member['name'] .= ' - Closed';
            }
        } else {
            $selected_member['name'] .= ' - Open';
            $selected_member['open'] = 'yes';
        }
    }

    //
    // Make sure the recommendation is still valid
    //
    if( $entry['status'] < 45 ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid link',
            ]]);
    } elseif( $entry['status'] == 50 ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Registration already completed',
            ]]);
    } elseif( $entry['status'] != 45 ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid Link',
            ]]);
    }
    
    //
    // Load the customer type, or ask for customer type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountCustomerTypeProcess');
    $rc = ciniki_musicfestivals_wng_accountCustomerTypeProcess($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'base_url' => $base_url,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['stop']) && $rc['stop'] == 'yes' ) {
        // 
        // Return form with select customer type
        //
        return $rc;
    }
    $customer_type = $rc['customer_type'];
    if( isset($rc['switch_block']) ) {
        $customer_switch_type_block = $rc['switch_block'];
    }

    //
    // Get the list of competitors
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.name, "
        . "competitors.pronoun, "
        . "competitors.parent, "
        . "competitors.age, "
        . "competitors.instrument "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "WHERE competitors.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "ORDER BY competitors.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name', 'pronoun', 'parent', 'age', 'instrument')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.257', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Setup the session registration
    //
//    unset($request['session']['musicfestival-registration']);
    if( !isset($request['session']['musicfestival-registration']) 
        || $request['session']['musicfestival-registration']['entry_id'] != $entry['id']
        ) {
        $request['session']['musicfestival-registration'] = [
            'registration_id' => 0,
            'entry_id' => $entry['id'],
            'member_id' => $entry['member_id'],
            'class_id' => $entry['class_id'],
            ];
        for($i = 1; $i <= 8; $i++) {
            foreach(['title', 'movements', 'composer', 'perf_time'] as $field) {
                if( isset($entry["{$field}{$i}"]) && $entry["{$field}{$i}"] != '' ) {
                    $request['session']['musicfestival-registration']["{$field}{$i}"] = $entry["{$field}{$i}"];
                }
            }
        }
    }

    //
    // Check if competitors setup
    //
    for($i = 1; $i <= 5; $i++) {
        //
        // Check if competitor has been set up yet
        //
        if( $entry["competitor{$i}_id"] > 0     // Competitor is set in local festival
            && (!isset($request['session']['musicfestival-registration']["competitor{$i}_id"]) // competitor not added yet
                || !isset($competitors[$request['session']['musicfestival-registration']["competitor{$i}_id"]]) // competitor added by does not exist
                )
            ) {
            //
            // Load the festival competitor
            //
            $strsql = "SELECT id AS competitor_id, "
                . "uuid, "
                . "billing_customer_id, "
                . "ctype, "
                . "first, "
                . "last, "
                . "name, "
                . "public_name, "
                . "pronoun, "
                . "flags, "
                . "conductor, "
                . "num_people, "
                . "parent, "
                . "address, "
                . "city, "
                . "province, "
                . "postal, "
                . "country, "
                . "phone_home, "
                . "phone_cell, "
                . "email, "
                . "age, "
                . "study_level, "
                . "last_exam, "
                . "instrument, "
                . "etransfer_email, "
                . "notes "
                . "FROM ciniki_musicfestival_competitors "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $entry["competitor{$i}_id"]) . "' "
                . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $entry['local_festival_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $entry['local_tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.355', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
            }
            if( !isset($rc['competitor']) ) {
                return array('stat'=>'ok', 'blocks'=>[
                    ['type' => 'msg',
                    'level' => 'error',
                    'content' => 'Invalid Link',
                    ]]);
            }
            $local_competitor = isset($rc['competitor']) ? $rc['competitor'] : array();
            $local_competitor['id'] = 0;
            $local_competitor['flags'] = 0;

            //
            // Check if competitor exists already
            //
            foreach($competitors as $c) {
                if( $c['name'] == $local_competitor['name']
                    && $c['parent'] == $local_competitor['parent']
                    ) {
                    $request['session']['musicfestival-registration']["competitor{$i}_id"] = $c['id'];
                    continue 2;
                }
            }

            //
            // Setup the fields for the competitor
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'competitorFormGenerate');
            $rc = ciniki_musicfestivals_wng_competitorFormGenerate($ciniki, $tnid, $request, [
                'ctype' => $local_competitor['ctype'],
                'customer_type' => $customer_type,
                'festival' => $festival,
                'competitor' => isset($local_competitor) ? $local_competitor : null,
                ]); 
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1355', 'msg'=>'Unable to generate form', 'err'=>$rc['err']));
            }
            $fields = $rc['fields'];
            $ctype = $local_competitor['ctype'];

            //
            // Process the competitor
            //
            if( isset($_POST['f-competitor_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
                $request['session']['account-musicfestivals-competitor-form-return'] = $base_url;
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'competitorFormUpdateProcess');
                $rc = ciniki_musicfestivals_wng_competitorFormUpdateProcess($ciniki, $tnid, $request, [
                    'ctype' => $local_competitor['ctype'],
                    'customer_type' => $customer_type,
                    'festival' => $festival,
                    'fields' => $fields,
                    'competitor_id' => $_POST['f-competitor_id'],
                    'provincials-competitor-number' => $i,
                    ]); 
                if( $rc['stat'] == 'exit' ) {
                    return $rc;
                } elseif( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1355', 'msg'=>'Unable to generate form', 'err'=>$rc['err']));
                }
                if( isset($rc['errors']) ) {
                    $errors = $rc['errors'];
                }
            }

            //
            // Display the form 
            //
            $guidelines = '';
            if( $customer_type == 10 && $ctype == 50 && isset($festival['competitor-group-parent-msg']) ) {
                $guidelines = $festival['competitor-group-parent-msg'];
            } elseif( $customer_type == 20 && $ctype == 50 && isset($festival['competitor-group-teacher-msg']) ) {
                $guidelines = $festival['competitor-group-teacher-msg'];
            } elseif( $customer_type == 30 && $ctype == 50 && isset($festival['competitor-group-adult-msg']) ) {
                $guidelines = $festival['competitor-group-adult-msg'];
            } elseif( $customer_type == 10 && isset($festival['competitor-parent-msg']) ) {
                $guidelines = $festival['competitor-parent-msg'];
            } elseif( $customer_type == 20 && isset($festival['competitor-teacher-msg']) ) {
                $guidelines = $festival['competitor-teacher-msg'];
            } elseif( $customer_type == 30 && isset($festival['competitor-adult-msg']) ) {
                $guidelines = $festival['competitor-adult-msg'];
            }

            //
            // Prepare any errors
            //
            $form_errors = '';
            if( isset($errors) && count($errors) > 0 ) {
                foreach($errors as $err) {
                    $form_errors .= ($form_errors != '' ? '<br/>' : '') . $err['msg'];
                }
            }
           
            $blocks[] = array(
                'type' => 'form',
                'guidelines' => $guidelines,
                'title' => 'Add' . ($ctype == 50 ? ' Group/Ensemble' : " Individual {$festival['competitor-label-singular']}"),
                'class' => 'limit-width limit-width-70',
                'problem-list' => $form_errors,
                'cancel-label' => 'Cancel',
                'submit-label' => 'Save',
                'fields' => $fields,
                );

            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
    }

    //
    // Check if teacher needs to be set up
    //
    if( $customer_type == 20 ) {
        $request['session']['musicfestival-registration']['teacher_customer_id'] = $request['session']['customer']['id'];
               
    } elseif( $entry['teacher_customer_id'] > 0 ) {
        //
        // Lookup local teacher and set up in provincials
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $entry['local_tnid'], [
            'customer_id' => $entry['teacher_customer_id'], 
            'phones'=>'yes', 
            'emails'=>'yes',
            ]);
        if( $rc['stat'] == 'ok' ) {
            $teacher_first = $rc['customer']['first'];
            $teacher_last = $rc['customer']['last'];
            $teacher_email = '';
            if( isset($rc['customer']['emails'][0]['address']) ) {
                $teacher_email = $rc['customer']['emails'][0]['address'];
            }
            $teacher_phone_label = '';
            $teacher_phone = '';
            if( isset($rc['customer']['phones'][0]['phone_number']) ) {
                $teacher_phone_label = $rc['customer']['phones'][0]['phone_label'];
                $teacher_phone = $rc['customer']['phones'][0]['phone_number'];
            }

            //
            // Check if teacher exists in provincials
            //
            $strsql = "SELECT emails.id, emails.customer_id "
                . "FROM ciniki_customer_emails AS emails "
                . "INNER JOIN ciniki_customers AS customers ON ("
                    . "emails.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND customers.status < 60 "
                    . ") "
                . "WHERE emails.email = '" . ciniki_core_dbQuote($ciniki, trim($teacher_email)) . "' "
                . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'customer');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1367', 'msg'=>'Unable to search teacher', 'err'=>$rc['err']));
            }
            if( isset($rc['customer']) ) {
                $request['session']['musicfestival-registration']['teacher_customer_id'] = $rc['customer']['customer_id'];
            } else {
                // 
                // Add teacher
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerAdd');
                $rc = ciniki_customers_web_customerAdd($ciniki, $tnid, array(
                    'first'=>$teacher_first,
                    'last'=>$teacher_last,
                    'email_address'=>$teacher_email,
                    'phone_label_1'=>$teacher_phone_label,
                    'phone_number_1'=>$teacher_phone,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'ok', 'blocks'=>[
                        ['type' => 'msg',
                        'level' => 'error',
                        'content' => 'We had a problem setting up the teacher, please try again or contact us for help.',
                        ]]);
                }
                $request['session']['musicfestival-registration']['teacher_customer_id'] = $rc['id'];
            }
        }
    }

    //
    // Load the teachers for the registration form
    //
    $strsql = "SELECT customers.id, "
        . "customers.display_name "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "registrations.teacher_customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.653', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
    }
    $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();
    if( isset($request['session']['musicfestival-registration']['teacher_customer_id']) 
        && $request['session']['musicfestival-registration']['teacher_customer_id'] > 0
        && !isset($teachers[$request['session']['musicfestival-registration']['teacher_customer_id']]) 
        ) {
        $teachers[$request['session']['musicfestival-registration']['teacher_customer_id']] = [
            'id' => $request['session']['musicfestival-registration']['teacher_customer_id'],
            'name' => trim($teacher_first . ' ' . $teacher_last),
            ];
    }

    //
    // Load accompanists
    //
    $accompanists = array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
        $strsql = "SELECT customers.id, "
            . "customers.display_name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.accompanist_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accompanists', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.297', 'msg'=>'Unable to load accompanists', 'err'=>$rc['err']));
        }
        $accompanists = isset($rc['accompanists']) ? $rc['accompanists'] : array();
    }

    //
    // Setup the registration form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'registrationFormGenerate');
    $rc = ciniki_musicfestivals_wng_registrationFormGenerate($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'display' => 'recommendation-registration',
        'competitors' => $competitors,
        'teachers' => $teachers,
        'accompanists' => $accompanists,
        'members' => [], //isset($members) ? $members : null,
        'registration' => $request['session']['musicfestival-registration'],
        'customer_type' => $customer_type,
        'customer_id' => $request['session']['customer']['id'],
        ));
    if( $rc['stat'] == 'noexist' ) {
        return array('stat'=>'ok', 'blocks'=>array(
            array(
                'type' => 'msg',
                'level' => 'error', 
                'content' => $rc['err']['msg'],
                ),
            ));
    } elseif( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.348', 'msg'=>'', 'err'=>$rc['err']));
    }
    $fields = $rc['fields'];
    $js = $rc['js'];
/*    $sections = $rc['sections'];
    if( isset($rc['selected_section']) ) {
        $selected_section = $rc['selected_section'];
    } */
    if( isset($rc['selected_class']) ) {
        $selected_class = $rc['selected_class'];
        if( ($festival['flags']&0x0100) == 0x0100 ) {
            $selected_class['name'] = $selected_class['category_name'] . ' - ' . $selected_class['name'];
        }
    } 
//    if( isset($rc['selected_member']) ) {
//        $selected_member = $rc['selected_member'];
//    }

    //
    // Process the registration
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'registrationFormUpdateProcess');
        $rc = ciniki_musicfestivals_wng_registrationFormUpdateProcess($ciniki, $tnid, $request, [
            'festival' => $festival,
            'registration_id' => 0,
            'display' => 'recommendation-registration',
            'selected_class' => isset($selected_class) ? $selected_class : null,
            'selected_member' => $selected_member,
            'fields' => $fields,
            'registration' => isset($registration) ? $registration : null,
            'customer_type' => $customer_type,
            'customer_id' => $request['session']['customer']['id'],
            ]);
        if( $rc['stat'] == 'exit' ) {
            //
            // Update the recommendation with the registration
            //
            if( isset($rc['id']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $entry['id'], [
                    'status' => 50,
                    'provincials_reg_id' => $rc['id'],
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1376', 'msg'=>'Unable to update the recommendationentry', 'err'=>$rc['err']));
                }
            }
            return array('stat'=>'exit');
        } elseif( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>[[
                'type' => 'msg',
                'level' => 'error', 
                'content' => $rc['err']['msg'],
                ]]);
        }
        $errors = $rc['errors'];

    }

    $form_errors = '';
    if( isset($errors) && count($errors) > 0 ) {
        foreach($errors as $err) {
            $form_errors .= ($form_errors != '' ? '<br/>' : '') . $err['msg'];
        }
    }

    //
    // Display the registration form
    //
    $guidelines = '';
    if( $customer_type == 10 
        && isset($festival['recommendation-registration-parent-msg']) 
        && $festival['recommendation-registration-parent-msg'] != '' 
        ) {
        $guidelines = $festival['recommendation-registration-parent-msg'];
    } elseif( $customer_type == 10 && isset($festival['registration-parent-msg']) ) {
        $guidelines = $festival['registration-parent-msg'];
    } elseif( $customer_type == 20 
        && isset($festival['recommendation-registration-teacher-msg']) 
        && $festival['recommendation-registration-teacher-msg'] != '' 
        ) {
        $guidelines = $festival['recommendation-registration-teacher-msg'];
    } elseif( $customer_type == 20 && isset($festival['registration-teacher-msg']) ) {
        $guidelines = $festival['registration-teacher-msg'];
    } elseif( $customer_type == 30 
        && isset($festival['recommendation-registration-adult-msg']) 
        && $festival['recommendation-registration-adult-msg'] != '' 
        ) {
        $guidelines = $festival['recommendation-registration-adult-msg'];
    } elseif( $customer_type == 30 && isset($festival['registration-adult-msg']) ) {
        $guidelines = $festival['registration-adult-msg'];
    }
    $blocks[] = array(
        'type' => 'form',
        'form-id' => 'addregform',
        'guidelines' => $guidelines,
        'title' => 'Add Registration',
        'class' => 'limit-width limit-width-80',
//            'submit-buttons-class' => (isset($members) && $fields['member_id']['value'] == 0 ? 'hidden' : ''),
        'problem-list' => $form_errors,
        'cancel-label' => 'Cancel',
        'js-submit' => 'formSubmit();',
        'js-cancel' => 'formCancel();',
        'submit-label' => 'Save',
        'fields' => $fields,
        'js' => $js,
        );


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
