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
function ciniki_musicfestivals_accoladeUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'accolade_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Accolade'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'),
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Subcategory'),
//        'typename'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
//        'category'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Category'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'donated_by'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Donated By'),
        'first_presented'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'First Presented'),
        'criteria'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Criteria'),
        'amount'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Amount'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Description'),
        'donor_thankyou_info'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Donor Thank You Info'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT id, "
        . "name "
//        . "typename, "
//        . "category "
        . "FROM ciniki_musicfestival_accolades "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'accolade');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.926', 'msg'=>'Unable to load accolade', 'err'=>$rc['err']));
    }
    if( !isset($rc['accolade']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.927', 'msg'=>'Unable to find requested accolade'));
    }
    $accolade = $rc['accolade'];
    
    //
    // Check name/permalink
    //
    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_musicfestival_accolades "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND category ='" . ciniki_core_dbQuote($ciniki, (isset($args['category']) ? $args['category'] : $accolade['category'])) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['accolade_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.558', 'msg'=>'You already have an accolade with this name, please choose another.'));
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
    // Update the Accolade in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.accolade', $args['accolade_id'], $args, 0x04);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.accolade', 'object_id'=>$args['accolade_id']));

    return array('stat'=>'ok');
}
?>
