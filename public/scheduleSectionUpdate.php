<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleSectionUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'schedulesection_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Section'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
//        'adjudicator1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Adjudicator'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'top_sponsors_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Top Sponsors Title'),
        'top_sponsor_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Top Sponsors'),
        'top_sponsors_image_ratio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Top Image Ratio'),
        'bottom_sponsors_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bottom Sponsors Title'),
        'bottom_sponsors_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bottom Content'),
        'bottom_sponsor_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Bottom Sponsors'),
        'bottom_sponsors_image_ratio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bottom Image Ratio'),
        'pdfheader_sponsor_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'PDF Header Sponsor Image'),
        'pdffooter_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Footer Title'),
        'pdffooter_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Footer Image'),
        'webheader_sponsor_ids'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Header Sponsor Image'),
        'webheader_sponsors_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Header Sponsors Title'),
        'webheader_sponsors_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Header Sponsors Content'),
        'provincials_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Title'),
        'provincials_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Content'),
        'provincials_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Image'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleSectionUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current section
    //
    $strsql = "SELECT id, "
        . "festival_id, "
        . "sequence "
        . "FROM ciniki_musicfestival_schedule_sections "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' "
        . "ORDER BY sequence, name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.681', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.682', 'msg'=>'Unable to find requested section'));
    }
    $section = $rc['section'];

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
    // Update the Schedule Section in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.schedulesection', $args['schedulesection_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Check if sequences should be updated
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.schedulesection', 
            'festival_id', $section['festival_id'], $args['sequence'], $section['sequence']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    return array('stat'=>'ok');
}
?>
