<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an class.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// class_id:          The ID of the class to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function ciniki_musicfestivals_classHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.classHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'flags9' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBits');
        return ciniki_core_dbGetModuleHistoryFlagBits($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], 'flags', 0x0700);
    }
    if( $args['field'] == 'flags13' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBits');
        return ciniki_core_dbGetModuleHistoryFlagBits($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], 'flags', 0x3000);
    }
    if( $args['field'] == 'flags15' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBits');
        return ciniki_core_dbGetModuleHistoryFlagBits($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], 'flags', 0xC000);
    }
    if( $args['field'] == 'flags17' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBits');
        return ciniki_core_dbGetModuleHistoryFlagBits($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], 'flags', 0x030000);
    }
    if( $args['field'] == 'flags21' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBits');
        return ciniki_core_dbGetModuleHistoryFlagBits($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], 'flags', 0x300000);
    }
    if( preg_match("/^flags([0-9]+)/", $args['field'], $m) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBit');
        return ciniki_core_dbGetModuleHistoryFlagBit($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', 
            $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], 'flags', pow(2, ($m[1]-1)), 'No', 'Yes');
    }

    if( $args['field'] == 'fee' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], $args['field'], 'currency');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_classes', $args['class_id'], $args['field']);
}
?>
