<?php
//
// Description
// -----------
// This method will return the list of Sections for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Section for.
//
// Returns
// -------
//
function ciniki_musicfestivals_sectionList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of sections
    //
    $strsql = "SELECT ciniki_musicfestival_sections.id, "
        . "ciniki_musicfestival_sections.festival_id, "
        . "ciniki_musicfestival_sections.name, "
        . "ciniki_musicfestival_sections.permalink, "
        . "ciniki_musicfestival_sections.sequence, "
        . "ciniki_musicfestival_sections.flags "
        . "FROM ciniki_musicfestival_sections "
        . "WHERE ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'permalink', 'sequence', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['sections']) ) {
        $sections = $rc['sections'];
        $section_ids = array();
        foreach($sections as $iid => $section) {
            $section_ids[] = $section['id'];
        }
    } else {
        $sections = array();
        $section_ids = array();
    }

    return array('stat'=>'ok', 'sections'=>$sections, 'nplist'=>$section_ids);
}
?>
