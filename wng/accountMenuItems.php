<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountMenuItems($ciniki, $tnid, $request, $args) {

    $items = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // Get the music festival with the most recent date and status published
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_musicfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 30 "        // Published
        . "ORDER BY start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.253', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        // No festivals published, no items to return
        return array('stat'=>'ok');
    }
    $festival = $rc['festival'];

    //
    // Check if the customer is or has been registered for the published festival
    //
    $strsql = "SELECT COUNT(*) AS registrations "
        . "FROM ciniki_musicfestival_registrations "
        . "WHERE billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.254', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    if( isset($rc['num']) && $rc['num'] > 0 ) {
        $items[] = array(
            'title' => 'Registrations', 
            'priority' => 750, 
            'selected' => 'no',
            'ref' => 'ciniki.musicfestivals.registrations',
            'url' => $base_url . '/musicfestivalregistrations',
            );
    }

    //
    // Check if they are setup for this music festival
    //
    /*
    $strsql = "SELECT id, ctype "
        . "FROM ciniki_musicfestival_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.327', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    if( isset($rc['customer']) ) { */
/*        $items[] = array(
            'title' => 'Registrations', 
            'priority' => 749, 
            'selected' => 'no',
            'ref' => 'ciniki.musicfestivals.registrations',
            'url' => $base_url . '/musicfestivalregistrations',
            );
        $items[] = array(
            'title' => 'Competitors', 
            'priority' => 748, 
            'selected' => 'no',
            'ref' => 'ciniki.musicfestivals.competitors',
            'url' => $base_url . '/musicfestivalcompetitors',
            ); */
//    }

    return array('stat'=>'ok', 'items'=>$items);
}
?>
