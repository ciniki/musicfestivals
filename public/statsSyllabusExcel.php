<?php
//
// Description
// -----------
// Generate the excel stats file for a year
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_statsSyllabusExcel(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.statsSyllabusExcel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Get the syllabus sections, number of registrations and fees for each section
    //
    $strsql = "SELECT sections.id, "
        . "sections.name, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.fee AS live_fee, "
        . "classes.virtual_fee AS virtual_fee, "
        . "classes.plus_fee AS plus_fee, "
        . "IFNULL(registrations.participation, 0) AS _p, "
        . "COUNT(registrations.id) AS num_reg, "
        . "IFNULL(SUM(items.total_amount), 0) AS fees "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_invoice_items AS items ON ("
            . "registrations.invoice_id = items.invoice_id "
            . "AND registrations.id = items.object_id "
            . "AND items.object = 'ciniki.musicfestivals.registration' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY sections.id, categories.id, classes.id, _p "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name, _p "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        array('container'=>'categories', 'fname'=>'category_id', 'fields'=>array('id'=>'category_id', 'name'=>'category_name')),
        array('container'=>'classes', 'fname'=>'class_id', 'fields'=>array('id'=>'class_id', 
            'code'=>'class_code', 'name'=>'class_name', 'live_fee', 'virtual_fee', 'plus_fee',
            )),
        array('container'=>'registrations', 'fname'=>'_p', 'fields'=>array('participation'=>'_p', 'num_reg', 'fees')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1082', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $stats = isset($rc['sections']) ? $rc['sections'] : array();
    $categories = [];
    $classes = [];
    foreach($stats as $sid => $section) {
        foreach(['live', 'virtual', 'plus', 'total'] as $t) {
            $stats[$sid]["{$t}_reg"] = 0;
            $stats[$sid]["{$t}_fees"] = 0;
        }
        if( isset($section['categories']) ) { 
            foreach($section['categories'] as $cid => $category) {
                foreach(['live', 'virtual', 'plus', 'total'] as $t) {
                    $stats[$sid]['categories'][$cid]["{$t}_reg"] = 0;
                    $stats[$sid]['categories'][$cid]["{$t}_fees"] = 0;
                }
                if( isset($category['classes']) ) { 
                    foreach($category['classes'] as $id => $class) {
                        foreach(['live', 'virtual', 'plus', 'total'] as $t) {
                            $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_reg"] = 0;
                            $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_fees"] = 0;
                        }
                        foreach(['live', 'virtual', 'plus'] as $t) {
                            if( $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_fee"] == 0 ) {
                                $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_fee"] = '';
                            }

                        }
                        if( isset($class['registrations']) ) {
                            foreach($class['registrations'] as $regtype) {
                                $t = '';
                                if( $regtype['participation'] == 0 ) {
                                    $t = 'live';
                                } elseif( $regtype['participation'] == 1 ) {
                                    $t = 'virtual';
                                } elseif( $regtype['participation'] == 2 ) {
                                    $t = 'plus';
                                }
                                if( $t != '' ) {
                                    $stats[$sid]["{$t}_reg"] += $regtype['num_reg'];
                                    $stats[$sid]["{$t}_fees"] += $regtype['fees'];
                                    $stats[$sid]['categories'][$cid]["{$t}_reg"] += $regtype['num_reg'];
                                    $stats[$sid]['categories'][$cid]["{$t}_fees"] += $regtype['fees'];
                                    $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_reg"] += $regtype['num_reg'];
                                    $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_fees"] += $regtype['fees'];
                                    $stats[$sid]["total_reg"] += $regtype['num_reg'];
                                    $stats[$sid]["total_fees"] += $regtype['fees'];
                                    $stats[$sid]['categories'][$cid]["total_reg"] += $regtype['num_reg'];
                                    $stats[$sid]['categories'][$cid]["total_fees"] += $regtype['fees'];
                                    $stats[$sid]['categories'][$cid]['classes'][$id]["total_reg"] += $regtype['num_reg'];
                                    $stats[$sid]['categories'][$cid]['classes'][$id]["total_fees"] += $regtype['fees'];
                                }
                            }
                        }
                        foreach(['live', 'virtual', 'plus', 'total'] as $t) {
                            if( $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_reg"] == 0 ) {
                                $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_reg"] = '';
                            }
                            if( $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_fees"] == 0 ) {
                                $stats[$sid]['categories'][$cid]['classes'][$id]["{$t}_fees"] = '';
                            }
                        }
                        $stats[$sid]['categories'][$cid]['classes'][$id]['section'] = $section['name'];
                        $stats[$sid]['categories'][$cid]['classes'][$id]['category'] = $category['name'];
                        $classes[] = $stats[$sid]['categories'][$cid]['classes'][$id];
                    }
                }
                foreach(['live', 'virtual', 'plus', 'total'] as $t) {
                    if( $stats[$sid]['categories'][$cid]["{$t}_reg"] == 0 ) {
                        $stats[$sid]['categories'][$cid]["{$t}_reg"] = '';
                    }
                    if( $stats[$sid]['categories'][$cid]["{$t}_fees"] == 0 ) {
                        $stats[$sid]['categories'][$cid]["{$t}_fees"] = '';
                    }
                }
                $stats[$sid]['categories'][$cid]['section'] = $section['name'];
                $stats[$sid]['categories'][$cid]['category'] = $category['name'];
                $categories[] = $stats[$sid]['categories'][$cid];
            }
        }
        foreach(['live', 'virtual', 'plus', 'total'] as $t) {
            if( $stats[$sid]["{$t}_reg"] == 0 ) {
                $stats[$sid]["{$t}_reg"] = '';
            }
            if( $stats[$sid]["{$t}_fees"] == 0 ) {
                $stats[$sid]["{$t}_fees"] = '';
            }
        }
    }


    $sheets = [
        'sections' => [
            'label' => 'Sections',
            'columns' => [
                ['label' => 'Section', 'field' => 'name'],
                ['label' => 'Live #', 'field' => 'live_reg', 'footer'=>'sum'],
                ['label' => 'Live Fees', 'field' => 'live_fees', 'format'=>'currency', 'footer'=>'sum'],
                ],
            'rows' => $stats,
            ],
        'categories' => [
            'label' => 'Categories',
            'columns' => [
                ['label' => 'Section', 'field' => 'section'],
                ['label' => 'Category', 'field' => 'name'],
                ['label' => 'Live #', 'field' => 'live_reg', 'footer'=>'sum'],
                ['label' => 'Live Fees', 'field' => 'live_fees', 'format'=>'currency', 'footer'=>'sum'],
                ],
            'rows' => $categories,
            ],
        'classes' => [
            'label' => 'Classes',
            'columns' => [
                ['label' => 'Section', 'field' => 'section'],
                ['label' => 'Category', 'field' => 'category'],
                ['label' => 'Code', 'field' => 'code'],
                ['label' => 'Class', 'field' => 'name'],
                ['label' => 'Live #', 'field' => 'live_reg', 'footer'=>'sum'],
                ['label' => 'Live Fee', 'field' => 'live_fee', 'format'=>'currency'],
                ['label' => 'Live Fees', 'field' => 'live_fees', 'format'=>'currency', 'footer'=>'sum'],
                ],
            'rows' => $classes,
            ],
        ];

    // Virtual
    if( ($festival['flags']&0x06) > 0 ) {
        $sheets['sections']['columns'][] = ['label' => 'Virtual #', 'field' => 'virtual_reg', 'footer'=>'sum'];
        $sheets['sections']['columns'][] = ['label' => 'Virtual Fees', 'field' => 'virtual_fees', 'format'=>'currency', 'footer'=>'sum'];
        $sheets['categories']['columns'][] = ['label' => 'Virtual #', 'field' => 'virtual_reg', 'footer'=>'sum'];
        $sheets['categories']['columns'][] = ['label' => 'Virtual Fees', 'field' => 'virtual_fees', 'format'=>'currency', 'footer'=>'sum'];
        $sheets['classes']['columns'][] = ['label' => 'Virtual #', 'field' => 'virtual_reg', 'footer'=>'sum'];
        $sheets['classes']['columns'][] = ['label' => 'Virtual Fee', 'field' => 'virtual_fee', 'format'=>'currency'];
        $sheets['classes']['columns'][] = ['label' => 'Virtual Fees', 'field' => 'virtual_fees', 'format'=>'currency', 'footer'=>'sum'];
    }

    // Live Plus
    if( ($festival['flags']&0x10) > 0 ) {
        $sheets['sections']['columns'][] = ['label' => 'Plus #', 'field' => 'plus_reg', 'footer'=>'sum'];
        $sheets['sections']['columns'][] = ['label' => 'Plus Fees', 'field' => 'plus_fees', 'format'=>'currency', 'footer'=>'sum'];
        $sheets['categories']['columns'][] = ['label' => 'Plus #', 'field' => 'plus_reg', 'footer'=>'sum'];
        $sheets['categories']['columns'][] = ['label' => 'Plus Fees', 'field' => 'plus_fees', 'format'=>'currency', 'footer'=>'sum'];
        $sheets['classes']['columns'][] = ['label' => 'Plus #', 'field' => 'plus_reg', 'footer'=>'sum'];
        $sheets['classes']['columns'][] = ['label' => 'Plus Fee', 'field' => 'plus_fee', 'format'=>'currency'];
        $sheets['classes']['columns'][] = ['label' => 'Plus Fees', 'field' => 'plus_fees', 'format'=>'currency', 'footer'=>'sum'];
    }

    // Add totals columns
    if( ($festival['flags']&0x16) > 0 ) {
        $sheets['sections']['columns'][] = ['label' => 'Total #', 'field' => 'total_reg', 'footer'=>'sum'];
        $sheets['sections']['columns'][] = ['label' => 'Total Fees', 'field' => 'total_fees', 'format'=>'currency', 'footer'=>'sum'];
        $sheets['categories']['columns'][] = ['label' => 'Total #', 'field' => 'total_reg', 'footer'=>'sum'];
        $sheets['categories']['columns'][] = ['label' => 'Total Fees', 'field' => 'total_fees', 'format'=>'currency', 'footer'=>'sum'];
        $sheets['classes']['columns'][] = ['label' => 'Total #', 'field' => 'total_reg', 'footer'=>'sum'];
        $sheets['classes']['columns'][] = ['label' => 'Total Fees', 'field' => 'total_fees', 'format'=>'currency', 'footer'=>'sum'];
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'excelGenerate');
    return ciniki_core_excelGenerate($ciniki, $args['tnid'], [
        'sheets' => $sheets,
        'download' => 'yes',
        'filename' => 'Syllabus Statistics.xlsx'
        ]);
}
?>
