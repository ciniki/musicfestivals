<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_scheduleOPFWord(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %e, %Y') AS division_date_text, "
        . "locations.name AS location_name, "
        . "IFNULL(arefs.adjudicator_id, 0) AS adjudicator_id, "
        . "IFNULL(customers.display_name, '') AS adjudicator_name, "
        . "CONCAT_WS(' ', divisions.division_date, timeslots.slot_time) AS division_sort_key, "
        . "timeslots.slot_time, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.flags AS timeslot_flags, "
        . "timeslots.groupname AS timeslot_groupname, "
        . "timeslots.description, "
        . "timeslots.start_num, "
        . "timeslots.slot_seconds, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.composer1, "
        . "registrations.composer2, "
        . "registrations.composer3, "
        . "registrations.composer4, "
        . "registrations.composer5, "
        . "registrations.composer6, "
        . "registrations.composer7, "
        . "registrations.composer8, "
        . "registrations.movements1, "
        . "registrations.movements2, "
        . "registrations.movements3, "
        . "registrations.movements4, "
        . "registrations.movements5, "
        . "registrations.movements6, "
        . "registrations.movements7, "
        . "registrations.movements8, "
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.perf_time1, "
        . "registrations.perf_time2, "
        . "registrations.perf_time3, "
        . "registrations.perf_time4, "
        . "registrations.perf_time5, "
        . "registrations.perf_time6, "
        . "registrations.perf_time7, "
        . "registrations.perf_time8, "
        . "registrations.participation, "
        . "classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
            . "( "
                . "(ssections.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.schedulesection') "
                . "OR (divisions.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.scheduledivision') "
                . ") "
                . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
            . "arefs.adjudicator_id = adjudicators.id "
            ."AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "( "
                . "timeslots.id = registrations.timeslot_id "
                . "OR timeslots.id = registrations.finals_timeslot_id "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id " 
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id " 
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['division_id']) && $args['division_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
//        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
        $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY divisions.division_date, ssections.sequence, divisions.name, divisions.id, slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence, class_code, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'section_name', 'date'=>'division_date_text', 
                'location_name', 'adjudicator_id', 'adjudicator_name',
                'sort_key' => 'division_sort_key',
                ),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'flags'=>'timeslot_flags',
                'groupname'=>'timeslot_groupname', 'slot_time', 'time'=>'slot_time_text', 
                'start_num', 'slot_seconds', 'description', 'class_id', 'class_name',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'participation', 
                'description', 'category_name', 'section_name'=>'syllabus_section_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                'class_code', 'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();

    //
    // Build word document
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/vendor/autoload.php');

    $PHPWord = new \PhpOffice\PhpWord\PhpWord();
    $PHPWord->addTitleStyle(1, array('bold'=>true, 'size'=>20), array('spaceBefore'=>240, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(2, array('bold'=>true, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(3, array('bold'=>false, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addParagraphStyle('Divisions Date', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        ));
    $PHPWord->addParagraphStyle('Divisions Header', array('align' => 'center', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        ));
    $PHPWord->addParagraphStyle('Timeslots Name', array('align' => 'left', 'spaceAfter' => 120, 'spaceBefore'=>120,
        'lineHeight' => '1.0',
        'keepLines' => true,
        'keepNext' => true,
        ));
    $PHPWord->addParagraphStyle('Timeslots Time', array('align' => 'center', 'spaceAfter' => 120, 'spaceBefore'=>120,
        'lineHeight' => '1.0',
        'keepLines' => true,
        'keepNext' => true,
        ));
    $PHPWord->addParagraphStyle('Divisions Break', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => false,
        'keepNext' => false,
        ));
    $PHPWord->addFontStyle('Division Font', ['size'=>11, 'bold'=>true]);
    $PHPWord->addFontStyle('Timeslot Font', ['size'=>11, 'bold'=>false]);
    
    $filename = 'Schedule'; 

    $sectionWord = $PHPWord->addSection([
        'marginTop' => 1000,
        'marginBottom' => 1000,
        'marginLeft' => 1000,
        'marginRight' => 1000,
        'orientation' => 'portrait',
        'paperSize' => 'Letter',
        ]);
    $header = $sectionWord->addHeader();
    $footer = $sectionWord->addFooter();

    $w = [3200, 1800, 1300, 1300, 1300, 1300];

    foreach($divisions as $division) {
        if( !isset($division['timeslots']) ) {
            continue;
        }
        $header = $sectionWord->addHeader();
        $footer = $sectionWord->addFooter();

        //
        // Add first line
        //
        $table = $sectionWord->addTable([
            'borderTopSize'=>0, 
            'borderBottomSize'=>0, 
            'borderLeftSize'=>0, 
            'borderRightSize'=>0, 
            'borderColor' => 'ffffff',
            'cellMargin' => 100,
            'cellMarginLeft' => 100,
            'cellMarginTop' => 0,
            'cellMarginBottom' => 0,
            'cellMarginRight' => 100,
            'shading' => [],
            'cellSpacing' => 0,
            ]);
        $table->addRow(240, ['vAlign'=>'center', 'cantSplit'=>'true', 'shadding'=>'ffffff']);
        $table->addCell($w[0], ['valign'=>'center', 'bgColor'=>'ffffff'])->addText($division['date'], 'Division Font', 'Divisions Date');
        $table->addCell($w[1], ['valign'=>'center', 'bgColor'=>'ffffff'])->addText('Check-in time', 'Division Font', 'Divisions Header');
        $table->addCell($w[2], ['valign'=>'center', 'bgColor'=>'ffffff'])->addText('Start', 'Division Font', 'Divisions Header');
        $table->addCell($w[3], ['valign'=>'center', 'bgColor'=>'ffffff'])->addText('End', 'Division Font', 'Divisions Header');
        $table->addCell($w[4], ['valign'=>'center', 'bgColor'=>'ffffff'])->addText('Break', 'Division Font', 'Divisions Header');
        $table->addCell($w[5], ['valign'=>'center', 'bgColor'=>'ffffff'])->addText('Duration', 'Division Font', 'Divisions Header');

        $i = 0;
        for($i = 0; $i < count($division['timeslots']); $i++) {
            //
            // Calculate checkin time
            //
            $timeslot = $division['timeslots'][$i];

            $dt = new DateTime($division['date'] . ' ' . $timeslot['time'], new DateTimezone($intl_timezone));
            $checkin = clone $dt;
            $checkin->sub(new DateInterval('PT15M'));

            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotProcess');
            $rc = ciniki_musicfestivals_scheduleTimeslotProcess($ciniki, $tnid, $timeslot, [
                'festival' => $festival,
                ]);
//            unset($timeslot['registrations']);
//            error_log(print_r($timeslot,true));
            $end_dt = new DateTime($division['date'] . ' ' . $timeslot['end_time_text'], new DateTimezone($intl_timezone));

            $table->addRow(240, ['vAlign'=>'center', 'cantSplit'=>'true']);
            $table->addCell($w[0], ['valign'=>'center'])->addText($timeslot['name'] . ' - ' . $timeslot['groupname'], 'Timeslot Font', 'Timeslots Name');
            $table->addCell($w[1], ['valign'=>'center'])->addText($checkin->format('g:i A'), 'Timeslot Font', 'Timeslots Time');
            $table->addCell($w[2], ['valign'=>'center'])->addText($timeslot['time'], 'Timeslot Font', 'Timeslots Time');
            $table->addCell($w[3], ['valign'=>'center'])->addText($end_dt->format('g:i A'), 'Timeslot Font', 'Timeslots Time');
            $break_length_text = '';
            if( isset($division['timeslots'][($i+1)]) && preg_match('/Break/', $division['timeslots'][($i+1)]['name']) ) {
                $i++;
                $break_dt = new DateTime($division['date'] . ' ' . $timeslot['end_time_text'], new DateTimezone($intl_timezone));
                if( isset($division['timeslots'][($i+1)]['time']) ) {
                    $next_dt = new DateTime($division['date'] . ' ' . $division['timeslots'][($i+1)]['time'], new DateTimezone($intl_timezone));
                    $interval = $next_dt->diff($break_dt);
                    $break_length_text = $interval->format('%I:%S');
                }
            }
            $table->addCell($w[4], ['valign'=>'center'])->addText($break_length_text, 'Timeslot Font', 'Timeslots Time');
            $interval = $end_dt->diff($dt);
            $duration_text = ($interval->format('%H') * 60) + $interval->format('%I') . ':' . $interval->format('%S');
            $table->addCell($w[5], ['valign'=>'center'])->addText($duration_text, 'Timeslot Font', 'Timeslots Time');
        }

        $sectionWord->addTextBreak(1, null, 'Divisions Break');
    }

    return array('stat'=>'ok', 'word'=>$PHPWord, 'filename'=>$filename);
}
?>
