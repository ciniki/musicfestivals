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
    // Return default for new Registration
    //
    if( $args['registration_id'] == 0 ) {
        $registration = array('id'=>0,
            'festival_id'=>$args['festival_id'],
            'teacher_customer_id'=>'0',
            'billing_customer_id'=>'0',
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
            'payment_type'=>'0',
            'participation'=>0,
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
            'instrument' => '',
            'mark' => '',
            'placement' => '',
            'level' => '',
            'provincials_status' => 0,
            'provincials_position' => '',
            'comments' => '',
            'notes'=>'',
            'internal_notes'=>'',
        );
    }

    //
    // Get the details for an existing Registration
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_registrations.id, "
            . "ciniki_musicfestival_registrations.festival_id, "
            . "ciniki_musicfestival_registrations.teacher_customer_id, "
            . "ciniki_musicfestival_registrations.billing_customer_id, "
            . "ciniki_musicfestival_registrations.accompanist_customer_id, "
            . "ciniki_musicfestival_registrations.member_id, "
            . "ciniki_musicfestival_registrations.rtype, "
            . "ciniki_musicfestival_registrations.status, "
            . "ciniki_musicfestival_registrations.flags, "
            . "ciniki_musicfestival_registrations.invoice_id, "
            . "ciniki_musicfestival_registrations.display_name, "
            . "ciniki_musicfestival_registrations.competitor1_id, "
            . "ciniki_musicfestival_registrations.competitor2_id, "
            . "ciniki_musicfestival_registrations.competitor3_id, "
            . "ciniki_musicfestival_registrations.competitor4_id, "
            . "ciniki_musicfestival_registrations.competitor5_id, "
            . "ciniki_musicfestival_registrations.class_id, "
            . "ciniki_musicfestival_registrations.title1, "
            . "ciniki_musicfestival_registrations.composer1, "
            . "ciniki_musicfestival_registrations.movements1, "
            . "ciniki_musicfestival_registrations.perf_time1, "
            . "ciniki_musicfestival_registrations.title2, "
            . "ciniki_musicfestival_registrations.composer2, "
            . "ciniki_musicfestival_registrations.movements2, "
            . "ciniki_musicfestival_registrations.perf_time2, "
            . "ciniki_musicfestival_registrations.title3, "
            . "ciniki_musicfestival_registrations.composer3, "
            . "ciniki_musicfestival_registrations.movements3, "
            . "ciniki_musicfestival_registrations.perf_time3, "
            . "ciniki_musicfestival_registrations.title4, "
            . "ciniki_musicfestival_registrations.composer4, "
            . "ciniki_musicfestival_registrations.movements4, "
            . "ciniki_musicfestival_registrations.perf_time4, "
            . "ciniki_musicfestival_registrations.title5, "
            . "ciniki_musicfestival_registrations.composer5, "
            . "ciniki_musicfestival_registrations.movements5, "
            . "ciniki_musicfestival_registrations.perf_time5, "
            . "ciniki_musicfestival_registrations.title6, "
            . "ciniki_musicfestival_registrations.composer6, "
            . "ciniki_musicfestival_registrations.movements6, "
            . "ciniki_musicfestival_registrations.perf_time6, "
            . "ciniki_musicfestival_registrations.title7, "
            . "ciniki_musicfestival_registrations.composer7, "
            . "ciniki_musicfestival_registrations.movements7, "
            . "ciniki_musicfestival_registrations.perf_time7, "
            . "ciniki_musicfestival_registrations.title8, "
            . "ciniki_musicfestival_registrations.composer8, "
            . "ciniki_musicfestival_registrations.movements8, "
            . "ciniki_musicfestival_registrations.perf_time8, "
            . "FORMAT(ciniki_musicfestival_registrations.fee, 2) AS fee, "
            . "ciniki_musicfestival_registrations.payment_type, "
            . "ciniki_musicfestival_registrations.participation, "
            . "ciniki_musicfestival_registrations.video_url1, "
            . "ciniki_musicfestival_registrations.video_url2, "
            . "ciniki_musicfestival_registrations.video_url3, "
            . "ciniki_musicfestival_registrations.video_url4, "
            . "ciniki_musicfestival_registrations.video_url5, "
            . "ciniki_musicfestival_registrations.video_url6, "
            . "ciniki_musicfestival_registrations.video_url7, "
            . "ciniki_musicfestival_registrations.video_url8, "
            . "ciniki_musicfestival_registrations.music_orgfilename1, "
            . "ciniki_musicfestival_registrations.music_orgfilename2, "
            . "ciniki_musicfestival_registrations.music_orgfilename3, "
            . "ciniki_musicfestival_registrations.music_orgfilename4, "
            . "ciniki_musicfestival_registrations.music_orgfilename5, "
            . "ciniki_musicfestival_registrations.music_orgfilename6, "
            . "ciniki_musicfestival_registrations.music_orgfilename7, "
            . "ciniki_musicfestival_registrations.music_orgfilename8, "
            . "ciniki_musicfestival_registrations.backtrack1, "
            . "ciniki_musicfestival_registrations.backtrack2, "
            . "ciniki_musicfestival_registrations.backtrack3, "
            . "ciniki_musicfestival_registrations.backtrack4, "
            . "ciniki_musicfestival_registrations.backtrack5, "
            . "ciniki_musicfestival_registrations.backtrack6, "
            . "ciniki_musicfestival_registrations.backtrack7, "
            . "ciniki_musicfestival_registrations.backtrack8, "
            . "ciniki_musicfestival_registrations.instrument, "
            . "ciniki_musicfestival_registrations.mark, "
            . "ciniki_musicfestival_registrations.placement, "
            . "ciniki_musicfestival_registrations.level, "
            . "ciniki_musicfestival_registrations.provincials_status, "
            . "ciniki_musicfestival_registrations.provincials_position, "
            . "ciniki_musicfestival_registrations.comments, "
            . "ciniki_musicfestival_registrations.notes, "
            . "ciniki_musicfestival_registrations.internal_notes "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE ciniki_musicfestival_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'billing_customer_id', 
                    'accompanist_customer_id', 'member_id',
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
                    'fee', 'payment_type', 
                    'participation', 
                    'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                    'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3',  'music_orgfilename4', 
                    'music_orgfilename5', 'music_orgfilename6',  'music_orgfilename7', 'music_orgfilename8',  
                    'backtrack1', 'backtrack2', 'backtrack3',  'backtrack4', 
                    'backtrack5', 'backtrack6',  'backtrack7', 'backtrack8',  
                    'instrument', 'mark', 'placement', 'level', 'comments', 'provincials_status', 'provincials_position',
                    'notes', 'internal_notes'),
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
        // Get the teacher details
        //
        if( isset($registration['teacher_customer_id']) && $registration['teacher_customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
                array('customer_id'=>$registration['teacher_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['teacher_details'] = $rc['details'];
        } else {
            $registration['teacher_details'] = array();
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
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
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
                    . "ciniki_musicfestival_competitors.name, "
                    . "ciniki_musicfestival_competitors.pronoun, "
                    . "ciniki_musicfestival_competitors.parent, "
                    . "ciniki_musicfestival_competitors.address, "
                    . "ciniki_musicfestival_competitors.city, "
                    . "ciniki_musicfestival_competitors.province, "
                    . "ciniki_musicfestival_competitors.postal, "
                    . "ciniki_musicfestival_competitors.phone_home, "
                    . "ciniki_musicfestival_competitors.phone_cell, "
                    . "ciniki_musicfestival_competitors.email, "
                    . "ciniki_musicfestival_competitors.age AS _age, "
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
                        'fields'=>array('festival_id', 'ctype', 'name', 'pronoun', 'parent', 'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 'email', '_age', 'study_level', 'instrument', 'notes'),
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
                if( $competitor['study_level'] != '' ) { $details[] = array('label'=>'Study/Level', 'value'=>$competitor['study_level']); }
                if( $competitor['instrument'] != '' ) { $details[] = array('label'=>'Instrument', 'value'=>$competitor['instrument']); }
                $details[] = array('label'=>'Notes', 'value'=>$competitor['notes']);
                $registration['competitor' . $i . '_details'] = $details;
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
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x2000) ) {
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

    //
    // Get the list of classes
    //
    $strsql = "SELECT classes.id, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS name, "
        . "classes.flags, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "FORMAT(classes.earlybird_fee, 2) AS earlybird_fee, "
        . "FORMAT(classes.fee, 2) AS fee, "
        . "FORMAT(classes.virtual_fee, 2) AS virtual_fee, "
        . "FORMAT(classes.earlybird_plus_fee, 2) AS earlybird_plus_fee, "
        . "FORMAT(classes.plus_fee, 2) AS plus_fee "
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
            'fields'=>array('id', 'name', 'flags', 'min_titles', 'max_titles', 'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee')),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $rsp['classes'] = $rc['classes'];
    }

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
    // Get the complete list of tags
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x2000) ) {
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
