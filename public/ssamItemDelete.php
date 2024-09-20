<?php
//
// Description
// -----------
// Delete a category from SSAM chart
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_ssamItemDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Section'),
        'category_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Category'),
        'item_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Item'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.ssamItemDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current ssam content
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'ssamLoad');
    $rc = ciniki_musicfestivals_ssamLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ssam = $rc['ssam'];

    //
    // Find the category and remove it
    //
    foreach($ssam['sections'] as $sid => $section) {
        if( $section['name'] == $args['section_name'] ) {
            foreach($section['categories'] as $cid => $category) {
                if( $category['name'] == $args['category_name'] ) {
                    foreach($category['items'] as $iid => $item) {
                        if( $item['name'] == $args['item_name'] ) {
                            unset($ssam['sections'][$sid]['categories'][$cid]['items'][$iid]);
                            break;
                        }
                    }
                }
            }
        }
    }

    //
    // Load the current ssam content
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'ssamSave');
    $rc = ciniki_musicfestivals_ssamSave($ciniki, $args['tnid'], $args['festival_id'], $ssam);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
