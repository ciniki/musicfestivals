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
    // Lookup the requested event if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.musicfestivals.registration' && isset($args['object_id']) && $args['object_id'] > 0 ) {

        //
        // Get the registration and festival details
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.fee, "
            . "registrations.festival_id, "
            . "registrations.virtual "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
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
        // Get the music festival for the registration
        //
        $strsql = "SELECT id, "
            . "name, "
            . "flags, "
            . "earlybird_date, "
            . "live_date, "
            . "virtual_date "
            . "FROM ciniki_musicfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "        // Published
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.259', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
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

        //
        // Registrations are closed
        //
        if( ($festival['flags']&0x01) == 0      // Registrations are closed
            || ($registration['virtual'] == 0 && $festival['live'] == 'no') // Live registrations are closed
            || ($registration['virtual'] == 1 && $festival['virtual'] == 'no') // Live registrations are closed
            ) {
            return array('stat'=>'unavailable', 'err'=>array('code'=>'ciniki.musicfestivals.381', 'msg'=>'Registrations are closed'));
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.380', 'msg'=>'No registration specified.'));
}
?>
