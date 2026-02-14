<?php
//
// Description
// -----------
// This function will load the assignment information and send and email to the volunteer.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_volunteerEmail(&$ciniki, $tnid, $args) {

    //
    // Load the assignment, volunteer and emails
    //
    if( isset($args['assignment_id']) && $args['assignment_id'] > 0 ) {
        $strsql = "SELECT assignments.id, "
            . "assignments.festival_id, "
            . "shifts.id AS shift_id, "
            . "assignments.status AS assignment_status, "
            . "DATE_FORMAT(shifts.shift_date, '%a, %b %e, %Y') AS shift_date, "
            . "TIME_FORMAT(shifts.start_time, '%l:%i %p') as start_time, "
            . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time, "
            . "shifts.object, "
            . "shifts.object_id, "
            . "shifts.role, "
            . "volunteers.customer_id, "
            . "customers.first AS firstname, "
            . "customers.last AS lastname, "
            . "customers.display_name, "
            . "emails.id AS email_id, "
            . "emails.email "
            . "FROM ciniki_musicfestival_volunteer_assignments AS assignments "
            . "INNER JOIN ciniki_musicfestival_volunteer_shifts AS shifts ON ("
                . "assignments.shift_id = shifts.id "
                . "AND shifts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                . "assignments.volunteer_id = volunteers.id "
                . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "volunteers.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customer_emails AS emails ON ("
                . "customers.id = emails.customer_id "
                . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE assignments.id = '" . ciniki_core_dbQuote($ciniki, $args['assignment_id']) . "' "
            . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'assignments', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'customer_id', 'firstname', 'lastname', 'display_name', 
                    'shift_date', 'start_time', 'end_time', 'object', 'object_id', 'role',
                    ),
                ),
            array('container'=>'emails', 'fname'=>'email_id', 
                'fields'=>array('id'=>'email_id', 'email'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1418', 'msg'=>'Unable to load assignment', 'err'=>$rc['err']));
        }
        if( isset($rc['assignments'][0]) ) {
            $assignment = $rc['assignments'][0];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1264', 'msg'=>'Invalid assignment'));
        }

        //
        // Load the locations
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'locationsLoad');
        $rc = ciniki_musicfestivals_locationsLoad($ciniki, $tnid, $assignment['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $locations = isset($rc['locations']) ? $rc['locations'] : array();

        $assignment['location'] = 'Location To Be Determined';
        if( isset($locations["{$assignment['object']}:{$assignment['object_id']}"]['name']) ) {
            $assignment['location'] = $locations["{$assignment['object']}:{$assignment['object_id']}"]['name'];
        }

        $object = 'ciniki.musicfestivals.volunteerassignment';
        $object_id = $assignment['id'];
        $customer_id = $assignment['customer_id'];
        $display_name = $assignment['display_name'];
        $emails = isset($assignment['emails']) ? $assignment['emails'] : [];

    } elseif( isset($args['volunteer_id']) && $args['volunteer_id'] > 0 ) {
        //
        // Load the volunteer and customer
        //
        $strsql = "SELECT volunteers.id, "
            . "volunteers.festival_id, "
            . "volunteers.status, "
            . "volunteers.customer_id, "
            . "customers.first AS firstname, "
            . "customers.last AS lastname, "
            . "customers.display_name, "
            . "emails.id AS email_id, "
            . "emails.email "
            . "FROM ciniki_musicfestival_volunteers AS volunteers "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "volunteers.customer_id = customers.id "
                . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_customer_emails AS emails ON ("
                . "customers.id = emails.customer_id "
                . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE volunteers.id = '" . ciniki_core_dbQuote($ciniki, $args['volunteer_id']) . "' "
            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'volunteers', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'customer_id', 'firstname', 'lastname', 'display_name'),
                ),
            array('container'=>'emails', 'fname'=>'email_id', 
                'fields'=>array('id'=>'email_id', 'email'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1301', 'msg'=>'Unable to load volunteer', 'err'=>$rc['err']));
        }
        if( isset($rc['volunteers'][0]) ) {
            $volunteer = $rc['volunteers'][0];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1383', 'msg'=>'Invalid volunteer'));
        }
        
        $object = 'ciniki.musicfestivals.volunteer';
        $object_id = $args['volunteer_id'];
        $customer_id = $volunteer['customer_id'];
        $display_name = $volunteer['display_name'];
        $emails = isset($volunteer['emails']) ? $volunteer['emails'] : [];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1379', 'msg'=>'No assignment or volunteer specified'));
    }

    //
    // Load the festival if not passed
    //
    if( !isset($args['festival']) && isset($assignment['festival_id']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $assignment['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1304', 'msg'=>'', 'err'=>$rc['err']));
        }
        $festival = $rc['festival'];
    } else {
        $festival = $args['festival'];
    }

    //
    // Check for template
    //
    if( isset($festival["{$args['template']}-subject"]) 
        && $festival["{$args['template']}-subject"] != ''
        && isset($festival["{$args['template']}-message"]) 
        && $festival["{$args['template']}-message"] != ''
        ) {
        
        $subject = $festival["{$args['template']}-subject"];
        $message = $festival["{$args['template']}-message"];

        //
        // Run substitutions
        //
        $object = '';
        $object_id = '';
        if( isset($assignment) ) {
            $subject = str_replace('{_firstname_}', $assignment['firstname'], $subject);
            $message = str_replace('{_firstname_}', $assignment['firstname'], $message);
            $subject = str_replace('{_shiftdate_}', $assignment['shift_date'], $subject);
            $message = str_replace('{_shiftdate_}', $assignment['shift_date'], $message);
            $subject = str_replace('{_starttime_}', $assignment['start_time'], $subject);
            $message = str_replace('{_starttime_}', $assignment['start_time'], $message);
            $subject = str_replace('{_endtime_}', $assignment['end_time'], $subject);
            $message = str_replace('{_endtime_}', $assignment['end_time'], $message);
            $subject = str_replace('{_role_}', $assignment['role'], $subject);
            $message = str_replace('{_role_}', $assignment['role'], $message);
            $subject = str_replace('{_location_}', $assignment['location'], $subject);
            $message = str_replace('{_location_}', $assignment['location'], $message);
        } elseif( isset($volunteer) ) {
            $subject = str_replace('{_firstname_}', $volunteer['firstname'], $subject);
            $message = str_replace('{_firstname_}', $volunteer['firstname'], $message);
        }

        foreach($emails as $email) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'object' => $object,
                'object_id' => $object_id,
                'customer_id' => $customer_id,
                'customer_email' => $email['email'],
                'customer_name' => $display_name,
                'subject' => $subject,
                'tinymce' => 'yes',
                'html_content' => $message,
                'text_content' => html_entity_decode(strip_tags($message)),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1382', 'msg'=>'Unable to volunteer', 'err'=>$rc['err']));
            } else {
                $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
            }
        }
    }

    //
    // Check if notification needs to be removed
    //
    if( isset($args['notification_id']) && $args['notification_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.volunteernotification',
            $args['notification_id'], null, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
