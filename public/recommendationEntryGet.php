<?php
//
// Description
// ===========
// This method will return all the information about an adjudicator recommendation entry.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the adjudicator recommendation entry is attached to.
// entry_id:          The ID of the adjudicator recommendation entry to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_recommendationEntryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'entry_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Adjudicator Recommendation Entry'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
//        'member_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Member'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationEntryGet');
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
    // Return default for new Adjudicator Recommendation Entry
    //
    if( $args['entry_id'] == 0 ) {
        $entry = array('id'=>0,
            'status' => 10,
            'recommendation_id'=>'',
            'class_id'=>0,
            'position'=>'',
            'name'=>'',
            'mark'=>'',
            'notes'=>'',
        );
    }

    //
    // Get the details for an existing Adjudicator Recommendation Entry
    //
    else {
        $strsql = "SELECT entries.id, "
            . "entries.status, "
            . "entries.recommendation_id, "
            . "entries.class_id, "
            . "entries.position, "
            . "entries.name, "
            . "entries.mark, "
            . "entries.notes "
            . "FROM ciniki_musicfestival_recommendation_entries AS entries "
            . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND entries.id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'recommendation_id', 'class_id', 'position', 'name', 'mark', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.609', 'msg'=>'Adjudicator Recommendation Entry not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['entries'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.610', 'msg'=>'Unable to find Adjudicator Recommendation Entry'));
        }
        $entry = $rc['entries'][0];
    }

    $rsp = array('stat'=>'ok', 'entry'=>$entry);

    //
    // Get the list of submissions
    //
/*    $strsql = "SELECT recommendations.id, "
        . "CONCAT_WS(' - ', sections.name, recommendations.adjudicator_name) AS name "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "recommendations.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'recommendations', 'fname'=>'id', 
            'fields'=>array(
                'id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.688', 'msg'=>'Unable to load recommendations', 'err'=>$rc['err']));
    }
    $rsp['recommendations'] = isset($rc['recommendations']) ? $rc['recommendations'] : array(); */

    //
    // Get the list of classes for this section
    //
    $strsql = "SELECT classes.id , "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS name "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
//        . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY classes.code "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.722', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    $rsp['classes'] = isset($rc['classes']) ? $rc['classes'] : array();

    //
    // Get the list of recommendations for this class
    //
    if( $entry['class_id'] > 0 ) {
        //
        // Get the list of recommendations for this class
        //
        $strsql = "SELECT entries.id, "
            . "entries.status, "
            . "IF(entries.status >= 70, 600, entries.position) AS position, "
            . "entries.name, "
            . "entries.mark, "
            . "recommendations.id AS recommendation_id, "
            . "recommendations.member_id, "
            . "recommendations.section_id, "
            . "recommendations.date_submitted, "
            . "members.shortname AS member_name, "
            . "member.reg_end_dt AS end_date, "
            . "member.latedays "
            . "FROM ciniki_musicfestival_recommendation_entries AS entries "
            . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                . "entries.recommendation_id = recommendations.id "
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                . "recommendations.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_members AS member ON ("
                . "members.id = member.member_id "
                . "AND member.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND member.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE entries.class_id = '" . ciniki_core_dbQuote($ciniki, $entry['class_id']) . "' "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY recommendations.date_submitted, position "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'recommendation_id', 'position', 'name', 'mark',
                    'date_submitted', 'member_id', 'section_id', 'member_name', 'end_date', 'latedays'),
                'utctotz'=>array(
                    'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                    'end_date'=> array('timezone'=>$intl_timezone, 'format'=>'M j'),
                    ),
                'maps'=>array('position'=>$maps['recommendationentry']['position_shortname']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.656', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
        }
        $rsp['class_recommendations'] = isset($rc['entries']) ? $rc['entries'] : array();
    }

    //
    // Get the list of classes this name has been recommended for
    //
    if( $entry['name'] != '' ) {
        $strsql = "SELECT entries.id, "
            . "entries.recommendation_id, "
            . "entries.status, "
            . "entries.position, "
            . "entries.name, "
            . "entries.mark, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "recommendations.adjudicator_name, "
            . "recommendations.date_submitted, "
            . "members.shortname AS member_name "
            . "FROM ciniki_musicfestival_recommendation_entries AS entries "
            . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                . "entries.recommendation_id = recommendations.id "
                . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestivals_members AS members ON ("
                . "recommendations.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "entries.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ("
                . "entries.name like '" . ciniki_core_dbQuote($ciniki, $entry['name']) . "%' "
                . "OR entries.name like '% " . ciniki_core_dbQuote($ciniki, $entry['name']) . "%' "
                . ") "
            . "ORDER BY class_code, entries.position "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('id', 'recommendation_id', 'status', 'adjudicator_name', 
                    'class_code', 'class_name', 'position', 'name', 'mark', 'date_submitted', 'member_name',
                    ),
                'utctotz'=>array(
                    'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                    ),
                'maps'=>array('position'=>$maps['recommendationentry']['position_shortname']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.974', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
        }
        $rsp['name_recommendations'] = isset($rc['entries']) ? $rc['entries'] : array();
    }

    return $rsp;
}
?>
