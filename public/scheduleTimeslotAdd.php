<?php
//
// Description
// -----------
// This method will add a new schedule time slot for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Schedule Time Slot to.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'sdivision_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Division'),
        'slot_time'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'time', 'name'=>'Time'),
        'class1_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class 1'),
        'class2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class 2'),
        'class3_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class 3'),
        'class4_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class 4'),
        'class5_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class 5'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
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
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the of registrations for each class if not split
    //
    if( isset($args['flags']) && ($args['flags'] == 'undefined' || ($args['flags']&0x01) == 0) ) {
        for($i = 1; $i <= 5; $i++) {
            if( isset($args["class{$i}_id"]) && $args["class{$i}_id"] > 0 ) {
                $strsql = "SELECT registrations.id "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "WHERE class_id = '" . ciniki_core_dbQuote($ciniki, $args["class{$i}_id"]) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
                $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.musicfestivals', 'registrations', 'id');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.456', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                }
                $args["registrations{$i}"] = isset($rc['registrations']) ? $rc['registrations'] : array();
            }
        }
    }
    
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
    // Add the schedule time slot to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduletimeslot', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $scheduletimeslot_id = $rc['id'];

    //
    // Add any registrations
    //
    for($i = 1; $i <= 5; $i++) {
        if( isset($args["registrations{$i}"]) ) {
            foreach($args["registrations{$i}"] as $reg_id) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg_id, 
                    array('timeslot_id'=>$scheduletimeslot_id), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.scheduleTimeslot', 'object_id'=>$scheduletimeslot_id));

    return array('stat'=>'ok', 'id'=>$scheduletimeslot_id);
}
?>
