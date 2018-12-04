<?php
//
// Description
// -----------
// This method will return the list of Festivals for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Festival for.
//
// Returns
// -------
//
function ciniki_musicfestivals_festivalList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of festivals
    //
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.start_date, "
        . "ciniki_musicfestivals.end_date, "
        . "ciniki_musicfestivals.status, "
        . "ciniki_musicfestivals.flags, "
        . "ciniki_musicfestivals.earlybird_date "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 'earlybird_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['festivals']) ) {
        $festivals = $rc['festivals'];
        $festival_ids = array();
        foreach($festivals as $iid => $festival) {
            $festival_ids[] = $festival['id'];
        }
    } else {
        $festivals = array();
        $festival_ids = array();
    }

    return array('stat'=>'ok', 'festivals'=>$festivals, 'nplist'=>$festival_ids);
}
?>
