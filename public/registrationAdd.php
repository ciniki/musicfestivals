<?php
//
// Description
// -----------
// This method will add a new registration for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the Registration to.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'billing_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing'),
        'rtype'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'),
        'invoice_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
        'display_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'competitor1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 1'),
        'competitor2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 2'),
        'competitor3_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 3'),
        'competitor4_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 4'),
        'competitor5_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 5'),
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'perf_time'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Performance Time'),
        'fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Fee'),
        'payment_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment Type'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.registrationAdd');
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
    // Add the registration to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.musicfestivals.registration', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $registration_id = $rc['id'];

    //
    // Update the display_name for the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
    $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $args['business_id'], $registration_id);
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
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'musicfestivals');

    return array('stat'=>'ok', 'id'=>$registration_id);
}
?>
