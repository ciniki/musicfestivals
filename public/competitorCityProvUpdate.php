<?php
//
// Description
// -----------
// This method will update the city and provinces for competitors for a festival.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_competitorCityProvUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'old_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Old City'),
        'old_province'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Old Province'),
        'new_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'New City'),
        'new_province'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'New Province'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitorCityProvUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the competitors with the city and provice
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.city, "
        . "competitors.province "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "WHERE competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND competitors.province = '" . ciniki_core_dbQuote($ciniki, $args['old_province']) . "' "
        . "";
    if( isset($args['old_city']) ) {
        $strsql .= "AND competitors.city = '" . ciniki_core_dbQuote($ciniki, $args['old_city']) . "' ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'city', 'province'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.470', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    $update_args = array('province'=>$args['new_province']);
    if( isset($args['old_city']) && isset($args['new_city']) ) {
        $update_args['city'] = $args['new_city'];
    }
    foreach($competitors as $c) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', $c['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.471', 'msg'=>'Unable to update the competitor', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
