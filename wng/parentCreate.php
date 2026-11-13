<?php
//
// Description
// -----------
// This function will create or find an existing parent.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get music festival request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_parentCreate(&$ciniki, $tnid, $request, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1719', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( !isset($_POST['f-parent_first']) || !isset($_POST['f-parent_last']) || !isset($_POST['f-parent_email'])
        || $_POST['f-parent_first'] == '' || $_POST['f-parent_last'] == '' || $_POST['f-parent_email'] == ''
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1720', 'msg'=>'No parent specified'));
    }

    //
    // Make sure email is valid
    //
    if( !filter_var($_POST['f-parent_email'], FILTER_VALIDATE_EMAIL) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1725', 'msg'=>'Invalid email address'));
    }

    //
    // Check if parent email is found
    //
    if( isset($_POST['f-parent_email']) && $_POST['f-parent_email'] != '' ) {
        $strsql = "SELECT e.id, e.customer_id "
            . "FROM ciniki_customer_emails AS e "
            . "INNER JOIN ciniki_customers AS c ON ("
                . "e.customer_id = c.id "
                . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND c.status < 60 "
                . ") "
            . "WHERE e.email = '" . ciniki_core_dbQuote($ciniki, trim($_POST['f-parent_email'])) . "' "
            . "AND e.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1721', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            return array('stat'=>'ok', 'parent_customer_id'=>$rc['item']['customer_id']);
        }
    }

    //
    // Create the parent
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'customerAdd');
    $rc = ciniki_customers_wng_customerAdd($ciniki, $tnid, $request, [
        'first'=>$_POST['f-parent_first'],
        'last'=>$_POST['f-parent_last'],
        'email_address'=>$_POST['f-parent_email'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'error', 'err'=>array('code'=>'ciniki.musicfestivals.1723', 'msg'=>"We had a problem saving the parent, please try again or contact us for help."));
    }
    $customer_id = $rc['id'];

    //
    // Set the customer type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.customer', [
        'festival_id' => $args['festival']['id'],
        'customer_id' => $customer_id,
        'ctype' => 10,  // Parent
        ], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1724', 'msg'=>'Unable to add the parent', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'parent_customer_id'=>$rc['id']);
}
?>
