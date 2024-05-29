<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountScheduleProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestivalschedule';
    $display = 'list';
    $form_errors = '';
    $errors = array();

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.419', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load the festival details
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.422', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    if( isset($rc['settings']) ) {
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }
    }

    //
    // Check if request to download PDF
    //
    if( isset($_GET['schedulepdf']) && $_GET['schedulepdf'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'registrationsSchedulePDF');
        $rc = ciniki_musicfestivals_templates_registrationsSchedulePDF($ciniki, $tnid, array(
            'festival_id' => $festival['id'],
            'customer_id' => $request['session']['customer']['id'],
            'shared' => 'yes',
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.700', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'], 'I');
            return array('stat'=>'exit');
        }
    }

    //
    // Get the list of registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.status, "
        . "registrations.invoice_id, "
        . "registrations.billing_customer_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.accompanist_customer_id, "
        . "registrations.member_id, "
        . "registrations.participation, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
        $strsql .= "registrations.pn_display_name AS display_name, ";
    } else {
        $strsql .= "registrations.display_name, ";
    }
    $strsql .= "registrations.title1, "
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
        . "registrations.fee, "
        . "registrations.participation, "
        . "classes.code AS class_code, "
        . "sections.name AS section_name, "
        . "categories.name AS category_name, "
        . "classes.name AS class_name, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS codename, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "IFNULL(TIME_FORMAT(registrations.timeslot_time, '%l:%i %p'), '') AS timeslot_time, ";
    } else {
        $strsql .= "IFNULL(TIME_FORMAT(timeslots.slot_time, '%l:%i %p'), '') AS timeslot_time, ";
    }
    $strsql .= "IFNULL(DATE_FORMAT(divisions.division_date, '%b %D, %Y'), '') AS timeslot_date, "
        . "IFNULL(locations.name, '') AS timeslot_address, "
        . "IFNULL(ssections.flags, 0) AS timeslot_flags, "
        . "IFNULL(invoices.status, 0) AS invoice_status "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
            . "registrations.invoice_id = invoices.id "
            . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE (registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . " OR ("
                . "registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND (registrations.flags&0x01) = 0x01 "
                . ") "
            . " OR ("
                . "registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND (registrations.flags&0x02) = 0x02 "
                . ") "
            . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' ";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
            $strsql .= "ORDER BY divisions.division_date, registrations.timeslot_time, registrations.display_name, registrations.status, registrations.display_name ";
        } else {
            $strsql .= "ORDER BY divisions.division_date, timeslots.slot_time, registrations.display_name, registrations.status, registrations.display_name ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'invoice_status', 'invoice_id', 
                    'billing_customer_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 'display_name', 
                    'class_code', 'class_name', 'section_name', 'category_name', 'codename', 
                    'fee', 'participation', 
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                    'timeslot_time', 'timeslot_date', 'timeslot_address', 'timeslot_flags',
                    ),
                ),
            ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.300', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    foreach($registrations as $rid => $reg) {
        if( ($festival['flags']&0x0100) == 0x0100 ) {
            $reg['codename'] = $reg['class_code'] . ' - ' . $reg['section_name'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name'];
        }
        $registrations[$rid]['titles'] = '';
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg, ['basicnumbers'=>'yes', 'newline'=>'<br/>']);
        if( isset($rc['titles']) ) {
            $registrations[$rid]['titles'] = $rc['titles'];
        }
        if( $reg['participation'] == 1 ) {
            $registrations[$rid]['codename'] .= ' (Virtual)';
        } elseif( $reg['participation'] == 0 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
            $registrations[$rid]['codename'] .= ' (Live)';
        } elseif( $reg['participation'] == 2 ) {
            $registrations[$rid]['codename'] .= ' (Adjudication Plus)';
        }

        //
        // If the registration has been schedule and schedule released
        //
        if( $reg['participation'] == 1 ) {
            $registrations[$rid]['scheduled'] = 'Virtual';
        } 
        elseif( ($reg['timeslot_flags']&0x01) == 0x01 
            && $reg['timeslot_time'] != ''
            && $reg['timeslot_date'] != ''
            ) {
            $registrations[$rid]['scheduled'] = $reg['timeslot_date'] . ' - ' . $reg['timeslot_time'] . '<br/>' . $reg['timeslot_address'];
        }
    }

    $blocks[] = array(
        'type' => 'table',
        'title' => $festival['name'] . ' - Schedule of Registrations',
        'class' => 'musicfestival-registrations limit-width limit-width-80 fold-at-50',
        'headers' => 'yes',
        'columns' => array(
            array('label' => 'Date/Time', 'fold-label'=>'Scheduled:', 'field' => 'scheduled', 'class' => 'alignleft'),
            array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
            array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
            array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
            array('label' => '', 'field' => 'viewbutton', 'class' => 'buttons alignright'),
            ),
        'rows' => $registrations,
        );
       
    $blocks[] = array(
        'type' => 'buttons',
        'class' => 'limit-width limit-width-80 aligncenter',
        'list' => array(array(
            'text' => 'Download Schedule PDF',
            'target' => '_blank',
            'url' => "/account/musicfestivalregistrations?schedulepdf=yes",
            )),
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
