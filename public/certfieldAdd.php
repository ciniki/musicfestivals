<?php
//
// Description
// -----------
// This method will add a new certificate field for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Certificate Field to.
//
// Returns
// -------
//
function ciniki_musicfestivals_certfieldAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'certificate_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certificate'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'),
        'xpos'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'X Position'),
        'ypos'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Y Position'),
        'width'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Width'),
        'height'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Height'),
        'font'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Font'),
        'size'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Size'),
        'style'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Style'),
        'align'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Align'),
        'valign'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Vertical Align'),
        'color'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Color'),
        'bgcolor'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Background Color'),
        'text'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Text'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.certfieldAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Add the certificate field to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.certfield', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $field_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.certfield', 'object_id'=>$field_id));

    return array('stat'=>'ok', 'id'=>$field_id);
}
?>
