<?php
//
// Description
// ===========
// This method will return all the information about an trophy.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the trophy is attached to.
// trophy_id:          The ID of the trophy to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophyGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'trophy_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Trophy'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophyGet');
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
    // Return default for new Trophy
    //
    if( $args['trophy_id'] == 0 ) {
        $trophy = array('id'=>0,
            'name' => '',
            'typename' => (isset($args['typename']) ? $args['typename'] : 'Trophies'),
            'category' => '',
            'primary_image_id' => '0',
            'donated_by' => '',
            'first_presented' => '',
            'criteria' => '',
            'description' => '',
            'amount' => '',
            'winners' => array(),
        );
    }

    //
    // Get the details for an existing Trophy
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_trophies.id, "
            . "ciniki_musicfestival_trophies.name, "
            . "ciniki_musicfestival_trophies.typename, "
            . "ciniki_musicfestival_trophies.category, "
            . "ciniki_musicfestival_trophies.primary_image_id, "
            . "ciniki_musicfestival_trophies.donated_by, "
            . "ciniki_musicfestival_trophies.first_presented, "
            . "ciniki_musicfestival_trophies.criteria, "
            . "ciniki_musicfestival_trophies.amount, "
            . "ciniki_musicfestival_trophies.description "
            . "FROM ciniki_musicfestival_trophies "
            . "WHERE ciniki_musicfestival_trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_trophies.id = '" . ciniki_core_dbQuote($ciniki, $args['trophy_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'trophies', 'fname'=>'id', 
                'fields'=>array('name', 'typename', 'category', 
                    'primary_image_id', 'donated_by', 'first_presented', 'criteria', 'amount', 'description',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.403', 'msg'=>'Trophy not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['trophies'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.404', 'msg'=>'Unable to find Trophy'));
        }
        $trophy = $rc['trophies'][0];

        //
        // Get the list of winners
        //
        $strsql = "SELECT ciniki_musicfestival_trophy_winners.id, "
            . "ciniki_musicfestival_trophy_winners.trophy_id, "
            . "ciniki_musicfestival_trophy_winners.name, "
            . "ciniki_musicfestival_trophy_winners.year "
            . "FROM ciniki_musicfestival_trophy_winners "
            . "WHERE trophy_id = '" . ciniki_core_dbQuote($ciniki, $args['trophy_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY year DESC, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('id', 'trophy_id', 'name', 'year')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $trophy['winners'] = isset($rc['winners']) ? $rc['winners'] : array();
        $trophy['winner_ids'] = array();
        foreach($trophy['winners'] as $iid => $winner) {
            $trophy['winner_ids'][] = $winner['id'];
        }
    }

    return array('stat'=>'ok', 'trophy'=>$trophy);
}
?>
