<?php
//
// Description
// -----------
// Get a section from SSAM chart
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_ssamSectionGet(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Name'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.ssamSectionGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current ssam content
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'ssamLoad');
    $rc = ciniki_musicfestivals_ssamLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ssam = $rc['ssam'];

    if( isset($ssam['sections']) ) {
        foreach($ssam['sections'] as $section) {
            if( $section['name'] == $args['section_name'] ) {
                return array('stat'=>'ok', 'section'=>$section);
            }
        }
    }

    return array('stat'=>'ok', 'section'=>array('name'=>'', 'content'=>''));
}
?>
