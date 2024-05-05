<?php
//
// Description
// -----------
// This method will return the list of Locations for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Location for.
//
// Returns
// -------
//
function ciniki_musicfestivals_locationList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.locationList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of locations
    //
    $strsql = "SELECT ciniki_musicfestival_locations.id, "
        . "ciniki_musicfestival_locations.festival_id, "
        . "ciniki_musicfestival_locations.name, "
        . "ciniki_musicfestival_locations.address1, "
        . "ciniki_musicfestival_locations.city, "
        . "ciniki_musicfestival_locations.province, "
        . "ciniki_musicfestival_locations.postal, "
        . "ciniki_musicfestival_locations.latitude, "
        . "ciniki_musicfestival_locations.longitude "
        . "FROM ciniki_musicfestival_locations "
        . "WHERE ciniki_musicfestival_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'locations', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'address1', 'city', 'province', 'postal', 'latitude', 'longitude')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $locations = isset($rc['locations']) ? $rc['locations'] : array();
    $location_ids = array();
    foreach($locations as $iid => $location) {
        $location_ids[] = $location['id'];
    }

    return array('stat'=>'ok', 'locations'=>$locations, 'nplist'=>$location_ids);
}
?>
