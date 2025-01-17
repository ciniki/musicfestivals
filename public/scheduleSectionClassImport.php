<?php
//
// Description
// -----------
// Import the unscheduled classes into the division
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_scheduleSectionClassImport(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'scheduledivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'status_5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Draft'),
        'status_70-'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Disqualified'),
        'status_75'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Withdrawn'),
        'status_80'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cancelled'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleSectionClassImport');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the list of classes and registrations for the syllabus section
    //
    $strsql = "SELECT classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "registrations.id AS reg_id "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.timeslot_id = 0 "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "(registrations.status > 5 AND registrations.status < 70) ";
    if( isset($args['status_5']) && $args['status_5'] == 'yes' ) {
        $strsql .= "OR registrations.status = 5 ";
    }
    if( isset($args['status_70']) && $args['status_70'] == 'yes' ) {
        $strsql .= "OR registrations.status = 70 ";
    }
    if( isset($args['status_75']) && $args['status_75'] == 'yes' ) {
        $strsql .= "OR registrations.status = 75 ";
    }
    if( isset($args['status_80']) && $args['status_80'] == 'yes' ) {
        $strsql .= "OR registrations.status = 80 ";
    }
    $strsql .= ") "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.code, classes.name, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'code'=>'class_code', 'name'=>'class_name',
                'category_name', 'section_name',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.380', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    foreach($classes as $class) {
        //
        // Add the timeslot
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduletimeslot', [
            'festival_id' => $args['festival_id'],
            'ssection_id' => 0,
            'sdivision_id' => $args['scheduledivision_id'],
            'slot_time' => '00:00:00',
            'flags' => 0,
            'name' => $class['code'] . ' - ' . $class['category_name'] . ' - ' . $class['name'],
            'groupname' => '',
            'description' => '',
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
        $scheduletimeslot_id = $rc['id'];

        //
        // Update the registrations
        //
        $sequence = 1;
        foreach($class['registrations'] as $reg) {
            //
            // Update the timeslot_id on the registration
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg['id'], [
                'timeslot_id' => $scheduletimeslot_id,
                'timeslot_sequence' => $sequence,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return $rc;
            }

            $sequence++;
        }
    }


    return array('stat'=>'ok');
}
?>
