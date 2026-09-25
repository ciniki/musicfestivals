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
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

//
// Get the list of timeslots and their registrations
//
$strsql = "SELECT id, "
    . "tnid, "
    . "list_id "
    . "FROM ciniki_musicfestivals_titles AS titles "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'reg');
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}
$titles = isset($rc['rows']) ? $rc['rows'] : array();

foreach($titles as $title) {
    //
    // Add to join table
    //
    $rc = ciniki_core_objectAdd($ciniki, $title['tnid'], 'ciniki.musicfestivals.titlelisttitle', [
        'list_id' => $title['list_id'],
        'title_id' => $title['id'],
        ], 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1690', 'msg'=>'Unable to add the titlelist_title', 'err'=>$rc['err']));
    }

    //
    // Reset list_id
    //
    $rc = ciniki_core_objectUpdate($ciniki, $title['tnid'], 'ciniki.musicfestivals.title', $title['id'], [
        'list_id' => 0,
        ], 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1259', 'msg'=>'Unable to update the titlelisttitle', 'err'=>$rc['err']));
    }
}

?>

