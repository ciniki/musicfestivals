<?php
//
// Description
// -----------
// This method will delete the uploaded music files and backtracks for a festival.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the festival is attached to.
// festival_id:            The ID of the festival to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_musicfestivals_festivalFilesDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalFilesDelete');
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
    // Get the list of backtracks for the festival
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
        . "registrations.music_orgfilename4, "
        . "registrations.music_orgfilename5, "
        . "registrations.music_orgfilename6, "
        . "registrations.music_orgfilename7, "
        . "registrations.music_orgfilename8, "
        . "registrations.backtrack1, "
        . "registrations.backtrack2, "
        . "registrations.backtrack3, "
        . "registrations.backtrack4, "
        . "registrations.backtrack5, "
        . "registrations.backtrack6, "
        . "registrations.backtrack7, "
        . "registrations.backtrack8 "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3', 
                'music_orgfilename4', 'music_orgfilename5', 'music_orgfilename6', 'music_orgfilename7', 
                'music_orgfilename8', 'backtrack1', 'backtrack2', 'backtrack3', 'backtrack4', 'backtrack5', 
                'backtrack6', 'backtrack7', 'backtrack8'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.773', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the festival
    //
    foreach($registrations as $reg) {
        $update_args = array(); 
        for($i = 1; $i <= 8; $i++) {
            if( $reg["music_orgfilename{$i}"] != '' ) {
                $filename = "{$tenant_storage_dir}/ciniki.musicfestivals/files/{$reg['uuid'][0]}/{$reg['uuid']}_music{$i}";
                if( file_exists($filename) ) {
                    unlink($filename);
                }
                $update_args["music_org_filename{$i}"] = '';
            }
            if( $reg["backtrack{$i}"] != '' ) {
                $filename = "{$tenant_storage_dir}/ciniki.musicfestivals/files/{$reg['uuid'][0]}/{$reg['uuid']}_backtrack{$i}";
                if( file_exists($filename) ) {
                    unlink($filename);
                }
                $update_args["backtrack{$i}"] = '';
            }
        }
        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.774', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
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

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    return array('stat'=>'ok');
}
?>
