<?php
//
// Description
// -----------
// This method will return the list of Mails for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Mail for.
//
// Returns
// -------
//
function ciniki_musicfestivals_messagesDraftsDelete($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'y', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageDraftsDelete');
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of messages
    //
    $strsql = "SELECT messages.id, "
        . "messages.uuid, "
        . "messages.status, "
        . "messages.subject, "
        . "messages.files, "
        . "refs.id AS ref_id, "
        . "refs.uuid AS ref_uuid "
        . "FROM ciniki_musicfestival_messages AS messages "
        . "LEFT JOIN ciniki_musicfestival_messagerefs AS refs ON ("
            . "messages.id = refs.message_id "
            . "AND refs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND messages.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND messages.status = 10 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'messages', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'status', 'subject', 'files')),
        array('container'=>'refs', 'fname'=>'ref_id', 'fields'=>array('id'=>'ref_id', 'uuid'=>'ref_uuid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $messages = isset($rc['messages']) ? $rc['messages'] : array();

    //
    // Delete the draft messages
    //
    foreach($messages as $message) {
        if( $message['status'] != 10 ) {
            continue;
        }
        error_log("Remove message: " . $message['id'] . " - " . $message['subject']);
        if( isset($message['refs']) ) {
            foreach($message['refs'] as $ref) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageref',
                    $ref['id'], $ref['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                    return $rc;
                }
            }
        }

        if( $message['files'] != '' ) {
            $files = unserialize($message['files']);
            foreach($files as $fid => $file) {
                //
                // Remove file
                //
                $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
                    . $message['uuid'][0] . '/' . $message['uuid'] . '_' . $file['filename'];
                if( is_file($storage_filename) ) {
                    unlink($storage_filename);
                }
            }
        } 
        
        //
        // Remove the message
        //
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.message',
            $message['id'], $message['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
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
