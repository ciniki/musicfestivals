<?php
//
// Description
// -----------
// This method will delete an registration.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the registration is attached to.
// registration_id:            The ID of the registration to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Registration'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the registration
    //
    $strsql = "SELECT id, uuid, invoice_id "
        . "FROM ciniki_musicfestival_registrations "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.70', 'msg'=>'Registration does not exist.'));
    }
    $registration = $rc['registration'];

    //
    // Check for an invoice, and remove from the sapos module, which will hook back and remove registration
    //
    if( $registration['invoice_id'] > 0 ) {
        //
        // Get the status of the invoice
        //
        $strsql = "SELECT id, invoice_type, status "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $registration['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.83', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.185', 'msg'=>'Unable to find requested invoice'));
        }
        $invoice = $rc['invoice'];

        //
        // Get the invoice item
        //
        $strsql = "SELECT id, description, object, object_id "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $registration['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND object = 'ciniki.musicfestivals.registration' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $registration['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.127', 'msg'=>'Unable to load invoice item', 'err'=>$rc['err']));
        }
//        if( !isset($rc['item']) ) {
//            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.128', 'msg'=>'Unable to find requested invoice item'));
//        }
        if( isset($rc['item']) ) {
            $invoice_item = $rc['item'];
        }

        //
        // Remove invoice item, the callback from sapos will remove the registration.
        //
        if( $invoice['status'] < 15 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemDelete');
            $rc = ciniki_sapos_hooks_invoiceItemDelete($ciniki, $args['tnid'], array(
                'invoice_id' => $invoice['id'],
                'object' => 'ciniki.musicfestivals.registration',
                'object_id' => $registration['id'],
//                'deleteinvoice' => 'yes',
                ));
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'updated' && $rc['stat'] != 'blocked' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.154', 'msg'=>'Unable to remove item from invoice', 'err'=>$rc['err']));
            }
            return array('stat'=>'ok');
        } elseif( !preg_match("/Cancelled/", $invoice_item['description']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
            $rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $args['tnid'], array(
                'item_id' => $invoice_item['id'],
                'description' => $invoice_item['description'] . ' (Cancelled)',
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.425', 'msg'=>'Unable to update invoice', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $args['registration_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.71', 'msg'=>'Unable to check if the registration is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.72', 'msg'=>'The registration is still in use. ' . $rc['msg']));
    }

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
    // If the invoice item is specified
    //
    if( isset($invoice_item) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item',
            $invoice_item['id'], array('object'=>'', 'object_id'=>''), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Remove the registration
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration',
        $args['registration_id'], $registration['uuid'], 0x04);
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

    return array('stat'=>'ok');
}
?>
