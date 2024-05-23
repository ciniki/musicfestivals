<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountMemberRegistrationsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'videoProcess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestivalmembers';
    $display = 'list';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Back' ) {
        header("Location: {$request['ssl_domain_base_url']}/account/musicfestivalmembers");
        return array('stat'=>'exit');
    }

    if( !isset($args['member']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.757', 'msg'=>'No member specified'));
    }
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.758', 'msg'=>'No festival specified'));
    }

    //
    // Load the list of recommendations for the member festival
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.display_name, "
        . "registrations.participation, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS class, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "IFNULL(TIME_FORMAT(registrations.timeslot_time, '%l:%i %p'), '') AS timeslot_time, ";
    } else {
        $strsql .= "IFNULL(TIME_FORMAT(timeslots.slot_time, '%l:%i %p'), '') AS timeslot_time, ";
    }
    $strsql .= "IFNULL(DATE_FORMAT(divisions.division_date, '%b %D'), '') AS timeslot_date, "
        . "IFNULL(locations.name, '') AS location_name, "
        . "divisions.flags AS division_flags, "
        . "ssections.flags AS section_flags "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member']['id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY class, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array( 'id', 'display_name', 'participation', 'class', 
                'timeslot_time', 'timeslot_date', 'location_name', 'division_flags', 'section_flags',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.756', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    foreach($registrations as $rid => $reg) {
        $registrations[$rid]['scheduled'] = '';
        if( $reg['participation'] == 1 ) {
            $registrations[$rid]['scheduled'] = 'Virtual';
        } elseif( $reg['timeslot_time'] != '' && $reg['timeslot_date'] != '' && ($reg['section_flags']&0x01) == 0x01 ) {
            $registrations[$rid]['scheduled'] = $reg['timeslot_date'] . ' @ ' . $reg['timeslot_time'];
            if( $reg['location_name'] != '' ) {
                $registrations[$rid]['scheduled'] .= '<br/>' . $reg['location_name'];
            }
        }
    }

    $blocks[] = array(
        'type' => 'table',
        'headers' => 'yes',
        'class' => 'fold-at-50',
        'columns' => array(
            array('label'=>'Class', 'fold-label'=>'Class: ', 'field'=>'class'),
            array('label'=>'Competitor', 'field'=>'display_name'),
            array('label'=>'Scheduled', 'fold-label'=>'Scheduled: ', 'field'=>'scheduled'),
            ),
        'rows' => $registrations,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
