<?php
//
// Description
// -----------
// Save the SSAM content for the festival
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_ssamSave(&$ciniki, $tnid, $festival_id, $new_ssam) {

    $new_value = json_encode($new_ssam);

    //
    // Get the current setting
    //
    $strsql = "SELECT id, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND detail_key = 'content-ssam-chart' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'content');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.865', 'msg'=>'Unable to load content', 'err'=>$rc['err']));
    }
    if( isset($rc['content']['detail_value']) ) {
        if( $rc['content']['detail_value'] != $new_value ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.setting', $rc['content']['id'], [
                'detail_value' => $new_value,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.842', 'msg'=>'Unable to update the setting', 'err'=>$rc['err']));
            }
        }
    } else {
        //
        // Add the setting
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.setting', [
            'festival_id' => $festival_id,
            'detail_key' => 'content-ssam-chart',
            'detail_value' => $new_value,
            ],0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.843', 'msg'=>'Unable to add the setting', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
