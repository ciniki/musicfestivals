<?php
//
// Description
// -----------
// This method will add a new schedule section for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Schedule Section to.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleSectionAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'adjudicator1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Adjudicator'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'top_sponsors_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Top Sponsors Title'),
        'top_sponsor_ids'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Top Sponsors'),
        'bottom_sponsors_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bottom Sponsors Title'),
        'bottom_sponsors_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bottom Content'),
        'bottom_sponsor_ids'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bottom Sponsors'),
        'provincials_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Title'),
        'provincials_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Content'),
        'provincials_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Image ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleSectionAdd');
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
    // Add the schedule section to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.schedulesection', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $schedulesection_id = $rc['id'];

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

    return array('stat'=>'ok', 'id'=>$schedulesection_id);
}
?>
