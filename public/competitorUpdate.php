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
function ciniki_musicfestivals_competitorUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'competitor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Competitor'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'ctype'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Competitor Type'),
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'),
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
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
        'etransfer_email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'etransfer Email'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitorUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the competitor
    //
    $strsql = "SELECT ciniki_musicfestival_competitors.id, "
        . "ciniki_musicfestival_competitors.ctype, "
        . "ciniki_musicfestival_competitors.first, "
        . "ciniki_musicfestival_competitors.last, "
        . "ciniki_musicfestival_competitors.name, "
        . "ciniki_musicfestival_competitors.public_name, "
        . "ciniki_musicfestival_competitors.pronoun "
        . "FROM ciniki_musicfestival_competitors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.150', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
    }
    if( !isset($rc['competitor']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.151', 'msg'=>'Unable to find requested competitor'));
    }
    $competitor = $rc['competitor'];

    $ctype = (isset($args['ctype']) ? $args['ctype'] : $competitor['ctype']);
    if( $ctype == 10 && (isset($args['first']) || isset($args['last'])) ) {
        $args['name'] = (isset($args['first']) ? $args['first'] : $competitor['first'])
            . ' ' . (isset($args['last']) ? $args['last'] : $competitor['last']);
        $public_name = (isset($args['first']) ? $args['first'][0] : $competitor['first'][0])
            . '. ' . (isset($args['last']) ? $args['last'] : $competitor['last']);
    } elseif( $ctype == 50 && isset($args['name']) ) {
        $public_name = $args['name']; 
    }
    if( $ctype == 50 && ((isset($args['pronoun']) && $args['pronoun'] != '') || $competitor['pronoun'] != '') ) {
        $args['pronoun'] = '';
    }
    
    //
    // Check if public name should be updated
    //
    if( isset($public_name) && $public_name != $competitor['public_name'] ) {
        $args['public_name'] = $public_name;
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
    // Update the Competitor in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.competitor', $args['competitor_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Update the competitor registrations
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'competitorUpdateNames');
    $rc = ciniki_musicfestivals_competitorUpdateNames($ciniki, $args['tnid'], $args['festival_id'], $args['competitor_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
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

    return array('stat'=>'ok');
}
?>
