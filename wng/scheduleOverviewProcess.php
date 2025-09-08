<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_scheduleOverviewProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1018', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1019', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Check if schedule is displaying just live or just virtual
    //
    $ipv_sql = '';
    if( isset($s['ipv']) && $s['ipv'] == 'inperson' ) {
        $lv_word = 'Live ';
        $ipv_sql = "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($s['ipv']) && $s['ipv'] == 'virtual' ) {
        $lv_word = 'Virtual ';
        $ipv_sql = "AND registrations.participation = 1 ";
    }

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1020', 'msg'=>"No festival specified"));
    }

    //
    // Get the music festival details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $s['festival-id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    $division_date_format = '%W, %M %D, %Y';
    if( isset($festival['schedule-date-format']) && $festival['schedule-date-format'] != '' ) {
        $division_date_format = $festival['schedule-date-format'];
    }


    //
    // Get the list of sections and divisions and locations 
    //
    $strsql = "SELECT locations.id AS location_id, "
        . "locations.name AS location_name, "
        . "ssections.id AS ssection_id, "
        . "ssections.name AS ssection_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.division_date AS division_date, "
        . "DATE_FORMAT(divisions.division_date, '%a, %b %D') AS division_date_str, "
        . "MIN(timeslots.slot_time) AS start_time "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") " 
        . "WHERE ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND (ssections.flags&0x0110) = 0x0110 "
        . "GROUP BY location_id, ssection_id, division_id "
        . "ORDER BY locations.name, divisions.division_date, start_time "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'locations', 'fname'=>'location_id', 
            'fields'=>array('id'=>'location_id', 'name'=>'location_name'),
            ),
        array('container'=>'dates', 'fname'=>'division_date', 
            'fields'=>array('date' => 'division_date', 'str'=>'division_date_str'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1021', 'msg'=>'Unable to load locations', 'err'=>$rc['err']));
    }
    $locations = isset($rc['locations']) ? $rc['locations'] : array();

    //
    // Get the list of dates
    //
    $dates = [];
    foreach($locations as $location) {
        foreach($location['dates'] as $date => $date_details) {
            if( !in_array($date, $dates) ) {
                $dates[$date] = $date_details['str'];
            }
        }
    }
    ksort($dates);

    //
    // Build data array
    //
    $rows = [];
    foreach($locations as $location) {
        $rows[$location['id']] = [
            'name' => $location['name'],
            ];
        foreach($dates as $date => $date_str) {
            $division_list = '';
            if( isset($location['dates'][$date]['divisions']) ) {
                foreach($location['dates'][$date]['divisions'] as $division) {
                    $division_list .= ($division_list != '' ? '<br/><br/>' : '') . $division['name'];
                }
            }
            $rows[$location['id']][$date_str] = $division_list;
        }
    }

    $block = array(
        'type' => 'table',
        'title' => $s['title'],
        'class' => 'musicfestival-schedule-overview fold-at-60',
        'headers' => 'yes',
        'columns' => array(
            array('label' => 'Location', 'field' => 'name'),
            ),
        'rows' => $rows,
        );
    foreach($dates as $date => $date_str) {
        $block['columns'][] = array('label' => $date_str, 'field' => $date_str, 'fold-label'=>"{$date_str}:");
    }
    $blocks[] = $block;

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
