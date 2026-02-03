<?php
//
// Description
// ===========
// This method will submit a draft recommendation to provincials.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the adjudicator recommendation is attached to.
// recommendation_id:          The ID of the adjudicator recommendation to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_provincialsRecommendationSubmit($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
        'adjudicator_phone'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Adjudicator Phone'),
        'adjudicator_email'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Adjudicator Email'),
        'acknowledgement'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Acknowledgement'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsRecommendationSubmit');
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

    if( !isset($member['reg_status']) || $member['reg_status'] != 'open' ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1329', 'msg'=>'Recommendation submissions are closed for your festival'));
    }

    $nplist = [];
    //
    // Load the recommendation
    //
    $strsql = "SELECT recommendations.id, "
        . "recommendations.festival_id, "
        . "IFNULL(sections.name, '') AS section_name, "
        . "recommendations.status, "
        . "recommendations.status AS status_text, "
        . "recommendations.section_id, "
        . "recommendations.adjudicator_name, "
        . "recommendations.adjudicator_phone, "
        . "recommendations.adjudicator_email, "
        . "recommendations.acknowledgement, "
        . "DATE_FORMAT(recommendations.date_submitted, '%b %d, %Y %l:%i %p') AS date_submitted, "
        . "recommendations.local_adjudicator_id "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "recommendations.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "AND recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'recommendations', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'status_text', 'section_id', 'section_name', 
                'adjudicator_name', 'adjudicator_phone', 'adjudicator_email', 'acknowledgement', 
                'date_submitted', 'local_adjudicator_id', 
                ),
            'maps'=>array('status_text'=>$maps['recommendation']['status']),
            'utctotz'=>array(
                'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.604', 'msg'=>'Submission not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['recommendations'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.605', 'msg'=>'Unable to find Submission'));
    }
    $recommendation = $rc['recommendations'][0];

    //
    // Check status is still in draft mode
    //
    if( $recommendation['status'] > 10 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1307', 'msg'=>'This has already been submitted'));
    }
    if( $args['acknowledgement'] != 'yes' ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1325', 'msg'=>'You must acknowledge the inforation is correct'));
    }

    $update_args = [];
    //
    // Check if phone or email different and update
    //
    if( isset($args['adjudicator_phone']) && $args['adjudicator_phone'] != $recommendation['adjudicator_phone'] ) {
        $update_args['adjudicator_phone'] = $args['adjudicator_phone'];
        $recommendation['adjudicator_phone'] = $args['adjudicator_phone'];
    }
    if( isset($args['adjudicator_email']) && $args['adjudicator_email'] != $recommendation['adjudicator_email'] ) {
        $update_args['adjudicator_email'] = $args['adjudicator_email'];
        $recommendation['adjudicator_email'] = $args['adjudicator_email'];
    }
    $update_args['status'] = 30;
    $update_args['acknowledgement'] = $args['acknowledgement'];
    $recommendation['acknowledgement'] = $args['acknowledgement'];

    $dt = new DateTime('now', new DateTimezone('UTC'));
    $update_args['date_submitted'] = $dt->format('Y-m-d H:i:s');
    $dt->setTimezone(new DateTimezone($intl_timezone));
    $recommendation['date_submitted'] = $dt->format('Y-m-d H:i:s');

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the recommendation
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendation', $args['recommendation_id'], $update_args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1313', 'msg'=>'Unable to submit the recommendations', 'err'=>$rc['err']));
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Email the submission
    //
    $recommendation['member_name'] = $member['name'];

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $provincials_tnid, 'ciniki', 'musicfestivals');

    return array('stat'=>'ok');
}
?>
