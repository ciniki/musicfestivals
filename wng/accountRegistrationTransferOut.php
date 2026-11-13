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
function ciniki_musicfestivals_wng_accountRegistrationTransferOut(&$ciniki, $tnid, &$request, $args) {

    $blocks = [];

    //
    // Make sure a festival was specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1736', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    //
    // Make sure registrations where passed in arguments
    //
    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1737', 'msg'=>"No registration specified"));
    }
    $registration = $args['registration'];

    //
    // Lookup the class code and name
    //
    $strsql = "SELECT classes.code, "
        . "classes.name, "
        . "categories.name AS category_name, "
        . "sections.name AS section_name "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $registration['class_id']) . "' "
        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1735', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1747', 'msg'=>'Unable to find requested class'));
    }
    $selected_class = $rc['class'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');
    $rc = ciniki_musicfestivals_classNameFormat($ciniki, $tnid, [
        'code' => $selected_class['code'],
        'name' => $selected_class['name'],
        'category' => $selected_class['category_name'],
        'section' => $selected_class['section_name'],
        'format' => isset($festival['registrations-class-format']) ? preg_replace("/code-/", '', $festival['registrations-class-format']) : 'class',
        ]);
    $registration['class_code'] = $selected_class['code'];
    $registration['class_name'] = $rc['name'];

    //
    // Load the invoice item
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
    $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $tnid, $request['session']['cart']['id'], 'ciniki.musicfestivals.registration', $registration['registration_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1738', 'msg'=>'Unable to get invoice item', 'err'=>$rc['err']));
    }
    $item = $rc['item'];

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
        'parent_customer_id' => $args['parent_customer_id'],
        'invoice_id' => 0,
        'status' => 6,
        ], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1739', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
    } 

    //
    // Remove registration from cart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice_item', $item['id'], $item['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1740', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
    } 

    //
    // Update cart Extra Fees
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'invoiceExtraFeesCheck');
    $rc = ciniki_musicfestivals_sapos_invoiceExtraFeesCheck($ciniki, $tnid, [
        'invoice_id' => $registration['invoice_id'],
        ]);
    if( $rc['stat'] != 'ok' && $rc['stat'] != 'updated' && $rc['stat'] != 'blocked' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1741', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
    }

    //
    // Update the old cart taxes
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $registration['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1742', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
    }

    //
    // Update the old cart invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $registration['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1743', 'msg'=>'Unable to transfer registration, please try again or contact us for help.'));
    }

    //
    // Reload cart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
    $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1744', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
    }

    //
    // Check for email to send
    //
    if( isset($festival['registration-transfers-email-subject']) && $festival['registration-transfers-email-subject'] != '' 
        && isset($festival['registration-transfers-email-message']) && $festival['registration-transfers-email-message'] != '' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationTransferOutEmail');
        $rc = ciniki_musicfestivals_registrationTransferOutEmail($ciniki, $tnid, $request, [
            'registration' => $registration,
            'festival' => $festival,
            'parent_customer_id' => $args['parent_customer_id'],
            'tofirstname' => $args['tofirstname'],
            'toname' => $args['toname'],
            'fromname' => $args['fromname'],
            'email' => $args['email'],
            ]);
    }

    ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    return array('stat'=>'transferred');
}
?>
