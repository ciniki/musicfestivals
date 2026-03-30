<?php
//
// Description
// -----------
// This function will load the assignment information and send and email to a single volunteer who may have multiple emails.
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
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Set current date
    //
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Load the assignment, volunteer and emails
    //
    if( isset($args['assignment_id']) && $args['assignment_id'] > 0 ) {
        $strsql = "SELECT assignments.id, "
            . "assignments.festival_id, "
            . "shifts.id AS shift_id, "
            . "assignments.status AS assignment_status, "
            . "shifts.shift_date AS shift_date_raw, "
            . "shifts.start_time AS start_time_raw, "
            . "shifts.end_time AS end_time_raw, "
            . "DATE_FORMAT(shifts.shift_date, '%a, %b %e, %Y') AS shift_date, "
            . "TIME_FORMAT(shifts.start_time, '%l:%i %p') as start_time, "
            . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time, "
            . "shifts.object, "
            . "shifts.object_id, "
            . "shifts.role, "
            . "volunteers.id AS volunteer_id, "
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
                'fields'=>array('id', 'festival_id', 'volunteer_id', 'customer_id', 'firstname', 'lastname', 'display_name', 
                    'shift_date_raw', 'start_time_raw', 'end_time_raw', 
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
        // Check to make sure assignment is in the future
        //
        $assignment_dt = new DateTime($assignment['shift_date'] . ' ' . $assignment['start_time'], new DateTimezone($intl_timezone));
        if( $assignment_dt < $dt ) {
            error_log('assignment past, no emails sent');
            return array('stat'=>'ok');
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
        $assignment['start_end_time'] = "{$assignment['start_time']} to {$assignment['end_time']}";

        if( isset($args['old_shift']) ) {
            if( $args['old_shift']['shift_date'] != $assignment['shift_date'] ) {
                $assignment['shift_date'] = "<b>{$assignment['shift_date']}</b> (<i>was {$args['old_shift']['shift_date']}</i>)";
            }
            if( ($args['old_shift']['start_time'] != $assignment['start_time']) 
                || ($args['old_shift']['end_time'] != $assignment['end_time'])
                ) {
                $assignment['start_end_time'] = "<b>{$assignment['start_end_time']}</b> (<i>was {$args['old_shift']['start_time']} to {$args['old_shift']['end_time']}</i>)";
            }
            if( $args['old_shift']['start_time'] != $assignment['start_time'] ) {
                $assignment['start_time'] = "<b>{$assignment['start_time']}</b> (<i>was {$args['old_shift']['start_time']}</i>)";
            }
            if( $args['old_shift']['end_time'] != $assignment['end_time'] ) {
                $assignment['end_time'] = "<b>{$assignment['end_time']}</b> (<i>was {$args['old_shift']['end_time']}</i>)";
            }
            if( $args['old_shift']['role'] != $assignment['role'] ) {
                $assignment['role'] = "<b>{$assignment['role']}</b> (<i>was {$args['old_shift']['role']}</i>)";
            }
            if( ($args['old_shift']['object'] != $assignment['object'])
                || ($args['old_shift']['object_id'] != $assignment['object_id'])
                ) {
                $old_location = 'Location To Be Determined';
                if( isset($locations["{$args['old_shift']['object']}:{$args['old_shift']['object_id']}"]['name']) ) {
                    $old_location = $locations["{$args['old_shift']['object']}:{$args['old_shift']['object_id']}"]['name'];
                }
                $assignment['location'] = "<b>{$assignment['location']}</b> (<i>was {$old_location}</i>)";
            }
        }

        $object = 'ciniki.musicfestivals.volunteer';
        $object_id = $assignment['volunteer_id'];
        $parent_object = 'ciniki.musicfestivals.volunteerassignment';
        $parent_object_id = $assignment['id'];
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
        $parent_object = '';
        $parent_object_id = '';
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
    // Get the disciplines
    //
    if( isset($assignment) ) {
        if( isset($festival['volunteers-discipline-format']) 
            && $festival['volunteers-discipline-format'] == 'section' 
            && $assignment['object'] != ''
            && preg_match("/^ciniki.musicfestivals.(building|location)/", $assignment['object'])
            && $assignment['object_id'] != ''
            && $assignment['object_id'] > 0 
            ) {
            $strsql = "SELECT sections.name "
                . "FROM ciniki_musicfestival_locations AS rooms "
                . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                    . "rooms.id = divisions.location_id "
                    . "AND divisions.division_date = '" . ciniki_core_dbQuote($ciniki, $assignment['shift_date_raw']) . "' "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                    . "divisions.id = timeslots.sdivision_id "
                    . "AND timeslots.slot_time > '" . ciniki_core_dbQuote($ciniki, $assignment['start_time_raw']) . "' "
                    . "AND timeslots.slot_time < '" . ciniki_core_dbQuote($ciniki, $assignment['end_time_raw']) . "' "
                    . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                    . "divisions.ssection_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") ";
            if( $assignment['object'] == 'ciniki.musicfestivals.location' ) {
                // Assignment is at a single room
                $strsql .= "WHERE rooms.id = '" . ciniki_core_dbQuote($ciniki, $assignment['object_id']) . "' ";
            } elseif( $assignment['object'] == 'ciniki.musicfestivals.building' ) {
                // Assignment is at a building with multiple rooms
                $strsql .= "WHERE rooms.building_id = '" . ciniki_core_dbQuote($ciniki, $assignment['object_id']) . "' ";
            }
            $strsql .= "AND rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY sections.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'disciplines', 'fname'=>'name', 'fields'=>array('name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1534', 'msg'=>'Unable to load disciplines', 'err'=>$rc['err']));
            }
            $assignment['discipline'] = isset($rc['disciplines']) ? implode(', ', array_keys($rc['disciplines'])) : '';
        } else {
            $assignment['discipline'] = '';
        }
    }

    //
    // Check for volunteer from name or email address to send from
    //
    $from_name = '';
    if( isset($festival['volunteers-smtp-from-name']) && $festival['volunteers-smtp-from-name'] != '' ) {
        $from_name = $festival['volunteers-smtp-from-name'];
    }
    $from_address = '';
    if( isset($festival['volunteers-smtp-from-address']) && $festival['volunteers-smtp-from-address'] != '' ) {
        $from_address = $festival['volunteers-smtp-from-address'];
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
    } elseif( isset($args['subject']) && $args['subject'] != '' 
        && isset($args['message']) && $args['message'] != '' 
        ) {
        $subject = $args['subject'];
        $message = $args['message'];
    }

    if( isset($subject) && isset($message) ) {
        //
        // Check for substitutions base on volunteer
        //
        if( isset($assignment) ) {
            $subject = str_replace('{_firstname_}', $assignment['firstname'], $subject);
            $message = str_replace('{_firstname_}', $assignment['firstname'], $message);
            $subject = str_replace('{_shiftdate_}', $assignment['shift_date'], $subject);
            $message = str_replace('{_shiftdate_}', $assignment['shift_date'], $message);
            $subject = str_replace('{_starttime_}', $assignment['start_time'], $subject);
            $message = str_replace('{_starttime_}', $assignment['start_time'], $message);
            $subject = str_replace('{_endtime_}', $assignment['end_time'], $subject);
            $message = str_replace('{_endtime_}', $assignment['end_time'], $message);
            $subject = str_replace('{_startendtime_}', $assignment['start_end_time'], $subject);
            $message = str_replace('{_startendtime_}', $assignment['start_end_time'], $message);
            $subject = str_replace('{_role_}', $assignment['role'], $subject);
            $message = str_replace('{_role_}', $assignment['role'], $message);
            $subject = str_replace('{_location_}', $assignment['location'], $subject);
            $message = str_replace('{_location_}', $assignment['location'], $message);
            $subject = str_replace('{_discipline_}', $assignment['discipline'], $subject);
            $message = str_replace('{_discipline_}', $assignment['discipline'], $message);
        }
        elseif( isset($volunteer) ) {
            $subject = str_replace('{_firstname_}', $volunteer['firstname'], $subject);
            $message = str_replace('{_firstname_}', $volunteer['firstname'], $message);
        }

        foreach($emails as $email) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'object' => $object,
                'object_id' => $object_id,
                'parent_object' => $object,
                'parent_object_id' => $object_id,
                'customer_id' => $customer_id,
                'customer_email' => $email['email'],
                'customer_name' => $display_name,
                'subject' => $subject,
                'tinymce' => 'yes',
                'from_name' => $from_name,
                'from_address' => $from_address,
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
