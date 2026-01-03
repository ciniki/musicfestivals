<?php
//
// Description
// -----------
// This method returns the list of roles for a festival, volunteers and shifts. It combines
// the configured list and adds any extras that have been added.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_musicfestivals_volunteerRoleSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'festival_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Festival'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerRoleSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the roles for the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerRolesLoad');
    $rc = ciniki_musicfestivals_volunteerRolesLoad($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1244', 'msg'=>'', 'err'=>$rc['err']));
    }
    $roles = $rc['roles'];

    //
    // Remove anything doesn't match search
    //
    if( isset($args['start_needle']) && $args['start_needle'] != '' ) {
        foreach($roles as $rid => $role) {
            if( strncasecmp($args['start_needle'], $role, strlen($args['start_needle'])) !== 0 ) {
                unset($roles[$rid]);
            }
        }
    }

    $values = [];
    foreach($roles as $role) {
        $values[] = ['value' => $role];
    }

    return array('stat'=>'ok', 'results'=>$values);
}
?>
