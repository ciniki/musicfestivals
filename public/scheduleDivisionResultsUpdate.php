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
function ciniki_musicfestivals_scheduleDivisionResultsUpdate(&$ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Section'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleDivisionResultsUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load conference maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load the registrations
    //
    $strsql = "SELECT timeslots.id AS timeslot_id, "
        . "timeslots.flags AS timeslot_flags, "
        . "timeslots.linked_timeslot_id, "
        . "registrations.id, "
        . "registrations.status, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "registrations.finals_timeslot_id, "
        . "registrations.finals_timeslot_time, "
        . "registrations.finals_timeslot_sequence, "
        . "registrations.finals_mark, "
        . "registrations.finals_placement, "
        . "registrations.finals_level, "
        . "registrations.provincials_status, "
        . "registrations.provincials_position "
        . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "("
                . "((timeslots.flags&0x02) = 0 && timeslots.id = registrations.timeslot_id) "
                . "OR ((timeslots.flags&0x02) = 0x02 && timeslots.id = registrations.finals_timeslot_id) "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
        . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY registrations.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'timeslot_flags', 'linked_timeslot_id', 'status', 
                'mark', 'placement', 'level', 'finals_timeslot_id',
                'provincials_status', 'provincials_position',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.697', 'msg'=>'Unable to load results', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
    $timeslot_update_ids = [];
    foreach($registrations as $rid => $result) {
        $update_args = array();
        if( isset($ciniki['request']['args']["mark_{$rid}"]) ) {
            if( ($result['timeslot_flags']&0x02) == 0x02 ) {
                $update_args['finals_mark'] = $ciniki['request']['args']["mark_{$rid}"];
            } else {
                $update_args['mark'] = $ciniki['request']['args']["mark_{$rid}"];
            }
        }
        if( isset($ciniki['request']['args']["placement_{$rid}"]) ) {
            if( ($result['timeslot_flags']&0x02) == 0x02 ) {
                $update_args['finals_placement'] = $ciniki['request']['args']["placement_{$rid}"];
            } else {
                $update_args['placement'] = $ciniki['request']['args']["placement_{$rid}"];
            }
            //
            // Check if registration should be added to finals timeslot
            //
            if( ($result['timeslot_flags']&0x01) == 0x01 
                && isset($festival['scheduling-linked-playoffs-placement'])
                && $festival['scheduling-linked-playoffs-placement'] == $update_args['placement']
                && $result['finals_timeslot_id'] != $result['linked_timeslot_id']
                ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationFinalsAssign');
                $rc = ciniki_musicfestivals_registrationFinalsAssign($ciniki, $args['tnid'], [
                    'registration' => $result,
                    'festival' => $festival,
                    'finals_timeslot_id' => $result['linked_timeslot_id'],
                    ]);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.130', 'msg'=>'', 'err'=>$rc['err']));
                }
            }
            elseif( ($result['timeslot_flags']&0x01) == 0x01 
                && isset($festival['scheduling-linked-playoffs-placement'])
                && $festival['scheduling-linked-playoffs-placement'] != $update_args['placement']
                && $result['finals_timeslot_id'] == $result['linked_timeslot_id']
                ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationFinalsUnassign');
                $rc = ciniki_musicfestivals_registrationFinalsUnassign($ciniki, $args['tnid'], [
                    'registration' => $result,
                    'festival' => $festival,
                    'finals_timeslot_id' => $result['linked_timeslot_id'],
                    ]);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1032', 'msg'=>'', 'err'=>$rc['err']));
                }
            }
        }
        if( isset($ciniki['request']['args']["level_{$rid}"]) ) {
            if( ($result['timeslot_flags']&0x02) == 0x02 ) {
                $update_args['finals_level'] = $ciniki['request']['args']["level_{$rid}"];
            } else {
                $update_args['level'] = $ciniki['request']['args']["level_{$rid}"];
            }
        }
        if( isset($ciniki['request']['args']["noshow_{$rid}"]) ) {
            if( $ciniki['request']['args']["noshow_{$rid}"] == 'yes' && $result['status'] != 77 ) {
                $update_args['status'] = 77;
            } elseif( $ciniki['request']['args']["noshow_{$rid}"] == 'no' && $result['status'] == 77 ) {
                // Get last status from history
                $strsql = "SELECT new_value "
                    . "FROM ciniki_musicfestivals_history "
                    . "WHERE table_name = 'ciniki_musicfestival_registrations' "
                    . "AND table_key = '" . ciniki_core_dbQuote($ciniki, $result['id']) . "' "
                    . "AND table_field = 'status' "
                    . "AND new_value != '77' "
                    . "ORDER BY log_date DESC "
                    . "LIMIT 1 "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.315', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                }
                if( !isset($rc['item']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.316', 'msg'=>'Unable to find requested item'));
                }
                $item = $rc['item'];
                $update_args['status'] = $item['new_value'];
            }
        }
        if( isset($ciniki['request']['args']["provincials_status_{$rid}"]) ) {
            $update_args['provincials_status'] = $ciniki['request']['args']["provincials_status_{$rid}"];
        }
        if( isset($ciniki['request']['args']["provincials_position_{$rid}"]) ) {
            $update_args['provincials_position'] = $ciniki['request']['args']["provincials_position_{$rid}"];
        }
        if( count($update_args) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $rid, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
