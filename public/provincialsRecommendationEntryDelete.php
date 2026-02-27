<?php
//
// Description
// ===========
// This method will return an entry as part of a adjudicator recommendation
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
function ciniki_musicfestivals_provincialsRecommendationEntryDelete($ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
        'entry_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsRecommendationEntryDelete');
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
    // Load the entry
    //
    $strsql = "SELECT entries.id, "
        . "entries.uuid, "
        . "entries.status, "
        . "entries.position, "
        . "entries.name, "
        . "entries.mark, "
        . "entries.notes, "
        . "entries.dt_invite_sent, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "recommendations.id AS recommendation_id, "
        . "recommendations.status AS recommendation_status, "
        . "localreg.id AS registration_id, "
        . "localreg.display_name AS local_display_name, "
        . "localclasses.code AS local_class_code, "
        . "localclasses.name AS local_class_name, "
        . "localcategories.name AS local_category_name, "
        . "localsections.name AS local_section_name "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
            . "entries.recommendation_id = recommendations.id "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ( "
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS localreg ON ("
            . "entries.local_reg_id = localreg.id "
            . "AND localreg.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS localclasses ON ("
            . "localreg.class_id = localclasses.id "
            . "AND localclasses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS localcategories ON ("
            . "localclasses.category_id = localcategories.id "
            . "AND localcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS localsections ON ("
            . "localcategories.section_id = localsections.id "
            . "AND localsections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "AND entries.id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
        . "AND entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1477', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
    }
    if( !isset($rc['entry']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1478', 'msg'=>'Unable to find requested entry'));
    }
    $entry = $rc['entry'];
    
    if( $entry['recommendation_status'] != 10 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1488', 'msg'=>'Unable to delete'));
    }
    if( $entry['status'] != 10 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1488', 'msg'=>'Unable to delete'));
    }

    //
    // Remove the recommendation
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendationentry',
        $args['entry_id'], $entry['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
