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
    // Load the attachments
    //
    $attachments = array();
    if( isset($args['message']['files']) && is_array($args['message']['files']) ) {
        //
        // Get the tenant storage directory
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
        $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $tenant_storage_dir = $rc['storage_dir'];

        //
        // Load the files
        //
        if( isset($args['message']['files']) && is_array($args['message']['files']) ) {
            foreach($args['message']['files'] as $file) {
                $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
                    . $args['message']['uuid'][0] . '/' 
                    . $args['message']['uuid'] . '_' . $file['filename'];
                if( is_file($storage_filename) ) {
                    $attachments[] = array(
                        'filename' => $file['filename'],
                        'content' => file_get_contents($storage_filename),
                        );
                }
            }
        }
    }

    //
    // Send message
    //
    if( isset($args['send']) && $args['send'] == 'all' ) {
        $errors = '';
        $num_errors = 0;
        $num_sent = 0;
        $emails = array();
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        foreach($args['message']['teachers'] as $teacher) {
            if( !isset($emails[$teacher['email']]) ) {
                $content = $args['message']['content'];
                $content = str_replace('{_first_}', $teacher['first'], $content);
                $content = str_replace('{_name_}', $teacher['name'], $content);
                $emails[$teacher['email']] = array(
                    'customer_id'=>$teacher['id'],
                    'customer_email'=>$teacher['email'],
                    'customer_name'=>$teacher['name'],
                    'subject'=>$args['message']['subject'],
                    'text_content'=>$content,
                    );
            }
        }
        foreach($args['message']['accompanists'] as $accompanist) {
            if( !isset($emails[$accompanist['email']]) ) {
                $content = $args['message']['content'];
                $content = str_replace('{_first_}', $accompanist['first'], $content);
                $content = str_replace('{_name_}', $accompanist['name'], $content);
                $emails[$accompanist['email']] = array(
                    'customer_id'=>$accompanist['id'],
                    'customer_email'=>$accompanist['email'],
                    'customer_name'=>$accompanist['name'],
                    'subject'=>$args['message']['subject'],
                    'text_content'=>$content,
                    );
            }
        }
        if( isset($args['message']['customers']) ) {
            foreach($args['message']['customers'] as $customer) {
                if( !isset($emails[$customer['email']]) ) {
                    $content = $args['message']['content'];
                    $content = str_replace('{_first_}', $customer['first'], $content);
                    $content = str_replace('{_name_}', $customer['name'], $content);
                    $emails[$customer['email']] = array(
                        'customer_id'=>$customer['id'],
                        'customer_email'=>$customer['email'],
                        'customer_name'=>$customer['name'],
                        'subject'=>$args['message']['subject'],
                        'text_content'=>$content,
                        );
                }
            }
        }
        foreach($args['message']['competitors'] as $cid => $competitor) {
            $idx = $competitor['email'];
            if( $args['message']['mtype'] == 40 ) {
                $idx = $competitor['name'] . '-' . $competitor['email'];
            }
            if( !isset($emails[$idx]) ) {
                $content = $args['message']['content'];
                $content = str_replace('{_first_}', $competitor['first'], $content);
                $content = str_replace('{_name_}', $competitor['name'], $content);
                $emails[$idx] = array(
                    'customer_email'=>$competitor['email'],
                    'customer_name'=>$competitor['name'],
                    'object'=>'ciniki.musicfestivals.competitor',
                    'object_id'=>$competitor['id'],
                    'subject'=>$args['message']['subject'],
                    'text_content'=>$content,
                    );
            }
        }

        foreach($emails as $email) {
            //
            // Add the attachments
            //
            $email['attachments'] = $attachments;

            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, $email);
            if( $rc['stat'] != 'ok' ) {
                $errors .= $rc['err']['code'] . ' - ' . $rc['err']['msg'];
                $num_errors++;
            } else {
                $num_sent++;
                $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
            }
            unset($email['attachments']);
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
            'attachments'=>$attachments,
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
