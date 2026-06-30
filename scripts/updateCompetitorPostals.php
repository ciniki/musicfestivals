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
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'postalFormat');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');

//
// Get the titles
//
$strsql = "SELECT competitors.id, "
    . "competitors.tnid, "
    . "competitors.postal "
    . "FROM ciniki_musicfestival_competitors AS competitors "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'postal')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1665', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
}
$competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

foreach($competitors as $competitor) {
  
    $update_args = [];
    if( $competitor['postal'] != '' ) {
        $rc = ciniki_tenants_hooks_postalFormat($ciniki, $competitor['tnid'], ['postal'=>$competitor['postal']]);
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
            exit;
        }
        if( $rc['formatted_postal'] != '' && $rc['formatted_postal'] != $competitor['postal'] ) {
            print "Update: {$competitor['postal']} -> {$rc['formatted_postal']}\n";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $competitor['tnid'], 'ciniki.musicfestivals.competitor', $competitor['id'], [
                'postal' => $rc['formatted_postal'],
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1666', 'msg'=>'Unable to update the competitor', 'err'=>$rc['err']));
            } 
        }
    }
}

?>
