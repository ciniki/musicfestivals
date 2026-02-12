<?php
//
// Description
// -----------
// This method will add a recommendation entry to a draft recommendation
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsRecommendationEntryAdd(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'local_reg_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
        'position'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Position'),
        'mark'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mark'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsRecommendationEntryAdd');
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

    if( !isset($member['reg_status']) || ($member['reg_status'] != 'open' && $member['reg_status'] != 'drafts') ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1328', 'msg'=>'Recommendation submissions are closed for your festival'));
    }

    //
    // Load the recommendation
    //
    $strsql = "SELECT recommendations.id, "
        . "recommendations.status, "
        . "recommendations.section_id "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'recommendation');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1294', 'msg'=>'Unable to load recommendation', 'err'=>$rc['err']));
    }
    if( !isset($rc['recommendation']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1295', 'msg'=>'Unable to find requested recommendation'));
    }
    $recommendation = $rc['recommendation'];

    //
    // Check status of recommendation
    //
    if( $recommendation['status'] > 10 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1296', 'msg'=>'Already submitted, no more recommendations can be added.'));
    }

    //
    // Check to make sure class/position has now already been added for this recommendation
    //
    $strsql = "SELECT entries.id "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
            . "entries.recommendation_id = recommendations.id "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE entries.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
        . "AND entries.position = '" . ciniki_core_dbQuote($ciniki, $args['position']) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1289', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1289', 'msg'=>'You already have recommendation for this class and position'));
    }

    //
    // Check to make sure local reg has not already been recommended
    //
    $strsql = "SELECT entries.id "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
            . "entries.recommendation_id = recommendations.id "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE entries.local_reg_id = '" . ciniki_core_dbQuote($ciniki, $args['local_reg_id']) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1289', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1289', 'msg'=>'This registration has already been recommendated'));
    }

    //
    // Load the registration information
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.display_name "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['local_reg_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1292', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1293', 'msg'=>'Unable to find requested registration'));
    }
    $registration = $rc['registration'];
    $args['name'] = $rc['registration']['display_name'];

    //
    // Add the entry
    //
    $args['status'] = 10;
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendationentry', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1291', 'msg'=>'Unable to add the recommendationentry', 'err'=>$rc['err']));
    }
    

    return array('stat'=>'ok');
}
?>
