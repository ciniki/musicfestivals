<?php
//
// Description
// -----------
// This method will add a new accolade for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Accolade to.
//
// Returns
// -------
//
function ciniki_musicfestivals_accoladeAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'),
        'subcategory_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subcategory'),
//        'typename'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Type'),
//        'category'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Category'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'donated_by'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Donated By'),
        'first_presented'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'First Presented'),
        'criteria'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Criteria'),
        'amount'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Amount'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'donor_thankyou_info'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Donor Thank You Info'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Make sure the subcategory exists
    //
    if( !isset($args['subcategory_id']) || $args['subcategory_id'] == '' || $args['subcategory_id'] == 0 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.111', 'msg'=>'You must specify a subcategory'));
    }
    $strsql = "SELECT id "
        . "FROM ciniki_musicfestival_accolade_subcategories "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'subcat');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.112', 'msg'=>'Unable to load subcategory', 'err'=>$rc['err']));
    }
    if( !isset($rc['subcat']) ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1298', 'msg'=>'Subcategory does not exist'));
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_musicfestival_accolades "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND subcategory_id = '" . ciniki_core_dbQuote($ciniki, (isset($args['subcategory_id']) ? $args['subcategory_id'] : '')) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.506', 'msg'=>'You already have an accolade with this name, please choose another.'));
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
    // Add the accolade to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.accolade', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $accolade_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.accolade', 'object_id'=>$accolade_id));

    return array('stat'=>'ok', 'id'=>$accolade_id);
}
?>
