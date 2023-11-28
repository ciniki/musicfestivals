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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Adjudicator Recommendation Entry
    //
    if( $args['entry_id'] == 0 ) {
        $entry = array('id'=>0,
            'recommendation_id'=>'',
            'class_id'=>'',
            'position'=>'',
            'name'=>'',
            'mark'=>'',
        );
    }

    //
    // Get the details for an existing Adjudicator Recommendation Entry
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_recommendation_entries.id, "
            . "ciniki_musicfestival_recommendation_entries.recommendation_id, "
            . "ciniki_musicfestival_recommendation_entries.class_id, "
            . "ciniki_musicfestival_recommendation_entries.position, "
            . "ciniki_musicfestival_recommendation_entries.name, "
            . "ciniki_musicfestival_recommendation_entries.mark "
            . "FROM ciniki_musicfestival_recommendation_entries "
            . "WHERE ciniki_musicfestival_recommendation_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_recommendation_entries.id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('recommendation_id', 'class_id', 'position', 'name', 'mark'),
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

    return array('stat'=>'ok', 'entry'=>$entry);
}
?>
