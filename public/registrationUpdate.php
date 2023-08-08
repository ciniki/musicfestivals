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
function ciniki_musicfestivals_registrationUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'billing_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing'),
        'rtype'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'invoice_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
        'display_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'competitor1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 1'),
        'competitor2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 2'),
        'competitor3_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 3'),
        'competitor4_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 4'),
        'competitor5_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor 5'),
        'class_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class'),
        'title1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'perf_time1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Performance Time'),
        'title2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2n Title'),
        'perf_time2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2n Performance Time'),
        'title3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'3rd Title'),
        'perf_time3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'3rd Performance Time'),
        'fee'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Fee'),
        'payment_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment Type'),
        'participation'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Virtual'),
        'video_url1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'video_url3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Link'),
        'music_orgfilename1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Music PDF'),
        'music_orgfilename2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Music PDF'),
        'music_orgfilename3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Music PDF'),
        'placement'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Placement'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Interal Notes'),
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Registration Tags'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    if( isset($args['display_name']) ) {
        $args['public_name'] = $args['display_name'];
    }

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the details of the registration
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
        . "registrations.festival_id, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3 "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.201', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.202', 'msg'=>'Unable to find requested registration'));
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
    $tenant_storage_dir = $rc['storage_dir'];

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
    // Check if new files added
    //
    if( isset($_FILES) ) {
        foreach($_FILES as $field_name => $file) {
            $file_num = 0;
            if( $field_name == 'music_orgfilename1' ) {
                $file_num = 1;
            } elseif( $field_name == 'music_orgfilename2' ) {
                $file_num = 2;
            } elseif( $field_name == 'music_orgfilename3' ) {
                $file_num = 3;
            } else {
                error_log('UNKNOWN FILE: ' . $field_name);
                continue;
            }
            if( isset($file['error']) && $file['error'] == UPLOAD_ERR_INI_SIZE ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.442', 'msg'=>'Upload failed, file too large.'));
            }
            if( !isset($file['tmp_name']) || $file['tmp_name'] == '' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.53', 'msg'=>'No file specified.'));
            }
            $args["music_orgfilename{$file_num}"] = $file['name'];
            $args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args["music_orgfilename{$file_num}"]);

            //
            // Check the extension is a PDF, currently only accept PDF files
            //
            if( $args['extension'] != 'pdf' && $args['extension'] != 'PDF' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.54', 'msg'=>'The file must be a PDF file.'));
            }

            //
            // Move the file to ciniki-storage
            //
            $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/files/' 
                . $registration['uuid'][0] . '/' . $registration['uuid'] . '_music' . $file_num;
            if( !is_dir(dirname($storage_filename)) ) {
                if( !mkdir(dirname($storage_filename), 0700, true) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.183', 'msg'=>'Unable to add file'));
                }
            }
            if( !rename($file['tmp_name'], $storage_filename) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.56', 'msg'=>'Unable to add file'));
            }
        }
    }

    //
    // Update the Registration in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $args['registration_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Update the display_name for the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
    $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $args['tnid'], $args['registration_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Load the updated registration to see if updates needed to invoice
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
        . "registrations.festival_id, "
        . "registrations.status, "
        . "registrations.invoice_id, "
        . "registrations.display_name, "
        . "registrations.title1, "
        . "registrations.fee, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.391', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.392', 'msg'=>'Unable to find requested registration'));
    }
    $registration = $rc['registration'];
   
    //
    // If the invoice is specified, check if anything needs changing on the invoice
    //
    if( $registration['invoice_id'] > 0 ) {
        //
        // Load the invoice item
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
        $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $args['tnid'], $registration['invoice_id'], 'ciniki.musicfestivals.registration', $args['registration_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.410', 'msg'=>'Unable to get invoice item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            $item = $rc['item'];

            //
            // Check if anything changed in the cart
            //
            $update_item_args = array();
            $notes = $registration['display_name'] . ($registration['title1'] != '' ? ' - ' . $registration['title1'] : '');

            if( $item['code'] != $registration['class_code'] ) {
                $update_item_args['code'] = $registration['class_code'];
            }
            if( $item['description'] != $registration['class_name'] ) {
                $update_item_args['description'] = $registration['class_name'];
            }
            if( $item['unit_amount'] != $registration['fee'] ) {
                $update_item_args['unit_amount'] = $registration['fee'];
            }
            if( $item['notes'] != $notes ) {
                $update_item_args['notes'] = $notes;
            }
            if( count($update_item_args) > 0 ) {
                $update_item_args['item_id'] = $item['id'];
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
                $rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $args['tnid'], $update_item_args);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.313', 'msg'=>'Unable to update invoice', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Update the tags
    //
    if( isset($args['tags']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'tag', $args['tnid'],
            'ciniki_musicfestival_registration_tags', 'ciniki_musicfestivals_history',
            'registration_id', $args['registration_id'], 10, $args['tags']);
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
