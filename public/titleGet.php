<?php
//
// Description
// ===========
// This method will return all the information about an approved title.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the approved title is attached to.
// title_id:          The ID of the approved title to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_titleGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'title_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Approved Title'),
        'list_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleGet');
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
    // Return default for new Approved Title
    //
    if( $args['title_id'] == 0 ) {
        $title = array('id'=>0,
            'list_id'=>0,
            'title'=>'',
            'movements'=>'',
            'composer'=>'',
            'source_type'=>'',
        );
        if( isset($args['list_id']) && $args['list_id'] > 0 ) {
            $title['list_id'] = $args['list_id'];
        }
    }

    //
    // Get the details for an existing Approved Title
    //
    else {
        $strsql = "SELECT ciniki_musicfestivals_titles.id, "
            . "ciniki_musicfestivals_titles.list_id, "
            . "ciniki_musicfestivals_titles.title, "
            . "ciniki_musicfestivals_titles.movements, "
            . "ciniki_musicfestivals_titles.composer, "
            . "ciniki_musicfestivals_titles.source_type "
            . "FROM ciniki_musicfestivals_titles "
            . "WHERE ciniki_musicfestivals_titles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestivals_titles.id = '" . ciniki_core_dbQuote($ciniki, $args['title_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'titles', 'fname'=>'id', 
                'fields'=>array('list_id', 'title', 'movements', 'composer', 'source_type'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1156', 'msg'=>'Approved Title not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['titles'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1157', 'msg'=>'Unable to find Approved Title'));
        }
        $title = $rc['titles'][0];
    }

    //
    // Get the list of titlelists
    //
    $strsql = "SELECT lists.id, "
        . "lists.name, "
        . "lists.permalink, "
        . "lists.flags "
        . "FROM ciniki_musicfestivals_titlelists AS lists "
        . "WHERE lists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY lists.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'lists', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $lists = isset($rc['lists']) ? $rc['lists'] : array();
    array_unshift($lists, ['id' => 0, 'name' => 'None']);

    return array('stat'=>'ok', 'title'=>$title, 'lists'=>$lists);
}
?>
