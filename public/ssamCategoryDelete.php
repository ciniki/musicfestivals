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
function ciniki_musicfestivals_ssamCategoryDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Section Name'),
        'category_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Category Name'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.ssamCategoryDelete');
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
        if( !isset($section['name']) ) {
            unset($ssam['sections'][$sid]);
        }
        if( isset($section['name']) && $section['name'] == $args['section_name'] ) {
            foreach($section['categories'] as $cid => $category) {
                if( !isset($category['name']) ) {
                    unset($ssam['sections'][$sid]['categories'][$cid]);
                }
                if( isset($category['name']) && $category['name'] == $args['category_name'] ) {
                    unset($ssam['sections'][$sid]['categories'][$cid]);
                    break;
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
