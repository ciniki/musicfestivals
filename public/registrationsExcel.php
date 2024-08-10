<?php
//
// Description
// -----------
// This method will return the excel export of registrations.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationsExcel($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Section'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Class'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'accompanist_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accompanist'),
        'registration_tag'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration Tag'),
        'member_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member'),
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationsExcel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Load festival year
    //
    $strsql = "SELECT DATE_FORMAT(start_date, '%Y') AS year, "
        . "flags "
        . "FROM ciniki_musicfestivals "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.553', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.554', 'msg'=>'Unable to find requested festival'));
    }
    $festival = $rc['festival'];
    $filename = $festival['year'] . ' Registrations';

    //
    // Get the additional settings
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.733', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    foreach($rc['settings'] as $k => $v) {
        $festival[$k] = $v;
    }

    //
    // Load registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.fee AS reg_fee, "
        . "registrations.flags, "
        . "registrations.title1, "
        . "registrations.composer1, "
        . "registrations.movements1, "
        . "registrations.perf_time1, "
        . "registrations.video_url1, "
        . "registrations.music_orgfilename1, "
        . "registrations.title2, "
        . "registrations.composer2, "
        . "registrations.movements2, "
        . "registrations.perf_time2, "
        . "registrations.video_url2, "
        . "registrations.music_orgfilename2, "
        . "registrations.title3, "
        . "registrations.composer3, "
        . "registrations.movements3, "
        . "registrations.perf_time3, "
        . "registrations.video_url3, "
        . "registrations.music_orgfilename3, "
        . "registrations.title4, "
        . "registrations.composer4, "
        . "registrations.movements4, "
        . "registrations.perf_time4, "
        . "registrations.video_url4, "
        . "registrations.music_orgfilename4, "
        . "registrations.title5, "
        . "registrations.composer5, "
        . "registrations.movements5, "
        . "registrations.perf_time5, "
        . "registrations.video_url5, "
        . "registrations.music_orgfilename5, "
        . "registrations.title6, "
        . "registrations.composer6, "
        . "registrations.movements6, "
        . "registrations.perf_time6, "
        . "registrations.video_url6, "
        . "registrations.music_orgfilename6, "
        . "registrations.title7, "
        . "registrations.composer7, "
        . "registrations.movements7, "
        . "registrations.perf_time7, "
        . "registrations.video_url7, "
        . "registrations.music_orgfilename7, "
        . "registrations.title8, "
        . "registrations.composer8, "
        . "registrations.movements8, "
        . "registrations.perf_time8, "
        . "registrations.video_url8, "
        . "registrations.music_orgfilename8, "
        . "registrations.participation, "
        . "registrations.notes AS reg_notes, "
        . "registrations.teacher_customer_id, "
        . "registrations.accompanist_customer_id, "
        . "registrations.member_id, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "competitors.id AS competitor_id, "
        . "competitors.name AS competitor_name, "
        . "competitors.pronoun, "
        . "competitors.parent, "
        . "competitors.address, "
        . "competitors.city, "
        . "competitors.province, "
        . "competitors.postal, "
        . "competitors.phone_home, "
        . "competitors.phone_cell, "
        . "competitors.email, "
        . "competitors.age AS cage, "
        . "competitors.study_level, "
        . "competitors.instrument, "
        . "competitors.notes ";
    if( isset($args['registration_tag']) && $args['registration_tag'] != '' ) {
        $strsql .= "FROM ciniki_musicfestival_registration_tags AS tags "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "tags.registration_id = registrations.id ";
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND registrations.participation = 0 ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
        $strsql .= "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['registration_tag']) . "' "
            . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'section_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 
                    'display_name', 'section_name', 'category_id', 'category_name', 'class_code', 'class_name', 'fee'=>'reg_fee', 
                    'title1', 'composer1', 'movements1', 'perf_time1', 'video_url1', 'music_orgfilename1',
                    'title2', 'composer2', 'movements2', 'perf_time2', 'video_url2', 'music_orgfilename2',
                    'title3', 'composer3', 'movements3', 'perf_time3', 'video_url3', 'music_orgfilename3',
                    'title4', 'composer4', 'movements4', 'perf_time4', 'video_url4', 'music_orgfilename4',
                    'title5', 'composer5', 'movements5', 'perf_time5', 'video_url5', 'music_orgfilename5',
                    'title6', 'composer6', 'movements6', 'perf_time6', 'video_url6', 'music_orgfilename6',
                    'title7', 'composer7', 'movements7', 'perf_time7', 'video_url7', 'music_orgfilename7',
                    'title8', 'composer8', 'movements8', 'perf_time8', 'video_url8', 'music_orgfilename8',
                    'participation', 'notes'=>'reg_notes', 'flags',
                    'mark', 'placement', 'level', 
                    ),
                ),
            array('container'=>'competitors', 'fname'=>'competitor_id', 
                'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'pronoun', 'parent', 'address', 'city', 'province', 'postal', 
                    'phone_home', 'phone_cell', 'email', 'age'=>'cage', 'study_level', 'instrument', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sections = array(
            array(
                'id' => 0,
                'name' => $args['registration_tag'],
                'registrations' => $rc['registrations'],
                ),
            );
        $filename .= ' - ' . $args['registration_tag'];
    } 
    elseif( isset($args['teacher_customer_id']) && $args['teacher_customer_id'] > 0 ) {
        $strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND registrations.participation = 0 ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'section_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 
                    'display_name', 'section_name', 'category_id', 'category_name', 'class_code', 'class_name', 'fee'=>'reg_fee', 
                    'title1', 'composer1', 'movements1', 'perf_time1', 'video_url1', 'music_orgfilename1',
                    'title2', 'composer2', 'movements2', 'perf_time2', 'video_url2', 'music_orgfilename2',
                    'title3', 'composer3', 'movements3', 'perf_time3', 'video_url3', 'music_orgfilename3',
                    'title4', 'composer4', 'movements4', 'perf_time4', 'video_url4', 'music_orgfilename4',
                    'title5', 'composer5', 'movements5', 'perf_time5', 'video_url5', 'music_orgfilename5',
                    'title6', 'composer6', 'movements6', 'perf_time6', 'video_url6', 'music_orgfilename6',
                    'title7', 'composer7', 'movements7', 'perf_time7', 'video_url7', 'music_orgfilename7',
                    'title8', 'composer8', 'movements8', 'perf_time8', 'video_url8', 'music_orgfilename8',
                    'participation', 'notes'=>'reg_notes', 'flags',
                    'mark', 'placement', 'level',
                    ),
                ),
            array('container'=>'competitors', 'fname'=>'competitor_id', 
                'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'pronoun', 'parent', 'address', 'city', 'province', 'postal', 
                    'phone_home', 'phone_cell', 'email', 'age'=>'cage', 'study_level', 'instrument', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sections = array(
            array(
                'id' => 0,
                'name' => 'Teacher',
                'registrations' => $rc['registrations'],
                ),
            );
        //
        // Get teacher name
        //
        $strsql = "SELECT display_name "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'teacher');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.731', 'msg'=>'Unable to load teacher', 'err'=>$rc['err']));
        }
        if( !isset($rc['teacher']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.732', 'msg'=>'Unable to find requested teacher'));
        }
        $sections[0]['name'] = $rc['teacher']['display_name'];
        $filename .= ' - Teacher - ' . $rc['teacher']['display_name'];
    } 
    elseif( isset($args['accompanist_customer_id']) && $args['accompanist_customer_id'] > 0 ) {
        $strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['accompanist_customer_id']) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND registrations.participation = 0 ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'section_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 
                    'display_name', 'section_name', 'category_id', 'category_name', 'class_code', 'class_name', 'fee'=>'reg_fee', 
                    'title1', 'composer1', 'movements1', 'perf_time1', 'video_url1', 'music_orgfilename1',
                    'title2', 'composer2', 'movements2', 'perf_time2', 'video_url2', 'music_orgfilename2',
                    'title3', 'composer3', 'movements3', 'perf_time3', 'video_url3', 'music_orgfilename3',
                    'title4', 'composer4', 'movements4', 'perf_time4', 'video_url4', 'music_orgfilename4',
                    'title5', 'composer5', 'movements5', 'perf_time5', 'video_url5', 'music_orgfilename5',
                    'title6', 'composer6', 'movements6', 'perf_time6', 'video_url6', 'music_orgfilename6',
                    'title7', 'composer7', 'movements7', 'perf_time7', 'video_url7', 'music_orgfilename7',
                    'title8', 'composer8', 'movements8', 'perf_time8', 'video_url8', 'music_orgfilename8',
                    'participation', 'notes'=>'reg_notes', 'flags',
                    'mark', 'placement', 'level',
                    ),
                ),
            array('container'=>'competitors', 'fname'=>'competitor_id', 
                'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'pronoun', 'parent', 'address', 'city', 'province', 'postal', 
                    'phone_home', 'phone_cell', 'email', 'age'=>'cage', 'study_level', 'instrument', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sections = array(
            array(
                'id' => 0,
                'name' => 'Teacher',
                'registrations' => $rc['registrations'],
                ),
            );
        //
        // Get accompanist name
        //
        $strsql = "SELECT display_name "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['accompanist_customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'accompanist');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.551', 'msg'=>'Unable to load accompanist', 'err'=>$rc['err']));
        }
        if( !isset($rc['accompanist']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.552', 'msg'=>'Unable to find requested accompanist'));
        }
        $sections[0]['name'] = $rc['accompanist']['display_name'];
        $filename .= ' - Accompanist - ' . $rc['accompanist']['display_name'];
    } 
    //
    // Get the registrations for a member
    //
    elseif( isset($args['member_id']) && $args['member_id'] > 0 ) {
        $strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE registrations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND registrations.participation = 0 ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY registrations.date_added DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'section_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 
                    'display_name', 'section_name', 'category_id', 'category_name', 'class_code', 'class_name', 'fee'=>'reg_fee', 
                    'title1', 'composer1', 'movements1', 'perf_time1', 'video_url1', 'music_orgfilename1',
                    'title2', 'composer2', 'movements2', 'perf_time2', 'video_url2', 'music_orgfilename2',
                    'title3', 'composer3', 'movements3', 'perf_time3', 'video_url3', 'music_orgfilename3',
                    'title4', 'composer4', 'movements4', 'perf_time4', 'video_url4', 'music_orgfilename4',
                    'title5', 'composer5', 'movements5', 'perf_time5', 'video_url5', 'music_orgfilename5',
                    'title6', 'composer6', 'movements6', 'perf_time6', 'video_url6', 'music_orgfilename6',
                    'title7', 'composer7', 'movements7', 'perf_time7', 'video_url7', 'music_orgfilename7',
                    'title8', 'composer8', 'movements8', 'perf_time8', 'video_url8', 'music_orgfilename8',
                    'participation', 'notes'=>'reg_notes', 'flags',
                    'mark', 'placement', 'level',
                    ),
                ),
            array('container'=>'competitors', 'fname'=>'competitor_id', 
                'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'pronoun', 'parent', 'address', 'city', 'province', 'postal', 
                    'phone_home', 'phone_cell', 'email', 'age'=>'cage', 'study_level', 'instrument', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sections = array(
            array(
                'id' => 0,
                'name' => 'Member',
                'registrations' => $rc['registrations'],
                ),
            );
        //
        // Get member name
        //
        $strsql = "SELECT name "
            . "FROM ciniki_musicfestivals_members "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'member');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.620', 'msg'=>'Unable to load member', 'err'=>$rc['err']));
        }
        if( !isset($rc['member']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.621', 'msg'=>'Unable to find requested member'));
        }
        $sections[0]['name'] = $rc['member']['name'];
        $filename .= ' - ' . $rc['member']['name'];
    } 
    else {
        $strsql .= "FROM ciniki_musicfestival_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "sections.id = categories.section_id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id ";
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND registrations.participation = 0 ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['section_id']) && $args['section_id'] > 0 ) {
            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
        }
        if( isset($args['class_id']) && $args['class_id'] > 0 ) {
            $strsql .= "AND classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
        }
        $strsql .= "ORDER BY sections.id, registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'section_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 
                    'display_name', 'section_name', 'category_id', 'category_name', 'class_code', 'class_name', 'fee'=>'reg_fee', 
                    'title1', 'composer1', 'movements1', 'perf_time1', 'video_url1', 'music_orgfilename1',
                    'title2', 'composer2', 'movements2', 'perf_time2', 'video_url2', 'music_orgfilename2',
                    'title3', 'composer3', 'movements3', 'perf_time3', 'video_url3', 'music_orgfilename3',
                    'title4', 'composer4', 'movements4', 'perf_time4', 'video_url4', 'music_orgfilename4',
                    'title5', 'composer5', 'movements5', 'perf_time5', 'video_url5', 'music_orgfilename5',
                    'title6', 'composer6', 'movements6', 'perf_time6', 'video_url6', 'music_orgfilename6',
                    'title7', 'composer7', 'movements7', 'perf_time7', 'video_url7', 'music_orgfilename7',
                    'title8', 'composer8', 'movements8', 'perf_time8', 'video_url8', 'music_orgfilename8',
                    'participation', 'notes'=>'reg_notes', 'flags',
                    'mark', 'placement', 'level',
                    ),
                ),
            array('container'=>'competitors', 'fname'=>'competitor_id', 
                'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'pronoun', 'parent', 'address', 'city', 'province', 'postal', 
                    'phone_home', 'phone_cell', 'email', 'age'=>'cage', 'study_level', 'instrument', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sections = $rc['sections'];
        if( isset($args['section_id']) && $args['section_id'] > 0 && isset($sections[0]['name']) ) {
            $filename .= ' - ' . $sections[0]['name'];
        }
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');

    //
    // Create an ALL sheet
    //
    if( count($sections) > 1 ) {
        $all = array(
            'name' => 'All', 
            'registrations' => array(),
            );
        foreach($sections as $s) {
            if( isset($s['registrations']) ) {
                foreach($s['registrations'] as $r) {
                    $all['registrations'][] = $r;
                }
            }
        }
        $sections[] = $all;
    }

    //
    // Export to excel
    //
    ini_set('memory_limit', '1024M');
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    $teachers = array();

    $num = 0;
    foreach($sections as $section) {
        if( !isset($section['registrations']) || count($section['registrations']) == 0 ) {
            continue;
        }
        if( $num > 0 ) {
            $objPHPExcelWorksheet = $objPHPExcel->createSheet($num);
        }
        $title = str_split($section['name'], 31);
        $objPHPExcelWorksheet->setTitle(preg_replace("/[\/\:\?]/", "-", $title[0]));

        $col = 0;
        $row = 1;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Name', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Section', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Category', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Class Code', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Class Name', false);
        for($i = 1; $i <= 8; $i++) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, "Title #{$i}", false);
//            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x040000) ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, "Movements", false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, "Composer", false);
//            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Time(sec)', false);
            if( ($festival['flags']&0x02) == 0x02 ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Video URL', false);
            }
            if( ($festival['flags']&0x0202) > 0 ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Music File', false);
            }
        }
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Fee', false);
        if( ($festival['flags']&0x10) == 0x10 ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Regular/Plus', false);
        }
        if( ($festival['flags']&0x02) == 0x02 ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Virtual', false);
        }
        if( isset($festival['comments-mark-ui']) && $festival['comments-mark-ui'] == 'yes' ) {
            if( isset($festival['comments-mark-label']) && $festival['comments-mark-label'] != '' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $festival['comments-mark-label'], false);
            } else {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Mark', false);
            }
        }
        if( isset($festival['comments-placement-ui']) && $festival['comments-placement-ui'] == 'yes' ) {
            if( isset($festival['comments-placement-label']) && $festival['comments-placement-label'] != '' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $festival['comments-placement-label'], false);
            } else {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Placement', false);
            }
        }
        if( isset($festival['comments-level-ui']) && $festival['comments-level-ui'] == 'yes' ) {
            if( isset($festival['comments-level-label']) && $festival['comments-level-label'] != '' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $festival['comments-level-label'], false);
            } else {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Level', false);
            }
        }
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher Phone', false);
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Accompanist', false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Accompanist Email', false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Accompanist Phone', false);
        }
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Member', false);
        }
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Notes', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Pronoun', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 2', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Pronoun', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 3', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Pronoun', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 4', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Pronoun', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 5', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Pronoun', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);

        $objPHPExcelWorksheet->getStyle('A1:AG1')->getFont()->setBold(true);

        $row++;

        foreach($section['registrations'] as $registration) {

            $registration['teacher_name'] = '';
            $registration['teacher_phone'] = '';
            $registration['teacher_email'] = '';
            if( $registration['teacher_customer_id'] > 0 ) {
                if( isset($teachers[$registration['teacher_customer_id']]) ) {
                    $registration['teacher_name'] = $teachers[$registration['teacher_customer_id']]['teacher_name'];
                    $registration['teacher_phone'] = $teachers[$registration['teacher_customer_id']]['teacher_phone'];
                    $registration['teacher_email'] = $teachers[$registration['teacher_customer_id']]['teacher_email'];
                } else {
                    $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
                        array('customer_id'=>$registration['teacher_customer_id'], 'phones'=>'yes', 'emails'=>'yes'));
                    if( isset($rc['customer']) ) {
                        $registration['teacher_name'] = $rc['customer']['display_name'];
                        if( isset($rc['customer']['phones']) ) {
                            foreach($rc['customer']['phones'] as $phone) {
                                $registration['teacher_phone'] .= ($registration['teacher_phone'] != '' ? ', ' : '') . $phone['phone_number'];
                            }
                        }
                        if( isset($rc['customer']['emails']) ) {
                            foreach($rc['customer']['emails'] as $email) {
                                $registration['teacher_email'] .= ($registration['teacher_email'] != '' ? ', ' : '') . $email['address'];
                            }
                        }

                        $teachers[$registration['teacher_customer_id']] = array(
                            'teacher_name'=>$registration['teacher_name'],
                            'teacher_phone'=>$registration['teacher_phone'],
                            'teacher_email'=>$registration['teacher_email'],
                            );
                    }
                }
            }
            $registration['accompanist_name'] = '';
            $registration['accompanist_phone'] = '';
            $registration['accompanist_email'] = '';
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) 
                && $registration['accompanist_customer_id'] > 0 
                ) {
                if( isset($accompanists[$registration['accompanist_customer_id']]) ) {
                    $registration['accompanist_name'] = $accompanists[$registration['accompanist_customer_id']]['accompanist_name'];
                    $registration['accompanist_phone'] = $accompanists[$registration['accompanist_customer_id']]['accompanist_phone'];
                    $registration['accompanist_email'] = $accompanists[$registration['accompanist_customer_id']]['accompanist_email'];
                } else {
                    $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
                        array('customer_id'=>$registration['accompanist_customer_id'], 'phones'=>'yes', 'emails'=>'yes'));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    if( isset($rc['customer']) ) {
                        $registration['accompanist_name'] = $rc['customer']['display_name'];
                        if( isset($rc['customer']['phones']) ) {
                            foreach($rc['customer']['phones'] as $phone) {
                                $registration['accompanist_phone'] .= ($registration['accompanist_phone'] != '' ? ', ' : '') . $phone['phone_number'];
                            }
                        }
                        if( isset($rc['customer']['emails']) ) {
                            foreach($rc['customer']['emails'] as $email) {
                                $registration['accompanist_email'] .= ($registration['accompanist_email'] != '' ? ', ' : '') . $email['address'];
                            }
                        }

                        $accompanists[$registration['accompanist_customer_id']] = array(
                            'accompanist_name'=>$registration['accompanist_name'],
                            'accompanist_phone'=>$registration['accompanist_phone'],
                            'accompanist_email'=>$registration['accompanist_email'],
                            );
                    }
                }
            }
            $registration['member_name'] = '';
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
                && $registration['member_id'] > 0 
                ) {
                if( isset($members[$registration['member_id']]) ) {
                    $registration['member_name'] = $members[$registration['member_id']]['member_name'];
                } else {
                    $strsql = "SELECT members.name "
                        . "FROM ciniki_musicfestivals_members AS members "
                        . "WHERE members.id = '" . ciniki_core_dbQuote($ciniki, $registration['member_id']) . "' "
                        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . "";
                    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'member');
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.590', 'msg'=>'Unable to load member', 'err'=>$rc['err']));
                    }
                    if( isset($rc['member']) ) {
                        $registration['member_name'] = $rc['member']['name'];
                    } else {
                        $registration['member_name'] = '';
                    }
                    $members[$registration['member_id']] = array(
                        'member_name' => $registration['member_name'],
                        );
                }
            }

            $col = 0;
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['display_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['section_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['category_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['class_code'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['class_name'], false);
            for($i = 1; $i <= 8; $i++) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration["title{$i}"], false);
//                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x040000) ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration["movements{$i}"], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration["composer{$i}"], false);
//                }
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration["perf_time{$i}"], false);
                if( ($festival['flags']&0x02) == 0x02 ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration["video_url{$i}"], false);
                }
                if( ($festival['flags']&0x0202) > 0 ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration["music_orgfilename{$i}"], false);
                }
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['fee'], false);
            if( ($festival['flags']&0x10) == 0x10 ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, ($registration['participation'] == 2 ? 'Plus' : 'Regular'), false);
            }
            if( ($festival['flags']&0x02) == 0x02 ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, ($registration['participation'] == 1 ? 'Virtual' : 'In Person'), false);
            }
            if( isset($festival['comments-mark-ui']) && $festival['comments-mark-ui'] == 'yes' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['mark'], false);
            }
            if( isset($festival['comments-placement-ui']) && $festival['comments-placement-ui'] == 'yes' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['placement'], false);
            }
            if( isset($festival['comments-level-ui']) && $festival['comments-level-ui'] == 'yes' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['level'], false);
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['teacher_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['teacher_email'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['teacher_phone'], false);
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['accompanist_name'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['accompanist_email'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['accompanist_phone'], false);
            }
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['member_name'], false);
            }
            $notes = $registration['notes'];
            if( isset($registration['competitors']) ) {
                foreach($registration['competitors'] as $competitor) {
                    if( $competitor['notes'] != '' ) {
                        $notes .= ($notes != '' ? '  ' : '') . $competitor['notes'];
                    }
                }
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $notes, false);
            
            
            if( isset($registration['competitors']) ) {
                foreach($registration['competitors'] as $competitor) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['name'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['pronoun'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['parent'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['address'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['city'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['phone_home'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['phone_cell'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['email'], false);
                }
            }

            $color = '';
            if( ($registration['flags']&0x0100) == 0x0100 ) {
                $color = 'EEEEEE';  // grey
            } else if( ($registration['flags']&0x0200) == 0x0200 ) {
                $color = 'CEFFF8';  // teal
            } else if( ($registration['flags']&0x0400) == 0x0400 ) {
                $color = 'DDF1FF';  // blue
            } else if( ($registration['flags']&0x0800) == 0x0800 ) {
                $color = 'F0DDFF';  // Purple
            } else if( ($registration['flags']&0x1000) == 0x1000 ) {
                $color = 'FFDDDD';  // Red
            } elseif( ($registration['flags']&0x2000) == 0x2000 ) {
                $color = 'FFEFDD';  // Orange
            } elseif( ($registration['flags']&0x4000) == 0x4000 ) {
                $color = 'FFFDC5';  // Yellow
            } elseif( ($registration['flags']&0x8000) == 0x8000 ) {
                $color = 'DDFFDD';  // Green
            }
            if( $color != '' ) {
                $objPHPExcelWorksheet->getStyle("A{$row}:AG{$row}")->applyFromArray(
                    array('fill'=>array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID, 
                        'color' => array('rgb' => $color),
                        )));
            }

            $row++;
        }

        for($i = 0; $i< 26; $i++) {
            $objPHPExcelWorksheet->getColumnDimension(chr($i+65))->setAutoSize(true);
        }
        $objPHPExcelWorksheet->freezePaneByColumnAndRow(0, 2);
        $num++;
    }

    //
    // Output the excel file
    //
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
