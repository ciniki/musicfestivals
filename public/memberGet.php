<?php
//
// Description
// ===========
// This method will return all the information about an member festival.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the member festival is attached to.
// member_id:          The ID of the member festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_memberGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'member_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Member Festival'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Provincial Festival'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.memberGet');
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Member Festival
    //
    if( $args['member_id'] == 0 ) {
        $member = array('id'=>0,
            'name' => '',
            'shortname' => '',
            'synopsis' => '',
            'status' => '10',
            'reg_start_dt' => '',
            'reg_end_dt' => '',
            'latedays' => '0',
            'customer_id' => '0',
            'customer' => array(),
            'customer_details' => array(),
        );
    }

    //
    // Get the details for an existing Member Festival
    //
    else {
        $strsql = "SELECT members.id, "
            . "members.name, "
            . "members.shortname, "
            . "members.category, "
            . "members.synopsis, "
            . "members.status, "
            . "members.customer_id, "
            . "IFNULL(fmembers.reg_start_dt, '') AS reg_start_dt, "
            . "IFNULL(fmembers.reg_end_dt, '') AS reg_end_dt, "
            . "IFNULL(fmembers.latedays, '') AS latedays "
            . "FROM ciniki_musicfestivals_members AS members "
            . "LEFT JOIN ciniki_musicfestival_members AS fmembers ON ("
                . "members.id = fmembers.member_id "
                . "AND fmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND fmembers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND members.id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'members', 'fname'=>'id', 
                'fields'=>array('name', 'shortname', 'category', 'synopsis', 'status', 'customer_id', 'reg_start_dt', 'reg_end_dt', 'latedays'),
                'utctotz'=>array(
                    'reg_start_dt' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'reg_end_dt' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.585', 'msg'=>'Member Festival not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['members'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.586', 'msg'=>'Unable to find Member Festival'));
        }
        $member = $rc['members'][0];

        //
        // If the customer is specified, load the details
        //
        if( isset($member['customer_id']) && $member['customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
                array('customer_id'=>$member['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $member['customer'] = $rc['customer'];
            $member['customer_details'] = $rc['details'];
        } else {
            $member['customer'] = array();
            $member['customer_details'] = array();
        }
    }

    return array('stat'=>'ok', 'member'=>$member);
}
?>
