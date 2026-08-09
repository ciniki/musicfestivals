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

    $items = [];

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'loadCurrentFestivals');
    $rc = ciniki_musicfestivals_wng_loadCurrentFestivals($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.884', 'msg'=>'', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals']) ) {
        return array('stat'=>'ok', 'blocks'=>[]);
    }
    $festivals = $rc['festivals'];

    $priority = 3750;
    foreach($festivals as $festival) {
        $festival_items = [];

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
            $adjudicator_id = $rc['adjudicator']['id'];
            //
            // Check if virtual entries
            //
            $num_virtual = 0;
            if( !isset($festival['comments-live-adjudication-online']) || $festival['comments-live-adjudication-online'] == 'no' ) {
                $strsql = "SELECT COUNT(registrations.id) AS num "
                    . "FROM ciniki_musicfestival_schedule_sections AS ssections "
                    . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                        . "ssections.id = divisions.ssection_id "
                        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
                        . "arefs.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
                        . "AND divisions.id = arefs.object_id "
                        . "AND arefs.object = 'ciniki.musicfestivals.scheduledivision' "
                        . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                        . "divisions.id = timeslots.sdivision_id "
                        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . ") "
                    . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                        . "timeslots.id = registrations.timeslot_id "
                        . "AND registrations.participation = 1 "
                        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . ") "
                    . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
                $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1016', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
                }
                $num_virtual = isset($rc['num']) ? $rc['num'] : '';
            }
            if( (isset($festival['comments-live-adjudication-online']) && $festival['comments-live-adjudication-online'] == 'yes')
                || (($festival['flags']&0x02) > 0 && $num_virtual > 0)
                ) {
                $festival_items[] = array(
                    'title' => 'Adjudications', 
                    'priority' => 3751, 
                    'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/adjudications' ? 'yes' : 'no',
                    'ref' => 'ciniki.musicfestivals.adjudications',
                    'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/adjudications',
                    );
            }
            if( isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 ) {
                $festival_items[] = array(
                    'title' => 'Provincial Recommendations', 
                    'priority' => 3750, 
                    'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/recommendations' ? 'yes' : 'no',
                    'ref' => 'ciniki.musicfestivals.recommendations',
                    'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/recommendations',
                    );
            }
            $adjudicator = 'yes';
        }

        //
        // Check if customer is a scrutineer
        //
        $scrutineer = 'no';
        if( isset($festival['registration-scrutineers-enable']) && $festival['registration-scrutineers-enable'] == 'yes' ) {
            $strsql = "SELECT id "
                . "FROM ciniki_musicfestival_scrutineers "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'scrutineer');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1140', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
                $scrutineer = 'yes';
                $festival_items[] = array(
                    'title' => 'Registrations Awaiting Review', 
                    'priority' => 3751, 
                    'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/scrutinizations' ? 'yes' : 'no',
                    'ref' => 'ciniki.musicfestivals.scrutinizations',
                    'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/scrutinizations',
                    );
            }
        }

        //
        // Check if the customer is an adjudicator
        //
        $soundtech = 'no';
        $strsql = "SELECT id "
            . "FROM ciniki_musicfestival_soundtechs "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'soundtech');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1609', 'msg'=>'Unable to load sound technician', 'err'=>$rc['err']));
        }
        if( isset($rc['soundtech']) ) {
            $soundtech = 'yes';
            $festival_items[] = array(
                'title' => 'Backtracks', 
                'priority' => 3751, 
                'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/backtracks' ? 'yes' : 'no',
                'ref' => 'ciniki.musicfestivals.backtracks',
                'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/backtracks',
                );
        }

        //
        // Check if they are setup for this music festival
        //
        $festival_items[] = array(
            'title' => 'Registrations', 
            'priority' => 3749, 
            'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/registrations' ? 'yes' : 'no',
            'ref' => 'ciniki.musicfestivals.registrations',
            'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/registrations',
            );
        $festival_items[] = array(
            'title' => $festival['competitor-label-plural'], 
            'priority' => 3748, 
            'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/competitors' ? 'yes' : 'no',
            'ref' => 'ciniki.musicfestivals.competitors',
            'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/competitors',
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
            $festival_items[] = array(
                'title' => 'Schedule', 
                'priority' => 3747, 
                'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/schedule' ? 'yes' : 'no',
                'ref' => 'ciniki.musicfestivals.schedule',
                'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/schedule',
                );
        }

        //
        // Check if volunteers are enabled
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x01) 
            && (!isset($festival['volunteers-account-menu']) || $festival['volunteers-account-menu'] == 'yes') 
            ) {
            $festival_items[] = array(
                'title' => 'Volunteer', 
                'priority' => 3746, 
                'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/volunteer' ? 'yes' : 'no',
                'ref' => 'ciniki.musicfestivals.volunteer',
                'url' => $base_url . '/musicfestival/' . $festival['permalink'] . '/volunteer',
                );
        }

        //
        // Adjudicators for the current local festival get dropdown menu 
        //
        if( count($festivals) > 1 ) {
            $dropdown_items = $festival_items;
            $items[] = [
                'title' => $festival['name'],
                'priority' => $priority--,
                'items' => $dropdown_items,
                ];
        } elseif( $adjudicator == 'yes' || $scrutineer == 'yes' ) {
            $dropdown_items = $festival_items;
            $items[] = [
                'title' => 'Music Festival',
                'priority' => 3750,
                'items' => $dropdown_items,
                ];
        } else {
            foreach($festival_items as $item) {
                $items[] = $item;
            }
        }
    }

    //
    // Check if customer is an admin for a member festival
    //
    $member_festival = 'no';
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql = "SELECT members.id, "
            . "members.name "
            . "FROM ciniki_musicfestival_member_customers AS mc "
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
                'selected' => isset($args['selected']) && $args['selected'] == 'musicfestival/member' ? 'yes' : 'no',
                'ref' => 'ciniki.musicfestivals.members',
                'url' => $base_url . '/musicfestival/member',
                );
            $member_festival = 'yes';
        }
    }

    //
    // Check for past festival registrations
    //
    $strsql = "SELECT DISTINCT registrations.festival_id "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "registrations.festival_id = festivals.id "
            . "AND festivals.status = 50 "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND registrations.status < 70 "
        . "AND registrations.status > 7 "
//        . "AND registrations.comments <> '' "
        . "AND registrations.festival_id <> '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.930', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items[] = array(
            'title' => 'Past Results', 
            'priority' => 3740, 
            'selected' => isset($args['selected']) && $args['selected'] == 'pastmusicfestivals' ? 'yes' : 'no',
            'ref' => 'ciniki.musicfestivals.past',
            'url' => $base_url . '/pastmusicfestivals',
            );
    }

    return array('stat'=>'ok', 'items'=>$items);
}
?>
