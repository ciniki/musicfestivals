<?php
//
// Description
// ===========
// This function will load the locations
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the volunteer is attached to.
// volunteer_id:          The ID of the volunteer to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_locationsLoad($ciniki, $tnid, $festival_id) {

    //
    // Build the list of buildings/locations
    //
    $strsql = "SELECT buildings.id, "
        . "buildings.name, "
        . "IF(buildings.shortname <> '', buildings.shortname, buildings.name) AS shortname, "
        . "locations.id AS location_id, "
        . "locations.name AS location_name, "
        . "IF(locations.shortname <> '', locations.shortname, locations.name) AS location_shortname "
        . "FROM ciniki_musicfestival_buildings AS buildings "
        . "INNER JOIN ciniki_musicfestival_locations AS locations ON ("
            . "buildings.id = locations.building_id "
            . "AND locations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE buildings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY buildings.sequence, buildings.name, locations.sequence, locations.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'buildings', 'fname'=>'id', 'fields'=>array('id', 'name', 'shortname')),
        array('container'=>'rooms', 'fname'=>'location_id', 
            'fields'=>array('id'=>'location_id', 'name'=>'location_name', 'shortname'=>'location_shortname')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1240', 'msg'=>'Unable to load buildings', 'err'=>$rc['err']));
    }
    $buildings = isset($rc['buildings']) ? $rc['buildings'] : array();

    $locations = [];
    foreach($buildings as $building) {
        if( count($building['rooms']) > 1 ) {
            $locations["ciniki.musicfestivals.building:{$building['id']}"] = [
                'id' => 'ciniki.musicfestivals.building:' . $building['id'],
                'name' => $building['name'],
                'shortname' => $building['shortname'],
                ];
            foreach($building['rooms'] as $room) {
                $locations["ciniki.musicfestivals.location:{$room['id']}"] = [
                    'id' => 'ciniki.musicfestivals.location:' . $room['id'],
                    'name' => $room['name'],
                    'shortname' => $room['shortname'],
                    ];
            }
        } else {
            $locations["ciniki.musicfestivals.location:{$building['rooms'][0]['id']}"] = [
                'id' => 'ciniki.musicfestivals.location:' . $building['rooms'][0]['id'],
                'name' => $building['rooms'][0]['name'],
                'shortname' => $building['rooms'][0]['shortname'],
                ];
        }
    }

    return array('stat'=>'ok', 'locations'=>$locations);
}
?>
