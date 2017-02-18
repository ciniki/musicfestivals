<?php
//
// Description
// ===========
// This method will return all the information about an schedule section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the schedule section is attached to.
// schedulesection_id:          The ID of the schedule section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleSectionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'schedulesection_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Section'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.scheduleSectionGet');
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
    // Return default for new Schedule Section
    //
    if( $args['schedulesection_id'] == 0 ) {
        $schedulesection = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Section
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_schedule_sections.id, "
            . "ciniki_musicfestival_schedule_sections.festival_id, "
            . "ciniki_musicfestival_schedule_sections.name "
            . "FROM ciniki_musicfestival_schedule_sections "
            . "WHERE ciniki_musicfestival_schedule_sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_musicfestival_schedule_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'schedulesections', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.88', 'msg'=>'Schedule Section not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['schedulesections'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.89', 'msg'=>'Unable to find Schedule Section'));
        }
        $schedulesection = $rc['schedulesections'][0];
    }

    return array('stat'=>'ok', 'schedulesection'=>$schedulesection);
}
?>
