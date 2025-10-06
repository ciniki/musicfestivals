<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_approvedTitlesProcess(&$ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1182', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1183', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = [
            'type' => 'text',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : '',
            'content' => $s['content'],
            ];
    } elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = [
            'type' => 'title',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => $s['title'],
            ];
    }

    $list_ids = [];
    for($i = 1; $i < 100; $i++) {
        if( isset($s["list-id-{$i}"]) && $s["list-id-{$i}"] != '' && $s["list-id-{$i}"] > 0 ) {
            $list_ids[] = $s["list-id-{$i}"];
        }
    }

    if( count($list_ids) == 0 ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error',
            'content' => 'No titles found',
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Get the lists and titles 
    //
    $strsql = "SELECT lists.id, "
        . "lists.name, "
        . "lists.flags, "
        . "lists.description, "
        . "lists.col1_field, "
        . "lists.col1_label, "
        . "lists.col2_field, "
        . "lists.col2_label, "
        . "lists.col3_field, "
        . "lists.col3_label, "
        . "lists.col4_field, "
        . "lists.col4_label, "
        . "titles.id AS title_id, "
        . "titles.title, "
        . "titles.movements, "
        . "titles.composer, "
        . "titles.source_type "
        . "FROM ciniki_musicfestivals_titlelists AS lists "
        . "INNER JOIN ciniki_musicfestivals_titles AS titles ON ("
            . "lists.id = titles.list_id "
            . "AND titles.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE list_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $list_ids) . ") "
        . "AND ("
            . "lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "OR (lists.flags&0x01) = 0x01 "
            . ") "
        . "ORDER BY lists.id, title, movements, composer "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'lists', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'description',
                'col1_field', 'col1_label',
                'col2_field', 'col2_label',
                'col3_field', 'col3_label',
                'col4_field', 'col4_label',
                ),
            ),
        array('container'=>'titles', 'fname'=>'title_id', 
            'fields'=>array('id'=>'title_id', 'title', 'movements', 'composer', 'source_type'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $lists = isset($rc['lists']) ? $rc['lists'] : array();

    if( count($lists) == 0 ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error',
            'content' => 'No titles found',
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    if( !isset($s['search']) || $s['search'] == 'yes' ) {
        $blocks[] = [
            'type' => 'tablefilter',
            'selector' => '.musicfestivals-approved-titles tbody > tr',
            ];
    }

    foreach($lists as $list) {
        $columns = [];
        for($i = 1; $i < 5; $i++) {
            if( in_array($list["col{$i}_field"], ['title', 'movements', 'composer', 'source_type']) ) {
                $columns[] = [
                    'label' => $list["col{$i}_label"],
                    'field' => $list["col{$i}_field"],
                    ];
            }
        }
        if( count($columns) > 0 ) {
            // Sort based on columns specified
            uasort($list['titles'], function($a, $b) use ($list) {
                for($i = 1; $i < 5; $i++) {
                    if( in_array($list["col{$i}_field"], ['title', 'movements', 'composer', 'source_type']) ) {
                        if( $a[$list["col{$i}_field"]] != $b[$list["col{$i}_field"]] ) {
                            return strnatcasecmp($a[$list["col{$i}_field"]], $b[$list["col{$i}_field"]]);
                        }
                    }
                }
                });
            $blocks[] = [
                'type' => 'table',
                'class' => 'musicfestivals-approved-titles',
                'title' => $list['name'],
                'content' => $list['description'],
                'headers' => 'yes',
                'columns' => $columns,
                'rows' => $list['titles'],
                ];
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
