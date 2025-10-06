<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_messageUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mail'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'template_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Template'),
        'subject'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Subject'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'mtype'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Message Type'),
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'dt_scheduled'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Scheduled Date'),
        'delfile'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Remove File'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageUpdate');
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
    // Load existing message
    //
    $strsql = "SELECT messages.id, "
        . "messages.uuid, "
        . "messages.subject, "
        . "messages.content, "
        . "messages.files "
        . "FROM ciniki_musicfestival_messages AS messages "
        . "WHERE messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'message');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1074', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
    }
    if( !isset($rc['message']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1075', 'msg'=>'Unable to find requested message'));
    }
    $message = $rc['message'];

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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1076', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
        }
        if( !isset($rc['message']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1077', 'msg'=>'Unable to find requested message'));
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
    // Copy the template files
    //
    if( isset($args['template_id']) && $args['template_id'] > 0 && isset($template['files']) && $template['files'] != '' ) {
        $template_files = unserialize($template['files']);
        if( $message['files'] != '' ) {
            $message_files = unserialize($message['files']);
        } else {
            $message_files = [];
        }

        $added = 'no';
        foreach($template_files as $fid => $file) {
            foreach($message_files as $mfid => $mfile) {
                if( $mfile['filename'] == $file['filename'] ) {
                    // Skip adding duplicate file
                    continue 2;
                }
            }
            $template_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
                . $template['uuid'][0] . '/' . $template['uuid'] . '_' . $file['filename'];
            if( is_file($template_filename) ) {
                $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/messages/' 
                    . $message['uuid'][0] . '/' . $message['uuid'] . '_' . $file['filename'];
                if( !is_dir(dirname($storage_filename)) ) {
                    if( !mkdir(dirname($storage_filename), 0700, true) ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1190', 'msg'=>'Unable to add file'));
                    }
                }
                
                if( !copy($template_filename, $storage_filename) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1191', 'msg'=>'Unable to add file'));
                }

                //
                // Add file to message
                //
                $added = 'yes';
                $message_files[] = ['filename' => $file['filename']];
            }
        }
        if( $added == 'yes' ) {
            $args['files'] = serialize($message_files);
        }
    }


    //
    // Update the Mail in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.message', $args['message_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.message', 'object_id'=>$args['message_id']));

    return array('stat'=>'ok');
}
?>
