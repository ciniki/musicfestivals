<?php
//
// Description
// ===========
// This method will return all the information about an schedule time slot image.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule time slot image is attached to.
// timeslot_image_id:          The ID of the schedule time slot image to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_timeslotImageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'timeslot_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot Image'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.timeslotImageGet');
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
    // Return default for new Schedule Time Slot Image
    //
    if( $args['timeslot_image_id'] == 0 ) {
        $image = array('id'=>0,
            'timeslot_id'=>'',
            'title'=>'',
            'permalink'=>'',
            'flags'=>'0',
            'sequence'=>'1',
            'image_id'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Time Slot Image
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_timeslot_images.id, "
            . "ciniki_musicfestival_timeslot_images.timeslot_id, "
            . "ciniki_musicfestival_timeslot_images.title, "
            . "ciniki_musicfestival_timeslot_images.permalink, "
            . "ciniki_musicfestival_timeslot_images.flags, "
            . "ciniki_musicfestival_timeslot_images.sequence, "
            . "ciniki_musicfestival_timeslot_images.image_id, "
            . "ciniki_musicfestival_timeslot_images.description "
            . "FROM ciniki_musicfestival_timeslot_images "
            . "WHERE ciniki_musicfestival_timeslot_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_timeslot_images.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_image_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('timeslot_id', 'title', 'permalink', 'flags', 'sequence', 'image_id', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.234', 'msg'=>'Schedule Time Slot Image not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['images'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.235', 'msg'=>'Unable to find Schedule Time Slot Image'));
        }
        $image = $rc['images'][0];
    }

    return array('stat'=>'ok', 'image'=>$image);
}
?>
