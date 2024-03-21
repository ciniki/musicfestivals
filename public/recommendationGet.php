<?php
//
// Description
// ===========
// This method will return all the information about an adjudicator recommendation.
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
function ciniki_musicfestivals_recommendationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Adjudicator Recommendation'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationGet');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Adjudicator Recommendation
    //
    if( $args['recommendation_id'] == 0 ) {
        $recommendation = array('id'=>0,
            'festival_id'=>'',
            'member_id'=>'',
            'section_id'=>'',
            'adjudicator_name'=>'',
            'adjudicator_phone'=>'',
            'adjudicator_email'=>'',
            'acknowledgement'=>'',
            'date_submitted'=>'',
        );
    }

    //
    // Get the details for an existing Adjudicator Recommendation
    //
    else {
        $strsql = "SELECT recommendations.id, "
            . "recommendations.festival_id, "
            . "recommendations.member_id, "
            . "IFNULL(members.name, '') AS member_name, "
            . "IFNULL(sections.name, '') AS section_name, "
            . "recommendations.section_id, "
            . "recommendations.adjudicator_name, "
            . "recommendations.adjudicator_phone, "
            . "recommendations.adjudicator_email, "
            . "recommendations.acknowledgement, "
            . "recommendations.date_submitted "
            . "FROM ciniki_musicfestival_recommendations AS recommendations "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "recommendations.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                . "recommendations.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'recommendations', 'fname'=>'id', 
                'fields'=>array('id', 'member_id', 'member_name', 'section_id', 'section_name', 
                    'adjudicator_name', 'adjudicator_phone', 'adjudicator_email', 'acknowledgement', 'date_submitted',
                    ),
                'utctotz'=>array(
                    'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.604', 'msg'=>'Adjudicator Recommendation not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['recommendations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.605', 'msg'=>'Unable to find Adjudicator Recommendation'));
        }
        $recommendation = $rc['recommendations'][0];
        $recommendation['details'] = array(
            array('label' => 'Member', 'value'=>$recommendation['member_name']),
            array('label' => 'Section', 'value'=>$recommendation['section_name']),
            array('label' => 'Acknowledgement', 'value'=>$recommendation['acknowledgement']),
            array('label' => 'Submitted', 'value'=>$recommendation['date_submitted']),
            );

        //
        // Get the entries
        //
        $strsql = "SELECT entries.id, "
            . "entries.status, "
            . "entries.position, "
            . "entries.name, "
            . "entries.mark, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name "
            . "FROM ciniki_musicfestival_recommendation_entries AS entries "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "entries.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY class_code, entries.position "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'class_code', 'class_name', 'position', 'name', 'mark'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.598', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
        }
        $recommendation['entries'] = isset($rc['entries']) ? $rc['entries'] : array();
        foreach($recommendation['entries'] as $eid => $entry) {
            $recommendation['entries'][$eid]['class_name'] = str_replace($recommendation['section_name'] . ' - ', '', $recommendation['entries'][$eid]['class_name']);
            if( preg_match("/^([^-]+) - /", $recommendation['section_name'], $m) ) {
                if( $m[1] != '' ) {
                    $recommendation['entries'][$eid]['name'] = str_replace($m[1] . ' - ', '', $recommendation['entries'][$eid]['name']);
                }
            }
            switch($entry['position']) {
                case 1: $recommendation['entries'][$eid]['position'] = '1st Recommendation'; break;
                case 2: $recommendation['entries'][$eid]['position'] = '2nd Recommendation'; break;
                case 3: $recommendation['entries'][$eid]['position'] = '3rd Recommendation'; break;
                case 101: $recommendation['entries'][$eid]['position'] = '1st Alternate'; break;
                case 102: $recommendation['entries'][$eid]['position'] = '2nd Alternate'; break;
                case 103: $recommendation['entries'][$eid]['position'] = '3rd Alternate'; break;
            }
        }
    }

    return array('stat'=>'ok', 'recommendation'=>$recommendation);
}
?>
