<?php
//
// Description
// -----------
// Send an email with the recommendation details and entries
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the provincials tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_recommendationEmail(&$ciniki, $tnid, $args) {

    if( !isset($args['recommendation']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1051', 'msg'=>'No recommendation supplied'));
    }
    $recommendation = $args['recommendation'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    $email_content = '';
    $email_text = '';
    if( $recommendation['date_submitted'] != '' ) {
        $dt = new DateTime($recommendation['date_submitted'], new DateTimezone($intl_timezone));
        $recommendation['date_submitted'] = $dt->format('M j, Y g:i:s a');
    } 

    //
    // Process the recommendation into email
    //
    $email_content .= "<table><tbody>";
    $email_content .= "<tr><th style='text-align: right;'>Section</th><td>{$recommendation['section_name']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Festival</th><td>{$recommendation['member_name']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Adjudicator Name</th><td>{$recommendation['adjudicator_name']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Adjudicator Phone</th><td>{$recommendation['adjudicator_phone']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Adjudicator Email</th><td>{$recommendation['adjudicator_email']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Acknowledgement</th><td>{$recommendation['acknowledgement']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Date Submitted</th><td>{$recommendation['date_submitted']}</td></tr>";
    $email_content .= "<tr><th style='text-align: right;'>Status</th><td>{$recommendation['status_text']}</td></tr>";
    $email_content .= "</tbody></table>";
    $email_content .= "<br/><br/>";

    $email_text .= "Section: {$recommendation['section_name']}\n";
    $email_text .= "Festival: {$recommendation['member_name']}\n";
    $email_text .= "Adjudicator Name: {$recommendation['adjudicator_name']}\n";
    $email_text .= "Adjudicator Phone: {$recommendation['adjudicator_phone']}\n";
    $email_text .= "Adjudicator Email: {$recommendation['adjudicator_email']}\n";
    $email_text .= "Acknowledgement: {$recommendation['acknowledgement']}\n";
    $email_text .= "Date Submitted: {$recommendation['date_submitted']}\n";
    $email_text .= "Status: {$recommendation['status_text']}\n";
    $email_text .= "\n\n";
  

    $positions = [
        '1' => '1st Recommendation',
        '2' => '2nd Recommendation',
        '3' => '3rd Recommendation',
        '101' => '1st Alternate',
        '102' => '2nd Alternate',
        '103' => '3rd Alternate',
        ];

    //
    // Check to make sure at least 1 class is specified
    //
    $entries = array();
    foreach($args['classes'] as $cid => $class) {
        $class_email_content = '';
        $class_email_text = '';
        foreach($positions as $position => $label) {
            if( isset($recommendation['entries'][$cid][$position]) ) {
                $entry = $recommendation['entries'][$cid][$position];
                if( isset($args['email-type']) && $args['email-type'] == 'updated' ) {
                    $class_email_content .= "<tr><td>{$entry['status_text']}</td><td>{$label}</td><td>{$entry['name']}</td><td>{$entry['mark']}</td></tr>";
                    $class_email_text .= "{$entry['status_text']} - {$label} - {$entry['name']} - Mark: {$entry['mark']}\n";
                    if( $entry['notes'] != '' ) {
                        $class_email_text .= "Notes: {$entry['notes']}\n";
                        $notes = preg_replace("/\n/", '<br/>', $entry['notes']) . "\n";
                        $class_email_content .= "<tr><td></td><td colspan=4><i>{$notes}</i></td></tr>";
                    }
                } else {
                    $class_email_content .= "<tr><td>{$label}</td><td>{$entry['name']}</td><td>{$entry['mark']}</td></tr>";
                    $class_email_text .= "{$label} - {$entry['name']} - {$entry['mark']}\n";
                }
            }
        }

        //
        // Check if at least 1 recommendation or alternate specified
        //
        if( $class_email_content != '' ) {
            $email_content .= "<h3>{$class['code']} - {$class['name']}</h3>"
                . "<table>";
            if( isset($args['email-type']) && $args['email-type'] == 'updated' ) {
                $email_content .= "<thead><tr><th>Status</th><th>Position</th><th>Competitor</th><th>Mark</th></tr></thead>";
            } else {
                $email_content .= "<thead><tr><th>Position</th><th>Competitor/Class</th><th>Mark</th></tr></thead>";
            }
            $email_content .= "<tbody>" 
                . $class_email_content
                . "</tbody></table>" 
                . "<br/>";
            $email_text .= "CLASS: {$class['code']} - {$class['name']}\n"
                . $class_email_text
                . "\n\n";
        }
    }

    //
    // Send an email to the adjudicator
    //
    if( isset($args['adjudicator-subject'])  && $args['adjudicator-subject'] != '' 
        && isset($recommendation['adjudicator_name']) 
        && isset($recommendation['adjudicator_email']) && $recommendation['adjudicator_email'] != '' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
            'object' => 'ciniki.musicfestivals.recommendation',
            'object_id' => $recommendation['id'],
            'subject' => $args['adjudicator-subject'],
            'html_content' => $email_content,
            'text_content' => $email_text,
            'customer_name' => $recommendation['adjudicator_name'],
            'customer_email' => $recommendation['adjudicator_email'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1049', 'msg'=>'Unable to email recommendations', 'err'=>$rc['err']));
        } else {
            $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
        }
    }

    //
    // Check for the member email address
    //
    if( isset($args['member-subject']) && $args['member-subject'] != ''
        && isset($args['members']) && is_array($args['members'])
        ) {
        foreach($args['members'] as $customer_id => $customer) {
            //
            // Lookup customer record
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, 
                array('customer_id'=>$customer_id, 'phones'=>'no', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']['emails'][0]['address']) ) {
                $customer = $rc['customer'];
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                    'object' => 'ciniki.musicfestivals.recommendation',
                    'object_id' => $recommendation['id'],
                    'subject' => $args['member-subject'],
                    'html_content' => $email_content,
                    'text_content' => $email_text,
                    'customer_id' => $customer_id,
                    'customer_name' => $customer['display_name'],
                    'customer_email' => $customer['emails'][0]['address'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    error_log("Unable to email member contact for recommendation: " . $recommedation['id']);
                } else {
                    $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
                }
            } else {
                error_log("No member contact info found for recommendation: " . $recommendation['id']);
            }
        }
    }

    //
    // Send an email to the emails specified
    //
    if( isset($args['notify-subject']) && $args['notify-subject'] != ''
        && isset($args['notify-emails']) && $args['notify-emails'] != '' 
        ) {
        $emails = explode(',', $args['notify-emails']);
        foreach($emails as $email) {
            $email = trim($email);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'object' => 'ciniki.musicfestivals.recommendation',
                'object_id' => $recommendation['id'],
                'subject' => $args['notify-subject'],
                'html_content' => $email_content,
                'text_content' => $email_text,
                'customer_email' => $email,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1050', 'msg'=>'Unable to email recommendations', 'err'=>$rc['err']));
            } else {
                $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
            }
        }
    }

    return array('stat'=>'ok');
}
?>
