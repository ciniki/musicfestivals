<?php
//
// Description
// ===========
// This function will lookup an invoice item and make sure it is still available for purchase.
// This function is called for any items previous to checkout.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_cartItemCheck($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.340', 'msg'=>'No registration specified.'));
    }

    //
    // Lookup the requested object (registration) and see if it is still available or if registrations have closed
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
        && $args['object'] == 'ciniki.musicfestivals.registration' 
        && isset($args['object_id']) && $args['object_id'] > 0 
        ) {
        //
        // Get the registration and festival details
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.fee, "
            . "registrations.class_id, "
            . "registrations.festival_id, "
            . "registrations.participation, "
            . "IFNULL(members.reg_start_dt, '0000-00-00 00:00:00') AS reg_start_dt, "
            . "IFNULL(members.reg_end_dt, '0000-00-00 00:00:00') AS reg_end_dt "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_musicfestival_members AS members ON ("
                . "registrations.member_id = members.member_id "
                . "AND registrations.festival_id = members.festival_id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
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
            . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.659', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( !isset($rc['registration']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.660', 'msg'=>'Unable to find requested registration'));
        }
        $registration = $rc['registration'];

        $now = new DateTime('now', new DateTimezone('UTC'));
        $edt = new DateTime($registration['reg_end_dt'], new DateTimezone('UTC'));
        if( $edt < $now ) {
            $diff = $now->diff($edt);
            if( $diff->days < 1 ) {
                $latefee = 25;
            } elseif( $diff->days < 2 ) {
                $latefee = 50;
//            } elseif( $diff->days < 3 ) {
//                $latefee = 75;
            } else {
                return array('stat'=>'blocked', 'err'=>array('code'=>'ciniki.musicfestivals.664', 'msg'=>'Registrations are closed'));
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceMemberLateFeeUpdate');
            $rc = ciniki_musicfestivals_invoiceMemberLateFeeUpdate($ciniki, $tnid, $args['invoice_id'], $latefee);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['added']) || isset($rc['updated']) ) {
                return array('stat'=>'updated', 'msg'=>'Late fee is now $' . $latefee);
            }
        }

        return array('stat'=>'ok');
    }
    //
    // The following have been moved to cartCheck
    //
/*    elseif( $args['object'] == 'ciniki.musicfestivals.memberlatefee' 
        || $args['object'] == 'ciniki.musicfestivals.adminfee' 
        || $args['object'] == 'ciniki.musicfestivals.latefee' 
        ) {
        //
        // Run the check on the entire invoice
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'invoiceExtraFeesCheck');
        return ciniki_musicfestivals_sapos_invoiceExtraFeesCheck($ciniki, $tnid, [
            'invoice_id' => $args['invoice_id'],
            ]);
    }
    elseif( $args['object'] == 'ciniki.musicfestivals.registration' && isset($args['object_id']) && $args['object_id'] > 0 ) {

        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'registrationExtraFeesCheck');
        return ciniki_musicfestivals_sapos_registrationExtraFeesCheck($ciniki, $tnid, [
            'registration_id' => $args['object_id'],
            'invoice_id' => $args['invoice_id'],
            'invoice_item_id' => $args['id'],
            ]);
    } */

    return array('stat'=>'ok');
}
?>
