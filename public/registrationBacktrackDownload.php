<?php
//
// Description
// ===========
// This function will download a backtrack for a registration
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationBacktrackDownload($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
        'num'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array(1,2,3,4,5,6,7,8), 'name'=>'Title Number (1,2,3)'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationMusicPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the details of the registration
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
        . "registrations.festival_id, "
        . "registrations.backtrack1, "
        . "registrations.backtrack2, "
        . "registrations.backtrack3, "
        . "registrations.backtrack4, "
        . "registrations.backtrack5, "
        . "registrations.backtrack6, "
        . "registrations.backtrack7, "
        . "registrations.backtrack8 "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.197', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.200', 'msg'=>'Unable to find requested registration'));
    }
    $registration = $rc['registration'];
    
    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $storage_filename = $rc['storage_dir'] . '/ciniki.musicfestivals/files/' 
        . $registration['uuid'][0] . '/' . $registration['uuid'] . '_backtrack' . $args['num'];
    if( !file_exists($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.204', 'msg'=>'File does not exist'));
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    // Set mime header
    $finfo = finfo_open(FILEINFO_MIME);
    if( $finfo ) { header('Content-Type: ' . finfo_file($finfo, $storage_filename)); }
    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $registration["backtrack{$args['num']}"] . '"');
    header('Content-Length: ' . filesize($storage_filename));
    header('Cache-Control: max-age=0');

    $fp = fopen($storage_filename, 'rb');
    fpassthru($fp);

    return array('stat'=>'binary');
}
?>
