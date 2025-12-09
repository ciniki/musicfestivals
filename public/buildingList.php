<?php
//
// Description
// -----------
// This method will return the list of Buildings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Building for.
//
// Returns
// -------
//
function ciniki_musicfestivals_buildingList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.buildingList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of buildings
    //
    $strsql = "SELECT ciniki_musicfestival_buildings.id, "
        . "ciniki_musicfestival_buildings.festival_id, "
        . "ciniki_musicfestival_buildings.name, "
        . "ciniki_musicfestival_buildings.permalink, "
        . "ciniki_musicfestival_buildings.category, "
        . "ciniki_musicfestival_buildings.sequence, "
        . "ciniki_musicfestival_buildings.address1, "
        . "ciniki_musicfestival_buildings.city, "
        . "ciniki_musicfestival_buildings.province, "
        . "ciniki_musicfestival_buildings.postal, "
        . "ciniki_musicfestival_buildings.latitude, "
        . "ciniki_musicfestival_buildings.longitude "
        . "FROM ciniki_musicfestival_buildings "
        . "WHERE ciniki_musicfestival_buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'buildings', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'permalink', 'category', 'sequence', 'address1', 'city', 'province', 'postal', 'latitude', 'longitude')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $buildings = isset($rc['buildings']) ? $rc['buildings'] : array();
    $building_ids = array();
    foreach($buildings as $iid => $building) {
        $building_ids[] = $building['id'];
    }

    return array('stat'=>'ok', 'buildings'=>$buildings, 'nplist'=>$building_ids);
}
?>
