<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_memberUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'member_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Member Festival'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'shortname'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Short Name'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'member_tnid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Tenant'),
        'reg_start_dt'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Registrations Open'),
        'reg_end_dt'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Registrations Close'),
        'latedays'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Late Days'),
        'yearly_details'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Yearly Details'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.memberUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load existing member
    //
    $strsql = "SELECT members.id, "
        . "members.name, "
        . "members.shortname, "
        . "members.category, "
        . "members.synopsis, "
        . "members.status, "
        . "members.member_tnid, "
        . "IFNULL(fmembers.id, 0) AS fmember_id, "
        . "IFNULL(fmembers.reg_start_dt, '') AS reg_start_dt, "
        . "IFNULL(fmembers.reg_end_dt, '') AS reg_end_dt, "
        . "IFNULL(fmembers.latedays, '') AS latedays, "
        . "IFNULL(fmembers.yearly_details, '') AS yearly_details "
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
            'fields'=>array('name', 'shortname', 'category', 'synopsis', 'status', 'member_tnid', 'fmember_id', 'reg_start_dt', 'reg_end_dt', 'latedays', 'yearly_details'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.632', 'msg'=>'Member Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['members'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.636', 'msg'=>'Unable to find Member Festival'));
    }
    $member = $rc['members'][0];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Member Festival in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.member', $args['member_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Update the existing festival member
    //
    if( $member['fmember_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalmember', $member['fmember_id'], $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    } elseif( (isset($args['reg_start_dt']) && $args['reg_start_dt'] != '') 
        || (isset($args['reg_end_dt']) && $args['reg_end_dt'] != '' )
        || (isset($args['yearly_details']) && $args['yearly_details'] != '' )
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalmember', $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
        $fmember_id = $rc['id'];
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.member', 'object_id'=>$args['member_id']));

    return array('stat'=>'ok');
}
?>
