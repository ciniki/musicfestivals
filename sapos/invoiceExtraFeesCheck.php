<?php
//
// Description
// -----------
// This function will check for updates to admin or late fees, along with checking if they should be removed.
//
function ciniki_musicfestivals_sapos_invoiceExtraFeesCheck($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'registrationExtraFeesCheck');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemDelete');

    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.875', 'msg'=>'No invoice specified.'));
    }

    //
    // Get the list of registrations on the invoice
    //
    $strsql = "SELECT items.id, "
        . "items.code, "
        . "items.description, "
        . "items.object, "
        . "items.object_id "
        . "FROM ciniki_sapos_invoice_items AS items "
        . "WHERE items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'code', 'description', 'object', 'object_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.867', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
    }
    $items = isset($rc['items']) ? $rc['items'] : array();
   
    //
    // Look for registrations 
    //
    $num_registrations = 0;
    $latefee = 0;
    $cart_latefees = 0;
    $closed_registrations = '';
    foreach($items as $iid => $item) {
        if( $item['object'] == 'ciniki.musicfestivals.registration' && $item['object_id'] > 0 ) {
            if( isset($args['ignore_registration_id']) && $args['ignore_registration_id'] == $item['object_id'] ) {
                // This is the registration that is in the process of being deleted
                continue;
            }
            $num_registrations++;
            $rc = ciniki_musicfestivals_sapos_registrationExtraFeesCheck($ciniki, $tnid, [
                'invoice_id' => $args['invoice_id'],
                'registration_id' => $item['object_id'],
                'closed' => (isset($args['closed']) ? $args['closed'] : ''),
                ]);
            if( $rc['stat'] == 'blocked' ) {
                $closed_registrations .= ($closed_registrations != '' ? "\n" : '') . $item['code'] . ' - ' . $item['description'];
            }
            elseif( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['latefee']) ) {
                $latefee += $rc['latefee'];
            }
            if( isset($rc['cart_latefee']) ) {
                $cart_latefees += $rc['cart_latefee'];
            }
        }
    }

    if( $closed_registrations != '' ) {
        return array('stat'=>'blocked', 'msg'=>$closed_registrations);
    }

    //
    // If no items, check if fees still exist
    //
    $updated = 'no';
    foreach($items as $iid => $item) {
        if( ($item['object'] == 'ciniki.musicfestivals.latefee' && $latefee == 0) 
            || ($item['object'] == 'ciniki.musicfestivals.latefees' && $cart_latefees == 0) 
            || ($item['object'] == 'ciniki.musicfestivals.adminfee' && $num_registrations == 0)
            || ($item['object'] == 'ciniki.musicfestivals.memberlatefee' && $num_registrations == 0)
            ) {
            $rc = ciniki_sapos_hooks_invoiceItemDelete($ciniki, $tnid, [
                'invoice_id' => $args['invoice_id'],
                'object' => $item['object'],
                'object_id' => $item['object_id'],
                ]);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $item['object'] == 'ciniki.musicfestivals.latefee' ) {
                $updated = 'yes';
            }
            if( $item['object'] == 'ciniki.musicfestivals.latefees' ) {
                $updated = 'removed';
            }
        }
    }

    if( $cart_latefees > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceLateFeesUpdate');
        $rc = ciniki_musicfestivals_invoiceLateFeesUpdate($ciniki, $tnid, $args['invoice_id'], $cart_latefees);
        if( isset($rc['added']) && $rc['added'] == 'yes' ) {
            return array('stat'=>'updated', 'msg'=>'Late fees have been added.');
        }
        if( isset($rc['updated']) && $rc['updated'] == 'yes' ) {
            return array('stat'=>'updated', 'msg'=>'Late fees have been updated.');
        }
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.726', 'msg'=>'Unable to update late fees', 'err'=>$rc['err']));
        }
    }

    if( $updated == 'removed' ) {
        return array('stat'=>'updated', 'msg'=>'Late fees have been removed.');
    }
    if( $updated == 'yes' ) {
        return array('stat'=>'updated', 'msg'=>'Late fees have been removed.');
    }

    return array('stat'=>'ok');
}
?>
