<?php
//
// Description
// ===========
// This method will send the registration instructions to provincials to accepted entries
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the adjudicator recommendation is attached to.
// recommendation_id:          The ID of the adjudicator recommendation to get the details for.
// // Returns // -------
//
function ciniki_musicfestivals_provincialsRegInvitesSend(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'recommendation_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Submissions'),
        'entry_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommendation'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsRegInvitesSend');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the load festival and provincials festival info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsFestivalMemberLoad');
    $rc = ciniki_musicfestivals_provincialsFestivalMemberLoad($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $rc['member'];
    $provincials_tnid = $member['tnid'];

    //
    // Make sure registrations are still open
    //
    if( !isset($member['reg_status']) || $member['reg_status'] != 'open' ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1342', 'msg'=>'Recommendation submissions are closed for your festival'));
    }

    //
    // Check if instruction message configured
    //
    if( !isset($festival['provincials-email-register-subject']) || $festival['provincials-email-register-subject'] == '' 
        || !isset($festival['provincials-email-register-message']) || $festival['provincials-email-register-message'] == '' 
        ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1343', 'msg'=>'No template configured'));
    }

    $recommendation_sql = '';
    if( isset($args['recommendation_id']) && $args['recommendation_id'] > 0 ) {
        $recommendation_sql = "AND recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' ";
    }

    //
    // Get the list of entries to send the invite to
    //
    $strsql = "SELECT entries.id, " 
        . "entries.uuid, "
        . "entries.status, "
        . "entries.local_reg_id, "
        . "classes.feeflags AS feeflags, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
            . "entries.recommendation_id = recommendations.id "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . $recommendation_sql
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "AND entries.status = 40 " // Accepted entries
        . "";
    if( isset($args['entry_id']) && $args['entry_id'] > 0 ) {
        $strsql .= "AND entries.id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array(
                'id', 'uuid', 'status', 'local_reg_id', 'class_code', 'class_name', 'feeflags'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1344', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
    }
    $entries = isset($rc['entries']) ? $rc['entries'] : array();

    //
    // Go through the entries and send invite email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsRegInviteSend');
    foreach($entries as $entry) {
        $rc = ciniki_musicfestivals_provincialsRegInviteSend($ciniki, $args['tnid'], [
            'festival' => $festival,
            'member' => $member,
            'entry' => $entry,
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
