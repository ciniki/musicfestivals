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

//
// Get the messages with no content
//
print "Loading messages...\n";
$strsql = "SELECT id, dt_sent, content "
    . "FROM ciniki_musicfestival_messages "
    . "WHERE dt_sent > '2020-01-01 12:00:00' "
    . "AND content = '' "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'messages', 'fname'=>'id', 
        'fields'=>array('id', 'dt_sent', 'content'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1571', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
}
$messages = isset($rc['messages']) ? $rc['messages'] : array();




//
// Find messages that have been erased by the bug in Messages->Save when viewing message
//
print "Loading history...\n";
$strsql = "SELECT tnid, table_key, table_field, new_value "
    . "FROM ciniki_musicfestivals_history "
    . "WHERE table_name = 'ciniki_musicfestival_messages' "
    . "AND table_field IN ('dt_sent', 'content') "
    . "ORDER BY table_key, log_date "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1568', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
}
$rows = isset($rc['rows']) ? $rc['rows'] : array();

$message = [
    'id' => 0,
    'tnid' => 0,
    'dt_sent' => '',
    'content' => '',
    ];

print "Checking messages...\n";
foreach($rows as $row) {

    if( $row['table_key'] != $message['id'] ) {
        //
        // Process message update
        //
        $message = [
            'id' => $row['table_key'],
            'tnid' => $row['tnid'],
            'dt_sent' => '',
            'content' => '',
            'restored' => 'no',
            ];
    }
    if( $row['table_field'] == 'dt_sent' ) {
        $message['dt_sent'] = 'yes';
    }
    elseif( $row['table_field'] == 'content' && $row['new_value'] != '' ) {
        $message['content'] = $row['new_value'];
    }
    elseif( $row['table_field'] == 'content' && $row['new_value'] == '' 
        && $message['dt_sent'] == 'yes' && $message['content'] != '' 
        && $message['restored'] == 'no' 
        ) {
        // Restore message
        if( isset($messages[$message['id']]) ) {
            error_log("Restore: " . $message['id']);
            $message['restored'] = 'yes';
            $rc = ciniki_core_objectUpdate($ciniki, $message['tnid'], 'ciniki.musicfestivals.message', $message['id'], [
                'content' => $message['content'],
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1569', 'msg'=>'Unable to update the message', 'err'=>$rc['err']));
            } 
        }
    }
}





print "Done\n";

function print_usage($argv) {
    print "php {$argv[0]} <tnid> <old_festival_id> <new_festival_id>\n";
    exit;
}
?>

