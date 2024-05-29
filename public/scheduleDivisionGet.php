<?php
//
// Description
// ===========
// This method will return all the information about an schedule division.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule division is attached to.
// scheduledivision_id:          The ID of the schedule division to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleDivisionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'scheduledivision_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Division'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Section'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleDivisionGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Return default for new Schedule Division
    //
    if( $args['scheduledivision_id'] == 0 ) {
        $scheduledivision = array('id'=>0,
            'festival_id'=>'',
            'ssection_id'=>(isset($args['ssection_id']) ? $args['ssection_id'] : 0),
            'location_id'=>0,
            'adjudicator_id'=>0,
            'name'=>'',
            'flags' => 0,
            'division_date'=>'',
            'address'=>'',
            'results_notes'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Division
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_schedule_divisions.id, "
            . "ciniki_musicfestival_schedule_divisions.festival_id, "
            . "ciniki_musicfestival_schedule_divisions.ssection_id, "
            . "ciniki_musicfestival_schedule_divisions.location_id, "
            . "ciniki_musicfestival_schedule_divisions.adjudicator_id, "
            . "ciniki_musicfestival_schedule_divisions.name, "
            . "ciniki_musicfestival_schedule_divisions.flags, "
            . "ciniki_musicfestival_schedule_divisions.division_date, "
            . "ciniki_musicfestival_schedule_divisions.address, "
            . "ciniki_musicfestival_schedule_divisions.results_notes "
            . "FROM ciniki_musicfestival_schedule_divisions "
            . "WHERE ciniki_musicfestival_schedule_divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_schedule_divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'scheduledivisions', 'fname'=>'id', 
                'fields'=>array('festival_id', 'ssection_id', 'name', 'flags', 'division_date', 
                    'address', 'adjudicator_id', 'location_id', 'results_notes',
                    ),
                'utctotz'=>array('division_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.93', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['scheduledivisions'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.94', 'msg'=>'Unable to find Schedule Division'));
        }
        $scheduledivision = $rc['scheduledivisions'][0];
    }

    $rsp = array('stat'=>'ok', 'scheduledivision'=>$scheduledivision);

    //
    // Get the list of sections
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_musicfestival_schedule_sections "
        . "WHERE ciniki_musicfestival_schedule_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_schedule_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.100', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
    }
    if( isset($rc['sections']) ) {
        $rsp['schedulesections'] = $rc['sections'];
    }

    //
    // Get the list of locations
    //
    $strsql = "SELECT locations.id, "
        . "locations.name "
        . "FROM ciniki_musicfestival_locations AS locations "
        . "WHERE locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND locations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY locations.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'locations', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['locations'] = isset($rc['locations']) ? $rc['locations'] : array();

    //
    // Get the list of adjudicators
    //
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.customer_id, "
        . "customers.display_name "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'customer_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['adjudicators'] = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    return $rsp;
}
?>
