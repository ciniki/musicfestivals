<?php
//
// Description
// -----------
// This method accepts an image upload for the timeslot
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_timeslotImageDrop(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'timeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Timeslot'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.timeslotImageDrop');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.237', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

    //
    // Check for a uploaded file
    //
    if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.238', 'msg'=>'Upload failed, no file specified.'));
    }
    $uploaded_file = $_FILES['uploadfile']['tmp_name'];

    //
    // Add the image into the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'insertFromUpload');
    $rc = ciniki_images_insertFromUpload($ciniki, $args['tnid'], $ciniki['session']['user']['id'], 
        $_FILES['uploadfile'], 1, $_FILES['uploadfile']['name'], '', 'no');
    // If a duplicate image is found, then use that id instead of uploading a new one
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.images.66' ) {
    //    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.images');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.240', 'msg'=>'Internal Error', 'err'=>$rc['err']));
    }
    if( !isset($rc['id']) ) {
    //    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.images');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.241', 'msg'=>'Invalid file type'));
    }
    $args['image_id'] = $rc['id'];

    //
    // Set to visible on the website
    //
    $args['flags'] = 0x01;
    
    //
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.239', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        if( !isset($args['title']) || $args['title'] == '' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['uuid']);
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
        }
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, title, permalink "
        . "FROM ciniki_musicfestival_timeslot_images "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.ags', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.242', 'msg'=>'You already have a item image with that name, please choose another.'));
    }

    //
    // Get the next sequence
    //
    $args['sequence'] = 1;
    $strsql = "SELECT MAX(sequence) AS max_sequence "
        . "FROM ciniki_musicfestival_timeslot_images "
        . "WHERE timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
        . "AND ciniki_musicfestival_timeslot_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'max');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['max']['max_sequence']) ) {
        $args['sequence'] = $rc['max']['max_sequence'] + 1;
    }

    //
    // Add the image
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.timeslotimage', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.243', 'msg'=>'Unable to add the timeslotimage', 'err'=>$rc['err']));
    }
    $timeslot_image_id = $rc['id'];

    //
    // Create the thumbnail
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadThumbnail');
    $rc = ciniki_images_hooks_loadThumbnail($ciniki, $args['tnid'], array(
        'image_id' => $args['image_id'],
        'maxlength' => 50,
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.245', 'msg'=>'No thumbnail', 'err'=>$rc['err']));
    }
    $image_data = 'data:image/jpg;base64,' . base64_encode($rc['image']);


    return array('stat'=>'ok', 'id'=>$timeslot_image_id, 'image'=>$image_data);
}
?>
