<?php
//
// Description
// -----------
// This function sends the provincials recommendation invite email to the registration
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsInviteSend(&$ciniki, $tnid, $args) {

    $festival = $args['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $args['member'];
    $provincials_tnid = $member['tnid'];
    $entry = $args['entry'];


    //
    // Get the website base url
    //
    if( !isset($festival['site-base-url']) || $festival['site-base-url'] == '' ) {
        $strsql = "SELECT sites.id, "
            . "sites.permalink, "
            . "domains.domain "
            . "FROM ciniki_wng_sites AS sites "
            . "LEFT JOIN ciniki_tenant_domains AS domains ON ("
                . "sites.domain_id = domains.id "
                . "AND domains.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sites.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'site');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1335', 'msg'=>'Unable to load site', 'err'=>$rc['err']));
        }
        if( !isset($rc['site']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1336', 'msg'=>'Unable to find requested site'));
        }
        $site = $rc['site'];
        if( isset($site['domain']) && $site['domain'] != '' ) {
            $base_url = 'https://' . $site['domain'];
        } else {
            $base_url = 'https://' . $ciniki['config']['wng']['master.domain'] . '/' . $site['permalink'];
        }
    } else {    
        $base_url = $festival['site-base-url'];
    }
        
    //
    // Load the local registration
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.private_name, "
        . "registrations.teacher_customer_id, "
        . "registrations.teacher2_customer_id, "
        . "registrations.billing_customer_id, "
        . "registrations.parent_customer_id, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
        . "registrations.competitor5_id "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $entry['local_reg_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1337', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1338', 'msg'=>'Unable to find requested registration'));
    }
    $registration = $rc['registration'];
  
    if( $registration['private_name'] != '' ) { 
        $registration_name = $registration['private_name'];
    } else {
        $registration_name = $registration['display_name'];
    }

    //
    // Prepare substitutions
    //
    $class_live_virtual = 'Live';
    if( ($entry['feeflags']&0x0a) == 0x08 ) {
        $class_live_virtual = 'Virtual';
    } elseif( ($entry['feeflags']&0x0a) == 0x02 ) {
        $class_live_virtual = 'Live';
    } elseif( ($entry['feeflags']&0x0a) == 0x0a ) {
        $class_live_virtual = 'Live OR Virtual';
    }

    //
    // Prepare the message
    // Note: in the message template, clickable links can be added <a href="{_acceptlink_}">Yes, I accept</a>
    //
    $accept_url = $base_url . "/ahk/musicfestival/provincials/{$entry['uuid']}/accept";
    $decline_url = $base_url . "/ahk/musicfestival/provincials/{$entry['uuid']}/decline";
    if( !isset($festival['provincials-email-invite-subject']) || $festival['provincials-email-invite-subject'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1332', 'msg'=>'No subject specified'));
    }
    $subject = $festival['provincials-email-invite-subject'];
    if( !isset($festival['provincials-email-invite-message']) || $festival['provincials-email-invite-message'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1333', 'msg'=>'No message specified'));
    }
    $message = $festival['provincials-email-invite-message'];

    //
    // run substitutions
    //
    $subject = str_replace('{_name_}', $registration_name, $subject);
    $message = str_replace('{_name_}', $registration_name, $message);
    $subject = str_replace('{_livevirtual_}', $class_live_virtual, $subject);
    $message = str_replace('{_livevirtual_}', $class_live_virtual, $message);
    $subject = str_replace('{_provincialsclass_}', $entry['class_code'] . ' - ' . $entry['class_name'], $subject);
    $message = str_replace('{_provincialsclass_}', $entry['class_code'] . ' - ' . $entry['class_name'], $message);
    $subject = str_replace('{_provincialsclasscode_}', $entry['class_code'], $subject);
    $message = str_replace('{_provincialsclasscode_}', $entry['class_code'], $message);
    $subject = str_replace('{_provincialsclassname}', $entry['class_name'], $subject);
    $message = str_replace('{_provincialsclassname}', $entry['class_name'], $message);
    $subject = str_replace('{_acceptlink_}', $accept_url, $subject);
    $message = str_replace('{_acceptlink_}', $accept_url, $message);
    $subject = str_replace('{_declinelink_}', $decline_url, $subject);
    $message = str_replace('{_declinelink_}', $decline_url, $message);
    $subject = str_replace('{_deadline_}', $member['deadline'], $subject);
    $message = str_replace('{_deadline_}', $member['deadline'], $message);

    //
    // Build a list of email addresses
    //
    $emails = [];
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    foreach(['teacher_customer_id', 'teacher2_customer_id', 'billing_customer_id', 'parent_customer_id'] AS $field) {
        if( $registration[$field] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, [
                'customer_id' => $registration[$field],
                ]);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1339', 'msg'=>'Unable to open customer', 'err'=>$rc['err']));
            }
            if( isset($rc['customer']['emails']) ) {
                foreach($rc['customer']['emails'] as $email) {
                    if( !isset($emails[$email['address']]) ) {
                        $emails[$email['address']] = [
                            'customer_id' => $registration[$field],
                            'customer_name' => $rc['customer']['display_name'],
                            'customer_email' => $email['address'],
                            'parent_object' => 'ciniki.musicfestivals.recommendationentry',
                            'parent_object_id' => $entry['id'],
                            'object' => 'ciniki.musicfestivals.registration',
                            'object_id' => $registration['id'],
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
        if( $registration["competitor{$i}_id"] > 0 ) {
            $strsql = "SELECT competitors.id, "
                . "competitors.name, "            
                . "competitors.email "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "WHERE competitors.id = '" . ciniki_core_dbQuote($ciniki, $registration["competitor{$i}_id"]) . "' "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1340', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
            }
            if( isset($rc['competitor']['email']) && $rc['competitor']['email'] != '' ) {
                if( !isset($emails[$rc['competitor']['email']]) ) {
                    $emails[$rc['competitor']['email']] = [
                        'customer_id' => 0,
                        'customer_name' => $rc['competitor']['name'],
                        'customer_email' => $rc['competitor']['email'],
                        'parent_object' => 'ciniki.musicfestivals.recommendationentry',
                        'parent_object_id' => $entry['id'],
                        'object' => 'ciniki.musicfestivals.registration',
                        'object_id' => $registration['id'],
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
    $dt = new DateTime('now', new DateTimezone('UTC'));
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendationentry', $entry['id'], [
        'status' => 35,
        'dt_invite_sent' => $dt->format('Y-m-d H:i:s'),
        ], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1334', 'msg'=>'Unable to update the recommendationentry', 'err'=>$rc['err']));
    }
    
    return array('stat'=>'ok');
}
?>
