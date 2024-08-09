<?php
//
// Description
// -----------
// This method returns the list of categories or another field
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_musicfestivals_sectionFieldSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('syllabus'), 'name'=>'Field'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Festival'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionFieldSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the number of faqs in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT DISTINCT " . $args['field'] . " AS value "
        . "FROM ciniki_musicfestival_sections "
        . "WHERE ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND (" . $args['field']  . " LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "AND " . $args['field'] . " <> '' "
            . ") "
        . "ORDER BY " . $args['field'] . " "
        . "";
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'results', 'fname'=>'value', 'fields'=>array('value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
