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
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');

//
// Get the titles
//
$strsql = "SELECT titles.id, "
    . "titles.tnid, "
    . "titles.title, "
    . "titles.movements, "
    . "titles.composer, "
    . "titles.source_type, "
    . "titles.keywords "
    . "FROM ciniki_musicfestivals_titles AS titles "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'titles', 'fname'=>'id', 
        'fields'=>array(
            'id', 'tnid', 'title', 'movements', 'composer', 'source_type', 'keywords'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1652', 'msg'=>'Unable to load titles', 'err'=>$rc['err']));
}
$titles = isset($rc['titles']) ? $rc['titles'] : array();

foreach($titles as $title) {
    
    $rc = ciniki_musicfestivals_titleListKeywordsMake($ciniki, $title['tnid'], ['title'=>$title]);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }
    if( $rc['keywords'] != $title['keywords'] ) {
        $keywords = $rc['keywords'];
        $rc = ciniki_core_objectUpdate($ciniki, $title['tnid'], 'ciniki.musicfestivals.title', $title['id'], [
            'keywords' => $keywords,
            ], 0x04);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1653', 'msg'=>'Unable to add the title keywords', 'err'=>$rc['err']));
        }
    }
}

?>
