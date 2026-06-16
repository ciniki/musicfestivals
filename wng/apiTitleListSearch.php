<?php
//
// Description
// -----------
// Search the approved titles
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_apiTitleListSearch(&$ciniki, $tnid, $request) {
   
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
   
    if( !isset($request['args']['search_string']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1654', 'msg'=>'No search string specified'));
    }
    if( !isset($request['args']['list-ids']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1654', 'msg'=>'No lists specified'));
    }

    $list_ids = [];
    $ids = explode(',', preg_replace("/[^0-9,]/", '', $request['args']['list-ids']));
    foreach($ids as $id) {
        if( is_numeric($id) && $id > 0 ) {
            $list_ids[] = $id;
        }
    }

    //
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');
    $rc = ciniki_musicfestivals_titleListKeywordsMake($ciniki, $tnid, [
        'keywords' => $request['args']['search_string'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $request['args']['search_string']);
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    $limit = 50;

    //
    // search the titles
    //
    if( $keywords != '' && count($list_ids) > 0 ) {
        $strsql = "SELECT lists.id, "
            . "lists.name, "
            . "lists.permalink, "
            . "lists.flags, "
            . "lists.col1_field, "
            . "lists.col1_label, "
            . "lists.col2_field, "
            . "lists.col2_label, "
            . "lists.col3_field, "
            . "lists.col3_label, "
            . "lists.col4_field, "
            . "lists.col4_label "
            . "FROM ciniki_musicfestivals_titlelists AS lists "
            . "WHERE ID IN (" . ciniki_core_dbQuoteIDs($ciniki, $list_ids) . ") "
            . "AND ("
                . "lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "OR (lists.flags&0x01) = 0x01 "
                . ") ";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'lists', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'permalink', 'flags', 'col1_field', 'col1_label', 
                    'col2_field', 'col2_label', 'col3_field', 'col3_label', 'col4_field', 'col4_label'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1656', 'msg'=>'Unable to load lists', 'err'=>$rc['err']));
        }
        $lists = isset($rc['lists']) ? $rc['lists'] : array();
        $list_ids = [];
        foreach($rc['lists'] as $list) {
            $list_ids[] = $list['id'];
        }

        $strsql = "SELECT titles.id, "
            . "titles.list_id, "
            . "titles.title, "
            . "titles.movements, "
            . "titles.composer, "
            . "titles.source_type "
            . "FROM ciniki_musicfestivals_titles AS titles "
            . "WHERE titles.list_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $list_ids) . ") "
            . "AND titles.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
            . "ORDER BY source_type, title, movements, composer "
            . "LIMIT " . ($limit + 1)
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'titles', 'fname'=>'id', 
                'fields'=>array('id', 'list_id', 'title', 'movements', 'composer', 'source_type'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $titles = isset($rc['titles']) ? $rc['titles'] : array();
    } else {
        $titles = [];
    }

    if( count($titles) > 0 ) {
        foreach($titles as $tid => $title) {
            $titles[$tid]['list'] = $lists[$title['list_id']]['name'];
        }
        $columns = [['label' => 'Discipline', 'field'=>'list']];
        for($j = 1; $j < 5; $j++) {
            if( in_array($list["col{$j}_field"], ['title', 'movements', 'composer', 'source_type']) ) {
                $columns[] = [
                    'label' => $list["col{$j}_label"],
                    'field' => $list["col{$j}_field"],
                    ];
            }
        }
        $blocks[] = [
            'type' => 'table',
            'id' => $list['permalink'],
            'class' => 'musicfestivals-approved-titles',
            'headers' => 'yes',
            'columns' => $columns,
            'rows' => $titles,
            ];
        if( count($titles) > $limit ) {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'warning',
                'content' => 'Too many results, please add more keywords to your search',
                ];
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'blocksGenerate');
        return ciniki_wng_blocksGenerate($ciniki, $tnid, $request, $blocks);
        
    } elseif( $request['args']['search_string'] != '' && $keywords == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'warning', 
            'content' => 'Keep typing...',
            ]);
    } elseif( $request['args']['search_string'] == '' ) {
        return array('stat'=>'ok', 'content'=>'');
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'error', 
            'content' => 'Nothing found',
            ]);
    }

    return array('stat'=>'ok', 'content'=>'');
}
?>
