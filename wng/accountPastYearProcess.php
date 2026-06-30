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
function ciniki_musicfestivals_wng_accountPastYearProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $args['base_url'];

    //
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Get the list of registrations for the past year
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.display_name, "
        . "registrations.comments, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "registrations.participation, "
        . "registrations.fulltitle1, "
        . "registrations.fulltitle2, "
        . "registrations.fulltitle3, "
        . "registrations.fulltitle4, "
        . "registrations.fulltitle5, "
        . "registrations.fulltitle6, "
        . "registrations.fulltitle7, "
        . "registrations.fulltitle8, "
        . "sections.name AS section_name, "
        . "categories.name AS category_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags "
        . "FROM ciniki_musicfestival_registrations AS registrations "
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
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.status < 70 "
        . "AND registrations.status > 5 "
//        . "AND registrations.comments <> '' "
        . "AND ("
            . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'section_name', 'category_name', 'class_code', 'class_name', 'class_flags',
                'fulltitle1', 'fulltitle2', 'fulltitle3', 'fulltitle4', 'fulltitle5', 'fulltitle6', 'fulltitle7', 'fulltitle8', 
                'participation', 'mark', 'placement', 'level', 'comments',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.932', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
   
    foreach($registrations as $rid => $reg) {
        $rc = ciniki_musicfestivals_classNameFormat($ciniki, $tnid, [
            'format' => isset($festival['comments-class-format']) ? $festival['comments-class-format'] : '',
            'code' => $reg['class_code'],
            'name' => $reg['class_name'],
            'category' => $reg['category_name'],
            'section' => $reg['section_name'],
            ]);
        $registrations[$rid]['name_class'] = "<b>{$reg['display_name']}</b><br/>{$rc['name']}";
        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg, ['basicnumbers'=>'yes', 'newline'=>'<br/>']);
        if( isset($rc['titles']) ) {
            $registrations[$rid]['titles'] = $rc['titles'];
        }
        $registrations[$rid]['buttons'] = '';
        if( $reg['participation'] == 1 && $reg['comments'] != '' ) {
            $registrations[$rid]['buttons'] .= "<a class='button' target='_blank' href='{$base_url}/{$reg['id']}/comments.pdf'>Comments</a>"
                . "<a class='button' target='_blank' href='{$base_url}/{$reg['id']}/certificate.pdf'>Certificate</a>"
                . "";
        }

        //
        // Check if download comments requested
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+4)]) 
            && $request['uri_split'][($request['cur_uri_pos']+3)] == $reg['id']
            && $request['uri_split'][($request['cur_uri_pos']+4)] == 'comments.pdf'
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'commentsPDF');
            $rc = ciniki_musicfestivals_templates_commentsPDF($ciniki, $tnid, array(
                'festival_id' => $festival['id'],
                'registration_id' => $reg['id'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.933', 'msg'=>'Unable to load comments', 'err'=>$rc['err']));
            }
            if( isset($rc['pdf']) ) {
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Content-Type: application/pdf');
                header('Cache-Control: max-age=0');
                $rc['pdf']->Output($rc['filename'], 'I');
                return array('stat'=>'exit');
            }
        }
        //
        // Check if download comments requested
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+4)]) 
            && $request['uri_split'][($request['cur_uri_pos']+3)] == $reg['id']
            && $request['uri_split'][($request['cur_uri_pos']+4)] == 'certificate.pdf'
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationCertsPDF');
            $rc = ciniki_musicfestivals_registrationCertsPDF($ciniki, $tnid, array(
                'festival_id' => $festival['id'],
                'registration_id' => $reg['id'],
                'single' => 'yes',
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.934', 'msg'=>'Unable to load comments', 'err'=>$rc['err']));
            }
            if( isset($rc['pdf']) ) {
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Content-Type: application/pdf');
                header('Cache-Control: max-age=0');
                $rc['pdf']->Output('Certificate.pdf', 'I');
                return array('stat'=>'exit');
            }
        }
    }

    $columns = array(
        array('label' => $festival['competitor-label-singular'] . '/Class', 'field' => 'name_class', 'class' => 'alignleft'),
//        array('label' => 'Class', 'field' => 'codename', 'class' => 'alignleft'),
        array('label' => 'Titles', 'field' => 'titles', 'class' => 'alignleft'),
        );
    if( isset($festival['comments-mark-pdf']) && $festival['comments-mark-pdf'] == 'yes' ) {
        $label = isset($festival['comments-mark-label']) && $festival['comments-mark-label'] != '' ? $festival['comments-mark-label'] : 'Mark';
        $columns[] = array('label' => $label, 'fold-label' => "{$label}:", 'field' => 'mark', 'class' => '');
    }
    if( isset($festival['comments-placement-pdf']) && $festival['comments-placement-pdf'] == 'yes' ) {
        $label = isset($festival['comments-placement-label']) && $festival['comments-placement-label'] != '' ? $festival['comments-placement-label'] : 'Placement';
        $columns[] = array('label' => $label, 'fold-label' => "{$label}:", 'field' => 'placement', 'class' => '');
    }
    if( isset($festival['comments-level-pdf']) && $festival['comments-level-pdf'] == 'yes' ) {
        $label = isset($festival['comments-level-label']) && $festival['comments-level-label'] != '' ? $festival['comments-level-label'] : 'Level';
        $columns[] = array('label' => $label, 'fold-label' => "{$label}:", 'field' => 'level', 'class' => '');
    }
    $columns[] = array('label' => '', 'field' => 'buttons', 'class' => 'buttons alignright');
    $blocks[] = array(
        'type' => 'table',
        'title' => $festival['name'] . ' Past Results',
        'class' => 'musicfestival-registrations limit-width limit-width-90 fold-at-60',
        'headers' => 'yes',
        'columns' => $columns,
        'rows' => $registrations,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
