<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_registrationTransferOutEmail(&$ciniki, $tnid, $request, $args) {

    
    //
    // Load the festival if not passed
    //
    if( !isset($args['festival']) ) {
        if( isset($registration['festival_id']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
            $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $registration['festival_id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival = $rc['festival'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1748', 'msg'=>'No festival specified'));
        }
    } else {
        $festival = $args['festival'];
    }

    //
    // Check for template
    //
    if( isset($festival['registration-transfers-email-subject']) && $festival['registration-transfers-email-subject'] != ''
        && isset($festival['registration-transfers-email-message']) && $festival['registration-transfers-email-message'] != ''
        ) {
        
        $subject = $festival['registration-transfers-email-subject'];
        $message = $festival['registration-transfers-email-message'];
    }
    if( isset($subject) && isset($message) ) {

        //
        // Check for substitutions 
        //
        $subject = str_replace('{_tofirstname_}', $args['tofirstname'], $subject);
        $message = str_replace('{_tofirstname_}', $args['tofirstname'], $message);
        $subject = str_replace('{_toname_}', $args['toname'], $subject);
        $message = str_replace('{_toname_}', $args['toname'], $message);
        $subject = str_replace('{_fromname_}', $args['fromname'], $subject);
        $message = str_replace('{_fromname_}', $args['fromname'], $message);
        $subject = str_replace('{_registrationname_}', $args['registration']['display_name'], $subject);
        $message = str_replace('{_registrationname_}', $args['registration']['display_name'], $message);
        $subject = str_replace('{_classcode_}', $args['registration']['class_code'], $subject);
        $message = str_replace('{_classcode_}', $args['registration']['class_code'], $message);
        $subject = str_replace('{_classname_}', $args['registration']['class_name'], $subject);
        $message = str_replace('{_classname_}', $args['registration']['class_name'], $message);

        //
        // Send the message
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
            'object' => 'ciniki.musicfestivals.registration',
            'object_id' => $args['registration']['registration_id'],
            'customer_id' => $args['parent_customer_id'],
            'customer_email' => $args['email'],
            'customer_name' => $args['toname'],
            'subject' => $subject,
            'tinymce' => 'yes',
//            'from_name' => $from_name,
//            'from_address' => $from_address,
            'html_content' => $message,
            'text_content' => html_entity_decode(strip_tags($message)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1758', 'msg'=>'Unable to send email to parent', 'err'=>$rc['err']));
        } else {
            $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
        }
    }

    return array('stat'=>'ok');
}
?>
