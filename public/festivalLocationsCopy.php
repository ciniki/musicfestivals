<?php
//
// Description
// ===========
// This method will copy another festivals syllabus
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the festival is attached to.
// festival_id:          The ID of the festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_festivalLocationsCopy($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'old_festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Previous Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.locationsCopy');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['old_festival_id'] == 'previous' ) {
        $strsql = "SELECT id "
            . "FROM ciniki_musicfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['festival']['id']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1197', 'msg'=>'No previous festival found'));
        }
        $args['old_festival_id'] = $rc['festival']['id'];
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of buildings and rooms
    //
    $strsql = "SELECT buildings.id, "
        . "buildings.name, "
        . "buildings.permalink, "
        . "buildings.category, "
        . "buildings.sequence, "
        . "buildings.address1, "
        . "buildings.city, "
        . "buildings.province, "
        . "buildings.postal, "
        . "buildings.latitude, "
        . "buildings.longitude, "
        . "buildings.image_id, "
        . "buildings.description, "
        . "rooms.id AS room_id, "
        . "rooms.roomname, "
        . "rooms.name AS room_name, "
        . "rooms.permalink AS room_permalink, "
        . "rooms.shortname, "
        . "rooms.sequence AS room_sequence, "
        . "rooms.disciplines "
        . "FROM ciniki_musicfestival_buildings AS buildings "
        . "LEFT JOIN ciniki_musicfestival_locations AS rooms ON ("
            . "buildings.id = rooms.building_id "
            . "AND rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE buildings.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['old_festival_id']) . "' "
        . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY buildings.category, buildings.sequence, buildings.name, rooms.sequence, rooms.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'buildings', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'category', 'sequence', 'address1', 'city', 
                'province', 'postal', 'latitude', 'longitude', 'image_id', 'description', 
                ),
            ),
        array('container'=>'rooms', 'fname'=>'room_id',
            'fields'=>array('roomname', 'name'=>'room_name', 'permalink'=>'room_permalink', 'shortname', 
                'sequence'=>'room_sequence', 'disciplines',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1220', 'msg'=>'Unable to load buildings', 'err'=>$rc['err']));
    }
    $buildings = isset($rc['buildings']) ? $rc['buildings'] : array();

    foreach($buildings as $building) {
        //
        // Add the building
        //
        $building['festival_id'] = $args['festival_id'];
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.building', $building, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $building_id = $rc['id'];

        //
        // Add the rooms
        //
        foreach($building['rooms'] as $room) {
            $room['festival_id'] = $args['festival_id'];
            $room['building_id'] = $building_id;
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.location', $room, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $room_id = $rc['id'];
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>

