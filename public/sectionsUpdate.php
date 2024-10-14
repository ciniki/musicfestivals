<?php
//
// Description
// -----------
// Update all sections in a syllabus with new admin fees
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_sectionsUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'syllabus'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus'),
        'adminfees_flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Admin Fees'),
        'adminfees_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Admin Fees Amount'),
        'latefees_flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Late Fees'),
        'latefees_start_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Late Fees Start Amount'),
        'latefees_daily_increase'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Late Fees Daily Increase'),
        'latefees_days'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Late Fees Days'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionsUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the sections in the syllabus for the festival
    //
    $strsql = "SELECT sections.id, "
        . "sections.flags, "
        . "sections.latefees_start_amount, "
        . "sections.latefees_daily_increase, "
        . "sections.latefees_days, "
        . "sections.adminfees_amount "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.syllabus = '" . ciniki_core_dbQuote($ciniki, $args['syllabus']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.858', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    $sections = isset($rc['rows']) ? $rc['rows'] : array();
    
    $update_args = array();
    foreach($sections as $section) {
        $flags = $section['flags']; 
        if( isset($args['adminfees_flags']) && ($section['flags']&0xC0) != $args['adminfees_flags'] ) {
            $flags = ($flags&0xFFFFFF3F) | $args['adminfees_flags'];
        }
        if( isset($args['adminfees_amount']) && $args['adminfees_amount'] != $section['adminfees_amount'] ) {
            $update_args['adminfees_amount'] = $args['adminfees_amount'];
        }
        if( isset($args['latefees_flags']) && ($section['flags']&0x30) != $args['latefees_flags'] ) {
            $flags = ($flags&0xFFFFFFCF) | $args['latefees_flags'];
        }
        if( isset($args['latefees_start_amount']) && $args['latefees_start_amount'] != $section['latefees_start_amount'] ) {
            $update_args['latefees_start_amount'] = $args['latefees_start_amount'];
        }
        if( isset($args['latefees_daily_increase']) && $args['latefees_daily_increase'] != $section['latefees_daily_increase'] ) {
            $update_args['latefees_daily_increase'] = $args['latefees_daily_increase'];
        }
        if( isset($args['latefees_days']) && $args['latefees_days'] != $section['latefees_days'] ) {
            $update_args['latefees_days'] = $args['latefees_days'];
        }

        if( $flags != $section['flags'] ) {
            $update_args['flags'] = $flags;
        }

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.section', $section['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.859', 'msg'=>'Unable to update the section', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
