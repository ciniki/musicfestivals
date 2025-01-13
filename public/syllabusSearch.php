<?php
//
// Description
// -----------
// This method searchs the syllabus for classes that match the search string.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Class for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_syllabusSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.classSearch');
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

    //
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classKeywordsMake');
    $rc = ciniki_musicfestivals_classKeywordsMake($ciniki, $args['tnid'], [
        'keywords' => $args['start_needle'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $args['start_needle']);
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    //
    // Get the list of classes
    //
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
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee, "
        . "classes.min_competitors, "
        . "classes.max_competitors, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "classes.synopsis, "
        . "classes.schedule_seconds "
//        . "COUNT(registrations.id) AS num_registrations "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "INNER JOIN ciniki_musicfestival_categories AS categories USE INDEX (festival_id_2) ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
//        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations USE INDEX (festival_id_2) ON ("
//            . "classes.id = registrations.class_id "
//            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//            . ") "
        . "WHERE classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND classes.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
/*        . "AND ("
            . "classes.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.keywords LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") " */
//        . "GROUP BY classes.id "
        . "ORDER BY sections.sequence, sections.name, "
            . "categories.sequence, categories.name, "
            . "classes.sequence, classes.name "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'section_name', 'category_name', 
                'code', 'name', 'permalink', 'sequence', 'flags', 
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                'min_competitors', 'max_competitors', 'min_titles', 'max_titles', 
                'synopsis', 'schedule_seconds',
//                'num_registrations',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();
    $class_ids = array();
    foreach($classes as $cid => $class) {
        $classes[$cid]['earlybird_fee'] = '$' . number_format($class['earlybird_fee'], 2);
        $classes[$cid]['fee'] = '$' . number_format($class['fee'], 2);
        $classes[$cid]['virtual_fee'] = '$' . number_format($class['virtual_fee'], 2);
        $classes[$cid]['earlybird_plus_fee'] = '$' . number_format($class['earlybird_plus_fee'], 2);
        $classes[$cid]['plus_fee'] = '$' . number_format($class['plus_fee'], 2);
        if( $class['min_competitors'] == $class['max_competitors'] ) {
            $classes[$cid]['num_competitors'] = $class['min_competitors'];
        } else {
            $classes[$cid]['num_competitors'] = $class['min_competitors'] . ' - ' . $class['max_competitors'];
        }
        $classes[$cid]['competitor_type'] = 'I or G';
        if( ($class['flags']&0x4000) == 0x4000 ) {
            $classes[$cid]['competitor_type'] = 'person';
            if( $class['min_competitors'] > 1 || $class['max_competitors'] > 1 ) {
                $classes[$cid]['competitor_type'] = 'people';
            }
        } elseif( ($class['flags']&0x8000) == 0x8000 ) {
            $classes[$cid]['competitor_type'] = 'group';
        } 
        if( $class['min_titles'] == $class['max_titles'] ) {
            $classes[$cid]['num_titles'] = $class['min_titles'];
        } else {
            $classes[$cid]['num_titles'] = $class['min_titles'] . ' - ' . $class['max_titles'];
        }
        $classes[$cid]['backtrack'] = '';
        if( ($class['flags']&0x01000000) == 0x01000000 ) {
            $classes[$cid]['backtrack'] = 'Required';
        } elseif( ($class['flags']&0x01000000) == 0x01000000 ) {
            $classes[$cid]['backtrack'] = 'Optional';
        }
        $classes[$cid]['instrument'] = '';
        if( ($class['flags']&0x04) == 0x04 ) {
            $classes[$cid]['instrument'] = 'Yes';
        }
        $classes[$cid]['accompanist'] = '';
        if( ($class['flags']&0x1000) == 0x1000 ) {
            $classes[$cid]['accompanist'] = 'Required';
        } elseif( ($class['flags']&0x2000) == 0x2000 ) {
            $classes[$cid]['accompanist'] = 'Optional';
        }
        $classes[$cid]['schedule_time'] = '';
        if( $class['schedule_seconds'] > 0 ) {
            $classes[$cid]['schedule_time'] = floor($class['schedule_seconds']/60) . ' min';
            if( ($class['schedule_seconds']%60) > 0 ) {
                $classes[$cid]['schedule_time'] .= ' ' . ($class['schedule_seconds']%60) . ' sec';
            }
        }
        $class_ids[] = $class['id'];
    }

    return array('stat'=>'ok', 'classes'=>$classes, 'nplist'=>$class_ids);
}
?>
