<?php
//
// Description
// -----------
// This function will check if the user has access to the landingpages module.
//
// Arguments
// ---------
// ciniki:
// tnid:                 The tenant ID to check the session user against.
// method:                      The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_musicfestivals_checkAccess(&$ciniki, $tnid, $method) {
    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1', 'msg'=>'No permissions granted'));
    }

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms']&0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$modules);
    }

    //
    // Check if the user is an owner of the tenant
    //
    $strsql = "SELECT tnid, user_id "
        . "FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "
        . "AND ("
            . "permission_group = 'owners' "
//            . "OR (permission_group = 'employees' && modperms like '%ciniki.musicfestivals%') "
            . "OR permission_group = 'resellers' "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.176', 'msg'=>'Access denied.'));
    }
    //
    // If the user has permission, return ok
    //
    if( isset($rc['rows']) && isset($rc['rows'][0])
        && $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
        return array('stat'=>'ok', 'modules'=>$modules);
    }

    //
    // Check if they are an employee
    //
    $strsql = "SELECT tnid, user_id, modperms "
        . "FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "
        . "AND permission_group = 'employees' "
        . "AND modperms LIKE '%ciniki.musicfestivals%' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.176', 'msg'=>'Access denied.'));
    }

    if( isset($rc['user']['modperms']) ) {
        $modperms = json_decode($rc['user']['modperms'], true);
        if( in_array('ciniki.musicfestivals', $modperms) ) {
            return array('stat'=>'ok', 'modules'=>$modules);
        }
        if( in_array('ciniki.musicfestivals.volunteers', $modperms) 
            && preg_match("/ciniki.musicfestivals.volunteer/", $method)
            ) {
            // FIXME Volunteers: - make sure this works
            return array('stat'=>'ok', 'modules'=>$modules);
        }
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.3', 'msg'=>'Access denied'));
}
?>
