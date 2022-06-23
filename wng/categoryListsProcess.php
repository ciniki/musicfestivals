<?php
//
// Description
// -----------
// This function will process the request for lists for a category.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_categoryListsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.278', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.279', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a category was specified
    //
    if( !isset($s['category']) || $s['category'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.280', 'msg'=>"No lists category specified"));
    }

    //
    // Check if list specified
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        ) {
        $section['settings']['list-id'] = $request['uri_split'][($request['cur_uri_pos']+1)];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'listProcess');
        return ciniki_musicfestivals_wng_listProcess($ciniki, $tnid, $request, $section); 
    }

    //
    // Show the list of lists
    //
    $strsql = "SELECT lists.id, "
        . "festivals.id AS festival_id, "
        . "lists.name AS text "
        . "FROM ciniki_musicfestival_lists AS lists "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "lists.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND lists.category = '" . ciniki_core_dbQuote($ciniki, $s['category']) . "' "
        . "ORDER BY festivals.start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'lists', 'fname'=>'id', 'fields'=>array('id', 'text')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.281', 'msg'=>'Unable to load lists', 'err'=>$rc['err']));
    }
    $lists = isset($rc['lists']) ? $rc['lists'] : array();

    foreach($lists as $lid => $list) {
        $lists[$lid]['url'] = $request['page']['path'] . '/' . $list['id'];
    }

    if( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title', 
            'class' => 'musicfestival-lists',
            'title' => $s['title'],
            );
    }

    $blocks[] = array(
        'type' => 'buttons',
        'class' => 'musicfestival-lists',
        'list' => $lists,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
