<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an schedule time slot.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// scheduletimeslot_id:          The ID of the schedule time slot to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'scheduletimeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( preg_match("/(comments|mark|placement)_([0-9]+)/", $args['field'], $m) ) {
/*        $strsql = "SELECT id "
            . "FROM ciniki_musicfestival_comments "
            . "WHERE registration_id = '" . ciniki_core_dbQuote($ciniki, $m[2]) . "' "
            . "AND adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $m[3]) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'comment');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.169', 'msg'=>'Unable to load comment', 'err'=>$rc['err']));
        }
        if( !isset($rc['comment']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.170', 'msg'=>'Unable to find requested comment'));
        }
        $comment = $rc['comment']; */

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_registrations', $m[2], $m[1]);
        
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_schedule_timeslots', $args['scheduletimeslot_id'], $args['field']);
}
?>
