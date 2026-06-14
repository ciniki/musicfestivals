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
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');

//
// Get the list of divisions for each adjudicator section
//
$strsql = "SELECT arefs.id, "
    . "arefs.tnid, "
    . "arefs.adjudicator_id, "
    . "arefs.object, "
    . "arefs.object_id, "
    . "divisions.id AS division_id "
    . "FROM ciniki_musicfestival_adjudicatorrefs AS arefs "
    . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
        . "arefs.object_id = divisions.ssection_id "
        . ") "
    . "WHERE arefs.object = 'ciniki.musicfestivals.schedulesection' "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'refs', 'fname'=>'id', 
        'fields'=>array('id', 'tnid', 'adjudicator_id', 'object', 'object_id'),
        ),
    array('container'=>'divisions', 'fname'=>'division_id', 
        'fields'=>array('id'=>'division_id'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1614', 'msg'=>'Unable to load adjudicatorrefs', 'err'=>$rc['err']));
}
$refs = isset($rc['refs']) ? $rc['refs'] : array();

foreach($refs as $ref) {
    if( !isset($ref['divisions']) ) {
        continue;
    }
    foreach($ref['divisions'] as $division) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $ref['tnid'], 'ciniki.musicfestivals.adjudicatorref', [
            'adjudicator_id' => $ref['adjudicator_id'],
            'object' => 'ciniki.musicfestivals.scheduledivision',
            'object_id' => $division['id'],
            ], 0x04);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1413', 'msg'=>'Unable to add the adjudicatorref', 'err'=>$rc['err']));
        }
    }
}

?>
