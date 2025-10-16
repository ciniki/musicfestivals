<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an competitor.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// competitor_id:          The ID of the competitor to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function ciniki_musicfestivals_competitorHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'competitor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Competitor'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitorHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

/*    if( $args['field'] == 'flags1' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBit');
        return ciniki_core_dbGetModuleHistoryFlagBit($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', 
            $args['tnid'], 'ciniki_musicfestival_competitors', $args['competitor_id'], 'flags', 0x01, 'Unsigned', 'Signed');
    }
    if( $args['field'] == 'flags2' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBit');
        return ciniki_core_dbGetModuleHistoryFlagBit($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', 
            $args['tnid'], 'ciniki_musicfestival_competitors', $args['competitor_id'], 'flags', 0x02, 'No Photos', 'Publish');
    }
    if( $args['field'] == 'flags3' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBit');
        return ciniki_core_dbGetModuleHistoryFlagBit($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', 
            $args['tnid'], 'ciniki_musicfestival_competitors', $args['competitor_id'], 'flags', 0x04, 'Hide Name', 'Publish');
    } */

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectHistory');
    if( $args['field'] == 'flags1' ) {
        return ciniki_core_objectHistory($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', [
            'key' => $args['competitor_id'], 
            'field' => 'flags',
            'flagbit' => 0x01,
            'flagoff' => 'Unsigned',
            'flagon' => 'Signed',
            ]);
    }
    if( $args['field'] == 'flags2' ) {
        return ciniki_core_objectHistory($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', [
            'key' => $args['competitor_id'], 
            'field' => 'flags',
            'flagbit' => 0x02,
            'flagoff' => 'No Photos',
            'flagon' => 'Publish',
            ]);
    }
    if( $args['field'] == 'flags3' ) {
        return ciniki_core_objectHistory($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', [
            'key' => $args['competitor_id'], 
            'field' => 'flags',
            'flagbit' => 0x04,
            'flagoff' => 'Hide Name',
            'flagon' => 'Publish',
            ]);
    }
    if( $args['field'] == 'flags6' ) {
        return ciniki_core_objectHistory($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', [
            'key' => $args['competitor_id'], 
            'field' => 'flags',
            'flagbit' => 0x20,
            'flagoff' => 'Unsigned',
            'flagon' => 'Signed',
            ]);
    }
    if( $args['field'] == 'flags7' ) {
        return ciniki_core_objectHistory($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', [
            'key' => $args['competitor_id'], 
            'field' => 'flags',
            'flagbit' => 0x40,
            'flagoff' => 'Unsigned',
            'flagon' => 'Signed',
            ]);
    }
    return ciniki_core_objectHistory($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', [
        'key' => $args['competitor_id'], 
        'field' => $args['field']
        ]);
//    return ciniki_core_objectHistory($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', $args['competitor_id'], $args['field']);
//    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
//    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $args['tnid'], 'ciniki_musicfestival_competitors', $args['competitor_id'], $args['field']);
}
?>
