<?php
//
// Description
// ===========
// This method will return the list of divisions or timeslots for photos.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the festival is attached to.
// festival_id:          The ID of the festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_photos($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'division_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.photos');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load conference maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the additional settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $festival = $rc['festival'];

    $rsp = array('stat'=>'ok', 'divisions'=>array(), 'timeslots'=>array());

    //
    // Get the list of timeslots/photos
    //
    if( isset($args['division_id']) && $args['division_id'] > 0 ) {
        //
        // Load competitors to check if photos
        //
        if( isset($festival['waiver-photo-status']) && $festival['waiver-photo-status'] != 'no' ) {
            // Load list of no photos
            $strsql = "SELECT timeslots.id, competitors.id AS comp_id, competitors.flags, competitors.name "
                . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "(timeslots.id = registrations.timeslot_id "
                        . "OR timeslots.id = registrations.finals_timeslot_id "
                        . ") "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                    . "("
                        . "registrations.competitor1_id = competitors.id "
                        . "OR registrations.competitor2_id = competitors.id "
                        . "OR registrations.competitor3_id = competitors.id "
                        . "OR registrations.competitor4_id = competitors.id "
                        . "OR registrations.competitor5_id = competitors.id "
                        . ") "
                    . "AND (competitors.flags&0x02) = 0 "   // No photos
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' "
                . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'timeslots', 'fname'=>'id', 'fields'=>array('id')),
                array('container'=>'competitors', 'fname'=>'comp_id', 'fields'=>array('id'=>'comp_id', 'name', 'flags')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.901', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
            }
            $nophoto_timeslots = isset($rc['timeslots']) ? $rc['timeslots'] : array();
        }

        $strsql = "SELECT timeslots.id, "
            . "timeslots.festival_id, "
            . "timeslots.sdivision_id, "
            . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
            . "timeslots.name, "
            . "timeslots.description, "
            . "images.id AS timeslot_image_id, "
            . "images.image_id, "
            . "images.last_updated "
            . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
            . "LEFT JOIN ciniki_musicfestival_timeslot_images AS images ON ("
                . "timeslots.id = images.timeslot_id "
                . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' "
            . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "ORDER BY slot_time, images.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'scheduletimeslots', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'sdivision_id', 'slot_time_text', 
                    'name', 'description'),
                ),
            array('container'=>'images', 'fname'=>'image_id', 
                'fields'=>array('timeslot_image_id', 'image_id', 'last_updated'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['scheduletimeslots']) ) {
            $rsp['timeslots'] = $rc['scheduletimeslots'];
            foreach($rsp['timeslots'] as $tid => $scheduletimeslot) {
                $nophoto_names = '';
                if( isset($nophoto_timeslots[$scheduletimeslot['id']]) ) {
                    foreach($nophoto_timeslots[$scheduletimeslot['id']]['competitors'] as $competitor) {
                        $nophoto_names .= ($nophoto_names != '' ? ', ' : '') . $competitor['name'];
                    }
                }
                if( $nophoto_names != '' ) {
                    $rsp['timeslots'][$tid]['nophoto_names'] = $nophoto_names;
                }

                //
                // Create image thumbnails
                //
                if( isset($scheduletimeslot['images']) ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadThumbnail');
                    foreach($scheduletimeslot['images'] as $iid => $image) {
                        $rc = ciniki_images_hooks_loadThumbnail($ciniki, $args['tnid'], array(
                            'image_id' => $image['image_id'],
                            'maxlength' => 40,
                            'last_updated' => $image['last_updated'],
                            ));
                        if( $rc['stat'] != 'ok' ) {
//                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.698', 'msg'=>'No thumbnail', 'err'=>$rc['err']));
                        } else {
                            $rsp['timeslots'][$tid]['images'][$iid]['image'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                        }
                    }
                }
            }
        }
    }
    //
    // Get the list of divisions
    //
    else {
        $strsql = "SELECT divisions.id, "
            . "sections.name AS section_name, "
            . "divisions.name AS division_name, "
            . "DATE_FORMAT(divisions.division_date, '%b %D') AS division_date, "
            . "divisions.address "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY divisions.division_date, divisions.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'divisions', 'fname'=>'id', 
                'fields'=>array('id', 'section_name', 'division_name', 'division_date', 'address'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.81', 'msg'=>'Unable to load divisions', 'err'=>$rc['err']));
        }
        $rsp['divisions'] = isset($rc['divisions']) ? $rc['divisions'] : array();
    }

    return $rsp;
}
?>
