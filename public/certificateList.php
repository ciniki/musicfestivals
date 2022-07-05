<?php
//
// Description
// -----------
// This method will return the list of Certificates for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Certificate for.
//
// Returns
// -------
//
function ciniki_musicfestivals_certificateList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.certificateList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of certificates
    //
    $strsql = "SELECT certificates.id, "
        . "certificates.festival_id, "
        . "certificates.name, "
        . "certificates.section_id, "
        . "certificates.min_score "
        . "FROM ciniki_musicfestivals_certificates AS certificates "
        . "WHERE certificates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'certificates', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'section_id', 'min_score')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $certificates = isset($rc['certificates']) ? $rc['certificates'] : array();
    $certificate_ids = array();
    foreach($certificates as $iid => $certificate) {
        $certificate_ids[] = $certificate['id'];
    }

    return array('stat'=>'ok', 'certificates'=>$certificates, 'nplist'=>$certificate_ids);
}
?>
