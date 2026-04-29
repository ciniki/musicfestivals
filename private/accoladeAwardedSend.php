<?php
//
// Description
// -----------
// This function sends the accolade awarded email to the registration
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_accoladeAwardedSend(&$ciniki, $tnid, $args) {

    $festival = $args['festival'];
    $winner = $args['winner'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    if( !isset($winner['awarded_email_subject']) || $winner['awarded_email_subject'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1504', 'msg'=>'No subject specified'));
    }
    $subject = $winner['awarded_email_subject'];
    if( !isset($winner['awarded_email_content']) || $winner['awarded_email_content'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1505', 'msg'=>'No message specified'));
    }
    $message = $winner['awarded_email_content'];

    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    $current_date = $dt->format('F j, Y');

    $awarded_amount = '$' . number_format($winner['awarded_amount'], 2);

    $donor_thankyou_info = $winner['donor_thankyou_info'];
    $donor_thankyou_info = preg_replace("/^<p>/", '', $donor_thankyou_info);
    $donor_thankyou_info = preg_replace("/<\/p>$/", '', $donor_thankyou_info);

    //
    // run substitutions
    //
    $subject = str_replace('{_name_}', $winner['private_name'], $subject);
    $message = str_replace('{_name_}', $winner['private_name'], $message);
    $subject = str_replace('{_date_}', $current_date, $subject);
    $message = str_replace('{_date_}', $current_date, $message);
    $subject = str_replace('{_accolade_}', $winner['accolade_name'], $subject);
    $message = str_replace('{_accolade_}', $winner['accolade_name'], $message);
    $subject = str_replace('{_accoladename_}', $winner['accolade_name'], $subject);
    $message = str_replace('{_accoladename_}', $winner['accolade_name'], $message);
    $subject = str_replace('{_accoladeamount_}', $awarded_amount, $subject);
    $message = str_replace('{_accoladeamount_}', $awarded_amount, $message);
    $subject = str_replace('{_awardedamount_}', $awarded_amount, $subject);
    $message = str_replace('{_awardedamount_}', $awarded_amount, $message);
    $subject = str_replace('{_discipline_}', $awarded_amount, $subject);
    $message = str_replace('{_discipline_}', $awarded_amount, $message);
    $subject = str_replace('{_thankyou_}', $donor_thankyou_info, $subject);
    $message = str_replace('{_thankyou_}', $donor_thankyou_info, $message);

    //
    // Build a list of email addresses
    //
    $emails = [];
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    foreach(['billing_customer_id', 'parent_customer_id'] AS $field) {
        if( $winner[$field] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, [
                'customer_id' => $winner[$field],
                ]);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1508', 'msg'=>'Unable to open customer', 'err'=>$rc['err']));
            }
            if( isset($rc['customer']['emails']) ) {
                foreach($rc['customer']['emails'] as $email) {
                    if( !isset($emails[$email['address']]) ) {
                        $emails[$email['address']] = [
                            'customer_id' => $winner[$field],
                            'customer_name' => $rc['customer']['display_name'],
                            'customer_email' => $email['address'],
                            'parent_object' => 'ciniki.musicfestivals.accoladewinner',
                            'parent_object_id' => $winner['id'],
                            'object' => 'ciniki.musicfestivals.registration',
                            'object_id' => $winner['registration_id'],
                            'subject' => $subject,
                            'tinymce' => 'yes',
                            'html_content' => $message,
                            'text_content' => html_entity_decode(strip_tags($message)),
                            ];
                    }
                }
            }
        }
    }
    for($i = 1; $i <= 5; $i++) {
        if( $winner["competitor{$i}_id"] > 0 ) {
            $strsql = "SELECT competitors.id, "
                . "competitors.name, "            
                . "competitors.email "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "WHERE competitors.id = '" . ciniki_core_dbQuote($ciniki, $winner["competitor{$i}_id"]) . "' "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1564', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
            }
            if( isset($rc['competitor']['email']) && $rc['competitor']['email'] != '' ) {
                if( !isset($emails[$rc['competitor']['email']]) ) {
                    $emails[$rc['competitor']['email']] = [
                        'customer_id' => 0,
                        'customer_name' => $rc['competitor']['name'],
                        'customer_email' => $rc['competitor']['email'],
                        'parent_object' => 'ciniki.musicfestivals.accoladewinner',
                        'parent_object_id' => $winner['id'],
                        'object' => 'ciniki.musicfestivals.registration',
                        'object_id' => $winner['registration_id'],
                        'subject' => $subject,
                        'tinymce' => 'yes',
                        'html_content' => $message,
                        'text_content' => html_entity_decode(strip_tags($message)),
                        ];
                }
            }
        }
    }

    // 
    // Add the message
    //
    $errors = [];
    $num_errors = 0;
    $num_sent = 0;
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
    foreach($emails as $email) {
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, $email);
        if( $rc['stat'] != 'ok' ) {
            $errors .= $rc['err']['code'] . ' - ' . $rc['err']['msg'];
            $num_errors++;
        } else {
            $num_sent++;
            $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid' => $tnid);
        }
    }

    //
    // Update the recommendation
    //
    if( ($winner['flags']&0x01) == 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.accoladewinner', $winner['id'], [
            'flags' => ($winner['flags'] | 0x01),
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1485', 'msg'=>'Unable to update the winner', 'err'=>$rc['err']));
        }
    }
    
    return array('stat'=>'ok');
}
?>
