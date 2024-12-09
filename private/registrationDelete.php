<?php
//
// Description
// -----------
// This function will remove the files for a registration and then remove the registration
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_registrationDelete(&$ciniki, $tnid, $reg_id) {

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Load the registration details
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
        . "registrations.backtrack8, "
        . "registrations.artwork1, "
        . "registrations.artwork2, "
        . "registrations.artwork3, "
        . "registrations.artwork4, "
        . "registrations.artwork5, "
        . "registrations.artwork6, "
        . "registrations.artwork7, "
        . "registrations.artwork8 "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $reg_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.775', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.776', 'msg'=>'Unable to find requested registration'));
    }
    $reg = $rc['registration'];
    
    //
    // Remove the files
    //
    for($i = 1; $i <= 8; $i++) {
        if( $reg["music_orgfilename{$i}"] != '' ) {
            $filename = "{$tenant_storage_dir}/ciniki.musicfestivals/files/{$reg['uuid'][0]}/{$reg['uuid']}_music{$i}";
            if( file_exists($filename) ) {
                unlink($filename);
            }
        }
        if( $reg["backtrack{$i}"] != '' ) {
            $filename = "{$tenant_storage_dir}/ciniki.musicfestivals/files/{$reg['uuid'][0]}/{$reg['uuid']}_backtrack{$i}";
            if( file_exists($filename) ) {
                unlink($filename);
            }
        }
        if( $reg["artwork{$i}"] != '' ) {
            $filename = "{$tenant_storage_dir}/ciniki.musicfestivals/files/{$reg['uuid'][0]}/{$reg['uuid']}_artwork{$i}";
            if( file_exists($filename) ) {
                unlink($filename);
            }
        }
    }

    //
    // Remove the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.registration', $reg['id'], $reg['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.331', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
