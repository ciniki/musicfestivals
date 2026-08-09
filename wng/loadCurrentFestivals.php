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
function ciniki_musicfestivals_wng_loadCurrentFestivals(&$ciniki, $tnid) {

    //
    // Get the current festival
    //
    $strsql = "SELECT id, "
        . "name, "
        . "permalink, "
        . "flags, "
        . "earlybird_date, "
        . "live_date, "
        . "virtual_date, "
        . "titles_end_dt, "
        . "accompanist_end_dt, "
        . "upload_end_dt "
        . "FROM ciniki_musicfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 30 "        // Current
        . "ORDER BY start_date DESC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.259', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) ) {
        // No festivals published, no items to return
        return array('stat'=>'ok', 'items'=>array());
    }
    $festivals = $rc['rows'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    foreach($festivals as $fid => $festival) {
        $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid, $festival);
        if( isset($rc['festival']) ) {
            $festivals[$fid] = $rc['festival'];
        }
    }

    return array('stat'=>'ok', 'festivals'=>$festivals);
}
?>
