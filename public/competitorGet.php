<?php
//
// Description
// ===========
// This method will return all the information about an competitor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the competitor is attached to.
// competitor_id:          The ID of the competitor to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_competitorGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'competitor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Competitor'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitorGet');
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
    // Return default for new Competitor
    //
    if( $args['competitor_id'] == 0 ) {
        $competitor = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
            'public_name'=>'',
            'pronoun'=>'',
            'flags'=>'0',
            'parent'=>'',
            'address'=>'',
            'city'=>'',
            'province'=>'',
            'postal'=>'',
            'phone_home'=>'',
            'phone_cell'=>'',
            'email'=>'',
            'age'=>'',
            'study_level'=>'',
            'instrument'=>'',
            'notes'=>'',
        );
        $details = array();
    }

    //
    // Get the details for an existing Competitor
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_competitors.id, "
            . "ciniki_musicfestival_competitors.festival_id, "
            . "ciniki_musicfestival_competitors.name, "
            . "ciniki_musicfestival_competitors.public_name, "
            . "ciniki_musicfestival_competitors.pronoun, "
            . "ciniki_musicfestival_competitors.flags, "
            . "ciniki_musicfestival_competitors.parent, "
            . "ciniki_musicfestival_competitors.address, "
            . "ciniki_musicfestival_competitors.city, "
            . "ciniki_musicfestival_competitors.province, "
            . "ciniki_musicfestival_competitors.postal, "
            . "ciniki_musicfestival_competitors.phone_home, "
            . "ciniki_musicfestival_competitors.phone_cell, "
            . "ciniki_musicfestival_competitors.email, "
            . "ciniki_musicfestival_competitors.age AS _age, "
            . "ciniki_musicfestival_competitors.study_level, "
            . "ciniki_musicfestival_competitors.instrument, "
            . "ciniki_musicfestival_competitors.notes "
            . "FROM ciniki_musicfestival_competitors "
            . "WHERE ciniki_musicfestival_competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_competitors.id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'competitors', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'public_name', 'pronoun', 'flags',
                    'parent', 'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 
                    'email', '_age', 'study_level', 'instrument', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.76', 'msg'=>'Competitor not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['competitors'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.77', 'msg'=>'Unable to find Competitor'));
        }
        $competitor = $rc['competitors'][0];
        $competitor['age'] = $competitor['_age'];
        if( $competitor['public_name'] == '' ) {
            $competitor['public_name'] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $competitor['name']); 
        }
        $details = array();
        $details[] = array('label'=>'Name', 'value'=>$competitor['name']);
        if( $competitor['parent'] != '' ) { $details[] = array('label'=>'Parent', 'value'=>$competitor['parent']); }
        $address = '';
        if( $competitor['address'] != '' ) { $address .= $competitor['address']; }
        $city = $competitor['city'];
        if( $competitor['province'] != '' ) { $city .= ($city != '' ? ", " : '') . $competitor['province']; }
        if( $competitor['postal'] != '' ) { $city .= ($city != '' ? "  " : '') . $competitor['postal']; }
        if( $city != '' ) { $address .= ($address != '' ? "\n" : '' ) . $city; }
        if( $address != '' ) {
            $details[] = array('label'=>'Address', 'value'=>$address);
        }
        if( $competitor['phone_home'] != '' ) { $details[] = array('label'=>'Home', 'value'=>$competitor['phone_home']); }
        if( $competitor['phone_cell'] != '' ) { $details[] = array('label'=>'Cell', 'value'=>$competitor['phone_cell']); }
        if( $competitor['email'] != '' ) { $details[] = array('label'=>'Email', 'value'=>$competitor['email']); }
        if( $competitor['age'] != '' ) { $details[] = array('label'=>'Age', 'value'=>$competitor['age']); }
        if( $competitor['study_level'] != '' ) { $details[] = array('label'=>'Study/Level', 'value'=>$competitor['study_level']); }
        if( $competitor['instrument'] != '' ) { $details[] = array('label'=>'Instrument', 'value'=>$competitor['instrument']); }
        if( ($competitor['flags']&0x01) == 0x01 ) { $details[] = array('label'=>'Waiver', 'value'=>'Signed'); }
    }

    return array('stat'=>'ok', 'competitor'=>$competitor, 'details'=>$details);
}
?>
