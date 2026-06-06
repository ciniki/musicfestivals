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
function ciniki_musicfestivals_volunteerResourceUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'resource_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Resource'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'resourcetype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerResourceUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the existing resource
    //
    $strsql = "SELECT resources.id, "
        . "resources.resourcetype, "
        . "resources.url, "
        . "resources.org_filename "
        . "FROM ciniki_musicfestival_volunteer_resources AS resources "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['resource_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'resource');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1599', 'msg'=>'Unable to load resource', 'err'=>$rc['err']));
    }
    if( !isset($rc['resource']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1600', 'msg'=>'Unable to find requested resource'));
    }
    $resource = $rc['resource'];

    //
    // Check if file uploaded
    //
    if( isset($_FILES['uploadfile']['tmp_name']) && $_FILES['uploadfile']['tmp_name'] != '' ) {
        if( $resource['org_filename'] != '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1601', 'msg'=>'You alrady have a file, you need to remove this resource and add a new one.', 'err'=>$rc['err']));
        }
        $args['org_filename'] = $_FILES['uploadfile']['name'];
        $args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['org_filename']);

        //
        // Check filename does not already exist
        //
        $strsql = "SELECT id, name, org_filename "
            . "FROM ciniki_musicfestival_volunteer_resources "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND resourcetype = 50 "
            . "AND org_filename = '" . ciniki_core_dbQuote($ciniki, $args['org_filename']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1602', 'msg'=>'You already have a resource with that file, please choose another file.'));
        }

        //
        // Get a UUID for use in file
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.musicfestivals');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1603', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $args['uuid'] = $rc['uuid'];

        $storage_dirname = $tenant_storage_dir . '/ciniki.musicfestivals/volunteerfiles/' . $args['uuid'][0];
        if( !file_exists($storage_dirname) ) {
            if( !mkdir($storage_dirname, 0700, true) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1604', 'msg'=>'Unable to save file'));
            }
        }
        $storage_filename = $storage_dirname . '/' . $args['uuid'];
        if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1605', 'msg'=>'Unable to save file'));
        }
        $args['url'] = '';
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
    // Update the Resource in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerresource', $args['resource_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.volunteerresource', 'object_id'=>$args['resource_id']));

    return array('stat'=>'ok');
}
?>
