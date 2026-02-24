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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.531', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
        }
        if( !isset($rc['message']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.532', 'msg'=>'No message found', 'err'=>$rc['err']));
        }
        $message = isset($rc['message']) ? $rc['message'] : array();

        //
        // Make sure the message has the correct status
        //
        if( $message['status'] != 30 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.533', 'msg'=>'Email has already been sent'));
        }
        
        //
        // Add the message to the queue
        //
        $rc = ciniki_musicfestivals_messageQueue($ciniki, $item['tnid'], array(
            'message' => $message,
            'send' => 'all',
            )); 
    }

    //
    // Check for volunteer email reminders
    //
    $end_dt = new DateTime('now', new DateTimezone('UTC'));
    $start_dt = clone $end_dt;
    $start_dt->sub(new DateInterval('PT1H'));
    $end_dt->add(new DateInterval('PT5M'));
    $strsql = "SELECT notifications.id AS notification_id, "   
        . "notifications.uuid, "
        . "notifications.tnid, "
        . "notifications.assignment_id, "
        . "notifications.template "
        . "FROM ciniki_musicfestival_volunteer_notifications AS notifications "
        . "WHERE notifications.scheduled_dt > '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
        . "AND notifications.scheduled_dt < '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1299', 'msg'=>'Unable to get music festival volunteer notifications', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'ok');
    }
    $notifications = $rc['rows'];

    foreach($notifications as $notification) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerEmail');
        $rc = ciniki_musicfestivals_volunteerEmail($ciniki, $notification['tnid'], $notification);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1300', 'msg'=>'Unable to send music festival volunteer message', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
