<?php
//
// Description
// -----------
// This method returns the recommendation entries in excel format.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_titleListExcel(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'list_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'List'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleListExcel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Get the list
    //
    $strsql = "SELECT id, "
        . "name, "
        . "flags, "
        . "col1_field, "
        . "col1_label, "
        . "col2_field, "
        . "col2_label, "
        . "col3_field, "
        . "col3_label, "
        . "col4_field, "
        . "col4_label "
        . "FROM ciniki_musicfestivals_titlelists "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'list');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1185', 'msg'=>'Unable to load list', 'err'=>$rc['err']));
    }
    if( !isset($rc['list']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1186', 'msg'=>'Unable to find requested list'));
    }
    $list = $rc['list'];
    $order_sql = '';
    $columns = [];
    for($i = 1; $i < 5; $i++ ) {
        if( $list["col{$i}_field"] != 'none' && $list["col{$i}_field"] != '' ) {
            $order_sql .= ($order_sql != '' ? ', ' : '') . $list["col{$i}_field"];
            $columns[] = [
                'label' => $list["col{$i}_label"],
                'field' => $list["col{$i}_field"],
                ];
        }
    }
    if( $order_sql == '' ) {
        $order_sql = "ORDER BY title, movements, composer ";
    } else {
        $order_sql = "ORDER BY " . $order_sql;
    }
    $strsql = "SELECT id, "
        . "title, "
        . "movements, "
        . "composer, "
        . "source_type "
        . "FROM ciniki_musicfestivals_titles "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND list_id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
        . $order_sql
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'titles', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'movements', 'composer', 'source_type')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $titles = isset($rc['titles']) ? $rc['titles'] : array();

    $sheets = [
        'titles' => [
            'label' => $list['name'],
            'columns' => $columns,
            'rows' => $titles,
            ],
        ];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'excelGenerate');
    return ciniki_core_excelGenerate($ciniki, $args['tnid'], [
        'sheets' => $sheets,
        'download' => 'yes',
        'filename' => $list['name'] . '.xlsx'
        ]);
}
?>
