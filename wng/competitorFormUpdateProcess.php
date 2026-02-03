<?php
//
// Description
// -----------
// This will process the form submission for a competitor
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_competitorFormUpdateProcess(&$ciniki, $tnid, &$request, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1360', 'msg'=>"I'm sorry, the page you requested does not exist."));
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
    // Make sure a required args specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1361', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    if( !isset($args['ctype']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1362', 'msg'=>"No competitor type specified"));
    }
    $ctype = $args['ctype'];

    if( !isset($args['customer_type']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1363', 'msg'=>"No customer type specified"));
    }
    $customer_type = $args['customer_type'];

    if( !isset($args['fields']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1364', 'msg'=>"No competitor specified"));
    }
    $fields = $args['fields'];

    if( !isset($args['competitor_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1366', 'msg'=>"No competitor specified"));
    }
    $competitor_id = $args['competitor_id'];

    if( isset($args['competitor']) ) {
        $competitor = $args['competitor'];
    }

    //
    // Process the form
    //
    $errors = [];
    $fields['competitor_id']['value'] = $competitor_id;
    foreach($fields as $field) {
        if( isset($field['required']) && $field['required'] == 'yes' 
            && $field['value'] == '' 
            && $field['id'] != 'termstitle' 
            && $field['id'] != 'terms' 
            && $field['id'] != 'secondwaivertitle' 
            && $field['id'] != 'secondwaiver' 
            && $field['id'] != 'thirdwaivertitle' 
            && $field['id'] != 'thirdwaiver' 
            && $field['id'] != 'photowaiver' 
            && $field['id'] != 'namewaiver' 
            && $field['id'] != 'email_confirm' 
            && $field['id'] != 'etransfer_email_confirm' 
            ) {
            $errors[] = array(
                'msg' => 'You must specify the competitor ' . $field['label'] . '.',
                );
        }
        elseif( isset($field['id']) && $field['id'] == 'email' ) {
            if( !preg_match("/^[^@ ]+@[A-Za-z0-9\.\-]+\.[a-zA-Z]+$/", $field['value']) ) {
                $errors[] = array(
                    'msg' => 'Invalid email address format.',
                    );
            }
            elseif( (($fields['ctype']['value'] == 50
                    && isset($festival['competitor-group-email-confirm']) 
                    && $festival['competitor-group-email-confirm'] == 'yes' 
                ) || ($fields['ctype']['value'] != 50
                    && isset($festival['competitor-individual-email-confirm']) 
                    && $festival['competitor-individual-email-confirm'] == 'yes' 
                ))
                && $field['value'] != $fields['email_confirm']['value'] 
                ) {
                $errors[] = array(
                    'msg' => 'Email address do not match.',
                    );
            }
        }
        elseif( isset($field['id']) && $field['id'] == 'etransfer_email' ) {
            if( $field['value'] != '' && !preg_match("/^[^@ ]+@[A-Za-z0-9\.\-]+\.[a-zA-Z]+$/", $field['value']) ) {
                $errors[] = array(
                    'msg' => 'Invalid etransfer email address format.',
                    );
            }
            elseif( (($fields['ctype']['value'] == 50
                    && isset($festival['competitor-group-etransfer-email-confirm']) 
                    && $festival['competitor-group-etransfer-email-confirm'] == 'yes' 
                ) || ($fields['ctype']['value'] != 50
                    && isset($festival['competitor-individual-etransfer-email-confirm']) 
                    && $festival['competitor-individual-etransfer-email-confirm'] == 'yes' 
                ))
                && $field['value'] != $fields['etransfer_email_confirm']['value'] 
                ) {
                $errors[] = array(
                    'msg' => 'Etransfer emails address do not match.',
                    );
            }
        }
    }
    if( isset($festival['waiver-general-msg']) && $festival['waiver-general-msg'] != '' 
        && (!isset($fields['terms']['value']) || $fields['terms']['value'] != 'on') 
        ) {
        $errors[] = array(
            'msg' => "You must accept the {$festival['waiver-general-title']} for the competitor.",
            );
    }
    if( isset($festival['waiver-second-msg']) && $festival['waiver-second-msg'] != '' 
        && (!isset($fields['secondwaiver']['value']) || $fields['secondwaiver']['value'] != 'on') 
        ) {
        $errors[] = array(
            'msg' => "You must accept the " 
                . (isset($festival['waiver-second-title']) ? $festival['waiver-second-title'] : "waiver")
                . " for the competitor.",
            );
    }
    if( isset($festival['waiver-third-msg']) && $festival['waiver-third-msg'] != '' 
        && (!isset($fields['thirdwaiver']['value']) || $fields['thirdwaiver']['value'] != 'on') 
        ) {
        $errors[] = array(
            'msg' => "You must accept the "
                . (isset($festival['waiver-third-title']) ? $festival['waiver-third-title'] : "waiver")
                . " for the competitor.",
            );
    }
    if( isset($festival['waiver-photo-status']) && $festival['waiver-photo-status'] == 'on' 
        && $fields['photowaiver']['value'] != $fields['photowaiver']['option-2']
        && $fields['photowaiver']['value'] != $fields['photowaiver']['option-3']
        ) {
        $errors[] = array(
            'msg' => "You must select an option for {$festival['waiver-photo-title']} for the competitor.",
            );
    }
    if( isset($festival['waiver-name-status']) && $festival['waiver-name-status'] == 'on' 
        && $fields['namewaiver']['value'] != $fields['namewaiver']['option-2']
        && $fields['namewaiver']['value'] != $fields['namewaiver']['option-3']
        ) {
        $errors[] = array(
            'msg' => "You must select an option for {$festival['waiver-name-title']} for the competitor.",
            );
    }
    //
    // Create name
    //
    if( $ctype == 50 ) {
        $name = $_POST['f-name'];
    } else {
        $name = $_POST['f-first'] . ' ' . $_POST['f-last'];
    }

    //
    // Check for duplicate child
    //
    if( $fields['competitor_id']['value'] == 0 
//            || (isset($_POST['f-name']) && isset($competitor['name']) && $_POST['f-name'] != $competitor['name']) 
        ) {
        //
        // Check for a duplicate name
        //
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM ciniki_musicfestival_competitors AS competitors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND name = '" . ciniki_core_dbQuote($ciniki, $name) . "' "
            . "AND parent = '" . ciniki_core_dbQuote($ciniki, $fields['parent']['value']) . "' "
            . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.354', 'msg'=>'Unable to load the number of items', 'err'=>$rc['err']));
        }
        if( $rc['num'] > 0 ) {
            $errors[] = array(
                'msg' => "You already have a competitor with this name.",
                );
        }
    }
    //
    // If no errors add/update the competitor
    //
    if( count($errors) == 0 ) {
        if( $fields['competitor_id']['value'] == 0 ) {
            //
            // Create the competitor
            //
            $competitor = array(
                'festival_id' => $festival['id'],
                'billing_customer_id' => $request['session']['customer']['id'],
                'ctype' => $fields['ctype']['value'],
                'flags' => 0,
                'parent' => $fields['parent']['value'],
                'address' => $fields['address']['value'],
                'city' => $fields['city']['value'],
                'province' => $fields['province']['value'],
                'postal' => $fields['postal']['value'],
                'country' => $fields['country']['value'],
                'phone_home' => isset($fields['phone_home']['value']) ? $fields['phone_home']['value'] : '',
                'phone_cell' => $fields['phone_cell']['value'],
                'email' => $fields['email']['value'],
                'age' => isset($fields['age']['value']) ? $fields['age']['value'] : '',
                'study_level' => isset($fields['study_level']['value']) ? $fields['study_level']['value'] : '',
                'last_exam' => isset($fields['last_exam']['value']) ? $fields['last_exam']['value'] : '',
                'instrument' => isset($fields['instrument']['value']) ? $fields['instrument']['value'] : '',
                'etransfer_email' => isset($fields['etransfer_email']['value']) ? $fields['etransfer_email']['value'] : '',
                'notes' => isset($fields['comp_notes']['value']) ? $fields['comp_notes']['value'] : '',
                );
            if( isset($fields['terms']['value']) && $fields['terms']['value'] == 'on' ) {
                $competitor['flags'] |= 0x01;
            }
            if( isset($fields['secondwaiver']['value']) && $fields['secondwaiver']['value'] == 'on' ) {
                $competitor['flags'] |= 0x20;
            }
            if( isset($fields['thirdwaiver']['value']) && $fields['thirdwaiver']['value'] == 'on' ) {
                $competitor['flags'] |= 0x40;
            }
            //
            // Default to photo and name flags to set
            // waiver-(photo/name)-status option can be missing, off, internal and they will be set to on by default
            // Only when waiver-*-status set to on will they be visible in form
            //
            if( !isset($fields['photowaiver']['value']) || $fields['photowaiver']['value'] == $fields['photowaiver']['option-2'] ) {
                $competitor['flags'] |= 0x02;
            }
            if( !isset($fields['namewaiver']['value']) || $fields['namewaiver']['value'] == $fields['namewaiver']['option-2'] ) {
                $competitor['flags'] |= 0x04;
            }
            if( $fields['ctype']['value'] == 50 ) {
                $competitor['first'] = '';
                $competitor['last'] = '';
                $competitor['name'] = $fields['name']['value'];
                $competitor['public_name'] = $fields['name']['value'];
                $competitor['pronoun'] = '';
                $competitor['conductor'] = $fields['conductor']['value'];
                $competitor['num_people'] = $fields['num_people']['value'];
            } else {
                $competitor['first'] = $fields['first']['value'];
                $competitor['last'] = $fields['last']['value'];
                $competitor['name'] = $competitor['first'] . ' ' . $competitor['last'];
                $competitor['public_name'] = $competitor['first'][0] . '. ' . $competitor['last'];
                $competitor['pronoun'] = (isset($fields['pronoun']['value']) ? $fields['pronoun']['value'] : '');
                $competitor['conductor'] = '';
                $competitor['num_people'] = '';
            }

            //
            // Add the competitor
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $competitor, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.353', 'msg'=>'Unable to add the competitor', 'err'=>$rc['err']));
            }
            if( isset($args['provincials-competitor-number']) && $args['provincials-competitor-number'] != '' ) {
                $request['session']['musicfestival-registration']["competitor{$args['provincials-competitor-number']}_id"] = $rc['id'];
            }
            if( isset($request['session']['account-musicfestivals-competitor-form-return']) ) {
                $request['session']['account-musicfestivals-registration-saved']['new-id'] = $rc['id'];
                header("Location: {$request['session']['account-musicfestivals-competitor-form-return']}");
                return array('stat'=>'exit');
            }
            header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/competitors");
            exit;
        } 
        else {
            $update_args = array();
            foreach($fields as $field) {
                if( $field['ftype'] == 'content' || $field['ftype'] == 'hidden' || $field['ftype'] == 'newline' || $field['id'] == 'terms' ) {
                    continue;
                }
                if( isset($field['id']) && $field['id'] == 'comp_notes' ) {
                    if( isset($field['value']) && $field['value'] != $competitor['notes'] ) {
                        $update_args['notes'] = $field['value'];
                    }
                }
                elseif( !isset($competitor[$field['id']]) || (isset($field['value']) && $field['value'] != $competitor[$field['id']]) ) {
                    $update_args[$field['id']] = $field['value'];
                }
            }
            $flags = $competitor['flags'];
            if( isset($fields['photowaiver']['value']) ) {
                if( ($competitor['flags']&0x02) == 0 && $fields['photowaiver']['value'] == $fields['photowaiver']['option-2'] ) {
                    $flags |= 0x02;
                } elseif( ($competitor['flags']&0x02) == 0x02 && $fields['photowaiver']['value'] != $fields['photowaiver']['option-2'] ) {
                    $flags = ($competitor['flags']&0xFFFFFFFD);
                }
            }
            if( isset($fields['namewaiver']['value']) ) {
                if( ($competitor['flags']&0x04) == 0 && $fields['namewaiver']['value'] == $fields['namewaiver']['option-2'] ) {
                    $flags |= 0x04;
                } elseif( ($competitor['flags']&0x04) == 0x04 && $fields['namewaiver']['value'] != $fields['namewaiver']['option-2'] ) {
                    $flags = ($competitor['flags']&0xFFFFFFFB);
                }
            }
            if( $flags != $competitor['flags'] ) {
                $update_args['flags'] = $flags;
            }
            if( $ctype == 10 && (isset($update_args['first']) || isset($update_args['last'])) ) {
                $name = (isset($update_args['first']) ? $update_args['first'] : $competitor['first']) 
                    . ' ' . (isset($update_args['last']) ? $update_args['last'] : $competitor['last']);
                $public_name = (isset($update_args['first']) ? $update_args['first'][0] : $competitor['first'][0]) 
                    . ' ' . (isset($update_args['last']) ? $update_args['last'] : $competitor['last']);
                if( $name != $competitor['name'] ) {
                    $update_args['name'] = $name;
                }
                if( $public_name != $competitor['public_name'] ) {
                    $update_args['public_name'] = $public_name;
                }
            }
            elseif( $ctype == 50 && isset($update_args['name']) ) {
                $update_args['public_name'] = $update_args['name'];
            }
            
            //
            // Check if the competitor is part of any submitted/paid registrations and then 
            // nothing can be changed
            //
            $strsql = "SELECT COUNT(*) AS num "
                . "FROM ciniki_musicfestival_registrations "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND status >= 7 "    // E-transfer pending, applied or paid
                . "AND ("
                    . "competitor1_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                    . "OR competitor2_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                    . "OR competitor3_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                    . "OR competitor4_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                    . "OR competitor5_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                    . ") "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.358', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $num_items = isset($rc['num']) ? $rc['num'] : '';

            if( $num_items > 0 ) {
                $blocks[] = array(
                    'type' => 'msg',
                    'class' => 'limit-width limit-width-70',
                    'level' => 'error',
                    'content' => "There " . ($num_items > 1 ? 'are' : 'is') . " {$num_items} registration" . ($num_items > 1 ? 's' : '') . " for {$competitor['name']}. Please contact us with any changes to competitor information.",
                    );
                $display = 'list';
            }
            //
            // Update the competitor
            //
            else {
                if( count($update_args) > 0 ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $competitor_id, $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.357', 'msg'=>'Unable to update the competitor', 'err'=>$rc['err']));
                    }

                    //
                    // Update any registration this competitor is a part of
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'competitorUpdateNames');
                    $rc = ciniki_musicfestivals_competitorUpdateNames($ciniki, $tnid, [
                        'festival_id' => $festival['id'], 
                        'competitor_id' => $competitor_id,
                        ]);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.328', 'msg'=>'Unable to update registrations', 'err'=>$rc['err']));
                    }
                }

                if( isset($request['session']['account-musicfestivals-competitor-form-return']) ) {
                    header("Location: {$request['session']['account-musicfestivals-competitor-form-return']}");
                    exit;
                }
                header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/competitors");
                exit;
            }
        }
    }

    return array('stat'=>'ok', 'errors'=>$errors);
}
?>
