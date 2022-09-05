<?php
//
// Description
// -----------
// This method will return the list of Trophys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Trophy for.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophyList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophyList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of trophies
    //
    $strsql = "SELECT ciniki_musicfestival_trophies.id, "
        . "ciniki_musicfestival_trophies.name, "
        . "ciniki_musicfestival_trophies.category, "
        . "ciniki_musicfestival_trophies.donated_by, "
        . "ciniki_musicfestival_trophies.first_presented, "
        . "ciniki_musicfestival_trophies.criteria "
        . "FROM ciniki_musicfestival_trophies "
        . "WHERE ciniki_musicfestival_trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY category, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'trophies', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'category', 'donated_by', 'first_presented', 'criteria')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();
    $trophy_ids = array();
    foreach($trophies as $iid => $trophy) {
        $trophy_ids[] = $trophy['id'];
    }

    return array('stat'=>'ok', 'trophies'=>$trophies, 'nplist'=>$trophy_ids);
}
?>
