<?php
//
// Description
// -----------
// This method will return the list of Registrations for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of registrations
    //
    $strsql = "SELECT ciniki_musicfestival_registrations.id, "
        . "ciniki_musicfestival_registrations.festival_id, "
        . "ciniki_musicfestival_registrations.teacher_customer_id, "
        . "ciniki_musicfestival_registrations.billing_customer_id, "
        . "ciniki_musicfestival_registrations.rtype, "
        . "ciniki_musicfestival_registrations.status, "
        . "ciniki_musicfestival_registrations.invoice_id, "
        . "ciniki_musicfestival_registrations.display_name, "
        . "ciniki_musicfestival_registrations.competitor1_id, "
        . "ciniki_musicfestival_registrations.competitor2_id, "
        . "ciniki_musicfestival_registrations.competitor3_id, "
        . "ciniki_musicfestival_registrations.competitor4_id, "
        . "ciniki_musicfestival_registrations.competitor5_id, "
        . "ciniki_musicfestival_registrations.class_id, "
        . "ciniki_musicfestival_registrations.title1, "
        . "ciniki_musicfestival_registrations.perf_time1, "
        . "ciniki_musicfestival_registrations.title2, "
        . "ciniki_musicfestival_registrations.perf_time2, "
        . "ciniki_musicfestival_registrations.title3, "
        . "ciniki_musicfestival_registrations.perf_time3, "
        . "ciniki_musicfestival_registrations.fee "
        . "FROM ciniki_musicfestival_registrations "
        . "WHERE ciniki_musicfestival_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'invoice_id',
                'display_name', 'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id', 
                'class_id', 'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3', 
                'fee', 
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['registrations']) ) {
        $registrations = $rc['registrations'];
        $registration_ids = array();
        foreach($registrations as $iid => $registration) {
            $registration_ids[] = $registration['id'];
        }
    } else {
        $registrations = array();
        $registration_ids = array();
    }

    return array('stat'=>'ok', 'registrations'=>$registrations, 'nplist'=>$registration_ids);
}
?>
