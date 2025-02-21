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
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Get the list of teachers
    //
    $strsql = "SELECT customers.id, "
        . "customers.display_name AS name, "
        . "emails.id AS email_id, "
        . "emails.email "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "("
                . "registrations.teacher_customer_id = customers.id "
                . "OR registrations.teacher2_customer_id = customers.id "
                . ") "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.teacher_customer_id > 0 "  
        . "AND registrations.timeslot_id > 0 "  // Scheduled registrations only
        . "AND registrations.participation <> 1 "   // Live only
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY customers.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name')),
        array('container'=>'emails', 'fname'=>'email_id', 'fields'=>array('email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.915', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
    }
    $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();
//    error_log(print_r($teachers,true));

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
        . "registrations.teacher_customer_id, "
        . "registrations.teacher2_customer_id, "
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
        . "registrations.provincials_status AS provincials_status_text, "
        . "registrations.provincials_position AS provincials_position_text, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.provincials_code, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name, "
        . "competitors.id AS competitor_id, "
        . "competitors.name AS competitor_name, "
        . "competitors.email, "
        . "competitors.etransfer_email "
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
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
            . "( "
                . "registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['provincials_recommendations']) && $args['provincials_recommendations'] == 'yes' ) {
        $strsql .= "AND registrations.provincials_position > 0 ";
    }
    $strsql .= "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['provincials_recommendations']) && $args['provincials_recommendations'] == 'yes' ) {
        $strsql .= "ORDER BY ssections.sequence, ssections.name, registrations.display_name ";
    } else {
        $strsql .= "ORDER BY ssections.sequence, ssections.name, class_code, registrations.provincials_position ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('id', 'name'=>'section_name'),
            ),
        array('container'=>'registrations', 'fname'=>'registration_id', 
            'fields'=>array('id'=>'registration_id', 'display_name', 'participation',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id',
                'teacher_customer_id', 'teacher2_customer_id',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 
                'mark', 'placement', 'level', 'provincials_code', 'provincials_status_text', 'provincials_position_text',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                ),
            'maps'=>array(
                'provincials_status_text'=>$maps['registration']['provincials_status'],
                'provincials_position_text'=>$maps['registration']['provincials_position'],
                ),
            ),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'email', 'etransfer_email',),
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
    $objPHPExcelWorksheet->setTitle('Recommendations');

    $num = 0;
    $col = 0;
    $row = 1;
    foreach($sections as $section) {
        if( !isset($section['registrations']) || count($section['registrations']) == 0 ) {
            continue;
        }
        if( $num > 0 && (!isset($args['provincials_recommendations']) || $args['provincials_recommendations'] != 'yes') ) {
            $objPHPExcelWorksheet = $objPHPExcel->createSheet($num);
        }

        if( $num == 0 || (!isset($args['provincials_recommendations']) || $args['provincials_recommendations'] != 'yes') ) {
            if( !isset($args['provincials_recommendations']) || $args['provincials_recommendations'] != 'yes' ) {
                $title = str_split($section['name'], 31);
                $objPHPExcelWorksheet->setTitle(preg_replace("/[\/\:\?]/", "-", $title[0]));
            }
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
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Status', false);
            }

            if( !isset($args['provincials_recommendations']) || $args['provincials_recommendations'] != 'yes' ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Titles', false);
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher Emails', false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor Emails', false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor etransfer Emails', false);

            $objPHPExcelWorksheet->getStyle('A1:M1')->getFont()->setBold(true);
            $row++;
        }
        
        //
        // Add the registrations
        //
        foreach($section['registrations'] as $registration) {
            $registration['teacher_emails'] = '';
            $registration['emails'] = '';
            $registration['etransfer_emails'] = '';
            foreach($registration['competitors'] as $competitor) {
                if( !str_contains($registration['emails'], $competitor['email']) ) {
                    $registration['emails'] .= ($registration['emails'] != '' ? ', ' : '') . $competitor['email'];
                }
                if( !str_contains($registration['etransfer_emails'], $competitor['etransfer_email']) ) {
                    $registration['etransfer_emails'] .= ($registration['etransfer_emails'] != '' ? ', ' : '') . $competitor['etransfer_email'];
                }
            }
            if( $registration['teacher_customer_id'] > 0
                && isset($teachers[$registration['teacher_customer_id']]['emails']) 
                ) {
                foreach($teachers[$registration['teacher_customer_id']]['emails'] as $email) {
                    if( !str_contains($registration['teacher_emails'], $email['email']) ) {
                        $registration['teacher_emails'] .= ($registration['teacher_emails'] != '' ? ',' : '') 
                            . $email['email'];
                    }
                }
            }
            if( $registration['teacher2_customer_id'] > 0
                && isset($teachers[$registration['teacher2_customer_id']]['emails']) 
                && $teachers[$registration['teacher2_customer_id']]['emails'] != ''
                ) {
                foreach($teachers[$registration['teacher2_customer_id']]['emails'] as $email) {
                    if( !str_contains($registration['teacher_emails'], $email['email']) ) {
                        $registration['teacher_emails'] .= ($registration['teacher_emails'] != '' ? ',' : '') 
                            . $email['email'];
                    }
                }
            }
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
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['provincials_position_text'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['provincials_status_text'], false);
            }
            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $registration);
            if( !isset($args['provincials_recommendations']) || $args['provincials_recommendations'] != 'yes' ) {
                if( isset($rc['titles']) ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rc['titles'], false);
                } else {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
                }
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['teacher_emails'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['emails'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['etransfer_emails'], false);
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
