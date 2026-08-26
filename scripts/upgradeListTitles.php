<?php
//
// Description
// -----------
// This script will update the titlelists fulltitle fields.
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
// Get the titles
//
$strsql = "SELECT id, "
    . "tnid, "
    . "fulltitle, "
    . "title, "
    . "opus, "
    . "movements, "
    . "musical, "
    . "composer, "
    . "arranger, "
    . "keywords "
    . "FROM ciniki_musicfestivals_titles AS titles "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'title');
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}
$titles = isset($rc['rows']) ? $rc['rows'] : array();

$max_title = 0;
$max_movement = 0;
$max_composer = 0;
$max_length = 0;
foreach($titles as $title) {
    $update_args = [];
    $titlepieces = [];

    $rc = ciniki_musicfestivals_titleMerge($ciniki, $title['tnid'], $title, '');
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }
    $fulltitle = $rc['title'];
    if( strlen($fulltitle) > $max_length) {
        $max_length = strlen($fulltitle);
    }
    if( $fulltitle != $title["fulltitle"] ) {
        $update_args['fulltitle'] = $fulltitle;
    }
    //
    // Check keywords are correct
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');
    $rc = ciniki_musicfestivals_titleListKeywordsMake($ciniki, $title['tnid'], $title);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $request['args']['s']);
        exit;
    }
    if( $rc['keywords'] != $title['keywords'] ) {   
        $update_args['keywords'] = $rc['keywords'];
    }

    if( count($update_args) > 0 ) {
        //
        // Update the title
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $title['tnid'], 'ciniki.musicfestivals.title', $title['id'], ['fulltitle'=>$fulltitle], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1677', 'msg'=>'Unable to update the title', 'err'=>$rc['err']));
        } 
    }
}

print "Max Length: {$max_length}\n";
//print "Max Movement: {$max_movement}\n";
//print "Max Composer: {$max_composer}\n";
//print "Combined Max Length: {$max_length}\n";



?>
