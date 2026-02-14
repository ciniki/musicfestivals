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
function ciniki_musicfestivals_wng_scheduleOverviewDaysProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1386', 'msg'=>"I'm sorry, the page you requested does not exist."));
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1387', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1388', 'msg'=>"No festival specified"));
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
    $strsql = "SELECT ssections.id AS ssection_id, "
        . "ssections.name AS ssection_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.division_date AS division_date, "
        . "DATE_FORMAT(divisions.division_date, '%a, %b %D') AS division_date_str "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND (ssections.flags&0x80) = 0x80 "
        . "ORDER BY divisions.division_date, divisions.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'dates', 'fname'=>'division_date', 
            'fields'=>array('date' => 'division_date', 'title'=>'division_date_str'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1457', 'msg'=>'Unable to load locations', 'err'=>$rc['err']));
    }
    $dates = isset($rc['dates']) ? $rc['dates'] : array();

    //
    // Build the content for divisions
    //
    foreach($dates as $did => $date) {
        if( !isset($date['divisions']) ) {
            unset($dates[$did]);
            continue;
        }
        $dates[$did]['content'] = '';
        $division_names = [];
        foreach($date['divisions'] as $division) {
            $name = preg_replace("/\s*-\s*(Group|Playoffs).*/", '', $division['name']);
            if( !in_array($name, $division_names) ) {
                $division_names[] = $name;
                $dates[$did]['content'] .= ($dates[$did]['content'] != '' ? "<br>" : '') . $name;
            }
        }
    }

    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text',
            'title' => $s['title'],
            'level' => ($section['title_sequence'] > 1 ? 2 : 1),
            'content' => $s['content'],
            );
    } else {
        $blocks[] = array(
            'type' => 'title',
            'title' => $s['title'],
            );
    }
    $block = array(
        'type' => 'textcards',
        'class' => 'musicfestival-schedule-overview-days',
        'items' => $dates,
        );
    $blocks[] = $block;

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
