<?php
//
// Description
// -----------
// This method will return the list of Accolades for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Accolade for.
//
// Returns
// -------
//
function ciniki_musicfestivals_accoladeRegistrations($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeRegistrations');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of accolades
    //
    $strsql = "SELECT accolades.id, "
        . "accolades.name, "
        . "accolades.typename, "
        . "accolades.category, "
        . "accolades.donated_by, "
        . "accolades.first_presented, "
        . "accolades.criteria, "
        . "accolades.amount, "
        . "accolades.description, "
        . "COUNT(registrations.id) AS num_registrations "
        . "FROM ciniki_musicfestival_accolades AS accolades "
        . "INNER JOIN ciniki_musicfestival_accolade_classes AS classes ON ("
            . "accolades.id = classes.accolade_id "
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
        . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY accolades.id "
        . "ORDER BY accolades.category, accolades.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'accolades', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'typename', 'category', 'donated_by', 'first_presented', 'criteria', 'amount', 
                'description', 'num_registrations',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $accolades = isset($rc['accolades']) ? $rc['accolades'] : array();
    $accolade_ids = array();
    foreach($accolades as $iid => $accolade) {
        $accolade_ids[] = $accolade['id'];
    }

    if( isset($args['output']) && $args['output'] == 'pdf' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'accoladeRegistrationsPDF');
        $rc = ciniki_musicfestivals_templates_accoladeRegistrationsPDF($ciniki, $args['tnid'], array(
            'accolades' => $accolades,
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

    return array('stat'=>'ok', 'accolades'=>$accolades, 'nplist'=>$accolade_ids);
}
?>
