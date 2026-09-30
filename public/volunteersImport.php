<?php
//
// Description
// ===========
// This method will copy volunteers from a previous festival
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
function ciniki_musicfestivals_volunteersImport($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'old_festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Previous Festival'),
        'volunteer_ids'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'list', 'name'=>'Volunteers'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteersImport');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the volunteers from the old festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    $strsql = "SELECT volunteers.id, "
        . "volunteers.customer_id, "
        . "volunteers.status, "
        . "volunteers.shortname, "
        . "volunteers.notes, "
        . "volunteers.internal_notes, "
        . "tags.id AS tag_id, "
        . "tags.tag_type, "
        . "tags.tag_name, "
        . "tags.permalink "
        . "FROM ciniki_musicfestival_volunteers AS volunteers "
        . "LEFT JOIN ciniki_musicfestival_volunteer_tags AS tags ON ("
            . "volunteers.id = tags.volunteer_id "
            . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE volunteers.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['volunteer_ids']) . ") "
        . "AND volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['old_festival_id']) . "' "
        . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY volunteers.id, tag_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'volunteers', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'status', 'shortname', 'notes', 'internal_notes')
            ),
        array('container'=>'tags', 'fname'=>'tag_id', 
            'fields'=>array('id'=>'tag_id', 'tag_type', 'tag_name', 'permalink'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1710', 'msg'=>'Unable to load volunteers', 'err'=>$rc['err']));
    }
    $volunteers = isset($rc['volunteers']) ? $rc['volunteers'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    foreach($volunteers as $volunteer) {
        //
        // Add the volunteer to the database
        //
        $volunteer['festival_id'] = $args['festival_id'];
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteer', $volunteer, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
        $volunteer_id = $rc['id'];

        if( isset($volunteer['tags']) ) {
            foreach($volunteer['tags'] as $tag) {
                $tag['volunteer_id'] = $volunteer_id;
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteertag', $tag, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                    return $rc;
                }
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>

