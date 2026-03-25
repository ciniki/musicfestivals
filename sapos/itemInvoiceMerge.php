<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_itemInvoiceMerge($ciniki, $tnid, $item, $primary_invoice_id, $secondary_invoice_id) {

    //
    // Update registrations with new invoice id
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.musicfestivals.registration' && isset($item['object_id']) ) {
        //
        // Check the music festival registration exists
        //
        $strsql = "SELECT id, uuid, festival_id, invoice_id "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            // Don't worry if can't find existing reg, probably database error
            return array('stat'=>'ok');
        }
        $registration = $rc['registration'];

        //
        // Update the item
        //
        if( $registration['invoice_id'] != $primary_invoice_id ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $item['object_id'], [
                'invoice_id' => $primary_invoice_id,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1529', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
            }
        }

        return array('stat'=>'ok');
    }

    //
    // Admin/Late fees should be removed from the invoice
    //
    if( isset($item['object']) 
        && $item['object'] != 'ciniki.musicfestivals.registration' 
        && preg_match("/ciniki.musicfestivals.(memberlatefee|adminfee|latefee|latefees)/", $item['object'])
        && isset($item['object_id']) 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item', $item['id'], null, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1533', 'msg'=>'Unable to update the invoice', 'err'=>$rc['err']));
        }
    }
    
    return array('stat'=>'ok');
}
?>
