<?php
//
// Description
// -----------
// This method searchs for a Trophys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Trophy for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophySearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophySearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of trophies
    //
    $strsql = "SELECT ciniki_musicfestival_trophies.id, "
        . "ciniki_musicfestival_trophies.name, "
        . "ciniki_musicfestival_trophies.typename, "
        . "ciniki_musicfestival_trophies.category, "
        . "ciniki_musicfestival_trophies.donated_by, "
        . "ciniki_musicfestival_trophies.first_presented, "
        . "ciniki_musicfestival_trophies.criteria, "
        . "ciniki_musicfestival_trophies.amount "
        . "FROM ciniki_musicfestival_trophies "
        . "WHERE ciniki_musicfestival_trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'trophies', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'typename', 'category', 'donated_by', 'first_presented', 'criteria', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['trophies']) ) {
        $trophies = $rc['trophies'];
        $trophy_ids = array();
        foreach($trophies as $iid => $trophy) {
            $trophy_ids[] = $trophy['id'];
        }
    } else {
        $trophies = array();
        $trophy_ids = array();
    }

    return array('stat'=>'ok', 'trophies'=>$trophies, 'nplist'=>$trophy_ids);
}
?>
