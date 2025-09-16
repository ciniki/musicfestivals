<?php
//
// Description
// -----------
// This method will delete an adjudicator recommendation.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the adjudicator recommendation is attached to.
// recommendation_id:            The ID of the adjudicator recommendation to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_recommendationRemove(&$ciniki, $tnid, $recommendation_id) {

    //
    // Get the entries
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "WHERE entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $recommendation_id) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'entries', 'fname'=>'id', 'fields'=>array('id', 'uuid'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.622', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
    }
    $entries = isset($rc['entries']) ? $rc['entries'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove this submission from any mail entries
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessagesDetach');
    $rc = ciniki_mail_hooks_objectMessagesDetach($ciniki, $tnid, array(
        'object' => 'ciniki.musicfestivals.recommendation',
        'object_id' => $recommendation_id,
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.624', 'msg'=>'Unable to detach from mail messages.', 'err'=>$rc['err']));
    }

    //
    // Remove all the entries
    //
    foreach($entries as $entry) {
        $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry',
            $entry['id'], $entry['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Remove the recommendation
    //
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.recommendation',
        $recommendation_id, null, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
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
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'musicfestivals');

    return array('stat'=>'ok');
}
?>
