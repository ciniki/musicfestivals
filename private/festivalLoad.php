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
function ciniki_musicfestivals_festivalLoad(&$ciniki, $tnid, $festival_id) {

    //
    // Get the current festival
    //
    $strsql = "SELECT id, "
        . "name, "
        . "flags, "
        . "earlybird_date, "
        . "live_date, "
        . "virtual_date, "
        . "titles_end_dt, "
        . "accompanist_end_dt, "
        . "upload_end_dt, "
        . "document_logo_id, "
        . "document_header_msg, "
        . "document_footer_msg "
        . "FROM ciniki_musicfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "ORDER BY start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.838', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        // No festivals published, no items to return
        return array('stat'=>'ok', 'items'=>array());
    }
    $festival = $rc['festival'];

    //
    // Load the settings for the festival
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND ciniki_musicfestival_settings.detail_key NOT LIKE 'content-%' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.733', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    foreach($rc['settings'] as $k => $v) {
        $festival[$k] = $v;
    }

    //
    // Determine which dates are still open for the festival
    //
    $now = new DateTime('now', new DateTimezone('UTC'));
    $festival['live_end_dt'] = new DateTime($festival['live_date'], new DateTimezone('UTC'));
    $titles_end_dt = new DateTime($festival['titles_end_dt'], new DateTimezone('UTC'));
    $accompanist_end_dt = new DateTime($festival['accompanist_end_dt'], new DateTimezone('UTC'));
    $upload_end_dt = new DateTime($festival['upload_end_dt'], new DateTimezone('UTC'));
    if( ($festival['flags']&0x20) == 0x20 ) {
        $festival['earlybird_end_dt'] = new DateTime($festival['earlybird_date'], new DateTimezone('UTC'));
        $festival['earlybird'] = ($festival['earlybird_end_dt'] > $now ? 'yes' : 'no');
    } else {
        $festival['earlybird'] = 'no';
    }
    $festival['live'] = (($festival['flags']&0x01) == 0x01 && $festival['live_end_dt'] > $now ? 'yes' : 'no');
    if( ($festival['flags']&0x02) == 0x02 ) {
        $festival['virtual_end_dt'] = new DateTime($festival['virtual_date'], new DateTimezone('UTC'));
        $festival['virtual'] = (($festival['flags']&0x02) == 0x02 && $festival['virtual_end_dt'] > $now ? 'yes' : 'no');
    } else {
        $festival['virtual'] = 'no';
    }
    if( ($festival['flags']&0x10) == 0x10 ) {
        $festival['earlybird_plus_live'] = $festival['earlybird'];
        $festival['plus_live'] = $festival['live'];
    }
    $festival['edit'] = ($titles_end_dt > $now ? 'yes' : 'no');
    $festival['edit-accompanist'] = ($accompanist_end_dt > $now ? 'yes' : 'no');
    $festival['upload'] = (($festival['flags']&0x03) == 0x03 && $upload_end_dt > $now ? 'yes' : 'no');

    return array('stat'=>'ok', 'festival'=>$festival);
}
?>
