<?php
//
// Description
// ===========
// This function searches the exhibit items for sale.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_itemSearch($ciniki, $tnid, $args) {

    if( $args['start_needle'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.390', 'msg'=>'', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'ok', 'items'=>array());
    }
    $festival = $rc['festival'];

    //
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classKeywordsMake');
    $rc = ciniki_musicfestivals_classKeywordsMake($ciniki, $tnid, [
        'keywords' => $args['start_needle'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    //
    // Search classes by code or name
    //
    $strsql = "SELECT classes.id, "
        . "classes.code, "
        . "classes.name, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "categories.name AS category_name, "
        . "sections.name AS section_name "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND classes.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
/*        . "AND (classes.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.code LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR categories.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR categories.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR sections.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR sections.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") " */
        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.ags', array(
        array('container'=>'items', 'fname'=>'id',
            'fields'=>array('id', 'code', 'name', 'description'=>'name', 'earlybird_fee', 'fee', 'virtual_fee', 'section_name', 'category_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $items = array();
    if( isset($rc['items']) ) {
        foreach($rc['items'] as $item) {
            $item['flags'] = 0x28;
            if( ($festival['flags']&0x0100) == 0x0100 ) {
                $item['description'] = $item['section_name'] . ' - ' . $item['category_name'] . ' - ' . $item['name'];
            } else {
                $item['description'] = $item['name'];
            }
            $item['object'] = 'ciniki.musicfestivals.class';
            $item['object_id'] = $item['id'];
            $item['price_id'] = 0;
            $item['quantity'] = 1;
            $item['taxtype_id'] = 0;
            $item['notes'] = '';
            $item['unit_amount'] = $item['fee'];
            $item['unit_discount_amount'] = 0;
            $item['unit_discount_percentage'] = 0;
            if( $festival['earlybird'] == 'yes' && $item['earlybird_fee'] > 0 ) {
                $item['unit_amount'] = $item['earlybird_fee'];
            }
            if( isset($festival['virtual']) && $festival['virtual'] == 'yes' && $item['fee'] == 0 ) {
                $item['unit_amount'] = $item['virtual_fee'];
            }
            if( ($festival['flags']&0x04) == 0x04 ) {   
                $virtual = $item;
//                $item['description'] .= ' (Live)';
                $items[] = array('item'=>$item);
//                if( $item['virtual_fee'] > 0 ) {
//                    $virtual['description'] .= ' (Virtual)';
//                    $virtual['unit_amount'] = $item['virtual_fee'];
//                    $items[] = array('item'=>$virtual);
//                }
            } else {
                $items[] = array('item'=>$item);
            }
        }
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>
