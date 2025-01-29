<?php
//
// Description
// -----------
// This method will return the list of Trophys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Trophy for.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophyRegistrationsPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophyRegistrations');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of trophies
    //
    $strsql = "SELECT trophies.id, "
        . "trophies.name, "
        . "trophies.typename, "
        . "trophies.category, "
        . "trophies.donated_by, "
        . "trophies.first_presented, "
        . "trophies.criteria, "
        . "trophies.amount, "
        . "trophies.description, "
        . "classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "registrations.id AS registration_id, "
        . "registrations.display_name, "
        . "IFNULL(timeslots.id, 0) AS timeslot_id, "
        . "IFNULL(timeslots.name, '') AS timeslot_name, "
        . "IFNULL(timeslots.groupname, '') AS timeslot_groupname, "
        . "IFNULL(divisions.id, 0) AS division_id, "
        . "IFNULL(divisions.name, '') AS division_name, "
        . "IFNULL(ssections.id, 0) AS ssection_id, "
        . "IFNULL(ssections.name, '') AS ssection_name, "
        . "DATE_FORMAT(IFNULL(divisions.division_date, ''), '%b %d, %Y') AS division_date_text, "
        . "TIME_FORMAT(IFNULL(timeslots.slot_time, ''), '%l:%i %p') AS slot_time_text, "
        . "TIME_FORMAT(IFNULL(registrations.timeslot_time, ''), '%l:%i %p') AS reg_time_text, "
        . "IFNULL(locations.name, '') AS location_name "
        . "FROM ciniki_musicfestival_trophies AS trophies "
        . "INNER JOIN ciniki_musicfestival_trophy_classes AS tc ON ("
            . "trophies.id = tc.trophy_id "
            . "AND tc.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "tc.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") " 
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "tc.class_id = registrations.class_id "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//        . "GROUP BY trophies.id "
        . "ORDER BY trophies.category, trophies.name, trophies.id, classes.sequence, classes.code, registrations.timeslot_sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'trophies', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'typename', 'category', 'donated_by', 'first_presented', 'criteria', 'amount', 
                'description', 
                )),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'code'=>'class_code', 'name'=>'class_name', 'category_name', 'section_name'),
            ),
        array('container'=>'registrations', 'fname'=>'registration_id', 
            'fields'=>array('id'=>'class_id', 'display_name',
                'class_code', 'class_name', 'category_name', 'section_name',
                'timeslot_name', 'timeslot_groupname', 'division_name', 'ssection_name',
                'division_date_text', 'slot_time_text', 'reg_time_text', 'location_name',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'trophyRegistrationsPDF');
    $rc = ciniki_musicfestivals_templates_trophyRegistrationsPDF($ciniki, $args['tnid'], array(
        'trophies' => $trophies,
        'festival_id' => $args['festival_id'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.744', 'msg'=>'Unable to generate PDF', 'err'=>$rc['err']));
    }
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($rc['filename'], 'I');
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok');
}
?>
