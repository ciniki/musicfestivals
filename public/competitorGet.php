<?php
//
// Description
// ===========
// This method will return all the information about an competitor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the competitor is attached to.
// competitor_id:          The ID of the competitor to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_competitorGet($ciniki) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'competitor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Competitor'),
        'emails'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor Emails'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor Registrations'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitorGet');
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

    //
    // Return default for new Competitor
    //
    if( $args['competitor_id'] == 0 ) {
        $competitor = array('id'=>0,
            'festival_id'=>'',
            'ctype'=>'10',
            'first'=>'',
            'last'=>'',
            'name'=>'',
            'public_name'=>'',
            'pronoun'=>'',
            'flags'=>0x07,
            'conductor'=>'',
            'num_people'=>'',
            'parent'=>'',
            'address'=>'',
            'city'=>'',
            'province'=>'',
            'postal'=>'',
            'country'=>'',
            'phone_home'=>'',
            'phone_cell'=>'',
            'email'=>'',
            'age'=>'',
            'study_level'=>'',
            'instrument'=>'',
            'etransfer_email'=>'',
            'notes'=>'',
        );
        $details = array();
    }

    //
    // Get the details for an existing Competitor
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_competitors.id, "
            . "ciniki_musicfestival_competitors.festival_id, "
            . "ciniki_musicfestival_competitors.ctype, "
            . "ciniki_musicfestival_competitors.first, "
            . "ciniki_musicfestival_competitors.last, "
            . "ciniki_musicfestival_competitors.name, "
            . "ciniki_musicfestival_competitors.public_name, "
            . "ciniki_musicfestival_competitors.pronoun, "
            . "ciniki_musicfestival_competitors.flags, "
            . "ciniki_musicfestival_competitors.conductor, "
            . "ciniki_musicfestival_competitors.num_people, "
            . "ciniki_musicfestival_competitors.parent, "
            . "ciniki_musicfestival_competitors.address, "
            . "ciniki_musicfestival_competitors.city, "
            . "ciniki_musicfestival_competitors.province, "
            . "ciniki_musicfestival_competitors.postal, "
            . "ciniki_musicfestival_competitors.country, "
            . "ciniki_musicfestival_competitors.phone_home, "
            . "ciniki_musicfestival_competitors.phone_cell, "
            . "ciniki_musicfestival_competitors.email, "
            . "ciniki_musicfestival_competitors.age AS _age, "
            . "ciniki_musicfestival_competitors.study_level, "
            . "ciniki_musicfestival_competitors.instrument, "
            . "ciniki_musicfestival_competitors.etransfer_email, "
            . "ciniki_musicfestival_competitors.notes "
            . "FROM ciniki_musicfestival_competitors "
            . "WHERE ciniki_musicfestival_competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_competitors.id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'competitors', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'ctype', 'first', 'last', 'name', 'public_name', 'pronoun', 
                    'flags', 'conductor', 'num_people', 
                    'parent', 'address', 'city', 'province', 'postal', 'country', 'phone_home', 'phone_cell', 
                    'email', '_age', 'study_level', 'instrument', 'etransfer_email', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.76', 'msg'=>'Competitor not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['competitors'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.77', 'msg'=>'Unable to find Competitor'));
        }
        $competitor = $rc['competitors'][0];
        $competitor['age'] = $competitor['_age'];
        if( $competitor['public_name'] == '' ) {
            $competitor['public_name'] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $competitor['name']); 
        }
        if( $competitor['num_people'] == 0 ) {
            $competitor['num_people'] = '';
        }
        $details = array();
        $details[] = array('label'=>'Name', 'value'=>$competitor['name']);
        if( $competitor['ctype'] == 10 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
            $details[] = array('label'=>'Pronoun', 'value'=>$competitor['pronoun']);
        }
        if( $competitor['ctype'] == 10 && $competitor['parent'] != '' ) { 
            $details[] = array('label'=>'Parent', 'value'=>$competitor['parent']); 
        }
        if( $competitor['ctype'] == 50 && $competitor['parent'] != '' ) { 
            $details[] = array('label'=>'Contact', 'value'=>$competitor['parent']); 
        }
        $address = '';
        if( $competitor['address'] != '' ) { 
            $address .= $competitor['address']; 
        }
        $city = $competitor['city'];
        if( $competitor['province'] != '' ) { 
            $city .= ($city != '' ? ", " : '') . $competitor['province']; 
        }
        if( $competitor['postal'] != '' ) { 
            $city .= ($city != '' ? "  " : '') . $competitor['postal']; 
        }
        if( $city != '' ) { 
            $address .= ($address != '' ? "\n" : '' ) . $city; 
        }
        if( $address != '' ) {
            $details[] = array('label'=>'Address', 'value'=>$address);
        }
        if( $competitor['phone_home'] != '' ) { $details[] = array('label'=>'Home', 'value'=>$competitor['phone_home']); }
        if( $competitor['phone_cell'] != '' ) { $details[] = array('label'=>'Cell', 'value'=>$competitor['phone_cell']); }
        if( $competitor['email'] != '' ) { $details[] = array('label'=>'Email', 'value'=>$competitor['email']); }
        if( $competitor['age'] != '' ) { $details[] = array('label'=>'Age', 'value'=>$competitor['age']); }
        if( $competitor['study_level'] != '' ) { $details[] = array('label'=>'Study/Level', 'value'=>$competitor['study_level']); }
        if( $competitor['instrument'] != '' ) { $details[] = array('label'=>'Instrument', 'value'=>$competitor['instrument']); }
        if( $competitor['etransfer_email'] != '' ) { $details[] = array('label'=>'etransfer Email', 'value'=>$competitor['etransfer_email']); }
        if( ($competitor['flags']&0x01) == 0x01 ) { $details[] = array('label'=>'Waiver', 'value'=>'Signed'); }
        if( $competitor['notes'] != '' ) {
            $details[] = array('label'=>'Notes', 'value'=>$competitor['notes']);
        }
    }

    //
    // Get the emails sent to the competitor
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0400) 
        && isset($args['emails']) && $args['emails'] == 'yes' 
        ) {
        //
        // Get the messages the competitor will be included in
        //
        $class_ids = array();
        $category_ids = array();
        $section_ids = array();
        $timeslot_ids = array();
        $division_ids = array();
        $schedule_ids = array();

        $strsql = "SELECT registrations.id, "
            . "registrations.class_id, "
            . "registrations.timeslot_id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "WHERE (registrations.competitor1_id = '" . ciniki_core_dbQuote($ciniki, $competitor['id']) . "' "
                . "OR registrations.competitor2_id = '" . ciniki_core_dbQuote($ciniki, $competitor['id']) . "' "
                . "OR registrations.competitor3_id = '" . ciniki_core_dbQuote($ciniki, $competitor['id']) . "' "
                . "OR registrations.competitor4_id = '" . ciniki_core_dbQuote($ciniki, $competitor['id']) . "' "
                . "OR registrations.competitor5_id = '" . ciniki_core_dbQuote($ciniki, $competitor['id']) . "' "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'reg');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.528', 'msg'=>'Unable to load reg', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                if( $row['class_id'] > 0 && !in_array($row['class_id'], $class_ids) ) {
                    $class_ids[] = $row['class_id'];
                }
                if( $row['timeslot_id'] > 0 && !in_array($row['timeslot_id'], $timeslot_ids) ) {
                    $timeslot_ids[] = $row['timeslot_id'];
                }
            }
        }
        
        if( count($class_ids) > 0 ) {
            $strsql = "SELECT categories.section_id, "
                . "categories.id AS category_id "
                . "FROM ciniki_musicfestival_classes AS classes "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE classes.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $class_ids) . ") "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.529', 'msg'=>'Unable to load reg', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    if( $row['category_id'] > 0 && !in_array($row['category_id'], $category_ids) ) {
                        $category_ids[] = $row['category_id'];
                    }
                    if( $row['section_id'] > 0 && !in_array($row['section_id'], $section_ids) ) {
                        $section_ids[] = $row['section_id'];
                    }
                }
            }
        }
        if( count($timeslot_ids) > 0 ) {
            $strsql = "SELECT divisions.ssection_id, "
                . "divisions.id AS division_id "
                . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                    . "timeslots.sdivision_id = divisions.id "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE timeslots.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $timeslot_ids) . ") "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'timeslot');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.478', 'msg'=>'Unable to load reg', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    if( $row['division_id'] > 0 && !in_array($row['division_id'], $division_ids) ) {
                        $division_ids[] = $row['division_id'];
                    }
                    if( $row['ssection_id'] > 0 && !in_array($row['ssection_id'], $schedule_ids) ) {
                        $schedule_ids[] = $row['ssection_id'];
                    }
                }
            }
        }


        //
        // Get the list of messags in draft or scheduled
        //
        $strsql = "SELECT messages.id, "
            . "messages.status AS status_text, "
            . "messages.subject "
            . "FROM ciniki_musicfestival_messagerefs AS refs "
            . "INNER JOIN ciniki_musicfestival_messages AS messages ON ("
                . "refs.message_id = messages.id "
                . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ("
                . "("
                . "refs.object = 'ciniki.musicfestivals.competitor' "
                . "AND refs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
                . ") ";
        if( count($class_ids) > 0 ) {
            $strsql .= "OR ("
                . "refs.object = 'ciniki.musicfestivals.class' "
                . "AND refs.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $class_ids) . ") "
                . ") ";
        }
        if( count($category_ids) > 0 ) {
            $strsql .= "OR ("
                . "refs.object = 'ciniki.musicfestivals.category' "
                . "AND refs.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $category_ids) . ") "
                . ") ";
        }
        if( count($section_ids) > 0 ) {
            $strsql .= "OR ("
                . "refs.object = 'ciniki.musicfestivals.section' "
                . "AND refs.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $section_ids) . ") "
                . ") ";
        }
        if( count($timeslot_ids) > 0 ) {
            $strsql .= "OR ("
                . "refs.object = 'ciniki.musicfestivals.scheduletimeslot' "
                . "AND refs.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $timeslot_ids) . ") "
                . ") ";
        }
        if( count($division_ids) > 0 ) {
            $strsql .= "OR ("
                . "refs.object = 'ciniki.musicfestivals.scheduledivision' "
                . "AND refs.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $division_ids) . ") "
                . ") ";
        }
        if( count($schedule_ids) > 0 ) {
            $strsql .= "OR ("
                . "refs.object = 'ciniki.musicfestivals.schedulesection' "
                . "AND refs.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $schedule_ids) . ") "
                . ") ";
        }
        $strsql .= ") "
            . "AND messages.status < 50 "
            . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'messages', 'fname'=>'id', 
                'fields'=>array('id', 'status_text', 'subject'),
                'maps'=>array('status_text'=>$maps['message']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.497', 'msg'=>'Unable to load messages', 'err'=>$rc['err']));
        }
        $competitor['messages'] = isset($rc['messages']) ? $rc['messages'] : array();

        //
        // Get the sent emails
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
        $rc = ciniki_mail_hooks_objectMessages($ciniki, $args['tnid'], array(
            'object' => 'ciniki.musicfestivals.competitor',
            'object_id' => $args['competitor_id'],
            'xml' => 'no',
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $competitor['emails'] = isset($rc['messages']) ? $rc['messages'] : array();
    }

    //
    // Get the list of registrations for the competitor
    //
    if( isset($args['registrations']) && $args['registrations'] == 'yes' ) {
        $strsql = "SELECT registrations.id, "
            . "sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "categories.name AS category_name, "
            . "registrations.rtype, "
            . "registrations.rtype AS rtype_text, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "registrations.title1, "
            . "registrations.perf_time1, "
            . "registrations.title2, "
            . "registrations.perf_time2, "
            . "registrations.title3, "
            . "registrations.perf_time3, "
            . "FORMAT(registrations.fee, 2) AS fee "
            . "FROM ciniki_musicfestival_registrations AS registrations "
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
            . "WHERE ("
                . "competitor1_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
                . "OR competitor2_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
                . "OR competitor3_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
                . "OR competitor4_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
                . "OR competitor5_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sections.sequence, sections.name, "
                . "categories.sequence, categories.name, "
                . "classes.sequence, classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'section_name', 'category_name', 'class_code', 'class_name', 
                    'rtype', 'rtype_text', 'status', 'status_text', 
                    'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3',
                    ),
                'maps'=>array(
                    'rtype_text' => $maps['registration']['rtype'],
                    'status_text' => $maps['registration']['status'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.560', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $competitor['registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
    }

    return array('stat'=>'ok', 'competitor'=>$competitor, 'details'=>$details);
}
?>
