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
function ciniki_musicfestivals_accoladeWinnerUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'winner_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Accolade Winner'),
        'accolade_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Accolade'),
        'registration_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Registration'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'awarded_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Awarded Amount'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'),
        'year'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Year'),
        'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Internal Notes'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeWinnerUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the winner
    //
    $strsql = "SELECT id, "
        . "accolade_id, "
        . "registration_id, "
        . "flags, "
        . "awarded_amount, "
        . "name, "
        . "year, "
        . "internal_notes "
        . "FROM ciniki_musicfestival_accolade_winners "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'winners', 'fname'=>'id', 
            'fields'=>array('id', 'accolade_id', 'registration_id', 'flags', 'awarded_amount', 'name', 'year', 'internal_notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.408', 'msg'=>'Accolade Winner not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['winners'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.409', 'msg'=>'Unable to find Accolade Winner'));
    }
    $winner = $rc['winners'][0];


    if( (isset($args['accolade_id']) && $args['accolade_id'] != $args['accolade_id'])
        || (isset($args['registration_id']) && $args['registration_id'] != $args['registration_id']) 
        ) {
        //
        // Check to make sure this registration has not already won the specified accolade
        //
        $strsql = "SELECT winners.id "
            . "FROM ciniki_musicfestival_accolade_winners AS winners "
            . "WHERE winners.accolade_id = '" . ciniki_core_dbQuote($ciniki, isset($args['accolade_id']) ? $args['accolade_id'] : $winner['accolade_id']) . "' "
            . "AND winners.registration_id = '" . ciniki_core_dbQuote($ciniki, isset($args['registration_id']) ? $args['registration_id'] : $winner['registration_id']) . "' "
            . "AND winners.id <> '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
            . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1482', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1483', 'msg'=>'This registration has already received this accolade.'));
        }
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
    // Update the Accolade Winner in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladewinner', $args['winner_id'], $args, 0x04);
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.accoladeWinner', 'object_id'=>$args['winner_id']));

    return array('stat'=>'ok');
}
?>
