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
            'rtype'=>30,
            'status'=>'',
            'invoice_id'=>'0',
            'display_name'=>'',
            'competitor1_id'=>'0',
            'competitor2_id'=>'0',
            'competitor3_id'=>'0',
            'competitor4_id'=>'0',
            'competitor5_id'=>'0',
            'class_id'=>(isset($args['class_id']) ? $args['class_id'] : 0),
            'title1'=>'',
            'perf_time1'=>'',
            'title2'=>'',
            'perf_time2'=>'',
            'title3'=>'',
            'perf_time3'=>'',
            'fee'=>'0',
            'payment_type'=>'0',
            'virtual'=>0,
            'videolink'=>'',
            'notes'=>'',
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
            . "ciniki_musicfestival_registrations.rtype, "
            . "ciniki_musicfestival_registrations.status, "
            . "ciniki_musicfestival_registrations.invoice_id, "
            . "ciniki_musicfestival_registrations.display_name, "
            . "ciniki_musicfestival_registrations.competitor1_id, "
            . "ciniki_musicfestival_registrations.competitor2_id, "
            . "ciniki_musicfestival_registrations.competitor3_id, "
            . "ciniki_musicfestival_registrations.competitor4_id, "
            . "ciniki_musicfestival_registrations.competitor5_id, "
            . "ciniki_musicfestival_registrations.class_id, "
            . "ciniki_musicfestival_registrations.title1, "
            . "ciniki_musicfestival_registrations.perf_time1, "
            . "ciniki_musicfestival_registrations.title2, "
            . "ciniki_musicfestival_registrations.perf_time2, "
            . "ciniki_musicfestival_registrations.title3, "
            . "ciniki_musicfestival_registrations.perf_time3, "
            . "FORMAT(ciniki_musicfestival_registrations.fee, 2) AS fee, "
            . "ciniki_musicfestival_registrations.payment_type, "
            . "ciniki_musicfestival_registrations.virtual, "
            . "ciniki_musicfestival_registrations.videolink, "
            . "ciniki_musicfestival_registrations.music_orgfilename, "
            . "ciniki_musicfestival_registrations.notes "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE ciniki_musicfestival_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('festival_id', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'invoice_id',
                    'display_name', 'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id', 
                    'class_id', 
                    'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3', 
                    'fee', 'payment_type', 
                    'virtual', 'videolink', 'music_orgfilename', 'notes'),
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
        // Get the competitor details
        //
        for($i = 1; $i <= 5; $i++) {
            if( $registration['competitor' . $i . '_id'] > 0 ) {
                $strsql = "SELECT ciniki_musicfestival_competitors.id, "
                    . "ciniki_musicfestival_competitors.festival_id, "
                    . "ciniki_musicfestival_competitors.name, "
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
                        'fields'=>array('festival_id', 'name', 'parent', 'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 'email', '_age', 'study_level', 'instrument', 'notes'),
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
                if( $competitor['parent'] != '' ) { $details[] = array('label'=>'Parent', 'value'=>$competitor['parent']); }
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
                $registration['competitor' . $i . '_details'] = $details;
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
    $strsql = "SELECT ciniki_musicfestival_classes.id, CONCAT_WS(' - ', ciniki_musicfestival_classes.code, ciniki_musicfestival_classes.name) AS name, FORMAT(fee, 2) AS fee "
        . "FROM ciniki_musicfestival_sections, ciniki_musicfestival_categories, ciniki_musicfestival_classes "
        . "WHERE ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "AND ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_sections.id = ciniki_musicfestival_categories.section_id "
        . "AND ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_categories.id = ciniki_musicfestival_classes.category_id "
        . "AND ciniki_musicfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_musicfestival_sections.name, ciniki_musicfestival_classes.name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name', 'fee')),
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
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 'earlybird_date',
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
    // Get the festival settings
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.205', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    foreach($rc['settings'] as $k => $v) {
        $rsp['registration']['festival'][$k] = $v;
    }

    return $rsp;
}
?>
