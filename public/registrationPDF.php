<?php
//
// Description
// -----------
// This method returns the PDF for one registration.
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
function ciniki_musicfestivals_registrationPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'registrationsPDF');
    $rc = ciniki_musicfestivals_templates_registrationsPDF($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        'registration_ids' => [$args['registration_id']],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($rc['filename'], 'I');
    }

    return array('stat'=>'exit');
}
?>
