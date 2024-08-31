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
        $winner = array('id'=>0,
            'trophy_id'=>'',
            'name'=>'',
            'year'=>'',
        );
    }

    //
    // Get the details for an existing Trophy Winner
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_trophy_winners.id, "
            . "ciniki_musicfestival_trophy_winners.trophy_id, "
            . "ciniki_musicfestival_trophy_winners.name, "
            . "ciniki_musicfestival_trophy_winners.year "
            . "FROM ciniki_musicfestival_trophy_winners "
            . "WHERE ciniki_musicfestival_trophy_winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_trophy_winners.id = '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('trophy_id', 'name', 'year'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.408', 'msg'=>'Trophy Winner not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['winners'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.409', 'msg'=>'Unable to find Trophy Winner'));
        }
        $winner = $rc['winners'][0];
    }

    return array('stat'=>'ok', 'winner'=>$winner);
}
?>
