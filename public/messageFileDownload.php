<?php
//
// Description
// -----------
// Download a file from a message.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_messageFileDownload(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'filename'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Filename'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageFileDownload');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current list of files
    //
    $strsql = "SELECT messages.id, "
        . "messages.uuid, "
        . "messages.status, "
        . "messages.files "
        . "FROM ciniki_musicfestival_messages AS messages "
        . "WHERE messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'message');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.566', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
    }
    if( !isset($rc['message']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.567', 'msg'=>'Unable to find requested message'));
    }
    $message = $rc['message'];

    if( $message['status'] != 10 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.568', 'msg'=>'Message must be in Draft Mode to add files.'));
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

    if( $message['files'] != '' ) {
        $files = unserialize($message['files']);
    } else {
        $files = array();
    }
    foreach($files as $fid => $file) {
        if( $file['filename'] == $args['filename'] ) {
            //
            // Remove file
            //
            $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
                . $message['uuid'][0] . '/' . $message['uuid'] . '_' . $file['filename'];
            $binary_content = file_get_contents($storage_filename);
            if( is_file($storage_filename) ) {
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
                header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');

                // Specify Filename
                header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
                header('Content-Length: ' . strlen($binary_content));
                header('Cache-Control: max-age=0');

                print $binary_content;

                return array('stat'=>'exit');
            }
        }
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.575', 'msg'=>'File does not exist.'));
}
?>
