<?php
//
// Description
// -----------
// This function will process the request for a list.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_listProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.317', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.318', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a category was specified
    //
    if( !isset($s['list-id']) || $s['list-id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.319', 'msg'=>"No list specified"));
    }

    //
    // Get the list details
    //
    $strsql = "SELECT lists.id, "   
        . "lists.name, "
        . "lists.intro "
        . "FROM ciniki_musicfestival_lists AS lists "
        . "WHERE lists.id = '" . ciniki_core_dbQuote($ciniki, $s['list-id']) . "' "
        . "AND lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'list');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.283', 'msg'=>'Unable to load list', 'err'=>$rc['err']));
    }
    if( !isset($rc['list']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.284', 'msg'=>'Unable to find requested list'));
    }
    $list = $rc['list'];
   
    $blocks[] = array(
        'type' => 'title',
        'title' => $list['name'],
        );
    if( isset($list['intro']) && $list['intro'] != '' ) {
        $blocks[] = array(
            'type' => 'text',
            'content' => $list['intro'],
            );
    }

    //
    // Get the entries
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "entries.id AS entry_id, "
        . "entries.award, "
        . "entries.amount, "
        . "entries.donor, "
        . "entries.winner "
        . "FROM ciniki_musicfestival_list_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_list_entries AS entries ON ("
            . "sections.id = entries.section_id "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.list_id = '" . ciniki_core_dbQuote($ciniki, $s['list-id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sections.sequence, entries.sequence, entries.award "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('name'=>'section_name')),
        array('container'=>'entries', 'fname'=>'entry_id', 
            'fields'=>array('award', 'amount', 'donor', 'winner'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.282', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    $columns = array(
        array('label' => 'Award', 'field' => 'award', 'class' => ''),
        );
    $columns[] = array('label' => 'Winner', 'fold-label' => 'Winner:', 'field' => 'winner', 'class' => '');
    if( isset($s['amount-visible']) && $s['amount-visible'] == 'yes' ) {
        $columns[] = array('label' => 'Amount', 'fold-label' => 'Amount:', 'field' => 'amount', 'class' => 'alignright');
    }
    if( isset($s['donor-visible']) && $s['donor-visible'] == 'yes' ) {
        $columns[] = array('label' => 'Donor', 'fold-label' => 'Donor:', 'field' => 'donor', 'class' => '');
    }
    foreach($sections as $section) {
        $blocks[] = array(
            'type' => 'table', 
            'title' => $section['name'],
            'class' => 'fold-at-50 ciniki-musicfestival-list',
            'headers' => 'yes',
            'columns' => $columns,
            'rows' => $section['entries'],
            );
    }

    return array('stat'=>'ok', 'stop'=>'yes', 'clear'=>'yes', 'blocks'=>$blocks);
}
?>
