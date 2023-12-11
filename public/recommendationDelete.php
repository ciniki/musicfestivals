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
function ciniki_musicfestivals_recommendationDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Adjudicator Recommendation'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the adjudicator recommendation
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_recommendations "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'recommendation');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['recommendation']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.601', 'msg'=>'Adjudicator Recommendation does not exist.'));
    }
    $recommendation = $rc['recommendation'];
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'recommendation');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $entries = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "WHERE entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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

    if( count($entries) > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.623', 'msg'=>'There are still entries for this submission, they must be removed first.'));
    }

    //
    // Remove this submission from any mail entries
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessagesDetach');
    $rc = ciniki_mail_hooks_objectMessagesDetach($ciniki, $args['tnid'], array(
        'object' => 'ciniki.musicfestivals.recommendation',
        'object_id' => $args['recommendation_id'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.624', 'msg'=>'Unable to detach from mail messages.', 'err'=>$rc['err']));
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendation', $args['recommendation_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.602', 'msg'=>'Unable to check if the adjudicator recommendation is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.603', 'msg'=>'The adjudicator recommendation is still in use. ' . $rc['msg']));
    }

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
    // Remove all the entries
    //
    foreach($entries as $entry) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationentry',
            $entry['id'], $entry['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Remove the recommendation
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendation',
        $args['recommendation_id'], $recommendation['uuid'], 0x04);
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
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    return array('stat'=>'ok');
}
?>
