<?php
//
// Description
// ===========
// This method will return all the information about an scrutiner.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the scrutiner is attached to.
// scrutineer_id:          The ID of the scrutiner to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scrutineerGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'scrutineer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Scrutiner'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scrutineerGet');
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
    // Return default for new Scrutiner
    //
    if( $args['scrutineer_id'] == 0 ) {
        $scrutineer = array('id'=>0,
            'festival_id'=>'',
            'syllabus_id'=>'0',
            'section_id'=>'0',
            'customer_id'=>'',
        );
    }

    //
    // Get the details for an existing Scrutiner
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_scrutineers.id, "
            . "ciniki_musicfestival_scrutineers.festival_id, "
            . "ciniki_musicfestival_scrutineers.syllabus_id, "
            . "ciniki_musicfestival_scrutineers.section_id, "
            . "ciniki_musicfestival_scrutineers.customer_id "
            . "FROM ciniki_musicfestival_scrutineers "
            . "WHERE ciniki_musicfestival_scrutineers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_scrutineers.id = '" . ciniki_core_dbQuote($ciniki, $args['scrutineer_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'scrutineers', 'fname'=>'id', 
                'fields'=>array('festival_id', 'syllabus_id', 'section_id', 'customer_id'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1133', 'msg'=>'Scrutiner not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['scrutineers'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1134', 'msg'=>'Unable to find Scrutiner'));
        }
        $scrutineer = $rc['scrutineers'][0];
    }

    return array('stat'=>'ok', 'scrutineer'=>$scrutineer);
}
?>
