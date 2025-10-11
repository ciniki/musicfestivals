<?php
//
// Description
// -----------
// This script is used to fix isses with class permalinks after a renumbering
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
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');

//
// Get the


//
// Get the list of classes
//
$strsql = "SELECT id, tnid, code, name, permalink "
    . "FROM ciniki_musicfestival_classes "
    . "ORDER BY id "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'code', 'name', 'permalink')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1205', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
}
$classes = isset($rc['classes']) ? $rc['classes'] : array();
foreach($classes as $class) {
    $permalink = ciniki_core_makePermalink($ciniki, "{$class['code']} - {$class['name']}");
    if( $permalink != $class['permalink'] ) {
        print("{$class['tnid']}: {$class['code']} - {$class['name']}: {$class['permalink']} => {$permalink}\n");
        $rc = ciniki_core_objectUpdate($ciniki, $class['tnid'], 'ciniki.musicfestivals.class', $class['id'], [
            'permalink' => $permalink,
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
            exit;
        } 
    }
}

?>
