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
function ciniki_musicfestivals_wng_festivalLoad(&$ciniki, $tnid, $request) {

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
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( $request['uri_split'][0] == 'cpi' && isset($_POST['festival-id']) && $_POST['festival-id'] > 0 ) {
        $strsql .= "AND id = '" . ciniki_core_dbQuote($ciniki, $_POST['festival-id']) . "' ";
    } else {
        $strsql .= "AND permalink = '" . ciniki_core_dbQuote($ciniki, $request['uri_split'][2]) . "' ";
    }
    $strsql .= "AND status = 30 "        // Current
        . "ORDER BY start_date DESC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.259', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        // No festivals published, no items to return
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.254', 'msg'=>'Unable to find festival'));
    }
    $festival = $rc['festival'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    return ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid, $festival);
}
?>
