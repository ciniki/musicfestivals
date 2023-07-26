<?php
//
// Description
// -----------
// This method will return the list of Mails for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Mail for.
//
// Returns
// -------
//
function ciniki_musicfestivals_messageList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of messages
    //
    $strsql = "SELECT ciniki_musicfestival_messages.id, "
        . "ciniki_musicfestival_messages.festival_id, "
        . "ciniki_musicfestival_messages.subject, "
        . "ciniki_musicfestival_messages.status, "
        . "ciniki_musicfestival_messages.dt_scheduled, "
        . "ciniki_musicfestival_messages.dt_sent "
        . "FROM ciniki_musicfestival_messages "
        . "WHERE ciniki_musicfestival_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'subject', 'status', 'dt_scheduled', 'dt_sent')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $messages = isset($rc['messages']) ? $rc['messages'] : array();
    $message_ids = array();
    foreach($messages as $iid => $message) {
        $message_ids[] = $message['id'];
    }

    return array('stat'=>'ok', 'messages'=>$messages, 'nplist'=>$message_ids);
}
?>
