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
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.494', 'msg'=>'Email has already been scheduled'));
        } 
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.494', 'msg'=>'Email has already been sent'));
    }

    $args['message'] = $message;
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageQueue');
    return ciniki_musicfestivals_messageQueue($ciniki, $args['tnid'], $args);

    //
    // Send message
    //
    if( isset($args['send']) && $args['send'] == 'all' ) {
        $errors = '';
        $num_errors = 0;
        $num_sent = 0;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        foreach($message['teachers'] as $teacher) {
            $rc = ciniki_mail_hooks_addMessage($ciniki, $args['tnid'], array(
                'customer_id'=>$teacher['id'],
                'customer_email'=>$teacher['name'],
                'customer_name'=>$teacher['email'],
                'subject'=>$message['subject'],
                'text_content'=>$message['content'],
    //            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
                ));
            if( $rc['stat'] != 'ok' ) {
                $errors .= $rc['err']['code'] . ' - ' . $rc['err']['msg'];
                $num_errors++;
            } else {
                $num_sent++;
            }

        }
        foreach($message['competitors'] as $competitor) {
            $rc = ciniki_mail_hooks_addMessage($ciniki, $args['tnid'], array(
                'customer_email'=>$competitor['name'],
                'customer_name'=>$competitor['email'],
                'object'=>'ciniki.musicfestivals.competitor',
                'object_id'=>$competitor['id'],
                'subject'=>$message['subject'],
                'text_content'=>$message['content'],
    //            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
                ));
            if( $rc['stat'] != 'ok' ) {
                $errors .= $rc['err']['code'] . ' - ' . $rc['err']['msg'];
                $num_errors++;
            } else {
                $num_sent++;
            }
        }

        //
        // Update status to sent
        //
        if( $num_sent > 0 ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.message', $message['id'], array('status'=>50, 'dt_sent'=>$dt->format('Y-m-d H:i:s')), 0x04);
            if( $rc['stat'] != 'ok' ) {
                $errors .= "Unable to update status to sent, don't try to send this message again";
            }
        }

        if( $num_errors > 0 && $num_sent == 0 ) {
            return array('stat'=>'ok', 'msg'=>"No messages sent, the following errors occurred: \n{$errors}");
        } elseif( $num_errors > 0 && $num_sent > 0 ) {
            return array('stat'=>'ok', 'msg'=>"{$num_sent} messages sent, and {$num_errors} unable to send: \n{$errors}");
        } elseif( $num_errors == 0 && $num_sent > 0 ) {
            return array('stat'=>'ok', 'msg'=>"{$num_sent} messages sent");
        } else {
            return array('stat'=>'ok', 'msg'=>'No messages sent');
        }


    } 
    //
    // Default to send test message
    //
    else {
        //
        // Get the users email
        //
        $strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
            . "FROM ciniki_users "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.495', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
        }
        $name = $rc['user']['name'];
        $email = $rc['user']['email'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $args['tnid'], array(
            'customer_email'=>$email,
            'customer_name'=>$name,
            'subject'=>$message['subject'],
            'text_content'=>$message['content'],
//            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$args['tnid']);
        return array('stat'=>'ok', 'msg'=>'Message sent, please check your email.');
    }

    return array('stat'=>'ok');
}
?>
