<?php
//
// Description
// -----------
// This function will make sure the late fee has been added to the invoice.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_invoiceMemberLateFeeUpdate(&$ciniki, $tnid, $invoice_id, $latefee) {

    //
    // Check the invoice status
    //
    $strsql = "SELECT status "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.665', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.666', 'msg'=>'Unable to find requested invoice'));
    }
    $invoice = $rc['invoice'];

    if( $invoice['status'] < 50 ) {
        //
        // Check if the object already exists on the invoice
        //
        $strsql = "SELECT items.id, "
            . "items.uuid, "
            . "items.unit_amount "
            . "FROM ciniki_sapos_invoice_items AS items "
            . "WHERE items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "AND items.object = 'ciniki.musicfestivals.memberlatefee' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.661', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            $item = $rc['item'];
            if( $item['unit_amount'] != $latefee ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
                $rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $tnid, array(
                    'item_id' => $item['id'],
                    'unit_amount' => $latefee,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.662', 'msg'=>'Unable to update the late fee', 'err'=>$rc['err']));
                }
                return array('stat'=>'ok', 'updated'=>'yes');
            }
        } 
        // 
        // Add the late fee
        //
        else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
            $rc = ciniki_sapos_invoiceAddItem($ciniki, $tnid, array(
                'invoice_id' => $invoice_id,
                'object' => 'ciniki.musicfestivals.memberlatefee',
                'object_id' => 0,
                'description' => 'Late Fee',
                'flags' => 0x010088,
                'quantity' => 1,
                'unit_amount' => $latefee,
                'unit_discount_amount' => 0,
                'unit_discount_percentage' => 0,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.663', 'msg'=>'Unable to add the late fee', 'err'=>$rc['err']));
            }
            return array('stat'=>'ok', 'added'=>'yes');
        }
    }

    return array('stat'=>'ok');
}
?>
