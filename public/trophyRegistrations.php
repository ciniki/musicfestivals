<?php
//
// Description
// -----------
// This method will return the list of Trophys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Trophy for.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophyRegistrations($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophyRegistrations');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of trophies
    //
    $strsql = "SELECT trophies.id, "
        . "trophies.name, "
        . "trophies.category, "
        . "trophies.donated_by, "
        . "trophies.first_presented, "
        . "trophies.criteria, "
        . "trophies.description, "
        . "COUNT(registrations.id) AS num_registrations "
        . "FROM ciniki_musicfestival_trophies AS trophies "
        . "INNER JOIN ciniki_musicfestival_trophy_classes AS classes ON ("
            . "trophies.id = classes.trophy_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.class_id = registrations.class_id "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
/*        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") " */
        . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY trophies.id "
        . "ORDER BY trophies.category, trophies.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'trophies', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'category', 'donated_by', 'first_presented', 'criteria', 'description', 'num_registrations')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();
    $trophy_ids = array();
    foreach($trophies as $iid => $trophy) {
        $trophy_ids[] = $trophy['id'];
    }

    if( isset($args['output']) && $args['output'] == 'pdf' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'trophyRegistrationsPDF');
        $rc = ciniki_musicfestivals_templates_trophyRegistrationsPDF($ciniki, $args['tnid'], array(
            'trophies' => $trophies,
            'festival_id' => $args['festival_id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.744', 'msg'=>'Unable to generate PDF', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'], 'I');
            return array('stat'=>'exit');
        }
    }

    return array('stat'=>'ok', 'trophies'=>$trophies, 'nplist'=>$trophy_ids);
}
?>
