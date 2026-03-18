<?php
//
// Description
// -----------
// Add an approved role to all volunteers
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_volunteersRoleAdd(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'role'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Role'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteersRoleAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $permalink = ciniki_core_makePermalink($ciniki, $args['role']);

    //
    // Get the list of applied and approved volunteers
    //
    $strsql = "SELECT volunteers.id, "
        . "IFNULL(tags.tag_name, '') AS tag_name "
        . "FROM ciniki_musicfestival_volunteers AS volunteers "
        . "LEFT JOIN ciniki_musicfestival_volunteer_tags AS tags ON ("
            . "volunteers.id = tags.volunteer_id "
            . "AND tags.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
            . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'volunteers');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1523', 'msg'=>'Unable to load volunteers', 'err'=>$rc['err']));
    }
    $volunteers = isset($rc['rows']) ? $rc['rows'] : array();
   
    //
    // Add the role
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    foreach($volunteers AS $volunteer) {
        if( $volunteer['tag_name'] == '' ) {
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteertag', [
                'volunteer_id' => $volunteer['id'],
                'tag_type' => 50,
                'tag_name' => $args['role'],
                'permalink' => $permalink,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1524', 'msg'=>'Unable to update the volunteertag', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
