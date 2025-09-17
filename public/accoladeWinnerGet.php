<?php
//
// Description
// ===========
// This method will return all the information about an accolade winner.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the accolade winner is attached to.
// winner_id:          The ID of the accolade winner to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_accoladeWinnerGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'winner_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Accolade Winner'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeWinnerGet');
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
    // Return default for new Accolade Winner
    //
    if( $args['winner_id'] == 0 ) {
        $winner = [
            'id'=>0,
            'accolade_id' => '',
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
    // Get the details for an existing Accolade Winner
    //
    else {
        $strsql = "SELECT id, "
            . "accolade_id, "
            . "registration_id, "
            . "flags, "
            . "awarded_amount, "
            . "name, "
            . "year, "
            . "internal_notes "
            . "FROM ciniki_musicfestival_accolade_winners "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('id', 'accolade_id', 'registration_id', 'flags', 'awarded_amount', 'name', 'year', 'internal_notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.408', 'msg'=>'Accolade Winner not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['winners'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.409', 'msg'=>'Unable to find Accolade Winner'));
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
    // Get the list of accolades to pick from
    //
    $strsql = "SELECT accolades.id, "
        . "CONCAT_WS(' - ', categories.name, subcategories.name, accolades.name) AS name "
        . "FROM ciniki_musicfestival_accolade_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
            . "categories.id = subcategories.category_id "
            . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_accolades AS accolades ON ("
            . "subcategories.id = accolades.subcategory_id "
            . "AND accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, accolades.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'accolades', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1125', 'msg'=>'Unable to load accolades', 'err'=>$rc['err']));
    }
    $rsp['accolades'] = isset($rc['accolades']) ? $rc['accolades'] : array();
    array_unshift($rsp['accolades'], ['id' => 0, 'name' => 'Select a Accolade/Award/Scholarship']);

    return $rsp;
}
?>
