<?php
//
// Description
// ===========
// This method will produce a Word document of the registrations.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_classRegistrationsWord(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load the list of teachers
    //
    $strsql = "SELECT teachers.id, "
        . "teachers.display_name "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_customers AS teachers ON ("
            . "registrations.teacher_customer_id = teachers.id "
            . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'teachers');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.879', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
    }
    $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT classes.id AS class_id, "
        . "classes.sequence AS cla_seq, "
        . "categories.sequence AS cat_seq, "
        . "categories.name AS category_name, "
        . "sections.id AS section_id, "
        . "sections.sequence AS sec_seq, "
        . "sections.name AS section_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id AS reg_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.movements1, "
        . "registrations.composer1, "
        . "registrations.perf_time1, "
        . "registrations.title2, "
        . "registrations.movements2, "
        . "registrations.composer2, "
        . "registrations.perf_time2, "
        . "registrations.title3, "
        . "registrations.movements3, "
        . "registrations.composer3, "
        . "registrations.perf_time3, "
        . "registrations.title4, "
        . "registrations.movements4, "
        . "registrations.composer4, "
        . "registrations.perf_time4, "
        . "registrations.title5, "
        . "registrations.movements5, "
        . "registrations.composer5, "
        . "registrations.perf_time5, "
        . "registrations.title6, "
        . "registrations.movements6, "
        . "registrations.composer6, "
        . "registrations.perf_time6, "
        . "registrations.title7, "
        . "registrations.movements7, "
        . "registrations.composer7, "
        . "registrations.perf_time7, "
        . "registrations.title8, "
        . "registrations.movements8, "
        . "registrations.composer8, "
        . "registrations.perf_time8, "
        . "registrations.participation, "
        . "registrations.notes, "
        . "registrations.internal_notes, "
        . "competitors.id AS competitor_id, "
        . "competitors.ctype, "
        . "competitors.city AS competitor_city, "
        . "competitors.province AS competitor_province, "
        . "competitors.num_people, "
        . "competitors.notes AS competitor_notes "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id ";
//        if( isset($args['section_id']) && $args['section_id'] > 0 ) {
//            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
//        }
        $strsql .= "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
            . "(registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sec_seq, cat_seq, cla_seq, class_code, class_name, reg_id, competitor_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'section_id', 
                'cat_seq', 'category_name', 
                'sec_seq', 'section_name',
                'code'=>'class_code', 'name'=>'class_name'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 
                'title1', 'movements1', 'composer1', 'perf_time1', 
                'title2', 'movements2', 'composer2', 'perf_time2', 
                'title3', 'movements3', 'composer3', 'perf_time3', 
                'title4', 'movements4', 'composer4', 'perf_time4', 
                'title5', 'movements5', 'composer5', 'perf_time5', 
                'title6', 'movements6', 'composer6', 'perf_time6', 
                'title7', 'movements7', 'composer7', 'perf_time7', 
                'title8', 'movements8', 'composer8', 'perf_time8', 
                'notes', 'internal_notes',
                'participation', 'teacher_customer_id',
            )),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'ctype', 'num_people',
                'city'=>'competitor_city', 'competitor_province', 'notes'=>'competitor_notes',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    $competitor_classes = [];   // List of competitors and other classes they are part of
    foreach($classes as $cid => $class) {
        $classes[$cid]['num_reg'] = count($class['registrations']);
        $perf_time = 0;
        if( isset($class['registrations']) ) {
            foreach($class['registrations'] AS $rid => $reg) {
                if( isset($reg['internal_notes']) && $reg['internal_notes'] != '' ) {
                    $classes[$cid]['registrations'][$rid]['notes'] .= ($classes[$cid]['registrations'][$rid]['notes'] != '' ? "\n" : '') . $reg['internal_notes'];
                }
                $classes[$cid]['registrations'][$rid]['teacher_name'] = isset($teachers[$reg['teacher_customer_id']]) ? $teachers[$reg['teacher_customer_id']] : '';
                if( isset($reg['teacher2_customer_id']) && isset($teachers[$reg['teacher2_customer_id']]) ) {
                    $classes[$cid]['registrations'][$rid]['teacher_name'] .= ', ' . isset($teachers[$reg['teacher_customer_id']]) ? $teachers[$reg['teacher_customer_id']] : '';
                }
                $classes[$cid]['registrations'][$rid]['num_people'] = '';

                if( isset($reg['competitors']) ) {
                    foreach($reg['competitors'] as $competitor) {
                        if( !isset($competitor_classes[$competitor['id']]) ) {
                            $competitor_classes[$competitor['id']] = [$class['code']];
                        } else {
                            $competitor_classes[$competitor['id']][] = $class['code'];
                        }
                        if( isset($competitor['notes']) && $competitor['notes'] != '' ) {
                            $classes[$cid]['registrations'][$rid]['notes'] .= ($classes[$cid]['registrations'][$rid]['notes'] != '' ? "\n" : '') . $competitor['notes'];
                        }
                        if( $competitor['num_people'] > 0 && $competitor['ctype'] == 50 ) {
                            $classes[$cid]['registrations'][$rid]['num_people'] = $competitor['num_people'];
                        }
                    }
                }
                // FIXME: Merge Titles
                $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg, [
                    'newline' => ', ',
                    'basicnumbers' => 'yes',
                    ]);
                $classes[$cid]['registrations'][$rid]['titles'] = $rc['titles'];
                $classes[$cid]['registrations'][$rid]['perf_time'] = $rc['perf_time'];
                $perf_time += $rc['perf_time_seconds'];
            }
        }
        if( $perf_time > 3600 ) {
            $classes[$cid]['perf_time_str'] = intval($perf_time/3600) . 'h ' . ceil(($perf_time%3600)/60) . 'm';
        } else {
            $classes[$cid]['perf_time_str'] = intval($perf_time/60) . ':' . str_pad(($perf_time%60), 2, '0', STR_PAD_LEFT);
        }
    }

    //
    // Build word document
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/PHPWord/bootstrap.php');

    $PHPWord = new \PhpOffice\PhpWord\PhpWord();
    $PHPWord->addTitleStyle(1, array('bold'=>true, 'size'=>12), array('spaceBefore'=>240, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(2, array('bold'=>true, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(3, array('bold'=>false, 'size'=>14), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addParagraphStyle('pReg', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>60,
        'indentation' => ['left' => 8200, 'hanging' => 8200],
        'tabs' => array(
            new \PhpOffice\PhpWord\Style\Tab('left', 5500),
            new \PhpOffice\PhpWord\Style\Tab('left', 7500),
            new \PhpOffice\PhpWord\Style\Tab('left', 8200),
            )),
    );
    $PHPWord->addParagraphStyle('pTitles', array('align' => 'left', 'spaceAfter' => 0));
    $PHPWord->addFontStyle('fNotes', ['italic'=>true]);
    $PHPWord->addParagraphStyle('pNotes', ['align' => 'left', 'spaceAfter' => 0, 'indentation' => ['left' => 500]]);
    $style_table = array('cellMargin'=>80, 'borderColor'=>'aaaaaa', 'borderSize'=>6);
    $style_header = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'bgColor'=>'dddddd', 'valign'=>'center');
    $style_cell = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'valign'=>'center', 'bgcolor'=>'ffffff');
    $style_header_font = array('bold'=>true, 'spaceAfter'=>20);
    $style_cell_font = array();
    $style_header_pleft = array('align'=>'left');
    $style_header_pright = array('align'=>'right');
    $style_cell_pleft = array('align'=>'left');
    $style_cell_pright = array('align'=>'right');

//    $section = $PHPWord->addSection();
    $section = $PHPWord->addSection([
//        'pageSizeW' => $paper->getWidth(), 
//        'pageSizeH' => $paper->getHeight(), 
        'marginTop' => 400,
        'marginBottom' => 400,
        'marginLeft' => 300,
        'marginRight' => 300,
        'orientation' => 'landscape'
        ]);
    $section->setMarginLeft(5);
    $section->setMarginRight(5);

    foreach($classes as $class) {
        if( isset($args['section_id']) && $class['section_id'] != $args['section_id'] ) {
            continue;
        }
        $section->addTitle(htmlspecialchars("{$class['code']} - {$class['section_name']} - {$class['category_name']} - {$class['name']} ({$class['num_reg']}) [{$class['perf_time_str']}]"), 1);
        if( isset($class['registrations']) ) {
            foreach($class['registrations'] as $reg) {
                $other_classes = '';
                $cities = [];
                if( isset($reg['competitors']) ) {
                    foreach($reg['competitors'] as $competitor) {
                        if( isset($competitor_classes[$competitor['id']]) ) {
                            $other_classes = implode(',', $competitor_classes[$competitor['id']]);
                        }
                        if( $competitor['city'] != '' 
                            && !in_array($competitor['city'], $cities) 
                            ) {
                            $cities[] = $competitor['city'];
                        }
                    }
                }
                $other_classes = preg_replace("/{$class['code']}/", '', $other_classes);
                $other_classes = preg_replace("/,,/", ',', $other_classes);
                $other_classes = preg_replace("/^,/", '', $other_classes);
                $other_classes = preg_replace("/,$/", '', $other_classes);
                if( $other_classes != '' ) {
                    $other_classes = ' (' . $other_classes . ')';
                }
                $city_list = '';
                if( count($cities) > 0 ) {
                    $city_list = ' [' . implode(', ', $cities) . ']';
                }
                $num_people = '';
                if( $reg['num_people'] != '' && $reg['num_people'] > 0 ) {
                    $num_people = ' (' . $reg['num_people'] . ')';
                }
                $line = "{$reg['name']}{$num_people}{$city_list}{$other_classes}\t{$reg['teacher_name']}\t{$reg['perf_time']}\t{$reg['titles']}\n";
                $section->addText(htmlspecialchars($line), null, 'pReg');
//                $section->addText(htmlspecialchars($reg['titles']), null, 'pTitles');
                if( isset($reg['notes']) && $reg['notes'] != '' ) {
                    $section->addText(htmlspecialchars($reg['notes']), 'fNotes', 'pNotes');
                }
//                $section->addTextBreak(1);
            }
        }
        $section->addTextBreak();
    }

    return array('stat'=>'ok', 'word'=>$PHPWord, 'filename'=>'Registrations');
}
?>
