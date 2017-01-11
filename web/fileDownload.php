<?php
//
// Description
// ===========
// This function will return the file details and content so it can be downloaded on the website.
//
// Returns
// -------
//
function ciniki_musicfestivals_web_fileDownload($ciniki, $business_id, $festival_id, $file_permalink) {

    //
    // Get the business storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'storageDir');
    $rc = ciniki_businesses_hooks_storageDir($ciniki, $business_id, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $business_storage_dir = $rc['storage_dir'];

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_musicfestival_files.id, "
        . "ciniki_musicfestival_files.uuid, "
        . "ciniki_musicfestival_files.name, "
        . "ciniki_musicfestival_files.permalink, "
        . "ciniki_musicfestival_files.extension "
        . "FROM ciniki_musicfestival_files "
        . "WHERE ciniki_musicfestival_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_musicfestival_files.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND CONCAT_WS('.', ciniki_musicfestival_files.permalink, ciniki_musicfestival_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "AND (ciniki_musicfestival_files.webflags&0x01) > 0 "       // Make sure file is to be visible
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.musicfestivals.64', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    //
    // Get the storage filename
    //
    $storage_filename = $business_storage_dir . '/ciniki.musicfestivals/files/' . $rc['file']['uuid'][0] . '/' . $rc['file']['uuid'];
    if( file_exists($storage_filename) ) {
        $rc['file']['binary_content'] = file_get_contents($storage_filename);    
    }

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
