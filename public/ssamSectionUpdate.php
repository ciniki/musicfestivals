<?php
//
// Description
// -----------
// Update a section from SSAM chart
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_ssamSectionUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_name'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Old Name'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'New Name'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'moveto_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Move Section'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.ssamSectionGet');
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
    // Check if a new section being added
    //
    if( $args['section_name'] == '' ) {
        if( !isset($args['name']) || $args['name'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.844', 'msg'=>'No section name specified'));
        } else {
            $ssam['sections'][] = [
                'name' => $args['name'],
                'content' => (isset($args['content']) ? $args['content'] : ''),
                'categories' => [],
                ];
        }
    } else {
        foreach($ssam['sections'] as $sid => $section) {
            if( isset($args['moveto_name']) && $args['moveto_name'] == $section['name'] ) {
                $to = $sid;
            }
            if( isset($section['name']) && $section['name'] == $args['section_name'] ) {
                if( isset($args['name']) ) {
                    $ssam['sections'][$sid]['name'] = $args['name'];
                }
                if( isset($args['content']) ) {
                    $ssam['sections'][$sid]['content'] = $args['content'];
                }
                if( isset($args['name']) || isset($args['content']) ) {
                    break;
                }
                if( isset($args['moveto_name']) ) {
                    $from = $sid;
                }
            }
        }
        if( isset($to) && isset($from) ) {
            $item = $ssam['sections'][$from];
            unset($ssam['sections'][$from]);
            if( $to == 0 ) {
                array_unshift($ssam['sections'], $item);
            } else {
                array_splice($ssam['sections'], $to, 0, array('0'=>$item));
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
