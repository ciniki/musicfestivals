<?php
//
// Description
// -----------
// This function will generate the blocks to display member festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_membersProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.588', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.589', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();
    $base_url = $request['page']['path'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the list of member festivals and their details
    //
    $strsql = "SELECT members.id, "
        . "members.name, "
        . "members.permalink, "
        . "members.category, "
        . "members.status, "
        . "members.status AS status_text, "
        . "members.synopsis, "
        . "IFNULL(fmembers.reg_start_dt, '') AS reg_start_dt_display, "
        . "IFNULL(fmembers.reg_end_dt, '') AS reg_end_dt, "
        . "IFNULL(fmembers.reg_end_dt, '') AS reg_end_dt_display, "
//        . "IFNULL(DATE_ADD(fmembers.reg_end_dt, INTERVAL fmembers.latedays DAY), '') AS reg_late_dt_display, "
        . "IFNULL(fmembers.latedays, '') AS latedays, "
        . "IFNULL(fmembers.yearly_details, '') AS yearly_details "
        . "FROM ciniki_musicfestivals_members AS members "
        . "LEFT JOIN ciniki_musicfestival_members AS fmembers ON ("
            . "members.id = fmembers.member_id "
            . "AND fmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND fmembers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE members.status < 90 " // Active, Closed
        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY members.id "
        . "ORDER BY members.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'title'=>'name', 'permalink', 'id-permalink'=>'permalink', 'category', 'status', 'synopsis', 'reg_start_dt_display', 'reg_end_dt', 'reg_end_dt_display', 'latedays', 'yearly_details'),
            'utctotz'=>array(
                'reg_start_dt_display' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i A'),
                'reg_end_dt_display' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i A'),
//                'reg_late_dt_display' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i A'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.635', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    if( (isset($s['title']) && $s['title'] != '') || (isset($s['content']) && $s['content'] != '') ) {
        $blocks[] = array(
            'type' => (!isset($s['content']) || $s['content'] == '' ? 'title' : 'text'),
            'title' => isset($s['title']) ? $s['title'] : '',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'content' => isset($s['content']) ? $s['content'] : '',
            );
    }

    //
    // Display the alphabetical list
    //
    if( isset($s['display-format']) && ($s['display-format'] == 'both' || $s['display-format'] == 'alphabetical') ) {
        $content = '';
        foreach($members as $member) {
            $content .= "<a title='{$member['name']}' href='javascript:C.gE(\"{$member['id-permalink']}\").scrollIntoView();C.tC(C.gE(\"{$member['id-permalink']}\"),\"collapsed\");'>{$member['name']}</a>";
        }
        if( $content != '' ) {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'musicfestival-members musicfestival-members-alphabetical',
                'content' => $content,
                );
        }
    }

    // 
    // Display the categories with information blocks for each member festival
    //
    if( isset($s['display-format']) && ($s['display-format'] == 'both' || $s['display-format'] == 'categories') ) {
        $content = '';
        $categories = array();
        foreach($members as $member) {
            $member['synopsis'] = preg_replace("/\n([A-Za-z ]+): /", "\n<b>$1</b>: ", $member['synopsis']);
            $member['synopsis'] = preg_replace("/^([A-Za-z ]+): /", "\n<b>$1</b>: ", $member['synopsis']);
            $member['yearly_details'] = preg_replace("/^([A-Za-z ]+): /", "\n<b>$1</b>: ", $member['yearly_details']);
            $member['yearly_details'] = preg_replace("/\n([A-Za-z ]+): /", "\n<b>$1</b>: ", $member['yearly_details']);
            if( isset($s['display-synopsis']) && $s['display-synopsis'] == 'no' ) {
                $member['synopsis'] = '';
            }
            if( isset($s['display-deadlines']) && $s['display-deadlines'] == 'yes' ) {
                $late_dt = new DateTime($member['reg_end_dt_display'], new DateTimezone($intl_timezone));
                if( $member['latedays'] > 0 ) {
                    $late_dt->add(new DateInterval('P' . $member['latedays'] . 'D'));
                }
                $member['reg_late_dt_display'] = $late_dt->format('M j, Y g:i A');
                if( $member['yearly_details'] != '' ) {
                    $member['synopsis'] .= ($member['synopsis'] != '' ? "\n\n" : '' ) . $member['yearly_details'];
                }
                $member['synopsis'] .= ($member['synopsis'] != '' ? "\n\n<div class='line'></div>" : '')
                    . "<b class='subheading'>Provincial Registration Dates</b>"
                    . "<b>Registration Open</b>: " . $member['reg_start_dt_display'] . "<br>"
                    . "<b>Registration Close</b>: " . $member['reg_end_dt_display'] . "<br>"
                    . "<i>Late entries will be accepted until " . $member['reg_late_dt_display'] . "</i>"
                    . "";
            }
            $categories[$member['category']][] = $member;
        }
        for($i = 1; $i < 10; $i++) {
            if( isset($s["category-{$i}"]) && $s["category-{$i}"] != '' && isset($categories[$s["category-{$i}"]]) ) {  
                $blocks[] = array(
                    'type' => 'title',
                    'level' => 2,
                    'title' => $s["category-{$i}"],
                    );
                $blocks[] = array(
                    'type' => 'textcards',
                    'level' => 3,
                    'collapsible' => 'yes',
                    'collapsed' => 'yes',
                    'class' => 'musicfestival-members musicfestival-members-categories',
                    'items' => $categories[$s["category-{$i}"]],
                    );
            }
        }
    }



    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
