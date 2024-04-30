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
//        . "sections.id AS section_id, "
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
        . "FORMAT(registrations.fee, 2) AS fee, "
        . "registrations.payment_type, "
        . "registrations.participation, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.composer1, "
        . "registrations.composer2, "
        . "registrations.composer3, "
        . "registrations.composer4, "
        . "registrations.composer5, "
        . "registrations.composer6, "
        . "registrations.composer7, "
        . "registrations.composer8, "
        . "registrations.movements1, "
        . "registrations.movements2, "
        . "registrations.movements3, "
        . "registrations.movements4, "
        . "registrations.movements5, "
        . "registrations.movements6, "
        . "registrations.movements7, "
        . "registrations.movements8, "
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
        . "registrations.music_orgfilename4, "
        . "registrations.music_orgfilename5, "
        . "registrations.music_orgfilename6, "
        . "registrations.music_orgfilename7, "
        . "registrations.music_orgfilename8 "
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
/*        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") " */
        . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "competitors.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.parent LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.parent LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR competitors.email LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//            . "OR teachers.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//            . "OR teachers.first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//            . "OR teachers.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//            . "OR teachers.last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
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
                'fee', 'payment_type', 'participation', 
                'title1', 'composer1', 'movements1', 'video_url1', 'music_orgfilename1',
                'title2', 'composer2', 'movements2', 'video_url2', 'music_orgfilename2',
                'title3', 'composer3', 'movements3', 'video_url3', 'music_orgfilename3',
                'title4', 'composer4', 'movements4', 'video_url4', 'music_orgfilename4',
                'title5', 'composer5', 'movements5', 'video_url5', 'music_orgfilename5',
                'title6', 'composer6', 'movements6', 'video_url6', 'music_orgfilename6',
                'title7', 'composer7', 'movements7', 'video_url7', 'music_orgfilename7',
                'title8', 'composer8', 'movements8', 'video_url8', 'music_orgfilename8',
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
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
        foreach($registrations as $rid => $registration) {
            $registration_ids[] = $registration['id'];
            $registrations[$rid]['titles'] = '';
            for($i = 1; $i <= 8; $i++) {
                if( $registration["title{$i}"] != '' ) {
                    $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $registration, $i);
                    if( $rc['stat'] == 'ok' ) {
                        $registrations[$rid]["title{$i}"] = $rc['title'];
                        $registrations[$rid]['titles'] .= ($registrations[$rid]['titles'] != '' ? '<br/>' : '') . $rc['title'];
                    }
                }
            }

        }
    } else {
        $registrations = array();
        $registration_ids = array();
    }

    return array('stat'=>'ok', 'registrations'=>$registrations, 'nplist'=>$registration_ids);
}
?>
