<?php
//
// Description
// -----------
// This method will return the results in an excel file
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsAcceptedUpdate(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Section'),
        'action'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Action'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsAcceptedUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of accepted
    //
    if( isset($args['action']) && $args['action'] == 'movetoinstructionssent' ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.provincials_status "
            . "FROM ciniki_musicfestival_registrations AS registrations ";
        if( isset($args['section_id']) && $args['section_id'] > 0 ) {
            $strsql .= "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "registrations.class_id = classes.id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "categories.section_id = sections.id "
                    . "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") ";
        }
        $strsql .= "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.provincials_status = 50 "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array(
                    'id', 'provincials_status'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.947', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

        foreach($registrations as $reg) {
            if( $reg['provincials_status'] == 50 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $reg['id'], ['provincials_status' => 55], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.948', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
