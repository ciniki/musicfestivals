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
function ciniki_musicfestivals_messageQueue(&$ciniki, $tnid, $args) {

    //
    // Send message
    //
    if( isset($args['send']) && $args['send'] == 'all' ) {
        $errors = '';
        $num_errors = 0;
        $num_sent = 0;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        foreach($args['message']['teachers'] as $teacher) {
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'customer_id'=>$teacher['id'],
                'customer_email'=>$teacher['name'],
                'customer_name'=>$teacher['email'],
                'subject'=>$args['message']['subject'],
                'text_content'=>$args['message']['content'],
    //            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
                ));
            if( $rc['stat'] != 'ok' ) {
                $errors .= $rc['err']['code'] . ' - ' . $rc['err']['msg'];
                $num_errors++;
            } else {
                $num_sent++;
            }
            $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
        }
        foreach($args['message']['competitors'] as $competitor) {
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'customer_email'=>$competitor['name'],
                'customer_name'=>$competitor['email'],
                'object'=>'ciniki.musicfestivals.competitor',
                'object_id'=>$competitor['id'],
                'subject'=>$args['message']['subject'],
                'text_content'=>$args['message']['content'],
    //            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
                ));
            if( $rc['stat'] != 'ok' ) {
                $errors .= $rc['err']['code'] . ' - ' . $rc['err']['msg'];
                $num_errors++;
            } else {
                $num_sent++;
            }
            $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
        }

        //
        // Update status to sent
        //
        if( $num_sent > 0 ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.message', $args['message']['id'], array('status'=>50, 'dt_sent'=>$dt->format('Y-m-d H:i:s')), 0x04);
            if( $rc['stat'] != 'ok' ) {
                $errors .= "Unable to update status to sent, don't try to send this message again";
            }
        }

        if( $num_errors > 0 && $num_sent == 0 ) {
            return array('stat'=>'ok', 'num_sent'=>$num_sent, 'msg'=>"No messages sent, the following errors occurred: \n{$errors}");
        } elseif( $num_errors > 0 && $num_sent > 0 ) {
            return array('stat'=>'ok', 'num_sent'=>$num_sent, 'msg'=>"{$num_sent} messages sent, and {$num_errors} unable to send: \n{$errors}");
        } elseif( $num_errors == 0 && $num_sent > 0 ) {
            return array('stat'=>'ok', 'num_sent'=>$num_sent, 'msg'=>"{$num_sent} messages sent");
        } else {
            return array('stat'=>'ok', 'num_sent'=>$num_sent, 'msg'=>'No messages sent');
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
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
            'customer_email'=>$email,
            'customer_name'=>$name,
            'subject'=>$args['message']['subject'],
            'text_content'=>$args['message']['content'],
//            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
        return array('stat'=>'ok', 'msg'=>'Message sent, please check your email.');
    }

    return array('stat'=>'ok');
}
?>