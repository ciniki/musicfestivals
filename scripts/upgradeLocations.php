<?php
//
// Description
// -----------
// This script will setup the timeslot_id for each registration even
// when NOT a split class.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');

//
// Get the list of locations
//
$strsql = "SELECT id, "
    . "tnid, "
    . "festival_id, "
    . "name, "
    . "permalink, "
    . "category, "
    . "sequence, "
    . "address1, "
    . "city, "
    . "province, "
    . "postal, "
    . "latitude, "
    . "longitude, "
    . "image_id, "
    . "description "
    . "FROM ciniki_musicfestival_locations "
    . "WHERE building_id = 0 "
    . "ORDER BY tnid, festival_id, name, sequence "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'locations', 'fname'=>'id', 
        'fields'=>array('id', 'tnid', 'festival_id', 'name', 'permalink', 'category', 'sequence', 
            'address1', 'city', 'province', 'postal', 'latitude', 'longitude', 'image_id', 'description'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.166', 'msg'=>'Unable to load locations', 'err'=>$rc['err']));
}
$locations = isset($rc['locations']) ? $rc['locations'] : array();

// NOTE: Can't make smart, nobody consistent with name conventions
//print "Processing ";
/*$buildings = [];
foreach($locations as $k => $location) {
    if( preg_match("/^(.*) - (.*)$/", $location['name'], $m) ) {
        $locations[$k]['building_name'] = $m[1];
        $locations[$k]['room_name'] = $m[1];
    } else {
        $locations[$k]['building_name'] = $location['name'];
        $locations[$k]['room_name'= '';
    }
}
*/

//
// Set up the buildings table
//
print "Processing ";
foreach($locations as $location) {
    $rc = ciniki_core_objectAdd($ciniki, $location['tnid'], 'ciniki.musicfestivals.building', $location, 0x04);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $location['tnid'], 'ciniki.musicfestivals.location', $location['id'], [
        'building_id' => $rc['id'],
        ], 0x04);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }
    print ".";
    
}
print "done\n\n";

exit;
?>
