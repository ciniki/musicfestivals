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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'syllabus_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus'),
//        'syllabus'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus'),
        'sections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
        'groups'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Groups'),
        'groupname'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Group Name'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'classes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Classes'),
        'levels'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Levels'),
        'accolades'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accolades'),
        'accolade_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accolade'),
        'recommendations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicator Recommendations'),
        'recommendation_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommendation Status'),
        'recommendation_statuses'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommendation Statuses'),
        'member_submissions'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommendation Member Submissions'),
        'member_entries'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommendation Member Entries'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registrations'),
        'registrations_list'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration List'),
        'registration_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration Status'),
        'cr_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Change Request Status'),
//        'colour'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration Colour'),
        'schedule'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule'),
        'locations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Locations'),
        'location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'livegrid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Live Grid'),
        'dates'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Dates'),
        'date_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Date'),
        'adjudicator_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicator'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Section'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'accompanist_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accompanist'),
        'provincial_recommendations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincial Recommendations'),
        'provincials_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Status'),
        'provincials_class_code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Class Code'),
        'competitors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitors'),
        'city_prov'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitors From City Province'),
        'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitors From Province'),
        'invoices'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoices'),
        'invoice_typestatus'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Status'),
        'adjudicators'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicators'),
        'certificates'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Certificates'),
        'photos'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Photos'),
        'results'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Results'),
        'provincials'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials'),
        'comments'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Comments'),
        'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
        'lists'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Lists'),
        'list_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List'),
        'listsection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List Section'),
        'titlelists'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Approved Title Lists'),
        'titlelist_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Approved Title List'),
        'sponsors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sponsors'),
        'messages'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Messages List'),
        'messages_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Messages Status'),
        'members'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Members List'),
        'member_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Festival'),
        'emails_list'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Emails List'),
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        'entry_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Entry'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
        'lv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Live/Virtual Flags'),
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
        'registration_tag'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration Tag'),
        'statistics'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Statistics'),
        'ssam'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'SSAM'),
        'provincial_festivals'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Return Provincial Festivals'),
        'accolade_category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accolade Category'),
        'accolade_subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accolade Subcategory'),
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

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
    $nplists = [
        'sections' => [],
        'categories' => [],
        'classes' => [],
        'levels' => [],
        'registrations' => [],
        'schedule_sections' => [],
        'schedule_divisions' => [],
        'schedule_timeslots' => [],
        'adjudicators' => [],
        'locations' => [],
        'files' => [],
        'sponsors' => [],
        'submissions' => [],
        ];

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
            . "ciniki_musicfestivals.titles_end_dt, "
            . "ciniki_musicfestivals.accompanist_end_dt, "
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
                'fields'=>array('id', 'name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 
                    'earlybird_date', 'live_date', 'virtual_date', 'titles_end_dt', 'accompanist_end_dt', 'upload_end_dt',
                    'primary_image_id', 'description', 
                    'document_logo_id', 'document_header_msg', 'document_footer_msg',
                    'comments_grade_label', 'comments_footer_msg',
                    ),
                'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'earlybird_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'live_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'virtual_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'titles_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'accompanist_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
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
            return $rc;
        }
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }

        //
        // Format CRS deadline
        //
        if( isset($festival['registration-crs-deadline']) && $festival['registration-crs-deadline'] != '' ) {
            $now = new DateTime('now', new DateTimezone('UTC'));
            $dt = new DateTime($festival['registration-crs-deadline'], new DateTimezone('UTC'));
            if( $now < $dt ) {
                $festival['registration-crs-open'] = 'yes';
            }
            $dt->setTimezone(new DateTimezone($intl_timezone));
            $festival['registration-crs-deadline'] = $dt->format($datetime_format);
        }
        //
        // Build volunteer roles array
        //
        if( isset($festival['volunteers-roles']) && $festival['volunteers-roles'] != '' ) {
            $festival['volunteers-roles-array'] = preg_split('/\s*,\s*/', trim($festival['volunteers-roles']));
            $festival['volunteers-roles-permalinks'] = preg_split('/\s*,\s*/', trim($festival['volunteers-roles']));
            foreach($festival['volunteers-roles-permalinks'] as $pid => $permalink) {
                $festival['volunteers-roles-permalinks'][$pid] = ciniki_core_makePermalink($ciniki, $permalink);
            }
        }

/*        //
        // Load the festival
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $festival = $rc['festival']; */

        if( isset($festival['comments-placement-autofill']) && $festival['comments-placement-autofill'] != '' ) {
            $placements = explode(',', $festival['comments-placement-autofill']);
            $festival['comments-placement-autofills'] = array();
            foreach($placements as $p) {
                list($mark, $text) = explode(':', $p);
                $festival['comments-placement-autofills'][trim($mark)] = trim($text);
            }
        }
        if( isset($festival['comments-level-autofill']) && $festival['comments-level-autofill'] != '' ) {
            $levels = explode(',', $festival['comments-level-autofill']);
            $festival['comments-level-autofills'] = array();
            foreach($levels as $p) {
                list($mark, $text) = explode(':', $p);
                $festival['comments-level-autofills'][trim($mark)] = trim($text);
            }
        }

        //
        // Load festival maps
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
        $rc = ciniki_musicfestivals_festivalMaps($ciniki, $args['tnid'], $festival);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $maps = $rc['maps'];

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
            } elseif( $args['ipv'] == 'live' ) {
                $ipv_sql .= "AND registrations.participation = 0 ";
            } elseif( $args['ipv'] == 'virtual' ) {
                $ipv_sql .= "AND registrations.participation = 1 ";
            }
        }

        //
        // Setup the sql to limit the count of registrations statuses
        $reg_count_exclude_sql = '';
        foreach([5, 70, 75, 77, 80] as $status) {
            if( isset($festival["ui-registrations-count-status-{$status}"]) && $festival["ui-registrations-count-status-{$status}"] == 'no' ) {
                $reg_count_exclude_sql .= "AND registrations.status <> {$status} ";
            }
        }

        //
        // Get the list of sections
        //
        if( isset($args['sections']) && $args['sections'] == 'yes' ) {
            $strsql = "SELECT syllabuses.id, "
                . "syllabuses.name "
                . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
                . "WHERE syllabuses.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY syllabuses.sequence, syllabuses.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'syllabuses', 'fname'=>'id', 'fields'=>array('id', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1177', 'msg'=>'Unable to load syllabuses', 'err'=>$rc['err']));
            }
            $festival['syllabuses'] = isset($rc['syllabuses']) ? $rc['syllabuses'] : array();
            if( !isset($args['syllabus_id']) ) {
                $args['syllabus_id'] = $festival['syllabuses'][0]['id'];
                $festival['syllabus_id'] = $args['syllabus_id'];
            }
            if( isset($args['registrations_list']) && $args['registrations_list'] == 'sections' ) {
                $strsql = "SELECT sections.id, "
                    . "sections.festival_id, "
                    . "sections.syllabus_id, "
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
                        . $reg_count_exclude_sql
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
                    array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'festival_id', 'syllabus_id', 'name', 'permalink', 'sequence', 'num_registrations')),
                    ));
            } else {
                $strsql = "SELECT sections.id, "
                    . "sections.festival_id, "
                    . "sections.syllabus_id, "
                    . "sections.name, "
                    . "sections.permalink, "
                    . "sections.sequence, "
                    . "sections.flags, "
                    . "sections.live_end_dt, "
                    . "sections.virtual_end_dt, "
                    . "sections.titles_end_dt, "
                    . "sections.upload_end_dt "
                    . "FROM ciniki_musicfestival_sections AS sections "
                    . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
                if( isset($args['syllabus_id']) ) {
                    $strsql .= "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $args['syllabus_id']) . "' ";
                }
                $strsql .= "ORDER BY (sections.flags&0x01), sections.sequence, sections.name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'sections', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'syllabus_id', 'name', 'permalink', 'sequence', 'flags')),
                    ));
            }
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
        // Get the list of groups
        //
        if( isset($args['groups']) && $args['groups'] == 'yes' 
            && isset($args['section_id']) && $args['section_id'] > 0 
            ) {
            $strsql = "SELECT DISTINCT categories.groupname "
                . "FROM ciniki_musicfestival_categories AS categories "
                . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "ORDER BY categories.groupname "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'groups', 'fname'=>'groupname', 'fields'=>array('id'=>'groupname', 'name'=>'groupname')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival['groups'] = isset($rc['groups']) ? $rc['groups'] : array();
        }

        //
        // Get the list of categories
        //
        if( isset($args['categories']) && $args['categories'] == 'yes' 
            && isset($args['section_id']) && $args['section_id'] > 0 
            ) {
            $strsql = "SELECT categories.id, "
                . "categories.festival_id, "
                . "categories.section_id, "
                . "categories.groupname, "
                . "categories.name, "
                . "categories.permalink, "
                . "categories.sequence "
                . "FROM ciniki_musicfestival_categories AS categories "
                . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
            if( isset($args['groupname']) ) {
                $strsql .= "AND categories.groupname = '" . ciniki_core_dbQuote($ciniki, $args['groupname']) . "' ";
            }
            $strsql .= "ORDER BY categories.sequence, categories.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'categories', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'section_id', 'groupname', 'name', 'permalink', 'sequence')),
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
        if( isset($args['classes']) && $args['classes'] == 'yes' 
            && isset($args['section_id']) && $args['section_id'] > 0 
            ) {
            $strsql = "SELECT classes.id, "
                . "classes.festival_id, "
                . "classes.category_id, "
                . "sections.name AS section_name, "
                . "categories.groupname, "
                . "categories.name AS category_name, "
                . "classes.code, "
                . "classes.name, "
                . "classes.permalink, "
                . "classes.sequence, "
                . "classes.flags, "
                . "classes.feeflags, "
                . "classes.titleflags, "
                . "classes.earlybird_fee, "
                . "classes.fee, "
                . "classes.virtual_fee, "
                . "classes.earlybird_plus_fee, "
                . "classes.plus_fee, "
                . "classes.min_competitors, "
                . "classes.max_competitors, "
                . "classes.min_titles, "
                . "classes.max_titles, "
                . "classes.synopsis, "
                . "classes.provincials_code, "
                . "classes.schedule_seconds, "
                . "classes.schedule_at_seconds, "
                . "classes.schedule_ata_seconds, ";
            if( isset($args['levels']) && $args['levels'] == 'yes' ) {
                $strsql .= "'' AS accolades, "
                    . "tags.tag_name AS levels, "
                    . "'' AS provincials_class_name ";
            } elseif( isset($args['accolades']) && $args['accolades'] == 'yes' ) {
                $strsql .= "accolades.name AS accolades, "
                    . "'' AS levels, "
                    . "'' AS provincials_class_name ";
            } elseif( isset($args['provincials']) && $args['provincials'] == 'yes' 
                && isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 
                ) {
                $strsql .= "'' AS accolades, "
                    . "'' AS levels, "
                    . "IFNULL(provincials.name, '??') AS provincials_class_name ";
            } else {
                $strsql .= "'' AS accolades, "
                    . "'' AS levels, "
                    . "'' AS provincials_class_name ";
            }
            $strsql .= ", (SELECT COUNT(*) "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE classes.id = registrations.class_id "
                . ") AS num_registrations ";
            $strsql .= ", (SELECT SUM(perf_time1) + SUM(perf_time2) + SUM(perf_time3) "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE classes.id = registrations.class_id "
                . ") AS perf_time ";
//                . "COUNT(registrations.id) AS num_registrations "
            $strsql .= "FROM ciniki_musicfestival_sections AS sections "
                . "INNER JOIN ciniki_musicfestival_categories AS categories USE INDEX (festival_id_2) ON ("
                    . "sections.id = categories.section_id ";
            if( isset($args['category_id']) && $args['category_id'] > 0 ) {
                $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
            }
            if( isset($args['groupname']) && $args['groupname'] != 'all' ) {
                $strsql .= "AND categories.groupname = '" . ciniki_core_dbQuote($ciniki, $args['groupname']) . "' ";
            }
            $strsql .= "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_classes AS classes USE INDEX (festival_id_3) ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") ";
            if( isset($args['levels']) && $args['levels'] == 'yes' ) {
                $strsql .= "LEFT JOIN ciniki_musicfestival_class_tags AS tags ON ("
                    . "classes.id = tags.class_id "
                    . "AND tags.tag_type = 20 "
                    . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") ";
            } elseif( isset($args['accolades']) && $args['accolades'] == 'yes' ) {
                $strsql .= "LEFT JOIN ciniki_musicfestival_accolade_classes AS tc ON ("
                    . "classes.id = tc.class_id "
                    . "AND tc.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_accolades AS accolades ON ("
                    . "tc.accolade_id = accolades.id "
                    . "AND accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") ";
            } elseif( isset($args['provincials']) && $args['provincials'] == 'yes' 
                && isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 
                ) {
                $strsql .= "LEFT JOIN ciniki_musicfestival_classes AS provincials ON ("
                    . "classes.provincials_code = provincials.code "
                    . "AND provincials.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['provincial-festival-id']) . "' "
                    . ") ";
            }
//                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations USE INDEX (festival_id_2) ON ("
//                    . "classes.id = registrations.class_id "
//                    . $ipv_sql
//                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                    . ") "
            $strsql .= "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
//                . "GROUP BY classes.id "
                . "ORDER BY sections.sequence, sections.name, "
                    . "categories.sequence, categories.name, "
                    . "classes.sequence, classes.name ";
            if( isset($args['accolades']) && $args['accolades'] == 'yes' ) {
                $strsql .= ", accolades.name ";
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'category_id', 'section_name', 'groupname', 'category_name', 
                        'code', 'name', 'permalink', 'sequence', 'flags', 'feeflags', 'titleflags',
                        'earlybird_fee', 'fee', 'virtual_fee', 'plus_fee', 'earlybird_plus_fee',
                        'min_competitors', 'max_competitors', 'min_titles', 'max_titles', 
                        'synopsis', 'provincials_code', 'provincials_class_name', 
                        'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds', 'levels', 'accolades',
                        'num_registrations', 'perf_time',
                        ),
                    'dlists'=>array('levels'=>', ', 'accolades'=>', '),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['classes']) ) {
                $festival['classes'] = $rc['classes'];
                foreach($festival['classes'] as $iid => $class) {
                    if( ($class['feeflags']&0x02) == 0x02 ) {
                        $festival['classes'][$iid]['earlybird_fee'] = '$' . number_format($class['earlybird_fee'], 2);
                    } else {
                        $festival['classes'][$iid]['earlybird_fee'] = 'n/a';
                    }
                    if( ($class['feeflags']&0x02) == 0x02 ) {
                        $festival['classes'][$iid]['fee'] = '$' . number_format($class['fee'], 2);
                    } else {
                        $festival['classes'][$iid]['fee'] = 'n/a';
                    }
                    if( ($class['feeflags']&0x08) == 0x08 ) {
                        $festival['classes'][$iid]['virtual_fee'] = '$' . number_format($class['virtual_fee'], 2);
                    } else {
                        $festival['classes'][$iid]['virtual_fee'] = 'n/a';
                    }
                    if( ($class['feeflags']&0x10) == 0x10 ) {
                        $festival['classes'][$iid]['earlybird_plus_fee'] = '$' . number_format($class['earlybird_plus_fee'], 2);
                    } else {
                        $festival['classes'][$iid]['earlybird_plus_fee'] = 'n/a';
                    }
                    if( ($class['feeflags']&0x20) == 0x20 ) {
                        $festival['classes'][$iid]['plus_fee'] = '$' . number_format($class['plus_fee'], 2);
                    } else {
                        $festival['classes'][$iid]['plus_fee'] = '';
                    }
                    if( $class['min_competitors'] == $class['max_competitors'] ) {
                        $festival['classes'][$iid]['num_competitors'] = $class['min_competitors'];
                    } else {
                        $festival['classes'][$iid]['num_competitors'] = $class['min_competitors'] . ' - ' . $class['max_competitors'];
                    }
                    $festival['classes'][$iid]['multireg'] = '';
                    if( ($class['flags']&0x02) == 0x02 ) {
                        $festival['classes'][$iid]['multireg'] = 'yes';
                    }
                    $festival['classes'][$iid]['competitor_type'] = 'I or G';
                    if( ($class['flags']&0x4000) == 0x4000 ) {
                        $festival['classes'][$iid]['competitor_type'] = 'person';
                        if( $class['min_competitors'] > 1 || $class['max_competitors'] > 1 ) {
                            $festival['classes'][$iid]['competitor_type'] = 'people';
                        }
                    } elseif( ($class['flags']&0x8000) == 0x8000 ) {
                        $festival['classes'][$iid]['competitor_type'] = 'group';
                    } 
                    if( $class['min_titles'] == $class['max_titles'] ) {
                        $festival['classes'][$iid]['num_titles'] = $class['min_titles'];
                    } else {
                        $festival['classes'][$iid]['num_titles'] = $class['min_titles'] . ' - ' . $class['max_titles'];
                    }
                    $festival['classes'][$iid]['backtrack'] = '';
                    if( ($class['flags']&0x01000000) == 0x01000000 ) {
                        $festival['classes'][$iid]['backtrack'] = 'Required';
                    } elseif( ($class['flags']&0x02000000) == 0x02000000 ) {
                        $festival['classes'][$iid]['backtrack'] = 'Optional';
                    }
                    $festival['classes'][$iid]['artwork'] = '';
                    if( ($class['titleflags']&0x0100) == 0x0100) {
                        $festival['classes'][$iid]['artwork'] = 'Required';
                    } elseif( ($class['titleflags']&0x0200) == 0x0200) {
                        $festival['classes'][$iid]['artwork'] = 'Optional';
                    }
                    $festival['classes'][$iid]['instrument'] = '';
                    if( ($class['flags']&0x04) == 0x04 ) {
                        $festival['classes'][$iid]['instrument'] = 'Yes';
                    }
                    $festival['classes'][$iid]['accompanist'] = '';
                    if( ($class['flags']&0x1000) == 0x1000 ) {
                        $festival['classes'][$iid]['accompanist'] = 'Required';
                    } elseif( ($class['flags']&0x2000) == 0x2000 ) {
                        $festival['classes'][$iid]['accompanist'] = 'Optional';
                    }
                    $festival['classes'][$iid]['video'] = 'Optional';
                    if( ($class['flags']&0x010000) == 0x010000 ) {
                        $festival['classes'][$iid]['video'] = 'Required';
                    } elseif( ($class['flags']&0x020000) == 0x020000 ) {
                        $festival['classes'][$iid]['video'] = '';
                    }
                    $festival['classes'][$iid]['music'] = 'Optional';
                    if( ($class['flags']&0x100000) == 0x100000 ) {
                        $festival['classes'][$iid]['music'] = 'Required';
                    } elseif( ($class['flags']&0x200000) == 0x200000 ) {
                        $festival['classes'][$iid]['music'] = '';
                    }
                    $festival['classes'][$iid]['movements'] = '';
                    if( ($class['flags']&0x04000000) == 0x04000000 ) {
                        $festival['classes'][$iid]['movements'] = 'Required';
                    } elseif( ($class['flags']&0x08000000) == 0x08000000 ) {
                        $festival['classes'][$iid]['movements'] = 'Optional';
                    }
                    $festival['classes'][$iid]['composer'] = '';
                    if( ($class['flags']&0x10000000) == 0x10000000 ) {
                        $festival['classes'][$iid]['composer'] = 'Required';
                    } elseif( ($class['flags']&0x20000000) == 0x20000000 ) {
                        $festival['classes'][$iid]['composer'] = 'Optional';
                    }
                    if( ($class['flags']&0x040000) == 0x040000 ) {
                        $festival['classes'][$iid]['schedule_type'] = 'Performance&nbsp;+';
                    } elseif( ($class['flags']&0x080000) == 0x080000 ) {
                        $festival['classes'][$iid]['schedule_type'] = 'Total Time';
                    }
                    $total_seconds = 0;
                    $festival['classes'][$iid]['schedule_time'] = '';
                    if( $class['schedule_seconds'] > 0 ) {
                        if( ($class['flags']&0x040000) == 0x040000 ) {
                            $total_seconds += ($class['schedule_seconds'] * $class['num_registrations']) + $class['perf_time'];
                        } elseif( ($class['flags']&0x080000) == 0x080000 ) {
                            $total_seconds += ($class['schedule_seconds'] * $class['num_registrations']);
                        }
                        $festival['classes'][$iid]['schedule_time'] = floor($class['schedule_seconds']/60);
                        if( ($class['schedule_seconds']%60) > 0 ) {
                            $festival['classes'][$iid]['schedule_time'] .= ':' . ($class['schedule_seconds']%60) . '/reg';
                        } else {
                            $festival['classes'][$iid]['schedule_time'] .= ':00/reg';
                        }
                    }
                    $festival['classes'][$iid]['talk_time'] = '';
                    if( $class['schedule_at_seconds'] > 0 ) {
                        $total_seconds += $class['schedule_at_seconds'];
                        $festival['classes'][$iid]['talk_time'] .= floor($class['schedule_at_seconds']/60);
                        if( ($class['schedule_at_seconds']%60) > 0 ) {
                            $festival['classes'][$iid]['talk_time'] .= ':' . ($class['schedule_at_seconds']%60) . '';
                        } else {
                            $festival['classes'][$iid]['talk_time'] .= ':00';
                        }
                    }
                        
                    if( $class['schedule_ata_seconds'] > 0 ) {
                        $total_seconds += $class['schedule_ata_seconds'] * ($class['num_registrations'] - 1);
                        $festival['classes'][$iid]['talk_time'] .= '+' . floor($class['schedule_ata_seconds']/60);
                        if( ($class['schedule_ata_seconds']%60) > 0 ) {
                            $festival['classes'][$iid]['talk_time'] .= ':' . ($class['schedule_ata_seconds']%60) . '/reg';
                        } else {
                            $festival['classes'][$iid]['talk_time'] .= ':00/reg';
                        }
                    }
                    $festival['classes'][$iid]['total_time'] = '';
                    if( $total_seconds > 3600 ) {
                        $festival['classes'][$iid]['total_time'] = floor($total_seconds/3600) . 'h';
                        if( ($total_seconds%3600) > 0 ) {
                            $festival['classes'][$iid]['total_time'] .= ' ' . floor(($total_seconds%3600)/60) . 'm';
                        }
                    }
                    elseif( $total_seconds > 0 ) {
                        $festival['classes'][$iid]['total_time'] = floor($total_seconds/60) . 'm';
                        if( ($total_seconds%60) > 0 ) {
                            $festival['classes'][$iid]['total_time'] .= ' ' . floor($total_seconds%60) . 's';
                        }
                    }
                    $festival['classes'][$iid]['mark'] = '';
                    $festival['classes'][$iid]['placement'] = '';
                    $festival['classes'][$iid]['level'] = '';
                    if( ($class['flags']&0x0100) == 0x0100 ) {
                        $festival['classes'][$iid]['mark'] = 'Yes';
                    }
                    if( ($class['flags']&0x0200) == 0x0200 ) {
                        $festival['classes'][$iid]['placement'] = 'Yes';
                    }
                    if( ($class['flags']&0x0400) == 0x0400 ) {
                        $festival['classes'][$iid]['level'] = 'Yes';
                    }
                    $nplists['classes'][] = $class['id'];
                }
            } else {
                $festival['classes'] = array();
            }
        }

        //
        // Get the list of levels
        //
        if( isset($args['levels']) && $args['levels'] == 'yes' 
            && !isset($args['sections']) 
            ) {
            $strsql = "SELECT DISTINCT tags.tag_name, tags.tag_sort_name "
                . "FROM ciniki_musicfestival_classes AS classes "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
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
            //
            // Load maps
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
            $rc = ciniki_sapos_maps($ciniki);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $sapos_maps = $rc['maps'];

            //
            // Get the list of classes and how many registrations in each
            //
            if( isset($args['registrations_list']) && $args['registrations_list'] == 'classes' ) {
                if( (!isset($args['section_id']) || $args['section_id'] == 0)
                    && isset($festival['sections'][0]['id']) 
                    ) {
                    $args['section_id'] = $festival['sections'][0]['id'];
                }
                //
                // Get the list of classes for this section
                //
                $strsql = "SELECT classes.id, "
                    . "classes.code, "
                    . "classes.name, "
                    . "classes.flags AS class_flags, "
                    . "classes.schedule_seconds, "
                    . "classes.schedule_at_seconds, "
                    . "classes.schedule_ata_seconds, "
                    . "registrations.id AS reg_id, "
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
                    . "registrations.perf_time8 "
                    . "FROM ciniki_musicfestival_categories AS categories "
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "categories.id = classes.category_id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "classes.id = registrations.class_id "
                        . $reg_count_exclude_sql
                        . $ipv_sql
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
                if( isset($args['category_id']) && $args['category_id'] > 0 ) {
                    $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
                }
//                    . "GROUP BY classes.id "
                $strsql .= "ORDER BY categories.sequence, categories.name, classes.sequence, classes.code, classes.name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'code', 'name')), 
                    array('container'=>'registrations', 'fname'=>'reg_id', 
                        'fields'=>array('id'=>'reg_id',
                            'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                            'title1', 'composer1', 'movements1', 'perf_time1',
                            'title2', 'composer2', 'movements2', 'perf_time2',
                            'title3', 'composer3', 'movements3', 'perf_time3',
                            'title4', 'composer4', 'movements4', 'perf_time4',
                            'title5', 'composer5', 'movements5', 'perf_time5',
                            'title6', 'composer6', 'movements6', 'perf_time6',
                            'title7', 'composer7', 'movements7', 'perf_time7',
                            'title8', 'composer8', 'movements8', 'perf_time8',
                            )),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.597', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
                }
                $festival['registration_classes'] = isset($rc['classes']) ? $rc['classes'] : array();
                $section_perf_time = 0;
                foreach($festival['registration_classes'] as $cid => $class) {
                    $festival['registration_classes'][$cid]['num_registrations'] = 0;
                    $total_perf_time = 0;
                    $festival['registration_classes'][$cid]['total_perf_time_display'] = '';
                    if( isset($class['registrations']) ) {
                        foreach($class['registrations'] as $reg) {
                            $festival['registration_classes'][$cid]['num_registrations']++;
                            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, [
                                'rounding' => isset($festival['scheduling-perftime-rounding']) ? $festival['scheduling-perftime-rounding'] : '',
                                ]);
                            if( $rc['stat'] == 'ok' ) {
                                $total_perf_time += $rc['perf_time_seconds'];
                                $section_perf_time += $rc['perf_time_seconds'];
                            }
                        }
                    }
                    if( $total_perf_time > 0 ) {
                        $hours = intval($total_perf_time/3600);
                        $minutes = round(($total_perf_time%3600)/60, 0);
                        if( $hours > 0 ) {
                            $festival['registration_classes'][$cid]['total_perf_time_display'] = "{$hours} hours {$minutes} minutes";
                        } else {
                            $festival['registration_classes'][$cid]['total_perf_time_display'] = "{$minutes} minutes";
                        }
                    }
                }
                $festival['section_perf_time_text'] = '';
                if( $section_perf_time > 0 ) {
                    $hours = intval($section_perf_time/3600);
                    $minutes = round(($section_perf_time%3600)/60, 0);
                    if( $hours > 0 ) {
                        $festival['section_perf_time_text'] = "{$hours} hours {$minutes} minutes";
                    } else {
                        $festival['section_perf_time_text'] = "{$minutes} minutes";
                    }
                }
            }
            //
            // Get the list of teachers and number of registrations
            //
            elseif( isset($args['registrations_list']) && $args['registrations_list'] == 'teachers' ) {
                $strsql = "SELECT registrations.teacher_customer_id, "
                    . "customers.display_name, ";
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
                    . "LEFT JOIN ciniki_customers AS customers ON ("
                        . "registrations.teacher_customer_id = customers.id "
                        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE registrations.teacher_customer_id != 0 "
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . $reg_count_exclude_sql
                    . "GROUP BY registrations.teacher_customer_id "
                    . "ORDER BY customers.display_name "
                    . "";
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'teachers', 'fname'=>'teacher_customer_id', 
                        'fields'=>array('id'=>'teacher_customer_id', 'display_name', 'num_registrations'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['teachers']) ) {
                    $festival['registration_teachers'] = $rc['teachers'];
                }
            }

            //
            // Get the list of accompanists and number of registrations
            //
            elseif( isset($args['registrations_list']) && $args['registrations_list'] == 'accompanists' ) {
                $strsql = "SELECT registrations.accompanist_customer_id, "
                    . "customers.display_name, ";
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
                    . "LEFT JOIN ciniki_customers AS customers ON ("
                        . "registrations.accompanist_customer_id = customers.id "
                        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE registrations.accompanist_customer_id != 0 "
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . $reg_count_exclude_sql
                    . "GROUP BY registrations.accompanist_customer_id "
                    . "ORDER BY customers.display_name "
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
            }

            //
            // Get the list of tags
            //
            elseif( isset($args['registrations_list']) && $args['registrations_list'] == 'tags' ) {
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
                        . $reg_count_exclude_sql
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
            elseif( isset($args['registrations_list']) && $args['registrations_list'] == 'members'
                && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
                ) {
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
                        . $reg_count_exclude_sql
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
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.192', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
                }
                $festival['registration_members'] = isset($rc['members']) ? $rc['members'] : array();
            }
            //
            // Get the list of statuses
            //
            elseif( isset($args['registrations_list']) && $args['registrations_list'] == 'statuses') {
                $strsql = "SELECT status, "
                    . "status AS name, "
                    . "COUNT(registrations.id) AS num_registrations "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
                    if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) ) {
                        if( $args['ipv'] == 'inperson' ) {
                            $strsql .= "AND registrations.participation = 0 ";
                        } elseif( $args['ipv'] == 'virtual' ) {
                            $strsql .= "AND registrations.participation = 1 ";
                        }
                    }
                $strsql .= "GROUP BY status "
                    . "ORDER BY status "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
                $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'statuses', 'fname'=>'status', 
                        'fields'=>array('status', 'name', 'num_registrations'),
                        'maps'=>array('name'=>$maps['registration']['status']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.809', 'msg'=>'Unable to load statuses', 'err'=>$rc['err']));
                }
                $festival['registration_statuses'] = isset($rc['statuses']) ? $rc['statuses'] : array();
            }
            //
            // Get the list of change requests
            //
            elseif( isset($args['registrations_list']) && $args['registrations_list'] == 'crs' ) {
                $strsql = "SELECT crs.status, "
                    . "COUNT(registrations.id) AS num_reg "
                    . "FROM ciniki_musicfestival_crs AS crs "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "crs.object_id = registrations.id "
                        . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . $ipv_sql
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE crs.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND crs.object = 'ciniki.musicfestivals.registration' "
                    . "AND crs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY crs.status "
                    . "ORDER BY crs.status "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
                $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1092', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
                }
                $statuses = isset($rc['num']) ? $rc['num'] : array();
                $total = 0;
                foreach($statuses as $status) {
                    $total += $status;
                }

                $festival['registration_cr_statuses'] = [
                    ['status' => 0, 'name' => 'All', 'label' => 'Change Requests', 'num_registrations' => ($total > 0 ? $total : '')],
                    ['status' => 20, 'name' => 'Submitted', 'label' => 'Submitted Change Requests', 'num_registrations' => (isset($statuses[20]) ? $statuses[20] : '')],
                    ['status' => 30, 'name' => 'Reviewing', 'label' => 'Reviewing', 'num_registrations' => (isset($statuses[30]) ? $statuses[30] : '')],
                    ['status' => 40, 'name' => 'Pending Payment', 'label' => 'Pending Payments', 'num_registrations' => (isset($statuses[40]) ? $statuses[40] : '')],
                    ['status' => 50, 'name' => 'Pending', 'label' => 'Pending', 'num_registrations' => (isset($statuses[50]) ? $statuses[50] : '')],
                    ['status' => 70, 'name' => 'Completed', 'label' => 'Completed', 'num_registrations' => (isset($statuses[70]) ? $statuses[70] : '')],
                    ['status' => 90, 'name' => 'Cancelled', 'label' => 'Cancelled', 'num_registrations' => (isset($statuses[90]) ? $statuses[90] : '')],
                    ];
            }
            //
            // Get the list of colours
            //
/*            elseif( isset($args['registrations_list']) && $args['registrations_list'] == 'colours') {
                $strsql = "SELECT (registrations.flags&0xFF00) AS colour, "
                    . "COUNT(registrations.id) AS num_registrations "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
                        if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) ) {
                            if( $args['ipv'] == 'inperson' ) {
                                $strsql .= "AND registrations.participation = 0 ";
                            } elseif( $args['ipv'] == 'virtual' ) {
                                $strsql .= "AND registrations.participation = 1 ";
                            }
                        }
                $strsql .= "GROUP BY colour "
                    . "ORDER BY colour "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
                $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'colours', 'fname'=>'colour', 'fields'=>array('num_registrations')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.619', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
                }
                $festival['registration_colours'] = array(
                    array('name' => 'White', 
                        'num_registrations' => isset($rc['colours'][0]['num_registrations']) ? $rc['colours'][0]['num_registrations'] : 0),
                    array('name' => 'Green', 
                        'num_registrations' => isset($rc['colours'][0x8000]['num_registrations']) ? $rc['colours'][0x8000]['num_registrations'] : 0),
                    array('name' => 'Yellow', 
                        'num_registrations' => isset($rc['colours'][0x4000]['num_registrations']) ? $rc['colours'][0x4000]['num_registrations'] : 0),
                    array('name' => 'Orange', 
                        'num_registrations' => isset($rc['colours'][0x2000]['num_registrations']) ? $rc['colours'][0x2000]['num_registrations'] : 0),
                    array('name' => 'Red', 
                        'num_registrations' => isset($rc['colours'][0x1000]['num_registrations']) ? $rc['colours'][0x1000]['num_registrations'] : 0),
                    array('name' => 'Purple', 
                        'num_registrations' => isset($rc['colours'][0x0800]['num_registrations']) ? $rc['colours'][0x0800]['num_registrations'] : 0),
                    array('name' => 'Blue', 
                        'num_registrations' => isset($rc['colours'][0x0400]['num_registrations']) ? $rc['colours'][0x0400]['num_registrations'] : 0),
                    array('name' => 'Teal', 
                        'num_registrations' => isset($rc['colours'][0x0200]['num_registrations']) ? $rc['colours'][0x0200]['num_registrations'] : 0),
                    array('name' => 'Grey', 
                        'num_registrations' => isset($rc['colours'][0x0100]['num_registrations']) ? $rc['colours'][0x0100]['num_registrations'] : 0),
                    );
            } */

            //
            // Load the registration list
            //
            if( isset($args['section_id']) && $args['section_id'] > -1 ) {
                //
                // Get competitors to get notes
                //
                $strsql = "SELECT id, notes "
                    . "FROM ciniki_musicfestival_competitors AS competitors "
                    . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
                $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'competitors', 'fname'=>'id', 
                        'fields'=>array('id', 'notes'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.928', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
                }
                $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

                //
                // Load registrations
                //
                $strsql = "SELECT registrations.id, "
                    . "registrations.festival_id, "
                    . "sections.id AS section_id, "
                    . "registrations.teacher_customer_id, "
                    . "IFNULL(teachers.display_name, '') AS teacher_name, "
                    . "IFNULL(teachers2.display_name, '') AS teacher2_name, "
                    . "registrations.accompanist_customer_id, "
                    . "accompanists.display_name AS accompanist_name, "
                    . "classes.flags AS class_flags, "
                    . "classes.schedule_seconds, "
                    . "classes.schedule_at_seconds, "
                    . "classes.schedule_ata_seconds, "
                    . "classes.min_titles, "
                    . "classes.max_titles, "
                    . "registrations.billing_customer_id, "
                    . "registrations.rtype, "
                    . "registrations.rtype AS rtype_text, "
                    . "registrations.flags, "
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
                    . "categories.name AS category_name, "
                    . "registrations.instrument, "
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
                    . "registrations.competitor1_id, "
                    . "registrations.competitor2_id, "
                    . "registrations.competitor3_id, "
                    . "registrations.competitor4_id, "
                    . "registrations.competitor5_id, "
                    . "FORMAT(registrations.fee, 2) AS fee, "
                    . "registrations.participation, "
                    . "registrations.notes, "
                    . "registrations.internal_notes, "
                    . "registrations.runsheet_notes, "
                    . "registrations.mark, "
                    . "registrations.placement, "
                    . "registrations.level, "
                    . "invoices.invoice_type, "
                    . "invoices.status AS invoice_status, "
                    . "invoices.payment_status AS payment_status_text, "
//                    . "DATE_FORMAT(invoices.invoice_date, '%b %e') AS invoice_date "
                    . "invoices.invoice_date, ";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                    $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time, ";
                } else {
                    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time, ";
                }
                $strsql .= "IFNULL(divisions.name, '') AS division_name, "
                    . "IFNULL(DATE_FORMAT(divisions.division_date, '%b %e'), '') AS division_date, "
                    . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name, "
                    . "IFNULL(ssections.name, '') AS section_name, "
                    . "GROUP_CONCAT(tags.tag_name SEPARATOR ', ') AS tags ";
//                    . "competitors.id AS competitor_id, "
//                    . "competitors.notes AS competitor_notes ";
                $strsql .= "FROM ciniki_musicfestival_registrations AS registrations USE INDEX(festival_id_2) ";
                if( isset($args['registration_tag']) && $args['registration_tag'] != '' ) {
                    $strsql .= "INNER JOIN ciniki_musicfestival_registration_tags AS tags1 ON ("
                        . "registrations.id = tags1.registration_id "
                        . "AND tags1.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['registration_tag']) . "' "
                        . "AND tags1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") ";
                } 
                if( isset($args['cr_status']) && $args['cr_status'] != '' ) {
                    $strsql .= "INNER JOIN ciniki_musicfestival_crs AS crs ON ("
                        . "registrations.id = crs.object_id "
                        . "AND crs.object = 'ciniki.musicfestivals.registration' ";
                    if( $args['cr_status'] > 0 ) {
                        $strsql .= "AND crs.status = '" . ciniki_core_dbQuote($ciniki, $args['cr_status']) . "' ";
                    }
                    $strsql .= "AND crs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") ";
                } 
                $strsql .= "LEFT JOIN ciniki_musicfestival_registration_tags AS tags ON ("
                        . "registrations.id = tags.registration_id "
                        . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") ";
                $strsql .= "LEFT JOIN ciniki_customers AS teachers ON ("
                        . "registrations.teacher_customer_id = teachers.id "
                        . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                        . "LEFT JOIN ciniki_customers AS teachers2 ON ("
                        . "registrations.teacher2_customer_id = teachers2.id "
                        . "AND teachers2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
                    . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                        . "registrations.invoice_id = invoices.id "
                        . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "registrations.timeslot_id = timeslots.id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                        . "timeslots.sdivision_id = divisions.id "
                        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                        . "divisions.ssection_id = ssections.id "
                        . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                        . "divisions.location_id = locations.id "
                        . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
/*                    . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                        . "( "
                            . "registrations.competitor1_id = competitors.id "
                            . "OR registrations.competitor2_id = competitors.id "
                            . "OR registrations.competitor3_id = competitors.id "
                            . "OR registrations.competitor4_id = competitors.id "
                            . "OR registrations.competitor5_id = competitors.id "
                            . ") "
                        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") " */
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . $ipv_sql
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                if( isset($args['section_id']) && $args['section_id'] > 0 ) {
                    if( isset($args['class_id']) && $args['class_id'] > 0 ) {
                        $strsql .= "AND class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
                    }
                    $strsql .= "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                        . "GROUP BY registrations.id ";
                } elseif( isset($args['registration_status']) && $args['registration_status'] != '' ) {
                    $strsql .= "AND registrations.status = '" . ciniki_core_dbQuote($ciniki, $args['registration_status']) . "' "
                        . "GROUP BY registrations.id ";
                } elseif( isset($args['member_id']) && $args['member_id'] > 0 ) {
                    $strsql .= "AND registrations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
                        . "GROUP BY registrations.id "
                        . "ORDER BY registrations.date_added DESC "
                        . "";
                } elseif( isset($args['teacher_customer_id']) && $args['teacher_customer_id'] > 0 ) {
                    $strsql .= "AND registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' "
                        . "GROUP BY registrations.id ";
                } elseif( isset($args['accompanist_customer_id']) && $args['accompanist_customer_id'] > 0 ) {
                    $strsql .= "AND registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['accompanist_customer_id']) . "' "
                        . "GROUP BY registrations.id ";
                } else {
                    $strsql .= "GROUP BY registrations.id ";
                }
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'registrations', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'teacher_name', 'teacher2_name', 
                            'billing_customer_id', 'accompanist_name', 
                            'rtype', 'rtype_text', 'status', 'status_text', 'display_name', 
                            'invoice_type', 'invoice_status', 'payment_status_text', 'invoice_date', 
                            'class_id', 'class_code', 'class_name', 'category_name',
                            'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                            'min_titles', 'max_titles',
                            'fee', 'participation', 'flags', 'instrument',
                            'title1', 'composer1', 'movements1', 'perf_time1', 'video_url1', 'music_orgfilename1',
                            'title2', 'composer2', 'movements2', 'perf_time2', 'video_url2', 'music_orgfilename2',
                            'title3', 'composer3', 'movements3', 'perf_time3', 'video_url3', 'music_orgfilename3',
                            'title4', 'composer4', 'movements4', 'perf_time4', 'video_url4', 'music_orgfilename4',
                            'title5', 'composer5', 'movements5', 'perf_time5', 'video_url5', 'music_orgfilename5',
                            'title6', 'composer6', 'movements6', 'perf_time6', 'video_url6', 'music_orgfilename6',
                            'title7', 'composer7', 'movements7', 'perf_time7', 'video_url7', 'music_orgfilename7',
                            'title8', 'composer8', 'movements8', 'perf_time8', 'video_url8', 'music_orgfilename8',
                            'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                            'notes', 'internal_notes', 'runsheet_notes',
                            'mark', 'placement', 'level',
                            'slot_time', 'division_name', 'division_date', 'location_name', 'section_name', 'tags',
                            ),
                        'utctotz'=>array(
                            'invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>'M j'),
                            ),
                        'maps'=>array(
                            'rtype_text'=>$maps['registration']['rtype'],
                            'status_text'=>$maps['registration']['status'],
                            'payment_status_text'=>$sapos_maps['invoice']['payment_status'],
                            ),
                        ),
//                    array('container'=>'competitors', 'fname'=>'competitor_id', 
//                        'fields'=>array('id'=>'competitor_id', 'notes'=>'competitor_notes'),
//                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
    //            $festival['registrations_copy'] = '';
                if( isset($rc['registrations']) ) {
                    $festival['registrations'] = $rc['registrations'];
                    $nplists['registrations'] = array();
                    $total = 0;
    //                $festival['registrations_copy'] = "<table cellpadding=2 cellspacing=0>";
                    foreach($festival['registrations'] as $rid => $registration) {
                        $nplists['registrations'][] = $registration['id'];
                        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $registration, [
//                            'times' => 'startsum',
                            'newline' => '<br/>',
                            'rounding' => isset($festival['scheduling-perftime-rounding']) ? $festival['scheduling-perftime-rounding'] : '',
                            ]);
                        $festival['registrations'][$rid]['titles'] = $rc['titles'];
                        $festival['registrations'][$rid]['org_time'] = $rc['org_time'];
                        $festival['registrations'][$rid]['perf_time'] = $rc['perf_time'];
                        $perf_time = 0;
                        for($i = 1; $i <= 8; $i++) {
                            
/*                            if( $registration["title{$i}"] != '' ) {
                                $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $registration, $i);
                                if( $rc['stat'] == 'ok' ) {
                                    $festival['registrations'][$rid]["title{$i}"] = $rc['title'];
                                    $registration["title{$i}"] = $rc['title'];
                                    $festival['registrations'][$rid]['titles'] .= ($festival['registrations'][$rid]['titles'] != '' ? '<br/>' : '') . $rc['title'];
                                }
                            } */
                            unset($festival['registrations'][$rid]["movements{$i}"]);
                            unset($festival['registrations'][$rid]["composer{$i}"]);
                            if( $i > $registration['max_titles'] ) {
                                unset($festival['registrations'][$rid]["title{$i}"]);
                                unset($festival['registrations'][$rid]["perf_time{$i}"]);
                                unset($festival['registrations'][$rid]["music_orgfilename{$i}"]);
                                unset($festival['registrations'][$rid]["video_url{$i}"]);
                            }
                        } 

                        $scheduled = '';
                        $scheduled_sd = '';
                        if( $registration['division_date'] != '' ) {
                            $scheduled .= $registration['division_date'];
                        }
                        if( $registration['slot_time'] != '' && $registration['slot_time'] != '12:00 AM' ) {
                            $scheduled .= ($scheduled != '' ? ' - ' : '') . $registration['slot_time'];
                        }
                        if( $registration['location_name'] != '' ) {
                            $scheduled .= ($scheduled != '' ? ' - ' : '') . $registration['location_name'];
                        }
                        if( $registration['section_name'] != '' ) {
                            $scheduled_sd .= ($scheduled_sd != '' ? "" : '') . $registration['section_name'];
                        }
                        if( $registration['division_name'] != '' ) {
                            $scheduled_sd .= ($scheduled_sd != '' ? ' - ' : '') . $registration['division_name'];
                        }
                        $festival['registrations'][$rid]['scheduled'] = $scheduled;
                        $festival['registrations'][$rid]['scheduled_sd'] = $scheduled_sd;

                        //
                        // Add competitor notes
                        //
                        for($i = 1; $i <= 5; $i++) {
                            if( $registration['competitor1_id'] > 0 
                                && isset($competitors[$registration["competitor{$i}_id"]]['notes']) 
                                ) {
                                $festival['registrations'][$rid]['notes'] .= 
                                    ($festival['registrations'][$rid]['notes'] != '' ? "\n" : '') 
                                    . $competitors[$registration["competitor{$i}_id"]]['notes'];
                            }
                        }
                        if( isset($registration['internal_notes']) && $registration['internal_notes'] != '' ) {
                            $festival['registrations'][$rid]['notes'] .= 
                                ($festival['registrations'][$rid]['notes'] != '' ? "\n" : '') 
                                . '<b>Internal:</b> ' . $registration['internal_notes'];
                        }
                        if( isset($registration['runsheet_notes']) && $registration['runsheet_notes'] != '' ) {
                            $festival['registrations'][$rid]['notes'] .= 
                                ($festival['registrations'][$rid]['notes'] != '' ? "\n" : '') 
                                . '<b>Runsheet:</b> ' . $registration['runsheet_notes'];
                        }

                        //
                        // Setup invoice status
                        //
                        if( $registration['invoice_type'] == 20 ) {
                            $festival['registrations'][$rid]['invoice_status_text'] = 'Unpaid Cart';
                        } else {
                            $festival['registrations'][$rid]['invoice_status_text'] = $registration['payment_status_text'];
                        }
    //                    $festival['registrations_copy'] .= '<tr><td>' . $registration['class_code'] . '</td><td>' . $registration['title1'] . '</td><td>' . $registration['perf_time1'] . "</td></tr>\n";
                    }
    //                $festival['registrations_copy'] .= "</table>";
                } else {
                    $festival['registrations'] = array();
                    $nplists['registrations'] = array();
                }
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
                . "sections.flags AS options "
//                . "sections.adjudicator1_id "
                . "FROM ciniki_musicfestival_schedule_sections AS sections "
                . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) 
                && isset($args['lv']) && $args['lv'] > 0 
                ) {
                $strsql .= "AND ("
                    . "(sections.flags&0x0F00) = 0 "
                    . "OR (sections.flags&0x0F00) = '" . ciniki_core_dbQuote($ciniki, $args['lv']) . "' "
                    . ") ";
            }
            $strsql .= "ORDER BY sections.sequence, sections.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'schedulesections', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'name', 'sequence', 'flags', 'options', 
//                        'adjudicator1_id', 
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
            // Get the list of schedule locations
            //
            if( isset($args['locations']) && $args['locations'] == 'yes' ) {
                $strsql = "SELECT locations.id, "
                    . "locations.festival_id, "
                    . "IF(locations.shortname <> '', locations.shortname, locations.name) AS name "
                    . "FROM ciniki_musicfestival_locations AS locations "
                    . "WHERE locations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'schedulelocations', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'name'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['schedulelocations']) ) {
                    $festival['schedule_locations'] = $rc['schedulelocations'];
                    $nplists['schedule_locations'] = array();
                    foreach($festival['schedule_locations'] as $iid => $location) {
                        $nplists['schedule_locations'][] = $location['id'];
                        if( isset($args['location_id']) && $args['location_id'] == $location['id'] ) {
                            $requested_location = $location;
                        }
                    }
                } else {
                    $festival['schedule_locations'] = array();
                    $nplists['schedule_locations'] = array();
                }
            }

            //
            // Get the schedule live grid
            //
            if( isset($args['livegrid'])  && $args['livegrid'] == 'yes' ) {
                $strsql = "SELECT IFNULL(locations.id, 0) AS location_id, "
                    . "IFNULL(locations.shortname, 'Unknown') AS location_name, "
                    . "ssections.id AS ssection_id, "
                    . "ssections.name AS ssection_name, "
                    . "divisions.id AS division_id, "
                    . "IF(divisions.shortname <> '', divisions.shortname, divisions.name) AS division_name, "
                    . "MIN(timeslots.slot_time) AS start_time "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "registrations.timeslot_id = timeslots.id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                        . "timeslots.sdivision_id = divisions.id "
                        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                        . "divisions.ssection_id = ssections.id "
                        . "AND ssections.name <> 'Unscheduled' "
                        . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                        . "divisions.location_id = locations.id "
                        . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.participation = 0 "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY divisions.id "
                    . "ORDER BY locations.sequence, location_name, ssections.sequence, ssections.name, start_time "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'locations', 'fname'=>'location_id', 
                        'fields'=>array('id'=>'location_id', 'name'=>'location_name'),
                        ),
                    array('container'=>'ssections', 'fname'=>'ssection_id', 
                        'fields'=>array('id'=>'ssection_id', 'name'=>'ssection_name'),
                        ),
                    array('container'=>'divisions', 'fname'=>'division_id', 
                        'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'start_time'),
                        'utctotz'=>array(
                            'start_time'=>array('format'=>'g:i a', 'timezone'=>'UTC'),
                            ),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.959', 'msg'=>'Unable to load livegrid', 'err'=>$rc['err']));
                }
                $locations = isset($rc['locations']) ? $rc['locations'] : array();
                $days = [];

                foreach($locations as $lid => $location) {
                    foreach($location['ssections'] as $sid => $ssection) {
                        if( !isset($days[$ssection['name']]) ) {
                            $days[$ssection['name']] = $ssection['name'];
                        }
                    }
                }
                $festival['schedule_livegrid_locations'] = $locations;
                $festival['schedule_livegrid_days'] = $days;
            }

            //
            // Get the list of schedule dates
            //
            if( isset($args['dates']) && $args['dates'] == 'yes' ) {
                $strsql = "SELECT DATE_FORMAT(divisions.division_date, '%Y-%m-%d') AS id, "
                    . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS name "
                    . "FROM ciniki_musicfestival_schedule_sections AS ssections "
                    . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                        . "ssections.id = divisions.ssection_id "
                        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY divisions.division_date "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduledates', 'fname'=>'id', 
                        'fields'=>array('id', 'name'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['scheduledates']) ) {
                    $festival['schedule_dates'] = $rc['scheduledates'];
                    $nplists['schedule_dates'] = array();
                    foreach($festival['schedule_dates'] as $did => $date) {
                        $nplists['schedule_dates'][] = $date['id'];
                    }
                } else {
                    $festival['schedule_dates'] = array();
                    $nplists['schedule_dates'] = array();
                }
            }

            //
            // Get the list of schedule section divisions
            //
            if( isset($args['ssection_id']) && $args['ssection_id'] == 'unscheduled' ) {
                $strsql = "SELECT registrations.id, "
                    . "registrations.flags, ";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $strsql .= "registrations.pn_display_name AS display_name, ";
                } else {
                    $strsql .= "registrations.display_name, ";
                }
                $strsql .= "registrations.title1, "
                    . "registrations.title2, "
                    . "registrations.title3, "
                    . "registrations.title4, "
                    . "registrations.title5, "
                    . "registrations.title6, "
                    . "registrations.title7, "
                    . "registrations.title8, "
                    . "registrations.composer1, "
                    . "registrations.composer2, "
                    . "registrations.composer3, "
                    . "registrations.composer4, "
                    . "registrations.composer5, "
                    . "registrations.composer6, "
                    . "registrations.composer7, "
                    . "registrations.composer8, "
                    . "registrations.movements1, "
                    . "registrations.movements2, "
                    . "registrations.movements3, "
                    . "registrations.movements4, "
                    . "registrations.movements5, "
                    . "registrations.movements6, "
                    . "registrations.movements7, "
                    . "registrations.movements8, "
                    . "registrations.status, "
                    . "registrations.status AS status_text, "
                    . "classes.code AS class_code, "
                    . "registrations.timeslot_id "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.timeslot_id = 0 "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                    . "HAVING ISNULL(timeslot_id) "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'registrations', 'fname'=>'id', 
                        'fields'=>array('id', 'flags', 'display_name', 'class_code', 'status', 'status_text',
                            'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                            'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                            'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                            ),
                        'maps'=>array('status_text'=>$maps['registration']['status']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.717', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
                }
                $festival['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
                foreach($festival['unscheduled_registrations'] as $rid => $registration) {
                    $festival['unscheduled_registrations'][$rid]['titles'] = '';
                    for($i = 1; $i <= 8; $i++) {
                        if( $registration["title{$i}"] != '' ) {
                            $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $registration, $i);
                            if( $rc['stat'] == 'ok' ) {
                                $festival['unscheduled_registrations'][$rid]["title{$i}"] = $rc['title'];
                                $festival['unscheduled_registrations'][$rid]['titles'] .= ($festival['unscheduled_registrations'][$rid]['titles'] != '' ? '<br/>' : '') . $rc['title'];
                            }
                        }
                    }
                }
            }
            elseif( isset($args['ssection_id']) && $args['ssection_id'] == 'notes' ) {
                $strsql = "SELECT registrations.id, "
                    . "registrations.flags, ";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $strsql .= "registrations.pn_display_name AS display_name, ";
                } else {
                    $strsql .= "registrations.display_name, ";
                }
                $strsql .= "registrations.title1, "
                    . "registrations.title2, "
                    . "registrations.title3, "
                    . "registrations.title4, "
                    . "registrations.title5, "
                    . "registrations.title6, "
                    . "registrations.title7, "
                    . "registrations.title8, "
                    . "registrations.composer1, "
                    . "registrations.composer2, "
                    . "registrations.composer3, "
                    . "registrations.composer4, "
                    . "registrations.composer5, "
                    . "registrations.composer6, "
                    . "registrations.composer7, "
                    . "registrations.composer8, "
                    . "registrations.movements1, "
                    . "registrations.movements2, "
                    . "registrations.movements3, "
                    . "registrations.movements4, "
                    . "registrations.movements5, "
                    . "registrations.movements6, "
                    . "registrations.movements7, "
                    . "registrations.movements8, "
                    . "registrations.notes, "
                    . "GROUP_CONCAT(competitors.notes SEPARATOR ' ') AS competitor_notes, "
                    . "registrations.status, "
                    . "registrations.status AS status_text, "
                    . "classes.code AS class_code, "
                    . "registrations.timeslot_id "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                        . "("
                            . "registrations.competitor1_id = competitors.id "
                            . "OR registrations.competitor2_id = competitors.id "
                            . "OR registrations.competitor3_id = competitors.id "
                            . "OR registrations.competitor4_id = competitors.id "
                            . "OR registrations.competitor5_id = competitors.id "
                            . ") "
                            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY registrations.id "
//                    . "HAVING ISNULL(timeslot_id) "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'registrations', 'fname'=>'id', 
                        'fields'=>array('id', 'flags', 'display_name', 'class_code', 'status', 'status_text',
                            'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                            'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                            'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                            'notes', 'competitor_notes',
                            ),
                        'maps'=>array('status_text'=>$maps['registration']['status']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.172', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
                }
                $festival['registrations_notes'] = isset($rc['registrations']) ? $rc['registrations'] : array();
                foreach($festival['registrations_notes'] as $rid => $registration) {
                    if( $registration['notes'] == '' && trim($registration['competitor_notes']) == '' ) {
                        unset($festival['registrations_notes'][$rid]);
                        continue;
                    }
                    $festival['registrations_notes'][$rid]['titles'] = '';
                    for($i = 1; $i <= 8; $i++) {
                        if( $registration["title{$i}"] != '' ) {
                            $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $registration, $i);
                            if( $rc['stat'] == 'ok' ) {
                                $festival['registrations_notes'][$rid]["title{$i}"] = $rc['title'];
                                $festival['registrations_notes'][$rid]['titles'] .= ($festival['registrations_notes'][$rid]['titles'] != '' ? '<br/>' : '') . $rc['title'];
                            }
                        }
                    }
                    if( $registration['competitor_notes'] != '' ) {
                        $festival['registrations_notes'][$rid]['notes'] .= ($registration['notes'] != '' ? "\n" : '') . $registration['competitor_notes'];
                    }
                }
            }
            elseif( isset($args['ssection_id']) && $args['ssection_id'] > 0 ) {
                $strsql = "SELECT divisions.id, "
                    . "divisions.festival_id, "
                    . "divisions.ssection_id, "
                    . "divisions.flags, "
                    . "divisions.flags AS options, "
                    . "divisions.name, "
//                    . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
                    . "DATE_FORMAT(divisions.division_date, '%a, %b %e, %Y') AS division_date_text, "
//                    . "divisions.address, "
                    . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name, "
                    . "GROUP_CONCAT(DISTINCT customers.display_name ORDER BY customers.display_name SEPARATOR ', ') AS adjudicator_name, "
                    . "MIN(timeslots.slot_time) AS first_timeslot "
                    . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                    . "LEFT JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
                        . "divisions.id = arefs.object_id "
                        . "AND arefs.object = 'ciniki.musicfestivals.scheduledivision' "
                        . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
                        . "arefs.adjudicator_id = adjudicators.id "
                        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_customers AS customers ON ("
                        . "adjudicators.customer_id = customers.id "
                        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                        . "divisions.location_id = locations.id "
                        . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "divisions.id = timeslots.sdivision_id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND divisions.ssection_id = '" . ciniki_core_dbQuote($ciniki, $args['ssection_id']) . "' "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY divisions.id "
                    . "ORDER BY divisions.division_date, divisions.name, first_timeslot "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduledivisions', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'ssection_id', 'name', 'flags', 'options', 
                            'division_date_text', 'location_name', 'adjudicator_name', 
                            ),
                        'flags' => array('options'=>$maps['schedulesection']['flags']),
                        ),
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
            elseif( isset($args['location_id']) && $args['location_id'] > 0 ) {
                $strsql = "SELECT divisions.id, "
                    . "divisions.festival_id, "
                    . "divisions.ssection_id, "
                    . "sections.name AS section_name, "
                    . "divisions.flags, "
                    . "divisions.flags AS options, "
                    . "divisions.name, "
//                    . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
                    . "DATE_FORMAT(divisions.division_date, '%a, %b %e, %Y') AS division_date_text, "
//                    . "divisions.address, "
                    . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name, "
                    . "customers.display_name AS adjudicator_name, "
                    . "TIME_FORMAT(MIN(timeslots.slot_time), '%H%i') AS sort_timeslot, "
                    . "TIME_FORMAT(MIN(timeslots.slot_time), '%l:%i %p') AS first_timeslot, "
                    . "TIME_FORMAT(MAX(timeslots.slot_time), '%l:%i %p') AS last_timeslot "
                    . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                    . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                        . "divisions.ssection_id = sections.id "
                        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
                        . "divisions.adjudicator_id = adjudicators.id "
                        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_customers AS customers ON ("
                        . "adjudicators.customer_id = customers.id "
                        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                        . "divisions.location_id = locations.id "
                        . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "divisions.id = timeslots.sdivision_id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND divisions.location_id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY divisions.id "
                    . "ORDER BY divisions.division_date, sort_timeslot, divisions.name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduledivisions', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'ssection_id', 'section_name', 'name', 'flags', 'options', 
                            'division_date_text', 'location_name', 'adjudicator_name', 'first_timeslot', 'last_timeslot',
                            ),
                        'flags' => array('options'=>$maps['schedulesection']['flags']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['scheduledivisions']) ) {
                    $festival['schedule_divisions'] = $rc['scheduledivisions'];
                    $nplists['schedule_divisions'] = array();
                    foreach($festival['schedule_divisions'] as $iid => $scheduledivision) {
                        $nplists['schedule_divisions'][] = $scheduledivision['id'];
                        $festival['schedule_divisions'][$iid]['name'] = $scheduledivision['section_name'] . ' - ' . $scheduledivision['name'];
                    }
                } else {
                    $festival['schedule_divisions'] = array();
                    $nplists['schedule_divisions'] = array();
                }
            }
            elseif( isset($args['date_id']) && $args['date_id'] > 0 ) {
                $strsql = "SELECT divisions.id, "
                    . "divisions.festival_id, "
                    . "divisions.ssection_id, "
                    . "sections.name AS section_name, "
                    . "divisions.flags, "
                    . "divisions.flags AS options, "
                    . "divisions.name, "
//                    . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
                    . "DATE_FORMAT(divisions.division_date, '%a, %b %e, %Y') AS division_date_text, "
//                    . "divisions.address, "
                    . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name, "
                    . "customers.display_name AS adjudicator_name, "
                    . "TIME_FORMAT(MIN(timeslots.slot_time), '%H%i') AS sort_timeslot, "
                    . "TIME_FORMAT(MIN(timeslots.slot_time), '%l:%i %p') AS first_timeslot, "
                    . "TIME_FORMAT(MAX(timeslots.slot_time), '%l:%i %p') AS last_timeslot "
                    . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                    . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                        . "divisions.ssection_id = sections.id "
                        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
                        . "divisions.adjudicator_id = adjudicators.id "
                        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_customers AS customers ON ("
                        . "adjudicators.customer_id = customers.id "
                        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                        . "divisions.location_id = locations.id "
                        . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "divisions.id = timeslots.sdivision_id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND divisions.division_date = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "GROUP BY divisions.id "
                    . "ORDER BY divisions.division_date, sort_timeslot, divisions.name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduledivisions', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'ssection_id', 'section_name', 'name', 'flags', 'options', 
                            'division_date_text', 'location_name', 'adjudicator_name', 'first_timeslot', 'last_timeslot',
                            ),
                        'flags' => array('options'=>$maps['schedulesection']['flags']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['scheduledivisions']) ) {
                    $festival['schedule_divisions'] = $rc['scheduledivisions'];
                    $nplists['schedule_divisions'] = array();
                    foreach($festival['schedule_divisions'] as $iid => $scheduledivision) {
                        $nplists['schedule_divisions'][] = $scheduledivision['id'];
                        $festival['schedule_divisions'][$iid]['name'] = $scheduledivision['section_name'] . ' - ' . $scheduledivision['name'];
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
                    . "IF(timeslots.name='', IFNULL(classes.name, ''), timeslots.name) AS timeslot_name, "
                    . "timeslots.groupname, "
                    . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                    . "timeslots.description, "
                    . "registrations.id AS reg_id, "
                    . "registrations.uuid AS reg_uuid, ";
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
//                    . "IFNULL(ssections.adjudicator1_id, 0) AS adjudicator_id, "
                    . "registrations.mark, "
                    . "registrations.placement, "
                    . "registrations.level, "
                    . "registrations.comments "
//                    . "IFNULL(comments.id, 0) AS comment_id, "
//                    . "IFNULL(comments.comments, '') AS comments, "
//                    . "IFNULL(comments.grade, '') AS grade, "
//                    . "IFNULL(comments.score, '') AS score "
                    . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "timeslots.id = registrations.timeslot_id "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id " 
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                        . "timeslots.sdivision_id = divisions.id "
                        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                        . "divisions.ssection_id = ssections.id "
                        . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY slot_time, timeslots.name, timeslots.id, registrations.display_name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'timeslots', 'fname'=>'timeslot_id', 
                        'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 
                            'name'=>'timeslot_name', 'groupname', 'time'=>'slot_time_text', 
                            'description', 
                            )),
                    array('container'=>'registrations', 'fname'=>'reg_id', 
                        'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name',
                            'title'=>'title1', 
                            'participation', 'video_url1', 'video_url2', 'video_url3', 
                            'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3',
//                            'adjudicator_id', 
                            'mark', 'placement', 'level', 'comments',
                            )),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $festival['timeslot_comments'] = isset($rc['timeslots']) ? $rc['timeslots'] : array();
                foreach($festival['timeslot_comments'] as $tid => $timeslot) {
                    $num_completed = 0;
                    $num_registrations = 0;
                    if( isset($timeslot['registrations']) ) {
                        foreach($timeslot['registrations'] as $rid => $registration) {
                            $num_registrations++;
                            if( $registration['comments'] != '' 
                                && ($registration['mark'] != '' || $registration['placement'] != '' || $registration['level'] != '' ) 
                                ) {
                                $num_completed++;
                            }
                        }
                    }
                    $festival['timeslot_comments'][$tid]['status'] = $num_completed . ' of ' . $num_registrations;
                } 
            }
            elseif( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 
                && isset($args['photos']) && $args['photos'] == 'yes'
                && isset($requested_section)
                ) {
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
                        . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                        . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . "";
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
                    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                        array('container'=>'timeslots', 'fname'=>'id', 'fields'=>array('id')),
                        array('container'=>'competitors', 'fname'=>'comp_id', 'fields'=>array('id'=>'comp_id', 'name', 'flags')),
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.857', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
                    }
                    $nophoto_timeslots = isset($rc['timeslots']) ? $rc['timeslots'] : array();
                }

                $strsql = "SELECT timeslots.id, "
                    . "timeslots.festival_id, "
                    . "timeslots.sdivision_id, "
                    . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                    . "timeslots.name, "
                    . "timeslots.groupname, "
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
                    . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                    . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "ORDER BY slot_time, timeslots.name, timeslots.id, images.sequence "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'scheduletimeslots', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'sdivision_id', 'slot_time_text', 
                            'name', 'groupname', 'description'),
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
                        $nophoto_names = '';
                        if( isset($nophoto_timeslots[$scheduletimeslot['id']]) ) {
                            foreach($nophoto_timeslots[$scheduletimeslot['id']]['competitors'] as $competitor) {
                                $nophoto_names .= ($nophoto_names != '' ? ', ' : '') . $competitor['name'];
                            }
                        }
                        if( $nophoto_names != '' ) {
                            $festival['timeslot_photos'][$tid]['nophoto_names'] = $nophoto_names;
                        }
                        //
                        // Check if class is set, then use class name
                        //
/*                        if( $scheduletimeslot['class_id'] > 0 ) {
                            if( $scheduletimeslot['name'] == '' && $scheduletimeslot['class_name'] != '' ) {
                                $festival['timeslot_photos'][$tid]['name'] = $scheduletimeslot['class_name'];
                            }
                            $festival['timeslot_photos'][$tid]['description'] .= ($festival['timeslot_photos'][$tid]['description'] != '' ? "\n":'');
                        } */
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
            elseif( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 
                && isset($args['results']) && $args['results'] == 'yes'
//                && isset($requested_section)
                ) {
                $strsql = "SELECT timeslots.id AS timeslot_id, "
                    . "timeslots.groupname, "
                    . "timeslots.start_num, ";
                    
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                    $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, ";
                } else {
                    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
                }
                $strsql .= "registrations.id, "
                    . "registrations.status, "
                    . "registrations.display_name, "
                    . "registrations.timeslot_sequence, "
                    . "registrations.flags, "
                    . "registrations.title1, "
                    . "registrations.title2, "
                    . "registrations.title3, "
                    . "registrations.title4, "
                    . "registrations.title5, "
                    . "registrations.title6, "
                    . "registrations.title7, "
                    . "registrations.title8, "
                    . "registrations.composer1, "
                    . "registrations.composer2, "
                    . "registrations.composer3, "
                    . "registrations.composer4, "
                    . "registrations.composer5, "
                    . "registrations.composer6, "
                    . "registrations.composer7, "
                    . "registrations.composer8, "
                    . "registrations.movements1, "
                    . "registrations.movements2, "
                    . "registrations.movements3, "
                    . "registrations.movements4, "
                    . "registrations.movements5, "
                    . "registrations.movements6, "
                    . "registrations.movements7, "
                    . "registrations.movements8, "
                    . "IF((timeslots.flags&0x02)=0x02, registrations.finals_mark, registrations.mark) AS mark, "
                    . "IF((timeslots.flags&0x02)=0x02, registrations.finals_placement, registrations.placement) AS placement, "
                    . "IF((timeslots.flags&0x02)=0x02, registrations.finals_level, registrations.level) AS level, "
                    . "registrations.provincials_status, "
                    . "registrations.provincials_position, "
                    . "classes.code AS class_code, "
                    . "classes.name AS class_name, "
                    . "categories.name AS category_name, "
                    . "sections.name AS section_name "
                    . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "("
                            . "((timeslots.flags&0x02) = 0 && timeslots.id = registrations.timeslot_id) "
                            . "OR ((timeslots.flags&0x02) = 0x02 && timeslots.id = registrations.finals_timeslot_id) "
                            . ") "
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
                    . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                    . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "ORDER BY timeslots.slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'results', 'fname'=>'id', 
                        'fields'=>array('id', 'timeslot_id', 'groupname', 'start_num', 
                            'status', 'display_name', 'slot_time_text', 'timeslot_sequence', 'flags',
                            'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                            'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                            'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                            'mark', 'placement', 'level', 'provincials_status', 'provincials_position',
                            'class_code', 'class_name', 'category_name', 'section_name',
                            ),
                        'maps'=>array(
                            'provincials_status' => $maps['registration']['provincials_status'],
                            'provincials_position' => $maps['registration']['provincials_position'],
                            ),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.63', 'msg'=>'Unable to load results', 'err'=>$rc['err']));
                }
                $festival['schedule_results'] = isset($rc['results']) ? $rc['results'] : array();
                foreach($festival['schedule_results'] as $sid => $result) {
                    $festival['schedule_results'][$sid]['timeslot_number'] = $result['timeslot_sequence'];
                    if( $result['start_num'] > 1 ) {
                        $festival['schedule_results'][$sid]['timeslot_number'] += ($result['start_num'] - 1);
                    }
                    if( $result['status'] == 77 ) {
                        $festival['schedule_results'][$sid]['mark'] .= ($result['mark'] != '' ? ' - ' : '') . 'No Show';
                    }
                    $titles = '';
                    for($i = 1; $i <= 8; $i++) {
                        if( $result["title{$i}"] != '' ) {
                            $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $result, $i);
                            if( isset($rc['title']) ) {
                                $titles .= ($titles != '' ? '<br/>' : '') . $rc['title'];
                            }
                        }
                    }
                    $festival['schedule_results'][$sid]['titles'] = $titles;
                }
            }   
            elseif( isset($args['provincials']) && $args['provincials'] == 'yes'
                && isset($requested_section)
                ) {
                $strsql = "SELECT timeslots.id AS timeslot_id, "
                    . "timeslots.groupname, "
                    . "timeslots.start_num, "
                    . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                    . "registrations.id, "
                    . "registrations.display_name, "
                    . "registrations.timeslot_sequence, "
                    . "registrations.title1, "
                    . "registrations.title2, "
                    . "registrations.title3, "
                    . "registrations.title4, "
                    . "registrations.title5, "
                    . "registrations.title6, "
                    . "registrations.title7, "
                    . "registrations.title8, "
                    . "registrations.composer1, "
                    . "registrations.composer2, "
                    . "registrations.composer3, "
                    . "registrations.composer4, "
                    . "registrations.composer5, "
                    . "registrations.composer6, "
                    . "registrations.composer7, "
                    . "registrations.composer8, "
                    . "registrations.movements1, "
                    . "registrations.movements2, "
                    . "registrations.movements3, "
                    . "registrations.movements4, "
                    . "registrations.movements5, "
                    . "registrations.movements6, "
                    . "registrations.movements7, "
                    . "registrations.movements8, "
                    . "registrations.mark, "
                    . "registrations.placement, "
                    . "registrations.level, "
                    . "registrations.provincials_status AS provincials_status_text, "
                    . "registrations.provincials_position, "
                    . "IF(registrations.provincials_position=0, 999, registrations.provincials_position) AS position_sort, "
                    . "classes.code AS class_code, "
                    . "IF(registrations.provincials_code <> '', registrations.provincials_code, classes.provincials_code) AS provincials_code, "
                    . "IF(registrations.provincials_code <> '', registrations.provincials_code, IF(classes.provincials_code='', 'z', classes.provincials_code)) AS sort_code, "
                    . "classes.name AS class_name, "
                    . "categories.name AS category_name, "
                    . "sections.name AS section_name "
                    . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                    . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "divisions.id = timeslots.sdivision_id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "timeslots.id = registrations.timeslot_id "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.provincials_code <> 'na' "
                        . "AND classes.provincials_code <> 'NA' "
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
                    . "WHERE divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND divisions.ssection_id = '" . ciniki_core_dbQuote($ciniki, $args['ssection_id']) . "' "
                    . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "ORDER BY sort_code, position_sort, class_code, registrations.mark, registrations.display_name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'provincials', 'fname'=>'id', 
                        'fields'=>array('id', 'timeslot_id', 'groupname', 'start_num', 'display_name', 
                            'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                            'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                            'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                            'mark', 'placement', 'level', 'provincials_code', 'provincials_status_text', 'provincials_position',
                            'class_code', 'class_name', 'category_name', 'section_name',
                            ),
                        'maps'=>array('provincials_status_text'=>$maps['registration']['provincials_status']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.168', 'msg'=>'Unable to load results', 'err'=>$rc['err']));
                }
                $festival['schedule_provincials'] = isset($rc['provincials']) ? $rc['provincials'] : array();
                foreach($festival['schedule_provincials'] as $sid => $result) {
                    $titles = '';
                    for($i = 1; $i <= 8; $i++) {
                        if( $result["title{$i}"] != '' ) {
                            $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $result, $i);
                            if( isset($rc['title']) ) {
                                $titles .= ($titles != '' ? '<br/>' : '') . $rc['title'];
                            }
                        }
                    }
                    $festival['schedule_provincials'][$sid]['titles'] = $titles;
                    $festival['schedule_provincials'][$sid]['provincials_position_text'] = '';
                    if( $result['provincials_position'] == 1 ) {
                        $festival['schedule_provincials'][$sid]['provincials_position_text'] = '1st';
                    } elseif( $result['provincials_position'] == 2 ) {
                        $festival['schedule_provincials'][$sid]['provincials_position_text'] = '2nd';
                    } elseif( $result['provincials_position'] == 3 ) {
                        $festival['schedule_provincials'][$sid]['provincials_position_text'] = '3rd';
                    } elseif( $result['provincials_position'] == 101 ) {
                        $festival['schedule_provincials'][$sid]['provincials_position_text'] = 'Alt 1';
                    } elseif( $result['provincials_position'] == 102 ) {
                        $festival['schedule_provincials'][$sid]['provincials_position_text'] = 'Alt 2';
                    } elseif( $result['provincials_position'] == 103 ) {
                        $festival['schedule_provincials'][$sid]['provincials_position_text'] = 'Alt 3';
                    }
                }
            }   
            elseif( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotsLoad');
                $rc = ciniki_musicfestivals_scheduleTimeslotsLoad($ciniki, $args['tnid'], [
                    'festival' => $festival,
                    'division_id' => $args['sdivision_id'],
                    ]);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $festival['schedule_timeslots'] = $rc['timeslots'];
                $nplists['schedule_timeslots'] = $rc['nplist'];

                //
                // Check if competitor schedules should be loaded
                // NOTE: This is for adding hoverinfo in schedule
                //
/*                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x2000) ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'competitorsSchedules');
                    $rc = ciniki_musicfestivals_competitorsSchedules($ciniki, $args['tnid'], [  
                        'festival_id' => $args['festival_id'],
                        ]);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1273', 'msg'=>'', 'err'=>$rc['err']));
                    }
                    $festival['competitors_schedules'] = $rc['competitors'];
                } */

                //
                // Check if volunteer shifts should be loaded
                //
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x01) ) {
                    $strsql = "SELECT divisions.id, "
                        . "DATE_FORMAT(divisions.division_date, '%Y-%m-%d') AS division_date, "
                        . "divisions.location_id, "
                        . "IFNULL(locations.building_id, 0) AS building_id "
                        . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                            . "divisions.location_id = locations.id "
                            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . ") "
                        . "WHERE divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . "";
                    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'division');
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1255', 'msg'=>'Unable to load division', 'err'=>$rc['err']));
                    }
                    if( !isset($rc['division']) ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1256', 'msg'=>'Unable to find requested division'));
                    }
                    $division = $rc['division'];

                    $strsql = "SELECT shifts.id, "
                        . "TIME_FORMAT(shifts.start_time, '%l:%i %p') as start_time, "
                        . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time, "
                        . "TIME_FORMAT(shifts.start_time, '%H%i') as sort_start_time, "
                        . "TIME_FORMAT(shifts.end_time, '%H%i') as sort_end_time, "
                        . "shifts.role, "
                        . "shifts.min_volunteers, "
                        . "shifts.max_volunteers, "
                        . "volunteers.id AS volunteer_id, "
                        . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS names "
                        . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
                        . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
                            . "shifts.id = assignments.shift_id "
                            . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . ") "
                        . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                            . "assignments.volunteer_id = volunteers.id "
                            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . ") "
                        . "LEFT JOIN ciniki_customers AS customers ON ("
                            . "volunteers.customer_id = customers.id "
                            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . ") "
                        . "WHERE shifts.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . "AND shifts.shift_date = '" . ciniki_core_dbQuote($ciniki, $division['division_date']) . "' "
                        . "AND ("
                            . "(object = 'ciniki.musicfestivals.location' "
                            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $division['location_id']) . "' "
                            . ") OR ("
                            . "object = 'ciniki.musicfestivals.building' "
                            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $division['building_id']) . "' "
                            . "))"
                        . "ORDER BY shifts.role, shifts.start_time, names "
                        . "";
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                        array('container'=>'shifts', 'fname'=>'id', 
                            'fields'=>array('id', 'start_time', 'end_time', 
                                'sort_start_time', 'sort_end_time', 
                                'role', 
                                'min_volunteers', 'max_volunteers', 'names'),
                            'dlists'=>array('names'=>'<br>'),
                            ),
                        array('container'=>'volunteers', 'fname'=>'volunteer_id', 
                            'fields'=>array('id'=>'volunteer_id', 'name'=>'names'),
                            ),
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1241', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
                    }
                    $festival['schedule_vshifts'] = isset($rc['shifts']) ? $rc['shifts'] : array();
                    foreach($festival['schedule_vshifts'] as $sid => $shift) {
                        $festival['schedule_vshifts'][$sid]['num_volunteers'] = isset($shift['volunteers']) ? count($shift['volunteers']) : 0;
                    }
                }
            }
        }

        //
        // Get the list of competitors and their timeslots
        //
        if( isset($args['schedule']) && $args['schedule'] == 'competitors' ) {
            $strsql = "SELECT competitors.id, "
                . "competitors.last, "
                . "competitors.first, "
                . "IF(ctype=50,competitors.name, CONCAT_WS(', ', competitors.last, competitors.first)) AS name, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
                . "timeslots.id AS timeslot_id, "
                . "DATE_FORMAT(divisions.division_date, '%b %D') AS date_text, ";
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS time_text, ";
            } else {
                $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS time_text, ";
            }
            $strsql .= "timeslots.groupname, "
                . "ssections.name AS section_name, "
                . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '??') AS location_name "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                    . "("
                        . "registrations.competitor1_id = competitors.id "
                        . "OR registrations.competitor2_id = competitors.id "
                        . "OR registrations.competitor3_id = competitors.id "
                        . "OR registrations.competitor4_id = competitors.id "
                        . "OR registrations.competitor5_id = competitors.id "
                        . ") "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "registrations.class_id = classes.id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                    . "registrations.timeslot_id = timeslots.id "
                    . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                    . "timeslots.sdivision_id = divisions.id "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                    . "divisions.ssection_id = ssections.id "
                    . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                    . "divisions.location_id = locations.id "
                    . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY name, divisions.division_date, timeslots.slot_time "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'competitors', 'fname'=>'name', 'fields'=>array('id', 'name')),
                array('container'=>'timeslots', 'fname'=>'timeslot_id', 
                    'fields'=>array('id'=>'timeslot_id', 'class_code', 'class_name', 'section_name', 'location_name', 'date_text', 'time_text', 'groupname')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.445', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
            }
            $festival['schedule_competitors'] = isset($rc['competitors']) ? $rc['competitors'] : array();
            $festival['schedule_competitors_max_timeslots'] = 1;
            foreach($festival['schedule_competitors'] AS $cid => $c) {
                if( isset($c['timeslots']) && count($c['timeslots']) > $festival['schedule_competitors_max_timeslots'] ) {
                    $festival['schedule_competitors_max_timeslots'] = count($c['timeslots']);
                }
                if( isset($c['timeslots']) ) {
                    $prev_timeslot = null;
                    $prev_tid = 0;
                    foreach($c['timeslots'] as $tid => $timeslot) {
                        if( $prev_timeslot != null 
                            && $prev_timeslot['date_text'] == $timeslot['date_text']
                            && $prev_timeslot['location_name'] != $timeslot['location_name']
                            ) {
                            $festival['schedule_competitors'][$cid]['conflict'] = 'yes';

                            $festival['schedule_competitors'][$cid]['timeslots'][$tid]['conflict'] = 'yes';
                            $festival['schedule_competitors'][$cid]['timeslots'][$prev_tid]['conflict'] = 'yes';
                        }

                        $prev_timeslot = $timeslot;
                        $prev_tid = $tid;
                    }
                }
                if( isset($args['competitors']) && $args['competitors'] == 'conflicts' 
                    && !isset($festival['schedule_competitors'][$cid]['conflict'])
                    ) {
                    unset($festival['schedule_competitors'][$cid]);
                }
            }
        }
        
        //
        // Get the list of teachers, and the number of registrations they are teaching
        //
        if( isset($args['schedule']) && $args['schedule'] == 'teachers' ) {
            $strsql = "SELECT customers.id, "
                . "customers.display_name AS name, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_customers AS customers ON ("
                    . "("
                        . "registrations.teacher_customer_id = customers.id "
                        . "OR registrations.teacher2_customer_id = customers.id "
                        . ") "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.teacher_customer_id > 0 "  
                . "AND registrations.timeslot_id > 0 "  // Scheduled registrations only
                . "AND registrations.participation <> 1 "   // Live only
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY customers.id "
                . "ORDER BY name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.718', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
            }
            $festival['schedule_teachers'] = isset($rc['teachers']) ? $rc['teachers'] : array();

            //
            // if an teacher is specified
            //
            if( isset($args['teacher_customer_id']) && $args['teacher_customer_id'] > 0 ) {
                $strsql = "SELECT registrations.id, "
                    . "registrations.display_name, "
                    . "classes.code AS class_code, "
                    . "classes.name AS class_name, ";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                    $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, "
                        . "registrations.timeslot_time AS slot_sort_time, ";
                } else {
                    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                        . "timeslots.slot_time AS slot_sort_time, ";
                }
                $strsql .= "DATE_FORMAT(divisions.division_date, '%b %e') AS division_date_text, "
                    . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name "
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
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND ("
                        . "registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' "
                        . "OR registrations.teacher2_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' "
                        . ") "
                    . "AND registrations.timeslot_id > 0 "  // Scheduled registrations only
                    . "AND registrations.participation <> 1 "   // Live only
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY divisions.division_date, slot_sort_time, location_name, registrations.display_name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'registrations', 'fname'=>'id', 
                        'fields'=>array('id', 'display_name', 'slot_time_text', 'division_date_text', 'location_name',
                            'class_code', 'class_name'
                            ),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.916', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
                }
                $festival['teacher_schedule'] = isset($rc['registrations']) ? $rc['registrations'] : array();
            }
        }

        //
        // Get the list of accompanists, and the number of registrations they are accompanying
        //
        if( isset($args['schedule']) && $args['schedule'] == 'accompanists' ) {
            $strsql = "SELECT customers.id, "
                . "customers.display_name AS name, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_customers AS customers ON ("
                    . "registrations.accompanist_customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.accompanist_customer_id > 0 "  
                . "AND registrations.timeslot_id > 0 "  // Scheduled registrations only
                . "AND registrations.participation <> 1 "   // Live only
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY customers.id "
                . "ORDER BY name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'accompanists', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.745', 'msg'=>'Unable to load accompanists', 'err'=>$rc['err']));
            }
            $festival['schedule_accompanists'] = isset($rc['accompanists']) ? $rc['accompanists'] : array();

            //
            // if an accompanist is specified
            //
            if( isset($args['accompanist_customer_id']) && $args['accompanist_customer_id'] > 0 ) {
                $strsql = "SELECT registrations.id, "
                    . "registrations.display_name, "
                    . "classes.code AS class_code, "
                    . "classes.name AS class_name, ";
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                    $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, "
                        . "registrations.timeslot_time AS slot_sort_time, ";
                } else {
                    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
                        . "timeslots.slot_time AS slot_sort_time, ";
                }
                $strsql .= "DATE_FORMAT(divisions.division_date, '%b %e') AS division_date_text, "
                    . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name "
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
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "registrations.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['accompanist_customer_id']) . "' "
                    . "AND registrations.timeslot_id > 0 "  // Scheduled registrations only
                    . "AND registrations.participation <> 1 "   // Live only
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY divisions.division_date, slot_sort_time, location_name, registrations.display_name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'registrations', 'fname'=>'id', 
                        'fields'=>array('id', 'display_name', 'slot_time_text', 'division_date_text', 'location_name',
                            'class_code', 'class_name'
                            ),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.746', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
                }
                $festival['accompanist_schedule'] = isset($rc['registrations']) ? $rc['registrations'] : array();
            }
        }

        //
        // Get the list of adjudicators, and the number of registrations they are adjudicating
        //
        if( isset($args['schedule']) && $args['schedule'] == 'adjudicators' ) {
            $strsql = "SELECT adjudicators.id, "
                . "customers.id AS customer_id, "
                . "customers.display_name AS name, "
                . "registrations.id AS reg_id, "
                . "registrations.participation, "
                . "registrations.display_name, "
                . "ssections.name AS section_name, "
                . "divisions.name AS division_name, "
                . "timeslots.name AS timeslot_name, "
                . "timeslots.groupname AS timeslot_groupname, "
                . "DATE_FORMAT(divisions.division_date, '%b %D') AS date_text, ";
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS time_text, ";
            } else {
                $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS time_text, ";
            }
            $strsql .= "registrations.mark, "
                . "registrations.placement, "
                . "registrations.level "
                . "FROM ciniki_musicfestival_schedule_sections AS ssections "
                . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                    . "ssections.id = divisions.ssection_id "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
                    . "("
                        . "(ssections.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.schedulesection') "
                        . "OR (divisions.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.scheduledivision') "
                        . ")"
                    . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
                    . "arefs.adjudicator_id = adjudicators.id "
//                    . "("
//                        . "ssections.adjudicator1_id = adjudicators.id "
//                        . "OR divisions.adjudicator_id = adjudicators.id "
//                        . ")"
                    . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "adjudicators.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                    . "divisions.id = timeslots.sdivision_id "
                    . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "timeslots.id = registrations.timeslot_id "
                    . $ipv_sql
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY customers.display_name, adjudicators.id, ssections.sequence, ssections.name, divisions.division_date, divisions.name, slot_time, registrations.timeslot_sequence "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'adjudicators', 'fname'=>'id', 
                    'fields'=>array('id', 'customer_id', 'name'),
                    ),
                array('container'=>'registrations', 'fname'=>'reg_id', 
                    'fields'=>array('id'=>'reg_id', 'display_name', 'participation',
                        'section_name', 'division_name', 'timeslot_name', 'timeslot_groupname', 'date_text', 'time_text', 
                        'mark', 'placement', 'level'),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.748', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
            }
            $festival['schedule_adjudicators'] = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

            foreach($festival['schedule_adjudicators'] as $aid => $adjudicator) {
                $festival['schedule_adjudicators'][$aid]['num_completed'] = 0;
                $festival['schedule_adjudicators'][$aid]['num_registrations'] = 0;

                if( isset($args['adjudicator_id']) && $args['adjudicator_id'] == $adjudicator['id'] ) {
                    $festival['adjudicator_schedule'] = isset($adjudicator['registrations']) ? $adjudicator['registrations'] : [];
                    $festival['adjudicator'] = $adjudicator;

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
                            . ") "
                        . "WHERE refs.object_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator['id']) . "' "
                        . "AND refs.object = 'ciniki.musicfestivals.adjudicator' "
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
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1170', 'msg'=>'Unable to load messages', 'err'=>$rc['err']));
                    }
                    $festival['adjudicator_messages'] = isset($rc['messages']) ? $rc['messages'] : array();
                    foreach($festival['adjudicator_messages'] as $mid => $message) {
                        if( $message['status'] == 30 ) {
                            $festival['adjudicator_messages'][$mid]['date'] = $message['dt_scheduled'];
                        } else {
                            $festival['adjudicator_messages'][$mid]['date'] = $message['dt_sent'];
                        }
                    }
                }
                if( isset($adjudicator['registrations']) ) {
                    foreach($adjudicator['registrations'] as $reg) {
                        if( $reg['mark'] <> '' || $reg['placement'] <> '' || $reg['level'] <> '' ) {
                            $festival['schedule_adjudicators'][$aid]['num_completed']++;
                        }
                        $festival['schedule_adjudicators'][$aid]['num_registrations']++;
                    }
                    unset($festival['schedule_adjudicators'][$aid]['registrations']);
                }
                if( ($festival['flags']&0x02) == 0x02 && isset($args['ipv']) 
                    && ($args['ipv'] == 'inperson' || $args['ipv'] == 'live' || $args['ipv'] == 'virtual') 
                    && $festival['schedule_adjudicators'][$aid]['num_registrations'] == 0
                    ) {
                    unset($festival['schedule_adjudicators'][$aid]);
                }
            }
        }

        //
        // Get the list of registrations recommended for provincials
        //
        if( isset($args['provincial_recommendations']) && $args['provincial_recommendations'] == 'yes' ) {
            $strsql = "SELECT sections.id AS section_id, "
                . "sections.name AS section_name, "
                . "categories.name AS category_name, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
                . "classes.provincials_code, "
                . "registrations.id, "
                . "registrations.display_name, "
                . "registrations.title1, "
                . "registrations.title2, "
                . "registrations.title3, "
                . "registrations.title4, "
                . "registrations.title5, "
                . "registrations.title6, "
                . "registrations.title7, "
                . "registrations.title8, "
                . "registrations.composer1, "
                . "registrations.composer2, "
                . "registrations.composer3, "
                . "registrations.composer4, "
                . "registrations.composer5, "
                . "registrations.composer6, "
                . "registrations.composer7, "
                . "registrations.composer8, "
                . "registrations.movements1, "
                . "registrations.movements2, "
                . "registrations.movements3, "
                . "registrations.movements4, "
                . "registrations.movements5, "
                . "registrations.movements6, "
                . "registrations.movements7, "
                . "registrations.movements8, "
                . "registrations.mark, "
                . "registrations.placement, "
                . "registrations.level, "
                . "registrations.provincials_code, "
                . "registrations.provincials_status, "
                . "registrations.provincials_status AS provincials_status_text, "
                . "registrations.provincials_position, "
                . "IF(registrations.provincials_status >= 70, 999, registrations.provincials_position) AS position_sort, "
                . "registrations.provincials_position AS provincials_position_text, "
                . "DATE_FORMAT(registrations.provincials_invite_date, '%b %e, %Y') AS provincials_invite_date, "
                . "registrations.provincials_notes, ";
            if( isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 ) {
                $strsql .= "CONCAT_WS(' - ', " //pclasses.code, "
//                    . "psections.name, "
//                    . "pcategories.name, "
                    . "pclasses.name) AS provincials_class_name ";
            } else {
                $strsql .= "registrations.provincials_code AS provincials_class_name "; 
            }
            $strsql .= "FROM ciniki_musicfestival_sections AS sections "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.provincials_status > 0 ";
            if( isset($args['provincials_status']) && $args['provincials_status'] != '' 
                && $args['provincials_status'] != '0' && is_numeric($args['provincials_status']) 
                ) {
                $strsql .= "AND registrations.provincials_status = '" . ciniki_core_dbQuote($ciniki, $args['provincials_status']) . "' ";
            }
            if( isset($args['provincials_class_code']) && $args['provincials_class_code'] != 0 && $args['provincials_class_code'] != '' ) {
                $strsql .= "AND registrations.provincials_code = '" . ciniki_core_dbQuote($ciniki, $args['provincials_class_code']) . "' "; 
            }
                $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") ";
            if( isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 ) {
                $strsql .= "LEFT JOIN ciniki_musicfestival_classes AS pclasses ON ("
                        . "registrations.provincials_code = pclasses.code "
                        . "AND pclasses.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['provincial-festival-id']) . "' "
                    . ") "
                    . "LEFT JOIN ciniki_musicfestival_categories AS pcategories ON ("
                        . "pclasses.category_id = pcategories.id "
                        . "AND pcategories.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['provincial-festival-id']) . "' "
                    . ") "
                    . "LEFT JOIN ciniki_musicfestival_sections AS psections ON ("
                        . "pcategories.section_id = psections.id "
                        . "AND psections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['provincial-festival-id']) . "' "
                    . ") ";
            }
            $strsql .= "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            if( isset($args['section_id']) && $args['section_id'] > 0 ) {
                $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
                
            }
            if( isset($args['provincials_class_code']) && $args['provincials_class_code'] != 0 && $args['provincials_class_code'] != '' ) {
                $strsql .= "ORDER BY registrations.provincials_code, position_sort ";
            } else {
                $strsql .= "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.code, classes.name, position_sort ";
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'registrations', 'fname'=>'id', 
                    'fields'=>array('id', 'section_name', 'category_name', 'class_code', 'class_name',
                        'display_name', 
                        'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                        'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                        'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                        'mark', 'placement', 'level', 'provincials_code', 
                        'provincials_status', 'provincials_status_text', 'provincials_position', 'provincials_position_text', 
                        'provincials_invite_date', 'provincials_notes', 'provincials_class_name',
                        ),
                    'maps'=>array(
                        'provincials_status_text'=>$maps['registration']['provincials_status'],
                        'provincials_position_text'=>$maps['registration']['provincials_position_short'],
                        ),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.540', 'msg'=>'Unable to load results', 'err'=>$rc['err']));
            }
            $festival['provincial_recommendations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
            $festival['provincial_class_codes'] = [];
            foreach($festival['provincial_recommendations'] as $rid => $reg) {
                $titles = '';
                for($i = 1; $i <= 8; $i++) {
                    if( $reg["title{$i}"] != '' ) {
                        $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $reg, $i);
                        if( isset($rc['title']) ) {
                            $titles .= ($titles != '' ? '<br/>' : '') . $rc['title'];
                        }
                    }
                }
                $festival['provincial_recommendations'][$rid]['titles'] = $titles;
            }
            if( isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 ) {
                $strsql = "SELECT DISTINCT registrations.provincials_code AS id, "
                    . "pclasses.name AS name "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "INNER JOIN ciniki_musicfestival_classes AS pclasses ON ("
                        . "registrations.provincials_code = pclasses.code "
                        . "AND pclasses.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['provincial-festival-id']) . "' "
                    . ") "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.provincials_status > 0 "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY pclasses.name ";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'codes', 'fname'=>'id', 
                        'fields'=>array('id', 'name'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.917', 'msg'=>'Unable to load codes', 'err'=>$rc['err']));
                }
                $festival['provincial_class_codes'] = isset($rc['codes']) ? $rc['codes'] : array();
                array_unshift($festival['provincial_class_codes'], ['id'=>0, 'name'=>'All Provincial Classes']);
            }
        }

        //
        // Get the list of accolades
        //
        if( isset($args['accolades']) && $args['accolades'] == 'list' ) {
            $strsql = "SELECT accolades.id, "
                . "accolades.subcategory_id, "
                . "accolades.name, "
                . "categories.name AS category_name, "
                . "subcategories.name AS subcategory_name, "
                . "accolades.donated_by, "
                . "accolades.first_presented, "
                . "accolades.amount, "
                . "accolades.criteria "
                . "FROM ciniki_musicfestival_accolades AS accolades "
                . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
                    . "accolades.subcategory_id = subcategories.id "
                    . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_accolade_categories AS categories ON ("
                    . "subcategories.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            if( isset($args['accolade_category_id']) && $args['accolade_category_id'] != '' && $args['accolade_category_id'] > 0 ) {
                $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_category_id']) . "' ";
            }
            if( isset($args['accolade_subcategory_id']) && $args['accolade_subcategory_id'] != '' && $args['accolade_subcategory_id'] > 0 ) {
                $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_subcategory_id']) . "' ";
            }
            $strsql .= "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'accolades', 'fname'=>'id', 
                    'fields'=>array('id', 'subcategory_id', 'category_name', 'subcategory_name', 'name', 'donated_by', 'first_presented', 'amount', 
                        'criteria'),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.623', 'msg'=>'Unable to load accolades', 'err'=>$rc['err']));
            }
            $festival['accolades'] = isset($rc['accolades']) ? $rc['accolades'] : array();
            $nplists['accolades'] = [];
            foreach($festival['accolades'] as $aid => $accolade) {
                $nplists['accolades'][] = $accolade['id'];
            }

            if( isset($args['accolade_id']) && $args['accolade_id'] > 0 && in_array($args['accolade_id'], $nplists['accolades']) ) {
                $strsql = "SELECT winners.id, "
                    . "winners.flags, "
                    . "IFNULL(registrations.display_name, winners.name) AS recipient_name, "
                    . "winners.awarded_amount "
                    . "FROM ciniki_musicfestival_accolade_winners AS winners "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "winners.registration_id = registrations.id "
                        . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE winners.accolade_id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_id']) . "' "
                    . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'recipients', 'fname'=>'id', 
                        'fields'=>array(
                            'id', 'flags', 'recipient_name', 'awarded_amount'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1129', 'msg'=>'Unable to load recipients', 'err'=>$rc['err']));
                }
                $festival['accolade_recipients'] = isset($rc['recipients']) ? $rc['recipients'] : array();
                $nplists['accolade_recipients'] = [];
                if( count($festival['accolade_recipients']) > 0 ) {
                    $festival['totals']['accolade_recipients'] = ['awarded_amount' => 0];
                    foreach($festival['accolade_recipients'] as $rid => $recipient) {
                        $nplists['accolade_recipients'][] = $recipient['id'];
                        $festival['totals']['accolade_recipients']['awarded_amount'] += $recipient['awarded_amount'];
                    }
                }
            }
        }

        //
        // Get the list of accolade recipients
        //
        if( isset($args['accolades']) && $args['accolades'] == 'recipients' ) {
            $strsql = "SELECT winners.id, "
                . "winners.flags, "
                . "IFNULL(registrations.display_name, winners.name) AS recipient_name, "
                . "winners.awarded_amount, "
                . "accolades.id AS accolade_id, "
                . "accolades.name, "
                . "accolades.subcategory_id, "
                . "categories.name AS category_name, "
                . "subcategories.name AS subcategory_name "
                . "FROM ciniki_musicfestival_accolade_winners AS winners "
                . "INNER JOIN ciniki_musicfestival_accolades AS accolades ON ("
                    . "winners.accolade_id = accolades.id "
                    . "AND accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "winners.registration_id = registrations.id "
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
                    . "accolades.subcategory_id = subcategories.id "
                    . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_accolade_categories AS categories ON ("
                    . "subcategories.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            if( isset($args['accolade_category_id']) && $args['accolade_category_id'] != '' && $args['accolade_category_id'] > 0 ) {
                $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_category_id']) . "' ";
            }
            if( isset($args['accolade_subcategory_id']) && $args['accolade_subcategory_id'] != '' && $args['accolade_subcategory_id'] > 0 ) {
                $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_subcategory_id']) . "' ";
            }
            $strsql .= "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'recipients', 'fname'=>'id', 
                    'fields'=>array('id', 'flags', 'recipient_name', 'awarded_amount', 'accolade_id', 'name', 'subcategory_id',
                        'category_name', 'subcategory_name',),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1128', 'msg'=>'Unable to load recipients', 'err'=>$rc['err']));
            }
            $festival['accolades_recipients'] = isset($rc['recipients']) ? $rc['recipients'] : array();
            $nplists['accolades_recipients'] = [];
            if( count($festival['accolades_recipients']) > 0 ) {
                $festival['totals']['accolades_recipients'] = ['awarded_amount' => 0];
                foreach($festival['accolades_recipients'] as $rid => $recipient) {
                    $nplists['accolades_recipients'][] = $recipient['id'];
                    $festival['totals']['accolades_recipients']['awarded_amount'] += $recipient['awarded_amount'];
                }
            }
        }

        if( isset($args['accolades']) && in_array($args['accolades'], ['list', 'recipients']) ) {
            //
            // Get the list of categories and subcategories
            //
            $strsql = "SELECT id, sequence, name "
                . "FROM ciniki_musicfestival_accolade_categories AS categories "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY sequence, name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'categories', 'fname'=>'id', 
                    'fields'=>array('id', 'sequence', 'name'),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1179', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
            }
            $festival['accolade_categories'] = isset($rc['categories']) ? $rc['categories'] : array();
            array_unshift($festival['accolade_categories'], ['id'=>0, 'sequence'=>0, 'name'=>'All']);

            $festival['accolade_subcategories'] = [];
            if( isset($args['accolade_category_id']) && $args['accolade_category_id'] > 0 ) {
                $strsql = "SELECT id, sequence, name "
                    . "FROM ciniki_musicfestival_accolade_subcategories AS subcategories "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND category_id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_category_id']) . "' "
                    . "ORDER BY sequence, name "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'subcategories', 'fname'=>'id', 
                        'fields'=>array('id', 'sequence', 'name'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1180', 'msg'=>'Unable to load subcategories', 'err'=>$rc['err']));
                }
                $festival['accolade_subcategories'] = isset($rc['subcategories']) ? $rc['subcategories'] : array();
                array_unshift($festival['accolade_subcategories'], ['id'=>0, 'sequence'=>0, 'name'=>'All']);
            }
        }

        //
        // Get the list of competitors
        //
        if( isset($args['competitors']) && $args['competitors'] == 'yes' ) {
            $strsql = "SELECT registrations.competitor1_id, "
                . "registrations.competitor2_id, "
                . "registrations.competitor3_id, "
                . "registrations.competitor4_id, "
                . "registrations.competitor5_id, "
                . "IFNULL(classes.code, '') AS code "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "registrations.class_id = classes.id "
                    . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.770', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
            }
            $competitors = array();
            foreach($rc['rows'] as $row) {
                for($i = 1; $i <= 5; $i++) {
                    if( $row['code'] == '' ) {
                        continue;
                    }
                    if( $row["competitor{$i}_id"] > 0 ) {
                        if( !isset($competitors[$row["competitor{$i}_id"]]) ) {
                            $competitors[$row["competitor{$i}_id"]] = $row['code'];
                        } else {
                            $competitors[$row["competitor{$i}_id"]] .= ',' . $row['code'];
                        }
                    }
                }
            }

            $strsql = "SELECT competitors.id, "
                . "competitors.festival_id, "
                . "competitors.name, "
                . "competitors.ctype, ";
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                $strsql .= "competitors.pronoun, ";
            } else {
                $strsql .= "'' AS pronoun, ";
            }
            $strsql .= "IF((competitors.flags&0x01) = 0x01, 'Signed', '') AS waiver_signed, "
                . "IF((competitors.flags&0x02) = 0x02, 'Yes', 'NO PHOTOS') AS photos, "
                . "IF((competitors.flags&0x04) = 0x04, 'Yes', 'Name Withheld') AS name_published, "
                . "competitors.city, "
                . "competitors.province, "
                . "competitors.notes "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY competitors.name ";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'competitors', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'name', 'ctype', 'pronoun', 
                        'waiver_signed', 'photos', 'name_published',
                        'city', 'province', 'notes',
                        ),
//                    'dlists'=>array('classcodes'=>', '),
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
                if( isset($competitors[$comp['id']]) ) {
                    $festival['competitors'][$cid]['classcodes'] = $competitors[$comp['id']];
                } else {
                    $festival['competitors'][$cid]['classcodes'] = '';
                }
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
            $strsql = "SELECT adjudicators.id, "
                . "adjudicators.discipline, "
                . "adjudicators.festival_id, "
                . "adjudicators.customer_id, "
                . "adjudicators.flags AS options, "
                . "customers.display_name "
                . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "adjudicators.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY display_name "
                . "";
            if( isset($args['comments']) && $args['comments'] == 'yes' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
                $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'adjudicators', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name', 'discipline')),
                    ));
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'adjudicators', 'fname'=>'id', 
                        'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name', 'discipline', 'options'),
                        'flags'=>array('options'=>$maps['adjudicator']['flags']),
                        ),
                    ));
            }
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['adjudicators']) ) {
                $festival['adjudicators'] = $rc['adjudicators'];
                foreach($festival['adjudicators'] as $iid => $adjudicator) {
                    $nplists['adjudicators'][] = $adjudicator['id'];
                }
            } else {
                $festival['adjudicators'] = array();
            }
        }

        //
        // Get the list of locations
        //
        if( isset($args['locations']) && $args['locations'] == 'yes' ) {
            $strsql = "SELECT buildings.id, "
                . "buildings.name, "
                . "buildings.category, "
                . "buildings.sequence, "
                . "buildings.address1, "
                . "buildings.city, "
                . "rooms.id AS room_id, "
                . "rooms.roomname, "
                . "rooms.disciplines "
                . "FROM ciniki_musicfestival_buildings AS buildings "
                . "LEFT JOIN ciniki_musicfestival_locations AS rooms ON ("
                    . "buildings.id = rooms.building_id "
                    . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE buildings.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY buildings.category, buildings.sequence, buildings.name, rooms.sequence, rooms.roomname "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'buildings', 'fname'=>'id', 
                    'fields'=>array('id', 'category', 'sequence', 'name', 'address1', 'city')),
                array('container'=>'rooms', 'fname'=>'room_id', 
                    'fields'=>array('id'=>'room_id', 'roomname', 'disciplines')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['buildings']) ) {
                $festival['buildings'] = $rc['buildings'];
                foreach($festival['buildings'] as $iid => $building) {
                    $room_disciplines = '';
                    foreach($building['rooms'] as $room) {
                        $room_disciplines .= ($room_disciplines != '' ? "<br/>" : '')
                            . ($room['roomname'] != '' ? $room['roomname'] : '')
                            . ($room['disciplines'] != '' ? ($room['roomname'] != '' ? ' - ' : '') . $room['disciplines'] : '');
                    }
                    $festival['buildings'][$iid]['room_disciplines'] = $room_disciplines;
                    $nplists['buildings'][] = $building['id'];
                }
            } else {
                $festival['buildings'] = array();
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
        }

        //
        // Get the list of title lists
        //
        if( isset($args['titlelists']) && $args['titlelists'] == 'yes' ) {
            $strsql = "SELECT id, "
                . "name, "
                . "flags, "
                . "col1_field, "
                . "col1_label, "
                . "col2_field, "
                . "col2_label, "
                . "col3_field, "
                . "col3_label, "
                . "col4_field, "
                . "col4_label "
                . "FROM ciniki_musicfestivals_titlelists "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'lists', 'fname'=>'id', 'fields'=>array('id', 'name', 'flags',
                    'col1_field', 'col1_label',
                    'col2_field', 'col2_label',
                    'col3_field', 'col3_label',
                    'col4_field', 'col4_label',
                    )),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival['titlelists'] = isset($rc['lists']) ? $rc['lists'] : array();

            //
            // Check if need query for list sections
            //
            if( isset($args['titlelist_id']) && $args['titlelist_id'] > 0 ) {
                $order_sql = '';
                foreach($festival['titlelists'] as $list) {
                    if( $list['id'] == $args['titlelist_id'] ) {
                        for($i = 1; $i < 5; $i++ ) {
                            if( $list["col{$i}_field"] != 'none' && $list["col{$i}_field"] != '' ) {
                                $order_sql .= ($order_sql != '' ? ', ' : '') . $list["col{$i}_field"];
                            }
                        }
                    }
                }
                if( $order_sql == '' ) {
                    $order_sql = "ORDER BY title, movements, composer ";
                } else {
                    $order_sql = "ORDER BY " . $order_sql;
                }
                $strsql = "SELECT id, "
                    . "title, "
                    . "movements, "
                    . "composer, "
                    . "source_type "
                    . "FROM ciniki_musicfestivals_titles "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND list_id = '" . ciniki_core_dbQuote($ciniki, $args['titlelist_id']) . "' "
                    . $order_sql
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'titles', 'fname'=>'id', 
                        'fields'=>array('id', 'title', 'movements', 'composer', 'source_type')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $festival['titles'] = isset($rc['titles']) ? $rc['titles'] : array();
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
            // Get the templates
            //
            $strsql = "SELECT messages.id, "
                . "messages.subject "
                . "FROM ciniki_musicfestival_messages AS messages "
                . "WHERE messages.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND messages.status = 5 "
                . "ORDER BY subject "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'templates', 'fname'=>'id', 'fields'=>array('id', 'subject')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1056', 'msg'=>'Unable to load templates', 'err'=>$rc['err']));
            }
            $festival['message_templates'] = isset($rc['templates']) ? $rc['templates'] : array();

            //
            // Get the list of messages
            //
            if( !isset($args['messages_status']) ) {
                $args['messages_status'] = 10;
            }
            $strsql = "SELECT messages.id, "
                . "messages.subject, ";
            if( $args['messages_status'] == 50 ) {
                $strsql .= "messages.dt_sent AS date_text ";
//                $strsql .= "DATE_FORMAT(messages.dt_sent, '%b %e, %Y %l:%i %p') AS date_text ";
            } elseif( $args['messages_status'] == 30 ) {
                $strsql .= "messages.dt_scheduled AS date_text ";
//                $strsql .= "DATE_FORMAT(messages.dt_scheduled, '%b %e, %Y %l:%i %p') AS date_text ";
            } else {
                $strsql .= "messages.date_added AS date_text ";
//                $strsql .= "DATE_FORMAT(messages.date_added, '%b %e, %Y %l:%i %p') AS date_text ";
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
                    'fields'=>array('id', 'subject', 'date_text'),
                    'utctotz'=>array('date_text'=>array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i A')),
                    ),
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
            // 
            // Get the registration counts
            //
            $strsql = "SELECT registrations.member_id, COUNT(*) AS num_registrations "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY member_id "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
            $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.822', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $reg_counts = isset($rc['num']) ? $rc['num'] : array();

            //
            // Load the member list
            //
            $strsql = "SELECT members.id, "
                . "members.name, "
                . "members.shortname, "
                . "members.category, "
                . "members.status, "
                . "members.status AS status_text, "
//                . "members.customer_id, "
                . "IFNULL(customers.id, 0) AS customer_id, "
                . "IFNULL(customers.first, '') AS customer_name, "
                . "IFNULL(customers.display_name, '') AS display_name, "
                . "IFNULL(emails.email, '') AS emails, "
                . "IFNULL(fmembers.reg_start_dt, '') AS reg_start_dt_display, "
                . "IFNULL(fmembers.reg_end_dt, '') AS reg_end_dt_display, "
                . "IFNULL(fmembers.latedays, '') AS latedays "
//                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestivals_members AS members "
                . "LEFT JOIN ciniki_musicfestival_members AS fmembers ON ("
                    . "members.id = fmembers.member_id "
                    . "AND fmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND fmembers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_member_customers AS mc ON ("
                    . "members.id = mc.member_id "
                    . "AND mc.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "mc.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customer_emails AS emails ON ("
                    . "customers.id = emails.customer_id "
                    . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
/*                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "members.id = registrations.member_id "
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") " */
                . "WHERE members.status < 90 " // Active, Closed
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                . "GROUP BY members.id "
                . "ORDER BY members.shortname, members.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'members', 'fname'=>'id', 
                    'fields'=>array('id', 'customer_id', 'customer_name', 'name', 'shortname', 'category', 'status', 
                        'reg_start_dt_display', 'reg_end_dt_display', 'latedays',
                        ),
                    'utctotz'=>array(
                        'reg_start_dt_display' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                        'reg_end_dt_display' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                        ),
                    ),
                array('container'=>'customers', 'fname'=>'customer_id',
                    'fields'=>array('id'=>'customer_id', 'name'=>'customer_name', 'display_name', 'emails'),
                    'dlists'=>array('emails'=>', '),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.190', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
            }
            if( isset($rc['members']) ) {
                $festival['members'] = $rc['members'];
                $festival['members_customers'] = [];
                $festival['members_ids'] = array();
                foreach($festival['members'] as $k => $v) {
                    $festival['members_ids'][] = $v['id'];
                    $festival['members'][$k]['num_registrations'] = '';
                    if( isset($reg_counts[$v['id']]) ) {
                        $festival['members'][$k]['num_registrations'] = $reg_counts[$v['id']];
                    }
                    $festival['members'][$k]['admins'] = '';
                    if( isset($v['customers']) ) {
                        foreach($v['customers'] as $customer) {
                            $festival['members_customers'][] = [
                                'customer_id' => $customer['id'],
                                'display_name' => $customer['display_name'],
                                ];
                            $festival['members'][$k]['admins'] .= ($festival['members'][$k]['admins'] != '' ? ', ' : '')
                                . $customer['name'] . ' [' . (isset($customer['emails']) ? $customer['emails'] : '') . ']';
                            
                        }
                    }
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
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.721', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
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
                . "sponsors.flags, "
                . "tags.tag_name AS tags "
                . "FROM ciniki_musicfestival_sponsors AS sponsors "
                . "LEFT JOIN ciniki_musicfestival_sponsor_tags AS tags ON ("
                    . "sponsors.id = tags.sponsor_id "
                    . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "ORDER BY sponsors.name, tags.tag_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'sponsors', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'url', 'sequence', 'flags', 'tags'),
                    'dlists'=>array('tags'=>','),
                    ),
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
                . "(SELECT COUNT(new.id) "
                    . "FROM ciniki_musicfestival_recommendation_entries AS new "
                    . "WHERE classes.id = new.class_id "
                    . "AND new.status < 30 "    // New
                    . "AND new.position < 100 " // Ignore alternates
                    . "AND new.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") AS num_new, "
                . "(SELECT COUNT(ar.id) "
                    . "FROM ciniki_musicfestival_recommendation_entries AS ar "
                    . "WHERE classes.id = ar.class_id "
                    . "AND (ar.status = 30 OR ar.status = 50) "
                    . "AND ar.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") AS num_acceptedreg, "
                . "(SELECT COUNT(entries.id) "
                    . "FROM ciniki_musicfestival_recommendation_entries AS entries "
                    . "WHERE classes.id = entries.class_id "
                    . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") AS num_entries "
                . "FROM ciniki_musicfestival_categories AS categories "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY categories.sequence, classes.id "
                . "ORDER BY categories.sequence, classes.sequence, classes.code, classes.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'code', 'name', 'num_new', 'num_acceptedreg', 'num_entries')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.191', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
            }
            $festival['recommendation_classes'] = isset($rc['classes']) ? $rc['classes'] : array();

            if( !isset($args['class_id']) || $args['class_id'] == '' || $args['class_id'] == 0 ) {
                if( isset($festival['recommendation_class'][0]['id']) ) {
                    $args['class_id'] = $festival['recommendation_classes'][0]['id'];
                } else {
                    $args['class_id'] = 0;
                }
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
                    . "entries.status, "
                    . "IF(entries.status >= 70, 600, entries.position) AS position, "
                    . "entries.name, "
                    . "entries.mark, "
                    . "recommendations.id AS recommendation_id, "
                    . "recommendations.member_id, "
                    . "recommendations.section_id, "
                    . "recommendations.date_submitted, "
                    . "members.shortname AS member_name, "
                    . "member.reg_end_dt AS end_date, "
//                    . "DATE_FORMAT(member.reg_end_dt, '%b %d') AS end_date, "
                    . "member.latedays "
                    . "FROM ciniki_musicfestival_recommendation_entries AS entries "
                    . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                        . "entries.recommendation_id = recommendations.id "
                        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                        . "recommendations.member_id = members.id "
                        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_members AS member ON ("
                        . "members.id = member.member_id "
                        . "AND member.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                        . "AND member.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE entries.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
                    . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY recommendations.date_submitted, position "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'entries', 'fname'=>'id', 
                        'fields'=>array('id', 'status', 'recommendation_id', 'position', 'name', 'mark',
                            'date_submitted', 'member_id', 'section_id', 'member_name', 'end_date', 'latedays'),
                        'utctotz'=>array(
                            'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                            'end_date'=> array('timezone'=>$intl_timezone, 'format'=>'M j'),
                            ),
                        'maps'=>array('position'=>$maps['recommendationentry']['position_shortname']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1172', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
                }
                $festival['recommendation_entries'] = isset($rc['entries']) ? $rc['entries'] : array();
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
                . "members.shortname AS name, "
                . "(SELECT COUNT(rec_entries.id) "
                    . "FROM ciniki_musicfestival_recommendations AS rec "
                    . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS rec_entries ON ("
                        . "rec.id = rec_entries.recommendation_id "
                        . "AND (rec_entries.position < 100 AND rec_entries.status < 70) "
                        . "AND rec_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE members.id = rec.member_id "
                    . "AND rec.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND rec.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") AS num_recommendations, "
                . "(SELECT COUNT(alt_entries.id) "
                    . "FROM ciniki_musicfestival_recommendations AS alt "
                    . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS alt_entries ON ("
                        . "alt.id = alt_entries.recommendation_id "
                        . "AND (alt_entries.position >= 100 OR alt_entries.status >= 70) "
                        . "AND alt_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE members.id = alt.member_id "
                    . "AND alt.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND alt.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") AS num_alternates "
                . "FROM ciniki_musicfestivals_members AS members "
                . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                    . "members.id = recommendations.member_id "
                    . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
//                . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
//                    . "recommendations.id = entries.recommendation_id "
//                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                    . ") "
                . "WHERE members.status = 10 " // Active
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY members.id "
                . "ORDER BY members.shortname "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_recommendations', 'num_alternates')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.599', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
            }
            $festival['recommendation_members'] = isset($rc['members']) ? $rc['members'] : array();

            //
            // Get the member submission
            //
            if( isset($args['member_id']) && $args['member_id'] > 0 
                && isset($args['member_submissions']) && $args['member_submissions'] == 'yes'
                ) {
                $strsql = "SELECT recommendations.id, "
                    . "recommendations.adjudicator_name, "
                    . "recommendations.section_id, "
                    . "recommendations.member_id, "
                    . "recommendations.status, "
                    . "recommendations.status AS status_text, "
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
                            'status', 'status_text',
                            ),
                        'utctotz'=>array(
                            'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                            ),
                        'maps'=>array('status_text'=>$maps['recommendation']['status']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1171', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
                }
                $festival['member_submissions'] = isset($rc['submissions']) ? $rc['submissions'] : array();
                foreach($festival['member_submissions'] as $k => $v) {
                    $nplists['submissions'][] = $v['id'];
                }
            }
            //
            // Get the member recommendation entries
            //
            if( isset($args['member_id']) && $args['member_id'] > 0 
                && isset($args['member_entries']) && $args['member_entries'] == 'yes'
                ) {
                $strsql = "SELECT entries.id, "
                    . "entries.status, "
                    . "entries.position, "
                    . "entries.name, "
                    . "entries.mark, "
                    . "classes.code AS class_code, "
                    . "classes.name AS class_name "
                    . "FROM ciniki_musicfestival_recommendations AS recommendations "
                    . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                        . "recommendations.id = entries.recommendation_id "
                        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                        . "entries.class_id = classes.id "
                        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
                    . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY class_code, class_name, position "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'entries', 'fname'=>'id', 
                        'fields'=>array('id', 'status', 'class_code', 'class_name', 'position', 'name', 'mark'),
                        'maps'=>array('position'=>$maps['recommendationentry']['position_shortname']),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.598', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
                }
                $entries = isset($rc['entries']) ? $rc['entries'] : array();
                foreach($entries as $eid => $entry) {
                    $entries[$eid]['class_name'] = $entry['class_code'] . ' - ' . $entry['class_name'];
//                    if( preg_match("/^([^-]+) - /", $recommendation['section_name'], $m) ) {
//                        if( $m[1] != '' ) {
//                            $recommendation['entries'][$eid]['name'] = str_replace($m[1] . ' - ', '', $recommendation['entries'][$eid]['name']);
//                        }
//                    }
                }
                $festival['recommendation_member_entries'] = $entries;
            }
        }
        //
        // Get the counts for each submission status
        //
        if( isset($args['recommendation_statuses']) && $args['recommendation_statuses'] == 'yes' ) {
            $strsql = "SELECT recommendations.status, "
                . "COUNT(recommendations.id) AS num_submissions "
                . "FROM ciniki_musicfestival_recommendations AS recommendations "
                . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY recommendations.status "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
            $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'statuses');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1047', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $festival['recommendation_statuses'] = [
                '10'=>array('status' => 10, 'name' => 'Draft', 'num_submissions'=>(isset($rc['statuses'][10]) ? $rc['statuses'][10] : '')),
                '30'=>array('status' => 30, 'name' => 'Submitted', 'num_submissions'=>(isset($rc['statuses'][30]) ? $rc['statuses'][30] : '')),
                '50'=>array('status' => 50, 'name' => 'Reviewed', 'num_submissions'=>(isset($rc['statuses'][50]) ? $rc['statuses'][50] : '')),
                ];
        }

        //
        // Get the submissions of a status
        //
        if( isset($args['recommendation_status']) && $args['recommendation_status'] != '' ) {
            $strsql = "SELECT recommendations.id, "
                . "recommendations.adjudicator_name, "
                . "recommendations.section_id, "
                . "recommendations.member_id, "
                . "members.shortname AS member_name, "
                . "recommendations.status, "
                . "recommendations.status AS status_text, "
                . "recommendations.date_submitted, "
                . "sections.name AS section_name, "
                . "COUNT(entries.id) AS num_entries "
                . "FROM ciniki_musicfestival_recommendations AS recommendations "
                . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                    . "recommendations.member_id = members.id "
                    . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "recommendations.section_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                    . "recommendations.id = entries.recommendation_id "
                    . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE recommendations.status = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_status']) . "' "
                . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY recommendations.id ";
            if( $args['recommendation_status'] == 50 ) {
                $strsql .= "ORDER BY date_submitted DESC ";
            } else {
                $strsql .= "ORDER BY date_submitted ";
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'submissions', 'fname'=>'id', 
                    'fields'=>array(
                        'id', 'adjudicator_name', 'member_name', 'section_id', 'section_name', 'num_entries', 'date_submitted',
                        'status', 'status_text', 
                        ),
                    'utctotz'=>array(
                        'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                        ),
                    'maps'=>array('status_text'=>$maps['recommendation']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.600', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
            }
            $festival['recommendation_submissions'] = isset($rc['submissions']) ? $rc['submissions'] : array();
            foreach($festival['recommendation_submissions'] as $k => $v) {
                $nplists['submissions'][] = $v['id'];
            }
        }

        //
        // Get the number of registrations 
        //
/*        $strsql = "SELECT COUNT(id) "
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
        } */
        if( isset($args['statistics']) && $args['statistics'] == 'overview' ) {
            $festival['statistics'] = array(
                'num_registrations' => array('label' => 'Total Registrations', 'value' => ''),
                );

            $strsql = "SELECT participation, COUNT(*) AS num "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                    . "registrations.invoice_id = items.invoice_id "
                    . "AND registrations.id = items.object_id "
                    . "AND items.object = 'ciniki.musicfestivals.registration' "
                    . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                    . "items.invoice_id = invoices.id "
                    . "AND invoices.invoice_type = 10 "
                    . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY participation "
                . "ORDER BY participation "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
            $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.767', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }

            $total_reg = 0;
            if( ($festival['flags']&0x02) == 0x02 ) {
                $festival['statistics']['live_registrations'] = array('label'=>'Live Registrations', 'value'=>'');
                $festival['statistics']['virtual_registrations'] = array('label'=>'Virtual Registrations', 'value'=>'');
                foreach($rc['num'] as $p => $v) {
                    $total_reg += $v;
                    if( $p == 0 ) {
                        $festival['statistics']['live_registrations']['value'] = $v;
                    } elseif( $p == 1 ) {
                        $festival['statistics']['virtual_registrations']['value'] = $v;
                    }
                }
            }
            elseif( ($festival['flags']&0x10) == 0x10 ) {
                $festival['statistics']['reg_registrations'] = array('label'=>'Regular Registrations', 'value'=>'');
                $festival['statistics']['plus_registrations'] = array('label'=>'Plus Registrations', 'value'=>'');
                foreach($rc['num'] as $p => $v) {
                    $total_reg += $v;
                    if( $p == 0 ) {
                        $festival['statistics']['reg_registrations']['value'] = $v;
                    } elseif( $p == 2 ) {
                        $festival['statistics']['plus_registrations']['value'] = $v;
                    }
                }
            } else {
                foreach($rc['num'] as $p => $v) {
                    $total_reg += $v;
                }
            }
            $festival['statistics']['num_registrations']['value'] = $total_reg;

            //
            // Get the number of teachers
            //
            $strsql = "SELECT COUNT(DISTINCT teacher_customer_id) AS num "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND teacher_customer_id > 0 "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.778', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $festival['statistics']['num_teachers'] = array('label'=>'Number of Teachers', 'value'=>$rc['num']);

            //
            // Get the number of accompanists
            //
            $strsql = "SELECT COUNT(DISTINCT accompanist_customer_id) AS num "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND accompanist_customer_id > 0 "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1173', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $festival['statistics']['num_accompanists'] = array('label'=>'Number of Accompanists', 'value'=>$rc['num']);

            //
            // Get the number of individual competitors
            //
            $total_competitors = 0;
            $strsql = "SELECT COUNT(DISTINCT competitors.id) AS num "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                    . "(registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                    . "AND competitors.ctype = 10 "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1174', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $festival['statistics']['num_individuals'] = array('label'=>'Number of Individuals', 'value'=>$rc['num']);
            $total_competitors += $rc['num'];

            //
            // Get the number of group competitors
            //
            $strsql = "SELECT COUNT(DISTINCT competitors.id) AS num "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                    . "(registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                    . "AND competitors.ctype = 50 "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1175', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $festival['statistics']['num_groups'] = array('label'=>'Number of Groups', 'value'=>$rc['num']);

            //
            // Get the number of group competitors
            //
            $strsql = "SELECT SUM(competitors.num_people) AS num "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                    . "(registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                    . "AND competitors.ctype = 50 "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1176', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $festival['statistics']['num_group_competitors'] = array('label'=>'Number of Group Competitors', 'value'=>$rc['num']);
            $total_competitors += $rc['num'];

            //
            // Total Competitors
            //
            $festival['statistics']['num_competitors'] = array('label'=>'Total Number of Competitors', 'value'=>$total_competitors);

            //
            // Get the number of titles
            //
            $strsql = "SELECT COUNT(IF(title1 <> '', title1, null)) "
                . "+ COUNT(IF(title2 <> '', title2, null)) "
                . "+ COUNT(IF(title3 <> '', title3, null)) "
                . "+ COUNT(IF(title4 <> '', title4, null)) "
                . "+ COUNT(IF(title5 <> '', title5, null)) "
                . "+ COUNT(IF(title6 <> '', title6, null)) "
                . "+ COUNT(IF(title7 <> '', title7, null)) "
                . "+ COUNT(IF(title8 <> '', title8, null)) "
                . "AS num "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.768', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $festival['statistics']['num_titles'] = array('label'=>'Number of Titles', 'value'=>$rc['num']);

            //
            // Get the amount of paid registration fees
            //
            $strsql = "SELECT registrations.participation, "
                . "SUM(items.total_amount) AS total_fees "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                    . "registrations.invoice_id = items.invoice_id "
                    . "AND registrations.id = items.object_id "
                    . "AND items.object = 'ciniki.musicfestivals.registration' "
                    . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                    . "items.invoice_id = invoices.id "
                    . "AND invoices.invoice_type = 10 "
                    . "AND invoices.status = 50 "
                    . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY participation "
                . "ORDER BY participation "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'fees', 'fname'=>'participation', 
                    'fields'=>array('participation', 'total_fees'),
                    'maps'=>array('participation'=>$maps['registration']['participation']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1081', 'msg'=>'Unable to load fees', 'err'=>$rc['err']));
            }
            if( isset($rc['fees']) ) {
                foreach($rc['fees'] as $row) {
                    $festival['statistics'][$row['participation'] . '_paid_fees'] = array(
                        'label'=>'Paid ' . $row['participation'] . ' Fees', 
                        'value'=>'$' . number_format($row['total_fees'], 2),
                        );
                }
            }

            //
            // Get the amount of pending registration fees
            //
            $strsql = "SELECT registrations.participation, "
                . "SUM(items.total_amount) AS total_fees "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                    . "registrations.invoice_id = items.invoice_id "
                    . "AND registrations.id = items.object_id "
                    . "AND items.object = 'ciniki.musicfestivals.registration' "
                    . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                    . "items.invoice_id = invoices.id "
                    . "AND invoices.invoice_type = 10 "
                    . "AND invoices.status > 30 "
                    . "AND invoices.status < 50 "
                    . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY participation "
                . "ORDER BY participation "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'fees', 'fname'=>'participation', 
                    'fields'=>array('participation', 'total_fees'),
                    'maps'=>array('participation'=>$maps['registration']['participation']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1081', 'msg'=>'Unable to load fees', 'err'=>$rc['err']));
            }
            if( isset($rc['fees']) ) {
                foreach($rc['fees'] as $row) {
                    $festival['statistics'][$row['participation'] . '_pending_fees'] = array(
                        'label'=>'Pending ' . $row['participation'] . ' Fees', 
                        'value'=>'$' . number_format($row['total_fees'], 2),
                        );
                }
            }

            //
            // Get the placements
            //
            if( isset($festival['comments-placement-options']) && $festival['comments-placement-options'] != '' ) {
                $placements = explode(',', $festival['comments-placement-options']);
                foreach($placements as $pid => $placement) {
                    $placement = trim($placement);
                    $festival['stats_placements'][$placement] = array(
                        'label' => $placement,
                        'value' => 0,
                        );
                }

                //
                // Get the list of registrations
                //
                $strsql = "SELECT registrations.id, "
                    . "registrations.participation, "
                    . "registrations.timeslot_id, "
                    . "registrations.placement, "
                    . "registrations.finals_timeslot_id, "
                    . "registrations.finals_placement "
                    . "FROM ciniki_musicfestival_registrations AS registrations "
                    . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'registrations', 'fname'=>'id', 
                        'fields'=>array('id', 'participation', 'timeslot_id', 'placement', 'finals_timeslot_id', 'finals_placement'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.771', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
                }
                $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

                foreach($registrations as $reg) {
                    if( $reg['finals_placement'] != '' ) {
                        if( !isset($festival['stats_placements'][$reg['finals_placement']]) ) {
                            $festival['stats_placements'][$reg['finals_placement']] = array(
                                'label' => $reg['finals_placement'],
                                'value' => 0,
                                );
                        }
                        $festival['stats_placements'][$reg['finals_placement']]['value'] += 1;
                    } elseif( $reg['placement'] != '' ) {
                        if( !isset($festival['stats_placements'][$reg['placement']]) ) {
                            $festival['stats_placements'][$reg['placement']] = array(
                                'label' => $reg['placement'],
                                'value' => 0,
                                );
                        }
                        $festival['stats_placements'][$reg['placement']]['value'] += 1;
                    }
                }
            }
            //
            // Get the syllabus sections, number of registrations and fees for each section
            //
            $strsql = "SELECT sections.id, "
                . "sections.name, "
                . "IFNULL(registrations.participation, 0) AS _p, "
                . "COUNT(registrations.id) AS num_reg, "
                . "IFNULL(SUM(items.total_amount), 0) AS fees "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                    . "registrations.invoice_id = items.invoice_id "
                    . "AND registrations.id = items.object_id "
                    . "AND items.object = 'ciniki.musicfestivals.registration' "
                    . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                    . "items.invoice_id = invoices.id "
                    . "AND invoices.invoice_type = 10 "
                    . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") " 
                . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY sections.id, _p "
                . "ORDER BY sections.name, _p "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
                array('container'=>'registrations', 'fname'=>'_p', 'fields'=>array('participation'=>'_p', 'num_reg', 'fees')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1178', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
            }
            $festival['stats_sections'] = isset($rc['sections']) ? $rc['sections'] : array();
            foreach($festival['stats_sections'] as $sid => $section) {
                foreach(['live', 'virtual', 'plus', 'total'] as $t) {
                    $festival['stats_sections'][$sid]["{$t}_reg"] = 0;
                    $festival['stats_sections'][$sid]["{$t}_fees"] = 0;
                }
                if( isset($section['registrations']) ) {
                    foreach($section['registrations'] as $regtype) {
                        if( $regtype['participation'] == 0 ) {
                            $festival['stats_sections'][$sid]['live_reg'] += $regtype['num_reg'];
                            $festival['stats_sections'][$sid]['live_fees'] += $regtype['fees'];
                        } elseif( $regtype['participation'] == 1 ) {
                            $festival['stats_sections'][$sid]['virtual_reg'] += $regtype['num_reg'];
                            $festival['stats_sections'][$sid]['virtual_fees'] += $regtype['fees'];
                        } elseif( $regtype['participation'] == 2 ) {
                            $festival['stats_sections'][$sid]['plus_reg'] += $regtype['num_reg'];
                            $festival['stats_sections'][$sid]['plus_fees'] += $regtype['fees'];
                        }
                        $festival['stats_sections'][$sid]['total_reg'] += $regtype['num_reg'];
                        $festival['stats_sections'][$sid]['total_fees'] += $regtype['fees'];
                    }
                }
                foreach(['live', 'virtual', 'plus', 'total'] as $t) {
                    if( $festival['stats_sections'][$sid]["{$t}_reg"] == 0 ) {
                        $festival['stats_sections'][$sid]["{$t}_reg"] = '';
                    }
                    if( $festival['stats_sections'][$sid]["{$t}_fees"] > 0 ) {
                        $festival['stats_sections'][$sid]["{$t}_fees"] = '$' . number_format($festival['stats_sections'][$sid]["{$t}_fees"], 2);
                    } else {
                        $festival['stats_sections'][$sid]["{$t}_fees"] = '';
                    }
                }
            }
        }
        if( isset($args['statistics']) && $args['statistics'] == 'cities' ) {
            //
            // Get the number of city, province stats
            //
            $strsql = "SELECT CONCAT_WS(', ', city, province) AS cityprov, COUNT(DISTINCT competitors.id) AS num_competitors, "
                . "COUNT(DISTINCT registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                    . "AND ( "
                        . "competitors.id = registrations.competitor1_id "
                        . "OR competitors.id = registrations.competitor2_id "
                        . "OR competitors.id = registrations.competitor3_id "
                        . "OR competitors.id = registrations.competitor4_id "
                        . "OR competitors.id = registrations.competitor5_id "
                        . ") "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY cityprov "
                . "ORDER BY cityprov "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'cityprovincestats', 'fname'=>'cityprov', 
                    'fields'=>array('label'=>'cityprov', 'num_competitors', 'num_registrations'),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.769', 'msg'=>'Unable to load cityprovincestats', 'err'=>$rc['err']));
            }
            $festival['stats_cities'] = isset($rc['cityprovincestats']) ? $rc['cityprovincestats'] : array();
        }
        //
        // Get the member stats
        //
        if( isset($args['statistics']) && $args['statistics'] == 'members' ) {
            if( isset($festival['comments-placement-options']) && $festival['comments-placement-options'] != '' ) {
                $placements = explode(',', $festival['comments-placement-options']);
                foreach($placements as $pid => $placement) {
                    $placements[$pid] = trim($placement);
                }
            }

            //
            // Get the list of members
            //
            $strsql = "SELECT registrations.member_id, "
                . "members.name, "
                . "registrations.id AS reg_id, "
                . "registrations.participation, "
                . "registrations.timeslot_id, "
                . "registrations.placement, "
                . "registrations.finals_timeslot_id, "
                . "registrations.finals_placement "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                    . "registrations.member_id = members.id "
                    . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY members.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'members', 'fname'=>'member_id', 
                    'fields'=>array('id'=>'member_id', 'name'),
                    ),
                array('container'=>'registrations', 'fname'=>'reg_id', 
                    'fields'=>array('id'=>'reg_id', 'participation', 'timeslot_id', 'placement', 'finals_timeslot_id', 'finals_placement'),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.779', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
            }
            $members = isset($rc['members']) ? $rc['members'] : array();

            foreach($members as $mid => $member) {
                $members[$mid]['num_registrations'] = 0;
                $members[$mid]['num_live'] = 0;
                $members[$mid]['num_virtual'] = 0;
                foreach($placements as $p) {
                    $members[$mid][$p] = 0;
                }
               
                foreach($member['registrations'] as $reg) {
                    $members[$mid]['num_registrations']++;
                    if( $reg['participation'] == 1 ) {
                        $members[$mid]['num_virtual']++;
                    } else {
                        $members[$mid]['num_live']++;
                    }
                    if( $reg['finals_placement'] != '' ) {
                        $members[$mid][$reg['finals_placement']] += 1;
                    } elseif( $reg['placement'] != '' ) {
                        $members[$mid][$reg['placement']] += 1;
                    }
                }

                unset($members[$mid]['registrations']);
            }
            $festival['stats_members_headerValues'] = array('Name', 'Registrations', 'Live', 'Virtual');
            $festival['stats_members_dataMaps'] = array('name', 'num_registrations', 'num_live', 'num_virtual');
            foreach($placements as $p) {
                $festival['stats_members_headerValues'][] = $p;
                $festival['stats_members_dataMaps'][] = $p;
            }
            $festival['stats_members'] = $members;
        }

        //
        // Load Songs from the screen and more chart
        //
        if( isset($args['ssam']) && $args['ssam'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'ssamLoad');
            $rc = ciniki_musicfestivals_ssamLoad($ciniki, $args['tnid'], $args['festival_id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $festival['ssam'] = $rc['ssam'];
        }

        //
        // Load the list of provincial festivals
        //
        if( isset($args['provincials']) && $args['provincials'] == 'festivals' ) {
            $strsql = "SELECT festivals.id, "
                . "CONCAT_WS(' - ', festivals.name, tenants.name) AS name "
                . "FROM ciniki_tenant_modules AS modules "
                . "INNER JOIN ciniki_tenants AS tenants ON ("
                    . "modules.tnid = tenants.id "
                    . ") "
                . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
                    . "modules.tnid = festivals.tnid "
                    . ") "
                . "WHERE modules.package = 'ciniki' "
                . "AND modules.module = 'musicfestivals' "
                . "AND (modules.flags&0x010000) = 0x010000 "  // Provincials Tenant
                . "ORDER BY tenants.name, festivals.start_date DESC "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'festivals', 'fname'=>'id', 
                    'fields'=>array('id', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.904', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
            }
            $festival['provincial_festivals'] = isset($rc['festivals']) ? $rc['festivals'] : array();
        }
    }

    return array('stat'=>'ok', 'festival'=>$festival, 'nplists'=>$nplists);
}
?>
