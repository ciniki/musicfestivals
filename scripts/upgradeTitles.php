<?php
//
// Description
// -----------
// This script will upgrade the titles to be a single title, and other information into title pieces
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
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

//
// Get the list of timeslots and their registrations
//
$strsql = "SELECT id, "
    . "tnid, "
    . "fulltitle1, "
    . "fulltitle2, "
    . "fulltitle3, "
    . "fulltitle4, "
    . "fulltitle5, "
    . "fulltitle6, "
    . "fulltitle7, "
    . "fulltitle8, "
    . "title1, "
    . "title2, "
    . "title3, "
    . "title4, "
    . "title5, "
    . "title6, "
    . "title7, "
    . "title8, "
    . "opus1, "
    . "opus2, "
    . "opus3, "
    . "opus4, "
    . "opus5, "
    . "opus6, "
    . "opus7, "
    . "opus8, "
    . "movements1, "
    . "movements2, "
    . "movements3, "
    . "movements4, "
    . "movements5, "
    . "movements6, "
    . "movements7, "
    . "movements8, "
    . "musical1, "
    . "musical2, "
    . "musical3, "
    . "musical4, "
    . "musical5, "
    . "musical6, "
    . "musical7, "
    . "musical8, "
    . "composer1, "
    . "composer2, "
    . "composer3, "
    . "composer4, "
    . "composer5, "
    . "composer6, "
    . "composer7, "
    . "composer8 "
    . "FROM ciniki_musicfestival_registrations AS registrations "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'reg');
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}
$registrations = isset($rc['rows']) ? $rc['rows'] : array();

$max_title = 0;
$max_movement = 0;
$max_composer = 0;
$max_length = 0;
foreach($registrations as $reg) {
    $update_args = [];
    $titlepieces = [];
    for($i = 1; $i <= 8; $i++) {
        $rc = ciniki_musicfestivals_titleMerge($ciniki, $reg['tnid'], $reg, $i);
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
            exit;
        }
        $fulltitle = $rc['title'];
        if( strlen($fulltitle) > $max_length) {
            $max_length = strlen($fulltitle);
        }
        if( strlen($reg["title{$i}"]) > $max_title) {
            $max_title = strlen($reg["title{$i}"]);
        }
        if( strlen($reg["movements{$i}"]) > $max_movement) {
            $max_movement = strlen($reg["movements{$i}"]);
        }
        if( strlen($reg["composer{$i}"]) > $max_composer) {
            $max_composer = strlen($reg["composer{$i}"]);
        }
//        if( $reg["title{$i}"] != '' ) {
//            $titlepieces["title{$i}"] = $reg["title{$i}"];
//        }
//        if( $reg["movements{$i}"] != '' ) {
//            $titlepieces["movements{$i}"] = $reg["movements{$i}"];
//            $update_args["movements{$i}"] = '';
//        }
//        if( $reg["composer{$i}"] != '' ) {
//            $titlepieces["composer{$i}"] = $reg["composer{$i}"];
//            $update_args["composer{$i}"] = '';
//        }
        if( $fulltitle != $reg["fulltitle{$i}"] ) {
            $update_args["fulltitle{$i}"] = $fulltitle;
        }
    }
//    $titlepieces_json = json_encode($titlepieces);
//    if( $titlepieces_json != $reg['titlepieces'] ) {
//        $update_args['titlepieces'] = $titlepieces_json;
//    }
    if( count($update_args) > 0 ) {
        //
        // Update the registration
        //
        error_log("Upgrade {$reg['id']}");
        print_r($update_args);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $reg['tnid'], 'ciniki.musicfestivals.registration', $reg['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.563', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
        } 
    }
}

//print "Max Title: {$max_title}\n";
//print "Max Movement: {$max_movement}\n";
//print "Max Composer: {$max_composer}\n";
//print "Combined Max Length: {$max_length}\n";



?>
