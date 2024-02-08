<?php
//
// Description
// ===========
// This method will return all the information about an festival.
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
function ciniki_musicfestivals_festivalGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'schedule'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule'),
        'sections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'classes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Classes'),
        'levels'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Levels'),
        'recommendations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicator Recommendations'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registrations'),
        'schedule'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Section'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'accompanist_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accompanist'),
        'competitors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitors'),
        'city_prov'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitors From City Province'),
        'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitors From Province'),
        'invoices'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoices'),
        'invoice_typestatus'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Status'),
        'adjudicators'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicators'),
        'certificates'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Certificates'),
        'photos'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Photos'),
        'comments'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Comments'),
        'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
        'lists'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Lists'),
        'list_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List'),
        'listsection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List Section'),
        'sponsors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sponsors'),
        'messages'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Messages List'),
        'messages_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Messages Status'),
        'members'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Members List'),
        'member_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Festival'),
        'emails_list'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Emails List'),
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        'entry_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Entry'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
        'registration_tag'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration Tag'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalGet');
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
    // Check for update sequence actions
    //
    if( isset($args['action']) && $args['action'] == 'listentrysequenceupdate' 
        && isset($args['listsection_id']) && $args['listsection_id'] > 0
        && isset($args['entry_id']) && $args['entry_id'] > 0
        && isset($args['sequence']) && $args['sequence'] > 0
        ) {
        $strsql = "SELECT sequence "
            . "FROM ciniki_musicfestival_list_entries "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.287', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
        }
        if( !isset($rc['entry']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.288', 'msg'=>'Unable to find requested entry'));
        }
        $entry = $rc['entry'];
           
        // 
        // Update the sequence
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.listentry', $args['entry_id'], array(
            'sequence' => $args['sequence'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.286', 'msg'=>'Unable to update the listentry', 'err'=>$rc['err']));
        }
         
        //
        // Adjust sequences in section
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.listentry', 'section_id', $args['listsection_id'], $args['sequence'], $entry['sequence']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.285', 'msg'=>'Unable to move entry', 'err'=>$rc['err']));
        }
    }

    //
    // Setup the arrays for the lists of next/prev ids
    //
    $nplists = array(
        'sections'=>array(),
        'categories'=>array(),
        'classes'=>array(),
        'levels'=>array(),
        'registrations'=>array(),
        'schedule_sections'=>array(),
        'schedule_divisions'=>array(),
        'schedule_timeslots'=>array(),
        'adjudicators'=>array(),
        'files'=>array(),
        'sponsors'=>array(),
        );

    //
    // Return default for new Festival
    //
    if( $args['festival_id'] == 0 ) {
        $festival = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'start_date'=>'',
            'end_date'=>'',
            'status'=>'10',
            'flags'=>'0',
            'primary_image_id'=>'0',
            'header_logo_id'=>'0',
            'description'=>'',
            'num_registrations'=>0,
            'sponsors'=>array(),
        );
    }

    //
    // Get the details for an existing Festival
    //
    else {
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
            . "ciniki_musicfestivals.edit_end_dt, "
            . "ciniki_musicfestivals.upload_end_dt, "
            . "ciniki_musicfestivals.primary_image_id, "
            . "ciniki_musicfestivals.description, "
            . "ciniki_musicfestivals.document_logo_id, "
            . "ciniki_musicfestivals.document_header_msg, "
            . "ciniki_musicfestivals.document_footer_msg, "
            . "ciniki_musicfestivals.comments_grade_label, "
            . "ciniki_musicfestivals.comments_footer_msg "
            . "FROM ciniki_musicfestivals "
            . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'festivals', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 
                    'earlybird_date', 'live_date', 'virtual_date', 'edit_end_dt', 'upload_end_dt',
                    'primary_image_id', 'description', 
                    'document_logo_id', 'document_header_msg', 'document_footer_msg',
                    'comments_grade_label', 'comments_footer_msg',
                    ),
                'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'earlybird_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'live_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'virtual_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'edit_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'upload_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.8', 'msg'=>'Festival not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['festivals'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.9', 'msg'=>'Unable to find Festival'));
        }
        $festival = $rc['festivals'][0];

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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.195', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
        }
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }

        //
        // Get the number of registrations
        //
        $festival['num_registrations'] = '';

        //
        // Setup ipv (in person/live) sql
        //
        $ipv_sql = '';
        if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) ) {
            if( $args['ipv'] == 'inperson' ) {
                $ipv_sql .= "AND registrations.participation = 0 ";
            } elseif( $args['ipv'] == 'virtual' ) {
                $ipv_sql .= "AND registrations.participation = 1 ";
            }
        }

        //
        // Get the list of sections
        //
        if( isset($args['sections']) && $args['sections'] == 'yes' ) {
            $strsql = "SELECT sections.id, "
                . "sections.festival_id, "
                . "sections.name, "
                . "sections.permalink, "
                . "sections.sequence, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "LEFT JOIN ciniki_musicfestival_categories AS categories USE INDEX (festival_id_2) ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes USE INDEX (festival_id_3) ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations USE INDEX (festival_id_2) ON ("
                    . "classes.id = registrations.class_id "
                    . $ipv_sql
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "GROUP BY sections.id "
                . "ORDER BY sections.sequence, sections.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'festival_id', 'name', 'permalink', 'sequence', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['sections']) ) {
                $festival['sections'] = $rc['sections'];
                foreach($festival['sections'] as $iid => $section) {
                    if( isset($args['section_id']) && $args['section_id'] == $section['id'] ) {
                        $selected_section = $section;
                    }
                    $nplists['sections'][] = $section['id'];
                }
            } else {
                $festival['sections'] = array();
            }
        }

        //
        // Get the list of categories
        //
        if( isset($args['categories']) && $args['categories'] == 'yes' ) {
            $strsql = "SELECT categories.id, "
                . "categories.festival_id, "
                . "categories.section_id, "
                . "sections.name AS section_name, "
                . "categories.name, "
                . "categories.permalink, "
                . "categories.sequence, "
                . "COUNT(registrations.class_id) AS num_registrations "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "INNER JOIN ciniki_musicfestival_categories AS categories USE INDEX (festival_id_2) ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes USE INDEX (festival_id_3) ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations USE INDEX (festival_id_2) ON ("
                    . "classes.id = registrations.class_id "
                    . $ipv_sql
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "GROUP BY sections.id, categories.id "
                . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'categories', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'section_id', 'section_name', 'name', 'permalink', 'sequence', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['categories']) ) {
                $festival['categories'] = $rc['categories'];
                foreach($festival['categories'] as $iid => $category) {
                    $nplists['categories'][] = $category['id'];
                }
            } else {
                $festival['categories'] = array();
            }
        }

        //
        // Get the list of classes
        //
        if( isset($args['classes']) && $args['classes'] == 'yes' ) {
            $strsql = "SELECT classes.id, "
                . "classes.festival_id, "
                . "classes.category_id, "
                . "sections.name AS section_name, "
                . "categories.name AS category_name, "
                . "classes.code, "
                . "classes.name, "
                . "classes.permalink, "
                . "classes.sequence, "
                . "classes.flags, "
                . "classes.earlybird_fee, "
                . "classes.fee, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "INNER JOIN ciniki_musicfestival_categories AS categories USE INDEX (festival_id_2) ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_classes AS classes USE INDEX (festival_id_3) ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations USE INDEX (festival_id_2) ON ("
                    . "classes.id = registrations.class_id "
                    . $ipv_sql
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "GROUP BY classes.id "
                . "ORDER BY sections.sequence, sections.name, "
                    . "categories.sequence, categories.name, "
                    . "classes.sequence, classes.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'category_id', 'section_name', 'category_name', 
                        'code', 'name', 'permalink', 'sequence', 'flags', 'earlybird_fee', 'fee', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['classes']) ) {
                $festival['classes'] = $rc['classes'];
                foreach($festival['classes'] as $iid => $class) {
                    $festival['classes'][$iid]['earlybird_fee'] = numfmt_format_currency($intl_currency_fmt, $class['earlybird_fee'], $intl_currency);
                    $festival['classes'][$iid]['fee'] = numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency);
                    $nplists['classes'][] = $class['id'];
                }
            } else {
                $festival['classes'] = array();
            }
        }

        //
        // Get the list of levels
        //
        if( isset($args['levels']) && $args['levels'] == 'yes' ) {
            $strsql = "SELECT DISTINCT tags.tag_name, tags.tag_sort_name "
                . "FROM ciniki_musicfestival_classes AS classes "
                . "INNER JOIN ciniki_musicfestival_class_tags AS tags ON ("
                    . "classes.id = tags.class_id "
                    . "AND tags.tag_type = 20 "
                    . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY tags.tag_sort_name, tags.tag_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'levels', 'fname'=>'tag_name', 'fields'=>array('tag_name', 'tag_sort_name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.578', 'msg'=>'Unable to load levels', 'err'=>$rc['err']));
            }
            $festival['levels'] = isset($rc['levels']) ? $rc['levels'] : array();
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
                . "registrations.accompanist_customer_id, "
                . "accompanists.display_name AS accompanist_name, "
                . "classes.flags AS class_flags, "
                . "classes.min_titles, "
                . "classes.max_titles, "
                . "registrations.billing_customer_id, "
                . "registrations.rtype, "
                . "registrations.rtype AS rtype_text, "
                . "registrations.status, "
                . "registrations.status AS status_text, ";
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                $strsql .= "registrations.pn_display_name AS display_name, ";
            } else {
                $strsql .= "registrations.display_name, ";
            }
            $strsql .= "registrations.class_id, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
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
                . "FORMAT(registrations.fee, 2) AS fee, "
                . "registrations.payment_type, "
                . "registrations.participation "
                . "FROM ciniki_musicfestival_registrations AS registrations ";
            if( isset($args['registration_tag']) && $args['registration_tag'] != '' ) {
                $strsql .= "INNER JOIN ciniki_musicfestival_registration_tags AS tags ON ("
                    . "registrations.id = tags.registration_id "
                    . "AND tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['registration_tag']) . "' "
                    . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") ";
            }
            $strsql .= "LEFT JOIN ciniki_customers AS teachers ON ("
                    . "registrations.teacher_customer_id = teachers.id "
                    . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS accompanists ON ("
                    . "registrations.accompanist_customer_id = accompanists.id "
                    . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
                . $ipv_sql
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            if( isset($args['section_id']) && $args['section_id'] > 0 ) {
                $strsql .= "HAVING section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
            } elseif( isset($args['member_id']) && $args['member_id'] > 0 ) {
                $strsql .= "AND registrations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' ";
            } elseif( isset($args['teacher_customer_id']) && $args['teacher_customer_id'] > 0 ) {
                $strsql .= "AND registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' ";
            } elseif( isset($args['accompanist_customer_id']) && $args['accompanist_customer_id'] > 0 ) {
                $strsql .= "AND registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['accompanist_customer_id']) . "' ";
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'registrations', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'teacher_name', 'billing_customer_id', 
                        'rtype', 'rtype_text', 'status', 'status_text', 'display_name', 
                        'class_id', 'class_code', 'class_name', 'class_flags', 'min_titles', 'max_titles',
                        'fee', 'payment_type', 'participation', 
                        'title1', 'composer1', 'movements1', 'perf_time1', 'video_url1', 'music_orgfilename1',
                        'title2', 'composer2', 'movements2', 'perf_time2', 'video_url2', 'music_orgfilename2',
                        'title3', 'composer3', 'movements3', 'perf_time3', 'video_url3', 'music_orgfilename3',
                        'title4', 'composer4', 'movements4', 'perf_time4', 'video_url4', 'music_orgfilename4',
                        'title5', 'composer5', 'movements5', 'perf_time5', 'video_url5', 'music_orgfilename5',
                        'title6', 'composer6', 'movements6', 'perf_time6', 'video_url6', 'music_orgfilename6',
                        'title7', 'composer7', 'movements7', 'perf_time7', 'video_url7', 'music_orgfilename7',
                        'title8', 'composer8', 'movements8', 'perf_time8', 'video_url8', 'music_orgfilename8',
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
            $festival['registrations_copy'] = '';
            if( isset($rc['registrations']) ) {
                $festival['registrations'] = $rc['registrations'];
                $festival['nplists']['registrations'] = array();
                $total = 0;
                $festival['registrations_copy'] = "<table cellpadding=2 cellspacing=0>";
                foreach($festival['registrations'] as $iid => $registration) {
                    $festival['nplists']['registrations'][] = $registration['id'];
                    $festival['registrations_copy'] .= '<tr><td>' . $registration['class_code'] . '</td><td>' . $registration['title1'] . '</td><td>' . $registration['perf_time1'] . "</td></tr>\n";
                }
                $festival['registrations_copy'] .= "</table>";
            } else {
                $festival['registrations'] = array();
                $festival['nplists']['registrations'] = array();
            }

            //
            // Get the list of teachers and number of registrations
            //
            $strsql = "SELECT registrations.teacher_customer_id, "
                . "c.display_name, ";
            if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) ) {
                if( $args['ipv'] == 'inperson' ) {
                    $strsql .= "SUM(IF(registrations.participation=0,1,0)) AS num_registrations ";
                } elseif( $args['ipv'] == 'virtual' ) {
                    $strsql .= "SUM(registrations.participation) AS num_registrations ";
                } else {
                    $strsql .= "COUNT(registrations.id) AS num_registrations ";
                }
            } else {
                $strsql .= "COUNT(registrations.id) AS num_registrations ";
            }
            $strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "LEFT JOIN ciniki_customers AS c ON ("
                    . "registrations.teacher_customer_id = c.id "
                    . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.teacher_customer_id != 0 "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY registrations.teacher_customer_id "
                . "ORDER BY c.display_name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'teachers', 'fname'=>'teacher_customer_id', 'fields'=>array('id'=>'teacher_customer_id', 'display_name', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['teachers']) ) {
                $festival['registration_teachers'] = $rc['teachers'];
            }

            //
            // Get the list of accompanists and number of registrations
            //
            $strsql = "SELECT registrations.accompanist_customer_id, "
                . "c.display_name, ";
            if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) ) {
                if( $args['ipv'] == 'inperson' ) {
                    $strsql .= "SUM(IF(registrations.participation=0,1,0)) AS num_registrations ";
                } elseif( $args['ipv'] == 'virtual' ) {
                    $strsql .= "SUM(registrations.participation) AS num_registrations ";
                } else {
                    $strsql .= "COUNT(registrations.id) AS num_registrations ";
                }
            } else {
                $strsql .= "COUNT(registrations.id) AS num_registrations ";
            }
            $strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "LEFT JOIN ciniki_customers AS c ON ("
                    . "registrations.accompanist_customer_id = c.id "
                    . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.accompanist_customer_id != 0 "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY registrations.accompanist_customer_id "
                . "ORDER BY c.display_name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'accompanists', 'fname'=>'accompanist_customer_id', 'fields'=>array('id'=>'accompanist_customer_id', 'display_name', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['accompanists']) ) {
                $festival['registration_accompanists'] = $rc['accompanists'];
            }
            //
            // Get the list of tags
            //
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x2000) ) {
                $strsql = "SELECT tags.tag_name AS name, ";
                if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) ) {
                    if( $args['ipv'] == 'inperson' ) {
                        $strsql .= "SUM(IF(registrations.participation=0,1,0)) AS num_registrations ";
                    } elseif( $args['ipv'] == 'virtual' ) {
                        $strsql .= "SUM(registrations.participation) AS num_registrations ";
                    } else {
                        $strsql .= "COUNT(registrations.id) AS num_registrations ";
                    }
                } else {
                    $strsql .= "COUNT(registrations.id) AS num_registrations ";
                }
                $strsql .= "FROM ciniki_musicfestival_registration_tags AS tags "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "tags.registration_id = registrations.id "
                        . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY tags.tag_name "
                    . "ORDER BY tags.tag_name "
                    . "";
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'tags', 'fname'=>'name', 'fields'=>array('name', 'num_registrations')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['tags']) ) {
                    $festival['registration_tags'] = $rc['tags'];
                }
            }
            //
            // Get the list of members
            //
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
                $strsql = "SELECT members.id, "
                    . "members.name, "
                    . "COUNT(registrations.id) AS num_registrations "
                    . "FROM ciniki_musicfestivals_members AS members "
                    . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "members.id = registrations.member_id ";
                        if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) ) {
                            if( $args['ipv'] == 'inperson' ) {
                                $strsql .= "AND registrations.participation = 0 ";
                            } elseif( $args['ipv'] == 'virtual' ) {
                                $strsql .= "AND registrations.participation = 1 ";
                            }
                        }
                        $strsql .= "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE members.status = 10 "
                    . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY members.id "
                    . "ORDER BY members.name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_registrations')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.619', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
                }
                $festival['registration_members'] = isset($rc['members']) ? $rc['members'] : array();
            }
        }

        //
        // Get the schedule
        //
        if( isset($args['schedule']) && $args['schedule'] == 'yes' ) {
            //
            // Get the list of schedule sections
            //
            $strsql = "SELECT sections.id, "
                . "sections.festival_id, "
                . "sections.name, "
                . "sections.sequence, "
                . "sections.flags, "
                . "sections.flags AS options, "
                . "sections.adjudicator1_id, "
                . "sections.adjudicator2_id, "
                . "sections.adjudicator3_id "
                . "FROM ciniki_musicfestival_schedule_sections AS sections "
                . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY sections.sequence, sections.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'schedulesections', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'name', 'sequence', 'flags', 'options', 
                        'adjudicator1_id', 'adjudicator2_id', 'adjudicator3_id',
                        ),
                    'flags' => array('options'=>$maps['schedulesection']['flags']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['schedulesections']) ) {
                $festival['schedule_sections'] = $rc['schedulesections'];
                $nplists['schedule_sections'] = array();
                foreach($festival['schedule_sections'] as $iid => $schedulesection) {
                    $nplists['schedule_sections'][] = $schedulesection['id'];
                    if( isset($args['ssection_id']) && $args['ssection_id'] == $schedulesection['id'] ) {
                        $requested_section = $schedulesection;
                    }
                    $festival['schedule_sections'][$iid]['options'] == '';

                }
            } else {
                $festival['schedule_sections'] = array();
                $nplists['schedule_sections'] = array();
            }

            //
            // Get the list of schedule section divisions
            //
            if( isset($args['ssection_id']) && $args['ssection_id'] == 'unscheduled' ) {
                $strsql = "SELECT registrations.id, ";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $strsql .= "registrations.pn_display_name AS display_name, ";
                } else {
                    $strsql .= "registrations.display_name, ";
                }
                $strsql .= "registrations.title1, "
                    . "registrations.status, "
                    . "registrations.status AS status_text, "
                    . "classes.code AS class_code, "
                    . "registrations.timeslot_id "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
/*                    . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "(registrations.class_id = timeslots.class1_id "
                            . "OR registrations.class_id = timeslots.class2_id "
                            . "OR registrations.class_id = timeslots.class3_id "
                            . "OR registrations.class_id = timeslots.class4_id "
                            . "OR registrations.class_id = timeslots.class5_id "
                            . ") "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") " */
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.timeslot_id = 0 "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                    . "HAVING ISNULL(timeslot_id) "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'registrations', 'fname'=>'id', 
                        'fields'=>array('id', 'display_name', 'title1', 'class_code', 'status', 'status_text'),
                        'maps'=>array('status_text'=>$maps['registration']['status']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.172', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
                }
                $festival['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
            }
            elseif( isset($args['ssection_id']) && $args['ssection_id'] > 0 ) {
                $strsql = "SELECT divisions.id, "
                    . "divisions.festival_id, "
                    . "divisions.ssection_id, "
                    . "divisions.name, "
                    . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
                    . "divisions.address, "
                    . "MIN(timeslots.slot_time) AS first_timeslot "
                    . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                    . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "divisions.id = timeslots.sdivision_id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND divisions.ssection_id = '" . ciniki_core_dbQuote($ciniki, $args['ssection_id']) . "' "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY divisions.id "
                    . "ORDER BY divisions.division_date, first_timeslot "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduledivisions', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'ssection_id', 'name', 'division_date_text', 'address')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['scheduledivisions']) ) {
                    $festival['schedule_divisions'] = $rc['scheduledivisions'];
                    $nplists['schedule_divisions'] = array();
                    foreach($festival['schedule_divisions'] as $iid => $scheduledivision) {
                        $nplists['schedule_divisions'][] = $scheduledivision['id'];
                    }
                } else {
                    $festival['schedule_divisions'] = array();
                    $nplists['schedule_divisions'] = array();
                }
            }

            //
            // Get the list of schedule section divisions timeslots
            //
            if( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 
                && isset($args['comments']) && $args['comments'] == 'yes'
                && isset($requested_section)
                ) {
                $strsql = "SELECT "
                    . "timeslots.id AS timeslot_id, "
                    . "timeslots.uuid AS timeslot_uuid, "
                    . "IF(timeslots.name='', IFNULL(class1.name, ''), timeslots.name) AS timeslot_name, "
                    . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                    . "timeslots.class1_id, "
/*                    . "timeslots.class2_id, "
                    . "timeslots.class3_id, "
                    . "timeslots.class4_id, "
                    . "timeslots.class5_id, "
                    . "IFNULL(class1.name, '') AS class1_name, "
                    . "IFNULL(class2.name, '') AS class2_name, "
                    . "IFNULL(class3.name, '') AS class3_name, "
                    . "IFNULL(class4.name, '') AS class4_name, "
                    . "IFNULL(class5.name, '') AS class5_name, " */
            //        . "timeslots.name AS timeslot_name, "
                    . "timeslots.description, "
                    . "registrations.id AS reg_id, "
                    . "registrations.uuid AS reg_uuid, ";
//                    . "registrations.display_name, "
//                    . "registrations.public_name, ";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $strsql .= "registrations.pn_display_name AS display_name, "
                        . "registrations.pn_public_name AS public_name, ";
                } else {
                    $strsql .= "registrations.display_name, "
                        . "registrations.public_name, ";
                }
                $strsql .= "registrations.title1, "
                    . "registrations.participation, "
                    . "registrations.video_url1, "
                    . "registrations.video_url2, "
                    . "registrations.video_url3, "
                    . "registrations.music_orgfilename1, "
                    . "registrations.music_orgfilename2, "
                    . "registrations.music_orgfilename3, "
                    . "IFNULL(comments.adjudicator_id, 0) AS adjudicator_id, "
                    . "IFNULL(comments.id, 0) AS comment_id, "
                    . "IFNULL(comments.comments, '') AS comments, "
                    . "IFNULL(comments.grade, '') AS grade, "
                    . "IFNULL(comments.score, '') AS score "
                    . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                    . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
                        . "timeslots.class1_id = class1.id " 
                        . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
/*                    . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
                        . "timeslots.class2_id = class2.id " 
                        . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
                        . "timeslots.class3_id = class3.id " 
                        . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_classes AS class4 ON ("
                        . "timeslots.class4_id = class4.id " 
                        . "AND class4.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_classes AS class5 ON ("
                        . "timeslots.class5_id = class5.id " 
                        . "AND class5.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") " */
                    . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
/*                        . "(timeslots.class1_id = registrations.class_id "  
                            . "OR timeslots.class2_id = registrations.class_id "
                            . "OR timeslots.class3_id = registrations.class_id "
                            . "OR timeslots.class4_id = registrations.class_id "
                            . "OR timeslots.class5_id = registrations.class_id "
                            . ") " */
//                        . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
                        . "timeslots.id = registrations.timeslot_id "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_comments AS comments ON ("
                        . "registrations.id = comments.registration_id "
                        . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                        . "AND timeslots.class1_id > 0 "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY slot_time, registrations.display_name, comments.adjudicator_id "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'timeslots', 'fname'=>'timeslot_id', 
                        'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                            'class1_id', //'class2_id', 'class3_id', 'class4_id', 'class5_id',
                            'description', //'class1_name', 'class2_name', 'class3_name', 'class4_name', 'class5_name',
                            )),
                    array('container'=>'registrations', 'fname'=>'reg_id', 
                        'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name',
                            'title'=>'title1', 
                            'participation', 'video_url1', 'video_url2', 'video_url3', 
                            'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3',
                            )),
                    array('container'=>'comments', 'fname'=>'comment_id', 
                        'fields'=>array('id'=>'comment_id', 'adjudicator_id', 'comments', 'grade', 'score')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $festival['timeslot_comments'] = isset($rc['timeslots']) ? $rc['timeslots'] : array();
                foreach($festival['timeslot_comments'] as $tid => $timeslot) {
                    $num_completed = array();
                    $num_registrations = 0;
                    if( isset($timeslot['registrations']) ) {
                        foreach($timeslot['registrations'] as $rid => $registration) {
                            $num_registrations++;
                            if( isset($registration['comments']) ) {
                                foreach($registration['comments'] as $comment) {
                                    if( $comment['comments'] != '' && $comment['score'] != '' ) {
                                        if( !isset($num_completed[$comment['adjudicator_id']]) ) {
                                            $num_completed[$comment['adjudicator_id']] = 1;
                                        } else {
                                            $num_completed[$comment['adjudicator_id']]++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //
                    // For each adjudicator, add the
                    //
                    for($i = 1;$i < 4;$i++) {
                        if( $requested_section["adjudicator{$i}_id"] > 0 ) {
                            $adjudicator_completed = isset($num_completed[$requested_section["adjudicator{$i}_id"]]) ? $num_completed[$requested_section["adjudicator{$i}_id"]] : 0;
                            $festival['timeslot_comments'][$tid]['status' . $i] = $adjudicator_completed . ' of ' . $num_registrations;
                        } else {
                            $festival['timeslot_comments'][$tid]['status' . $i] = '';
                        }
                    }
                }
            }
            elseif( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 
                && isset($args['photos']) && $args['photos'] == 'yes'
                && isset($requested_section)
                ) {
                $strsql = "SELECT timeslots.id, "
                    . "timeslots.festival_id, "
                    . "timeslots.sdivision_id, "
                    . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                    . "timeslots.class1_id, "
                    . "timeslots.class2_id, "
                    . "timeslots.class3_id, "
                    . "timeslots.class4_id, "
                    . "timeslots.class5_id, "
                    . "class1.name AS class1_name, "
                    . "timeslots.name, "
                    . "timeslots.description, "
                    . "images.id AS timeslot_image_id, "
                    . "images.image_id, "
                    . "images.last_updated "
                    . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                    . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
                        . "timeslots.class1_id = class1.id " 
                        . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_timeslot_images AS images ON ("
                        . "timeslots.id = images.timeslot_id "
                        . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                    . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "ORDER BY slot_time, images.sequence "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduletimeslots', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'sdivision_id', 'slot_time_text', 'class1_id', 
                            'name', 'description', 'class1_name'),
                        ),
                    array('container'=>'images', 'fname'=>'image_id', 
                        'fields'=>array('timeslot_image_id', 'image_id', 'last_updated'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['scheduletimeslots']) ) {
                    $festival['timeslot_photos'] = $rc['scheduletimeslots'];
                    $nplists['timeslot_photos'] = array();
                    foreach($festival['timeslot_photos'] as $tid => $scheduletimeslot) {
                        //
                        // Check if class is set, then use class name
                        //
                        if( $scheduletimeslot['class1_id'] > 0 ) {
                            if( $scheduletimeslot['name'] == '' && $scheduletimeslot['class1_name'] != '' ) {
                                $festival['timeslot_photos'][$tid]['name'] = $scheduletimeslot['class1_name'];
                            }
                            $festival['timeslot_photos'][$tid]['description'] .= ($festival['timeslot_photos'][$tid]['description'] != '' ? "\n":'');
                        }
                        $nplists['timeslot_photos'][] = $scheduletimeslot['id'];

                        //
                        // Create image thumbnails
                        //
                        if( isset($scheduletimeslot['images']) ) {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadThumbnail');
                            foreach($scheduletimeslot['images'] as $iid => $image) {
                                $rc = ciniki_images_hooks_loadThumbnail($ciniki, $args['tnid'], array(
                                    'image_id' => $image['image_id'],
                                    'maxlength' => 50,
                                    'last_updated' => $image['last_updated'],
                                    ));
                                if( $rc['stat'] != 'ok' ) {
                                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.244', 'msg'=>'No thumbnail', 'err'=>$rc['err']));
                                }
                                $festival['timeslot_photos'][$tid]['images'][$iid]['image'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                            }
                        }
                    }
                } else {
                    $festival['timeslot_photos'] = array();
                    $nplists['timeslot_photos'] = array();
                }
            }   
            elseif( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 ) {
                $strsql = "SELECT timeslots.id, "
                    . "timeslots.festival_id, "
                    . "timeslots.sdivision_id, "
                    . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                    . "timeslots.class1_id, "
                    . "timeslots.class2_id, "
                    . "timeslots.class3_id, "
                    . "timeslots.class4_id, "
                    . "timeslots.class5_id, "
                    . "class1.name AS class1_name, "
                    . "timeslots.name, "
                    . "timeslots.description, "
                    . "registrations.id AS reg_id, "
                    . "registrations.title1, "
                    . "registrations.perf_time1, "
                    . "registrations.title2, "
                    . "registrations.perf_time2, "
                    . "registrations.title3, "
                    . "registrations.perf_time3, "
                    . "";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $strsql .= "registrations.pn_display_name AS display_name ";
                } else {
                    $strsql .= "registrations.display_name ";
                }
                $strsql .= "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                    . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
                        . "timeslots.class1_id = class1.id " 
                        . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
/*                        . "(timeslots.class1_id = registrations.class_id " 
                            . "OR timeslots.class2_id = registrations.class_id " 
                            . "OR timeslots.class3_id = registrations.class_id " 
                            . "OR timeslots.class4_id = registrations.class_id " 
                            . "OR timeslots.class5_id = registrations.class_id " 
                            . ") "
                        . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) " */
                        . "timeslots.id = registrations.timeslot_id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                    . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "ORDER BY slot_time, registrations.timeslot_sequence, registrations.display_name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduletimeslots', 'fname'=>'id', 'fields'=>array('id', 'festival_id', 'sdivision_id', 'slot_time_text', 'class1_id', 'name', 'description', 'class1_name')),
                    array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name',
                        'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3',
                        )),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['scheduletimeslots']) ) {
                    $festival['schedule_timeslots'] = $rc['scheduletimeslots'];
                    $nplists['schedule_timeslots'] = array();
                    foreach($festival['schedule_timeslots'] as $iid => $scheduletimeslot) {
                        $perf_time = '';
                        //
                        // Check if class is set, then use class name
                        //
                        if( $scheduletimeslot['class1_id'] > 0 ) {
                            if( $scheduletimeslot['name'] == '' && $scheduletimeslot['class1_name'] != '' ) {
                                $festival['schedule_timeslots'][$iid]['name'] = $scheduletimeslot['class1_name'];
                            }
                            $festival['schedule_timeslots'][$iid]['description'] .= ($festival['schedule_timeslots'][$iid]['description'] != '' ? "\n":'');
                            //
                            // Add the registrations to the description
                            //
                            if( isset($scheduletimeslot['registrations']) ) {
                                $perf_time = 0;
                                foreach($scheduletimeslot['registrations'] as $reg) {
                                    $ptime = 0;
                                    for($i = 1; $i <= 3; $i++) {
                                        if( $reg["perf_time{$i}"] != '' && $reg["perf_time{$i}"] > 0 ) {
                                            $ptime += $reg["perf_time{$i}"];
                                        }
                                    }
                                    $perf_time += $ptime;
                                    $ptime_text = ' [?]';
                                    if( $ptime > 0 ) {
                                        $ptime_text = ' [' . intval($ptime/60) . ':' . str_pad(($ptime%60), 2, '0', STR_PAD_LEFT) . ']';
                                    }
                                    $festival['schedule_timeslots'][$iid]['description'] .= ($festival['schedule_timeslots'][$iid]['description'] != '' ? "\n":'') . $reg['name'] . $ptime_text;
                                }
                                unset($festival['schedule_timeslots'][$iid]['registrations']);
                            }
                        }
                        if( $perf_time != '' && $perf_time > 0 ) {
                            $festival['schedule_timeslots'][$iid]['perf_time_text'] = '[' . intval($perf_time/60) . ':' . str_pad(($perf_time%60), 2, '0', STR_PAD_LEFT) . ']';
                        } elseif( $perf_time != '' && $perf_time == 0 ) {
                            $festival['schedule_timeslots'][$iid]['perf_time_text'] = '[?]';
                        }
                        $nplists['schedule_timeslots'][] = $scheduletimeslot['id'];
                    }
                } else {
                    $festival['schedule_timeslots'] = array();
                    $nplists['schedule_timeslots'] = array();
                }
            }
        }

        //
        // Get the list of competitors
        //
        if( isset($args['competitors']) && $args['competitors'] == 'yes' ) {
            $strsql = "SELECT competitors.id, "
                . "competitors.festival_id, "
                . "competitors.name, ";
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                $strsql .= "competitors.pronoun, ";
            } else {
                $strsql .= "'' AS pronoun, ";
            }
            $strsql .= "IF((competitors.flags&0x01) = 0x01, 'Signed', '') AS waiver_signed, "
                . "competitors.city, "
                . "competitors.province, "
                . "IFNULL(classes.code, '') AS classcodes "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND ("
                        . "registrations.competitor1_id = competitors.id "
                        . "OR registrations.competitor2_id = competitors.id "
                        . "OR registrations.competitor3_id = competitors.id "
                        . "OR registrations.competitor4_id = competitors.id "
                        . "OR registrations.competitor5_id = competitors.id "
                        . ") "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "registrations.class_id = classes.id "
                    . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY name ";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'competitors', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'name', 'pronoun', 'waiver_signed', 
                        'city', 'province', 'classcodes',
                        ),
                    'dlists'=>array('classcodes'=>', '),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival['competitors'] = isset($rc['competitors']) ? $rc['competitors'] : array();

            $festival['competitor_cities'] = array();
            $festival['competitor_provinces'] = array();
            $total_comp = count($festival['competitors']);
            $blank_city_prov = 0;
            $blank_prov = 0;
            foreach($festival['competitors'] as $cid => $comp) {
                $city_prov = $comp['city'] . ', ' . $comp['province'];
                if( $city_prov == ', ' ) {
                    $city_prov = '';
                }
                if( $city_prov == '' ) {
                    $blank_city_prov++;
                }
                elseif( isset($festival['competitor_cities'][$city_prov]) ) {
                    $festival['competitor_cities'][$city_prov]['num_competitors']++;
                } 
                else {
                    $festival['competitor_cities'][$city_prov] = array(
                        'name' => $comp['city'] . ', ' . $comp['province'],
                        'city' => $comp['city'],
                        'province' => $comp['province'],
                        'num_competitors' => 1,
                        );
                }
                if( $comp['province'] == '' ) {
                    $blank_prov++;
                }
                elseif( isset($festival['competitor_provinces'][$comp['province']]) ) {
                    $festival['competitor_provinces'][$comp['province']]['num_competitors']++;
                } 
                else {
                    $festival['competitor_provinces'][$comp['province']] = array(
                        'name' => $comp['province'],
                        'province' => $comp['province'],
                        'num_competitors' => 1,
                        );
                }
                
                if( isset($args['city_prov']) && $args['city_prov'] != 'All' && $args['city_prov'] != '' ) {
                    if( ($args['city_prov'] == 'Blank' && $city_prov != '')
                        || ($args['city_prov'] != 'Blank' && $city_prov != $args['city_prov'])
                        ) {
                        unset($festival['competitors'][$cid]);
                    }
                }
                if( isset($args['province']) && $args['province'] != 'All' && $args['province'] != '' ) {
                    if( ($args['province'] == 'Blank' && $comp['province'] != '')
                        || ($args['province'] != 'Blank' && $comp['province'] != $args['province'])
                        ) {
                        unset($festival['competitors'][$cid]);
                    }
                }
            }
            uasort($festival['competitor_cities'], function($a, $b) {
                return strcmp($a['name'], $b['name']);
                });
            if( $blank_city_prov > 0 ) {
                array_unshift($festival['competitor_cities'], array(
                    'name' => 'Blank',
                    'num_competitors' => $blank_city_prov,
                    ));
            }
            array_unshift($festival['competitor_cities'], array(
                'name' => 'All',
                'num_competitors' => $total_comp,
                ));
            uasort($festival['competitor_provinces'], function($a, $b) {
                return strcmp($a['name'], $b['name']);
                });
            if( $blank_prov > 0 ) {
                array_unshift($festival['competitor_provinces'], array(
                    'name' => 'Blank',
                    'num_competitors' => $blank_prov,
                    ));
            }
            array_unshift($festival['competitor_provinces'], array(
                'name' => 'All',
                'num_competitors' => $total_comp,
                ));
        }
        
        //
        // Get the list of invoices for this festival
        //
        if( isset($args['invoices']) && $args['invoices'] == 'yes' ) {
            //
            // Load maps
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
            $rc = ciniki_sapos_maps($ciniki);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $sapos_maps = $rc['maps'];
            $sapos_maps['invoice']['status'][50] = 'Paid';

            //
            // Get the list of statuses
            //
            $strsql = "SELECT CONCAT_WS('.', invoices.invoice_type, invoices.status) AS typestatus, "
                . "COUNT(DISTINCT invoices.id) AS num_invoices "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                    . "registrations.invoice_id = invoices.id "
                    . "AND invoices.invoice_type <> 20 "
                    . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY invoices.status "
                . "ORDER BY invoices.status "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'statuses', 'fname'=>'typestatus', 'fields'=>array('typestatus', 'num_invoices')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.565', 'msg'=>'Unable to load statuses', 'err'=>$rc['err']));
            }
            $festival['invoice_statuses'] = isset($rc['statuses']) ? $rc['statuses'] : array();
            $num_invoices = 0;
            foreach($festival['invoice_statuses'] as $sid => $s) {
                $num_invoices += $s['num_invoices'];
                $festival['invoice_statuses'][$sid]['status_text'] = $sapos_maps['invoice']['typestatus'][$s['typestatus']];
            }
            array_unshift($festival['invoice_statuses'], array(
                'typestatus' => '',
                'status_text' => 'All',
                'num_invoices' => $num_invoices,
                ));

            // 
            // Get the invoices
            //
            $strsql = "SELECT invoices.id, "
                . "invoices.invoice_number, "
                . "invoices.status, "
                . "invoices.status AS status_text, "
                . "invoices.total_amount, "
                . "invoices.balance_amount, "
                . "registrations.display_name AS competitor_names, "
                . "customers.display_name AS customer_name "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                    . "registrations.invoice_id = invoices.id "
                    . "AND invoices.invoice_type <> 20 ";
            if( isset($args['invoice_typestatus']) && $args['invoice_typestatus'] != '' && $args['invoice_typestatus'] > 0 ) {
                list($itype, $istatus) = explode('.', $args['invoice_typestatus']);
                $strsql .= "AND invoices.invoice_type = '" . ciniki_core_dbQuote($ciniki, $itype) . "' ";
                $strsql .= "AND invoices.status = '" . ciniki_core_dbQuote($ciniki, $istatus) . "' ";
            }
            $strsql .= "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "invoices.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY invoice_number "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'invoices', 'fname'=>'id', 
                    'fields'=>array('id', 'invoice_number', 'status', 'status_text', 'total_amount', 'balance_amount',
                        'customer_name', 'competitor_names',
                        ),
                    'dlists'=>array('competitor_names'=>', '),
                    'maps'=>array('status_text'=>$sapos_maps['invoice']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.564', 'msg'=>'Unable to load invoices', 'err'=>$rc['err']));
            }
            $festival['invoices'] = isset($rc['invoices']) ? $rc['invoices'] : array();
        }

        //
        // Get the list of adjudicators
        //
        if( isset($args['adjudicators']) && $args['adjudicators'] == 'yes' ) {
            $strsql = "SELECT ciniki_musicfestival_adjudicators.id, "
                . "ciniki_musicfestival_adjudicators.festival_id, "
                . "ciniki_musicfestival_adjudicators.customer_id, "
                . "ciniki_customers.display_name "
                . "FROM ciniki_musicfestival_adjudicators "
                . "LEFT JOIN ciniki_customers ON ("
                    . "ciniki_musicfestival_adjudicators.customer_id = ciniki_customers.id "
                    . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_musicfestival_adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND ciniki_musicfestival_adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            if( isset($args['comments']) && $args['comments'] == 'yes' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
                $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'adjudicators', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name')),
                    ));
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'adjudicators', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name')),
                    ));
            }
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['adjudicators']) ) {
                $festival['adjudicators'] = $rc['adjudicators'];
                foreach($festival['adjudicators'] as $iid => $adjudicator) {
                    $festival['nplists']['adjudicators'][] = $adjudicator['id'];
                }
            } else {
                $festival['adjudicators'] = array();
            }
        }

        //
        // Get the adjudicator comments
        //

        //
        // Get the list of files
        //
        if( isset($args['files']) && $args['files'] == 'yes' ) {
            $strsql = "SELECT id, name, webflags "
                . "FROM ciniki_musicfestival_files "
                . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name', 'webflags')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['files']) ) {
                $festival['files'] = $rc['files'];
            } else {
                $festival['files'] = array();
            }
        }

        //
        // Get any lists, list sections and/or list entries
        //
        if( isset($args['lists']) && $args['lists'] == 'yes'
            && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x20)
            ) {
            $strsql = "SELECT id, "
                . "name "
                . "FROM ciniki_musicfestival_lists "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "ORDER BY name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'lists', 'fname'=>'id', 'fields'=>array('id', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival['lists'] = isset($rc['lists']) ? $rc['lists'] : array();

            //
            // Check if need query for list sections
            //
            if( isset($args['list_id']) && $args['list_id'] > 0 ) {
                $strsql = "SELECT id, "
                    . "sequence, "
                    . "name "
                    . "FROM ciniki_musicfestival_list_sections "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND list_id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
                    . "ORDER BY sequence "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'listsections', 'fname'=>'id', 'fields'=>array('id', 'sequence', 'name')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $festival['listsections'] = isset($rc['listsections']) ? $rc['listsections'] : array();
            }

            //
            // Check if need query for list sections
            //
            if( isset($args['listsection_id']) && $args['listsection_id'] > 0 ) {
                $strsql = "SELECT id, "
                    . "sequence, "
                    . "award, "
                    . "amount, "
                    . "donor, "
                    . "winner "
                    . "FROM ciniki_musicfestival_list_entries "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND section_id = '" . ciniki_core_dbQuote($ciniki, $args['listsection_id']) . "' "
                    . "ORDER BY sequence "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'listentries', 'fname'=>'id', 
                        'fields'=>array('id', 'sequence', 'award', 'amount', 'donor', 'winner')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $festival['listentries'] = isset($rc['listentries']) ? $rc['listentries'] : array();
            }
        }

        //
        // Get any certificates for the festival
        //
        if( isset($args['certificates']) && $args['certificates'] == 'yes' ) {
            $strsql = "SELECT certificates.id, "
                . "certificates.festival_id, "
                . "certificates.name, "
                . "certificates.section_id, "
                . "IFNULL(sections.name, 'All') AS section_name, "
                . "certificates.min_score "
                . "FROM ciniki_musicfestival_certificates AS certificates "
                . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "certificates.section_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE certificates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND certificates.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'certificates', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'name', 'section_id', 'section_name', 'min_score')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival['certificates'] = isset($rc['certificates']) ? $rc['certificates'] : array();
        }

        //
        // Get the list of messages if requested
        //
        if( isset($args['messages']) && $args['messages'] == 'yes' ) {
            //
            // Get the count on statuses
            //
            $strsql = "SELECT messages.status, COUNT(*) AS num_messages "
                . "FROM ciniki_musicfestival_messages AS messages "
                . "WHERE messages.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY messages.status "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
            $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'statuses');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.476', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $statuses = isset($rc['statuses']) ? $rc['statuses'] : array();
            $festival['message_statuses'] = array(
                array(
                    'label' => 'Draft', 
                    'status' => 10, 
                    'num_messages' => (isset($statuses['10']) ? $statuses['10'] : 0),
                    ),
                array(
                    'label' => 'Scheduled', 
                    'status' => 30, 
                    'num_messages' => (isset($statuses['30']) ? $statuses['30'] : 0),
                    ),
                array(
                    'label' => 'Sent', 
                    'status' => 50, 
                    'num_messages' => (isset($statuses['50']) ? $statuses['50'] : 0),
                    ),
                );

            //
            // Get the list of messages
            //
            if( !isset($args['messages_status']) ) {
                $args['messages_status'] = 10;
            }
            $strsql = "SELECT messages.id, "
                . "messages.subject, ";
            if( $args['messages_status'] == 50 ) {
                $strsql .= "DATE_FORMAT(messages.dt_sent, '%b %e, %Y %l:%i %p') AS date_text ";
            } elseif( $args['messages_status'] == 30 ) {
                $strsql .= "DATE_FORMAT(messages.dt_scheduled, '%b %e, %Y %l:%i %p') AS date_text ";
            } else {
                $strsql .= "DATE_FORMAT(messages.date_added, '%b %e, %Y %l:%i %p') AS date_text ";
            }
            $strsql .= "FROM ciniki_musicfestival_messages AS messages "
                . "WHERE messages.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND messages.status = '" . ciniki_core_dbQuote($ciniki, $args['messages_status']) . "' "
                . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            if( $args['messages_status'] == 50 ) {
                $strsql .= "ORDER BY dt_sent DESC ";
            } elseif( $args['messages_status'] == 30 ) {
                $strsql .= "ORDER BY dt_scheduled DESC ";
            } else {
                $strsql .= "ORDER BY date_added DESC ";
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'messages', 'fname'=>'id', 
                    'fields'=>array('id', 'subject', 'date_text')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.477', 'msg'=>'Unable to load messages', 'err'=>$rc['err']));
            }
            $festival['messages'] = isset($rc['messages']) ? $rc['messages'] : array();
        }

        //
        // Get the list of members
        //
        if( isset($args['members']) && $args['members'] == 'yes' 
            && (!isset($args['recommendations']) || $args['recommendations'] != 'yes')
            ) {
            $strsql = "SELECT members.id, "
                . "members.name, "
                . "members.category, "
                . "members.status, "
                . "members.status AS status_text, "
                . "IFNULL(fmembers.reg_start_dt, '') AS reg_start_dt_display, "
                . "IFNULL(fmembers.reg_end_dt, '') AS reg_end_dt_display, "
                . "IFNULL(fmembers.latedays, '') AS latedays, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestivals_members AS members "
                . "LEFT JOIN ciniki_musicfestival_members AS fmembers ON ("
                    . "members.id = fmembers.member_id "
                    . "AND fmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND fmembers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "members.id = registrations.member_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE members.status < 90 " // Active, Closed
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY members.id "
                . "ORDER BY members.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'members', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'category', 'status', 'reg_start_dt_display', 'reg_end_dt_display', 'latedays',
                        'num_registrations',
                        ),
                    'utctotz'=>array(
                        'reg_start_dt_display' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                        'reg_end_dt_display' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                        ),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.581', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
            }
            if( isset($rc['members']) ) {
                $festival['members'] = $rc['members'];
                $festival['members_ids'] = array();
                foreach($festival['members'] as $k => $v) {
                    $festival['members_ids'][] = $v['id'];
                }
            } else {
                $festival['members'] = array();
                $festival['members_ids'] = array();
            }
        }

        //
        // Get the list of emails if requested
        //
        if( isset($args['emails_list']) && $args['emails_list'] != '' ) {
            $emails = array();
            if( $args['emails_list'] == 'all' || $args['emails_list'] == 'competitors' ) {
                $strsql = "SELECT competitors.id, "    
                    . "competitors.name, "
                    . "competitors.email, "
                    . "customers.display_name AS customer_name, "
                    . "customer_emails.email AS customer_email "
                    . "FROM ciniki_musicfestival_competitors AS competitors "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . "AND ("
                            . "competitors.id = registrations.competitor1_id "
                            . "OR competitors.id = registrations.competitor2_id "
                            . "OR competitors.id = registrations.competitor3_id "
                            . "OR competitors.id = registrations.competitor4_id "
                            . "OR competitors.id = registrations.competitor5_id "
                        . ") "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_customers AS customers ON ( "
                        . "registrations.billing_customer_id = customers.id "
                        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_customer_emails AS customer_emails ON ( "
                        . "customers.id = customer_emails.customer_id "
                        . "AND customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                        . "";
                if( isset($args['section_id']) && $args['section_id'] > 0 ) {
                    $strsql .= "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                        . "classes.category_id = categories.id "
                        . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") ";
                }
                $strsql .= "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'competitors', 'fname'=>'id', 
                        'fields'=>array('id', 'name', 'email'),
                        ),
                    array('container'=>'billing', 'fname'=>'customer_email', 
                        'fields'=>array('name'=>'customer_name', 'email'=>'customer_email'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.316', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
                }
                $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();
                foreach($competitors as $competitor) {
                    if( !isset($emails[$competitor['email']]) ) {
                        $emails[$competitor['email']] = array(
                            'name' => $competitor['name'],
                            'email' => $competitor['email'],
                            );
                    }
                    if( isset($competitors['billing']) ) {
                        foreach($competitors['billing'] as $customer) {
                            if( !isset($emails[$customer['email']]) ) {
                                $emails[$customer['email']] = array(
                                    'name' => $customer['name'],
                                    'email' => $customer['email'],
                                    );
                            }
                        }
                    }
                }
            }
            if( $args['emails_list'] == 'all' || $args['emails_list'] == 'teachers' ) {
                $strsql = "SELECT teacher_emails.id, "    
                    . "teachers.display_name, "
                    . "teacher_emails.email "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "INNER JOIN ciniki_customers AS teachers ON ( "
                        . "registrations.teacher_customer_id = teachers.id "
                        . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "INNER JOIN ciniki_customer_emails AS teacher_emails ON ( "
                        . "teachers.id = teacher_emails.customer_id "
                        . "AND teacher_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                        . "";
                if( isset($args['section_id']) && $args['section_id'] > 0 ) {
                    $strsql .= "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                        . "classes.category_id = categories.id "
                        . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") ";
                }
                $strsql .= "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'teachers', 'fname'=>'email', 
                        'fields'=>array('name'=>'display_name', 'email'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.544', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
                }
                $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();
                foreach($teachers as $teacher) {
                    if( !isset($emails[$teacher['email']]) ) {
                        $emails[$teacher['email']] = array(
                            'name' => $teacher['name'],
                            'email' => $teacher['email'],
                            );
                    }
                }
            }
            uasort($emails, function($a, $b) {
                return strcmp($a['name'], $b['name']);
                });
            $festival['emails_list'] = $emails;
            $festival['emails_html'] = '';
            foreach($emails as $email) {
                $festival['emails_html'] .= ($festival['emails_html'] != '' ? ',<br/>' : '') 
                    . $email['name'] . ' &lt;' . $email['email'] . '&gt;';
            }
        }

        //
        // Get any sponsors for this festival, and that references for sponsors is enabled
        //
        if( isset($args['sponsors']) && $args['sponsors'] == 'yes' 
            && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x10)
            ) {
            $strsql = "SELECT sponsors.id, "
                . "sponsors.name, "
                . "sponsors.url, "
                . "sponsors.sequence, "
                . "sponsors.flags "
                . "FROM ciniki_musicfestival_sponsors AS sponsors "
                . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "ORDER BY sponsors.flags DESC, sponsors.sequence "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'sponsors', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'url', 'sequence', 'flags')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival['sponsors'] = isset($rc['sponsors']) ? $rc['sponsors'] : array();
            foreach($festival['sponsors'] as $sid => $sponsor) {
                $festival['sponsors'][$sid]['level'] = '';
                if( ($sponsor['flags']&0x01) == 0x01 ) {
                    $festival['sponsors'][$sid]['level'] = ($festival['sponsors'][$sid]['level'] != '' ? ', ' : '') . '1';
                }
                if( ($sponsor['flags']&0x02) == 0x02 ) {
                    $festival['sponsors'][$sid]['level'] = ($festival['sponsors'][$sid]['level'] != '' ? ', ' : '') . '2';
                }
                if( ($sponsor['flags']&0x04) == 0x04 ) {
                    $festival['sponsors'][$sid]['level'] = ($festival['sponsors'][$sid]['level'] != '' ? ', ' : '') . '3';
                }
            }
        }
        if( isset($args['sponsors']) && $args['sponsors'] == 'yes' 
            && isset($ciniki['tenant']['modules']['ciniki.sponsors']) 
            && ($ciniki['tenant']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'sponsorList');
            $rc = ciniki_sponsors_hooks_sponsorList($ciniki, $args['tnid'], 
                array('object'=>'ciniki.musicfestivals.festival', 'object_id'=>$args['festival_id']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['sponsors']) ) {
                $festival['sponsors-old'] = $rc['sponsors'];
            }
        }

        //
        // Get the recommendations 
        //
        if( isset($args['recommendations']) && $args['recommendations'] == 'yes' 
            && isset($args['sections']) && $args['sections'] == 'yes' 
            ) {
            if( !isset($args['section_id']) || $args['section_id'] == '' || $args['section_id'] == 0 ) {
                $args['section_id'] = $festival['sections'][0]['id'];
                $festival['section_id'] = $args['section_id'];
            }
            foreach($festival['sections'] as $section) {
                if( $section['id'] == $args['section_id'] ) {
                    break;
                }
            }

            //
            // Get the list of classes for this section
            //
            $strsql = "SELECT classes.id, "
                . "classes.code, "
                . "classes.name, "
                . "COUNT(entries.id) AS num_entries "
                . "FROM ciniki_musicfestival_categories AS categories "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                    . "classes.id = entries.class_id "
                    . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY classes.id "
                . "ORDER BY categories.sequence, classes.sequence, classes.code, classes.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'code', 'name', 'num_entries')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.597', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
            }
            $festival['recommendation_classes'] = isset($rc['classes']) ? $rc['classes'] : array();

            if( !isset($args['class_id']) || $args['class_id'] == '' || $args['class_id'] == 0 ) {
                $args['class_id'] = $festival['recommendation_classes'][0]['id'];
                $festival['class_id'] = $args['class_id'];
            }
            foreach($festival['recommendation_classes'] as $cid => $class) {
                $festival['recommendation_classes'][$cid]['name'] = str_replace($section['name'] . ' - ', '', $festival['recommendation_classes'][$cid]['name']);
                if( preg_match("/^([^-]+) - /", $section['name'], $m) ) {
                    if( $m[1] != '' ) {
                        $festival['recommendation_classes'][$cid]['name'] = str_replace($m[1] . ' - ', '', $festival['recommendation_classes'][$cid]['name']);
                    }
                }
            }

            //
            // Get the list of recommendations
            //
            if( isset($args['class_id']) && $args['class_id'] > 0 ) {
                $strsql = "SELECT entries.id, "
                    . "entries.position, "
                    . "entries.name, "
                    . "entries.mark, "
                    . "recommendations.id AS recommendation_id, "
                    . "recommendations.date_submitted, "
                    . "members.name AS member_name "
                    . "FROM ciniki_musicfestival_recommendation_entries AS entries "
                    . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                        . "entries.recommendation_id = recommendations.id "
                        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                        . "recommendations.member_id = members.id "
                        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE entries.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
                    . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY recommendations.date_submitted, entries.position "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'entries', 'fname'=>'id', 
                        'fields'=>array('id', 'recommendation_id', 'position', 'name', 'mark',
                            'date_submitted', 'member_name'),
                        'utctotz'=>array(
                            'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                            ),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.656', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
                }
                $festival['recommendation_entries'] = isset($rc['entries']) ? $rc['entries'] : array();
                foreach($festival['recommendation_entries'] as $eid => $entry) {
                    switch($entry['position']) {
                        case 1: $festival['recommendation_entries'][$eid]['position'] = '1st Recommendation'; break;
                        case 2: $festival['recommendation_entries'][$eid]['position'] = '2nd Recommendation'; break;
                        case 3: $festival['recommendation_entries'][$eid]['position'] = '3rd Recommendation'; break;
                        case 101: $festival['recommendation_entries'][$eid]['position'] = '1st Alternate'; break;
                        case 102: $festival['recommendation_entries'][$eid]['position'] = '2nd Alternate'; break;
                        case 103: $festival['recommendation_entries'][$eid]['position'] = '3rd Alternate'; break;
                    }
                }
            }
        }

        //
        // If recommendations organized by festival submission
        //
        if( isset($args['recommendations']) && $args['recommendations'] == 'yes' 
            && isset($args['members']) && $args['members'] == 'yes'
            ) {
            //
            // Get the list of members
            // NOTE: this is not based on festival, so only currently active members listed
            //       for historical purposes this should be change to based on what festivals
            //       are attached to the festival being requested
            //
            $strsql = "SELECT members.id, "
                . "members.name, "
                . "COUNT(entries.id) AS num_entries "
                . "FROM ciniki_musicfestivals_members AS members "
                . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                    . "members.id = recommendations.member_id "
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                    . "recommendations.id = entries.recommendation_id "
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE members.status = 10 " // Active
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY members.id "
                . "ORDER BY members.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_entries')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.599', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
            }
            $festival['recommendation_members'] = isset($rc['members']) ? $rc['members'] : array();

            //
            // Get the member submission
            //
            if( isset($args['member_id']) && $args['member_id'] > 0 ) {
                $strsql = "SELECT recommendations.id, "
                    . "recommendations.adjudicator_name, "
                    . "recommendations.section_id, "
                    . "recommendations.member_id, "
                    . "recommendations.date_submitted, "
                    . "sections.name AS section_name, "
                    . "COUNT(entries.id) AS num_entries "
                    . "FROM ciniki_musicfestival_recommendations AS recommendations "
                    . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                        . "recommendations.section_id = sections.id "
                        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                        . "recommendations.id = entries.recommendation_id "
                        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
                    . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY recommendations.id "
                    . "ORDER BY date_submitted "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'submissions', 'fname'=>'id', 
                        'fields'=>array(
                            'id', 'adjudicator_name', 'section_id', 'section_name', 'num_entries', 'date_submitted',
                            ),
                        'utctotz'=>array(
                            'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                            ),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.600', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
                }
                $festival['recommendation_submissions'] = isset($rc['submissions']) ? $rc['submissions'] : array();
            }
        }

        //
        // Get the number of registrations 
        //
        $strsql = "SELECT COUNT(id) "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'registrations');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['registrations']) ) {
            $festival['num_registrations'] = $rc['registrations'];
        }
    }

    return array('stat'=>'ok', 'festival'=>$festival, 'nplists'=>$nplists);
}
?>
