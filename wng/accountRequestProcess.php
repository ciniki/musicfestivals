<?php
//
// Description
// -----------
// This function will process the account request from accountMenuItems
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountRequestProcess(&$ciniki, $tnid, &$request, $item) {

    if( !isset($item['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.252', 'msg'=>'No reference specified'));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.255', 'msg'=>'Must be logged in'));
    }

    if( $item['ref'] == 'ciniki.musicfestivals.registrations' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountRegistrationsProcess');
        return ciniki_musicfestivals_wng_accountRegistrationsProcess($ciniki, $tnid, $request, $item);
    } elseif( $item['ref'] == 'ciniki.musicfestivals.competitors' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountCompetitorsProcess');
        return ciniki_musicfestivals_wng_accountCompetitorsProcess($ciniki, $tnid, $request, $item);
    } elseif( $item['ref'] == 'ciniki.musicfestivals.adjudications' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountAdjudicationsProcess');
        return ciniki_musicfestivals_wng_accountAdjudicationsProcess($ciniki, $tnid, $request, $item);
    } elseif( $item['ref'] == 'ciniki.musicfestivals.members' ) {   
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountMembersProcess');
        return ciniki_musicfestivals_wng_accountMembersProcess($ciniki, $tnid, $request, $item);
    }
    

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.258', 'msg'=>'Account page not found'));
}
?>
