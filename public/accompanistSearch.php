<?php
//
// Description
// -----------
// This method searches the adjudicator for the festival
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Competitor for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_accompanistSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accompanistSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of accompanists
    //
    $strsql = "SELECT customers.id, "
        . "customers.display_name, "
        . "COUNT(registrations.id) AS num_registrations "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "registrations.accompanist_customer_id = customers.id "
            . "AND ("
                . "customers.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR customers.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY customers.id "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'accompanists', 'fname'=>'id', 
            'fields'=>array('id', 'name'=>'display_name', 'num_registrations')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $accompanists = isset($rc['accompanists']) ? $rc['accompanists'] : array();
    $accompanist_ids = [];
    foreach($accompanists as $aid => $accompanist) {
        $accompanist_ids[] = $accompanist['id'];
    }

    return array('stat'=>'ok', 'accompanists'=>$accompanists, 'nplist'=>$accompanist_ids);
}
?>
