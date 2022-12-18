<?php
//
// Description
// -----------
// This method searchs for a Registrations for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_registrationSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load conference maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Search for registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.festival_id, "
        . "sections.id AS section_id, "
        . "registrations.teacher_customer_id, "
        . "teachers.display_name AS teacher_name, "
        . "registrations.billing_customer_id, "
        . "registrations.rtype, "
        . "registrations.rtype AS rtype_text, "
        . "registrations.status, "
        . "registrations.status AS status_text, "
        . "registrations.invoice_id, "
        . "registrations.display_name, "
        . "registrations.class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.title1, "
        . "registrations.perf_time1, "
        . "registrations.title2, "
        . "registrations.perf_time2, "
        . "registrations.title3, "
        . "registrations.perf_time3, "
        . "FORMAT(registrations.fee, 2) AS fee, "
        . "registrations.payment_type, "
        . "registrations.participation, "
        . "registrations.video1_url, "
        . "registrations.video2_url, "
        . "registrations.video3_url, "
        . "registrations.music1_orgfilename, "
        . "registrations.music2_orgfilename, "
        . "registrations.music3_orgfilename "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "(competitors.id = registrations.competitor1_id "
                . "OR competitors.id = registrations.competitor2_id "
                . "OR competitors.id = registrations.competitor3_id "
                . "OR competitors.id = registrations.competitor4_id "
                . "OR competitors.id = registrations.competitor5_id "
            . ") "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS teachers ON ("
            . "registrations.teacher_customer_id = teachers.id "
            . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "competitors.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.parent LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.parent LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR teachers.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR teachers.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'teacher_name', 'billing_customer_id', 'rtype', 'rtype_text', 
                'status', 'status_text', 'invoice_id', 'display_name', 
                'class_id', 'class_code', 'class_name', 
                'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3', 'fee', 'payment_type', 
                'participation', 'video1_url', 'video2_url', 'video3_url', 
                'music1_orgfilename', 'music2_orgfilename', 'music3_orgfilename',
                ),
            'maps'=>array(
                'rtype_text'=>$maps['registration']['rtype'],
                'status_text'=>$maps['registration']['status'],
                'payment_type'=>$maps['registration']['payment_type'],
                ),
            ),
        ));

    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['registrations']) ) {
        $registrations = $rc['registrations'];
        $registration_ids = array();
        foreach($registrations as $iid => $registration) {
            $registration_ids[] = $registration['id'];
        }
    } else {
        $registrations = array();
        $registration_ids = array();
    }

    return array('stat'=>'ok', 'registrations'=>$registrations, 'nplist'=>$registration_ids);
}
?>
