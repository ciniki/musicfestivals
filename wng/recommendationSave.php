<?php
//
// Description
// -----------
// This function will check for differents between an existing recommendation
// and form filled out and update.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_recommendationSave(&$ciniki, $tnid, $request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    if( isset($args['recommendation']) ) {
        $recommendation = $args['recommendation'];
    }
    $form_sections = $args['form_sections'];
    $form_errors = '';

    //
    // Load all existing submissions for member
    //
    if( !isset($args['existing']) ) {
        $strsql = "SELECT recommendations.section_id, "
            . "entries.class_id, "
            . "entries.position "
            . "FROM ciniki_musicfestival_recommendations AS recommendations "
            . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                . "recommendations.id = entries.recommendation_id "
                . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation']['festival_id']) . "' "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation']['member_id']) . "' "
            . "AND recommendations.section_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation']['section_id']) . "' "
            . "AND recommendations.status > 10 "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1048', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        $entries = isset($rc['existing']) ? $rc['existing'] : array();
        $existing = [];
        foreach($entries as $e) {
            $existing[$e['class_id']][$e['position']] = 'Already Submitted';
        }
    } else {
        $existing = $args['existing'];
    }

    //
    // Add a new recommendation
    //
    $recommendation_args = [];
    $fields = [
        'festival_id',
        'member_id',
        'section_id',
        'status',
        'adjudicator_name',
        'adjudicator_phone',
        'adjudicator_email',
        'local_adjudicator_id',
        ];
    foreach($fields as $field) {
        if( isset($form_sections['adjudicator']['fields'][$field]['value'])
            && (!isset($recommendation[$field]) 
            || $recommendation['id'] == 0   // Add new recommendation
            || $form_sections['adjudicator']['fields'][$field]['value'] != $recommendation[$field] 
            )) {
            $recommendation_args[$field] = $form_sections['adjudicator']['fields'][$field]['value'];
        }
        elseif( isset($recommendation[$field]) ) {
            $recommendation_args[$field] = $recommendation[$field];
        }
    }

    //
    // Validate fields
    //
    if( !isset($recommendation_args['member_id']) 
        || trim($recommendation_args['member_id']) == '' 
        || $recommendation_args['member_id'] == 0
        || !isset($args['members'][$recommendation_args['member_id']])
        ) {
        $form_errors .= ($form_errors != '' ? "\n" : '') . "You must specify a Local Festival";
    }
    if( isset($recommendation_args['section_id']) && trim($recommendation_args['section_id']) == '' ) {
        $form_errors .= ($form_errors != '' ? "\n" : '') . "You must specify a syllabus section";
    }
    if( !isset($args['save-draft']) || $args['save-draft'] != 'yes' ) {
        if( isset($recommendation_args['adjudicator_name']) && trim($recommendation_args['adjudicator_name']) == '' ) {
            $form_errors .= ($form_errors != '' ? "\n" : '') . "You must specify an Adjudicator Name";
        }
        if( isset($recommendation_args['adjudicator_phone']) && trim($recommendation_args['adjudicator_phone']) == '' ) {
            $form_errors .= ($form_errors != '' ? "\n" : '') . "You must specify an Adjudicator Phone";
        }
        if( isset($recommendation_args['adjudicator_email']) && trim($recommendation_args['adjudicator_email']) == '' ) {
            $form_errors .= ($form_errors != '' ? "\n" : '') . "You must specify an Adjudicator Email";
        }

        //
        // Check for acknowledgement
        //
        if( !isset($form_sections['submit']['fields']['acknowledgement']['value']) 
            || $form_sections['submit']['fields']['acknowledgement']['value'] != 'on'
            ) {
            $form_errors .= ($form_errors != '' ? "\n" : '') . "You must accept the Acknowledgement";
        }
        $recommendation_args['acknowledgement'] = 'yes';
    }

    if( $form_errors != '' ) {
        return array('stat'=>'fail', 'form_errors'=>$form_errors);
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
    
    if( !isset($recommendation['id']) || $recommendation['id'] == 0 ) {
        $recommendation_args['status'] = 30;
        $recommendation['status'] = 10;
        $recommendation['status_text'] = 'Submitted';
        $recommendation['acknowledgement'] = 'yes';
        if( isset($args['save-draft']) && $args['save-draft'] == 'yes' ) {
            $recommendation_args['status'] = 10;
            $recommendation['status_text'] = 'Draft';
        } else {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $recommendation_args['date_submitted'] = $dt->format('Y-m-d H:i:s');
            $recommendation['date_submitted'] = $dt->format('Y-m-d H:i:s');
        }

        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.recommendation', $recommendation_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1043', 'msg'=>'Unable to add the recommendation', 'err'=>$rc['err']));
        }
        $recommendation['id'] = $rc['id'];
        $recommendation['adjudicator_name'] = $recommendation_args['adjudicator_name'];
        $recommendation['adjudicator_phone'] = $recommendation_args['adjudicator_phone'];
        $recommendation['adjudicator_email'] = $recommendation_args['adjudicator_email'];
    }
    elseif( count($recommendation_args) > 0 ) {
        if( $recommendation['status'] == 30 ) {
            $form_errors = "This recommendation has already been submitted and can no longer be updated.";
            return array('stat'=>'fail', 'form_errors'=>$form_errors);
        }
        if( !isset($args['save-draft']) || $args['save-draft'] != 'yes' ) {
            if( $recommendation['status'] != 30 ) {
                $recommendation_args['status'] = 30;
                $recommendation['status_text'] = 'Submitted';
                $recommendation['acknowledgement'] = 'yes';
            }
            if( $recommendation['date_submitted'] == '' ) {
                $dt = new DateTime('now', new DateTimezone('UTC'));
                $recommendation_args['date_submitted'] = $dt->format('Y-m-d H:i:s');
                $recommendation['date_submitted'] = $dt->format('Y-m-d H:i:s');
            }
        }

        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.recommendation', $recommendation['id'], $recommendation_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1044', 'msg'=>'Unable to update the recommendation', 'err'=>$rc['err']));
        }
    }

    //
    // Find any adds/updated/deletes on recommendation entries
    //
    foreach($form_sections as $form_section) {
        if( !isset($form_section['class_id']) ) {
            continue;
        }
        $cid = $form_section['class_id'];
        $class = $args['classes'][$cid];
        $num_recommendations = 3;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationPositions');
        $rc = ciniki_musicfestivals_recommendationPositions($ciniki, $tnid);
        $positions = $rc['positions'];
        foreach($positions as $i => $position) {
            $entry = [];
            if( $form_section['fields']["recommendation_{$i}_{$cid}"]['ftype'] == 'select' ) {
                // Lookup competitor name 
                $entry['local_reg_id'] = $form_section['fields']["recommendation_{$i}_{$cid}"]['value'];
                if( $entry['local_reg_id'] > 0 ) {
                    foreach($form_section['fields']["recommendation_{$i}_{$cid}"]['options'] as $option) {
                        if( $option['id'] == $entry['local_reg_id'] ) {
                            $entry['name'] = $option['display_name'];
                        }
                    }
                } else {
                    $entry['name'] = '';
                }
            } elseif( $form_section['fields']["recommendation_{$i}_{$cid}"]['ftype'] == 'text' ) {
                $entry['name'] = $form_section['fields']["recommendation_{$i}_{$cid}"]['value'];
            }
            if( isset($form_section['fields']["recommendation_mark_{$i}_{$cid}"]['value']) ) {
                $entry['mark'] = $form_section['fields']["recommendation_mark_{$i}_{$cid}"]['value'];
            } else {
                $entry['mark'] = '';
            }
            if( isset($existing[$cid][$i]) ) {
                if( isset($_POST["f-recommendation_{$i}_{$cid}"]) 
                    && $_POST["f-recommendation_{$i}_{$cid}"] != ''
                    && $_POST["f-recommendation_{$i}_{$cid}"] != 'Already Submitted'
                    ) {
                    $form_errors .= ($form_errors != '' ? "\n" : '') . "You already have a submissions for " . $position['label'] . ' in class ' . $class['code'] . ' - ' . $class['name'];
                }
                $entry['name'] = '';
            }
            if( isset($entry['name']) && $entry['name'] != '' && $entry['mark'] == '' 
                && (!isset($args['save-draft']) || $args['save-draft'] != 'yes')
                ) {
                $form_errors .= ($form_errors != '' ? "\n" : '') . "You must specify a Mark for your " . $position['label'] . ' in class ' . $class['code'] . ' - ' . $class['name'];
                continue;
            }

            if( !isset($entry['name']) || $entry['name'] == '' ) {
                // Check if existing entry needs to be removed
                if( isset($recommendation['entries'][$cid][$i]['id']) 
                    && $recommendation['entries'][$cid][$i]['id'] > 0 
                    ) {
                    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $recommendation['entries'][$cid][$i]['id'], null, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1046', 'msg'=>'Unable to add the recommendationentry', 'err'=>$rc['err']));
                    }
                    unset($recommendation['entries'][$cid][$i]);
                }
                continue;
            } else {
                // Check if entry needs to be updated or added
                if( isset($recommendation['entries'][$cid][$i]['id']) 
                    && $recommendation['entries'][$cid][$i]['id'] > 0
                    ) {
                    $update_args = [];
                    if( isset($entry['name']) && $entry['name'] != $recommendation['entries'][$cid][$i]['name'] ) {
                        $update_args['name'] = $entry['name'];
                    }
                    if( isset($entry['mark']) && $entry['mark'] != $recommendation['entries'][$cid][$i]['mark'] ) {
                        $update_args['mark'] = $entry['mark'];
                    }
                    if( isset($entry['local_reg_id']) && $entry['local_reg_id'] != $recommendation['entries'][$cid][$i]['local_reg_id'] ) {
                        $update_args['local_reg_id'] = $entry['local_reg_id'];
                    }
                    if( count($update_args) > 0 ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $recommendation['entries'][$cid][$i]['id'], $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1045', 'msg'=>'Unable to update the recommendation entry', 'err'=>$rc['err']));
                        }
                    }
                } else {
                    $entry['status'] = 10;
                    $entry['status_text'] = 'Draft';
                    $entry['recommendation_id'] = $recommendation['id'];
                    $entry['class_id'] = $cid;
                    $entry['position'] = $i;
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $entry, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1046', 'msg'=>'Unable to add the recommendationentry', 'err'=>$rc['err']));
                    }
                    $entry['id'] = $rc['id'];
                    $recommendation['entries'][$cid][$i] = $entry;
                }
            }
        }
/*        for($i = 1; $i <= $num_recommendations; $i++) {
            if( isset($existing[$cid][(100+$i)]) ) {
                $form_errors .= ($form_errors != '' ? "\n" : '') . "You already have a submissions for " . $form_section['fields']["recommendation_{$i}_{$cid}"]['label'] . ' in class ' . $class['code'] . ' - ' . $class['name'];
                continue;
            }
            $entry = [];
            if( $form_section['fields']["alternate_{$i}_{$cid}"]['ftype'] == 'select' ) {
                // Lookup competitor name 
                $entry['local_reg_id'] = $form_section['fields']["alternate_{$i}_{$cid}"]['value'];
                if( $entry['local_reg_id'] > 0 ) {
                    foreach($form_section['fields']["alternate_{$i}_{$cid}"]['options'] as $option) {
                        if( $option['id'] == $entry['local_reg_id'] ) {
                            $entry['name'] = $option['display_name'];
                        }
                    }
                } else {
                    $entry['name'] = '';
                }
            } elseif( $form_section['fields']["alternate_{$i}_{$cid}"]['ftype'] == 'text' ) {
                $entry['name'] = $form_section['fields']["alternate_{$i}_{$cid}"]['value'];
            }
            $entry['mark'] = $form_section['fields']["alternate_mark_{$i}_{$cid}"]['value'];
            if( isset($existing[$cid][(100+$i)]) ) {
                if( isset($_POST["f-alternate_{" . (100+$i) . "}_{$cid}"]) 
                    && $_POST["f-alternate_{" . (100+$i) . "}_{$cid}"] != ''
                    && $_POST["f-alternate_{" . (100+$i) . "}_{$cid}"] != 'Already Submitted'
                    ) {
                    $form_errors .= ($form_errors != '' ? "\n" : '') . "You already have a submissions for " . $form_section['fields']["recommendation_{$i}_{$cid}"]['label'] . ' in class ' . $class['code'] . ' - ' . $class['name'];
                }
                $entry['name'] = '';
            }
            if( isset($entry['name']) && $entry['name'] != '' && $entry['mark'] == '' 
                && (!isset($args['save-draft']) || $args['save-draft'] != 'yes')
                ) {
                $form_errors .= ($form_errors != '' ? "\n" : '') . "You must specify a Mark for your " . $form_section['fields']["alternate_{$i}_{$cid}"]['label'] . ' in class ' . $class['code'] . ' - ' . $class['name'];
                continue;
            }

            if( !isset($entry['name']) || $entry['name'] == '' ) {
                // Check if existing entry needs to be removed
                if( isset($recommendation['entries'][$cid][(100+$i)]['id']) 
                    && $recommendation['entries'][$cid][(100+$i)]['id'] > 0 
                    ) {
                    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $recommendation['entries'][$cid][(100+$i)]['id'], null, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1046', 'msg'=>'Unable to add the recommendationentry', 'err'=>$rc['err']));
                    }
                    unset($recommendation['entries'][$cid][(100+$i)]);
                }
                continue;
            } else {
                // Check if entry needs to be updated or added
                if( isset($recommendation['entries'][$cid][(100+$i)]['id']) 
                    && $recommendation['entries'][$cid][(100+$i)]['id'] > 0
                    ) {
                    $update_args = [];
                    if( isset($entry['name']) && $entry['name'] != $recommendation['entries'][$cid][(100+$i)]['name'] ) {
                        $update_args['name'] = $entry['name'];
                    }
                    if( isset($entry['mark']) && $entry['mark'] != $recommendation['entries'][$cid][(100+$i)]['mark'] ) {
                        $update_args['mark'] = $entry['mark'];
                    }
                    if( count($update_args) > 0 ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $recommendation['entries'][$cid][(100+$i)]['id'], $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1045', 'msg'=>'Unable to update the recommendation entry', 'err'=>$rc['err']));
                        }
                    }
                } else {
                    $entry['status'] = 10;
                    $entry['status_text'] = 'Draft';
                    $entry['recommendation_id'] = $recommendation['id'];
                    $entry['class_id'] = $cid;
                    $entry['position'] = (100+$i);
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $entry, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1046', 'msg'=>'Unable to add the recommendationentry', 'err'=>$rc['err']));
                    }
                    $entry['id'] = $rc['id'];
                    $recommendation['entries'][$cid][(100+$i)] = $entry;
                }
            }
        } */
    }

    //
    // Check to make sure if submitting there was at least 1 submission
    //
    if( (!isset($args['save-draft']) || $args['save-draft'] != 'yes')
        &&  count($recommendation['entries']) == 0 && $form_errors == '' 
        ) {
        $form_errors .= "You must specify at least 1 class recommendation.<br/>";
    }

    if( $form_errors != '' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'recommendation'=>$recommendation, 'form_errors'=>$form_errors);
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return array('stat'=>'ok', 'recommendation'=>$recommendation);
}
?>
