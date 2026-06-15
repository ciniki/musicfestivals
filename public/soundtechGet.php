<?php
//
// Description
// ===========
// This method will return all the information about an soundtech.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the soundtech is attached to.
// soundtech_id:          The ID of the soundtech to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_soundtechGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'soundtech_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sound Technician'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.soundtechGet');
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
    // Return default for new Sound Technician
    //
    if( $args['soundtech_id'] == 0 ) {
        $soundtech = array('id'=>0,
            'customer_id'=>(isset($args['customer_id']) ? $args['customer_id'] : 0),
            'festival_id'=>(isset($args['festival_id']) ? $args['festival_id'] : 0),
        );
    }

    //
    // Get the details for an existing Sound Technician
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_soundtechs.id, "
            . "ciniki_musicfestival_soundtechs.festival_id, "
            . "ciniki_musicfestival_soundtechs.customer_id, "
            . "ciniki_musicfestival_soundtechs.flags "
            . "FROM ciniki_musicfestival_soundtechs "
            . "WHERE ciniki_musicfestival_soundtechs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_soundtechs.id = '" . ciniki_core_dbQuote($ciniki, $args['soundtech_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'soundtechs', 'fname'=>'id', 
                'fields'=>array('festival_id', 'customer_id', 'flags', 'image_id', 
                    'category', 'discipline', 'description', 'sig_image_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1625', 'msg'=>'Sound Technician not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['soundtechs'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1626', 'msg'=>'Unable to find Sound Technician'));
        }
        $soundtech = $rc['soundtechs'][0];
    }

    //
    // If the customer is specified, load the details
    //
    if( isset($soundtech['customer_id']) && $soundtech['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
            array('customer_id'=>$soundtech['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $soundtech['customer'] = $rc['customer'];
        $soundtech['customer_details'] = $rc['details'];
    } else {
        $soundtech['customer'] = array();
        $soundtech['customer_details'] = array();
    }

    return array('stat'=>'ok', 'soundtech'=>$soundtech);
}
?>
