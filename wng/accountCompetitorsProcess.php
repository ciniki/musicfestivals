<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountCompetitorsProcess(&$ciniki, $tnid, &$request, $args) {

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/competitors';
    $display = 'list';

    //
    // Check for a cancel
    //
    if( isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel' ) {
        if( isset($request['session']['account-musicfestivals-competitor-form-return']) ) {
            header("Location: {$request['session']['account-musicfestivals-competitor-form-return']}");
            exit;
        }
        header("Location: {$base_url}");
        exit;
    }

    //
    // Check for a request to add competitor from registration form
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'addcompetitor' ) {
        $request['session']['account-musicfestivals-registration-saved'] = $_POST;
        $return_url = $request['ssl_domain_base_url'] . '/account/musicfestival/registrations';
        $request['session']['account-musicfestivals-competitor-form-return'] = $return_url;
    }
    elseif( isset($_POST['f-action']) && $_POST['f-action'] == 'editcompetitor' ) {
        $request['session']['account-musicfestivals-registration-saved'] = $_POST;
        $return_url = $request['ssl_domain_base_url'] . '/account/musicfestival/registrations';
        $request['session']['account-musicfestivals-competitor-form-return'] = $return_url;
    }

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.424', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Check for any sections that have different end date
    //
    if( ($festival['flags']&0x09) == 0x09 ) {
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "sections.live_end_dt, "
            . "sections.virtual_end_dt "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (sections.flags&0x01) = 0 "
            . "ORDER BY sections.sequence, sections.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'section_id', 
                'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'live_end_dt', 'virtual_end_dt'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.298', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();

        //
        // Check for sections that are still open
        //
        $dt = new DateTime('now', new DateTimezone('UTC'));
        foreach($sections as $sid => $section) {
            if( $festival['live'] == 'no' && $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
                $live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
                if( $live_dt > $dt ) {
                    $festival['live'] = 'sections';
                }
            }
            if( $festival['live'] == 'no' && $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
                $virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
                if( $live_dt > $dt ) {
                    $festival['virtual'] = 'sections';
                }
            }
        }
    }

    if( !isset($festival['waiver-general-title']) || $festival['waiver-general-title'] == '' ) {
        $festival['waiver-general-title'] = 'Terms and Conditions';
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
    // Keep track of any errors that have occured
    //
    $errors = array();

    //
    // Check if competitor specified and load
    //
    $ctype = 10;
    if( isset($_POST['f-competitor_id']) && $_POST['f-competitor_id'] > 0 ) {
        $competitor_id = $_POST['f-competitor_id'];
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
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $competitor_id) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.355', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
        }
        if( !isset($rc['competitor']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.356', 'msg'=>'Unable to find requested competitor'));
            $errors[] = array(
                'msg' => 'Unable to find the specified customer',
                );
            $display = 'list';
        } else {
            $competitor = $rc['competitor'];
            $ctype = $rc['competitor']['ctype'];
            $display = 'form';
        }
    }
    elseif( isset($_GET['add']) && $_GET['add'] == 'group' ) {
        $ctype = 50;
    }

    //
    // Setup the fields for the competitor
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'competitorFormGenerate');
    $rc = ciniki_musicfestivals_wng_competitorFormGenerate($ciniki, $tnid, $request, [
        'ctype' => $ctype,
        'customer_type' => $customer_type,
        'festival' => $festival,
        'competitor' => isset($competitor) ? $competitor : null,
        ]); 
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1355', 'msg'=>'Unable to generate form', 'err'=>$rc['err']));
    }
    $fields = $rc['fields'];

    //
    // Check if the form is submitted
    //
    if( isset($_POST['f-competitor_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'update' && count($errors) == 0 ) {
        $display = 'form';
        $competitor_id = $_POST['f-competitor_id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'competitorFormUpdateProcess');
        $rc = ciniki_musicfestivals_wng_competitorFormUpdateProcess($ciniki, $tnid, $request, [
            'ctype' => $ctype,
            'customer_type' => $customer_type,
            'festival' => $festival,
            'fields' => $fields,
            'competitor_id' => $_POST['f-competitor_id'],
            'competitor' => isset($competitor) ? $competitor : null,
            ]);
        if( $rc['stat'] == 'exit' ) {
            return $rc;
        } elseif( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>[[
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Internal Error, please try again or contact us for help.',
                ]]);
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1365', 'msg'=>'Unable to process form', 'err'=>$rc['err']));
        }
        if( isset($rc['errors']) ) {
            $errors = $rc['errors'];
        }
    }
    elseif( isset($_POST['f-delete']) && $_POST['f-delete'] == 'Remove' && isset($competitor) ) {
        //
        // Load the number of registrations for the competitor
        //
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.648', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num']) ? $rc['num'] : '';

        if( $num_items > 0 ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => 'limit-width limit-width-70',
                'level' => 'error',
                'content' => "There are still {$num_items} registration" . ($num_items > 1 ? 's' : '') . " for {$competitor['name']}, they cannot be removed.",
                );
            $display = 'list';
        } elseif( isset($_POST['submit']) && $_POST['submit'] == "Remove {$festival['competitor-label-singular']}"
            && isset($_POST['f-action']) && $_POST['f-action'] == 'confirmdelete'
            ) {
            $display = 'list';
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $competitor['competitor_id'], $competitor['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.359', 'msg'=>'Unable to remove competitor', 'err'=>$rc['err']));
            }
            header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/competitors");
            exit;
        } else {
            $display = 'delete';
        }
    }
    elseif( isset($_GET['add']) && ($_GET['add'] == 'individual' || $_GET['add'] == 'group') ) {
        $competitor_id = 0;
        if( $customer_type == 10 ) {
            $fields['parent']['value'] = $request['session']['customer']['display_name'];
        } elseif( $customer_type == 30 ) {
            if( $ctype == 50 ) {
                $fields['name']['value'] = $request['session']['customer']['display_name'];
            } else {
                $fields['first']['value'] = $request['session']['customer']['first'];
                $fields['last']['value'] = $request['session']['customer']['last'];
            }
        }
        if( $customer_type == 10 || $customer_type == 30 ) {
            $fields['email']['value'] = $request['session']['customer']['email'];
            //
            // Lookup address
            //
            $strsql = "SELECT address1, "
                . "address2, "
                . "city, "
                . "province, "
                . "postal, "
                . "country "
                . "FROM ciniki_customer_addresses "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY flags DESC "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'address');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.260', 'msg'=>'Unable to load address', 'err'=>$rc['err']));
            }
            if( isset($rc['address']) ) {
                $address = $rc['address']['address1'];
                if( $rc['address']['address2'] != '' ) {
                    $address .= ($address != '' ? ', ' : '') . $rc['address']['address2'];
                }
                $fields['address']['value'] = $address;
                $fields['city']['value'] = $rc['address']['city'];
                $fields['province']['value'] = $rc['address']['province'];
                $fields['postal']['value'] = $rc['address']['postal'];
                $fields['country']['value'] = $rc['address']['country'];
            }
            //
            // Lookup phones
            //
            $strsql = "SELECT phone_label, "
                . "phone_number "
                . "FROM ciniki_customer_phones "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND ciniki_customer_phones.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY flags DESC "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'address');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.360', 'msg'=>'Unable to load address', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $phone) {
                    if( $phone['phone_label'] == 'Cell' ) {
                        $fields['phone_cell']['value'] = $phone['phone_number'];
                    }
                    if( $phone['phone_label'] == 'Home' ) {
                        $fields['phone_home']['value'] = $phone['phone_number'];
                    }
                }
            }
        }
        $display = 'form';
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
   
    //
    // Show the competitor edit/add form
    //
    if( $display == 'form' ) {
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

        $blocks[] = array(
            'type' => 'form',
            'guidelines' => $guidelines,
            'title' => ($competitor_id > 0 ? 'Update' : 'Add') . ($ctype == 50 ? ' Group/Ensemble' : " Individual {$festival['competitor-label-singular']}"),
            'class' => 'limit-width limit-width-70',
            'problem-list' => $form_errors,
            'cancel-label' => 'Cancel',
            'submit-label' => ($competitor_id > 0 ? 'Save' : 'Save'),
            'fields' => $fields,
            );
    }
    //
    // Show the delete form
    //
    elseif( $display == 'delete' ) {
        $blocks[] = array(
            'type' => 'form',
            'title' => "Remove {$festival['competitor-label-singular']}",
            'class' => 'limit-width limit-width-50',
            'cancel-label' => 'Cancel',
            'submit-label' => "Remove {$festival['competitor-label-singular']}",
            'fields' => array(
                'competitor_id' => array(
                    'id' => 'competitor_id',
                    'ftype' => 'hidden',
                    'value' => $competitor['competitor_id'],
                    ),
                'delete' => array(
                    'id' => 'delete',
                    'ftype' => 'hidden',
                    'value' => 'Remove',
                    ),
                'action' => array(
                    'id' => 'action',
                    'ftype' => 'hidden',
                    'value' => 'confirmdelete',
                    ),
                'msg' => array(
                    'id' => 'content',
                    'ftype' => 'content',
                    'label' => 'Are you sure you want to remove ' . $competitor['name'] . '?',
                    ),
                ),
            );
    }
    //
    // Show the list of competitors
    //
    else {
        if( $form_errors != '' ) { 
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => $form_errors,
                );
        }
        if( count($competitors) > 0 ) {
            $add_button = '';
            //if( ($festival['flags']&0x01) == 0x01 ) {
            if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] != 'no' || $festival['virtual'] != 'no') ) {
                foreach($competitors as $cid => $competitor) {
                    $competitors[$cid]['editbutton'] = "<form action='{$base_url}' method='POST'>"
                        . "<input type='hidden' name='f-competitor_id' value='{$cid}' />"
                        . "<input type='hidden' name='action' value='edit' />"
                        . "<input class='button' type='submit' name='submit' value='Edit'>"
                        . "<input class='button' type='submit' name='f-delete' value='Remove'>"
                        . "</form>";
                }
//                $add_button = "<a class='button' href='{$request['ssl_domain_base_url']}/account/musicfestival/competitors?add=yes'>Add</a>";
            }
            $columns = array(
                array('label' => 'Name', 'field' => 'name', 'class' => 'alignleft')
                );
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                $columns[] = array('label' => 'Pronoun', 'field' => 'pronoun', 'class' => 'alignleft');
            }
            if( $customer_type != 30 ) {
                $columns[] = array('label' => 'Parent', 'fold-label'=>'Parent:', 'field' => 'parent', 'class' => 'alignleft');
            }
            $columns[] = array('label' => 'Age', 'fold-label'=>'Age:', 'field' => 'age', 'class' => 'alignleft');
            if( (isset($festival['competitor-individual-instrumenut']) 
                && in_array($festival['competitor-individual-instrument'], ['optional', 'required']) )
                || (isset($festival['competitor-group-instrumenut']) 
                && in_array($festival['competitor-group-instrument'], ['optional', 'required']) )
                ) {
                $columns[] = array('label' => 'Instrument', 'fold-label'=>'Instrument:', 'field' => 'instrument', 'class' => 'alignleft');
            }
            $columns[] = array('label' => $add_button, 'field' => 'editbutton', 'class' => 'buttons alignright');

            $blocks[] = array(
                'type' => 'table',
                'title' => "{$festival['name']} {$festival['competitor-label-plural']}",
                'class' => 'musicfestival-competitors limit-width limit-width-70 fold-at-40',
                'headers' => 'yes',
                'columns' => $columns,
                'rows' => $competitors,
                );

        } elseif( ($festival['flags']&0x01) == 0 ) {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-40',
                'title' => "{$festival['name']} {$festival['competitor-label-plural']}",
                'content' => 'Registrations closed',
                );
        } else {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-40',
                'title' => "{$festival['name']} {$festival['competitor-label-plural']}",
                'content' => 'No competitors',
                );
        }

        if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') ) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-40 aligncenter',
                'list' => array(
                    array(
                        'text' => 'Add Individual',
                        'url' => "/account/musicfestival/competitors?add=individual",
                        ),
                    array(
                        'text' => 'Add Group/Ensemble',
                        'url' => "/account/musicfestival/competitors?add=group",
                        ),
                    ),
                );
            if( isset($customer_switch_type_block) ) {
                $blocks[] = $customer_switch_type_block;
            }
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
