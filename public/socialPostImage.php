<?php
//
// Description
// -----------
// This function will return the image binary data in jpg format.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the image from.
// image_id:            The ID if the image requested.
// version:             The version of the image (original, thumbnail)
//
//                      *note* the thumbnail is not referring to the size, but to a 
//                      square cropped version, designed for use as a thumbnail.
//                      This allows only a portion of the original image to be used
//                      for thumbnails, as some images are too complex for thumbnails.
//
// maxwidth:            The max width of the longest side should be.  This allows
//                      for generation of thumbnail's, etc.
//
// maxlength:           The max length of the longest side should be.  This allows
//                      for generation of thumbnail's, etc.
//
// Returns
// -------
// Binary image data
//
function ciniki_musicfestivals_socialPostImage($ciniki) {
    //
    // Check args
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'socialpost_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image'), 
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.socialPostImage');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Get the post details
    //
    if( $args['socialpost_id'] > 0 ) {
        $strsql = "SELECT posts.id, "
            . "posts.tnid, "
            . "posts.user_id, "
            . "posts.flags, "
            . "posts.image_id, "
            . "posts.content, "
            . "posts.notes "
            . "FROM ciniki_musicfestivals_socialposts AS posts "
            . "WHERE ("
                . "posts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "OR (posts.flags&0x01) = 0x01 "
                . ") "
            . "AND posts.id = '" . ciniki_core_dbQuote($ciniki, $args['socialpost_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'socialposts', 'fname'=>'id', 
                'fields'=>array('tnid', 'user_id', 'flags', 'image_id', 'content', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.615', 'msg'=>'Social Post not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['socialposts'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.616', 'msg'=>'Unable to find Social Post'));
        }
        $socialpost = $rc['socialposts'][0];

        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
        $rc = ciniki_images_loadCacheOriginal($ciniki, $socialpost['tnid'], $socialpost['image_id'], 0, 600);
    } elseif( isset($args['image_id']) && $args['image_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
        $rc = ciniki_images_loadCacheOriginal($ciniki, $args['tnid'], $args['image_id'], 0, 600);
    
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.617', 'msg'=>'No social post or image specified.', 'err'=>$rc['err']));
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $rc['last_updated']) . ' GMT', true, 200);
    if( isset($ciniki['request']['args']['attachment']) && $ciniki['request']['args']['attachment'] == 'yes' ) {
        header('Content-Disposition: attachment; filename="' . $rc['original_filename'] . '"');
    }
    if( isset($rc['type']) && $rc['type'] == 6 ) {
        header("Content-type: image/svg+xml"); 
    } else {
        header("Content-type: image/jpeg"); 
    }

    echo $rc['image'];
    return array('stat'=>'exit');
}
?>
