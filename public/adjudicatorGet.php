<?php
//
// Description
// ===========
// This method will return all the information about an adjudicator.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the adjudicator is attached to.
// adjudicator_id:          The ID of the adjudicator to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_adjudicatorGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'adjudicator_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Adjudicator'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.adjudicatorGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Adjudicator
    //
    if( $args['adjudicator_id'] == 0 ) {
        $adjudicator = array('id'=>0,
            'customer_id'=>(isset($args['customer_id']) ? $args['customer_id'] : 0),
            'festival_id'=>(isset($args['festival_id']) ? $args['festival_id'] : 0),
        );
    }

    //
    // Get the details for an existing Adjudicator
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_adjudicators.id, "
            . "ciniki_musicfestival_adjudicators.festival_id, "
            . "ciniki_musicfestival_adjudicators.customer_id "
            . "FROM ciniki_musicfestival_adjudicators "
            . "WHERE ciniki_musicfestival_adjudicators.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_musicfestival_adjudicators.id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('festival_id', 'customer_id'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.15', 'msg'=>'Adjudicator not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['adjudicators'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.16', 'msg'=>'Unable to find Adjudicator'));
        }
        $adjudicator = $rc['adjudicators'][0];
    }

    //
    // If the customer is specified, load the details
    //
    if( isset($adjudicator['customer_id']) && $adjudicator['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], 
            array('customer_id'=>$adjudicator['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $adjudicator['customer'] = $rc['customer'];
        $adjudicator['customer_details'] = $rc['details'];
    } else {
        $adjudicator['customer'] = array();
        $adjudicator['customer_details'] = array();
    }

    return array('stat'=>'ok', 'adjudicator'=>$adjudicator);
}
?>
