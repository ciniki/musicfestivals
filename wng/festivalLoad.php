<?php
//
// Description
// -----------
// Load the current festival
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_festivalLoad(&$ciniki, $tnid, $festival_id) {

    //
    // Get the current festival
    //
    $strsql = "SELECT id, "
        . "name, "
        . "flags, "
        . "earlybird_date, "
        . "live_date, "
        . "virtual_date, "
        . "edit_end_dt, "
        . "accompanist_end_dt, "
        . "upload_end_dt "
        . "FROM ciniki_musicfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "ORDER BY start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.766', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        // No festivals published, no items to return
        return array('stat'=>'ok', 'items'=>array());
    }
    $festival = $rc['festival'];

    //
    // Determine which dates are still open for the festival
    //
    $now = new DateTime('now', new DateTimezone('UTC'));
    $live_dt = new DateTime($festival['live_date'], new DateTimezone('UTC'));
    $edit_end_dt = new DateTime($festival['edit_end_dt'], new DateTimezone('UTC'));
    $accompanist_end_dt = new DateTime($festival['accompanist_end_dt'], new DateTimezone('UTC'));
    $upload_end_dt = new DateTime($festival['upload_end_dt'], new DateTimezone('UTC'));
    if( ($festival['flags']&0x20) == 0x20 ) {
        $earlybird_dt = new DateTime($festival['earlybird_date'], new DateTimezone('UTC'));
        $festival['earlybird'] = ($earlybird_dt > $now ? 'yes' : 'no');
    } else {
        $festival['earlybird'] = 'no';
    }
    $festival['live'] = (($festival['flags']&0x01) == 0x01 && $live_dt > $now ? 'yes' : 'no');
    if( ($festival['flags']&0x02) == 0x02 ) {
        $virtual_dt = new DateTime($festival['virtual_date'], new DateTimezone('UTC'));
        $festival['virtual'] = (($festival['flags']&0x02) == 0x02 && $virtual_dt > $now ? 'yes' : 'no');
    } else {
        $festival['virtual'] = 'no';
    }
    if( ($festival['flags']&0x10) == 0x10 ) {
        $festival['earlybird_plus_live'] = $festival['earlybird'];
        $festival['plus_live'] = $festival['live'];
    } else {
        $festival['earlybird_plus_live'] = 'no';
        $festival['plus_live'] = 'no';
    }
    $festival['edit'] = ($edit_end_dt > $now ? 'yes' : 'no');
    $festival['edit-accompanist'] = ($accompanist_end_dt > $now ? 'yes' : 'no');
    $festival['upload'] = (($festival['flags']&0x03) == 0x03 && $upload_end_dt > $now ? 'yes' : 'no');

    return array('stat'=>'ok', 'festival'=>$festival);
}
?>
