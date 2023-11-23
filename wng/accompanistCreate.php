<?php
//
// Description
// -----------
// This function will create or find an existing accompanist.
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
function ciniki_musicfestivals_wng_accompanistCreate(&$ciniki, $tnid, $request, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.369', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( !isset($_POST['f-accompanist_name']) 
        || ($_POST['f-accompanist_name'] == '' && isset($_POST['f-accompanist_email']) && $_POST['f-accompanist_email'] == '')
        ) {
        return array('stat'=>'ok', 'accompanist_customer_id'=>0);
    }

    //
    // Check if accompanist email is found
    //
    if( isset($_POST['f-accompanist_email']) && $_POST['f-accompanist_email'] != '' ) {
        $strsql = "SELECT e.id, e.customer_id "
            . "FROM ciniki_customer_emails AS e "
            . "INNER JOIN ciniki_customers AS c ON ("
                . "e.customer_id = c.id "
                . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND c.status < 60 "
                . ") "
            . "WHERE e.email = '" . ciniki_core_dbQuote($ciniki, $_POST['f-accompanist_email']) . "' "
            . "AND e.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.375', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            return array('stat'=>'ok', 'accompanist_customer_id'=>$rc['item']['customer_id']);
        }
    }

    //
    // Check if the accompanist_name is found
    //
    if( isset($_POST['f-accompanist_name']) && $_POST['f-accompanist_name'] != '' ) {
        $strsql = "SELECT id, display_name "
            . "FROM ciniki_customers "
            . "WHERE (display_name = '" . ciniki_core_dbQuote($ciniki, $_POST['f-accompanist_name']) . "' "
                . "OR company = '" . ciniki_core_dbQuote($ciniki, $_POST['f-accompanist_name']) . "' "
                . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.376', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            return array('stat'=>'ok', 'accompanist_customer_id'=>$rc['item']['id']);
        }
    }

    //
    // Check that both name and email were specified
    //
    if( $_POST['f-accompanist_name'] == '' && isset($_POST['f-accompanist_email']) && $_POST['f-accompanist_email'] != '' ) {
        return array('stat'=>'error', 'err'=>array('code'=>'ciniki.musicfestivals.377', 'msg'=>"You must specifiy the Teacher's Name"));
    }

    //
    // Create the accompanist
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerAdd');
    $rc = ciniki_customers_web_customerAdd($ciniki, $tnid, array(
        'name'=>$_POST['f-accompanist_name'],
        'email_address'=>$_POST['f-accompanist_email'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'error', 'err'=>array('code'=>'ciniki.musicfestivals.374', 'msg'=>"We had a problem saving the accompanist, please try again or contact us for help."));
    }

    return array('stat'=>'ok', 'accompanist_customer_id'=>$rc['id']);
}
?>
