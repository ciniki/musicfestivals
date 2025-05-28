<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_adjudicatorsRunsheetsZIP($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
//        'schedulesection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
//        'scheduledivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division'),
//        'adjudicator_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicator'),
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
//        'sortorder'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sort Order'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.adjudicatorsRunsheetsZIP');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    set_time_limit(300);
    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Set for in person only
    //
//    $args['ipv'] = 'inperson';

    //
    // Get the list of adjudicators for ipv
    //
    $strsql = "SELECT DISTINCT divisions.adjudicator_id,  "
        . "customers.display_name "
        . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
        . "INNER JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ( "
            . "divisions.adjudicator_id = adjudicators.id "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_customers AS customers ON ( "
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    if( isset($args['ipv']) && ($args['ipv'] == 'inperson' || $args['ipv'] == 'virtual') ) {
        $strsql .= "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "timeslots.id = registrations.timeslot_id ";
        if( $args['ipv'] == 'inperson' ) {
            $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
        } elseif( $args['ipv'] == 'virtual' ) {
            $strsql .= "AND (registrations.participation = 1 OR registrations.participation = 3) ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $strsql .= "WHERE divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND divisions.adjudicator_id > 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'adjudicator_id', 
            'fields'=>array('id'=>'adjudicator_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.996', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();
    
    //
    // Create the zip file
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/zipstream-php/src/ZipStream.php');

    $zip = new Pablotron\ZipStream\ZipStream('adjudicators_runsheets.zip');

    // 
    // Generate and add each adjudicators runsheet
    //
    foreach($adjudicators as $adjudicator) { 
        error_log($adjudicator['name']);
        //
        // Run the template
        //
        $args['adjudicator_id'] = $adjudicator['id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'runsheetsPDF');
        $rc = ciniki_musicfestivals_templates_runsheetsPDF($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Return the pdf
        //
        if( isset($rc['pdf']) ) {
            $zip->add_file($adjudicator['name'] . '.pdf', $rc['pdf']->Output($rc['filename'], 'S'));
        }
    }

    $zip->close();

    return array('stat'=>'exit');
}
?>
