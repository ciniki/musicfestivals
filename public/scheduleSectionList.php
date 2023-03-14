<?php
//
// Description
// -----------
// This method will return the list of Schedule Sections for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Schedule Section for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleSectionList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleSectionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of schedulesections
    //
    $strsql = "SELECT ciniki_musicfestival_schedule_sections.id, "
        . "ciniki_musicfestival_schedule_sections.festival_id, "
        . "ciniki_musicfestival_schedule_sections.name, "
        . "ciniki_musicfestival_schedule_sections.flags "
        . "FROM ciniki_musicfestival_schedule_sections "
        . "WHERE ciniki_musicfestival_schedule_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'schedulesections', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['schedulesections']) ) {
        $schedulesections = $rc['schedulesections'];
        $schedulesection_ids = array();
        foreach($schedulesections as $iid => $schedulesection) {
            $schedulesection_ids[] = $schedulesection['id'];
        }
    } else {
        $schedulesections = array();
        $schedulesection_ids = array();
    }

    return array('stat'=>'ok', 'schedulesections'=>$schedulesections, 'nplist'=>$schedulesection_ids);
}
?>
