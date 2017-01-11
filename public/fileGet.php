<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to file belongs to.
// file_id:             The ID of the file to get.
//
// Returns
// -------
//
function ciniki_musicfestivals_fileGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.fileGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_musicfestival_files.id, "
        . "ciniki_musicfestival_files.name, "
        . "ciniki_musicfestival_files.permalink, "
        . "ciniki_musicfestival_files.webflags, "
        . "IF(ciniki_musicfestival_files.webflags&0x01=1,'Hidden','Visible') AS webvisible, "
        . "IFNULL(DATE_FORMAT(publish_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS publish_date, "
        . "ciniki_musicfestival_files.description "
        . "FROM ciniki_musicfestival_files "
        . "WHERE ciniki_musicfestival_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_musicfestival_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "";

    //
    // Check if we need to include thumbnail images
    //
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.60', 'msg'=>'Unable to find file'));
    }
    
    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
