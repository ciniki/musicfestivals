<?php
//
// Description
// -----------
// This method will return the list of Syllabuss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Syllabus for.
//
// Returns
// -------
//
function ciniki_musicfestivals_syllabusList($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.syllabusList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of syllabuses
    //
    $strsql = "SELECT ciniki_musicfestival_syllabuses.id, "
        . "ciniki_musicfestival_syllabuses.festival_id, "
        . "ciniki_musicfestival_syllabuses.name, "
        . "ciniki_musicfestival_syllabuses.permalink, "
        . "ciniki_musicfestival_syllabuses.sequence, "
        . "ciniki_musicfestival_syllabuses.flags, "
        . "ciniki_musicfestival_syllabuses.live_end_dt, "
        . "ciniki_musicfestival_syllabuses.virtual_end_dt, "
        . "ciniki_musicfestival_syllabuses.titles_end_dt, "
        . "ciniki_musicfestival_syllabuses.upload_end_dt "
        . "FROM ciniki_musicfestival_syllabuses "
        . "WHERE ciniki_musicfestival_syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'syllabuses', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'permalink', 'sequence', 'flags', 'live_end_dt', 'virtual_end_dt', 'titles_end_dt', 'upload_end_dt')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $syllabuses = isset($rc['syllabuses']) ? $rc['syllabuses'] : array();
    $syllabus_ids = array();
    foreach($syllabuses as $iid => $syllabus) {
        $syllabus_ids[] = $syllabus['id'];
    }

    return array('stat'=>'ok', 'syllabuses'=>$syllabuses, 'nplist'=>$syllabus_ids);
}
?>
