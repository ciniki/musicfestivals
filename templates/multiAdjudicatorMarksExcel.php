<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
require_once($ciniki['config']['core']['lib_dir'] . '/vendor/autoload.php');

function ciniki_musicfestivals_templates_multiAdjudicatorMarksExcel(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotProcess');

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
    // Load the sections, divisions and adjudicators
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "adjudicators.id AS adjudicator_id, "
        . "customers.display_name AS adjudicator_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
            . "( "
                . "(ssections.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.schedulesection') "
                . "OR (divisions.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.scheduledivision') "
                . ") "
            . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
            . "arefs.adjudicator_id = adjudicators.id "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['scheduledivision_id']) && $args['scheduledivision_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' ";
    }
    $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.name, adjudicator_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'ssections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator_name', 
                ),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 
                ),
            ),
        array('container'=>'adjudicators', 'fname'=>'adjudicator_id', 
            'fields' => array('id'=>'adjudicator_id', 'name'=>'adjudicator_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['ssections']) ? $rc['ssections'] : array();

    //
    // Load the division timeslots and registrations
    //
    $strsql = "SELECT timeslots.sdivision_id AS division_id, "
        . "timeslots.id AS timeslot_id, " 
        . "timeslots.name AS timeslot_name, "
        . "timeslots.start_num, "
        . "timeslots.groupname, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "registrations.id AS reg_id, "
        . "registrations.status AS reg_status, "
        . "registrations.private_name, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
        . "registrations.competitor5_id, "
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
        . "registrations.perf_time1, "
        . "registrations.perf_time2, "
        . "registrations.perf_time3, "
        . "registrations.perf_time4, "
        . "registrations.perf_time5, "
        . "registrations.perf_time6, "
        . "registrations.perf_time7, "
        . "registrations.perf_time8, "
        . "registrations.participation, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
            . ") ";
    $strsql .= "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['scheduledivision_id']) && $args['scheduledivision_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' ";
    }
    $strsql .= "ORDER BY divisions.id, slot_time, registrations.timeslot_sequence, class_code, registrations.private_name, registrations.id ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'groupname', 'start_num',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 'time'=>'slot_time_text',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'private_name', 'participation', 
                'status'=>'reg_status',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 'groupname',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();

    $filename = "Marks Sheet";

    //
    // Generate the excel file
    //
    $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    //
    // Setup grey background
    //
    $greybg = [
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFD0D0D0'],
            ],
        ];
    $yellowbg = [
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFFFFF00'],
            ],
        ];
    $orangebg = [
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFCAAC00'],
            ],
        ];
    $borders = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF555555'],
                ],
            ],
        ];

    $sheet_num = 0;
    foreach($sections as $sid => $section) {
        foreach($section['divisions'] as $did => $division) {
            $cur_col = 1;
            $cur_row = 1;
            if( $sheet_num == 0 ) {
                $spreadsheet = $excel->getActiveSheet();
            } else {
                $spreadsheet = $excel->createSheet();
            }
            $spreadsheet->setTitle($division['name']);

            // Row 1 - Headings
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Participant');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Composer');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Piece');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Adj. 1');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Adj. 2');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Adj. 3');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Average');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Standing');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Gold');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Silver');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Bronze');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Merit');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], '');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Participant');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Standing');
            $spreadsheet->getStyle("A{$cur_row}:L{$cur_row}")->applyFromArray($greybg);
            $spreadsheet->getStyle("N{$cur_row}:O{$cur_row}")->applyFromArray($greybg);
            
            // Row 2 - mark thresholds
            $cur_row++;
            $cur_col = 8;
            $spreadsheet->setCellValue([$cur_col++, $cur_row], '2');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], '86.5');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], '83.5');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], '79.5');
            $spreadsheet->setCellValue([$cur_col++, $cur_row], '74.5');
            $spreadsheet->getStyle("H{$cur_row}:L{$cur_row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getStyle("H{$cur_row}:L{$cur_row}")->applyFromArray($yellowbg);

            //
            // Go through the timeslots
            //
            $num_reg = 0; 
            foreach($divisions[$division['id']]['timeslots'] as $timeslot) {
                if( !isset($timeslot['registrations']) || count($timeslot['registrations']) == 0 ) {
                    continue;
                }
                $cur_row++;
                $cur_col = 1;
                $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Start: ' . $timeslot['time']);
                $spreadsheet->setCellValue([$cur_col++, $cur_row], $timeslot['class_name'] . ($timeslot['groupname'] != '' ? ' - ' . $timeslot['groupname'] : ''));
                $spreadsheet->getStyle("A{$cur_row}:B{$cur_row}")->getFont()->setBold(true);
                $spreadsheet->getStyle("A{$cur_row}")->getFont()->setBold(true);
                $spreadsheet->getStyle("A{$cur_row}:L{$cur_row}")->applyFromArray($greybg);

                $cur_row++;
                $cur_col = 1;
                $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Check-in');
                $spreadsheet->getStyle("A{$cur_row}:L{$cur_row}")->applyFromArray($greybg);
                    
                $cur_row++;
                $cur_col = 1;
                $spreadsheet->setCellValue([$cur_col++, $cur_row], 'Adjudicators');
                $spreadsheet->getStyle("A{$cur_row}:L{$cur_row}")->applyFromArray($greybg);
                //
                // Add adjudicators
                //
                $cur_col = 4;
                foreach($division['adjudicators'] as $adjudicator) {
                    $words = explode(" ", $adjudicator['name']);
                    $initials = '';
                    foreach($words as $word) {
                        if( $word[0] != '' && $word[0] != ' ' ) {
                            $initials .= $word[0];
                        }
                    }
                    $spreadsheet->setCellValue([$cur_col++, $cur_row], $initials);
                }
                $spreadsheet->getStyle("D{$cur_row}:F{$cur_row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $cur_row++;
                $cur_row++;
                $cur_col = 1;
                foreach($timeslot['registrations'] as $reg) {
                    $num_reg++;
                    $spreadsheet->setCellValue([$cur_col++, $cur_row], $reg['name']);
                    for($i = 1; $i <= 8; $i++) {
                        if( $reg["title{$i}"] != '' ) {
                            $spreadsheet->setCellValue([$cur_col++, $cur_row], $reg["composer{$i}"]);
                            $spreadsheet->setCellValue([$cur_col++, $cur_row], $reg["title{$i}"]);
                            $cur_col+=3;
                            $spreadsheet->setCellValue([$cur_col++, $cur_row], "=IF(COUNT(D{$cur_row}:F{$cur_row})>0,ROUND(AVERAGE(D{$cur_row}:F{$cur_row}),2),\"\")");
                            $spreadsheet->getStyle("D{$cur_row}:L{$cur_row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $spreadsheet->getStyle("D{$cur_row}:F{$cur_row}")->applyFromArray($orangebg);
                            if( $i == 1 ) {
                                $avg_row = $cur_row;
                            }
                            $last_row = $cur_row;
                            $cur_row++;
                            $cur_col = 2;
                        }
                    }
                    $spreadsheet->setCellValue([8, $avg_row], "=IF(COUNT(G{$avg_row}:G{$last_row})>=H2,ROUND(AVERAGE(G{$avg_row}:G{$last_row}),2), \"\")");
//                    =IF(COUNT($G$7:$G$8)>=2,AVERAGE($G$7:$G$8),"")
                    $spreadsheet->setCellValue([9, $avg_row], "=IF(AND(H{$avg_row}<>\"\",H{$avg_row}>=I2),\"G\",\"\")");
                    $spreadsheet->setCellValue([10, $avg_row], "=IF(AND(H{$avg_row}>=J2,H{$avg_row}<I2),\"S\",\"\")");
                    $spreadsheet->setCellValue([11, $avg_row], "=IF(AND(H{$avg_row}>=K2,H{$avg_row}<J2),\"B\",\"\")");
                    $spreadsheet->setCellValue([12, $avg_row], "=IF(AND(H{$avg_row}>=L2,H{$avg_row}<K2),\"M\",\"\")");
                    $cur_row+=2;
                    $cur_col = 1;
                }
            }
//            $spreadsheet->setCellValue([14,2], "=SORT(FILTER(CHOOSECOLS(A2:H{$cur_row},1,8), H2:H{$cur_row}<>\"\"),2,-1)");
//            $spreadsheet->setCellValue([14,2], "=IF(D7<>\"\",SORT(FILTER(CHOOSECOLS(A2:H{$cur_row},1,8), H2:H{$cur_row}<>\"\"),2,-1),\"\")");
            // =ARRAY_CONSTRAIN(ARRAYFORMULA(SORT(FILTER(CHOOSECOLS(A2:H300,1,8), H2:H300<>""),2,0)), 300, 8)
            $spreadsheet->setCellValue([14,2], "ARRAY_CONSTRAIN(ARRAYFORMULA(SORT(FILTER(CHOOSECOLS(A7:H{$last_row},1,8), H7:H{$last_row}<>\"\"),2,0)), {$last_row}, 8)");
//            $spreadsheet->setCellValue([14,2], "=SORT(FILTER(A2:H{$cur_row}, H2:H{$cur_row}<>\"\"),1,-1)");
            
            $cur_row++;
            $cur_col = 2;
            $spreadsheet->setCellValue([$cur_col++, $cur_row], "Total Count");
            $spreadsheet->setCellValue([$cur_col++, $cur_row], $num_reg);

            $spreadsheet->getColumnDimension("A")->setAutoSize(true);
            $spreadsheet->getColumnDimension("B")->setAutoSize(true);
            $spreadsheet->getColumnDimension("C")->setAutoSize(true);
            $spreadsheet->getStyle("A1:L{$cur_row}")->applyFromArray($borders);

            $sheet_num++;
        }
    }

    return array('stat'=>'ok', 'excel'=>$excel, 'filename'=>$filename);
}
?>
