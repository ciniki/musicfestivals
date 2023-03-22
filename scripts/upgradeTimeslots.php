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
// Get the list of timeslots and their registrations
//
$strsql = "SELECT "
    . "timeslots.id AS timeslot_id, "
    . "timeslots.tnid, "
    . "timeslots.class1_id, "
    . "timeslots.class2_id, "
    . "timeslots.class3_id, "
    . "timeslots.class4_id, "
    . "timeslots.class5_id, "
    . "registrations.id AS reg_id "
    . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
    . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
        . "timeslots.class1_id = class1.id " 
        . "AND class1.tnid = timeslots.tnid "
        . ") "
    . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
        . "timeslots.class2_id = class2.id " 
        . "AND class2.tnid = timeslots.tnid "
        . ") "
    . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
        . "timeslots.class3_id = class3.id " 
        . "AND class3.tnid = timeslots.tnid "
        . ") "
    . "LEFT JOIN ciniki_musicfestival_classes AS class4 ON ("
        . "timeslots.class4_id = class4.id " 
        . "AND class4.tnid = timeslots.tnid "
        . ") "
    . "LEFT JOIN ciniki_musicfestival_classes AS class5 ON ("
        . "timeslots.class5_id = class5.id " 
        . "AND class5.tnid = timeslots.tnid "
        . ") "
    . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
        . "(timeslots.class1_id = registrations.class_id "  
            . "OR timeslots.class2_id = registrations.class_id "
            . "OR timeslots.class3_id = registrations.class_id "
            . "OR timeslots.class4_id = registrations.class_id "
            . "OR timeslots.class5_id = registrations.class_id "
            . ") "
        . "AND registrations.tnid = timeslots.tnid "
        . ") "
    . "WHERE timeslots.class1_id > 0 "
        . "AND (timeslots.flags&0x01) = 0 " // Not split class
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'registrations', 'fname'=>'reg_id', 
        'fields'=>array('timeslot_id', 'reg_id', 'tnid', 'class1_id', 'class2_id', 'class3_id', 'class4_id', 'class5_id'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.459', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
}
$registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

foreach($registrations as $reg) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $reg['tnid'], 'ciniki.musicfestivals.registration', $reg['reg_id'], array('timeslot_id'=>$reg['timeslot_id']), 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.460', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
    }
}





?>
