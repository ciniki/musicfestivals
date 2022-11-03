<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_itemAdd($ciniki, $tnid, $invoice_id, $item) {

    //
    // An registration was added to an invoice item, get the details and see if we need to 
    // create a registration 
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.musicfestivals.class' && isset($item['object_id']) ) {
        //
        // Load current festival
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
        $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.413', 'msg'=>'', 'err'=>$rc['err']));
        }
        $festival = $rc['festival'];
       
        //
        // Search classes by code or name
        //
        $strsql = "SELECT classes.id, "
            . "classes.code, "
            . "classes.name, "
            . "classes.earlybird_fee, "
            . "classes.fee, "
            . "classes.virtual_fee "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "WHERE classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND classes.id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.398', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
        }
        if( !isset($rc['class']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.399', 'msg'=>'Unable to find requested class'));
        }
        $class = $rc['class'];

        $fee = 0;
        $participation = 0;
        if( isset($festival['earlybird']) && $festival['earlybird'] == 'yes' ) {
            $fee = $class['earlybird_fee'];
        } elseif( isset($festival['live']) && $festival['live'] == 'yes' ) {
            $fee = $class['fee'];
        } elseif( isset($festival['virtual']) && $festival['virtual'] == 'yes' ) {
            $participation = 1;
            $fee = $class['virtual_fee'];
        } else {
            $fee = $class['fee'];
        }

        //
        // Load the customer for the invoice
        //
        $strsql = "SELECT id, customer_id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.411', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        

        //
        // Create the registration
        //
        $registration = array(
            'festival_id' => $festival['id'],
            'billing_customer_id' => $invoice['customer_id'],
            'teacher_customer_id' => 0,
            'rtype' => 30,
            'status' => 6,
            'invoice_id' => $invoice_id,
            'display_name' => '',
            'public_name' => '',
            'competitor1_id' => 0,
            'competitor2_id' => 0,
            'competitor3_id' => 0,
            'competitor4_id' => 0,
            'competitor5_id' => 0,
            'class_id' => $class['id'],
            'timeslot_id' => 0,
            'title1' => '',
            'perf_time1' => '',
            'title2' => '',
            'perf_time2' => '',
            'title3' => '',
            'perf_time3' => '',
            'payment_type' => 0,
            'fee' => $fee,
            'participation' => $participation,
            );

        //
        // Add the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.412', 'msg'=>'Unable to add the registration', 'err'=>$rc['err']));
        }
        $reg_id = $rc['id'];
        
        return array('stat'=>'ok', 'object'=>'ciniki.musicfestivals.registration', 'object_id'=>$reg_id);
    }

    return array('stat'=>'ok');
}
?>
