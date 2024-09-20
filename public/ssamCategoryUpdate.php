<?php
//
// Description
// -----------
// Update a category from SSAM chart
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_ssamCategoryUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'category_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Category'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.ssamCategoryUpdate');
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

    $sid = -1;
    if( isset($ssam['sections']) ) {
        foreach($ssam['sections'] as $k => $section) {
            if( $section['name'] == $args['section_name'] ) {
                $sid = $k;
                break;
            }
        }
    }
    
    //
    // Check that section exists
    //
    if( $sid < 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.848', 'msg'=>'Section does not exist'));
    }

    //
    // Check if a new section being added
    //
    if( $args['category_name'] == '' ) {
        if( !isset($args['name']) || $args['name'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.847', 'msg'=>'No category name specified'));
        } else {
            if( !isset($ssam['sections'][$sid]['categories']) ) {
                $ssam['sections'][$sid]['categories'] = [];
            }
            // 
            // Make sure the category doesn't exist
            //
            foreach($ssam['sections'][$sid]['categories'] as $cid => $category) {
                if( $category['name'] == $args['name'] ) {
                    return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.852', 'msg'=>'Category already exists'));
                }
            }
            $ssam['sections'][$sid]['categories'][] = [
                'name' => $args['name'],
                'items' => [],
                ];
        }
    } else {
        foreach($ssam['sections'][$sid]['categories'] as $cid => $category) {
            if( $category['name'] == $args['category_name'] ) {
                if( isset($args['name']) ) {
                    $ssam['sections'][$sid]['categories'][$cid]['name'] = $args['name'];
                }
                break;
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
