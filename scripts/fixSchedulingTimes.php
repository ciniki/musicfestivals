<?php
//
// Description
// -----------
//

//
// This script should run as www-data and will create the setup for an apache ssl domain
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');

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
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
ciniki_core_loadMethod($ciniki, 'ciniki', 'cron', 'private', 'logMsg');
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

if( isset($argv[1]) && $argv[1] != '' ) {
    $tnid = $argv[1];
} else {
    print_usage($argv);
}
if( isset($argv[2]) && $argv[2] != '' ) {
    $old_festival_id = $argv[2];
} else {
    print_usage($argv);
}
if( isset($argv[3]) && $argv[3] != '' ) {
    $new_festival_id = $argv[3];
} else {
    print_usage($argv);
}

//
// Load the old festival schedule times
//
$strsql = "SELECT code, name, schedule_seconds, schedule_at_seconds, schedule_ata_seconds "
    . "FROM ciniki_musicfestival_classes "
    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
    . "AND schedule_seconds > 0 "
    . "ORDER BY code, name "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.195', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
}
$old_classes = isset($rc['rows']) ? $rc['rows'] : array();

//
// Load the new festival schedule times
//
$strsql = "SELECT id, code, name, schedule_seconds, schedule_at_seconds, schedule_ata_seconds "
    . "FROM ciniki_musicfestival_classes "
    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $new_festival_id) . "' "
    . "ORDER BY code, name "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'classes', 'fname'=>'code', 
        'fields'=>array('id', 'code', 'name', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.962', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
}
$new_classes = isset($rc['classes']) ? $rc['classes'] : array();

foreach($old_classes as $class) {
    print "{$class['code']} - {$class['name']} - {$class['schedule_seconds']} ";
    
    if( isset($new_classes[$class['code']])
        && $new_classes[$class['code']]['schedule_seconds'] == 0 
        && $new_classes[$class['code']]['name'] == $class['name']
        ) {
        print "update";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.class', $new_classes[$class['code']]['id'], [
            'schedule_seconds' => $class['schedule_seconds'],
            'schedule_at_seconds' => $class['schedule_at_seconds'],
            'schedule_ata_seconds' => $class['schedule_ata_seconds'],
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.963', 'msg'=>'Unable to update the class', 'err'=>$rc['err']));
        }
    } elseif( isset($new_classes[$class['code']])
        && $new_classes[$class['code']]['schedule_seconds'] > 0 
        ) {
        print "existing value"; 
    } else {
        print " ignored";
    }
    print "\n";
}

print "Done\n";

function print_usage($argv) {
    print "php {$argv[0]} <tnid> <old_festival_id> <new_festival_id>\n";
    exit;
}
?>

