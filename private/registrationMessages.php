<?php
//
// Description
// -----------
// Load the messages sent to the registration via the messages module or direct
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_registrationMessages(&$ciniki, $tnid, $registration_id) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the message sent directly to this registration
    //
    $strsql = "SELECT messages.id, "
        . "messages.subject, "
        . "messages.status, "
        . "messages.status AS status_text, "
        . "messages.dt_scheduled AS dt_scheduled_date, "
        . "messages.dt_scheduled AS dt_scheduled_time, "
        . "messages.dt_sent AS dt_sent_date, "
        . "messages.dt_sent AS dt_sent_time "
        . "FROM ciniki_musicfestival_messagerefs AS refs "
        . "INNER JOIN ciniki_musicfestival_messages AS messages ON ("
            . "refs.message_id = messages.id "
//                . "AND messages.status > 10 "
            . ") "
        . "WHERE refs.object_id = '" . ciniki_core_dbQuote($ciniki, $registration_id) . "' "
        . "AND refs.object = 'ciniki.musicfestivals.registration' "
        . "AND refs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY dt_sent DESC, dt_scheduled DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'subject', 'status', 'status_text', 
                'dt_scheduled_date', 'dt_scheduled_time', 'dt_sent_date', 'dt_sent_time',
                ),
            'maps'=>array(
                'status_text'=>$maps['message']['status'],
                ),
            'utctotz'=>array(
                'dt_scheduled_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_scheduled_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                'dt_sent_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_sent_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.321', 'msg'=>'Unable to load messages', 'err'=>$rc['err']));
    }
    $messages = isset($rc['messages']) ? $rc['messages'] : array();
    foreach($messages as $mid => $message) {
        $messages[$mid]['type'] = 'message';
        if( $message['status'] == 30 ) {
            $messages[$mid]['date'] = $message['dt_scheduled_date'];
            $messages[$mid]['time'] = $message['dt_scheduled_time'];
        } else {
            $messages[$mid]['date'] = $message['dt_sent_date'];
            $messages[$mid]['time'] = $message['dt_sent_time'];
        }
    } 

    //
    // Load the list of emails sent to this registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
    $rc = ciniki_mail_hooks_objectMessages($ciniki, $tnid, [
        'object' => 'ciniki.musicfestivals.registration',
        'object_id' => $registration_id,
        'xml' => 'no',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1341', 'msg'=>'Unable to load emails', 'err'=>$rc['err']));
    }
    if( isset($rc['messages']) ) {
        foreach($rc['messages'] as $message) {
            $message['type'] = 'mail';
            $messages[] = $message;
        }
    }

    return array('stat'=>'ok', 'messages'=>$messages);
}
?>
