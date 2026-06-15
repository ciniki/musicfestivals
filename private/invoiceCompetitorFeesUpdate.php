<?php
//
// Description
// -----------
// This function will make sure the admin fee has been added to the invoice.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_invoiceCompetitorFeesUpdate(&$ciniki, $tnid, $args) {

    //
    // Check the invoice status
    //
    $strsql = "SELECT status "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1644', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1645', 'msg'=>'Unable to find requested invoice'));
    }
    $invoice = $rc['invoice'];

    //
    // Check for the number of competitors
    //
    $num_competitors = count($args['competitor_fees']);
    $competitor_fee = 0;
    foreach($args['competitor_fees'] as $fee) {
        if( $fee > $competitor_fee ) {
            $competitor_fee = $fee;
        }
    }

    if( $invoice['status'] < 50 ) {
        //
        // Check if the object already exists on the invoice
        //
        $strsql = "SELECT items.id, "
            . "items.uuid, "
            . "items.quantity, "
            . "items.unit_amount "
            . "FROM ciniki_sapos_invoice_items AS items "
            . "WHERE items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND items.object = 'ciniki.musicfestivals.competitorfee' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1646', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            $item = $rc['item'];
            if( $item['unit_amount'] != $competitor_fee || $item['quantity'] != $num_competitors ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
                $rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $tnid, array(
                    'item_id' => $item['id'],
                    'quantity' => $num_competitors,
                    'unit_amount' => $competitor_fee,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1647', 'msg'=>'Unable to update the admin fee', 'err'=>$rc['err']));
                }
                return array('stat'=>'ok', 'updated'=>'yes');
            }
        } 
        // 
        // Add the admin fee
        //
        else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
            $rc = ciniki_sapos_invoiceAddItem($ciniki, $tnid, array(
                'invoice_id' => $args['invoice_id'],
                'object' => 'ciniki.musicfestivals.competitorfee',
                'object_id' => 0,
                'description' => 'Competitor Fee',
                'flags' => 0x011008,
//                'line_number' => 990,
                'quantity' => $num_competitors,
                'unit_amount' => $competitor_fee,
                'unit_discount_amount' => 0,
                'unit_discount_percentage' => 0,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1648', 'msg'=>'Unable to add the admin fee', 'err'=>$rc['err']));
            }
            return array('stat'=>'ok', 'added'=>'yes');
        }
    }

    return array('stat'=>'ok');
}
?>
