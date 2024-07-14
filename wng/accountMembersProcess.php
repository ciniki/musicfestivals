<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountMembersProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'videoProcess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestivalmembers';
    $display = 'list';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Back' ) {
        header("Location: {$request['ssl_domain_base_url']}/account/musicfestivalmembers");
        return array('stat'=>'exit');
    }

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.395', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load the festival details
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.396', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    if( isset($rc['settings']) ) {
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }
    }

    //
    // Load the member festivals
    //
    $strsql = "SELECT members.id, "
        . "members.permalink, "
        . "members.name "
        . "FROM ciniki_musicfestivals_members AS members "
        . "INNER JOIN ciniki_musicfestival_members AS fm ON ("
            . "members.id = fm.member_id "
            . "AND fm.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND fm.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE members.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'permalink', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.750', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    //
    // Check if no members for this festival
    //
    if( count($members) == 0 ) {
        return array('stat'=>'ok', 'blocks'=>array(
            array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'No local festival found',
                ),
            ));
    }
    $member_ids = array_keys($members);

    if( isset($request['uri_split'][3]) ) {
        foreach($members as $member) {
            if( $member['permalink'] == $request['uri_split'][2] ) {
                $args['member'] = $member;
                $args['festival'] = $festival;
                if( $request['uri_split'][3] == 'recommendations' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountMemberRecommendationsProcess');
                    return ciniki_musicfestivals_wng_accountMemberRecommendationsProcess($ciniki, $tnid, $request, $args);
                } elseif( $request['uri_split'][3] == 'registrations' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountMemberRegistrationsProcess');
                    return ciniki_musicfestivals_wng_accountMemberRegistrationsProcess($ciniki, $tnid, $request, $args);
                }
            }
        }
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => 'No local festival found',
            );
    }

    //
    // Get the number of recommendations
    //
    $strsql = "SELECT recommendations.member_id, COUNT(entries.id) "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE recommendations.member_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $member_ids) . ") "
        . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY recommendations.member_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'recommendations');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.751', 'msg'=>'Unable to load get the number of recommendations', 'err'=>$rc['err']));
    }
    $recommendations = isset($rc['recommendations']) ? $rc['recommendations'] : array();

    //
    // Get the number of registrations
    //
    $strsql = "SELECT member_id, COUNT(member_id) "
        . "FROM ciniki_musicfestival_registrations "
        . "WHERE member_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $member_ids) . ") "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY member_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'registrations');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.752', 'msg'=>'Unable to load get the number of registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    //
    // Setup the buttons for each festival
    //
    foreach($members as $mid => $member) {
        $blocks[] = array(
            'type' => 'buttons',
            'title' => $member['name'],
            'level' => 2,
            'class' => 'aligncenter',
            'items' => array(
//                array(
//                    'text' => (isset($recommendations[$mid]) ? $recommendations[$mid] : '0') . ' Recommendations',
//                    'url' => $base_url . '/' . $member['permalink'] . '/recommendations',
//                ),
                array(
                    'text' => 'View Your ' . (isset($registrations[$mid]) ? $registrations[$mid] : '0') . ' Registrations',
                    'url' => $base_url . '/' . $member['permalink'] . '/registrations',
                ),
            ));
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
