<?php
//
// Description
// ===========
// This method will return the volunteer resource file in it's binary form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the requested file belongs to.
// file_id:         The ID of the file to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_musicfestivals_volunteerResourceDownload($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'resource_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Resource'), 
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerResourceDownload'); 
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
    // Load the resource
    //
    $strsql = "SELECT resources.id, "
        . "resources.uuid, "
        . "resources.name, "
        . "resources.resourcetype, "
        . "resources.category, "
        . "resources.synopsis, "
        . "resources.url, "
        . "resources.org_filename, "
        . "resources.extension "
        . "FROM ciniki_musicfestival_volunteer_resources AS resources "
        . "WHERE resources.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND resources.id = '" . ciniki_core_dbQuote($ciniki, $args['resource_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'resource');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1593', 'msg'=>'Unable to load resource', 'err'=>$rc['err']));
    }
    if( !isset($rc['resource']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1594', 'msg'=>'Unable to find requested resource'));
    }
    $resource = $rc['resource'];
   
    if( $resource['resourcetype'] != 50 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1595', 'msg'=>'Resource is not a file'));
    }

    //
    // Build the storage filename
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/volunteerfiles/' . $resource['uuid'][0] . '/' . $resource['uuid'];
    if( file_exists($storage_filename) ) {
        $binary_content = file_get_contents($storage_filename);
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1618', 'msg'=>'Unable to find file'));
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    if( $resource['extension'] == 'pdf' ) {
        header('Content-Type: application/pdf');
//    } else {
//        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1596', 'msg'=>'Unsupported file type'));
    }

    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $resource['org_filename'] . '"');
    header('Content-Length: ' . strlen($binary_content));
    header('Cache-Control: max-age=0');

    print $binary_content;
    
    return array('stat'=>'exit');
}
?>
