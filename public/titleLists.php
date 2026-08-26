<?php
//
// Description
// ===========
// This method will return all the information about an approved title list.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the approved title list is attached to.
// list_id:          The ID of the approved title list to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_titleLists($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleLists');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the list of lists
    //
    $strsql = "SELECT id, "
        . "name "
        . "FROM ciniki_musicfestivals_titlelists "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'lists', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $lists = isset($rc['lists']) ? $rc['lists'] : array();

    return array('stat'=>'ok', 'lists'=>$lists);
}
?>
