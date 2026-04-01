<?php
//
// Description
// -----------
// This function sends the provincials registration instructions to the recommendation entry
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsRegReminderSend(&$ciniki, $tnid, $args) {

    $festival = $args['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $args['member'];
    $provincials_tnid = $member['tnid'];
    $entry = $args['entry'];

    //
    // Load provincials festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $provincials_tnid, $provincials_festival_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1489', 'msg'=>'Unable to load provincials festival', 'err'=>$rc['err']));
    }
    $provincials = $rc['festival'];

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
    // Get the website base url
    //
    if( !isset($provincials['site-base-url']) || $provincials['site-base-url'] == '' ) {
        $strsql = "SELECT sites.id, "
            . "sites.permalink, "
            . "domains.domain "
            . "FROM ciniki_wng_sites AS sites "
            . "LEFT JOIN ciniki_tenant_domains AS domains ON ("
                . "sites.domain_id = domains.id "
                . "AND domains.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "WHERE sites.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'site');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1493', 'msg'=>'Unable to load site', 'err'=>$rc['err']));
        }
        if( !isset($rc['site']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1494', 'msg'=>'Unable to find requested site'));
        }
        $site = $rc['site'];
        if( isset($site['domain']) && $site['domain'] != '' ) {
            $base_url = 'https://' . $site['domain'];
        } else {
            $base_url = 'https://' . $ciniki['config']['wng']['master.domain'] . '/' . $site['permalink'];
        }
    } else {    
        $base_url = $provincials['site-base-url'];
    }
        
    //
    // Check for provincials from name or email address to send from
    //
    $from_name = '';
    if( isset($festival['provincials-smtp-from-name']) && $festival['provincials-smtp-from-name'] != '' ) {
        $from_name = $festival['provincials-smtp-from-name'];
    }
    $from_address = '';
    if( isset($festival['provincials-smtp-from-address']) && $festival['provincials-smtp-from-address'] != '' ) {
        $from_address = $festival['provincials-smtp-from-address'];
    }

    //
    // Load the local registration
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1495', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1496', 'msg'=>'Unable to find requested registration'));
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
    $template = 'provincials-email-register-reminder';
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
    $register_url = $base_url . "/ahk/musicfestival/register/{$entry['uuid']}/{$registration['uuid']}";
    if( !isset($festival["{$template}-subject"]) || $festival["{$template}-subject"] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1497', 'msg'=>'No subject specified'));
    }
    $subject = $festival["{$template}-subject"];
    if( !isset($festival["{$template}-message"]) || $festival["{$template}-message"] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1498', 'msg'=>'No message specified'));
    }
    $message = $festival["{$template}-message"];

    //
    // run substitutions
    //
    $subject = str_replace('{_name_}', $registration_name, $subject);
    $message = str_replace('{_name_}', $registration_name, $message);
    $subject = str_replace('{_livevirtual_}', $class_live_virtual, $subject);
    $message = str_replace('{_livevirtual_}', $class_live_virtual, $message);
    $subject = str_replace('{_registerlink_}', $register_url, $subject);
    $message = str_replace('{_registerlink_}', $register_url, $message);
    $dt = new DateTime($member['deadline'], new DateTimezone($intl_timezone));
    $now = new DateTime('now', new DateTimezone($intl_timezone));
    $dayshours = '';
    if( $dt > $now ) {
        $interval = $dt->diff($now);
        if( $interval->format('d') > 0 ) {
            $dayshours = $interval->format('%d days');
        } else {
            $dayshours = $interval->format('%h hours');
        }
    }
    $subject = str_replace('{_dayshours_}', $dayshours, $subject);
    $message = str_replace('{_dayshours_}', $dayshours, $message);

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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1499', 'msg'=>'Unable to open customer', 'err'=>$rc['err']));
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
                            'from_name' => $from_name,
                            'from_address' => $from_address,
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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1500', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
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
                        'from_name' => $from_name,
                        'from_address' => $from_address,
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
        'status' => 45,
        ], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1501', 'msg'=>'Unable to update the recommendationentry', 'err'=>$rc['err']));
    }
    
    return array('stat'=>'ok');
}
?>
