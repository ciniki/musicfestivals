<?php
//
// Description
// ===========
// This method will return all the information about an accolade.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the accolade is attached to.
// accolade_id:          The ID of the accolade to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_accoladeGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'accolade_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Accolade'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeGet');
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
    // Return default for new Accolade
    //
    if( $args['accolade_id'] == 0 ) {
        $accolade = array('id'=>0,
            'name' => '',
            'subcategory_id' => 0,
            'flags' => 1,
            'primary_image_id' => '0',
            'donated_by' => '',
            'first_presented' => '',
            'criteria' => '',
            'amount' => '',
            'description' => '',
            'donor_thankyou_info' => '',
            'winners' => array(),
        );
    }

    //
    // Get the details for an existing Accolade
    //
    else {
        $strsql = "SELECT accolades.id, "
            . "accolades.name, "
            . "accolades.subcategory_id, "
            . "accolades.flags, "
            . "accolades.primary_image_id, "
            . "accolades.donated_by, "
            . "accolades.first_presented, "
            . "accolades.criteria, "
            . "accolades.amount, "
            . "accolades.description, "
            . "accolades.donor_thankyou_info "
            . "FROM ciniki_musicfestival_accolades AS accolades "
            . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND accolades.id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accolades', 'fname'=>'id', 
                'fields'=>array('name', 'subcategory_id', 'flags', 'primary_image_id', 
                    'donated_by', 'first_presented', 'criteria', 'amount', 'description', 'donor_thankyou_info',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.403', 'msg'=>'Accolade not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['accolades'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.404', 'msg'=>'Unable to find Accolade'));
        }
        $accolade = $rc['accolades'][0];

        //
        // Get the list of winners
        //
        $strsql = "SELECT winners.id, "
            . "winners.accolade_id, "
            . "winners.registration_id, "
            . "IF(winners.registration_id > 0, registrations.display_name, winners.name) AS name, "
            . "winners.awarded_amount, "
            . "winners.year "
            . "FROM ciniki_musicfestival_accolade_winners AS winners "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "winners.registration_id = registrations.id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE winners.accolade_id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_id']) . "' "
            . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY winners.year DESC, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('id', 'accolade_id', 'registration_id', 'awarded_amount', 'name', 'year')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $accolade['winners'] = isset($rc['winners']) ? $rc['winners'] : array();
        $accolade['winner_ids'] = array();
        foreach($accolade['winners'] as $iid => $winner) {
            $accolade['winner_ids'][] = $winner['id'];
        }
    }

    $rsp = array('stat'=>'ok', 'accolade'=>$accolade);

    //
    // Get the list of available subcategories
    //
    $strsql = "SELECT subcategories.id, "
        . "CONCAT_WS(' - ', categories.name, subcategories.name) AS name "
        . "FROM ciniki_musicfestival_accolade_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
            . "categories.id = subcategories.category_id "
            . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'subcategories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1101', 'msg'=>'Unable to load subcategories', 'err'=>$rc['err']));
    }
    $rsp['subcategories'] = isset($rc['subcategories']) ? $rc['subcategories'] : array();

    return $rsp;
}
?>
