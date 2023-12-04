<?php
//
// Description
// -----------
// This method will return the list of Social Posts for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Social Post for.
//
// Returns
// -------
//
function ciniki_musicfestivals_socialPostList($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.socialPostList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of socialposts
    //
    $strsql = "SELECT posts.id, "
        . "posts.tnid, "
        . "posts.user_id, "
        . "posts.flags, "
        . "posts.image_id "
        . "FROM ciniki_musicfestivals_socialposts AS posts "
        . "WHERE ("
            . "posts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "OR (posts.flags&0x01) = 0x01 "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'socialposts', 'fname'=>'id', 
            'fields'=>array('id', 'tnid', 'user_id', 'flags', 'image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $socialposts = isset($rc['socialposts']) ? $rc['socialposts'] : array();
    $socialpost_ids = array();
    foreach($socialposts as $iid => $socialpost) {
        $socialpost_ids[] = $socialpost['id'];
        if( isset($socialpost['image_id']) && $socialpost['image_id'] > 0 ) {
            $rc = ciniki_images_loadCacheThumbnail($ciniki, $socialpost['tnid'], $socialpost['image_id'], 200);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $socialposts[$iid]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
        }
    }

    return array('stat'=>'ok', 'socialposts'=>$socialposts, 'nplist'=>$socialpost_ids);
}
?>
