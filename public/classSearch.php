<?php
//
// Description
// -----------
// This method searchs for a Classs for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Class for.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.classSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of classes
    //
    $strsql = "SELECT ciniki_musicfestival_classes.id, "
        . "ciniki_musicfestival_classes.festival_id, "
        . "ciniki_musicfestival_classes.category_id, "
        . "ciniki_musicfestival_classes.code, "
        . "ciniki_musicfestival_classes.name, "
        . "ciniki_musicfestival_classes.permalink, "
        . "ciniki_musicfestival_classes.sequence, "
        . "ciniki_musicfestival_classes.flags, "
        . "ciniki_musicfestival_classes.fee "
        . "FROM ciniki_musicfestival_classes "
        . "WHERE ciniki_musicfestival_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee')),
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
