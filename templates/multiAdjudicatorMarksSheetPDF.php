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
function ciniki_musicfestivals_templates_multiAdjudicatorMarksSheetPDF(&$ciniki, $tnid, $args) {

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
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "IF(locations.shortname<>'', locations.shortname, locations.name) AS location_name, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) { // Provincials
        $strsql .= "CONCAT_WS(' ', divisions.division_date, divisions.name, timeslots.slot_time) AS division_sort_key, ";
    } else {
        $strsql .= "CONCAT_WS(' ', divisions.division_date, timeslots.slot_time) AS division_sort_key, ";
    }
    $strsql .= "DATE_FORMAT(divisions.division_date, '%b %d, %Y') AS division_date_text, "
        . "adjudicators.id AS adjudicator_id, "
        . "customers.display_name AS adjudicator_name, ";
    if( isset($festival['runsheets-separate-classes']) && $festival['runsheets-separate-classes'] == 'yes' ) {
        $strsql .= "CONCAT_WS('-', timeslots.id, classes.id) AS timeslot_id, ";
    } else {
        $strsql .= "timeslots.id AS timeslot_id, ";
    }
    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS reg_time_text, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.groupname, "
        . "timeslots.slot_seconds, "
        . "timeslots.slot_time, "
        . "timeslots.start_num, "
        . "timeslots.description, "
        . "timeslots.runsheet_notes, "
        . "registrations.id AS reg_id, "
        . "registrations.status AS reg_status, ";
    if( isset($festival['waiver-name-status']) && $festival['waiver-name-status'] != 'off' ) {
        if( isset($festival['runsheets-include-pronouns']) && $festival['runsheets-include-pronouns'] == 'yes' ) {
            $strsql .= "registrations.pn_private_name AS display_name, ";
        } else {
            $strsql .= "registrations.private_name AS display_name, ";
        }
    } elseif( isset($festival['runsheets-include-pronouns']) && $festival['runsheets-include-pronouns'] == 'yes' ) {
        $strsql .= "registrations.pn_display_name AS display_name, ";
    } else {
        $strsql .= "registrations.display_name, ";
    }
    $strsql .= "registrations.competitor1_id, "
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
        . "registrations.notes, "
        . "registrations.internal_notes, "
        . "registrations.runsheet_notes AS runnote, "
        . "";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) 
        && (!isset($festival['runsheets-accolades-list']) || $festival['runsheets-accolades-list'] == 'yes')
        ) {
        $strsql .= "GROUP_CONCAT(accolades.name SEPARATOR ', ') AS accolade_name, ";
    } else {
        $strsql .= "'' AS accolade_name, ";
    }
    $strsql .= "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
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
            . "arefs.adjudicator_id = adjudicators.id ";
    if( isset($args['adjudicator_id']) && $args['adjudicator_id'] > 0 ) {
        $strsql .= "AND adjudicators.id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' ";
    }
        $strsql .= "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "( "
                . "timeslots.id = registrations.timeslot_id "
                . "OR timeslots.id = registrations.finals_timeslot_id "
                . ") "
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
    if( isset($args['sortorder']) && $args['sortorder'] == 'date' ) {
        $strsql .= "ORDER BY divisions.division_date, ssections.sequence, ssections.name, divisions.division_date, divisions.name, slot_time, adjudicator_name, registrations.timeslot_sequence, class_code, registrations.display_name, registrations.id ";
    } elseif( isset($args['sortorder']) && $args['sortorder'] == 'schedule' ) {
        $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.division_date, divisions.name, slot_time, adjudicator_name, registrations.timeslot_sequence, class_code, registrations.display_name, registrations.id ";
    } else {
        $strsql .= "ORDER BY adjudicator_name, ssections.sequence, ssections.name, divisions.division_date, divisions.name, slot_time, adjudicator_name, registrations.timeslot_sequence, class_code, registrations.display_name, registrations.id ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'ssections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator_name', 
                'sort_key'=>'division_sort_key',
                ),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 
                'location_name', 'sort_key'=>'division_sort_key',
                ),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'groupname', 'start_num',
                'description', 'runsheet_notes', 'slot_time', 'slot_seconds', 
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 'accolade_name',
                ),
            ),
        array('container'=>'adjudicators', 'fname'=>'adjudicator_id', 
            'fields' => array('id'=>'adjudicator_id', 'name'=>'adjudicator_name'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'participation', 'reg_time_text', 
                'status'=>'reg_status',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                'notes', 'internal_notes', 'runsheet_notes'=>'runnote',
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
    $sections = isset($rc['ssections']) ? $rc['ssections'] : array();

    //
    // Filter out live or virtual if requested
    //
    if( isset($args['ipv']) && ($args['ipv'] == 'inperson' || $args['ipv'] == 'virtual') ) {
        foreach($sections as $sid => $section) {
            $section_num_reg = 0;
            if( isset($section['divisions']) ) {
                foreach($section['divisions'] as $did => $division) {
                    if( isset($division['timeslots']) ) {
                        foreach($division['timeslots'] as $tid => $timeslot) {
                            if( isset($timeslot['registrations']) ) {
                                $start_num_reg = count($timeslot['registrations']);
                                foreach($timeslot['registrations'] as $rid => $reg) {
                                    if( $args['ipv'] == 'inperson' && $reg['participation'] != 0 && $reg['participation'] != 2 ) {
                                        unset($timeslot['registrations'][$rid]);
                                        unset($sections[$sid]['divisions'][$sid]['timeslots'][$tid]['registrations'][$rid]);
                                    }
                                    elseif( $args['ipv'] == 'virtual' && $reg['participation'] != 1 && $reg['participation'] != 3 ) {
                                        unset($timeslot['registrations'][$rid]);
                                        unset($sections[$sid]['divisions'][$sid]['timeslots'][$tid]['registrations'][$rid]);
                                    } 
                                    else {
                                        $section_num_reg++;
                                    }
                                }
                                if( $start_num_reg > 0 && count($timeslot['registrations']) == 0 ) {
                                    unset($division['timeslots'][$tid]);
                                    unset($sections[$sid]['divisions'][$sid]['timeslots'][$tid]);
                                }
                            }
                        }
                    } else {
                        unset($sections[$sid]['divisions'][$did]);
                    }
                }
            }
            if( !isset($section['divisions']) || count($section['divisions']) == 0 ) {
                unset($sections[$sid]['divisions']);
            }
            if( $section_num_reg == 0 ) {
                unset($sections[$sid]);
            }
        }
    }

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    if( !class_exists('MYPDF') ) {
        class MYPDF extends TCPDF {
            //Page header
            public $left_margin = 18;
            public $right_margin = 18;
            public $top_margin = 0;
            public $date_time = '';
            public $class_name = '';
            public $adjudicator_name = '';
            public $header_visible = 'yes';
            public $header_image = null;
            public $header_title = '';
            public $header_sub_title = '';
            public $header_msg = '';
            public $header_height = 10;
            public $footer_visible = 'yes';
            public $footer_msg = '';
            public $tenant_details = array();

            public function Header() {
                $w = [45, 90, 45];   //Adjudicator, time, class
                $this->SetFont('helvetica', 'B', 10);
                $ah = $this->getStringHeight($w[2], $this->adjudicator_name);

                $this->SetFont('helvetica', '', 10);
                $h = $this->getStringHeight($w[0], $this->date_time);
                if( $h < $ah ) {
                    $h = $ah;
                }
                if( $this->getStringHeight($w[1], $this->class_name) > $h ) {
                    $h = $this->getStringHeight($w[1], $this->class_name);
                }
                $this->SetFont('helvetica', '', 10);
                $this->Ln(2);
                $this->MultiCell($w[0], 0, $this->date_time, 0, 'L', 0, 0);
                $this->MultiCell($w[1], 0, $this->class_name, 0, 'C', 0, 0);
                $this->SetFont('helvetica', 'B', 10);
                $this->MultiCell($w[2], 0, $this->adjudicator_name, 0, 'R', 0, 1);
            }

            // Page footer
            public function Footer() {
            }
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = '';

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Marks Sheets');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(5);

    // set font
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.1);

    $filename = 'Marks Sheets';

    $newpage = 'no';
    //
    // Go through the sections, divisions and classes
    //
    $wncm = [45, 120, 15];
    $prev_adjudicator = '';
    $num_adjudicator_pages = 0;
    foreach($sections as $section) {
        if( count($sections) == 1 ) {
            $filename .= ' - ' . $section['name'];
        }

        if( !isset($section['divisions']) ) {
            continue;
        }

        //
        // Sort the divisions
        //
        uasort($section['divisions'], function($a, $b) {
            return $a['sort_key'] < $b['sort_key'] ? -1 : 1;
            });

        //
        // Output the divisions
        //
        foreach($section['divisions'] as $division) {
            
            if( !isset($division['timeslots']) ) {
                continue;
            }

            foreach($division['timeslots'] as $timeslot) {
                if( !isset($timeslot['adjudicators']) ) {
                    continue;
                }
                foreach($timeslot['adjudicators'] as $adjudicator) {
                    if( !isset($adjudicator['registrations']) ) {
                        continue;
                    }
                    if( $prev_adjudicator != $adjudicator['name'] && $prev_adjudicator != '' ) {
                        if( ($num_adjudicator_pages%2) > 0 ) {
                            $pdf->AddPage();
                        }
                        $num_adjudicator_pages = 0;
                    } else {
                    }
                    $pdf->date_time = $division['date'] . ' ' . $timeslot['time'];
                    $pdf->class_name = $timeslot['class_code'] . ' - ' . $timeslot['class_name'];
                    $pdf->adjudicator_name = $adjudicator['name'];

                    $pdf->AddPage();
                    $num_adjudicator_pages++;

                    foreach($adjudicator['registrations'] as $reg) {
                        $pdf->Ln(3);
                        // Calc title heights
                        $h = 0;
                        $pdf->SetFont('helvetica', '', 10);
                        for($i = 1; $i <= 8; $i++) {
                            if( trim($reg["title{$i}"]) == '' ) {
                                continue;
                            }
                            $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
                            $reg["title{$i}_merged"] = $rc['title'];
                            $reg["title{$i}_h"] = $pdf->getStringHeight($wncm[1], $reg["title{$i}_merged"]);
                            $h += $reg["title{$i}_h"];
                        }
                        
                        if( ($pdf->GetY() + $h) > $pdf->getPageHeight() - 20 ) {
                            $pdf->AddPage();
                            $num_adjudicator_pages++;
                        }

                        $pdf->SetFont('helvetica', 'B', 10);
                        $nh = $pdf->getStringHeight($wncm[0], $reg['name']);
                        if( $nh > $h ) {
                            $pdf->MultiCell($wncm[0], $nh, $reg['name'], 1, 'L', 0, 0);
                        } else {
                            $pdf->MultiCell($wncm[0], $h, $reg['name'], 1, 'L', 0, 0);
                        }

                        $pdf->SetFont('helvetica', '', 10);
                        for($i = 1; $i <= 8; $i++) {
                            if( trim($reg["title{$i}"]) == '' ) {
                                continue;
                            }
                            if( $i > 1 ) {
                                $pdf->setX($pdf->getX() + $wncm[0]);
                            }
                            $pdf->MultiCell($wncm[1], $reg["title{$i}_h"], $reg["title{$i}_merged"], 1, 'L', 0, 0);
                            $pdf->MultiCell($wncm[2], $reg["title{$i}_h"], '', 1, 'L', 0, 1);
                        }
                    }
                    $prev_adjudicator = $adjudicator['name'];
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
