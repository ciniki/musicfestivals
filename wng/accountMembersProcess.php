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
    // Get the list of members and the provincial festivals they are attached to
    //
    $strsql = "SELECT members.id, "
        . "members.permalink, "
        . "members.name, "
        . "festivals.id AS festival_id, "
        . "festivals.name AS festival_name, "
        . "festivals.permalink AS festival_permalink "
        . "FROM ciniki_musicfestival_member_customers AS mc "
        . "INNER JOIN ciniki_musicfestival_members AS fm ON ("
            . "mc.member_id = fm.member_id "
            . "AND fm.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "fm.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestivals_members AS members ON ("
            . "mc.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE mc.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND mc.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY members.name, festivals.start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'name'),
            ),
        array('container'=>'festivals', 'fname'=>'festival_id', 
            'fields'=>array('id'=>'festival_id', 'permalink'=>'festival_permalink', 'name'=>'festival_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.136', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    //
    // Build member_ids
    //
    $member_ids = [];
    foreach($members as $member) {
        $member_ids[] = $member['id'];
    }

    if( count($members) == 0 ) {
        return array('stat'=>'ok', 'blocks'=>array(
            array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'No local festival found',
                ),
            ));
    }

    //
    // Get the number of recommendations
    //
    $strsql = "SELECT recommendations.member_id, recommendations.festival_id, COUNT(entries.id) AS num_items "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE recommendations.member_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $member_ids) . ") "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY recommendations.member_id, recommendations.festival_id "
        . "ORDER BY recommendations.member_id, recommendations.festival_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'member_id', 'fields'=>array()),
        array('container'=>'recommendations', 'fname'=>'festival_id', 
            'fields'=>array('num_items'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.396', 'msg'=>'Unable to load recommendations', 'err'=>$rc['err']));
    }
    $recommendations = isset($rc['members']) ? $rc['members'] : array();

    //
    // Get the number of registrations
    //
    $strsql = "SELECT registrations.member_id, registrations.festival_id, COUNT(registrations.member_id) AS num_items "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.member_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $member_ids) . ") "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY registrations.member_id, registrations.festival_id "
        . "ORDER BY registrations.member_id, registrations.festival_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'member_id', 'fields'=>array()),
        array('container'=>'registrations', 'fname'=>'festival_id', 
            'fields'=>array('num_items'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.422', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['members']) ? $rc['members'] : array();

    //
    // Add recommendations and registrations to festivals array
    //
    foreach($members as $mid => $member) {

        foreach($member['festivals'] as $fid => $festival) {
       
            $members[$mid]['festivals'][$fid]['links'] = '';
            $members[$mid]['festivals'][$fid]['num_recommendations'] = '';
            if( isset($recommendations[$mid]['recommendations'][$fid]['num_items']) ) {
                $members[$mid]['festivals'][$fid]['num_recommendations'] = $recommendations[$mid]['recommendations'][$fid]['num_items'];
                $members[$mid]['festivals'][$fid]['links'] .= "<a class='button' href='{$base_url}/recommendations/{$member['permalink']}/{$festival['permalink']}'>Recommendations</a>";
                if( isset($request['uri_split'][4]) 
                    && $request['uri_split'][2] == 'recommendations'
                    && $request['uri_split'][3] == $member['permalink']
                    && $request['uri_split'][4] == $festival['permalink']
                    ) {
                    $args['member'] = $member;
                    $args['festival'] = $festival;
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountMemberRecommendationsProcess');
                    return ciniki_musicfestivals_wng_accountMemberRecommendationsProcess($ciniki, $tnid, $request, $args);
                }
            }
            $members[$mid]['festivals'][$fid]['num_registrations'] = '';
            if( isset($registrations[$mid]['registrations'][$fid]['num_items']) ) {
                $members[$mid]['festivals'][$fid]['num_registrations'] = $registrations[$mid]['registrations'][$fid]['num_items'];
                $members[$mid]['festivals'][$fid]['links'] .= "<a class='button' href='{$base_url}/registrations/{$member['permalink']}/{$festival['permalink']}'>Registrations</a>";
                if( isset($request['uri_split'][4]) 
                    && $request['uri_split'][2] == 'registrations'
                    && $request['uri_split'][3] == $member['permalink']
                    && $request['uri_split'][4] == $festival['permalink']
                    ) {
                    $args['member'] = $member;
                    $args['festival'] = $festival;
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountMemberRegistrationsProcess');
                    return ciniki_musicfestivals_wng_accountMemberRegistrationsProcess($ciniki, $tnid, $request, $args);
                }
            }

        }

        $blocks[] = [
            'type' => 'table', 
            'title' => $member['name'],
            'headers' => 'yes',
            'columns' => [
                ['label' => 'Provincials', 'field' => 'name'],
                ['label' => 'Recommendations', 'field' => 'num_recommendations', 'class' => 'aligncenter' ],
                ['label' => 'Registrations', 'field' => 'num_registrations', 'class' => 'aligncenter' ],
                ['label' => '', 'field' => 'links', 'class' => 'alignright buttons' ],
                ],
            'rows' => $members[$mid]['festivals'],
            ];
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
