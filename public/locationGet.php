<?php
//
// Description
// ===========
// This method will return all the information about an location.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the location is attached to.
// location_id:          The ID of the location to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_locationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'location_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Location'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'building_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Building'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.locationGet');
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
    // Return default for new Location
    //
    if( $args['location_id'] == 0 ) {
        $location = array(
            'id' => 0,
            'festival_id' => '',
            'building_id' => isset($args['building_id']) ? $args['building_id'] : 0,
            'roomname' => '',
            'name' => '',
            'permalink' => '',
            'shortname' => '',
            'sequence' => '',
            'disciplines' => '',
        );
    }

    //
    // Get the details for an existing Location
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_locations.id, "
            . "ciniki_musicfestival_locations.festival_id, "
            . "ciniki_musicfestival_locations.building_id, "
            . "ciniki_musicfestival_locations.roomname, "
            . "ciniki_musicfestival_locations.name, "
            . "ciniki_musicfestival_locations.permalink, "
            . "ciniki_musicfestival_locations.shortname, "
            . "ciniki_musicfestival_locations.sequence, "
            . "ciniki_musicfestival_locations.disciplines "
            . "FROM ciniki_musicfestival_locations "
            . "WHERE ciniki_musicfestival_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_locations.id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'locations', 'fname'=>'id', 
                'fields'=>array('id', 'building_id', 'festival_id', 'roomname', 'name', 'permalink', 'shortname', 'sequence', 'disciplines')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.707', 'msg'=>'Location not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['locations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.708', 'msg'=>'Unable to find Location'));
        }
        $location = $rc['locations'][0];
    }
    $rsp = array('stat'=>'ok', 'location'=>$location);

    //
    // Get the list of buildings
    //
    $strsql = "SELECT buildings.id, "
        . "buildings.name "
        . "FROM ciniki_musicfestival_buildings AS buildings "
        . "WHERE buildings.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'buildings', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1201', 'msg'=>'Unable to load buildings', 'err'=>$rc['err']));
    }
    $rsp['buildings'] = isset($rc['buildings']) ? $rc['buildings'] : array();
    array_unshift($rsp['buildings'], ['id'=>0, 'name'=>'None']);

    return $rsp;
}
?>
