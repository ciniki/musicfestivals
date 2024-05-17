<?php
//
// Description
// ===========
// This method will return the details for multislot UI scheduler allowing
// 1 class to be scheduled over multiple timeslots
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule time slot is attached to.
// scheduletimeslot_id:          The ID of the schedule time slot to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleMultislot($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'timeslot1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Timeslot'),
        'timeslot2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Second Timeslot'),
        'timeslot3_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Third Timeslot'),
        'timeslot4_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Fourth Timeslot'),
        'timeslot5_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Fifth Timeslot'),
        'timeslot6_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sixth Timeslot'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Section'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Class'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotGet');
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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load sapos maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sapos_maps = $rc['maps'];

    $rsp = array('stat'=>'ok');

    //
    // Get the list of available timeslots
    //
    $strsql = "SELECT timeslots.id, "
        . "CONCAT_WS(' - ', sections.name, divisions.name, IF(timeslots.name='', TIME_FORMAT(slot_time, '%l:%i %p'), timeslots.name)) AS name "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name, divisions.name, timeslots.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'timeslots', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.709', 'msg'=>'Unable to load timeslots', 'err'=>$rc['err']));
    }
    $rsp['timeslots'] = isset($rc['timeslots']) ? $rc['timeslots'] : array();
    
    //
    // Get the registrations for each timeslot
    //
    for($i = 1; $i <= 6; $i++) {
        $rsp["registrations{$i}"] = array();
        if( isset($args["timeslot{$i}_id"]) && $args["timeslot{$i}_id"] > 0 ) {
            //
            // Get the list of registrations
            //
            $strsql = "SELECT registrations.id, "
                . "registrations.display_name, "
                . "registrations.flags, "
                . "registrations.participation, "
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
                . "registrations.perf_time1, "
                . "registrations.perf_time2, "
                . "registrations.perf_time3, "
                . "registrations.perf_time4, "
                . "registrations.perf_time5, "
                . "registrations.perf_time6, "
                . "registrations.perf_time7, "
                . "registrations.perf_time8, "
                . "registrations.timeslot_id, "
                . "TIME_FORMAT(registrations.timeslot_time, '%l:%i') AS timeslot_time, "
                . "registrations.timeslot_sequence, "
                . "registrations.notes, "
                . "GROUP_CONCAT(' ', competitors.notes) AS competitor_notes, "
                . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
                . "IFNULL(members.name, '') AS member_name, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
                . "categories.name AS category_name, "
                . "sections.name AS section_name "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                    . "(" 
                        . "registrations.competitor1_id = competitors.id "
                        . "OR registrations.competitor2_id = competitors.id "
                        . "OR registrations.competitor3_id = competitors.id "
                        . "OR registrations.competitor4_id = competitors.id "
                        . ") "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "registrations.class_id = classes.id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "categories.section_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS accompanists ON ("
                    . "registrations.accompanist_customer_id = accompanists.id "
                    . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                    . "registrations.member_id = members.id "
                    . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args["timeslot{$i}_id"]) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY registrations.id "
                . "ORDER BY registrations.timeslot_sequence, registrations.display_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'registrations', 'fname'=>'id', 
                    'fields'=>array('id', 'display_name', 'timeslot_id', 'timeslot_time', 'timeslot_sequence', 
                        'flags', 'accompanist_name', 'member_name', 
                        'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                        'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                        'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                        'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                        'class_code', 'class_name', 'category_name', 'section_name', 
                        'participation', 'notes', 'competitor_notes',
                        ),
                    'maps'=>array(
                        'participation'=>$maps['registration']['participation'],
                        ),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.710', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
            }
            $rsp["registrations{$i}"] = isset($rc['registrations']) ? $rc['registrations'] : array();
            foreach($rsp["registrations{$i}"] as $rid => $reg) {
                $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, array('times'=>'startsum', 'numbers'=>'yes'));
                $rsp["registrations{$i}"][$rid]['titles'] = $rc['titles'];
                $rsp["registrations{$i}"][$rid]['perf_time'] = $rc['perf_time'];
                if( $reg['competitor_notes'] != '' ) {
                    $rsp["registrations{$i}"][$rid]['notes'] .= ($reg['notes'] != '' ? ' ' : '') . $reg['competitor_notes'];
                }
            }
        }
    }

    //
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.name "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.711', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
    array_unshift($rsp['sections'], array('id'=>0, 'name'=>'Select Section'));

    //
    // Get the list of divisions if section is specified
    //
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql = "SELECT classes.id, "
            . "CONCAT_WS('', classes.code, ' - ', classes.name, ' (', COUNT(registrations.id), ')') AS name, "
            . "COUNT(registrations.id) AS num_registrations "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id "
                . "AND registrations.timeslot_id = 0 "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY classes.id "
            . "ORDER BY classes.code, classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_registrations')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.712', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $rsp['classes'] = isset($rc['classes']) ? $rc['classes'] : array();
        if( count($rsp['classes']) == 0 ) {
            $rsp['classes'] = array(
                array('id' => 0, 'name' => 'No Unscheduled Registrations'),
                );
        } else {
            array_unshift($rsp['classes'], array('id'=>0, 'name'=>'Select Class'));
        }
    } else {
        $rsp['classes'] = array(
            array('id' => 0, 'name' => 'Select Section'),
            );
    }

    //
    // Get the list of unscheduled registrations for a category
    //
    if( isset($args['class_id']) && $args['class_id'] > 0 ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.flags, "
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
            . "registrations.perf_time1, "
            . "registrations.perf_time2, "
            . "registrations.perf_time3, "
            . "registrations.perf_time4, "
            . "registrations.perf_time5, "
            . "registrations.perf_time6, "
            . "registrations.perf_time7, "
            . "registrations.perf_time8, "
            . "registrations.participation, "
            . "registrations.notes, "
            . "GROUP_CONCAT(' ', competitors.notes) AS competitor_notes, "
//            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
            . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
            . "IFNULL(members.name, '') AS member_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id "
                . "AND registrations.timeslot_id = 0 "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "(" 
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . ") "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
/*            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") " */
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS accompanists ON ("
                . "registrations.accompanist_customer_id = accompanists.id "
                . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                . "registrations.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        if( isset($args['class_id']) && $args['class_id'] > 0 ) {
            $strsql .= "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
        } elseif( isset($args['category_id']) && $args['category_id'] > 0 ) {
            $strsql .= "WHERE classes.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
        $strsql .= "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY registrations.id "
            . "ORDER BY categories.sequence, categories.name, member_name, class_code "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'flags', 'accompanist_name', 'member_name', 
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                    'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                    'class_code', 'class_name', 'category_name', 'section_name', 'participation', 'notes', 'competitor_notes',
                    ),
                'maps'=>array(
                    'participation'=>$maps['registration']['participation'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.713', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $rsp['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
        foreach($rsp['unscheduled_registrations'] as $rid => $reg) {
            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, array('times'=>'startsum', 'numbers'=>'yes'));
            $rsp['unscheduled_registrations'][$rid]['titles'] = $rc['titles'];
            $rsp['unscheduled_registrations'][$rid]['perf_time'] = $rc['perf_time'];
            if( $reg['competitor_notes'] != '' ) {
                $rsp['unscheduled_registrations'][$rid]['notes'] .= ($reg['notes'] != '' ? ' ' : '') . $reg['competitor_notes'];
            }
        }
    } else {
        $rsp['unschedule_registrations'] = array();
    }

    return $rsp;
}
?>
