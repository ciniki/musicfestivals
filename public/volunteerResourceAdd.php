<?php
//
// Description
// -----------
// This method will add a new volunteer resource for the festival.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Resource to.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerResourceAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'resourcetype'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerResourceAdd');
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
    // Check if file uploaded
    //
    if( $args['resourcetype'] == 50 ) {
        if( isset($_FILES['uploadfile']['tmp_name']) && $_FILES['uploadfile']['tmp_name'] != '' ) {
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
                return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1585', 'msg'=>'You already have a resource with that file, please choose another file.'));
            }

            //
            // Get a UUID for use in file
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
            $rc = ciniki_core_dbUUID($ciniki, 'ciniki.musicfestivals');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1586', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
            }
            $args['uuid'] = $rc['uuid'];

            $storage_dirname = $tenant_storage_dir . '/ciniki.musicfestivals/volunteerfiles/' . $args['uuid'][0];
            if( !file_exists($storage_dirname) ) {
                if( !mkdir($storage_dirname, 0700, true) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1587', 'msg'=>'Unable to save file'));
                }
            }
            $storage_filename = $storage_dirname . '/' . $args['uuid'];
            if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1588', 'msg'=>'Unable to save file'));
            }
            $args['url'] = '';
        }
        else {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1589', 'msg'=>'No file uploaded'));
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
    // Add the resource to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerresource', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $resource_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.volunteerresource', 'object_id'=>$resource_id));

    return array('stat'=>'ok', 'id'=>$resource_id);
}
?>
