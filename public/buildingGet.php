<?php
//
// Description
// ===========
// This method will return all the information about an building.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the building is attached to.
// building_id:          The ID of the building to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_buildingGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'building_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Building'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.buildingGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Building
    //
    if( $args['building_id'] == 0 ) {
        $building = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'category'=>'',
            'sequence'=>'1',
            'address1'=>'',
            'city'=>'',
            'province'=>'',
            'postal'=>'',
            'latitude'=>'',
            'longitude'=>'',
            'image_id'=>'0',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Building
    //
    else {
        $strsql = "SELECT buildings.id, "
            . "buildings.festival_id, "
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
            . "buildings.description "
            . "FROM ciniki_musicfestival_buildings AS buildings "
            . "WHERE buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND buildings.id = '" . ciniki_core_dbQuote($ciniki, $args['building_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'buildings', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'permalink', 'category', 'sequence', 'address1', 'city', 'province', 'postal', 'latitude', 'longitude', 'image_id', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1213', 'msg'=>'Building not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['buildings'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1214', 'msg'=>'Unable to find Building'));
        }
        $building = $rc['buildings'][0];

        //
        // Get the list of rooms/disciplines
        //
        $strsql = "SELECT rooms.id, "
            . "rooms.roomname, "
            . "rooms.name, "
            . "rooms.shortname, "
            . "rooms.disciplines "
            . "FROM ciniki_musicfestival_locations AS rooms "
            . "WHERE rooms.building_id = '" . ciniki_core_dbQuote($ciniki, $args['building_id']) . "' "
            . "AND rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sequence, roomname, disciplines "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'rooms', 'fname'=>'id', 
                'fields'=>array(
                    'id', 'roomname', 'name', 'shortname', 'disciplines'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.977', 'msg'=>'Unable to load rooms', 'err'=>$rc['err']));
        }
        $building['rooms'] = isset($rc['rooms']) ? $rc['rooms'] : array();
        foreach($building['rooms'] as $rid => $room) {
            $building['rooms'][$rid]['fullname'] = $room['shortname'];
            if( $room['roomname'] != '' ) {
                $building['rooms'][$rid]['fullname'] .= ($building['rooms'][$rid]['fullname'] != '' ? ' - ' : '') . $room['roomname'];
            }
            if( $room['disciplines'] != '' ) {
                $building['rooms'][$rid]['fullname'] .= ($building['rooms'][$rid]['fullname'] != '' ? ' - ' : '') . $room['disciplines'];
            }
            if( $building['rooms'][$rid]['fullname'] == '' ) {
                $building['add_id'] = $room['id'];
                unset($building['rooms'][$rid]);
            }
        }
    }

    return array('stat'=>'ok', 'building'=>$building);
}
?>
