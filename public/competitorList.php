<?php
//
// Description
// -----------
// This method will return the list of Competitors for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Competitor for.
//
// Returns
// -------
//
function ciniki_musicfestivals_competitorList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitorList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of competitors
    //
    $strsql = "SELECT ciniki_musicfestival_competitors.id, "
        . "ciniki_musicfestival_competitors.festival_id, "
        . "ciniki_musicfestival_competitors.name, "
        . "ciniki_musicfestival_competitors.flags, "
        . "ciniki_musicfestival_competitors.parent, "
        . "ciniki_musicfestival_competitors.address, "
        . "ciniki_musicfestival_competitors.city, "
        . "ciniki_musicfestival_competitors.province, "
        . "ciniki_musicfestival_competitors.postal, "
        . "ciniki_musicfestival_competitors.phone_home, "
        . "ciniki_musicfestival_competitors.phone_cell, "
        . "ciniki_musicfestival_competitors.email, "
        . "ciniki_musicfestival_competitors.age, "
        . "ciniki_musicfestival_competitors.study_level, "
        . "ciniki_musicfestival_competitors.instrument, "
        . "ciniki_musicfestival_competitors.notes "
        . "FROM ciniki_musicfestival_competitors "
        . "WHERE ciniki_musicfestival_competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'flags', 
                'parent', 'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 
                'email', 'age', 'study_level', 'instrument', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['competitors']) ) {
        $competitors = $rc['competitors'];
        $competitor_ids = array();
        foreach($competitors as $iid => $competitor) {
            $competitor_ids[] = $competitor['id'];
        }
    } else {
        $competitors = array();
        $competitor_ids = array();
    }

    return array('stat'=>'ok', 'competitors'=>$competitors, 'nplist'=>$competitor_ids);
}
?>
