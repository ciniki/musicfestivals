<?php
//
// Description
// -----------
// Send an email with the change request details 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the provincials tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_crEmail(&$ciniki, $tnid, $args) {

    if( !isset($args['cr']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1231', 'msg'=>'No change request supplied'));
    }
    $cr = $args['cr'];

    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1232', 'msg'=>'No registration supplied'));
    }
    $registration = $args['registration'];

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Check status text
    //
    if( !isset($cr['status_text']) ) {
        $cr['status_text'] = 'Unknown';
        if( isset($cr['status']) ) {
            $cr['status_text'] = $maps['cr']['status'][$cr['status']];
        }
    }

    //
    // Set up the subject
    //
    $subject = 'Change request submitted';
    if( $cr['status'] == 70 ) {
        $subject = 'Change request completed';
    } elseif( $cr['status'] > 20 ) {
        $subject = 'Change request updated';
    }
    
    $email_content = '';
    $email_text = '';
    if( $cr['dt_submitted'] != '' ) {
        $dt = new DateTime($cr['dt_submitted'], new DateTimezone('UTC'));
        $dt->setTimezone(new DateTimezone($intl_timezone));
        $cr['date_submitted'] = $dt->format('M j, Y g:i:s a');
    } 

    //
    // Process the change request into email
    //
    $email_content .= "<table><tbody>";
    $email_content .= "<tr><th style='text-align: right;'>Competitor</th><td>{$registration['display_name']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Date Submitted</th><td>{$cr['date_submitted']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Status</th><td>{$cr['status_text']}</td></tr>";
    $email_content .= "</tbody></table>";
    $email_content .= "<br/><br/>";
    $email_content .= $cr['content'];

    $email_text .= "Competitor: {$registration['display_name']}\n";
    $email_text .= "Date Submitted: {$cr['date_submitted']}\n";
    $email_text .= "Status: {$cr['status_text']}\n";
    $email_text .= "\n\n";

    $email_text .= $cr['content'];

    //
    // Send an email to the customer **FUTURE**
    //
    if( isset($args['adjudicator-subject'])  && $args['adjudicator-subject'] != '' 
        && isset($recommendation['adjudicator_name']) 
        && isset($recommendation['adjudicator_email']) && $recommendation['adjudicator_email'] != '' 
        ) {
/*        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
            'object' => 'ciniki.musicfestivals.recommendation',
            'object_id' => $recommendation['id'],
            'subject' => $subject,
            'html_content' => $email_content,
            'text_content' => $email_text,
            'customer_name' => $recommendation['adjudicator_name'],
            'customer_email' => $recommendation['adjudicator_email'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1233', 'msg'=>'Unable to email recommendations', 'err'=>$rc['err']));
        } else {
            $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
        } */
    }

    //
    // Send an email to the emails specified
    //
    if( isset($args['notify-emails']) && $args['notify-emails'] != '' ) {
        
        // FIXME: If status other than 20, change status.
        $emails = explode(',', $args['notify-emails']);
        foreach($emails as $email) {
            $email = trim($email);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'object' => 'ciniki.musicfestivals.registration',
                'object_id' => $registration['registration_id'],
                'subject' => $subject,
                'html_content' => $email_content,
                'text_content' => $email_text,
                'customer_email' => $email,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1422', 'msg'=>'Unable to email change request', 'err'=>$rc['err']));
            } else {
                $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
            }
        }
    }

    return array('stat'=>'ok');
}
?>
