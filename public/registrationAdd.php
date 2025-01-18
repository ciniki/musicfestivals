<?php
//
// Description
// -----------
// This method will add a new registration for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Registration to.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'billing_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing'),
        'accompanist_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accompanist'),
        'member_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member'),
        'rtype'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'invoice_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
        'display_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'competitor1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 1'),
        'competitor2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 2'),
        'competitor3_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 3'),
        'competitor4_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 4'),
        'competitor5_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 5'),
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'title1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'composer1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Composer'),
        'movements1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Movements'),
        'perf_time1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Performance Time'),
        'title2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2nd Title'),
        'composer2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2nd Composer'),
        'movements2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2nd Movements'),
        'perf_time2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2nd Performance Time'),
        'title3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'3rd Title'),
        'composer3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'3rd Composer'),
        'movements3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'3rd Movements'),
        'perf_time3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'3rd Performance Time'),
        'title4'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'4th Title'),
        'composer4'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'4th Composer'),
        'movements4'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'4th Movements'),
        'perf_time4'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'4th Performance Time'),
        'title5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'5th Title'),
        'composer5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'5th Composer'),
        'movements5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'5th Movements'),
        'perf_time5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'5th Performance Time'),
        'title6'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'6th Title'),
        'composer6'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'6th Composer'),
        'movements6'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'6th Movements'),
        'perf_time6'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'6th Performance Time'),
        'title7'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'7th Title'),
        'composer7'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'7th Composer'),
        'movements7'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'7th Movements'),
        'perf_time7'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'7th Performance Time'),
        'title8'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'8th Title'),
        'composer8'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'8th Composer'),
        'movements8'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'8th Movements'),
        'perf_time8'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'8th Performance Time'),
        'fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Fee'),
        'participation'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Virtual'),
        'video_url1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url4'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url6'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url7'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url8'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'instrument'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Instrument'),
        'placement'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Placement'),
        'level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Interal Notes'),
        'runsheet_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Runsheet Notes'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationAdd');
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
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $registration_id = $rc['id'];

    //
    // Update the display_name for the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
    $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $args['tnid'], $registration_id);
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

    return array('stat'=>'ok', 'id'=>$registration_id);
}
?>
