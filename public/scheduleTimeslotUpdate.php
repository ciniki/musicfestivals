<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'scheduletimeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Division'),
        'slot_time'=>array('required'=>'no', 'blank'=>'no', 'type'=>'time', 'name'=>'Time'),
        'class1_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 1'),
        'class2_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 2'),
        'class3_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 3'),
        'class4_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 4'),
        'class5_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class 5'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'runsheet_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Runsheet Notes'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'registrations1'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Registrations 1'),
        'registrations2'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Registrations 2'),
        'registrations3'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Registrations 3'),
        'registrations4'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Registrations 4'),
        'registrations5'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Registrations 5'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current timeslot flags
    //
    $strsql = "SELECT timeslots.id, "
        . "timeslots.festival_id, "
        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "timeslots.class4_id, "
        . "timeslots.class5_id, "
        . "timeslots.flags "
        . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'scheduletimeslot');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.457', 'msg'=>'Unable to load scheduletimeslot', 'err'=>$rc['err']));
    }
    if( !isset($rc['scheduletimeslot']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.458', 'msg'=>'Unable to find requested scheduletimeslot'));
    }
    $scheduletimeslot = $rc['scheduletimeslot'];

    //
    // Get the of registrations for each class if not split
    //
    $flags = $scheduletimeslot['flags'];
    if( isset($args['flags']) ) {   
        $flags = $args['flags'];
    }
    //
    // Full classes
    //
    if( ($flags&0x01) == 0 ) {
        for($i = 1; $i <= 5; $i++) {
            if( (isset($args["class{$i}_id"]) && $args["class{$i}_id"] > 0) 
                || (!isset($args["class{$i}_id"]) && $scheduletimeslot["class{$i}_id"] > 0) 
                ) {
                $strsql = "SELECT registrations.id "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "WHERE class_id = '" . ciniki_core_dbQuote($ciniki, isset($args["class{$i}_id"]) ? $args["class{$i}_id"] : $scheduletimeslot["class{$i}_id"]) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
                $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.musicfestivals', 'registrations', 'id');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.540', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                }
                $args["registrations{$i}"] = isset($rc['registrations']) ? $rc['registrations'] : array();
            }
        }
    } 
    //
    // Split Classes
    //
    else {
        for($i = 1; $i <= 5; $i++) {
            if( !isset($args["registrations{$i}"]) 
                && ((isset($args["class{$i}_id"]) && $args["class{$i}_id"] > 0) 
                || (!isset($args["class{$i}_id"]) && $scheduletimeslot["class{$i}_id"] > 0) 
                )) {
                $strsql = "SELECT registrations.id "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "WHERE class_id = '" . ciniki_core_dbQuote($ciniki, isset($args["class{$i}_id"]) ? $args["class{$i}_id"] : $scheduletimeslot["class{$i}_id"]) . "' "
                    . "AND timeslot_id = '" . ciniki_core_dbQuote($ciniki,$args['scheduletimeslot_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
                $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.musicfestivals', 'registrations', 'id');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.539', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                }
                $args["registrations{$i}"] = isset($rc['registrations']) ? $rc['registrations'] : array();
            }
            //
            // Only accept registrations when there is a class specified
            //
            elseif( isset($args["registrations{$i}"]) 
                && count($args["registrations{$i}"]) > 0 
                && ((isset($args["class{$i}_id"]) && $args["class{$i}_id"] == 0)
                    || (!isset($args["class{$i}_id"]) && $scheduletimeslot["class{$i}_id"] == 0) 
                    )
                ) {
                // This fixes bug when split class removed from timeslot and registrations left hanging on
                $args["registrations{$i}"] = array();
            }
        }
    }
        error_log(print_r($args,true));
    
    //
    // Get the current list of registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.timeslot_sequence "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 'fields'=>array('id', 'timeslot_sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.534', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
     
/*    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.musicfestivals', 'registrations', 'id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $registrations = (isset($rc['registrations']) ? $rc['registrations'] : array());
*/

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Schedule Time Slot in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduletimeslot', $args['scheduletimeslot_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Add any registrations
    //
    if( isset($args['registrations1']) ) {
        foreach($args['registrations1'] as $reg_id) {
            if( !isset($registrations[$reg_id]) ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg_id, array('timeslot_id'=>$args['scheduletimeslot_id']), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }
    if( isset($args['registrations2']) ) {
        foreach($args['registrations2'] as $reg_id) {
            if( !isset($registrations[$reg_id]) ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg_id, array('timeslot_id'=>$args['scheduletimeslot_id']), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }
    if( isset($args['registrations3']) ) {
        foreach($args['registrations3'] as $reg_id) {
            if( !isset($registrations[$reg_id]) ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg_id, array('timeslot_id'=>$args['scheduletimeslot_id']), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }
    if( isset($args['registrations4']) ) {
        foreach($args['registrations4'] as $reg_id) {
            if( !isset($registrations[$reg_id]) ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg_id, array('timeslot_id'=>$args['scheduletimeslot_id']), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }
    if( isset($args['registrations5']) ) {
        foreach($args['registrations5'] as $reg_id) {
            if( !isset($registrations[$reg_id]) ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg_id, array('timeslot_id'=>$args['scheduletimeslot_id']), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }


    //
    // Combine all registrations
    //
    $args['registrations'] = array();
    if( isset($args['registrations1']) ) {
        foreach($args['registrations1'] as $reg) {
            $args['registrations'][] = $reg;
        }
    }
    if( isset($args['registrations2']) ) {
        foreach($args['registrations2'] as $reg) {
            $args['registrations'][] = $reg;
        }
    }
    if( isset($args['registrations3']) ) {
        foreach($args['registrations3'] as $reg) {
            $args['registrations'][] = $reg;
        }
    }
    if( isset($args['registrations4']) ) {
        foreach($args['registrations4'] as $reg) {
            $args['registrations'][] = $reg;
        }
    }
    if( isset($args['registrations5']) ) {
        foreach($args['registrations5'] as $reg) {
            $args['registrations'][] = $reg;
        }
    }

    //
    // Remove any registrations
    //
    foreach($registrations as $reg_id => $reg) {
        if( !in_array($reg_id, $args['registrations']) ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg_id, array('timeslot_id'=>0), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check for any sequence updates
    //
    foreach($ciniki['request']['args'] as $k => $v) {   
        if( preg_match("/^seq_(.*)$/", $k, $m) ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $m[1], array('timeslot_sequence'=>$v), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.scheduleTimeslot', 'object_id'=>$args['scheduletimeslot_id']));

    return array('stat'=>'ok');
}
?>
