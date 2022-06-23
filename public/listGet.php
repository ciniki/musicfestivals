<?php
//
// Description
// ===========
// This method will return all the information about an list.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the list is attached to.
// list_id:          The ID of the list to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_listGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'list_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'List'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.listGet');
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
    // Return default for new List
    //
    if( $args['list_id'] == 0 ) {
        $list = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
            'category'=>'',
            'intro'=>'',
        );
    }

    //
    // Get the details for an existing List
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_lists.id, "
            . "ciniki_musicfestival_lists.festival_id, "
            . "ciniki_musicfestival_lists.name, "
            . "ciniki_musicfestival_lists.category, "
            . "ciniki_musicfestival_lists.intro "
            . "FROM ciniki_musicfestival_lists "
            . "WHERE ciniki_musicfestival_lists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_lists.id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'lists', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'category', 'intro'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.265', 'msg'=>'List not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['lists'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.266', 'msg'=>'Unable to find List'));
        }
        $list = $rc['lists'][0];
    }

    return array('stat'=>'ok', 'list'=>$list);
}
?>
