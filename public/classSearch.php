<?php
//
// Description
// -----------
// This method searchs for a Classs for a tenant.
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
function ciniki_musicfestivals_classSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'exclude_accolade_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Exclude Accolade'),
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
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classKeywordsMake');
    $rc = ciniki_musicfestivals_classKeywordsMake($ciniki, $args['tnid'], [
        'keywords' => $args['start_needle'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $request['args']['search_string']);
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    //
    // Get the list of classes
    //
    if( isset($args['exclude_accolade_id']) && $args['exclude_accolade_id'] > 0 ) {
        $strsql = "SELECT classes.id, "
            . "classes.festival_id, "
            . "classes.category_id, "
            . "classes.code, "
            . "classes.name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name, "
            . "classes.permalink, "
            . "classes.sequence, "
            . "classes.flags, "
            . "classes.fee, "
            . "ac.id AS ac_id "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_accolade_classes AS ac ON ("
                . "classes.id = ac.class_id "
                . "AND ac.accolade_id = '" . ciniki_core_dbQuote($ciniki, $args['exclude_accolade_id']) . "' "
                . "AND ac.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
//            . "AND ("
//                . "classes.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//                . "classes.keywords LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//                . "OR classes.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//                . "OR categories.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//                . "OR categories.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//                . "OR sections.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//                . "OR sections.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//            . ") "
            . "AND ISNULL(ac.id) "
            . "";

    } else {
        $strsql = "SELECT classes.id, "
            . "classes.festival_id, "
            . "classes.category_id, "
            . "classes.code, "
            . "classes.name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name, "
            . "classes.permalink, "
            . "classes.sequence, "
            . "classes.flags, "
            . "classes.fee "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND ("
                . "classes.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR classes.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR classes.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
            . "";
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'category_name', 'section_name', 'permalink', 'sequence', 'flags', 'fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $classes = $rc['classes'];
        $class_ids = array();
        foreach($classes as $iid => $class) {
            $class_ids[] = $class['id'];
        }
    } else {
        $classes = array();
        $class_ids = array();
    }

    return array('stat'=>'ok', 'classes'=>$classes, 'nplist'=>$class_ids);
}
?>
