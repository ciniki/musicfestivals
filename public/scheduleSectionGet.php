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
// tnid:         The ID of the tenant the schedule section is attached to.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'schedulesection_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Section'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleSectionGet');
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
    // Return default for new Schedule Section
    //
    if( $args['schedulesection_id'] == 0 ) {
        $strsql = "SELECT MAX(sequence) AS seq "
            . "FROM ciniki_musicfestival_schedule_sections "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'seq');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.680', 'msg'=>'Unable to load seq', 'err'=>$rc['err']));
        }
        $seq = isset($rc['seq']['seq']) ? ($rc['seq']['seq']+1) : 1;

        $schedulesection = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
            'sequence'=>$seq,
            'adjudicator1_id'=>0,
            'flags'=>0,
            'top_sponsors_title'=>'',
            'top_sponsor_ids'=>0,
            'bottom_sponsors_title'=>'',
            'bottom_sponsor_ids'=>0,
            'provincials_title'=>'',
            'provincials_content'=>'',
            'provincials_image_id'=>0,
        );
    }

    //
    // Get the details for an existing Schedule Section
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_schedule_sections.id, "
            . "ciniki_musicfestival_schedule_sections.festival_id, "
            . "ciniki_musicfestival_schedule_sections.name, "
            . "ciniki_musicfestival_schedule_sections.sequence, "
            . "ciniki_musicfestival_schedule_sections.adjudicator1_id, "
            . "ciniki_musicfestival_schedule_sections.flags, "
            . "ciniki_musicfestival_schedule_sections.sponsor_settings, "
            . "ciniki_musicfestival_schedule_sections.provincial_settings "
            . "FROM ciniki_musicfestival_schedule_sections "
            . "WHERE ciniki_musicfestival_schedule_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_schedule_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'schedulesections', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'sequence', 'adjudicator1_id', 'flags',
                    'sponsor_settings', 'provincial_settings',
                    ),
                'unserialize'=>array('sponsor_settings', 'provincial_settings'),
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

    $rsp = array('stat'=>'ok', 'schedulesection'=>$schedulesection);

    //
    // Get the list of adjudicators
    //
    $strsql = "SELECT ciniki_musicfestival_adjudicators.id, "
        . "ciniki_musicfestival_adjudicators.customer_id, "
        . "ciniki_customers.display_name "
        . "FROM ciniki_musicfestival_adjudicators "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_musicfestival_adjudicators.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_musicfestival_adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'customer_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['adjudicators'] = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Get the list of adjudicators
    //
    $strsql = "SELECT ciniki_musicfestival_sponsors.id, "
        . "ciniki_musicfestival_sponsors.name "
        . "FROM ciniki_musicfestival_sponsors "
        . "WHERE ciniki_musicfestival_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sponsors', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['sponsors'] = isset($rc['sponsors']) ? $rc['sponsors'] : array();
//    array_unshift($rsp['sponsors'], array('id'=>0, 'name'=>'None'));

    return $rsp;
}
?>
