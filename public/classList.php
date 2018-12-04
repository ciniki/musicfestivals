<?php
//
// Description
// -----------
// This method will return the list of Classs for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Class for.
//
// Returns
// -------
//
function ciniki_musicfestivals_classList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.classList');
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
        . "ciniki_musicfestival_classes.earlybird_fee, "
        . "ciniki_musicfestival_classes.fee "
        . "FROM ciniki_musicfestival_classes "
        . "WHERE ciniki_musicfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'earlybird_fee', 'fee')),
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
