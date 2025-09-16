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
function ciniki_musicfestivals_wng_accountMemberResultsProcess(&$ciniki, $tnid, &$request, $args) {

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
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/members';
    $display = 'list';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Back' ) {
        header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/members");
        return array('stat'=>'exit');
    }

    if( !isset($args['member']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1009', 'msg'=>'No member specified'));
    }
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1010', 'msg'=>'No festival specified'));
    }

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival']['id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $tnid, $festival);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the list of recommendations for the member festival
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.status, "
        . "registrations.status AS status_text, "
        . "registrations.display_name, "
        . "registrations.participation, "
        . "registrations.placement, "
        . "registrations.finals_placement, "
        . "classes.name AS class_name, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS class, "
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
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member']['id']) . "' "
        . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival']['id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY classes.name, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array( 'id', 'display_name', 'status', 'participation', 'class', 'class_name', 
                'division_flags', 'section_flags', 
                'placement', 'finals_placement', 'status', 'status_text', 
                ),
            'maps'=>array('status_text'=>$maps['registration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1011', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    uasort($registrations, function($a, $b) {
        return strnatcmp($a['class_name'], $b['class_name']);
        });

    foreach($registrations as $rid => $reg) {
        //
        // If results have been released on the website
        //
        $registrations[$rid]['result'] = '';
        if( (($reg['section_flags']&0x20) == 0x20 || ($reg['division_flags']&0x20) == 0x20) ) {
            $registrations[$rid]['result'] = $reg['placement'];
            if( $reg['finals_placement'] != '' ) {
                $registrations[$rid]['result'] .= ($registrations[$rid]['result'] != '' ? ' - ' : '') . $reg['finals_placement'];
            }
        }
        if( $reg['status'] >= 70 ) {
            $registrations[$rid]['result'] = $reg['status_text'];
        }
    }

    $blocks[] = array(
        'type' => 'title',
        'level' => 2,
        'title' => $args['member']['name'] . ' - ' . $args['festival']['name'] . ' - Results',
        );

    if( count($registrations) > 0 ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => array(   
                array('text' => 'Download Excel', 'target' => '_blank', 'url' => "{$base_url}/results/{$request['uri_split'][4]}/{$request['uri_split'][5]}/results.xls"),
                array('text' => 'Download PDF', 'target' => '_blank', 'url' => "{$base_url}/results/{$request['uri_split'][4]}/{$request['uri_split'][5]}/results.pdf"),
                ),
            );
    }

    if( isset($request['uri_split'][6]) && $request['uri_split'][6] == 'results.xls' ) {
        //
        // Generate XLS of registrations
        //
        $sheets = [
            'results' => [
                'label' => 'Results',
                'columns' => [
                    ['label' => 'Class', 'field' => 'class'],
                    ['label' => 'Competitor', 'field' => 'display_name'],
                    ['label' => 'Results', 'field' => 'result'],
                    ],
                'rows' => $registrations,
                ],
            ];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'excelGenerate');
        return ciniki_core_excelGenerate($ciniki, $tnid, [
            'sheets' => $sheets,
            'download' => 'yes', 
            'format' => 'xls',
            'filename' => "{$args['member']['name']} - {$args['festival']['name']} - Results.xls",
            ]);
    }
    elseif( isset($request['uri_split'][6]) && $request['uri_split'][6] == 'results.pdf' ) {
        //
        // Generate PDF of registrations
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'memberResultsPDF');
        $rc = ciniki_musicfestivals_templates_memberResultsPDF($ciniki, $tnid, [
            'festival_id' => $args['festival']['id'],
            'title' => $args['member']['name'],
            'subtitle' => $args['festival']['name'] . ' - Results',
            'registrations' => $registrations,
            ]);
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to generate local festival results pdf');
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
        $filename = "{$args['member']['name']} - {$args['festival']['name']} - Results";
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
//            array('label'=>'Status', 'field'=>'status'),
            array('label'=>'Results', 'field'=>'result'),
            ),
        'rows' => $registrations,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
