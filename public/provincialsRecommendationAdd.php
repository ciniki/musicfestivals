<?php
//
// Description
// -----------
// This method is used by local festivals to submit recommendation to provincials
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsRecommendationAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'local_adjudicator_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Adjudicator'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsRecommendationAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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

    if( !isset($member['reg_status']) || ($member['reg_status'] != 'open' && $member['reg_status'] != 'drafts') ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1330', 'msg'=>'Recommendation submissions are closed for your festival'));
    }

    //
    // Load adjudicator
    //
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.customer_id "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "WHERE adjudicators.id = '" . ciniki_core_dbQuote($ciniki, $args['local_adjudicator_id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'adjudicator');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1283', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
    }
    if( !isset($rc['adjudicator']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1284', 'msg'=>'Unable to find requested adjudicator'));
    }
    $adjudicator = $rc['adjudicator'];
    
    //
    // If the customer is specified, load the details
    //
    if( isset($adjudicator['customer_id']) && $adjudicator['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
            array('customer_id'=>$adjudicator['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $adjudicator['customer'] = $rc['customer'];
        $args['adjudicator_name'] = $adjudicator['customer']['display_name']; 
        $args['adjudicator_email'] = '';
        $args['adjudicator_phone'] = '';
        if( isset($adjudicator['emails'][0]['address']) ) {
            $args['adjudicator_email'] = $adjudicator['emails'][0]['address'];
        }
        if( isset($adjudicator['phones'][0]['phone_number']) ) {
            $args['adjudicator_phone'] = $adjudicator['phones'][0]['phone_number'];
        }
    } else {
        $adjudicator['adjudicator_name'] = '';
        $adjudicator['adjudicator_email'] = '';
        $adjudicator['adjudicator_phone'] = '';
    }


    //
    // Set the parameters for the recommendation
    //
    $args['festival_id'] = $provincials_festival_id;
    $args['member_id'] = $member['id'];
    $args['status'] = 10;

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
    // Add the recommendation
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendation', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1286', 'msg'=>'Unable to add the recommendation', 'err'=>$rc['err']));
    }
    $recommendation_id = $rc['id']; 
    
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
    ciniki_tenants_updateModuleChangeDate($ciniki, $provincials_tnid, 'ciniki', 'musicfestivals');

    return array('stat'=>'ok', 'id'=>$recommendation_id);
}
?>
