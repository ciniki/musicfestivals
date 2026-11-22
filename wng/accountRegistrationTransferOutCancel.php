<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_accountRegistrationTransferOutCancel(&$ciniki, $tnid, &$request, $args) {

    $blocks = [];

    //
    // Make sure a festival was specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1749', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    //
    // Make sure registrations where passed in arguments
    //
    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1750', 'msg'=>"No registration specified"));
    }
    $registration = $args['registration'];

    //
    // Check for Cart
    //
    if( !isset($request['session']['cart']['id']) || $request['session']['cart']['id'] == 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCreate');
        $rc = ciniki_sapos_wng_cartCreate($ciniki, $tnid, $request, array());
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1751', 'msg'=>'Error opening cart', 'err'=>$rc['err']));
        }
    }

    //
    // Lookup the class code and name
    //
    $strsql = "SELECT classes.code, "
        . "classes.name "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $registration['class_id']) . "' "
        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1752', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1753', 'msg'=>'Unable to find requested class'));
    }
    $selected_class = $rc['class'];

    // Setup the description for the invoice 
    $description = $selected_class['name'];
    if( $registration['participation'] == 1 ) {
        $description .= ' (Virtual)';
    } elseif( $registration['participation'] == 0 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
        $description .= ' (Live)';
    } elseif( $registration['participation'] == 2) {
        $description .= ' (Adjudication Plus)';
    }

    //
    // Generate notes field for invoice
    //
    $notes = $registration['display_name'];
    $titles = '';
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $registration, ['basicnumbers'=>'yes']);
    if( isset($rc['titles']) ) {
        $titles = $rc['titles'];
    } 
    if( $titles != '' ) {
        $notes .= "\n" . $titles;
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
    // Update the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration['registration_id'], [
        'billing_customer_id' => $request['session']['customer']['id'],
        'parent_customer_id' => 0,
        'invoice_id' => $request['session']['cart']['id'],
        'status' => 5,
        ], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1754', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
    }

    //
    // Add the registration to the cart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemAdd');
    $rc = ciniki_sapos_wng_cartItemAdd($ciniki, $tnid, $request, array(
        'object' => 'ciniki.musicfestivals.registration',
        'object_id' => $registration['registration_id'],
        'price_id' => 0,
        'quantity' => 1,
        'flags' => 0x08,
        'code' => $selected_class['code'],
        'description' => $description,
        'unit_amount' => $registration['fee'],
        'unit_discount_amount' => 0,
        'unit_discount_percentage' => 0,
        'taxtype_id' => 0,
        'notes' => $notes,
        ));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1755', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
    }

        //
        // FIXME: Make sure member late fee added if applicable
        //
//        if( isset($args['selected_member']['latefee']) ) {
//            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceMemberLateFeeUpdate');
//            $rc = ciniki_musicfestivals_invoiceMemberLateFeeUpdate($ciniki, $tnid, $request['session']['cart']['id'], $args['selected_member']['latefee']);
//            if( $rc['stat'] != 'ok' ) {
//                return $rc;
//            }
//        }

    //
    // Reload cart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
    $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1756', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
    }

    ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    return array('stat'=>'ok');
}
?>
