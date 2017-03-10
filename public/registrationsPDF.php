<?php
//
// Description
// -----------
// This method will return the excel export of registrations.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Registration for.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationsPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.registrationsPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'classRegistrationsPDF');
    $rc = ciniki_musicfestivals_templates_classRegistrationsPDF($ciniki, $args['business_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($rc['filename'], 'D');
    }

    return array('stat'=>'exit');
}
?>
