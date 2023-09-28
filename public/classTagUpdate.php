<?php
//
// Description
// -----------
// This method will update tag name from old to new
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_classTagUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'old_tag_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Old Name'),
        'new_tag_name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'New Name'),
        'old_tag_sort_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Old Sort Name'),
        'new_tag_sort_name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'New Sort Name'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.classTagUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the tag name
    //
    if( isset($args['old_tag_name']) && isset($args['new_tag_name']) && $args['old_tag_name'] != $args['new_tag_name'] ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['new_tag_name']);
        $strsql = "SELECT tags.id, tags.tag_name, tags.tag_sort_name "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "INNER JOIN ciniki_musicfestival_class_tags AS tags ON ("
                . "classes.id = tags.class_id "
                . "AND tags.tag_type = 20 "
                . "AND tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['old_tag_name']) . "' "
                . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tags.tag_sort_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.579', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        $rows = isset($rc['rows']) ? $rc['rows'] : array();
        foreach($rows as $row) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.classtag', $row['id'], array(
                'tag_name' => $args['new_tag_name'],
                'permalink' => $args['permalink'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.580', 'msg'=>'Unable to update the classtag', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Update the tag sort name
    //
    if( isset($args['old_tag_sort_name']) && isset($args['new_tag_sort_name']) && $args['old_tag_sort_name'] != $args['new_tag_sort_name'] ) {
        $strsql = "SELECT tags.id, tags.tag_name, tags.tag_sort_name "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "INNER JOIN ciniki_musicfestival_class_tags AS tags ON ("
                . "classes.id = tags.class_id "
                . "AND tags.tag_type = 20 "
                . "AND tags.tag_sort_name = '" . ciniki_core_dbQuote($ciniki, $args['old_tag_sort_name']) . "' "
                . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tags.tag_sort_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.579', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        $rows = isset($rc['rows']) ? $rc['rows'] : array();
        foreach($rows as $row) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.classtag', $row['id'], array(
                'tag_sort_name' => $args['new_tag_sort_name'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.580', 'msg'=>'Unable to update the classtag', 'err'=>$rc['err']));
            }
        }
    }


    return array('stat'=>'ok');
}
?>
