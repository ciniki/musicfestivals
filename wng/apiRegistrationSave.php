<?php
//
// Description
// -----------
// Auto save the adjudications form
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_apiRegistrationSave(&$ciniki, $tnid, $request) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Make sure customer is logged in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1715', 'msg'=>'Not signed in'));
    }
    $customer_id = $request['session']['customer']['id'];

    //
    // Make sure festival specified
    //
    if( !isset($_POST['festival-id']) || $_POST['festival-id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1716', 'msg'=>'No festival specified'));
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountRegistrationsProcess');
    $rc = ciniki_musicfestivals_wng_accountRegistrationsProcess($ciniki, $tnid, $request, ['format'=>'json']);
    if( $rc['stat'] == 'ok' ) {
        ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    } else {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
    }

    return $rc;
}
?>
