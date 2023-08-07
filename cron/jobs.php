<?php
//
// Description
// ===========
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_musicfestivals_cron_jobs(&$ciniki) {
//    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for music festival jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageLoad');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageQueue');

    $dt = new DateTime('now', new DateTimezone('UTC'));
    $dt->add(new DateInterval('PT5M'));
    $last_dt = clone $dt;
    $last_dt->sub(new DateInterval('P1D'));

    //
    // Check for any message that are schedule and should be sent
    //
    $strsql = "SELECT messages.id, "
        . "messages.tnid "
        . "FROM ciniki_musicfestival_messages AS messages "
        . "WHERE status = 30 "
        . "AND dt_scheduled <= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:i:s')) . "' "
        . "AND dt_scheduled > '" . ciniki_core_dbQuote($ciniki, $last_dt->format('Y-m-d H:i:s')) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'messages', 'fname'=>'id', 'fields'=>array('id', 'tnid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.505', 'msg'=>'Unable to load messages', 'err'=>$rc['err']));
    }
    $messages = isset($rc['messages']) ? $rc['messages'] : array();

    //
    // Send each message
    //
    foreach($messages as $item) {
        //
        // Clear tenant settings
        //
        if( isset($ciniki['tenant']['settings']) ) {
            unset($ciniki['tenant']['settings']);
        }

        //
        // Load the message
        //
        $rc = ciniki_musicfestivals_messageLoad($ciniki, $item['tnid'], array(
            'message_id' => $item['id'],
            'emails' => 'yes',
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.492', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
        }
        if( !isset($rc['message']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.493', 'msg'=>'No message found', 'err'=>$rc['err']));
        }
        $message = isset($rc['message']) ? $rc['message'] : array();

        //
        // Make sure the message has the correct status
        //
        if( $message['status'] != 30 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.494', 'msg'=>'Email has already been sent'));
        }
        
        //
        // Add the message to the queue
        //
        $rc = ciniki_musicfestivals_messageQueue($ciniki, $item['tnid'], array(
            'message' => $message,
            'send' => 'all',
            )); 
    }

    return array('stat'=>'ok');
}
?>
