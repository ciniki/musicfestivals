<?php
//
// Description
// -----------
// This function will check for admin and late fees for a registration and add them to the invoice if required
//
//
function ciniki_musicfestivals_sapos_registrationExtraFeesCheck($ciniki, $tnid, $args) {

    if( !isset($args['registration_id']) || $args['registration_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.875', 'msg'=>'No registration specified.'));
    }

    //
    // Get the registration and festival details
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.fee, "
        . "registrations.flags, "
        . "registrations.class_id, "
        . "registrations.festival_id, "
        . "registrations.participation, "
        . "IFNULL(sections.live_end_dt, '0000-00-00 00:00:00') AS live_end_dt, "
        . "IFNULL(sections.virtual_end_dt, '0000-00-00 00:00:00') AS virtual_end_dt, "
        . "IFNULL(sections.flags, 0) AS section_flags, "
        . "IFNULL(sections.latefees_start_amount, 0) AS latefees_start_amount, "
        . "IFNULL(sections.latefees_daily_increase, 0) AS latefees_daily_increase, "
        . "IFNULL(sections.latefees_days, 0) AS latefees_days, "
        . "IFNULL(sections.adminfees_amount, 0) AS adminfees_amount "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.378', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.379', 'msg'=>'Unable to find requested registration'));
    }
    $registration = $rc['registration'];

    //
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $registration['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

/*        //
        // Get the music festival for the registration
        //
        $strsql = "SELECT id, "
            . "name, "
            . "flags, "
            . "earlybird_date, "
            . "live_date, "
            . "virtual_date "
            . "FROM ciniki_musicfestivals "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.427', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
        }
        if( !isset($rc['festival']) ) {
            // No festivals published, no items to return
            return array('stat'=>'ok');
        }
        $festival = $rc['festival'];
        $now = new DateTime('now', new DateTimezone('UTC'));
        $earlybird_dt = new DateTime($festival['earlybird_date'], new DateTimezone('UTC'));
        $live_dt = new DateTime($festival['live_date'], new DateTimezone('UTC'));
        $virtual_dt = new DateTime($festival['virtual_date'], new DateTimezone('UTC'));
        $festival['earlybird'] = (($festival['flags']&0x01) == 0x01 && $earlybird_dt > $now ? 'yes' : 'no');
        $festival['live'] = (($festival['flags']&0x01) == 0x01 && $live_dt > $now ? 'yes' : 'no');
        $festival['virtual'] = (($festival['flags']&0x03) == 0x03 && $virtual_dt > $now ? 'yes' : 'no');
*/
    //
    // Check for section registration end dates and if still available
    //
    $section_live = 'yes';
    $live_days_past = 0;
    $section_virtual = 'yes';
    $virtual_days_past = 0; 
    $now = new DateTime('now', new DateTimezone('UTC'));

    //
    // Check the end dates for the registration section 
    //
    if( ($festival['flags']&0x08) == 0x08 
        && $registration['live_end_dt'] != '0000-00-00 00:00:00' 
        ) {
        $section_live_dt = new DateTime($registration['live_end_dt'], new DateTimezone('UTC'));
    } else {
        $section_live_dt = $festival['live_end_dt'];
    }
    // Get the days past live deadline
    // 1 second past deadline is 0 day
    $interval = $section_live_dt->diff($now);
    $registration['live_days_past'] = $interval->format('%d');
    if( $section_live_dt < $now ) {
        $festival['live'] = 'no';
    } else {
        $festival['live'] = 'yes';
    }

    //
    // Check if virtual festival
    //
    if( ($festival['flags']&0x02) == 0x02 ) {
        if( ($festival['flags']&0x08) == 0x08 
            && $registration['virtual_end_dt'] != '0000-00-00 00:00:00' 
            ) {
            $section_virtual_dt = new DateTime($registration['virtual_end_dt'], new DateTimezone('UTC'));
        } else {
            $section_virtual_dt = $festival['virtual_end_dt'];
        }
        // Get the number of days past virtual deadline
        // 1 second past deadline is 0 day
        $interval = $section_virtual_dt->diff($now);
        $registration['virtual_days_past'] = $interval->format('%d');
        if( $section_virtual_dt < $now ) {
            $festival['virtual'] = 'no';
        } else {
            $festival['virtual'] = 'yes';
        }
    }

    //
    // Check for any admin fees
    //
    if( ($registration['section_flags']&0x40) == 0x40 
        && $registration['adminfees_amount'] > 0 
        ) {
        $adminfee = $registration['adminfees_amount'];
    }

    //
    // Check for any late fees
    // Registrations still need to be open in festival, but past end date
    //
    if( ($registration['participation'] == 0 || $registration['participation'] == 2)    // Regular Live or Plus Live
        && $festival['live'] == 'no'        // registrations are closed for live
        && ($registration['section_flags']&0x10) == 0x10
        && isset($registration['live_days_past'])
        && isset($registration['latefees_days'])
        && $registration['live_days_past'] <= $registration['latefees_days']
        ) {
        $latefee = $registration['latefees_start_amount'] 
            + ($registration['latefees_daily_increase'] * $registration['live_days_past']);
    } elseif( ($registration['participation'] == 1 || $registration['participation'] == 3) // Virtual or Virtual Plus
        && $festival['virtual'] == 'no'     // registrations are closed for virtual
        && ($registration['section_flags']&0x10) == 0x10
        && isset($registration['virtual_days_past'])
        && isset($registration['latefees_days'])
        && $registration['virtual_days_past'] < $registration['latefees_days']
        ) {
        $latefee = $registration['latefees_start_amount'] 
            + ($registration['latefees_daily_increase'] * $registration['virtual_days_past']);
    }

    //
    // Registrations are closed and no late fees
    //
    if( !isset($latefee) 
        && (!isset($args['closed']) || $args['closed'] != 'ignore')
        && (
            ($festival['flags']&0x01) == 0      // Registrations are closed
            || ($registration['participation'] == 0 && $festival['live'] == 'no') // Live registrations are closed
            || ($registration['participation'] == 1 && $festival['virtual'] == 'no') // Virtual registrations are closed
        )) {
        return array('stat'=>'blocked', 'err'=>array('code'=>'ciniki.musicfestivals.381', 'msg'=>'Registrations are closed'));
    }

    //
    // Add admin fee
    //
    $fees_msg = '';
    if( isset($adminfee) && $adminfee > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceAdminFeeUpdate');
        $rc = ciniki_musicfestivals_invoiceAdminFeeUpdate($ciniki, $tnid, $args['invoice_id'], $adminfee);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['added']) || isset($rc['updated']) ) {
            $fees_msg .= ($fees_msg != '' ? "\n" : '') . 'Admin fee of $' . number_format($adminfee, 2) . ' has been added';
        }
    }

    if( isset($latefee) && $latefee > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceLateFeeUpdate');
        $rc = ciniki_musicfestivals_invoiceLateFeeUpdate($ciniki, $tnid, $args['invoice_id'], $latefee);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['added']) || isset($rc['updated']) ) {
            $fees_msg .= ($fees_msg != '' ? "\n" : '') . 'Late fee is now $' . number_format($latefee, 2);
        }
    }

    if( $fees_msg != '' ) {
        return array('stat'=>'updated', 'msg'=>$fees_msg);
    }

    if( isset($latefee) ) {
        return array('stat'=>'ok', 'latefee'=>$latefee);
    }

    return array('stat'=>'ok');
}
?>
