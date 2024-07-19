<?php
//
// Description
// ===========
// This method will return all the information about an class.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the class is attached to.
// class_id:          The ID of the class to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_classGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registrations'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.classGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

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
    // Return default for new Class
    //
    if( $args['class_id'] == 0 ) {
        $seq = 1;
        if( $args['category_id'] && $args['category_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS max_sequence "
                . "FROM ciniki_musicfestival_classes "
                . "WHERE ciniki_musicfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_musicfestival_classes.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'max');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['max']['max_sequence']) ) {
                $seq = $rc['max']['max_sequence'] + 1;
            }
        }
        $class = array(
            'id' => 0,
            'festival_id' => (isset($args['festival_id']) ? $args['festival_id'] : 0),
            'category_id' => (isset($args['category_id']) ? $args['category_id'] : 0),
            'section_id' => 0,
            'code' => '',
            'name' => '',
            'permalink' => '',
            'sequence' => $seq,
            'flags' => 0x4000,
            'earlybird_fee' => '',
            'fee' => '',
            'virtual_fee' => '',
            'earlybird_plus_fee' => '',
            'plus_fee' => '',
            'min_competitors' => 1,
            'max_competitors' => 1,
            'min_titles' => 1,
            'max_titles' => 1,
            'provincials_code' => '',
            'synopsis' => '',
            'schedule_minutes' => '',
        );
    }

    //
    // Get the details for an existing Class
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_classes.id, "
            . "ciniki_musicfestival_classes.festival_id, "
            . "ciniki_musicfestival_classes.category_id, "
            . "ciniki_musicfestival_categories.section_id, "
            . "ciniki_musicfestival_classes.code, "
            . "ciniki_musicfestival_classes.name, "
            . "ciniki_musicfestival_classes.permalink, "
            . "ciniki_musicfestival_classes.sequence, "
            . "ciniki_musicfestival_classes.flags, "
            . "ciniki_musicfestival_classes.earlybird_fee, "
            . "ciniki_musicfestival_classes.fee, "
            . "ciniki_musicfestival_classes.virtual_fee, "
            . "ciniki_musicfestival_classes.earlybird_plus_fee, "
            . "ciniki_musicfestival_classes.plus_fee, "
            . "ciniki_musicfestival_classes.min_competitors, "
            . "ciniki_musicfestival_classes.max_competitors, "
            . "ciniki_musicfestival_classes.min_titles, "
            . "ciniki_musicfestival_classes.max_titles, "
            . "ciniki_musicfestival_classes.provincials_code, "
            . "ciniki_musicfestival_classes.synopsis, "
            . "ciniki_musicfestival_classes.schedule_seconds "
            . "FROM ciniki_musicfestival_classes "
            . "INNER JOIN ciniki_musicfestival_categories ON ( "
                . "ciniki_musicfestival_classes.category_id = ciniki_musicfestival_categories.id "
                . "AND ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_musicfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('festival_id', 'category_id', 'section_id', 'code', 'name', 'permalink', 'sequence', 'flags', 
                    'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee', 
                    'min_competitors', 'max_competitors', 'min_titles', 'max_titles', 'provincials_code', 'synopsis',
                    'schedule_seconds',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.45', 'msg'=>'Class not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['classes'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.46', 'msg'=>'Unable to find Class'));
        }
        $class = $rc['classes'][0];
        $class['earlybird_fee'] = number_format($class['earlybird_fee'], 2);
        $class['fee'] = number_format($class['fee'], 2);
        $class['virtual_fee'] = number_format($class['virtual_fee'], 2);
        $class['earlybird_plus_fee'] = number_format($class['earlybird_plus_fee'], 2);
        $class['plus_fee'] = number_format($class['plus_fee'], 2);

        //
        // Get the tags for the class
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x1000) ) {
            $strsql = "SELECT tag_type, tag_name AS lists "
                . "FROM ciniki_musicfestival_class_tags "
                . "WHERE class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY tag_type, tag_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                    'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['tags']) ) {
                foreach($rc['tags'] as $tags) {
                    if( $tags['tags']['tag_type'] == 20 ) {
                        $class['levels'] = $tags['tags']['lists'];
                    }
                }
            }
        }
    }

    //
    // Get the list of registrations
    //
    if( isset($args['registrations']) && $args['registrations'] == 'yes' ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.festival_id, "
            . "sections.id AS section_id, "
            . "registrations.teacher_customer_id, "
            . "teachers.display_name AS teacher_name, "
            . "registrations.billing_customer_id, "
            . "registrations.rtype, "
            . "registrations.rtype AS rtype_text, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "registrations.display_name, "
            . "registrations.class_id, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "registrations.title1, "
            . "registrations.perf_time1, "
            . "registrations.title2, "
            . "registrations.perf_time2, "
            . "registrations.title3, "
            . "registrations.perf_time3, "
            . "FORMAT(registrations.fee, 2) AS fee, "
            . "registrations.payment_type "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_customers AS teachers ON ("
                . "registrations.teacher_customer_id = teachers.id "
                . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'teacher_name', 'billing_customer_id', 
                    'rtype', 'rtype_text', 'status', 'status_text', 'display_name', 
                    'class_id', 'class_code', 'class_name', 
                    'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3', 'fee', 'payment_type',
                    ),
                'maps'=>array(
                    'rtype_text'=>$maps['registration']['rtype'],
                    'status_text'=>$maps['registration']['status'],
                    'payment_type'=>$maps['registration']['payment_type'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['registrations']) ) {
            $class['registrations'] = $rc['registrations'];
        } else {
            $class['registrations'] = array();
        }

        //
        // Check if trophy list should be returned
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) ) {
            $strsql = "SELECT tc.id, "
                . "trophies.category, "
                . "trophies.name "
                . "FROM ciniki_musicfestival_trophy_classes AS tc "
                . "INNER JOIN ciniki_musicfestival_trophies AS trophies ON ("
                    . "tc.trophy_id = trophies.id "
                    . "AND trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE tc.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
                . "AND tc.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'trophies', 'fname'=>'id', 
                    'fields'=>array('id', 'category', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.550', 'msg'=>'Unable to load trophies', 'err'=>$rc['err']));
            }
            $class['trophies'] = isset($rc['trophies']) ? $rc['trophies'] : array();
        }
    }

    $rsp = array('stat'=>'ok', 'class'=>$class);

    //
    // Get the list of categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT ciniki_musicfestival_categories.id, "
            . "CONCAT_WS(' - ', ciniki_musicfestival_sections.name, ciniki_musicfestival_categories.name) AS name "
            . "FROM ciniki_musicfestival_sections, ciniki_musicfestival_categories "
            . "WHERE ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND ciniki_musicfestival_sections.id = ciniki_musicfestival_categories.section_id "
            . "AND ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_musicfestival_sections.sequence, ciniki_musicfestival_sections.name, "
                . "ciniki_musicfestival_categories.sequence, ciniki_musicfestival_categories.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.78', 'msg'=>'Categories not found', 'err'=>$rc['err']));
        }
        if( isset($rc['categories']) ) {
            $rsp['categories'] = $rc['categories'];
        } else {
            $rsp['categories'] = array();
        }
    }

    //
    // Get the complete list of tags
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x1000) ) {
        $rsp['tags'] = array();
        $strsql = "SELECT DISTINCT tags.tag_type, tags.tag_name AS names "
            . "FROM ciniki_musicfestival_class_tags AS tags "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "tags.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $class['section_id']) . "' " 
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tags.tag_type, tags.tag_sort_name, tags.tag_name "
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
                if( $type['type'] == 20 ) {
                    $rsp['levels'] = explode('::', $type['names']);
                }
            }
        }
    }

    return $rsp;
}
?>
