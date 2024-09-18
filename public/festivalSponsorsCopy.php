<?php
//
// Description
// ===========
// This method will copy another festivals syllabus
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
function ciniki_musicfestivals_festivalSponsorsCopy($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'old_festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Previous Festival'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalCopy');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['old_festival_id'] == 'previous' ) {
        $strsql = "SELECT id "
            . "FROM ciniki_musicfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['festival']['id']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.535', 'msg'=>'No previous festival found'));
        }
        $args['old_festival_id'] = $rc['festival']['id'];
    }

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

    //
    // Get the list of sponsors from old festival
    //
    $strsql = "SELECT sponsors.id, "
        . "sponsors.name, "
        . "sponsors.url, "
        . "sponsors.sequence, "
        . "sponsors.flags, "
        . "sponsors.image_id "
        . "FROM ciniki_musicfestival_sponsors AS sponsors "
        . "WHERE sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['old_festival_id']) . "' "
        . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sponsors', 'fname'=>'id', 'fields'=>array('id', 'name', 'url', 'sequence', 'flags', 'image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.839', 'msg'=>'Unable to load sponsors', 'err'=>$rc['err']));
    }
    $sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();

    //
    // Add sponsors to new festival
    //
    foreach($sponsors AS $sponsor) {
        $sponsor['festival_id'] = $args['festival_id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.sponsor', $sponsor, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.841', 'msg'=>'Unable to add the sponsor', 'err'=>$rc['err']));
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

