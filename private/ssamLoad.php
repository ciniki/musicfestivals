<?php
//
// Description
// -----------
// Load the SSAM content for the festival
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_ssamLoad(&$ciniki, $tnid, $festival_id) {

    if( $tnid <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.845', 'msg'=>'Invalid tenant'));
    }
    if( $festival_id <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.846', 'msg'=>'Invalid festival'));
    }

    //
    // Get the current setting
    //
    $strsql = "SELECT detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND detail_key = 'content-ssam-chart' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'content');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.840', 'msg'=>'Unable to load content', 'err'=>$rc['err']));
    }
    $ssam = [];
    if( isset($rc['content']['detail_value']) ) {
        $ssam = json_decode($rc['content']['detail_value'], true);
    }

    return array('stat'=>'ok', 'ssam'=>$ssam);
}
?>
