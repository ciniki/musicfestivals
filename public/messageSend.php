<?php
//
// Description
// ===========
// This method will return all the information about an mail.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the mail is attached to.
// message_id:          The ID of the mail to send.
//
// Returns
// -------
//
function ciniki_musicfestivals_messageSend(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'send'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Send Message'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the message
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageLoad');
    $rc = ciniki_musicfestivals_messageLoad($ciniki, $args['tnid'], array(
        'message_id' => $args['message_id'],
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
    if( $message['status'] != 10 ) {
        if( $message['status'] == 30 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.527', 'msg'=>'Email has already been scheduled'));
        } 
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.494', 'msg'=>'Email has already been sent'));
    }

    $args['message'] = $message;

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageQueue');
    return ciniki_musicfestivals_messageQueue($ciniki, $args['tnid'], $args);
}
?>
