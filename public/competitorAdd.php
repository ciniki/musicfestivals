<?php
//
// Description
// -----------
// This method will add a new competitor for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Competitor to.
//
// Returns
// -------
//
function ciniki_musicfestivals_competitorAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'billing_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Customer'),
        'ctype'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array(10, 50), 'name'=>'Competitor Type'),
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'),
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'public_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Public Name'),
        'pronoun'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Pronoun'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'conductor'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Conductor'),
        'num_people'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Number of People'),
        'parent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parent'),
        'address'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address'),
        'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'),
        'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province'),
        'postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Postal Code'),
        'country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Country'),
        'phone_home'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Home Phone'),
        'phone_cell'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cell Phone'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'),
        'age'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Age'),
        'study_level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Study/Level'),
        'instrument'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Instrument'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitorAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check the names
    //
    if( $args['ctype'] == 10 ) {
        if( !isset($args['first']) || $args['first'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.502', 'msg'=>'You must specifiy a first name'));
        }
        if( !isset($args['last']) || $args['last'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.503', 'msg'=>'You must specifiy a last name'));
        }
        $args['name'] = $args['first'] . ' ' . $args['last'];
        if( !isset($args['public_name']) || $args['public_name'] == '' ) {
            $args['public_name'] = $args['first'][0] . '. ' . $args['last'];
        }
    } elseif( $args['ctype'] == 50 ) {
        if( !isset($args['name']) || $args['name'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.504', 'msg'=>'You must specifiy a name'));
        }
        $args['public_name'] = $args['name']; 
        $args['first'] = '';
        $args['last'] = '';
        $args['pronoun'] = '';
    }
    
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
    // Add the competitor to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $competitor_id = $rc['id'];

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

    return array('stat'=>'ok', 'id'=>$competitor_id, 'name'=>$args['name']);
}
?>
