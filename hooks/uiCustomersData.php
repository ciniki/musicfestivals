<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get musicfestivals for.
//
// Returns
// -------
//
function ciniki_musicfestivals_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    $sections = array();

    //
    // Get the list of adjudications
    //
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.customer_id, "
        . "festivals.name "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "LEFT JOIN ciniki_musicfestivals AS festivals ON ("
            . "adjudicators.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND adjudicators.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND adjudicators.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['adjudicators']) && count($rc['adjudicators']) > 0 ) {
        $sections['ciniki.musicfestivals.adjudicators'] = array(
            'label' => 'Festivals Adjudicated',
            'type' => 'simplegrid', 
            'num_cols' => 1,
            'headerValues' => array('Festival'),
            'cellClasses' => array(''),
            'noData' => 'No adjudications',
//            'editApp' => array('app'=>'ciniki.musicfestivals.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
            'cellValues' => array(
                '0' => "d.name",
                ),
            'data' => $rc['adjudicators'],
            );
    }

    //
    // Get the list of competitors
    //
/*    $strsql = "SELECT competitors.id, "
        . "competitors.billing_customer_id, "
        . "festivals.name "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "LEFT JOIN ciniki_musicfestivals AS festivals ON ("
            . "competitors.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND competitors.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND competitors.billing_customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'billing_customer_id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['competitors']) && count($rc['competitors']) > 0 ) {
        $sections['ciniki.musicfestivals.competitors'] = array(
            'label' => 'Competitors',
            'type' => 'simplegrid', 
            'num_cols' => 1,
            'headerValues' => array('Festival'),
            'cellClasses' => array(''),
            'noData' => 'No competitors',
    //            'editApp' => array('app'=>'ciniki.musicfestivals.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
            'cellValues' => array(
                '0' => "d.name",
                ),
            'data' => array(),
            );
        $sections['ciniki.musicfestivals.competitors']['data'] = $rc['competitors'];
    }
*/
    //
    // Get the list of customers
    //
/*    $sections['ciniki.musicfestivals.customers'] = array(
        'label' => 'Signups',
        'type' => 'simplegrid', 
        'num_cols' => 1,
        'headerValues' => array('Festival'),
        'cellClasses' => array(''),
        'noData' => 'No signups',
//            'editApp' => array('app'=>'ciniki.musicfestivals.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.name",
            ),
        'data' => array(),
        );
    $strsql = "SELECT customers.id, "
        . "customers.customer_id, "
        . "festivals.name "
        . "FROM ciniki_musicfestival_customers AS customers "
        . "LEFT JOIN ciniki_musicfestivals AS festivals ON ("
            . "customers.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND customers.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections['ciniki.musicfestivals.customers']['data'] = isset($rc['customers']) ? $rc['customers'] : array();
*/
    //
    // Get the list of registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.festival_id, "
        . "festivals.name, "
        . "sections.id AS section_id, "
        . "registrations.teacher_customer_id, "
        . "teachers.display_name AS teacher_name, "
        . "registrations.billing_customer_id, "
        . "registrations.rtype, "
        . "registrations.rtype AS rtype_text, "
        . "registrations.status, "
        . "registrations.status AS status_text, "
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
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
        . "IFNULL(DATE_FORMAT(timeslots.slot_time, '%H:%i %p'), '') AS timeslot_time, "
        . "IFNULL(DATE_FORMAT(sdivisions.division_date, '%M %d, %Y'), '') AS division_date "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "registrations.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS teachers ON ("
            . "registrations.teacher_customer_id = teachers.id "
            . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS sdivisions ON ("
            . "timeslots.sdivision_id = sdivisions.id "
            . "AND sdivisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND ("
//            . "registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . ") ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND ("
//            . "registrations.teacher_customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") "
            . "registrations.billing_customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") "
            . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'teacher_customer_id', 'teacher_name', 
                'billing_customer_id', 'rtype', 'rtype_text', 'status', 'status_text', 'display_name', 
                'class_id', 'class_code', 'class_name', 
                'title1', 'perf_time1', 'title2', 'perf_time2', 'title3', 'perf_time3', 
                'fee', 'payment_type',
                'video_url1', 'video_url2', 'video_url3', 'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3',
                'timeslot_time', 'division_date',
                ),
            'maps'=>array(
                'rtype_text'=>$maps['registration']['rtype'],
                'status_text'=>$maps['registration']['status'],
                'payment_type'=>$maps['registration']['payment_type'],
                ),
            ),
        ));
/*    $strsql = "SELECT registrations.id, "
        . "registrations.teacher_customer_id, "
        . "registrations.billing_customer_id, "
        . "festivals.name "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_musicfestivals AS festivals ON ("
            . "registrations.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND ("
            . "registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "OR registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . ") ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND ("
            . "registrations.teacher_customer_id IN (" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . ") "
            . "OR registrations.billing_customer_id IN (" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . ") "
            . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY festivals.start_date DESC, festivals.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'teacher_customer_id', 'billing_customer_id', 'name'),
            ),
        )); */
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['registrations']) && count($rc['registrations']) > 0 ) {
        $sections['ciniki.musicfestivals.registrations'] = array(
            'label' => 'Registrations',
            'type' => 'simplegrid', 
            'num_cols' => 5,
            'headerValues' => array('Festival', 'Class', 'Registrant', 'Timeslot', 'Status'),
            'cellClasses' => array('', '', '', 'multiline', ''),
            'noData' => 'No registrations',
            'editApp' => array('app'=>'ciniki.musicfestivals.main', 'args'=>array('registration_id'=>'d.id;')),
            'cellValues' => array(
                '0' => "d.name",
                '1' => "d.class_code",
                '2' => "d.display_name",
                '3' => "M.multiline(d.division_date, d.timeslot_time)",
                '4' => "d.status_text",
                ),
            'data' => $rc['registrations'],
            );
    }

    //
    // Setup the tab
    //
/*    if( count($sections['ciniki.musicfestivals.adjudicators']['data']) > 0 
        || count($sections['ciniki.musicfestivals.competitors']['data']) > 0 
        || count($sections['ciniki.musicfestivals.customers']['data']) > 0 
        || count($sections['ciniki.musicfestivals.registrations']['data']) > 0 
        ) { */
    if( count($sections) > 0 ) {
        $rsp['tabs'][] = array(
            'id' => 'ciniki.musicfestivals.festivals',
            'label' => 'Music',
            'priority' => 3000,
            'sections' => $sections,
            );
        $sections = array();
    }

    return $rsp;
}
?>
