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
// Get the list of adjudicators
//
$strsql = "SELECT id "
    . "FROM ciniki_musicfestival_adjudicators "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1439', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
}
$adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

print "Updating sections";
//
// Get the list of section adjudicators
//
$strsql = "SELECT ssections.id, "
    . "ssections.tnid, "
    . "ssections.adjudicator1_id AS adjudicator_id "
    . "FROM ciniki_musicfestival_schedule_sections AS ssections "
    . "WHERE adjudicator1_id > 0 "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'ssections', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'adjudicator_id')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1440', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
}
$ssections = isset($rc['ssections']) ? $rc['ssections'] : array();

foreach($ssections as $section) {
    if( isset($adjudicators[$section['adjudicator_id']]) ) {
        print ".";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $section['tnid'], 'ciniki.musicfestivals.adjudicatorref', [
            'adjudicator_id' => $section['adjudicator_id'],
            'object' => 'ciniki.musicfestivals.schedulesection',
            'object_id' => $section['id'],
            ], 0x04);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1412', 'msg'=>'Unable to add the adjudicatorref', 'err'=>$rc['err']));
        }
    }
}

print "\nUpdating divisions";
//
// Get the list of division adjudicators
//
$strsql = "SELECT divisions.id, "
    . "divisions.tnid, "
    . "divisions.adjudicator_id "
    . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
    . "WHERE adjudicator_id > 0 "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'divisions', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'adjudicator_id')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1460', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
}
$divisions = isset($rc['divisions']) ? $rc['divisions'] : array();

foreach($divisions as $division) {
    if( isset($adjudicators[$division['adjudicator_id']]) ) {
        print ".";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $division['tnid'], 'ciniki.musicfestivals.adjudicatorref', [
            'adjudicator_id' => $division['adjudicator_id'],
            'object' => 'ciniki.musicfestivals.scheduledivision',
            'object_id' => $division['id'],
            ], 0x04);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1413', 'msg'=>'Unable to add the adjudicatorref', 'err'=>$rc['err']));
        }
    }
}
print "\n";
?>
