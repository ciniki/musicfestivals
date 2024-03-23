<?php
//
// Description
// -----------
// This function will generate an excel file of the registration results.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_templates_resultsExcel(&$ciniki, $tnid, $args) {

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

    $filename = 'Results';

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the festival
    //
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.start_date, "
        . "ciniki_musicfestivals.end_date, "
        . "ciniki_musicfestivals.primary_image_id, "
        . "ciniki_musicfestivals.description, "
        . "ciniki_musicfestivals.document_logo_id, "
        . "ciniki_musicfestivals.document_header_msg, "
        . "ciniki_musicfestivals.document_footer_msg "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.651', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.652', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the settings for the festival
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.195', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    foreach($rc['settings'] as $k => $v) {
        $festival[$k] = $v;
    }

    //
    // Get the list of registration
    //
    $strsql = "SELECT ssections.id, "
        . "ssections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "timeslots.runsheet_notes, "
        . "ssections.name, "
        . "registrations.id AS registration_id, "
        . "registrations.participation, "
        . "registrations.timeslot_sequence, "
        . "registrations.display_name, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
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
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "registrations.provincials_position, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.provincials_code, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    $strsql .= "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY ssections.sequence, ssections.name, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('id', 'name'=>'section_name'),
            ),
        array('container'=>'registrations', 'fname'=>'registration_id', 
            'fields'=>array('id'=>'registration_id', 'display_name', 'participation',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 
                'mark', 'placement', 'level', 'provincials_code', 'provincials_position',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.39', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();
   
    //
    // Export to excel
    //
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    $teachers = array();

    $num = 0;
    foreach($sections as $section) {
        if( !isset($section['registrations']) || count($section['registrations']) == 0 ) {
            continue;
        }
        if( $num > 0 ) {
            $objPHPExcelWorksheet = $objPHPExcel->createSheet($num);
        }
        $title = str_split($section['name'], 31);
        $objPHPExcelWorksheet->setTitle(preg_replace("/[\/\:\?]/", "-", $title[0]));

        $col = 0;
        $row = 1;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Name', false);
        if( isset($festival['comments-mark-ui']) && $festival['comments-mark-ui'] == 'yes' ) {
            if( isset($festival['comments-mark-label']) && $festival['comments-mark-label'] != '' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $festival['comments-mark-label'], false);
            } else {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Mark', false);
            }
        }
        if( isset($festival['comments-placement-ui']) && $festival['comments-placement-ui'] == 'yes' ) {
            if( isset($festival['comments-placement-label']) && $festival['comments-placement-label'] != '' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $festival['comments-placement-label'], false);
            } else {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Placement', false);
            }
        }
        if( isset($festival['comments-level-ui']) && $festival['comments-level-ui'] == 'yes' ) {
            if( isset($festival['comments-level-label']) && $festival['comments-level-label'] != '' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $festival['comments-level-label'], false);
            } else {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Level', false);
            }
        }
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Code', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Category', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Class', false);
        if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Provincial Class', false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Position', false);
        }

        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Titles', false);

        $objPHPExcelWorksheet->getStyle('A1:G1')->getFont()->setBold(true);
        $row++;
        
        //
        // Add the registrations
        //
        foreach($section['registrations'] as $registration) {
            $col = 0;
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['display_name'], false);
            if( isset($festival['comments-mark-ui']) && $festival['comments-mark-ui'] == 'yes' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['mark'], false);
            }
            if( isset($festival['comments-placement-ui']) && $festival['comments-placement-ui'] == 'yes' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['placement'], false);
            }
            if( isset($festival['comments-level-ui']) && $festival['comments-level-ui'] == 'yes' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['level'], false);
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['class_code'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['category_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['class_name'], false);
            if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['provincials_code'], false);
                if( $registration['provincials_position'] == 0 ) {
                    $registration['provincials_position'] = '';
                } elseif( $registration['provincials_position'] == 1 ) {
                    $registration['provincials_position'] = '1st Recommendation';
                } elseif( $registration['provincials_position'] == 2 ) {
                    $registration['provincials_position'] = '2nd Recommendation';
                } elseif( $registration['provincials_position'] == 3 ) {
                    $registration['provincials_position'] = '3rd Recommendation';
                } elseif( $registration['provincials_position'] == 101 ) {
                    $registration['provincials_position'] = '1st Alternate';
                } elseif( $registration['provincials_position'] == 102 ) {
                    $registration['provincials_position'] = '2nd Alternate';
                } elseif( $registration['provincials_position'] == 103 ) {
                    $registration['provincials_position'] = '3rd Alternate';
                }
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['provincials_position'], false);
            }
            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $registration);
            if( isset($rc['titles']) ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rc['titles'], false);
            }
            $row++;
        }

        for($i = 0; $i < $col; $i++) {
            $objPHPExcelWorksheet->getColumnDimension(chr($i+65))->setAutoSize(true);
        }
        $objPHPExcelWorksheet->freezePaneByColumnAndRow(0, 2);
        $num++;
    }

    return array('stat'=>'ok', 'excel'=>$objPHPExcel, 'filename'=>$filename);
}
?>
