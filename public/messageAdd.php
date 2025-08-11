<?php
//
// Description
// -----------
// This method will add a new mail for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Mail to.
//
// Returns
// -------
//
function ciniki_musicfestivals_messageAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'template_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Template'),
        'subject'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Subject'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'mtype'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Message Type'),
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'dt_scheduled'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Date Scheduled'),
        'dt_sent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Date Sent'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        'object_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Object IDs'),
        'object2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object 2'),
        'object2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object 2 ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($args['flags']) ) {
        $args['flags'] = 0x07;
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
    // Get template
    //
    if( isset($args['template_id']) && $args['template_id'] > 0 ) {
        $strsql = "SELECT messages.id, "
            . "messages.uuid, "
            . "messages.subject, "
            . "messages.content, "
            . "messages.files "
            . "FROM ciniki_musicfestival_messages AS messages "
            . "WHERE messages.id = '" . ciniki_core_dbQuote($ciniki, $args['template_id']) . "' "
            . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'message');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1069', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
        }
        if( !isset($rc['message']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1070', 'msg'=>'Unable to find requested message'));
        }
        $template = $rc['message'];
        $args['subject'] = $template['subject'];
        $args['content'] = $template['content'];
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
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1071', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Check if files from template need to be added
    //
    if( isset($args['template_id']) && $args['template_id'] > 0 && isset($template['files']) && $template['files'] != '' ) {
        $template_files = unserialize($template['files']);
        $files = [];

        foreach($template_files as $fid => $file) {
            $template_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
                . $template['uuid'][0] . '/' . $template['uuid'] . '_' . $file['filename'];
            if( is_file($template_filename) ) {
                $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
                    . $args['uuid'][0] . '/' . $args['uuid'] . '_' . $file['filename'];
                if( !is_dir(dirname($storage_filename)) ) {
                    if( !mkdir(dirname($storage_filename), 0700, true) ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1072', 'msg'=>'Unable to add file'));
                    }
                }
                
                if( !copy($template_filename, $storage_filename) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1073', 'msg'=>'Unable to add file'));
                }

                //
                // Add file to message
                //
                $files[] = ['filename' => $file['filename']];
            }
        }
        $args['files'] = serialize($files);
    }

    //
    // Add the mail to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.message', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $message_id = $rc['id'];

    //
    // Check if object and object id should be added to message refs
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != '' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageref', array(
            'message_id' => $message_id,
            'object' => $args['object'],
            'object_id' => $args['object_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.719', 'msg'=>'Unable to add the messageref', 'err'=>$rc['err']));
        }
    }

    //
    // Check if object and object id should be added to message refs
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_ids']) && is_array($args['object_ids']) && count($args['object_ids']) > 0 
        ) {
        foreach($args['object_ids'] as $oid) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageref', array(
                'message_id' => $message_id,
                'object' => $args['object'],
                'object_id' => $oid,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.925', 'msg'=>'Unable to add the messageref', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check if object and object id should be added to message refs
    //
    if( isset($args['object2']) && $args['object2'] != '' 
        && isset($args['object2_id']) && $args['object2_id'] != '' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageref', array(
            'message_id' => $message_id,
            'object' => $args['object2'],
            'object_id' => $args['object2_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.489', 'msg'=>'Unable to add the messageref', 'err'=>$rc['err']));
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.message', 'object_id'=>$message_id));

    return array('stat'=>'ok', 'id'=>$message_id);
}
?>
