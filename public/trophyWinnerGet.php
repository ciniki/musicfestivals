<?php
//
// Description
// ===========
// This method will return all the information about an trophy winner.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the trophy winner is attached to.
// winner_id:          The ID of the trophy winner to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophyWinnerGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'winner_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Trophy Winner'),
        'registration_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophyWinnerGet');
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
    // Return default for new Trophy Winner
    //
    if( $args['winner_id'] == 0 ) {
        $winner = [
            'id'=>0,
            'trophy_id' => '',
            'registration_id' => 0,
            'flags' => 0,
            'name' => '',
            'registration' => '',
            'year' => '',
            'internal_notes' =>'',
            ];
        if( isset($args['registration_id']) && $args['registration_id'] > 0 ) {
            $winner['registration_id'] = $args['registration_id'];
        }
    }

    //
    // Get the details for an existing Trophy Winner
    //
    else {
        $strsql = "SELECT id, "
            . "trophy_id, "
            . "registration_id, "
            . "flags, "
            . "awarded_amount, "
            . "name, "
            . "year, "
            . "internal_notes "
            . "FROM ciniki_musicfestival_trophy_winners "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('id', 'trophy_id', 'registration_id', 'flags', 'awarded_amount', 'name', 'year', 'internal_notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.408', 'msg'=>'Trophy Winner not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['winners'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.409', 'msg'=>'Unable to find Trophy Winner'));
        }
        $winner = $rc['winners'][0];
        if( $winner['awarded_amount'] > 0 ) {
            $winner['awarded_amount'] = '$' . number_format($winner['awarded_amount'], 2);
        } else {
            $winner['awarded_amount'] = '';
        }
    }

    if( $winner['registration_id'] > 0 ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.private_name, " 
            . "sections.name AS section_name, "
            . "categories.name AS category_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "DATE_FORMAT(festivals.end_date, '%Y') AS year "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestivals AS festivals ON ("
                . "registrations.festival_id = festivals.id "
                . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $winner['registration_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1123', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( !isset($rc['registration']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1124', 'msg'=>'Unable to find requested registration'));
        }
        $registration = $rc['registration'];
        $winner['registration_id'] = $registration['id'];
        $winner['registration_name'] = $registration['private_name'];
        $winner['section'] = $registration['section_name'];
        $winner['category'] = $registration['category_name'];
        $winner['classname'] = $registration['class_code'] . ' - ' . $registration['class_name'];
        if( $winner['year'] == '' ) {
            $winner['year'] = $registration['year'];
        }

        // 
        // Check flags9 on festival to see if section/name should 
        //
    }

    $rsp = array('stat'=>'ok', 'winner'=>$winner);

    //
    // Get the list of trophies to pick from
    //
    $strsql = "SELECT trophies.id, "
        . "CONCAT_WS(' - ', categories.name, subcategories.name, trophies.name) AS name "
        . "FROM ciniki_musicfestival_trophy_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_trophy_subcategories AS subcategories ON ("
            . "categories.id = subcategories.category_id "
            . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_trophies AS trophies ON ("
            . "subcategories.id = trophies.subcategory_id "
            . "AND trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, trophies.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'trophies', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1125', 'msg'=>'Unable to load trophies', 'err'=>$rc['err']));
    }
    $rsp['trophies'] = isset($rc['trophies']) ? $rc['trophies'] : array();
    array_unshift($rsp['trophies'], ['id' => 0, 'name' => 'Select a Trophy/Award/Scholarship']);

    return $rsp;
}
?>
