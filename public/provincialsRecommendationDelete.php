<?php
//
// Description
// -----------
// Remove a provincials recommendation that is in draft mode
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsRecommendationDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsRecommendationDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the load festival and provincials festival info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsFestivalMemberLoad');
    $rc = ciniki_musicfestivals_provincialsFestivalMemberLoad($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $rc['member'];
    $provincials_tnid = $member['tnid'];

    //
    // Load the recommendation
    //
    $strsql = "SELECT recommendations.id, "
        . "recommendations.uuid, "
        . "recommendations.status, "
        . "recommendations.section_id "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'recommendation');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1321', 'msg'=>'Unable to load recommendation', 'err'=>$rc['err']));
    }
    if( !isset($rc['recommendation']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1322', 'msg'=>'Unable to find requested recommendation'));
    }
    $recommendation = $rc['recommendation'];

    //
    // Check status of recommendation
    //
    if( $recommendation['status'] > 10 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1323', 'msg'=>'Already submitted, no more recommendations can be added.'));
    }

    //
    // Check to make sure class/position has now already been added for this recommendation
    //
    $strsql = "SELECT entries.id, "
        . "entries.uuid "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "WHERE entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1324', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
    }
    $entries = isset($rc['rows']) ? $rc['rows'] : array();

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
    // Remove the entries from the recommendation
    //
    foreach($entries as $entry) {
        $rc = ciniki_core_objectDelete($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendationentry',
            $entry['id'], $entry['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }


    //
    // Remove the recommendation
    //
    $rc = ciniki_core_objectDelete($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendation',
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
