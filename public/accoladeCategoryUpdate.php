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
function ciniki_musicfestivals_accoladeCategoryUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Accolade Category'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'awarded_email_subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Awarded Registration Email Subject'),
        'awarded_email_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Awarded Registration Email Template'),
        'awarded_pdf_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Awarded Registration PDF Template'),
        'teacher_email_subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Awarded Teacher Email Subject'),
        'teacher_email_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Awarded Teacher Email Template'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeCategoryUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the existing category
    //
    $strsql = "SELECT categories.id, "
        . "categories.name, "
        . "categories.sequence "
        . "FROM ciniki_musicfestival_accolade_categories AS categories "
        . "WHERE categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'category');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1426', 'msg'=>'Unable to load category', 'err'=>$rc['err']));
    }
    if( !isset($rc['category']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1428', 'msg'=>'Unable to find requested category'));
    }
    $category = $rc['category'];
  
    //
    // Check permalink
    //
    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_musicfestival_accolade_categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1108', 'msg'=>'You already have an accolade category with this name, please choose another.'));
        }
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
    // Update the Accolade Category in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladecategory', $args['category_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Check if sequences should be updated
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladecategory', 
            '', null, $args['sequence'], $category['sequence']);
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.accoladeCategory', 'object_id'=>$args['category_id']));

    return array('stat'=>'ok');
}
?>
