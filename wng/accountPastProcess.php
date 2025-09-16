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
function ciniki_musicfestivals_wng_accountPastProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/pastmusicfestivals';

    //
    // Get the list of past festivals
    //
    $strsql = "SELECT DISTINCT festivals.id, "
        . "festivals.name, "
        . "festivals.permalink "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ( "
            . "registrations.festival_id = festivals.id "
            . "AND festivals.status = 50 "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ("
            . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND registrations.status < 70 "
        . "AND registrations.status > 5 "
//        . "AND registrations.comments <> '' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.931', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
    }
    $festivals = isset($rc['festivals']) ? $rc['festivals'] : array();

    foreach($festivals as $fid => $festival) {
        $festivals[$fid]['buttons'] = "<a class='button' href='{$base_url}/{$festival['permalink']}'>View Registrations</a>";
        if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) 
            && $request['uri_split'][($request['cur_uri_pos']+2)] == $festival['permalink']
            ) {
            $args['festival_id'] = $festival['id'];
            $args['base_url'] = $base_url . '/' . $festival['permalink'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountPastYearProcess');
            return ciniki_musicfestivals_wng_accountPastYearProcess($ciniki, $tnid, $request, $args);
        }
    }

    $blocks[] = array(
        'type' => 'table', 
        'title' => 'Past Festival Registrations',
        'class' => 'musicfestival-pastyears limit-width-90 fold-at-40',
        'headers' => 'no',
        'columns' => array(
            array('label' => 'Name', 'field'=>'name', 'class'=>''),
            array('label' => '', 'field'=>'buttons', 'class'=>'alignright'),
            ),
        'rows' => $festivals,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
