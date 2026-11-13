<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_accountRegistrationTransferIn(&$ciniki, $tnid, &$request, $args) {

    $blocks = [];

    //
    // Make sure a festival was specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1726', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    //
    // Make sure registrations where passed in arguments
    //
    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1727', 'msg'=>"No registration specified"));
    }
    $registration = $args['registration'];

    //
    // Make sure competitors where passed in arguments
    //
    if( !isset($args['competitors']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1728', 'msg'=>"No competitors specified"));
    }
    $competitors = $args['competitors'];

    //
    // Make sure customer_type where passed in arguments
    //
    if( !isset($args['customer_type']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1731', 'msg'=>"No type specified"));
    }
    $customer_type = $args['customer_type'];

    //
    // Check for each competitors
    //
    $new_reg_competitor_ids = [];
//    $remote_competitors = [];
    for($i = 1; $i <= 5; $i++) {
        if( isset($registration["competitor{$i}_id"]) && $registration["competitor{$i}_id"] > 0 ) {
            //
            // Load competitor
            //
            $strsql = "SELECT competitors.id, "
                . "competitors.festival_id, "
                . "competitors.ctype, "
                . "competitors.first, "
                . "competitors.last, "
                . "competitors.name, "
                . "competitors.public_name, "
                . "competitors.pronoun, "
                . "competitors.conductor, "
                . "competitors.num_people, "
                . "competitors.organization, "
                . "competitors.parent, "
                . "competitors.address, "
                . "competitors.city, "
                . "competitors.province, "
                . "competitors.postal, "
                . "competitors.country, "
                . "competitors.phone_home, "
                . "competitors.phone_cell, "
                . "competitors.phone_work, "
                . "competitors.email, "
                . "competitors.age, "
                . "competitors.study_level, "
                . "competitors.grade, "
                . "competitors.last_exam, "
                . "competitors.instrument, "
                . "competitors.school, "
                . "competitors.etransfer_email, "
                . "competitors.notes, "
                . "COUNT(registrations.id) AS num_reg "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "(competitors.id = registrations.competitor1_id "
                        . "OR competitors.id = registrations.competitor2_id "
                        . "OR competitors.id = registrations.competitor3_id "
                        . "OR competitors.id = registrations.competitor4_id "
                        . "OR competitors.id = registrations.competitor5_id "
                        . ") "
                        . "AND registrations.id <> '" . ciniki_core_dbQuote($ciniki, $registration['registration_id']) . "' "    
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE competitors.id = '" . ciniki_core_dbQuote($ciniki, $registration["competitor{$i}_id"]) . "' "
                . "AND competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "GROUP BY competitors.id "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1729', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
            }
            if( !isset($rc['competitor']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1730', 'msg'=>'Unable to find requested competitor'));
            }
            $remote_competitor = $rc['competitor'];
//            $remote_competitors[$rc['competitor']['id']] = $remote_competitor;
            
            foreach($competitors as $competitor) {
                if( $competitor['name'] == $remote_competitor['name'] ) {
                    $new_reg_competitor_ids[$i] = $competitor['id'];
                    $registration["competitor{$i}_id"] = $competitor['id'];
                } 
            }
            //
            // If competitor doesn't exist, display form and add competitor
            //
            if( !isset($new_reg_competitor_ids[$i]) ) {
                //
                // Setup the fields for the competitor
                //
                unset($remote_competitor['id']);
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'competitorFormGenerate');
                $rc = ciniki_musicfestivals_wng_competitorFormGenerate($ciniki, $tnid, $request, [
                    'ctype' => $remote_competitor['ctype'],
                    'customer_type' => $customer_type,
                    'festival' => $festival,
                    'competitor' => isset($remote_competitor) ? $remote_competitor : null,
                    ]); 
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1732', 'msg'=>'Unable to generate form', 'err'=>$rc['err']));
                }
                $fields = $rc['fields'];
                $fields['action']['value'] = 'transferin';
                $fields['registration_id'] = [
                    'id' => 'registration_id', 
                    'ftype' => 'hidden',
                    'value' => $registration['registration_id'],
                    ];
                $ctype = $remote_competitor['ctype'];
                if( $ctype == 50 ) {
                    $fields['name']['editable'] = 'no';
                } else {
                    $fields['first']['editable'] = 'no';
                    $fields['last']['editable'] = 'no';
                }

                //
                // Process the competitor
                //
                if( isset($_POST['f-competitor_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'transferin' ) {
                    $request['session']['account-musicfestivals-competitor-form-return'] = $args['base_url'];
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'competitorFormUpdateProcess');
                    $rc = ciniki_musicfestivals_wng_competitorFormUpdateProcess($ciniki, $tnid, $request, [
                        'ctype' => $remote_competitor['ctype'],
                        'customer_type' => $customer_type,
                        'festival' => $festival,
                        'fields' => $fields,
                        'competitor_id' => $_POST['f-competitor_id'],
                        'display' => 'transferin',
                        ]); 
                    if( $rc['stat'] == 'ok' ) {
                        $registration["competitor{$i}_id"] = $rc['id'];
                        foreach($_POST as $k => $v) {   
                            if( strncmp($k, 'f-', 2) == 0 || $k == 'submit' ) {
                                unset($_POST[$k]);
                            }
                        }
                        continue;
                        
                    } elseif( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1733', 'msg'=>'Unable to generate form', 'err'=>$rc['err']));
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
    }

    //
    // All good to transfer
    //
    if( $registration['status'] == 6 && $registration['parent_customer_id'] == $request['session']['customer']['id'] ) {
        //
        // Check for Cart
        //
        if( !isset($request['session']['cart']['id']) || $request['session']['cart']['id'] == 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCreate');
            $rc = ciniki_sapos_wng_cartCreate($ciniki, $tnid, $request, array());
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1734', 'msg'=>'Error opening cart', 'err'=>$rc['err']));
            }
        }

        //
        // Lookup the class code and name
        //
        $strsql = "SELECT classes.code, "
            . "classes.name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $registration['class_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1735', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
        }
        if( !isset($rc['class']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1747', 'msg'=>'Unable to find requested class'));
        }
        $selected_class = $rc['class'];

        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');
        $rc = ciniki_musicfestivals_classNameFormat($ciniki, $args['tnid'], [
            'code' => $selected_class['code'],
            'name' => $selected_class['name'],
            'category' => $selected_class['category_name'],
            'section' => $selected_class['section_name'],
            'format' => isset($festival['registrations-class-format']) ? preg_replace("/code-/", '', $festival['registrations-class-format']) : 'class',
            ]);
        $selected_class['class_name'] = $rc['name'];

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
        // Generate notes field for invoice
        //
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

        //
        // Start transaction
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration['registration_id'], [
            'billing_customer_id' => $request['session']['customer']['id'],
            'parent_customer_id' => 0,
            'competitor1_id' => $registration['competitor1_id'],
            'competitor2_id' => $registration['competitor2_id'],
            'competitor3_id' => $registration['competitor3_id'],
            'competitor4_id' => $registration['competitor4_id'],
            'competitor5_id' => $registration['competitor5_id'],
            'invoice_id' => $request['session']['cart']['id'],
            'status' => 5,
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1745', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
        }

        //
        // Add the registration to the cart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemAdd');
        $rc = ciniki_sapos_wng_cartItemAdd($ciniki, $tnid, $request, array(
            'object' => 'ciniki.musicfestivals.registration',
            'object_id' => $registration['registration_id'],
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
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1746', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
        }

        //
        // FIXME: Make sure member late fee added if applicable
        //
//        if( isset($args['selected_member']['latefee']) ) {
//            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceMemberLateFeeUpdate');
//            $rc = ciniki_musicfestivals_invoiceMemberLateFeeUpdate($ciniki, $tnid, $request['session']['cart']['id'], $args['selected_member']['latefee']);
//            if( $rc['stat'] != 'ok' ) {
//                return $rc;
//            }
//        }

        //
        // Reload cart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
        $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1757', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
        }

        //
        // Success
        //
        ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'transferred');
    }
     
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
