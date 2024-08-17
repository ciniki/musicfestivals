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
function ciniki_musicfestivals_classUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'category_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Category'),
        'code'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Code'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'earlybird_fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Earlybird Fee'),
        'fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Fee'),
        'virtual_fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Virtual Fee'),
        'earlybird_plus_fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Earlybird Plus Fee'),
        'plus_fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Plus Fee'),
        'min_competitors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Minimum Competitors'),
        'max_competitors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Maximum Competitors'),
        'min_titles'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Minimum Titles'),
        'max_titles'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Maximum Titles'),
        'provincials_code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Provincials Class Code'),
        'levels'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Level Tags'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'schedule_seconds'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Seconds'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.classUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the existing class
    //
    $strsql = "SELECT classes.id, "
        . "classes.festival_id, "
        . "classes.code, "
        . "classes.category_id, "
        . "classes.name, "
        . "classes.sequence "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.382', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.383', 'msg'=>'Unable to find requested class'));
    }
    $class = $rc['class'];
    
    //
    // Check if the code is unique
    //
    if( isset($args['code']) && $args['code'] != $class['code'] ) {
        $strsql = "SELECT id "
            . "FROM ciniki_musicfestival_classes "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $class['festival_id']) . "' "
            . "AND code = '" . ciniki_core_dbQuote($ciniki, $args['code']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.810', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.41', 'msg'=>'You already have a class with that code, please choose another.'));
        }
    }

    //
    // Build new permalink
    //
    if( isset($args['name']) || isset($args['code']) ) {
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, 
            (isset($args['code']) ? $args['code'] : $class['code']) 
            . '-' . (isset($args['name']) ? $args['name'] : $class['name']) 
            );
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_musicfestival_classes "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
            . "AND category_id = '" . ciniki_core_dbQuote($ciniki, (isset($args['category_id']) ? $args['category_id'] : $class['category_id'])) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.47', 'msg'=>'You already have an class with this name, please choose another.'));
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
    // Update the Class in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.class', $args['class_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Update the tags
    //
    if( isset($args['levels']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classTagsUpdate');
        $rc = ciniki_musicfestivals_classTagsUpdate($ciniki, $args['tnid'], $args['class_id'], 20, $args['levels']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Check if sequences should be updated
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.class', 
            'category_id', $class['category_id'], $args['sequence'], $class['sequence']);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.class', 'object_id'=>$args['class_id']));

    return array('stat'=>'ok');
}
?>
