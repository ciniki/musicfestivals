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
$strsql = "SELECT id, tnid, title1, perf_time1, title2, perf_time2, title3, perf_time3 "
    . "FROM ciniki_musicfestival_registrations AS registrations "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'registrations', 'fname'=>'id', 
        'fields'=>array('id', 'tnid', 'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.562', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
}
$registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

foreach($registrations as $reg) {
    $update_args = array();
    for($i = 1; $i <= 3; $i++) {
        if( $reg["perf_time{$i}"] != '' ) {
            if( preg_match("/^[Nn]\/[aA]$/", $reg["perf_time{$i}"], $m) ) {
                $seconds = 0;
            } elseif( preg_match("/^(\?|TBA|TBC|TBD|na|[Uu]nk|[Uu]nknown|Any.*)$/", $reg["perf_time{$i}"], $m) ) {
                $seconds = 0;
            } elseif( preg_match("/^([0-9]+)\/[0-9]$/", $reg["perf_time{$i}"], $m) ) {
                $seconds = 0;
            } elseif( preg_match("/^([0-9])$/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60);
            } elseif( preg_match("/^([0-9]+)\'\'$/", $reg["perf_time{$i}"], $m) ) {
                $seconds = $m[1];
            } elseif( preg_match("/^([0-9]+)\"$/", $reg["perf_time{$i}"], $m) ) {
                $seconds = $m[1];
            } elseif( preg_match("/^:([0-9]+)$/", $reg["perf_time{$i}"], $m) ) {
                $seconds = $m[1];
            } elseif( preg_match("/([0-9]+):([0-9]+)/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60) + $m[2];
            } elseif( preg_match("/([0-9]+)\'([0-9]+)/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60) + $m[2];
            } elseif( preg_match("/([0-9]+)\s+[mM][^0-9]*([0-9]+)/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60) + $m[2];
            } elseif( preg_match("/([0-9\.]+)\s*[mM]/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60);
            } elseif( preg_match("/([0-9]+)\s*[sS]/", $reg["perf_time{$i}"], $m) ) {
                $seconds = $m[1];
            } elseif( preg_match("/([0-9]+)\.([0-9]+)/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60) + $m[2];
            } elseif( preg_match("/([0-9][0-9])([0-9][0-9])/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60) + $m[2];
            } elseif( preg_match("/([0-9]+)m([0-9][0-9])/", $reg["perf_time{$i}"], $m) ) {
                $seconds = ($m[1]*60) + $m[2];
            } else {
                $seconds = 0;
//                print "No time match: " . $reg['id'] . ' ' . $reg["perf_time{$i}"] . "\n";
            }
            $seconds = round($seconds, 0);
            $update_args["perf_time{$i}"] = $seconds;
           
            
        }
    }
    if( count($update_args) > 0 ) {
        //
        // Update the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $reg['tnid'], 'ciniki.musicfestivals.registration', $reg['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.563', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
        }
    }
}





?>
