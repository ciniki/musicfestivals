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
ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'phoneFormat');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');

//
// Get the titles
//
$strsql = "SELECT competitors.id, "
    . "competitors.tnid, "
    . "competitors.phone_cell, "
    . "competitors.phone_home "
    . "FROM ciniki_musicfestival_competitors AS competitors "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'phone_cell', 'phone_home')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1658', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
}
$competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

$num_good = 0;
$num_reformat = 0;
$num_bad = 0;
foreach($competitors as $competitor) {
  
    $update_args = [];
    if( $competitor['phone_cell'] != '' ) {
        $rc = ciniki_tenants_hooks_phoneFormat($ciniki, $competitor['tnid'], ['number'=>$competitor['phone_cell']]);
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
            exit;
        }
        if( $rc['formatted_number'] != $competitor['phone_cell'] ) {
            $update_args['phone_cell'] = $rc['formatted_number'];
            print "Reformat: {$competitor['phone_cell']} -> {$rc['formatted_number']}\n";
            $num_reformat++;
        } elseif( !preg_match("/[0-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]/", $rc['formatted_number']) ) {
            print "Invalid format: {$rc['formatted_number']}\n";
            $num_bad++;
        } else {
            $num_good++;
        }
    }
    if( $competitor['phone_home'] != '' ) {
        $rc = ciniki_tenants_hooks_phoneFormat($ciniki, $competitor['tnid'], ['number'=>$competitor['phone_home']]);
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
            exit;
        }
        if( $rc['formatted_number'] != $competitor['phone_home'] ) {
            $update_args['phone_home'] = $rc['formatted_number'];
            print "Reformat: {$competitor['phone_home']} -> {$rc['formatted_number']}\n";
            $num_reformat++;
        } elseif( !preg_match("/[0-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]/", $rc['formatted_number']) ) {
            print "Invalid format: {$rc['formatted_number']}\n";
            $num_bad++;
        } else {
            $num_good++;
        }
    }
    if( count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $competitor['tnid'], 'ciniki.musicfestivals.competitor', $competitor['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1659', 'msg'=>'Unable to update the competitor', 'err'=>$rc['err']));
        } 
    }
}

print "Good: {$num_good}\n";
print "Bad: {$num_bad}\n";
print "Reformat: {$num_reformat}\n";
?>
