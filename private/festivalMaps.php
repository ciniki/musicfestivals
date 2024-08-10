<?php
//
// Description
// -----------
// Load the maps for a particular festival
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_festivalMaps($ciniki, $tnid, $festival) {
    //
    // Load the default maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Check if festival needs to be loaded 
    //
    if( is_numeric($festival) ) {
        //
        // Get the additional settings
        //
        $strsql = "SELECT detail_key, detail_value "
            . "FROM ciniki_musicfestival_settings "
            . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.189', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
        }
        $festival = $rc['settings'];
    }
   
    //
    // Update the status maps
    //
    for($i = 31; $i <= 38; $i++) {
        if( isset($festival["registration-status-{$i}-label"])
            && $festival["registration-status-{$i}-label"] != ''
            ) {
            $maps['registration']['status'][$i] = $festival["registration-status-{$i}-label"];
        }
    }
    for($i = 50; $i <= 55; $i++) {
        if( isset($festival["registration-status-{$i}-label"])
            && $festival["registration-status-{$i}-label"] != ''
            ) {
            $maps['registration']['status'][$i] = $festival["registration-status-{$i}-label"];
        }
    }

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
