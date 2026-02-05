<?php
//
// Description
// -----------
// This method will add a new volunteer for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Volunteer to.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'shortname'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Name'),
        'local_festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Local Festival'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Internal Notes'),
        'available_days'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Available Days'),
        'available_times'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Available Times'),
        'skills'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Skills'),
        'disciplines'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Disciplines'),
        'approved_roles'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Approved Roles'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check the customer_id exists
    //
    $strsql = "SELECT display_name "
        . "FROM ciniki_customers "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1229', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1230', 'msg'=>'No customer specified'));
    }

    //
    // Check if the customer is already a volunteer
    //
    $strsql = "SELECT id "
        . "FROM ciniki_musicfestival_volunteers "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    
    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

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
    // Add the volunteer to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteer', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $volunteer_id = $rc['id'];

    //
    // Update the tags
    //
    if( isset($args['available_days']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $args['tnid'],
            'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
            'volunteer_id', $volunteer_id, 10, $args['available_days']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }
    if( isset($args['available_times']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $args['tnid'],
            'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
            'volunteer_id', $volunteer_id, 20, $args['available_times']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }
    if( isset($args['skills']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $args['tnid'],
            'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
            'volunteer_id', $volunteer_id, 30, $args['skills']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }
    if( isset($args['disciplines']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $args['tnid'],
            'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
            'volunteer_id', $volunteer_id, 35, $args['disciplines']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }
    if( isset($args['approved_roles']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $args['tnid'],
            'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
            'volunteer_id', $volunteer_id, 50, $args['approved_roles']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Check if approved email should be sent
    //
    if( isset($args['status']) && $args['status'] == 30 
        && isset($festival["volunteers-email-approved-subject"])
        && $festival["volunteers-email-approved-subject"] != ''
        && isset($festival["volunteers-email-approved-message"])
        && $festival["volunteers-email-approved-message"] != ''
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerEmail');
        $rc = ciniki_musicfestivals_volunteerEmail($ciniki, $args['tnid'], [
            'volunteer_id' => $volunteer_id,
            'festival' => $festival,
            'template' => 'volunteers-email-approved',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1378', 'msg'=>'Unable to email volunteer', 'err'=>$rc['err']));
        }
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.volunteer', 'object_id'=>$volunteer_id));

    return array('stat'=>'ok', 'id'=>$volunteer_id);
}
?>
