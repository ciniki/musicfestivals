<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_runsheetsPDF(&$ciniki, $tnid, $args) {

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

    if( isset($festival['runsheets-page-orientation']) && $festival['runsheets-page-orientation'] == 'landscape' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'compactRunSheetsPDF');
        return ciniki_musicfestivals_templates_compactRunSheetsPDF($ciniki, $tnid, $args);
    }

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "GROUP_CONCAT(DISTINCT customers.display_name SEPARATOR ', ') AS adjudicator_name, "
        . "COUNT(adjudicators.id) AS num_adjudicators, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "IF(locations.shortname<>'', locations.shortname, locations.name) AS location_name, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) { // Provincials
        $strsql .= "CONCAT_WS(' ', divisions.division_date, divisions.name, timeslots.slot_time) AS division_sort_key, ";
    } else {
        $strsql .= "CONCAT_WS(' ', divisions.division_date, timeslots.slot_time) AS division_sort_key, ";
    }
    $strsql .= "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, ";
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
        $strsql .= "GROUP_CONCAT(accolades.name SEPARATOR '::') AS accolade_name, ";
    } else {
        $strsql .= "'' AS accolade_name, ";
    }
    $strsql .= "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
            . "( "
                . "(ssections.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.schedulesection') "
                . "OR (divisions.id = arefs.object_id AND arefs.object = 'ciniki.musicfestivals.scheduledivision') "
                . ") "
            . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
            . "arefs.adjudicator_id = adjudicators.id "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40)
        && (!isset($festival['runsheets-accolades-list']) || $festival['runsheets-accolades-list'] == 'yes')
        ) {
        $strsql .= "LEFT JOIN ciniki_musicfestival_accolade_classes AS tclasses ON ("
            . "classes.id = tclasses.class_id "
            . "AND tclasses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_accolades AS accolades ON ("
            . "tclasses.accolade_id = accolades.id "
            . "AND tclasses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    }
    $strsql .= "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['scheduledivision_id']) && $args['scheduledivision_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' ";
    }
    if( isset($args['adjudicator_id']) && $args['adjudicator_id'] > 0 ) {
        $strsql .= "AND arefs.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' ";
    }
//    if( isset($args['adjudicator_id']) && $args['adjudicator_id'] > 0 ) {
//        $strsql .= "ORDER BY divisions.division_date, divisions.name, slot_time, registrations.timeslot_sequence, class_code, registrations.display_name ";
//    } else {
    $strsql .= "GROUP BY registrations.id ";
    if( isset($args['sortorder']) && $args['sortorder'] == 'date' ) {
        $strsql .= "ORDER BY divisions.division_date, ssections.sequence, ssections.name, divisions.division_date, divisions.name, slot_time, registrations.timeslot_sequence, class_code, registrations.display_name, registrations.id, adjudicator_name ";
    } else {
        $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.division_date, divisions.name, slot_time, registrations.timeslot_sequence, class_code, registrations.display_name, registrations.id, adjudicator_name ";
    }

//    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'ssections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator_name', 
                'sort_key'=>'division_sort_key',
                ),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 
                'location_name', 'adjudicator_name', 'num_adjudicators', 'sort_key'=>'division_sort_key',
                ),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'groupname', 'start_num',
                'description', 'runsheet_notes', 'slot_time', 'slot_seconds', 
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'participation', 'reg_time_text', 
                'status'=>'reg_status',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                'notes', 'internal_notes', 'runsheet_notes'=>'runnote', 'accolade_name',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                ),
            ),
//        array('container'=>'accolades', 'fname'=>'accolade_id', 
//            'fields'=>array('id'=>'accolade_id', 'name'=>'accolade_name'),
//            ),

        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['ssections']) ? $rc['ssections'] : array();

    if( isset($args['adjudicator_id']) && $args['adjudicator_id'] > 0 ) {
        uasort($sections, function($a, $b) {
            return $a['sort_key'] < $b['sort_key'] ? -1 : 1;
            });
    }

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
                                        unset($sections[$sid]['divisions'][$did]['timeslots'][$tid]['registrations'][$rid]);
                                    }
                                    elseif( $args['ipv'] == 'virtual' && $reg['participation'] != 1 && $reg['participation'] != 3 ) {
                                        unset($timeslot['registrations'][$rid]);
                                        unset($sections[$sid]['divisions'][$did]['timeslots'][$tid]['registrations'][$rid]);
                                    } 
                                    else {
                                        $section_num_reg++;
                                    }
                                }
                                if( $start_num_reg > 0 && count($timeslot['registrations']) == 0 ) {
                                    unset($division['timeslots'][$tid]);
                                    unset($sections[$sid]['divisions'][$did]['timeslots'][$tid]);
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
    // Load competitor notes
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.age, "
        . "competitors.city, "
        . "competitors.flags, "
        . "competitors.notes "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'age', 'city', 'flags', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.701', 'msg'=>'Unable to load cnotes', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    if( !class_exists('MYPDF') ) {
        class MYPDF extends TCPDF {
            //Page header
            public $orientation = 'portrait';
            public $layout = 'regular';
            public $page_width = 180;
            public $font_size = 11;
            public $padding = 2;
            public $titles = 'yes';
            public $mark = 'yes';
            public $placement = 'yes';
            public $level = 'no';
            public $advanceto = 'yes';
            public $left_margin = 18;
            public $right_margin = 18;
            public $top_margin = 15;
            public $header_visible = 'yes';
            public $header_image = null;
            public $header_title = '';
            public $header_sub_title = '';
            public $header_msg = '';
            public $header_height = 0;      // The height of the image and address
            public $section_name = '';
            public $division_date = '';
            public $adjudicator = '';
            public $location = '';
            public $footer_visible = 'yes';
            public $footer_msg = '';
            public $tenant_details = array();

            public function Header() {
                if( $this->header_visible == 'yes' && $this->layout == 'compact' ) {
                    $this->Ln(2);
                    $this->SetFont('helvetica', '', 12);
                    if( $this->orientation == 'landscape' ) {
                        $w = [149, 100];
                    } else {
                        $w = [100, 80];
                    }
                    $this->MultiCell($w[0], 0, $this->section_name, 0, 'L', 0, 0);
                    $this->MultiCell($w[1], 0, $this->adjudicator, 0, 'R', 0, 1);
                    $this->MultiCell($w[1], 0, $this->division_date, 0, 'L', 0, 0);
                    $this->MultiCell($w[0], 0, $this->location, 0, 'R', 0, 1);
                }
                elseif( $this->header_visible == 'yes' ) {
                    //
                    // Check if there is an image to be output in the header.   The image
                    // will be displayed in a narrow box if the contact information is to
                    // be displayed as well.  Otherwise, image is scaled to be 100% page width
                    // but only to a maximum height of the header_height (set far below).
                    //
                    $img_width = 0;
                    if( $this->header_image != null ) {
                        $height = $this->header_image->getImageHeight();
                        $width = $this->header_image->getImageWidth();
                        if( $width > 600 ) {
                            $this->header_image->scaleImage(600, 0);
                        }
                        $image_ratio = $width/$height;
                        $img_width = 60;
                        $available_ratio = $img_width/($this->header_height-8);
                        // Check if the ratio of the image will make it too large for the height,
                        // and scaled based on either height or width.
                        if( $available_ratio < $image_ratio ) {
                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150');
//                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 2, '150', '', false, false, 0, true);
                        } else {
                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', 2, '150');
                        }
                    }

                    $this->Ln(8);
                    $this->SetFont('helvetica', 'B', 14);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, 10, '', 0);
                    }
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(180-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(7);

                    $this->SetFont('helvetica', '', 14);
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(7);

                    $this->SetFont('helvetica', '', 12);
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(6);
                } else {
                    // No header
                }
            }

            // Page footer
            public function Footer() {
                // Position at 15 mm from bottom
                if( $this->footer_visible == 'yes' ) {
                    if( $this->orientation == 'landscape' ) {
                        $w = [124.5, 124.5];
                    } else {
                        $w = [90, 90];
                    }
                    $this->SetY(-15);
                    $this->SetFont('helvetica', '', 10);
                    $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
                    $this->SetFont('helvetica', '', 10);
                    $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                } else {
                    // No footer
                }
            }

            public function DivisionHeader($args, $section, $division, $continued) {
                if( $this->layout == 'compact' ) {
                    return;
                }
                $fields = array('section');
                $division['section'] = $section['name'];
                if( $continued == 'yes' ) {
                    $division['section'] .= ' (continued...)';
                }
                // Figure out how much room the division header needs
                $h = 0;
                if( $this->layout == 'compact' ) {
                    $this->SetFont('', 'B', $this->font_size);
                    $this->SetCellPadding($this->padding);
                } else {
                    $this->SetFont('', 'B', '16');
                    $this->SetCellPaddings(3, 3, 3, 3);
                }
                foreach($fields as $field) {
                    if( isset($division[$field]) && $division[$field] != '' ) {
                        $h += $this->getStringHeight($this->page_width, $division[$field]);
                    }
                }
                // Check if enough room for division header and at least 1 timeslot
                if( $this->getY() > $this->getPageHeight() - $h - 80) {
                    $this->AddPage();
                } elseif( $this->getY() > 80 ) {
                    $this->Ln(10); 
                }
                // Output the division header
                $this->SetFillColor(225);
                foreach($fields as $field) {
                    $this->MultiCell($this->page_width, 0, $division[$field], 0, 'C', 1, 1);
                    $this->SetFont('', '', '13');
                }
                $this->SetFillColor(246);
                $this->Ln(4);
            }

            public function ClassHeader($ciniki, $w) {
                $this->SetCellPadding($this->padding);
                $this->SetFont('helvetica', 'B', $this->font_size);
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                    $this->MultiCell($w[0], 0, 'Time', 1, 'C', 1, 0);
                } else {
                    $this->MultiCell($w[0], 0, '#', 1, 'C', 1, 0);
                }
                $this->MultiCell($w[1], 0, 'Name', 1, 'L', 1, 0);
                $col = 2;
                if( $this->mark == 'yes' ) {
                    $this->MultiCell($w[$col], 0, 'Mark', 1, 'C', 1, (($col+1) < count($w) ? 0 : 1));
                    $col++;
                }
                if( $this->placement == 'yes' ) {
                    $this->MultiCell($w[$col], 0, 'Place', 1, 'C', 1, (($col+1) < count($w) ? 0 : 1));
                    $col++;
                }
                if( $this->level == 'yes' ) {
                    $this->MultiCell($w[$col], 0, 'Level', 1, 'C', 1, (($col+1) < count($w) ? 0 : 1));
                    $col++;
                }
                if( $this->advanceto == 'yes' ) {
                    $this->MultiCell($w[$col], 0, 'Advanced to', 1, 'C', 1, 1);
                    $col++;
                }
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
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = '';

    if( isset($args['footerdate']) && $args['footerdate'] == 'yes' ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $pdf->footer_msg = $dt->format("M j, Y");
    }
    if( isset($festival['runsheets-footer-msg']) && $festival['runsheets-footer-msg'] != '' ) {
        $pdf->footer_msg .= ($pdf->footer_msg != '' ? ' - ' : '') . $festival['runsheets-footer-msg'];
    }
    if( isset($festival['runsheets-page-orientation']) && $festival['runsheets-page-orientation'] != '' ) {
        $pdf->orientation = $festival['runsheets-page-orientation'];
    }
    if( isset($festival['runsheets-page-layout']) && $festival['runsheets-page-layout'] != '' ) {
        $pdf->layout = $festival['runsheets-page-layout'];
    }

    if( isset($festival['runsheets-titles']) && $festival['runsheets-titles'] == 'no' ) {
        $pdf->titles = 'no';
    }
    if( isset($festival['runsheets-mark']) && $festival['runsheets-mark'] == 'no' ) {
        $pdf->mark = 'no';
    }
    if( isset($festival['runsheets-placement']) && $festival['runsheets-placement'] == 'no' ) {
        $pdf->placement = 'no';
    }
    if( isset($festival['runsheets-level']) && $festival['runsheets-level'] == 'yes' ) {
        $pdf->level = 'yes';
    }
    if( isset($festival['runsheets-advance-to']) && $festival['runsheets-advance-to'] == 'no' ) {
        $pdf->advanceto = 'no';
    }


    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
    } 
    if( $pdf->layout == 'compact' || $pdf->orientation == 'landscape' ) {
        $pdf->header_height = 18;
        $pdf->padding = 1;
    }

    //
    // Load the header image
    //
    if( isset($festival['document_logo_id']) && $festival['document_logo_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, $festival['document_logo_id'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    //
    // Check if header/footer should be hidden
    //
    if( isset($args['header']) && $args['header'] != 'yes' ) {
        $pdf->header_visible = 'no';
    }
    if( isset($args['footer']) && $args['footer'] != 'yes' ) {
        $pdf->footer_visible = 'no';
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Run Sheet');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'BI', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(220);
    $pdf->SetTextColor(0);
    if( $pdf->layout == 'compact' ) {
        $pdf->SetDrawColor(128);
    } else {
        $pdf->SetDrawColor(200);
    }
    $pdf->SetLineWidth(0.1);

    $filename = 'Run Sheets';

    $newpage = 'no';

    $w = array(10, 170);
    $mpl_size = 15;
    if( $pdf->font_size == 10 ) {
        $mpl_size = 14;
    } elseif( $pdf->font_size == 12 ) {
        $mpl_size = 16;
    }
    if( $pdf->mark == 'yes' ) {
        $w[] = $mpl_size;
        $w[1] -= $mpl_size;
    }
    if( $pdf->placement == 'yes' ) {
        $w[] = $mpl_size;
        $w[1] -= $mpl_size;
    }
    if( $pdf->level == 'yes' ) {
        $w[] = $mpl_size;
        $w[1] -= $mpl_size;
    }
    if( $pdf->advanceto == 'yes' ) {
        $w[] = 40;
        $w[1] -= 40;
    }

    $cw = array(30, 150);   // Class lines
    $tw = array(10, 170);   // Title lines
    $tnw = array(10, 15, 155);   // reg notes lines
    $trw = array(22, 128);   // Accolade lines

    //
    // Go through the sections, divisions and classes
    //
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
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) || count($division['timeslots']) <= 0 
                || (count($division['timeslots']) == 1 && $division['timeslots'][0]['id'] == '')
                ) {
                continue;
            }
            $division['timeslots'] = array_values($division['timeslots']);

            //
            // Start a new section
            //
            $pdf->header_title = $division['date'];
            $pdf->section_name = $section['name'];
            $pdf->division_date = $division['date'];
            if( $division['num_adjudicators'] > 1 ) {
                $pdf->header_sub_title = 'Adjudicators: ' . $division['adjudicator_name'];
                $pdf->adjudicator = 'Adjudicators: ' . $division['adjudicator_name'];
            } else {
                $pdf->header_sub_title = 'Adjudicator: ' . $division['adjudicator_name'];
                $pdf->adjudicator = 'Adjudicators: ' . $division['adjudicator_name'];
            }
            if( $division['location_name'] != '' ) {
                $pdf->header_msg = 'Location: ' . $division['location_name'];
                $pdf->location = 'Location: ' . $division['location_name'];
            }
            if( $newpage == 'no' ) {
                $pdf->AddPage();
                if( $pdf->layout == 'compact' ) {
                    $pdf->ClassHeader($ciniki, $w);
                }
            }
            $newpage = 'no';

            //
            // Setup the division header
            //
            $pdf->DivisionHeader($args, $section, $division, 'no');
            $pdf->SetFont('', '', '12');

            //
            // Output the timeslots
            //
            $fill = 0;
            $border = 'T';
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                $w[0] = 25;
                $w[1] = $w[1] - 15;
                $tw[0] = 25;
                $tw[1] = $tw[1] - 15;
                $tnw[0] = 25;
                $tnw[2] = $tnw[2] - 15;
            }
            $prev_time = '';
            $num_timeslots = count($division['timeslots']);
            $cur_timeslot = 0;
            foreach($division['timeslots'] as $tid => $timeslot) {
                $cur_timeslot++;
                $rc = ciniki_musicfestivals_scheduleTimeslotProcess($ciniki, $tnid, $timeslot, [
                    'festival' => $festival,
                    ]);
                $division['timeslots'][$tid] = $timeslot;
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
                    && $timeslot['name'] == '' 
                    ) {
                    $name = $division['name'];
                    //$name = $timeslot['class_code'] . ' - ' . $timeslot['class_name'] . ' - ' . $timeslot['name'];
                } elseif( $timeslot['name'] == '' ) {
                    // FIXME: Add check for joined section/category/class
                    if( isset($festival['runsheets-class-format']) 
                        && $festival['runsheets-class-format'] == 'code-section-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['syllabus_section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } elseif( isset($festival['runsheets-class-format']) 
                        && $festival['runsheets-class-format'] == 'code-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } else {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
                    }
                    // $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name'];
                } elseif( (!isset($timeslot['registrations']) || count($timeslot['registrations']) == 0)
                    && isset($festival['runsheets-class-format']) 
                    && ($festival['runsheets-class-format'] == 'code-section-category-class'
                        || $festival['runsheets-class-format'] == 'code-category-class') 
                    ) {
                    $name = $division['name'] . ' - ' . $timeslot['name'];
                } else {
                    $name = $timeslot['name'];
                }
                $time = $timeslot['time'];
                if( $prev_time == $time ) {
                    $time = '';
                } else {
                    $prev_time = $time;
                }

                if( isset($festival['runsheets-separate-classes']) && $festival['runsheets-separate-classes'] == 'yes' 
                    && $timeslot['class_code'] != '' 
                    ) {
                    if( isset($festival['runsheets-class-format']) 
                        && $festival['runsheets-class-format'] == 'code-section-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['syllabus_section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } elseif( isset($festival['runsheets-class-format']) 
                        && $festival['runsheets-class-format'] == 'code-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } else {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
                    }
                }

                //
                // Check if groupname should be added
                //
                if( isset($timeslot['groupname']) && $timeslot['groupname'] != '' ) {
                    $name .= ' - ' . $timeslot['groupname'];
                }

                //
                // Check height required
                //
                if( $pdf->layout == 'compact' ) {
                    $pdf->SetFont('', '', '14');
                } else {
                    $pdf->SetFont('', 'B', '14');
                }
                $pdf->SetCellPadding($pdf->padding);
                $h = $pdf->getStringHeight($cw[1], $name); 
                if( isset($timeslot['runsheet_notes']) && $timeslot['runsheet_notes'] != '' ) {
                    $pdf->SetFont('', '', 12);
                    $h += $pdf->getStringHeight($cw[1], $timeslot['runsheet_notes']); 
                }
                if( isset($festival['runsheets-timeslot-description']) && $festival['runsheets-timeslot-description'] == 'yes'
                    && isset($timeslot['description']) && $timeslot['description'] != '' 
                    ) {
                    $pdf->SetFont('', '', 12);
                    $h += $pdf->getStringHeight($cw[1], $timeslot['description']); 
                }
                if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                    $timeslot['registrations'] = array_values($timeslot['registrations']);
                    $pdf->SetFont('', 'B', 12);
                    $h += $pdf->getStringHeight($w[1], $timeslot['registrations'][0]['name']);
                    $pdf->SetFont('', '');
                    $pdf->SetCellPadding($pdf->padding);
                    $timeslot['accolades'] = [];
                    // FIXME: add height of titles
                    foreach($timeslot['registrations'] as $rid => $reg) {
                        $extra_info = '';
                        for($i = 1; $i <= 5; $i++) {
                            $info = '';
                            if( isset($festival['waiver-photo-status']) && $festival['waiver-photo-status'] != 'no'
                                && isset($competitors[$reg["competitor{$i}_id"]]['flags']) 
                                && ($competitors[$reg["competitor{$i}_id"]]['flags']&0x02) == 0 
                                ) {
                                $info .= "**NO PHOTOS**";
                            }
                            if( isset($festival['runsheets-competitor-age']) && $festival['runsheets-competitor-age'] == 'yes'
                                && isset($competitors[$reg["competitor{$i}_id"]]['age']) 
                                && $competitors[$reg["competitor{$i}_id"]]['age'] != ''
                                ) {
                                $info .= ($info != '' ? '/' : '') . $competitors[$reg["competitor{$i}_id"]]['age'];
                            }
                            if( isset($festival['runsheets-competitor-city']) && $festival['runsheets-competitor-city'] == 'yes'
                                && isset($competitors[$reg["competitor{$i}_id"]]['city']) 
                                && $competitors[$reg["competitor{$i}_id"]]['city'] != ''
                                ) {
                                $info .= ($info != '' ? '/' : '') . $competitors[$reg["competitor{$i}_id"]]['city'];
                            }
                            if( $info != '' ) {
                                $extra_info .= ($extra_info != '' ? ', ' : '') . $info;
                            }
                        }
                        if( $extra_info != '' ) {
                            $timeslot['registrations'][$rid]['name'] .= ' [' . $extra_info . ']';
                        }
                        if( $reg["status"] == 75 ) {
                            $timeslot['registrations'][$rid]['name'] = 'Withdrawn';
                        }
                        
                        for($i = 1; $i <= 8; $i++) {
                            if( $reg["title{$i}"] != '' ) {
                                if( $reg["status"] == 75 ) {
                                    $timeslot['registrations'][$rid]["title{$i}"] = '';
                                } else {
                                    $timeslot['registrations'][$rid]['last_title'] = $i;
                                    $perf_time = '??';
                                    if( $reg["perf_time{$i}"] != '' && is_numeric($reg["perf_time{$i}"]) ) {
                                        $perf_time = intval($reg["perf_time{$i}"]/60) 
                                            . ':' 
                                            . str_pad(($reg["perf_time{$i}"]%60), 2, '0', STR_PAD_LEFT);
                                    }
                                    $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
                                    if( isset($rc['title']) ) {
                                        if( !isset($festival['runsheets-perftime-show']) 
                                            || $festival['runsheets-perftime-show'] == 'yes'
                                            ) {
                                            $timeslot['registrations'][$rid]["title{$i}"] = "- [{$perf_time}] " . $rc['title'];
                                        } else {
                                            $timeslot['registrations'][$rid]["title{$i}"] = $rc['title'];
                                        }
                                    }
                                    if( $pdf->titles == 'yes' ) {
                                        $h += $pdf->getStringHeight($tw[1], $timeslot['registrations'][$rid]["title{$i}"]);
                                    } else {
//                                        $h += 2;
                                    }
                                }
                            }
                        }
                        //
                        // Setup the notes for the registration
                        //
                        $notes = '';
                        if( isset($festival['runsheets-registration-runnotes']) && $festival['runsheets-registration-runnotes'] == 'yes'
                            && $reg['runsheet_notes'] != ''
                            ) {
                            $notes .= ($notes != '' ? "\n" : '') . $reg['runsheet_notes'];
                        }
                        if( isset($festival['runsheets-internal-notes']) && $festival['runsheets-internal-notes'] == 'yes'
                            && $reg['internal_notes'] != ''
                            ) {
                            $notes .= ($notes != '' ? "\n" : '') . $reg['internal_notes'];
                        }
                        if( isset($festival['runsheets-registration-notes']) && $festival['runsheets-registration-notes'] == 'yes'
                            && $reg['notes'] != ''
                            ) {
                            $notes .= ($notes != '' ? "\n" : '') . $reg['notes'];
                        }
                        for($i = 1; $i <= 5; $i++ ) {
                            if( isset($festival['runsheets-competitor-notes']) && $festival['runsheets-competitor-notes'] == 'yes'
                                && isset($competitors[$reg["competitor{$i}_id"]]['notes']) 
                                && $competitors[$reg["competitor{$i}_id"]]['notes'] != '' 
                                ) {
                                $notes .= ($notes != '' ? "\n" : '') . $competitors[$reg["competitor{$i}_id"]]['notes'];
                            }
                        }
                        $timeslot['registrations'][$rid]['combined_notes'] = $notes;
                        if( $notes != '' ) {
                            $pdf->SetCellPadding($pdf->padding);
                            $pdf->SetFont('', '', '11');
                            $h += $pdf->getStringHeight($tnw[2], $notes);
                        }
                        if( isset($reg['accolade_name']) && $reg['accolade_name'] != '' ) {
                            $accolades = explode('::', $reg['accolade_name']);
                            foreach($accolades as $accolade) {
                                if( !in_array($accolade, $timeslot['accolades']) ) {
                                    $timeslot['accolades'][] = $accolade;
                                }
                            }
                        }
                    }
                } else {
                    $h += 5;
                }

                if( isset($timeslot['accolades']) && count($timeslot['accolades']) > 0 ) {
                    sort($timeslot['accolades']);
                    $timeslot['accolade_names'] = implode(', ', $timeslot['accolades']);
                    $h += $pdf->getStringHeight($trw[1], $timeslot['accolade_names']);
                }

                $continued = 'no';
                if( $pdf->GetY() > 70 && $pdf->GetY() > $pdf->getPageHeight() - $h - 35) {
                    if( $pdf->layout == 'compact' ) {
                        $pdf->SetFont('', '');
                    } else {
                        $pdf->SetFont('', 'B');
                    }
                    if( $time == '' ) {
                        $pdf->SetFont('', 'BI', $pdf->font_size);
                        $pdf->setCellPadding(0);
                        $pdf->MultiCell(180, 0, '*** continued on next page ***', 0, 'C', 0, 0);
                        $continued = 'yes';
                    }
                    $pdf->AddPage();
                    // Removed continued from division header so it will look better with split timeslots.
                    //$pdf->DivisionHeader($args, $section, $division, 'yes');
                    if( $pdf->layout == 'compact' ) {
                        $pdf->ClassHeader($ciniki, $w);
                    } else {
                        $pdf->DivisionHeader($args, $section, $division, 'no');
                    }
                    $pdf->SetFont('', '', '12');
                }

                if( $pdf->layout == 'compact' ) {
                    $pdf->SetFont('', '', $pdf->font_size);
                } else {
                    $pdf->SetFont('', 'B', '14');
                }
                $pdf->SetCellPaddings(0, 1, 0, 0);
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) 
                    && isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 
                    ) {
                    $pdf->MultiCell($cw[0] + $cw[1], 0, $name, 0, 'L', 0, 1);
                } else {
                    if( $continued == 'yes' ) {
                        $pdf->SetFont('', 'BI', $pdf->font_size);
                        $pdf->MultiCell(180, 0, '*** continued from previous page ***', 0, 'C', 0, 1);
                        $pdf->Ln(2);
                        $pdf->SetFont('', 'B', '14');
                        $time = $timeslot['time'];
                    }
                    if( $pdf->layout == 'compact' ) {
                        $pdf->SetFont('', 'B', $pdf->font_size);
                        $pdf->SetCellPadding($pdf->padding);
                        if( $time == '' ) {
                            $pdf->MultiCell($w[0], 0, '', 'LTB', 'L', 1, 0);
                            $pdf->MultiCell($pdf->page_width - $w[0], 0, $name, 'TRB', 'L', 1, 1);
                        } else {
                            $pdf->MultiCell($cw[0] + $cw[1], 0, $time . ' - ' . $name, 1, 'L', 1, 1);
                        }
                    } else {
                        $pdf->MultiCell($cw[0], 0, $time, 0, 'L', 0, 0);
                        $pdf->MultiCell($cw[1], 0, $name, 0, 'L', 0, 1);
                    }
                }
                $pdf->SetFont('', '', '11');

                if( isset($timeslot['accolade_names']) && $timeslot['accolade_names'] != '' ) {
                    $pdf->MultiCell($cw[0], 0, '', 0, 'L', 0, 0);
//                    $pdf->MultiCell($trw[0], 0, ($tid == 0 ? 'Eligible for: ' : ''), 0, 'L', 0, 0);
                    $pdf->MultiCell($trw[0], 0, 'Eligible for: ', 0, 'L', 0, 0);
                    $pdf->MultiCell($trw[1], 0, $timeslot['accolade_names'], 0, 'L', 0, 1);
                }
                if( isset($festival['runsheets-timeslot-description']) && $festival['runsheets-timeslot-description'] == 'yes'
                    && isset($timeslot['description']) && $timeslot['description'] != '' 
                    ) {
                    $pdf->SetFont('', 'B', 11);
                    $pdf->MultiCell($cw[0], 0, '', 0, 'L', 0, 0);
                    $pdf->SetFont('', '', 11);
                    $pdf->MultiCell($cw[1], 0, $timeslot['description'], 0, 'L', 0, 1);
                }
                if( $pdf->layout != 'compact' ) {
                    $pdf->Ln(2);
                }
                if( isset($timeslot['runsheet_notes']) && $timeslot['runsheet_notes'] != '' ) {
                    $pdf->SetFont('', 'B', 11);
                    $pdf->MultiCell($cw[0], 0, 'Notes', 0, 'L', 0, 0);
                    $pdf->SetFont('', '', 11);
                    $pdf->MultiCell($cw[1], 0, $timeslot['runsheet_notes'], 0, 'L', 0, 1);
                }
                if( $pdf->layout != 'compact' ) {
                    $pdf->Ln(2);
                }

                if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                    if( $pdf->layout != 'compact' ) {
                        $pdf->ClassHeader($ciniki, $w);
                    }
                    $pdf->SetFont('', '', '12');
                    $num = 1;
                    if( isset($timeslot['start_num']) && is_numeric($timeslot['start_num']) && $timeslot['start_num'] > 1 ) {
                        $num = $timeslot['start_num'];
                    }
                    foreach($timeslot['registrations'] as $reg) {
                        //
                        // Check height and see if we need new page
                        //
                        $h = 0;
                        $pdf->SetFont('', 'B');
                        $h += $pdf->getStringHeight($w[1], $reg['name']);
                        for($i = 1; $i <= 8; $i++) {
                            if( $reg["title{$i}"] != '' && $pdf->titles == 'yes' ) {
                                $h += $pdf->getStringHeight($tw[1], $reg["title{$i}"]);
                            }
                        }
                        if( $reg['combined_notes'] != '' ) {
                            $pdf->SetCellPadding($pdf->padding);
                            $pdf->SetFont('', '', '11');
                            $h += $pdf->getStringHeight($tnw[2], $reg['combined_notes']);
                        }
                        if( $pdf->GetY() > $pdf->getPageHeight() - $h - 22) {
                            // The following has been added by untested
                            $pdf->AddPage();
                            if( $pdf->layout == 'compact' ) {
                                $pdf->ClassHeader($ciniki, $w);
                            } else {
                                $pdf->DivisionHeader($args, $section, $division, 'yes');
                            }
                            // Set continued class
                            $pdf->SetFont('', 'B', '14');
                            $pdf->SetCellPaddings(0, 1, 0, 0);
                            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) 
                                && isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 
                                ) {
                                $pdf->MultiCell($cw[0] + $cw[1], 0, $name . ' (continued...)', 0, 'L', 0, 1);
                            } elseif( $pdf->layout == 'compact' ) {
                                $pdf->SetFont('', 'B', $pdf->font_size);
                                $pdf->SetCellPadding($pdf->padding);
                                $pdf->MultiCell($cw[0] + $cw[1], 0, $time . ' - ' . $name . ' (continued...)', 1, 'L', 1, 1);
                            } else {
                                $pdf->MultiCell($cw[0], 0, $time, 0, 'L', 0, 0);
                                $pdf->MultiCell($cw[1], 0, $name . ' (continued...)', 0, 'L', 0, 1);
                            }
                            $pdf->SetFont('', '', '11');
                            if( isset($timeslot['accolade_names']) && $timeslot['accolade_names'] != '' ) {
                                $pdf->MultiCell($cw[0] + $cw[1], 0, '', 0, 'L', 0, 0);
                                $pdf->MultiCell($trw[0], 0, 'Eligible for: ', 0, 'L', 0, 0);
                                $pdf->MultiCell($trw[1], 0, $timeslot['accolade_names'], 0, 'L', 0, 1);
                            }
                            if( isset($timeslot['runsheet_notes']) && $timeslot['runsheet_notes'] != '' ) {
                                $pdf->SetFont('', 'B', 11);
                                $pdf->MultiCell($cw[0], 0, 'Notes', 0, 'L', 0, 0);
                                $pdf->SetFont('', '', 11);
                                $pdf->MultiCell($cw[1], 0, $timeslot['runsheet_notes'], 0, 'L', 0, 1);
                            }
                            if( $pdf->layout != 'compact' ) {
                                $pdf->Ln(2);
                                $pdf->ClassHeader($ciniki, $w);
                            }
/*                            $pdf->SetCellPaddings(2,2,2,2);
                            $pdf->SetFont('', 'B', '11');
                            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                                $pdf->MultiCell($w[0], 0, 'Time', 1, 'C', 1, 0);
                            } else {
                                $pdf->MultiCell($w[0], 0, '#', 1, 'C', 1, 0);
                            }
                            $pdf->MultiCell($w[1], 0, 'Name', 1, 'L', 1, 0);
                            $col = 2;
                            if( $pdf->mark == 'yes' ) {
                                $pdf->MultiCell($w[$col], 0, 'Mark', 1, 'C', 1, (($col+1) < count($w) ? 0 : 1));
                                $col++;
                            }
                            if( $pdf->placement == 'yes' ) {
                                $pdf->MultiCell($w[$col], 0, 'Place', 1, 'C', 1, (($col+1) < count($w) ? 0 : 1));
                                $col++;
                            }
                            if( $pdf->level == 'yes' ) {
                                $pdf->MultiCell($w[$col], 0, 'Level', 1, 'C', 1, (($col+1) < count($w) ? 0 : 1));
                                $col++;
                            }
                            if( $pdf->advanceto == 'yes' ) {
                                $pdf->MultiCell($w[$col], 0, 'Advanced to', 1, 'C', 1, 1);
                                $col++;
                            } */
                        }
                        $pdf->SetCellPadding($pdf->padding);
                        if( $pdf->layout == 'compact' ) {
                            $pdf->SetFont('', '', $pdf->font_size);
                        } else {
                            $pdf->SetFont('', 'B', $pdf->font_size);
                        }
                        $border = 'LTR';
                        if( $reg["title1"] == '' || $pdf->titles == 'no' ) {
                            $border = 'BLTR';
                        }
                        $h = $pdf->getStringHeight($w[1], $reg['name']);
                        if( $reg['participation'] == 1 ) {
                            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                                $pdf->MultiCell($w[0], $h, 'Virtual', $border, 'C', 0, 0);
                            } else {
                                $pdf->MultiCell($w[0], $h, $num, $border, 'C', 0, 0);
                            }
                        } else {
                            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                                $pdf->MultiCell($w[0], $h, $reg['reg_time_text'], $border, 'C', 0, 0);
                            } else {
                                $pdf->MultiCell($w[0], $h, $num, $border, 'C', 0, 0);
                            }
                        }
                        $pdf->MultiCell($w[1], $h, $reg['name'], 'BLTR', 'L', 0, 0);
                        $col = 2;
                        if( $pdf->mark == 'yes' ) {
                            $pdf->MultiCell($w[$col], $h, '', 1, 'C', 0, (($col+1) < count($w) ? 0 : 1));
                            $col++;
                        }
                        if( $pdf->placement == 'yes' ) {
                            $pdf->MultiCell($w[$col], $h, '', 1, 'C', 0, (($col+1) < count($w) ? 0 : 1));
                            $col++;
                        }
                        if( $pdf->level == 'yes' ) {
                            $pdf->MultiCell($w[$col], $h, '', 1, 'C', 0, (($col+1) < count($w) ? 0 : 1));
                            $col++;
                        }
                        if( $pdf->advanceto == 'yes' ) {
                            $pdf->MultiCell($w[$col], $h, '', 1, 'C', 0, 1);
                            $col++;
                        }
                        $pdf->SetFont('', '');
                        $border = 'LR';
                        $pdf->SetCellPaddings($pdf->padding,$pdf->padding,$pdf->padding,0);
                        $pdf->SetFont('', '', '11');
                        for($i = 1; $i <= 8; $i++) {
                            if( $reg["title{$i}"] != '' ) {
                                if( $reg['last_title'] == $i && $reg['combined_notes'] == '' ) {
                                    $border = 'LBR';
                                    if( $i == 1 ) {
                                        $pdf->SetCellPadding($pdf->padding);
                                    } else {
                                        $pdf->SetCellPaddings($pdf->padding,0,$pdf->padding,2);
                                    }
                                } elseif( $i > 1 ) {
                                    $pdf->SetCellPaddings($pdf->padding,0,$pdf->padding,0);
                                }
                                if( $pdf->titles == 'yes' ) {
                                    $h = $pdf->getStringHeight($tw[1], $reg["title{$i}"]);
                                    $pdf->MultiCell($tw[0], $h, '', $border, 'C', 0, 0);
                                    $pdf->SetFont('arialunicodems', '', '11');
                                    $pdf->MultiCell($tw[1], $h, $reg["title{$i}"], $border, 'L', 0, 1);
                                }
                                $pdf->SetFont('helvetica', '', '11');
                            }
                        }
                        if( $reg['combined_notes'] != '' ) {
                            $pdf->SetCellPadding($pdf->padding);
                            $pdf->SetFont('', '', '11');
                            $h = $pdf->getStringHeight($tnw[2], $reg['combined_notes']);
                            $pdf->MultiCell($tnw[0], $h, '', 'LBR', 'C', 0, 0);
                            $pdf->SetFont('', 'B', '11');
                            $pdf->MultiCell($tnw[1], $h, 'Notes', 'LB', 'L', 0, 0);
                            $pdf->SetFont('', '', '11');
                            $pdf->MultiCell($tnw[2], $h, $reg['combined_notes'], 'BR', 'L', 0, 1);
                        }
                        $pdf->SetFont('', '', '12');

                        $num++;
                    }
                    if( isset($festival['runsheets-timeslot-singlepage']) && $festival['runsheets-timeslot-singlepage'] == 'yes' ) {
                        if( $cur_timeslot != $num_timeslots ) {
                            $pdf->AddPage();
                            $newpage = 'yes';
                        } else {
                            $newpage = 'no';
                        }
                    } elseif( $pdf->layout != 'compact' ) {
                        $pdf->Ln(5);
                    }
                } elseif( $pdf->layout != 'compact' ) {
                    $pdf->Ln(5);
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
