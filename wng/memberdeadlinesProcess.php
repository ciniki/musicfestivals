<?php
//
// Description
// -----------
// This function will display the member deadlines for a festival
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_memberdeadlinesProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.669', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) || !isset($section['settings']['festival-id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.670', 'msg'=>"No festival specified"));
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
    // Get the members and their deadlines
    //
    $strsql = "SELECT members.id, "
        . "members.name, "
        . "members.category, "
        . "members.status, "
        . "members.status AS status_text, "
        . "IFNULL(fmembers.reg_start_dt, '') AS reg_start_dt_display, "
        . "IFNULL(fmembers.reg_end_dt, '') AS reg_end_dt_display, "
        . "IFNULL(DATE_ADD(fmembers.reg_end_dt, INTERVAL fmembers.latedays DAY), '') AS reg_late_dt_display, "
        . "IFNULL(fmembers.latedays, '') AS latedays "
        . "FROM ciniki_musicfestivals_members AS members "
        . "LEFT JOIN ciniki_musicfestival_members AS fmembers ON ("
            . "members.id = fmembers.member_id "
            . "AND fmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND fmembers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE members.status = 10 " // Active
        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY members.id "
        . "ORDER BY fmembers.reg_end_dt, members.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'category', 'status', 
                'reg_start_dt_display', 'reg_end_dt_display', 'reg_late_dt_display', 'latedays',
                'num_registrations',
                ),
            'utctotz'=>array(
                'reg_start_dt_display' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i A'),
                'reg_end_dt_display' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i A'),
                'reg_late_dt_display' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i A'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.581', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
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

    foreach($members as $mid => $member) {
        if( $member['reg_start_dt_display'] == '' ) {
            $members[$mid]['reg_start_dt_display'] = 'TBD';
        }
        if( $member['reg_end_dt_display'] == '' ) {
            $members[$mid]['reg_end_dt_display'] = 'TBD';
        }
        if( $member['reg_late_dt_display'] == '' ) {
            $members[$mid]['reg_late_dt_display'] = 'TBD';
        }
    }

    //
    // Processing
    //
    $blocks[] = array(
        'type' => 'table',
        'section' => 'entry-deadlines',
        'headers' => 'yes',
        'class' => 'fold-at-50 musicfestival-entry-deadlines',
        'columns' => array(
            array('label'=>'Festival', 'fold-label'=>'', 'field'=>'name', 'class'=>''),
            array('label'=>'Registration Open', 'fold-label'=>'Registration Open:', 'field'=>'reg_start_dt_display', 'class'=>''),
            array('label'=>'Registration Close', 'fold-label'=>'Registration Close:', 'field'=>'reg_end_dt_display', 'class'=>''),
            array('label'=>'Late Entries', 'fold-label'=>'Late Registration Until:', 'field'=>'reg_late_dt_display', 'class'=>''),
            ),
        'rows' => $members,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
