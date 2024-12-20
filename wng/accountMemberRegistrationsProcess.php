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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sapos_maps = $rc['maps'];

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
        . "registrations.placement, "
        . "registrations.finals_placement, "
        . "classes.name AS class_name, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS class, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "IFNULL(TIME_FORMAT(registrations.timeslot_time, '%l:%i %p'), '') AS timeslot_time, ";
    } else {
        $strsql .= "IFNULL(TIME_FORMAT(timeslots.slot_time, '%l:%i %p'), '') AS timeslot_time, ";
    }
    $strsql .= "IFNULL(timeslots.flags, 0) AS timeslot_flags, "
        . "IFNULL(DATE_FORMAT(divisions.division_date, '%b %D'), '') AS timeslot_date, "
        . "IFNULL(locations.name, '') AS location_name, "
        . "divisions.flags AS division_flags, "
        . "ssections.flags AS section_flags, "
        . "IFNULL(CONCAT_WS('.', invoices.invoice_type, invoices.status), 0) AS invoice_status "
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
        . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
            . "registrations.invoice_id = invoices.id "
            . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member']['id']) . "' "
        . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival']['id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY classes.name, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array( 'id', 'display_name', 'participation', 'class', 'class_name', 'timeslot_flags',
                'timeslot_time', 'timeslot_date', 'location_name', 'division_flags', 'section_flags', 
                'placement', 'finals_placement', 'invoice_status',
                ),
            'maps'=>array('invoice_status'=>$sapos_maps['invoice']['typestatus']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.756', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    uasort($registrations, function($a, $b) {
        return strnatcmp($a['class_name'], $b['class_name']);
        });

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
        //
        // If results have been released on the website, show them instead of the scheduled time
        //
        if( (($reg['section_flags']&0x20) == 0x20 || ($reg['division_flags']&0x20) == 0x20) ) {
            $registrations[$rid]['scheduled'] = $reg['placement'];
            if( $reg['finals_placement'] != '' ) {
                $registrations[$rid]['scheduled'] .= ($registrations[$rid]['scheduled'] != '' ? ' - ' : '') . $reg['finals_placement'];
            }
        }
    }

    $blocks[] = array(
        'type' => 'title',
        'level' => 2,
        'title' => $args['member']['name'] . ' - ' . $args['festival']['name'] . ' - Registrations',
        );

    if( count($registrations) > 0 ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => array(   
                array('text' => 'Download Excel', 'target' => '_blank', 'url' => "{$base_url}/registrations/{$request['uri_split'][3]}/{$request['uri_split'][4]}/registrations.xls"),
                array('text' => 'Download PDF', 'target' => '_blank', 'url' => "{$base_url}/registrations/{$request['uri_split'][3]}/{$request['uri_split'][4]}/registrations.pdf"),
                ),
            );
    }

    if( isset($request['uri_split'][5]) && $request['uri_split'][5] == 'registrations.xls' ) {
        //
        // Generate XLS of registrations
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'memberRegistrationsExcel');
        $rc = ciniki_musicfestivals_templates_memberRegistrationsExcel($ciniki, $tnid, [
            'festival_id' => $args['festival']['id'],
            'registrations' => $registrations,
            ]);
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to generate local festival registrations excel');
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => 'Unable to create Excel file, please contact us for help.',
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }

        //
        // Output the excel file
        //
        $filename = "{$args['member']['name']} - {$args['festival']['name']} - Registrations";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($rc['excel'], 'Excel5');
        $objWriter->save('php://output');

        return array('stat'=>'exit');
    }
    elseif( isset($request['uri_split'][5]) && $request['uri_split'][5] == 'registrations.pdf' ) {
        //
        // Generate PDF of registrations
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'memberRegistrationsPDF');
        $rc = ciniki_musicfestivals_templates_memberRegistrationsPDF($ciniki, $tnid, [
            'festival_id' => $args['festival']['id'],
            'title' => $args['member']['name'],
            'subtitle' => $args['festival']['name'] . ' - Registrations',
            'registrations' => $registrations,
            ]);
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to generate local festival registrations excel');
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => 'Unable to create PDF file, please contact us for help.',
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }

        //
        // Output the excel file
        //
        $filename = "{$args['member']['name']} - {$args['festival']['name']} - Registrations";
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-Type: application/pdf');
        header('Cache-Control: max-age=0');

        $rc['pdf']->Output($filename . '.pdf', 'I');
        return array('stat'=>'exit');
    }

    $blocks[] = array(
        'type' => 'table',
        'headers' => 'yes',
        'class' => 'fold-at-50',
        'columns' => array(
            array('label'=>'Class', 'fold-label'=>'Class: ', 'field'=>'class'),
            array('label'=>'Competitor', 'field'=>'display_name'),
            array('label'=>'Status', 'field'=>'invoice_status'),
            array('label'=>'Scheduled/Results', 'fold-label'=>'Scheduled/Results: ', 'field'=>'scheduled'),
            ),
        'rows' => $registrations,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
