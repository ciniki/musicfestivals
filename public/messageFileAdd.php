<?php
//
// Description
// -----------
// Add a file to a message.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_messageFileAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'filename'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Filename'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageFileAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($_FILES['uploadfile']['name']) ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.574', 'msg'=>'No file specified'));
    }

    if( (!isset($args['filename']) || $args['filename'] == '') ) {
        $args['filename'] = $_FILES['uploadfile']['name'];
    }

    //
    // Get the message
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.633', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
    }
    if( !isset($rc['message']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.634', 'msg'=>'Unable to find requested message'));
    }
    $message = $rc['message'];

    if( $message['status'] != 10 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.645', 'msg'=>'Message must be in Draft Mode to add files.'));
    }

    if( $message['files'] != '' ) {
        $files = unserialize($message['files']);
    } else {
        $files = array();
    }
    foreach($files as $file) {
        if( $file['filename'] == $args['filename'] ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.646', 'msg'=>'You already have an attachment with that name.'));
        }
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
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.569', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

    //
    // Make sure a file was submitted
    //
    if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.570', 'msg'=>'No file specified.'));
    }

    $args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $_FILES['uploadfile']['name']);

    //
    // Check the extension is a PDF, currently only accept PDF files
    //
//    if( $args['extension'] != 'pdf' && $args['extension'] != 'PDF' ) {
//        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.571', 'msg'=>'The file must be a PDF file.'));
//    }
   
    //
    // Move the file to ciniki-storage
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
        . $message['uuid'][0] . '/' . $message['uuid'] . '_' . $args['filename'];
    if( !is_dir(dirname($storage_filename)) ) {
        if( !mkdir(dirname($storage_filename), 0700, true) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.572', 'msg'=>'Unable to add file'));
        }
    }

    if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.573', 'msg'=>'Unable to add file'));
    }

    //
    // Add file to message
    //
    $files[] = array(
        'filename' => $args['filename'],
        );
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.message', $args['message_id'], array(
        'files' => serialize($files),
        ), 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    
    return array('stat'=>'ok', 'files'=>$files);
}
?>
