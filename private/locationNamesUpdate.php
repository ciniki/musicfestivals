<?php
//
// Description
// -----------
// Update the room names for a building
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_locationNamesUpdate(&$ciniki, $tnid, $args) {

    //
    // Get the list of buildings/rooms
    //
    $strsql = "SELECT buildings.id, "
        . "buildings.name, "
        . "rooms.id AS room_id, "
        . "rooms.name AS location_name, "
        . "rooms.roomname "
        . "FROM ciniki_musicfestival_buildings AS buildings "
        . "LEFT JOIN ciniki_musicfestival_locations AS rooms ON ("
            . "buildings.id = rooms.building_id "
            . "AND rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE buildings.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['building_id']) && $args['building_id'] > 0 ) {
        $strsql .= "AND buildings.id = '" . ciniki_core_dbQuote($ciniki, $args['building_id']) . "' ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'buildings', 'fname'=>'id', 'fields'=>array('id', 'name')),
        array('container'=>'rooms', 'fname'=>'room_id', 'fields'=>array('id'=>'room_id', 'location_name', 'roomname')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1472', 'msg'=>'Unable to load buildings', 'err'=>$rc['err']));
    }
    $buildings = isset($rc['buildings']) ? $rc['buildings'] : array();

    foreach($buildings as $building) {
        if( !isset($building['rooms']) ) {
            continue;
        }
        foreach($building['rooms'] as $room) {
            $location_name = $building['name'] . ' - ' . $room['roomname'];
            if( $location_name != $room['location_name'] ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.location', $room['id'], [
                    'name' => $location_name,
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1473', 'msg'=>'Unable to update the location', 'err'=>$rc['err']));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
