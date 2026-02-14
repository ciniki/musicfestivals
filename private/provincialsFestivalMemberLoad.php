<?php
//
// Description
// -----------
// This function loads the local festival and the provincials member profile
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsFestivalMemberLoad(&$ciniki, $tnid, $args) {

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
    // Load the festival
    //
    if( !isset($args['festival']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['festival']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1246', 'msg'=>'No festival specified'));
        }
        $festival = $rc['festival'];

        if( !isset($festival['provincial-festival-id']) 
            || $festival['provincial-festival-id'] == '' 
            || $festival['provincial-festival-id'] <= 0 
            ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1278', 'msg'=>'You are not configured for provincials'));
        }
    } else {
        $festival = $args['festival'];
    }

    //
    // Get the provincials member info
    //
    $strsql = "SELECT members.id, "
        . "members.tnid, "
        . "members.name, "
        . "members.flags, "
        . "provincials.reg_start_dt, "
        . "provincials.reg_end_dt, "
        . "provincials.latedays "
        . "FROM ciniki_musicfestivals_members AS members "
        . "INNER JOIN ciniki_musicfestival_members AS provincials ON ("
            . "members.id = provincials.member_id "
            . "AND provincials.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['provincial-festival-id']) . "' "
            . "AND members.tnid = provincials.tnid "
            . ") "
        . "WHERE members.member_tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'member');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1276', 'msg'=>'Unable to load member', 'err'=>$rc['err']));
    }
    if( !isset($rc['member']) ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1277', 'msg'=>'You are not currently set up'));
    }
    $member = $rc['member'];

    //
    // Check if registrations are open
    //
    if( $member['reg_start_dt'] != '' ) {
        $reg_start_dt = new DateTime($member['reg_start_dt'], new DateTimezone('UTC'));
    }
    $member['deadline'] = '';
    if( $member['reg_end_dt'] != '' ) {
        $reg_end_dt = new DateTime($member['reg_end_dt'], new DateTimezone('UTC'));
        $reg_final_dt = clone $reg_end_dt;
        if( $member['latedays'] > 0 ) {
            $reg_final_dt->add(new DateInterval('P' . $member['latedays'] . 'D'));
        }
    }
    $dt = new DateTime('now', new DateTimezone('UTC'));

    $member['reg_status'] = 'closed';
    if( isset($reg_start_dt) && isset($reg_final_dt) && $reg_start_dt <= $dt && $dt <= $reg_final_dt ) {
        $member['reg_status'] = 'open';
    } elseif( isset($reg_start_dt) && isset($reg_final_dt) && $dt <= $reg_final_dt ) {
        $member['reg_status'] = 'drafts';
    }

    if( isset($reg_end_dt) ) {
        $reg_end_dt->setTimezone(new DateTimezone($intl_timezone));
        $member['deadline'] = $reg_end_dt->format("l, F j, Y g:i A");
        $reg_final_dt->setTimezone(new DateTimezone($intl_timezone));
        $member['latedeadline'] = $reg_final_dt->format("l, F j, Y g:i A");
    }
    
    return array('stat'=>'ok', 'festival'=>$festival, 'member'=>$member);
}
?>
