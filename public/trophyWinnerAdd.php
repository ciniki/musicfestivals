<?php
//
// Description
// -----------
// This method will add a new trophy winner for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Trophy Winner to.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophyWinnerAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'trophy_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Trophy'),
        'registration_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Registration'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'awarded_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Awarded Amount'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'year'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Year'),
        'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Internal Year'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    if( (!isset($args['registration_id']) || $args['registration_id'] == '')
        && (!isset($args['name']) || $args['name'] == '') 
        ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.365', 'msg'=>'You must specify a registration or name'));
    }

    if( $args['trophy_id'] == '' || $args['trophy_id'] == 0 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1127', 'msg'=>'You must specify a trophy'));
    }

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophyWinnerAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['registration_id']) && $args['registration_id'] > 0 ) {
        $args['name'] = '';
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

    //
    // Add the trophy winner to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophywinner', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $winner_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.trophyWinner', 'object_id'=>$winner_id));

    return array('stat'=>'ok', 'id'=>$winner_id);
}
?>
