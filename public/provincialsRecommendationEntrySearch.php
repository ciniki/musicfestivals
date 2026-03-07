<?php
//
// Description
// -----------
// This method will search entries for a name
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsRecommendationEntrySearch(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsRecommendationEntrySearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    $date_format = 'D, M j, Y';

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $provincials_tnid, $provincials_festival_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $provincials_maps = $rc['maps'];

    //
    // Search the entries
    //
    $strsql = "SELECT entries.id, "
        . "entries.recommendation_id, "
        . "entries.status, "
        . "entries.status AS status_text, "
        . "entries.name, "
        . "entries.position, "
        . "entries.position AS position_text, "
        . "entries.mark, "
        . "entries.provincials_reg_id, "
        . "entries.local_reg_id, "
        . "entries.dt_invite_sent, "
        . "recommendations.date_submitted, "
        . "sections.name AS section_name, "
        . "categories.name AS category_name, "
        . "classes.name AS class_name, "
        . "IFNULL(registrations.status, '') AS reg_status_text "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . "AND ("
                . "entries.name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR entries.name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "entries.provincials_reg_id = registrations.id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
        . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "ORDER BY recommendations.date_submitted DESC, sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name, entries.position "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'recommendation_id', 'status', 'status_text', 'name', 'position', 'position_text', 'mark', 
                'provincials_reg_id', 'local_reg_id', 'date_submitted', 'date_invited'=>'dt_invite_sent',
                'section_name', 'category_name', 'class_name', 'reg_status_text',
                ),
            'utctotz'=>array(
                'date_invited' => array('timezone'=>$intl_timezone, 'format'=>'M j - g:i A'),
                ),
            'maps'=>array(
                'status_text'=>$maps['recommendationentry']['status'],
                'position_text'=>$maps['recommendationentry']['position'],
                'reg_status_text'=>$provincials_maps['registration']['status'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1281', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
    }
    $entries = isset($rc['entries']) ? $rc['entries'] : array();
    foreach($entries as $eid => $entry) {
        if( $entry['position'] > 100 && $entry['position'] < 600 ) {
            $entries[$eid]['status_text'] .= ' - Alternate';
        }
    }

    return array('stat'=>'ok', 'entries'=>$entries);
}
?>
