<?php
//
// Description
// -----------
// This method will return the list of Adjudicator References for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Adjudicator Reference for.
//
// Returns
// -------
//
function ciniki_musicfestivals_adjudicatorRefList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'object'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object'),
        'object_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.adjudicatorRefList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of adjudicatorrefs
    //
    $strsql = "SELECT adjudicators.id, "
        . "customers.display_name AS name, "
        . "arefs.id AS ref_id "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
            . "adjudicators.id = arefs.adjudicator_id "
            . "AND arefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND arefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('ref_id', 'adjudicator_id'=>'id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    return array('stat'=>'ok', 'adjudicators'=>$adjudicators);
}
?>
