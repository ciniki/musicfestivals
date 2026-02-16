<?php
//
// Description
// -----------
// This method searchs for a Registrations for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_recommendationLocalRegistrationSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationLocalRegistrationSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load conference maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the local festival details
    //
    $strsql = "SELECT recommendations.id, "
        . "members.member_tnid, "
        . "adjudicators.festival_id "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
/*        . "INNER JOIN ciniki_musicfestival_members AS fm ON ("
            . "recommendations.member_id = fm.member_id "
            . "AND fm.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND fm.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") " */
        . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
            . "recommendations.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
            . "recommendations.local_adjudicator_id = adjudicators.id "
            . "AND members.member_tnid = adjudicators.tnid "
            . ") "
        . "LEFT JOIN ciniki_musicfestivals AS festivals ON ("
            . "adjudicators.festival_id = festivals.id "
            . "AND members.member_tnid = festivals.tnid "
            . ") "
        . "WHERE recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'recommendation');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1469', 'msg'=>'Unable to load recommendation', 'err'=>$rc['err']));
    }
    if( !isset($rc['recommendation']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1470', 'msg'=>'Unable to find requested recommendation'));
    }
    $recommendation = $rc['recommendation'];

    //
    // Search the local festivals registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.private_name AS name, "
        . "registrations.mark AS mark, "
        . "registrations.title1, "
        . "registrations.movements1, "
        . "registrations.composer1, "
        . "registrations.title2, "
        . "registrations.movements2, "
        . "registrations.composer2, "
        . "registrations.title3, "
        . "registrations.movements3, "
        . "registrations.composer3, "
        . "registrations.title4, "
        . "registrations.movements4, "
        . "registrations.composer4, "
        . "registrations.title5, "
        . "registrations.movements5, "
        . "registrations.composer5, "
        . "registrations.title6, "
        . "registrations.movements6, "
        . "registrations.composer6, "
        . "registrations.title7, "
        . "registrations.movements7, "
        . "registrations.composer7, "
        . "registrations.title8, "
        . "registrations.movements8, "
        . "registrations.composer8 "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $recommendation['member_tnid']) . "' "
        . "AND ("
            . "registrations.private_name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR registrations.private_name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'mark', 'title1', 'movements1', 'composer1', 'title2', 'movements2', 'composer2', 
                'title3', 'movements3', 'composer3', 'title4', 'movements4', 'composer4', 'title5', 
                'movements5', 'composer5', 'title6', 'movements6', 'composer6', 'title7', 'movements7', 
                'composer7', 'title8', 'movements8', 'composer8'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1471', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    $results = [];
    foreach($registrations as $reg) {
        $result = [
            'id' => $reg['id'],
            'name' => $reg['name'],
            'private_name' => $reg['name'],
            'local_reg_details' => [],
            ];
        $result['local_reg_details'] = [
            ['label'=>'Participant', 'value'=>$reg['name']],
            ];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
        for($i = 1; $i <= 8; $i++) {
            $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $reg, $i);
            if( isset($rc['title']) && $rc['title'] != '' ) {
                $result['local_reg_details'][] = [
                    'label' => "Title {$i}",
                    'value' => $rc['title'],
                    ];
                if( $i == 1 ) {
                    $result['name'] .= ' - ' . $rc['title'];
                }
            }
        }
        if( $reg['mark'] != '' ) {
            $result['name'] .= ' - ' . $reg['mark'];
        }

        $results[] = $result;
    }
        error_log(print_r($results,true));

    return array('stat'=>'ok', 'registrations'=>$results);
}
?>
