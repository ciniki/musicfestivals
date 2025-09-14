<?php
//
// Description
// ===========
// This method will return all the information about an registration.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the registration is attached to.
// registration_id:          The ID of the registration to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        'cr_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Change Request'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
    // Return default for new Registration
    //
    if( $args['registration_id'] == 0 ) {
        $registration = array('id'=>0,
            'festival_id'=>$args['festival_id'],
            'teacher_customer_id'=>'0',
            'teacher2_customer_id'=>'0',
            'billing_customer_id'=>'0',
            'parent_customer_id'=>'0',
            'accompanist_customer_id'=>'0',
            'member_id'=>'0',
            'rtype'=>30,
            'status'=>'',
            'flags'=>0,
            'invoice_id'=>'0',
            'display_name'=>'',
            'competitor1_id'=>'0',
            'competitor2_id'=>'0',
            'competitor3_id'=>'0',
            'competitor4_id'=>'0',
            'competitor5_id'=>'0',
            'class_id'=>(isset($args['class_id']) ? $args['class_id'] : 0),
            'title1'=>'',
            'composer1'=>'',
            'movements1'=>'',
            'perf_time1'=>'',
            'title2'=>'',
            'composer2'=>'',
            'movements2'=>'',
            'perf_time2'=>'',
            'title3'=>'',
            'composer3'=>'',
            'movements3'=>'',
            'perf_time3'=>'',
            'title4'=>'',
            'composer4'=>'',
            'movements4'=>'',
            'perf_time4'=>'',
            'title5'=>'',
            'composer5'=>'',
            'movements5'=>'',
            'perf_time5'=>'',
            'title6'=>'',
            'composer6'=>'',
            'movements6'=>'',
            'perf_time6'=>'',
            'title7'=>'',
            'composer7'=>'',
            'movements7'=>'',
            'perf_time7'=>'',
            'title8'=>'',
            'composer8'=>'',
            'movements8'=>'',
            'perf_time8'=>'',
            'fee'=>'0',
            'participation'=>0,
            'scheduled'=>'',
            'video_url1'=>'',
            'video_url2'=>'',
            'video_url3'=>'',
            'video_url4'=>'',
            'video_url5'=>'',
            'video_url6'=>'',
            'video_url7'=>'',
            'video_url8'=>'',
            'music_orgfilename1'=>'',
            'music_orgfilename2'=>'',
            'music_orgfilename3'=>'',
            'music_orgfilename4'=>'',
            'music_orgfilename5'=>'',
            'music_orgfilename6'=>'',
            'music_orgfilename7'=>'',
            'music_orgfilename8'=>'',
            'backtrack1'=>'',
            'backtrack2'=>'',
            'backtrack3'=>'',
            'backtrack4'=>'',
            'backtrack5'=>'',
            'backtrack6'=>'',
            'backtrack7'=>'',
            'backtrack8'=>'',
            'artwork1'=>'',
            'artwork2'=>'',
            'artwork3'=>'',
            'artwork4'=>'',
            'artwork5'=>'',
            'artwork6'=>'',
            'artwork7'=>'',
            'artwork8'=>'',
            'instrument' => '',
            'mark' => '',
            'placement' => '',
            'level' => '',
            'finals_mark' => '',
            'finals_placement' => '',
            'finals_level' => '',
            'provincials_code' => '',
            'provincials_status' => 0,
            'provincials_position' => '',
            'provincials_invite_date' => '',
            'provincials_notes' => '',
            'comments' => '',
            'notes'=>'',
            'internal_notes'=>'',
            'runsheet_notes'=>'',
        );
    }

    //
    // Get the details for an existing Registration
    //
    else {
        $strsql = "SELECT registrations.id, "
            . "registrations.festival_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.teacher2_customer_id, "
            . "registrations.billing_customer_id, "
            . "registrations.parent_customer_id, "
            . "registrations.accompanist_customer_id, "
            . "registrations.member_id, "
            . "registrations.rtype, "
            . "registrations.status, "
            . "registrations.flags, "
            . "registrations.invoice_id, "
            . "registrations.display_name, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id, "
            . "registrations.class_id, "
            . "registrations.title1, "
            . "registrations.composer1, "
            . "registrations.movements1, "
            . "registrations.perf_time1, "
            . "registrations.title2, "
            . "registrations.composer2, "
            . "registrations.movements2, "
            . "registrations.perf_time2, "
            . "registrations.title3, "
            . "registrations.composer3, "
            . "registrations.movements3, "
            . "registrations.perf_time3, "
            . "registrations.title4, "
            . "registrations.composer4, "
            . "registrations.movements4, "
            . "registrations.perf_time4, "
            . "registrations.title5, "
            . "registrations.composer5, "
            . "registrations.movements5, "
            . "registrations.perf_time5, "
            . "registrations.title6, "
            . "registrations.composer6, "
            . "registrations.movements6, "
            . "registrations.perf_time6, "
            . "registrations.title7, "
            . "registrations.composer7, "
            . "registrations.movements7, "
            . "registrations.perf_time7, "
            . "registrations.title8, "
            . "registrations.composer8, "
            . "registrations.movements8, "
            . "registrations.perf_time8, "
            . "registrations.fee, "
            . "registrations.timeslot_id, "
            . "registrations.finals_timeslot_id, ";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
            $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time, ";
            $strsql .= "TIME_FORMAT(registrations.finals_timeslot_time, '%l:%i %p') AS finals_slot_time, ";
        } else {
            $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time, ";
            $strsql .= "TIME_FORMAT(finals_timeslots.slot_time, '%l:%i %p') AS finals_slot_time, ";
        }
        $strsql .= "IFNULL(divisions.name, '') AS division_name, "
            . "IFNULL(finals_divisions.name, '') AS finals_division_name, "
            . "IFNULL(DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y'), '') AS division_date, "
            . "IFNULL(DATE_FORMAT(finals_divisions.division_date, '%W, %M %D, %Y'), '') AS finals_division_date, "
            . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name, "
            . "IFNULL(IF(finals_locations.shortname <> '', finals_locations.shortname, finals_locations.name), '') AS finals_location_name, "
            . "IFNULL(ssections.name, '') AS section_name, "
            . "IFNULL(finals_ssections.name, '') AS finals_section_name, "
            . "registrations.participation, "
            . "registrations.video_url1, "
            . "registrations.video_url2, "
            . "registrations.video_url3, "
            . "registrations.video_url4, "
            . "registrations.video_url5, "
            . "registrations.video_url6, "
            . "registrations.video_url7, "
            . "registrations.video_url8, "
            . "registrations.music_orgfilename1, "
            . "registrations.music_orgfilename2, "
            . "registrations.music_orgfilename3, "
            . "registrations.music_orgfilename4, "
            . "registrations.music_orgfilename5, "
            . "registrations.music_orgfilename6, "
            . "registrations.music_orgfilename7, "
            . "registrations.music_orgfilename8, "
            . "registrations.backtrack1, "
            . "registrations.backtrack2, "
            . "registrations.backtrack3, "
            . "registrations.backtrack4, "
            . "registrations.backtrack5, "
            . "registrations.backtrack6, "
            . "registrations.backtrack7, "
            . "registrations.backtrack8, "
            . "registrations.artwork1, "
            . "registrations.artwork2, "
            . "registrations.artwork3, "
            . "registrations.artwork4, "
            . "registrations.artwork5, "
            . "registrations.artwork6, "
            . "registrations.artwork7, "
            . "registrations.artwork8, "
            . "registrations.instrument, "
            . "registrations.mark, "
            . "registrations.placement, "
            . "registrations.level, "
            . "registrations.finals_mark, "
            . "registrations.finals_placement, "
            . "registrations.finals_level, "
            . "registrations.provincials_code, "
            . "registrations.provincials_status, "
            . "registrations.provincials_position, "
            . "DATE_FORMAT(registrations.provincials_invite_date, '%b %e, %Y') AS provincials_invite_date, "
            . "registrations.provincials_notes, "
            . "registrations.comments, "
            . "registrations.notes, "
            . "registrations.internal_notes, "
            . "registrations.runsheet_notes "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "registrations.timeslot_id = timeslots.id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "timeslots.sdivision_id = divisions.id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                . "divisions.location_id = locations.id "
                . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS finals_timeslots ON ("
                . "registrations.finals_timeslot_id = finals_timeslots.id "
                . "AND finals_timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                . "divisions.ssection_id = ssections.id "
                . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS finals_divisions ON ("
                . "finals_timeslots.sdivision_id = finals_divisions.id "
                . "AND finals_divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_locations AS finals_locations ON ("
                . "finals_divisions.location_id = finals_locations.id "
                . "AND finals_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_sections AS finals_ssections ON ("
                . "finals_divisions.ssection_id = finals_ssections.id "
                . "AND finals_ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'teacher2_customer_id',
                    'billing_customer_id', 'parent_customer_id',
                    'accompanist_customer_id', 'member_id',
                    'slot_time', 'division_date', 'division_name', 'location_name', 'section_name',
                    'finals_slot_time', 'finals_division_date', 'finals_division_name', 'finals_location_name', 'finals_section_name',
                    'rtype', 'status', 'flags', 'invoice_id', 'display_name', 
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id', 
                    'class_id', 
                    'title1', 'composer1', 'movements1', 'perf_time1', 
                    'title2', 'composer2', 'movements2', 'perf_time2', 
                    'title3', 'composer3', 'movements3', 'perf_time3', 
                    'title4', 'composer4', 'movements4', 'perf_time4', 
                    'title5', 'composer5', 'movements5', 'perf_time5', 
                    'title6', 'composer6', 'movements6', 'perf_time6', 
                    'title7', 'composer7', 'movements7', 'perf_time7', 
                    'title8', 'composer8', 'movements8', 'perf_time8', 
                    'fee',
                    'participation', 
                    'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                    'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3',  'music_orgfilename4', 
                    'music_orgfilename5', 'music_orgfilename6',  'music_orgfilename7', 'music_orgfilename8',  
                    'backtrack1', 'backtrack2', 'backtrack3',  'backtrack4', 
                    'backtrack5', 'backtrack6',  'backtrack7', 'backtrack8',  
                    'artwork1', 'artwork2', 'artwork3',  'artwork4', 
                    'artwork5', 'artwork6',  'artwork7', 'artwork8',  
                    'timeslot_id', 'finals_timeslot_id', 
                    'instrument', 'mark', 'placement', 'level', 'comments', 
                    'provincials_code', 'provincials_status', 'provincials_position', 'provincials_invite_date', 'provincials_notes',
                    'finals_mark', 'finals_placement', 'finals_level',
                    'notes', 'internal_notes', 'runsheet_notes',
                    ),
                'naprices' => array('fee'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.73', 'msg'=>'Registration not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['registrations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.74', 'msg'=>'Unable to find Registration'));
        }
        $registration = $rc['registrations'][0];

        //
        // Load the festival
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $registration['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $festival = $rc['festival'];

        $registration['scheduled'] = '';
        $registration['scheduled_sd'] = '';
        if( $registration['division_date'] != '' ) {
            $registration['scheduled'] .= $registration['division_date'];
        }
        if( $registration['slot_time'] != '' && $registration['slot_time'] != '12:00 AM' ) {
            $registration['scheduled'] .= ($registration['scheduled'] != '' ? ' - ' : '') . $registration['slot_time'];
        }
        if( $registration['location_name'] != '' ) {
            $registration['scheduled'] .= ($registration['scheduled'] != '' ? ' - ' : '') . $registration['location_name'];
        }
        if( $registration['section_name'] != '' ) {
            $registration['scheduled_sd'] .= ($registration['scheduled_sd'] != '' ? "" : '') . $registration['section_name'];
        }
        if( $registration['division_name'] != '' ) {
            $registration['scheduled_sd'] .= ($registration['scheduled_sd'] != '' ? ' - ' : '') . $registration['division_name'];
        }

        //
        // Get the teacher details
        //
        if( isset($registration['teacher_customer_id']) && $registration['teacher_customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
                array('customer_id'=>$registration['teacher_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['teacher_details'] = $rc['details'];
        } else {
            $registration['teacher_details'] = array();
        }

        //
        // Get the teacher 2 details
        //
        if( isset($registration['teacher2_customer_id']) && $registration['teacher2_customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
                array('customer_id'=>$registration['teacher2_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['teacher2_details'] = $rc['details'];
        } else {
            $registration['teacher2_details'] = array();
        }

        //
        // Get the parent details
        //
        if( isset($registration['parent_customer_id']) && $registration['parent_customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
                array('customer_id'=>$registration['parent_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['parent_details'] = $rc['details'];
        } else {
            $registration['parent_details'] = array();
        }

        //
        // Get the member festival
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) && $registration['member_id'] > 0 ) {
            $strsql = "SELECT members.name "
                . "FROM ciniki_musicfestivals_members AS members "
                . "WHERE members.id = '" . ciniki_core_dbQuote($ciniki, $registration['member_id']) . "' "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'member');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.654', 'msg'=>'Unable to load member', 'err'=>$rc['err']));
            }
            if( isset($rc['member']) ) {
                $registration['member_details'] = array(
                    array('label'=>'Name', 'value'=>$rc['member']['name']),
                    );
            } else {
                $registration['member_details'] = array();
            }
        }
       
        //
        // Get the Accompanist details
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) 
            && isset($registration['accompanist_customer_id']) && $registration['accompanist_customer_id'] > 0 
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
                array('customer_id'=>$registration['accompanist_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['accompanist_details'] = $rc['details'];
        } else {
            $registration['accompanist_details'] = array();
        }
       
        //
        // Get the competitor details
        //
        for($i = 1; $i <= 5; $i++) {
            if( $registration['competitor' . $i . '_id'] > 0 ) {
                $strsql = "SELECT ciniki_musicfestival_competitors.id, "
                    . "ciniki_musicfestival_competitors.festival_id, "
                    . "ciniki_musicfestival_competitors.ctype, "
                    . "ciniki_musicfestival_competitors.flags, "
                    . "ciniki_musicfestival_competitors.name, "
                    . "ciniki_musicfestival_competitors.pronoun, "
                    . "ciniki_musicfestival_competitors.conductor, "
                    . "ciniki_musicfestival_competitors.parent, "
                    . "ciniki_musicfestival_competitors.address, "
                    . "ciniki_musicfestival_competitors.city, "
                    . "ciniki_musicfestival_competitors.province, "
                    . "ciniki_musicfestival_competitors.postal, "
                    . "ciniki_musicfestival_competitors.phone_home, "
                    . "ciniki_musicfestival_competitors.phone_cell, "
                    . "ciniki_musicfestival_competitors.email, "
                    . "ciniki_musicfestival_competitors.age AS _age, "
                    . "ciniki_musicfestival_competitors.num_people, "
                    . "ciniki_musicfestival_competitors.study_level, "
                    . "ciniki_musicfestival_competitors.instrument, "
                    . "ciniki_musicfestival_competitors.notes "
                    . "FROM ciniki_musicfestival_competitors "
                    . "WHERE ciniki_musicfestival_competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND ciniki_musicfestival_competitors.id = '" . ciniki_core_dbQuote($ciniki, $registration['competitor' . $i . '_id']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'competitors', 'fname'=>'id', 
                        'fields'=>array('festival_id', 'ctype', 'flags', 'name', 'pronoun', 'conductor', 'parent', 
                            'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 
                            'email', '_age', 'num_people', 'study_level', 'instrument', 'notes'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.68', 'msg'=>'Competitor not found', 'err'=>$rc['err']));
                }
                if( !isset($rc['competitors'][0]) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.69', 'msg'=>'Unable to find Competitor'));
                }
                $competitor = $rc['competitors'][0];
                $competitor['age'] = $competitor['_age'];
                $details = array();
                $name = $competitor['name'];
                if( isset($festival['waiver-name-status']) && $festival['waiver-name-status'] != 'off' 
                    && ($competitor['flags']&0x04) == 0
                    ) {
                    $name .= '<br/><b>NAME WITHHELD</b>';
                }
                if( isset($festival['waiver-photo-status']) && $festival['waiver-photo-status'] != 'off' 
                    && ($competitor['flags']&0x02) == 0
                    ) {
                    $name .= '<br/><b>NO PHOTOS</b>';
                }
                $details[] = array('label'=>'Name', 'value'=>$name);
                if( $competitor['ctype'] == 10 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $details[] = array('label'=>'Pronoun', 'value'=>$competitor['pronoun']);
                }
                if( $competitor['ctype'] == 10 && $competitor['parent'] != '' ) { 
                    $details[] = array('label'=>'Parent', 'value'=>$competitor['parent']); 
                }
                if( $competitor['ctype'] == 50 && $competitor['parent'] != '' ) { 
                    $details[] = array('label'=>'Conductor', 'value'=>$competitor['conductor']); 
                }
                if( $competitor['ctype'] == 50 && $competitor['parent'] != '' ) { 
                    $details[] = array('label'=>'Contact', 'value'=>$competitor['parent']); 
                }
                $address = '';
                if( $competitor['address'] != '' ) { $address .= $competitor['address']; }
                $city = $competitor['city'];
                if( $competitor['province'] != '' ) { $city .= ($city != '' ? ", " : '') . $competitor['province']; }
                if( $competitor['postal'] != '' ) { $city .= ($city != '' ? "  " : '') . $competitor['postal']; }
                if( $city != '' ) { $address .= ($address != '' ? "\n" : '' ) . $city; }
                if( $address != '' ) {
                    $details[] = array('label'=>'Address', 'value'=>$address);
                }
                if( $competitor['phone_home'] != '' ) { $details[] = array('label'=>'Home', 'value'=>$competitor['phone_home']); }
                if( $competitor['phone_cell'] != '' ) { $details[] = array('label'=>'Cell', 'value'=>$competitor['phone_cell']); }
                if( $competitor['email'] != '' ) { $details[] = array('label'=>'Email', 'value'=>$competitor['email']); }
                if( $competitor['age'] != '' ) { $details[] = array('label'=>'Age', 'value'=>$competitor['_age']); }
                if( $competitor['num_people'] != '' ) { $details[] = array('label'=>'# People', 'value'=>$competitor['num_people']); }
                if( $competitor['study_level'] != '' ) { $details[] = array('label'=>'Study/Level', 'value'=>$competitor['study_level']); }
                if( $competitor['instrument'] != '' ) { $details[] = array('label'=>'Instrument', 'value'=>$competitor['instrument']); }
                if( ($competitor['flags']&0x01) == 0x01 ) { $details[] = array('label'=>'Waiver', 'value'=>'Signed'); }
                if( $competitor['notes'] != '' ) {
                    $details[] = array('label'=>'Notes', 'value'=>$competitor['notes']);
                }
                $registration['competitor' . $i . '_details'] = $details;
            }
        }

        //
        // Load the message sent directly to this registration
        //
        $strsql = "SELECT messages.id, "
            . "messages.subject, "
            . "messages.status, "
            . "messages.status AS status_text, "
            . "messages.dt_scheduled, "
            . "messages.dt_sent "
            . "FROM ciniki_musicfestival_messagerefs AS refs "
            . "INNER JOIN ciniki_musicfestival_messages AS messages ON ("
                . "refs.message_id = messages.id "
//                . "AND messages.status > 10 "
                . ") "
            . "WHERE refs.object_id = '" . ciniki_core_dbQuote($ciniki, $registration['id']) . "' "
            . "AND refs.object = 'ciniki.musicfestivals.registration' "
            . "AND refs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY dt_sent DESC, dt_scheduled DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'messages', 'fname'=>'id', 
                'fields'=>array('id', 'subject', 'status', 'status_text', 'dt_scheduled', 'dt_sent'),
                'maps'=>array(
                    'status_text'=>$maps['message']['status'],
                    ),
                'utctotz'=>array(
                    'dt_scheduled'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'dt_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.321', 'msg'=>'Unable to load messages', 'err'=>$rc['err']));
        }
        $registration['messages'] = isset($rc['messages']) ? $rc['messages'] : array();
        foreach($registration['messages'] as $mid => $message) {
            if( $message['status'] == 30 ) {
                $registration['messages'][$mid]['date'] = $message['dt_scheduled'];
            } else {
                $registration['messages'][$mid]['date'] = $message['dt_sent'];
            }
        }

        //
        // Load the invoice details
        //
        if( $registration['invoice_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
            $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $args['tnid'], $registration['invoice_id'], 
                'ciniki.musicfestivals.registration', $registration['id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['invoice']) ) {
                $registration['invoice_details'][] = array(
                    'label'=>'Invoice', 
                    'value'=>'#' . $rc['invoice']['invoice_number'] . ' - ' . $rc['invoice']['status_text'],
                    );
                if( $rc['invoice']['customer']['display_name'] != '' ) {
                    $registration['invoice_details'][] = array(
                        'label'=>'Customer', 
                        'value'=>$rc['invoice']['customer']['display_name'],
                        );
                }
                $registration['invoice_details'][] = array(
                    'label'=>'Type', 
                    'value'=>$rc['invoice']['invoice_type_text'],
                    );
                $registration['invoice_details'][] = array(
                    'label'=>'Date', 
                    'value'=>$rc['invoice']['invoice_date'],
                    );
                $registration['invoice_status'] = $rc['invoice']['status'];
            }
            if( isset($rc['item']) ) {
                $registration['item_id'] = $rc['item']['id'];
                $registration['unit_amount'] = $rc['item']['unit_amount_display'];
                $registration['unit_discount_amount'] = $rc['item']['unit_discount_amount_display'];
                $registration['unit_discount_percentage'] = $rc['item']['unit_discount_percentage'];
                $registration['taxtype_id'] = $rc['item']['taxtype_id'];
            } else {
                $registration['item_id'] = 0;
                $registration['unit_amount'] = '';
                $registration['unit_discount_amount'] = '';
                $registration['unit_discount_percentage'] = '';
                $registration['taxtype_id'] = 0;
            }
        }

        //
        // Get the categories and tags for the customer
        //
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_musicfestival_registration_tags "
            . "WHERE registration_id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) ) {
            foreach($rc['tags'] as $tags) {
                if( $tags['tags']['tag_type'] == 10 ) {
                    $registration['tags'] = $tags['tags']['lists'];
                }
            }
        }

        //
        // Get the list of trophies won
        //
        $strsql = "SELECT winners.id, "
            . "winners.flags, "
            . "winners.awarded_amount, "
            . "CONCAT_WS(' - ', categories.name, subcategories.name, trophies.name) AS name "
            . "FROM ciniki_musicfestival_trophy_winners AS winners "
            . "LEFT JOIN ciniki_musicfestival_trophies AS trophies ON ("
                . "winners.trophy_id = trophies.id "
                . "AND trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_trophy_subcategories AS subcategories ON ("
                . "trophies.subcategory_id = subcategories.id "
                . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_trophy_categories AS categories ON ("
                . "subcategories.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE winners.registration_id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY trophies.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'tas', 'fname'=>'id', 'fields'=>array('id', 'flags', 'awarded_amount', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1126', 'msg'=>'Unable to load tas', 'err'=>$rc['err']));
        }
        $registration['tas'] = isset($rc['tas']) ? $rc['tas'] : array();

        // 
        // Get the list of change requests
        //
        $strsql = "SELECT crs.id, "
            . "crs.cr_number, "
            . "crs.status, "
            . "crs.status AS status_text, "
            . "crs.dt_submitted, "
            . "crs.dt_completed, "
            . "crs.content "
            . "FROM ciniki_musicfestival_crs AS crs "
            . "WHERE crs.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND crs.object_id = '" . ciniki_core_dbQuote($ciniki, $registration['id']) . "' "
            . "AND crs.object = 'ciniki.musicfestivals.registration' "
            . "AND crs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY crs.dt_submitted DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'crs', 'fname'=>'id', 
                'fields'=>array(
                    'id', 'cr_number', 'status', 'status_text', 'dt_submitted', 'dt_completed', 'content',
                    ),
                'maps'=>array('status_text' => $maps['cr']['status']),
                'utctotz'=>array(
                    'dt_submitted' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'dt_completed' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1086', 'msg'=>'Unable to load crs', 'err'=>$rc['err']));
        }
        $registration['crs'] = isset($rc['crs']) ? $rc['crs'] : array();
        foreach($registration['crs'] as $cid => $cr) {
            $registration['crs'][$cid]['status_date'] = $cr['dt_submitted'];
            if( $cr['status'] == 70 ) {
                $registration['crs'][$cid]['status_date'] = $cr['dt_completed'];
            }
        }

        //
        // Check if change request requested
        //
        if( isset($args['cr_id']) && $args['cr_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'crLoad');
            $rc = ciniki_musicfestivals_crLoad($ciniki, $args['tnid'], [
                'cr_id' => $args['cr_id'],
                'details' => 'yes',
                'emails' => 'yes',
                'invoices' => 'yes',
                ]);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['cr'] = $rc['cr'];
            $registration['cr_details'] = $rc['cr']['details'];
            $registration['cr_invoice_items'] = $rc['cr']['invoice_items'];
            $registration['cr_emails'] = $rc['cr']['emails'];
        }
    }

    $rsp = array('stat'=>'ok', 'registration'=>$registration, 'competitors'=>array(), 'classes'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');


    //
    // Get the list of competitors
    //
/*    $strsql = "SELECT id, name "
        . "FROM ciniki_musicfestival_competitors "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['competitors']) ) {
        $rsp['competitors'] = $rc['competitors'];
    } */

    // FIXME: Remove load festival, should have been loaded previously
    //
    // Get the festival details
    //
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.start_date, "
        . "ciniki_musicfestivals.end_date, "
        . "ciniki_musicfestivals.status, "
        . "ciniki_musicfestivals.flags, "
        . "ciniki_musicfestivals.earlybird_date, "
        . "ciniki_musicfestivals.live_date, "
        . "ciniki_musicfestivals.virtual_date, "
        . "ciniki_musicfestivals.primary_image_id, "
        . "ciniki_musicfestivals.description, "
        . "ciniki_musicfestivals.document_logo_id, "
        . "ciniki_musicfestivals.document_header_msg, "
        . "ciniki_musicfestivals.document_footer_msg "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 
                'earlybird_date', 'live_date', 'virtual_date',
                'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg'),
            'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'earlybird_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.174', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.175', 'msg'=>'Unable to find Festival'));
    }
    $rsp['registration']['festival'] = $rc['festivals'][0];

    //
    // Determine which dates are still open for the festival
    //
    $now = new DateTime('now', new DateTimezone('UTC'));
    $earlybird_dt = new DateTime($rsp['registration']['festival']['earlybird_date'], new DateTimezone('UTC'));
    $live_dt = new DateTime($rsp['registration']['festival']['live_date'], new DateTimezone('UTC'));
    $virtual_dt = new DateTime($rsp['registration']['festival']['virtual_date'], new DateTimezone('UTC'));
    $rsp['registration']['festival']['earlybird'] = (($rsp['registration']['festival']['flags']&0x01) == 0x01 && $earlybird_dt > $now ? 'yes' : 'no');
    $rsp['registration']['festival']['live'] = (($rsp['registration']['festival']['flags']&0x01) == 0x01 && $live_dt > $now ? 'yes' : 'no');
    $rsp['registration']['festival']['virtual'] = (($rsp['registration']['festival']['flags']&0x03) == 0x03 && $virtual_dt > $now ? 'yes' : 'no');


    //
    // Get the festival settings
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.205', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    foreach($rc['settings'] as $k => $v) {
        $rsp['registration']['festival'][$k] = $v;
    }

    //
    // Get the list of classes
    //
    $strsql = "SELECT classes.id, ";
    if( isset($rsp['registration']['festival']['flags']) && ($rsp['registration']['festival']['flags']&0x0100) == 0x0100 ) {
        $strsql .= "CONCAT_WS(' - ', classes.code, sections.name, categories.name, classes.name) AS name, ";
    } else {
        $strsql .= "CONCAT_WS(' - ', classes.code, classes.name) AS name, ";
    }
    $strsql .= "classes.flags, "
        . "classes.feeflags, "
        . "classes.titleflags, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "classes.min_competitors, "
        . "classes.max_competitors, "
        . "FORMAT(classes.earlybird_fee, 2) AS earlybird_fee, "
        . "FORMAT(classes.fee, 2) AS fee, "
        . "FORMAT(classes.virtual_fee, 2) AS virtual_fee, "
        . "FORMAT(classes.earlybird_plus_fee, 2) AS earlybird_plus_fee, "
        . "FORMAT(classes.plus_fee, 2) AS plus_fee, "
        . "classes.provincials_code "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'flags', 'feeflags', 'titleflags', 'min_titles', 'max_titles', 
                'min_competitors', 'max_competitors',
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                'provincials_code',
                )),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $rsp['classes'] = $rc['classes'];
    }

    //
    // Get the list of provincial classes
    //
    if( isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 ) {
        $strsql = "SELECT classes.code, "
//            . "CONCAT_WS(' - ', classes.code, sections.name, categories.name, classes.name) AS name "
            . "CONCAT_WS(' - ', classes.code, categories.name, classes.name) AS name "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "sections.id = categories.section_id "
                . ") "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['provincial-festival-id']) . "' "
            . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'code', 'fields'=>array('code', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        $rsp['provincial_classes'] = isset($rc['classes']) ? $rc['classes'] : array();
    }

    //
    // Get the complete list of tags
    //
    $rsp['tags'] = array();
    $strsql = "SELECT DISTINCT tags.tag_type, tags.tag_name AS names "
        . "FROM ciniki_musicfestival_registration_tags AS tags "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "tags.registration_id = registrations.id "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND tags.tag_type = 10 "
        . "ORDER BY tags.tag_type, tags.tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.ags', array(
        array('container'=>'tags', 'fname'=>'tag_type', 'fields'=>array('type'=>'tag_type', 'names'), 
            'dlists'=>array('names'=>'::')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        foreach($rc['tags'] as $type) {
            if( $type['type'] == 10 ) {
                $rsp['tags'] = explode('::', $type['names']);
            }
        }
    }

    //
    // Get the complete list of festival members
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql = "SELECT members.id, "
            . "members.name, "
            . "IFNULL(festivalmembers.reg_start_dt, '') AS reg_start_dt, "
            . "IFNULL(festivalmembers.reg_end_dt, '') AS reg_end_dt "
            . "FROM ciniki_musicfestivals_members AS members "
            . "LEFT JOIN ciniki_musicfestival_members AS festivalmembers ON ("
                . "members.id = festivalmembers.member_id "
                . "AND festivalmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
                . "AND festivalmembers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE members.status = 10 "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY members.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'name', 'reg_start_dt', 'reg_end_dt')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.618', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
        }
        $rsp['members'] = isset($rc['members']) ? $rc['members'] : array();
    }

    return $rsp;
}
?>
