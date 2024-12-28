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
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.884', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Check if customer is an admin for a member festival
    //
    $member_festival = 'no';
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql = "SELECT members.id, "
            . "members.name "
            . "FROM ciniki_musicfestival_member_customers AS mc "
            . "INNER JOIN ciniki_musicfestival_members AS fm ON ("
                . "mc.member_id = fm.member_id "
                . "AND fm.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND fm.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestivals_members AS members ON ("
                . "mc.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE mc.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND mc.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'member');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.749', 'msg'=>'Unable to load member', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            $items[] = array(
                'title' => 'Local Festival' . (count($rc['rows']) > 1 ? 's' : ''), 
                'priority' => 3760, 
                'selected' => isset($args['selected']) && $args['selected'] == 'musicfestivalmember' ? 'yes' : 'no',
                'ref' => 'ciniki.musicfestivals.members',
                'url' => $base_url . '/musicfestivalmember',
                );
            $member_festival = 'yes';
        }
    }

    //
    // Check if the customer is an adjudicator
    //
    $adjudicator = 'no';
    $strsql = "SELECT id "
        . "FROM ciniki_musicfestival_adjudicators "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'adjudicator');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.393', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
    }
    if( isset($rc['adjudicator']) ) {
        $items[] = array(
            'title' => 'Adjudications', 
            'priority' => 3750, 
            'selected' => isset($args['selected']) && $args['selected'] == 'musicfestivaladjudications' ? 'yes' : 'no',
            'ref' => 'ciniki.musicfestivals.adjudications',
            'url' => $base_url . '/musicfestivaladjudications',
            );
    }

    //
    // Check if the customer is or has been registered for the published festival
    //
/*    $strsql = "SELECT COUNT(*) AS registrations "
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
    } */

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
    } */
    if( $member_festival == 'no' ) {
        $items[] = array(
            'title' => 'Registrations', 
            'priority' => 3749, 
            'selected' => isset($args['selected']) && $args['selected'] == 'musicfestivalregistrations' ? 'yes' : 'no',
            'ref' => 'ciniki.musicfestivals.registrations',
            'url' => $base_url . '/musicfestivalregistrations',
            );
        $items[] = array(
            'title' => $festival['competitor-label-plural'], 
            'priority' => 3748, 
            'selected' => isset($args['selected']) && $args['selected'] == 'musicfestivalcompetitors' ? 'yes' : 'no',
            'ref' => 'ciniki.musicfestivals.competitors',
            'url' => $base_url . '/musicfestivalcompetitors',
            );

        //
        // Check if schedule posted yet
        //
        $strsql = "SELECT COUNT(sections.id) "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND (sections.flags&0x01) = 0x01 "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
        if( $rc['stat'] == 'ok' && isset($rc['num']) && $rc['num'] > 0 ) {
            $items[] = array(
                'title' => 'Schedule', 
                'priority' => 3747, 
                'selected' => isset($args['selected']) && $args['selected'] == 'musicfestivalschedule' ? 'yes' : 'no',
                'ref' => 'ciniki.musicfestivals.schedule',
                'url' => $base_url . '/musicfestivalschedule',
                );
        }
    }

    return array('stat'=>'ok', 'items'=>$items);
}
?>
