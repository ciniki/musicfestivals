<?php
//
// Description
// -----------
// This method will return the list of Categorys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Category for.
//
// Returns
// -------
//
function ciniki_musicfestivals_categoryList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.categoryList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of categories
    //
    $strsql = "SELECT ciniki_musicfestival_categories.id, "
        . "ciniki_musicfestival_categories.festival_id, "
        . "ciniki_musicfestival_categories.section_id, "
        . "ciniki_musicfestival_sections.name AS section_name, "
        . "ciniki_musicfestival_categories.name, "
        . "ciniki_musicfestival_categories.permalink, "
        . "ciniki_musicfestival_categories.sequence "
        . "FROM ciniki_musicfestival_categories, ciniki_musicfestival_sections "
        . "WHERE ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_categories.section_id = ciniki_musicfestival_sections.id "
        . "AND ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'section_id', 'section_name', 'name', 'permalink', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
        $category_ids = array();
        foreach($categories as $iid => $category) {
            $category_ids[] = $category['id'];
        }
    } else {
        $categories = array();
        $category_ids = array();
    }

    return array('stat'=>'ok', 'categories'=>$categories, 'nplist'=>$category_ids);
}
?>
