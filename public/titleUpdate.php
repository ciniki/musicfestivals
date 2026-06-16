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
function ciniki_musicfestivals_titleUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'title_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Approved Title'),
        'list_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'List'),
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'),
        'movements'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Movements/Musical'),
        'composer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Composer'),
        'source_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Source Type'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['list_id']) && ($args['list_id'] == '' || $args['list_id'] <= 0) ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1159', 'msg'=>'You must choose a list'));
    }

    //
    // Load the current title
    //
    $strsql = "SELECT titles.id, "
        . "titles.list_id, "
        . "titles.title, "
        . "titles.movements, "
        . "titles.composer, "
        . "titles.source_type, "
        . "titles.keywords "
        . "FROM ciniki_musicfestivals_titles AS titles "
        . "WHERE titles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND titles.id = '" . ciniki_core_dbQuote($ciniki, $args['title_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'title');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1655', 'msg'=>'Unable to load title', 'err'=>$rc['err']));
    }
    if( !isset($rc['title']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1656', 'msg'=>'Unable to find requested title'));
    }
    $title = $rc['title'];

    //
    // Create the keywords
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');
    $rc = ciniki_musicfestivals_titleListKeywordsMake($ciniki, $args['tnid'], [
        'title'=>[
            'title' => isset($args['title']) ? $args['title'] : $title['title'],
            'movements' => isset($args['movements']) ? $args['movements'] : $title['movements'],
            'composer' => isset($args['composer']) ? $args['composer'] : $title['composer'],
            'source_type' => isset($args['source_type']) ? $args['source_type'] : $title['source_type'],
            ],
        ]);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }
    if( $title['keywords'] != $rc['keywords'] ) {
        $args['keywords'] = $rc['keywords'];
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
    // Update the Approved Title in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.title', $args['title_id'], $args, 0x04);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.title', 'object_id'=>$args['title_id']));

    return array('stat'=>'ok');
}
?>
