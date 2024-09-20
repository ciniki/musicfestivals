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
function ciniki_musicfestivals_ssamItemUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'category_name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'item_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Item'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'song1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 1'),
        'song2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 2'),
        'song3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 3'),
        'song4'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 4'),
        'song5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 5'),
        'song6'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 6'),
        'song7'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 7'),
        'song8'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 8'),
        'song9'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Song 9'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.ssamItemUpdate');
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
    $cid = -1;
    if( isset($ssam['sections']) ) {
        foreach($ssam['sections'] as $k => $section) {
            if( $section['name'] == $args['section_name'] ) {
                $sid = $k;
                if( isset($section['categories']) ) {
                    foreach($section['categories'] as $c => $category) {
                        if( $category['name'] == $args['category_name'] ) {
                            $cid = $c;
                            break;
                        }
                    }
                }
                break;
            }
        }
    }
    
    //
    // Check that section exists
    //
    if( $sid < 0 || $cid < 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.849', 'msg'=>'Section/Category does not exist'));
    }

    //
    // Check if a new section being added
    //
    if( $args['item_name'] == '' ) {
        if( !isset($args['name']) || $args['name'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.850', 'msg'=>'No item name specified'));
        } else {
            if( !isset($ssam['sections'][$sid]['categories'][$cid]['items']) ) {
                $ssam['sections'][$sid]['categories'][$cid]['items'] = [];
            }
            //
            // Make sure name doesn't exist
            //
            foreach($ssam['sections'][$sid]['categories'][$cid]['items'] as $iid => $item) {
                if( $item['name'] == $args['name'] ) {
                    return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.851', 'msg'=>'Movie/Show already exists'));
                }
            }
            $ssam['sections'][$sid]['categories'][$cid]['items'][] = [
                'name' => $args['name'],
                'song1' => isset($args['song1']) ? $args['song1'] : '',
                'song2' => isset($args['song2']) ? $args['song2'] : '',
                'song3' => isset($args['song3']) ? $args['song3'] : '',
                'song4' => isset($args['song4']) ? $args['song4'] : '',
                'song5' => isset($args['song5']) ? $args['song5'] : '',
                'song6' => isset($args['song6']) ? $args['song6'] : '',
                'song7' => isset($args['song7']) ? $args['song7'] : '',
                'song8' => isset($args['song8']) ? $args['song8'] : '',
                'song9' => isset($args['song9']) ? $args['song9'] : '',
                ];
        }
    } else {
        foreach($ssam['sections'][$sid]['categories'][$cid]['items'] as $iid => $item) {
            if( $item['name'] == $args['item_name'] ) {
                if( isset($args['name']) ) {
                    $ssam['sections'][$sid]['categories'][$cid]['items'][$iid]['name'] = $args['name'];
                }
                for($i = 1; $i <= 9; $i++) {
                    if( isset($args["song{$i}"]) ) {
                        $ssam['sections'][$sid]['categories'][$cid]['items'][$iid]["song{$i}"] = $args["song{$i}"];
                    }
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
