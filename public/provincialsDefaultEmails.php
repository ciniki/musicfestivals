<?php
//
// Description
// -----------
// This method will add a recommendation entry to a draft recommendation
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsDefaultEmails(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsDefaultEmails');
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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_details = isset($rc['details']) ? $rc['details'] : array();

    //
    // Load the load festival and provincials festival info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsFestivalMemberLoad');
    $rc = ciniki_musicfestivals_provincialsFestivalMemberLoad($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $rc['member'];
    $provincials_tnid = $member['tnid'];

    //
    // Load the provincials tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $provincials_tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $provincials_name = $rc['details']['name'];

    //
    // Load the default messages from provincials
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
        . "AND detail_key like 'provincials-email-%' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1468', 'msg'=>'Unable to load the list of ', 'err'=>$rc['err']));
    }
    $p_settings = isset($rc['settings']) ? $rc['settings'] : array();

    $rsp = ['stat'=>'ok'];
    foreach(['provincials-email-invite', 'provincials-email-register-live', 'provincials-email-register-virtual'] as $msg) {
        $rsp["{$msg}-subject"] = '';
        if( isset($p_settings["{$msg}-subject"]) ) {
            $rsp["{$msg}-subject"] = str_replace("{_tenantname_}", $tenant_details['name'], $p_settings["{$msg}-subject"]);
        }
        $rsp["{$msg}-message"] = '';
        if( isset($p_settings["{$msg}-message"]) ) {
            $rsp["{$msg}-message"] = str_replace("{_tenantname_}", $tenant_details['name'], $p_settings["{$msg}-message"]);
        }
    }
    foreach(['provincials-email-register-live', 'provincials-email-register-virtual'] as $msg) {
        $rsp["{$msg}-message"] = str_replace("{_deadline_}", $member['deadline'], $rsp["{$msg}-message"]);
        $rsp["{$msg}-message"] = str_replace("{_latedeadline_}", $member['latedeadline'], $rsp["{$msg}-message"]);
        $rsp["{$msg}-message"] = str_replace("{_latedays_}", $member['latedays'], $rsp["{$msg}-message"]);
    }

    return $rsp;
}
?>
