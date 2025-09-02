<?php
//
// Description
// ===========
// This method will return the PDF of the schedule with timings
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_scheduleTimingsPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    //
    // Make sure festival_id was passed in
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.727', 'msg'=>'No festival specified'));
    }

    //
    // Make sure schedule ssection_id was passed in
    //
    if( (!isset($args['ssection_id']) || $args['ssection_id'] <= 0) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.913', 'msg'=>'No section specified'));
    }

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
    // Load the schedule, timeslots, registrations
    //
    $strsql = "SELECT ssections.id AS ssection_id, "
        . "ssections.name AS ssection_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.groupname, "
        . "timeslots.slot_time, "
        . "timeslots.slot_seconds, "
        . "timeslots.start_num, ";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
            $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, ";
        } else {
            $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
        }
    $strsql .= "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.title1, "
        . "registrations.composer1, "
        . "registrations.movements1, "
        . "registrations.perf_time1, "
        . "registrations.title2, "
        . "registrations.composer2, "
        . "registrations.movements2, "
        . "registrations.perf_time2, "
        . "registrations.title3, "
        . "registrations.composer3, "
        . "registrations.movements3, "
        . "registrations.perf_time3, "
        . "registrations.title4, "
        . "registrations.composer4, "
        . "registrations.movements4, "
        . "registrations.perf_time4, "
        . "registrations.title5, "
        . "registrations.composer5, "
        . "registrations.movements5, "
        . "registrations.perf_time5, "
        . "registrations.title6, "
        . "registrations.composer6, "
        . "registrations.movements6, "
        . "registrations.perf_time6, "
        . "registrations.title7, "
        . "registrations.composer7, "
        . "registrations.movements7, "
        . "registrations.perf_time7, "
        . "registrations.title8, "
        . "registrations.composer8, "
        . "registrations.movements8, "
        . "registrations.perf_time8, "
        . "registrations.timeslot_sequence, "
        . "registrations.participation, "
        . "registrations.notes, "
        . "registrations.runsheet_notes, "
        . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
        . "IFNULL(teachers.display_name, '') AS teacher_name, "
        . "IFNULL(teachers2.display_name, '') AS teacher2_name, "
        . "classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "competitors.id AS competitor_id, "
        . "competitors.city AS competitor_city, "
        . "competitors.num_people, "
        . "competitors.notes AS competitor_notes "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id ";
    if( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' ";
    }
    $strsql .= "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS accompanists ON ("
            . "registrations.accompanist_customer_id = accompanists.id "
            . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS teachers ON ("
            . "registrations.teacher_customer_id = teachers.id "
            . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS teachers2 ON ("
            . "registrations.teacher2_customer_id = teachers2.id "
            . "AND teachers2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
            . "(registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['ssection_id']) && $args['ssection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['ssection_id']) . "' ";
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.division_date, registrations.timeslot_time, registrations.timeslot_sequence, registrations.display_name, registrations.status, registrations.display_name ";
    } else {
        $strsql .= "ORDER BY divisions.division_date, divisions.name, timeslots.slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence, registrations.display_name, registrations.status, registrations.display_name ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'ssection_id', 
            'fields'=>array('id'=>'ssection_id', 'name'=>'ssection_name'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'groupname', 'slot_time_text', 'slot_seconds', 'start_num'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'display_name', 
                'accompanist_name', 'teacher_name', 'teacher2_name',
                'timeslot_sequence', 
                'title1', 'composer1', 'movements1', 'perf_time1', 
                'title2', 'composer2', 'movements2', 'perf_time2', 
                'title3', 'composer3', 'movements3', 'perf_time3', 
                'title4', 'composer4', 'movements4', 'perf_time4', 
                'title5', 'composer5', 'movements5', 'perf_time5', 
                'title6', 'composer6', 'movements6', 'perf_time6', 
                'title7', 'composer7', 'movements7', 'perf_time7', 
                'title8', 'composer8', 'movements8', 'perf_time8', 
                'class_code', 'class_name', 'category_name', 'section_name',
                'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                'notes', 'runsheet_notes', 'participation', 'num_people',
            )),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'notes'=>'competitor_notes', 'city'=>'competitor_city', 'num_people'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.914', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 15;
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $tenant_details = array();
        public $fill = 0;

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
/*            $img_width = 0;
            if( $this->header_image != null ) {
                $height = $this->header_image->getImageHeight();
                $width = $this->header_image->getImageWidth();
                if( $width > 600 ) {
                    $this->header_image->scaleImage(600, 0);
                }
                $image_ratio = $width/$height;
                $img_width = 60;
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, 0, $this->header_height-13, '', '', 'L', 2, '150');
                }
            }

            $this->Ln(8);
            $this->SetFont('helvetica', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('helvetica', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('helvetica', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6); */
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
    $pdf->header_sub_title = 'Schedule Timings';
    $pdf->header_msg = $festival['document_header_msg'];
//    $pdf->footer_msg = $festival['document_footer_msg'];

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
//        $pdf->header_height = 30;
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
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Schedule Timings');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.15);

    $filename = 'Schedule Timings';

    //
    // Go through the sections, divisions and classes
    //
    $rw = array(8, 50, 107, 15);
    $nw = array(8, 10, 162);
    $border = '';
    
    foreach($sections as $section) {
        if( isset($args['ssection_id']) && $args['ssection_id'] == $section['id'] ) {
            $filename .= ' - ' . $section['name'];
        }
        $pdf->AddPage();
        foreach($section['divisions'] as $did => $division) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->MultiCell(180, '', $section['name'] . ' - ' . $division['name'], 0, 'C', 0, 1);
    
            foreach($division['timeslots'] as $tid => $timeslot) {
                
                //
                // Print time if not 12:00 AM, along with class name
                //
                $pdf->SetFont('helvetica', 'B', 12);
                if( $timeslot['slot_time_text'] == '12:00 AM' ) {
                    $w = array(0, 145, 35);
                    $name = $timeslot['name'];
                } else {
                    $w = array(20, 125, 35);
                    $name = $timeslot['slot_time_text'] . ' - ' . $timeslot['name'];
                }
                if( $timeslot['groupname'] != '' ) {
                    $name .= ' - ' . $timeslot['groupname'];
                }
                $name_lh = $pdf->getStringHeight($w[1], $name);

                $perf_time = 0;
                $schedule_at_seconds = 0;
                $schedule_ata_seconds = 0;
                $total_lh = 0;
                $pdf->SetFont('helvetica', '', 12);
                $num_reg = 0;
                if( isset($timeslot['registrations']) ) {
                    foreach($timeslot["registrations"] as $rid => $reg) {
                        if( ($reg['class_flags']&0x8000) == 0x8000 && $reg['num_people'] > 0 ) {
                            $reg['display_name'] .= ' (' . $reg['num_people'] . ')';
                        }
                        if( isset($festival['scheduling-accompanist-show']) 
                            && $festival['scheduling-accompanist-show'] == 'yes' 
                            && $reg['accompanist_name'] != ''
                            ) {
                            $reg['display_name'] .= "\nA:{$reg['accompanist_name']}";
                        }
                        if( isset($festival['scheduling-teacher-show']) 
                            && $festival['scheduling-teacher-show'] == 'yes' 
                            && $reg['teacher_name'] != ''
                            ) {
                            $reg['display_name'] .= "\nT:{$reg['teacher_name']}";
                        }
                        if( isset($festival['scheduling-teacher-show']) 
                            && $festival['scheduling-teacher-show'] == 'yes' 
                            && $reg['teacher2_name'] != ''
                            ) {
                            $reg['display_name'] .= "\nT:{$reg['teacher2_name']}";
                        }

                        $timeslot['registrations'][$rid]['display_name'] = $reg['display_name'];
                        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, array('basicnumbers'=>'yes'));
                        $timeslot["registrations"][$rid]['titles'] = $rc['titles'];
                        $timeslot['registrations'][$rid]['name_lh'] = $pdf->getStringHeight($rw[1], $reg['display_name']);
                        $timeslot['registrations'][$rid]['titles_lh'] = $pdf->getStringHeight($rw[2], $rc['titles']);
                        $timeslot['registrations'][$rid]['notes_lh'] = 0;
                        $timeslot["registrations"][$rid]['perf_time'] = $rc['perf_time'];
                        $perf_time += $rc['perf_time_seconds'];
                        if( isset($reg['competitors']) ) {
                            foreach($reg['competitors'] as $competitor) {
                                if( $competitor['notes'] != '' ) {
                                    $timeslot["registrations"][$rid]['notes'] .= ($timeslot["registrations"][$rid]['notes'] != '' ? ' ' : '') . $competitor['notes'];
                                }
                            }
                            unset($timeslot["registrations"][$rid]['competitors']);
                        }
                        if( $timeslot['registrations'][$rid]['titles_lh'] > $timeslot['registrations'][$rid]['name_lh'] ) {
                            $total_lh += $timeslot['registrations'][$rid]['titles_lh'];
                            $timeslot['registrations'][$rid]['lh'] = $timeslot['registrations'][$rid]['titles_lh'];
                        } else {
                            $total_lh += $timeslot['registrations'][$rid]['name_lh'];
                            $timeslot['registrations'][$rid]['lh'] = $timeslot['registrations'][$rid]['name_lh'];
                        }
                        if( $timeslot['registrations'][$rid]['notes'] != '' ) {
                            $timeslot['registrations'][$rid]['notes_lh'] = $pdf->getStringHeight($nw[2], $timeslot['registrations'][$rid]['notes']);
                            $total_lh += $timeslot['registrations'][$rid]['notes_lh'];
                        }
                        if( $reg['schedule_at_seconds'] > $schedule_at_seconds ) {
                            $schedule_at_seconds = $reg['schedule_at_seconds'];
                        }
                        if( $reg['schedule_ata_seconds'] > $schedule_ata_seconds ) {
                            $schedule_ata_seconds = $reg['schedule_ata_seconds'];
                        }
                        $num_reg++;
                    }
                }
                if( $schedule_at_seconds > 0 ) {
                    $perf_time += $schedule_at_seconds;
                }
                if( $schedule_ata_seconds > 0 && $num_reg > 1 ) {
                    $perf_time += ($schedule_ata_seconds * ($num_reg-1));
                }
                $slot_length = '';
                if( $timeslot['slot_seconds'] > 0 ) {
                    if( $timeslot['slot_seconds'] > 3600 ) {
                        $slot_length = intval($timeslot['slot_seconds']/3600) . 'h ' . ceil(($timeslot['slot_seconds']%3600)/60) . 'm';
                    } else {
                        $slot_length = '' . intval($timeslot['slot_seconds']/60) . ':' . str_pad(($timeslot['slot_seconds']%60), 2, '0', STR_PAD_LEFT) . '';
                    }
                }
                $perf_time_str = '';
                if( $perf_time != '' && $perf_time > 0 ) {
                    if( $perf_time > 3600 ) {
                        $perf_time_str = intval($perf_time/3600) . 'h ' . ceil(($perf_time%3600)/60) . 'm';
                    } else {
                        $perf_time_str = '' . intval($perf_time/60) . ':' . str_pad(($perf_time%60), 2, '0', STR_PAD_LEFT) . '';
                    }
                    if( $slot_length != '' ) {
                        $perf_time_str = '<strike>' . $perf_time_str . '</strike> ' . $slot_length;
                    }
                } elseif( $perf_time != '' && $perf_time == 0 ) {
                    $pref_time_str = '?';
                    if( $slot_length != '' ) {
                        $perf_time_str = $slot_length;
                    }
                }

                if( $pdf->getY() > $pdf->getPageHeight() - 30 - $name_lh - $total_lh ) {
                    $pdf->AddPage(); 
                    $pdf->SetFont('helvetica', 'B', 16);
                    $pdf->MultiCell(180, '', $section['name'] . ' - ' . $division['name'] . ' (continued...)', 0, 'C', 0, 1);
                }
        
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->MultiCell($w[1], $name_lh, $name, 0, 'L', 0, 0);
                $pdf->MultiCell($w[2], $name_lh, $perf_time_str, 0, 'R', 0, 1, '', '', true, 0, true);

                $pdf->SetFont('helvetica', '', 12);
                if( isset($timeslot['registrations']) ) {
                    $num = 1;
                    if( $timeslot['start_num'] > 1 ) {
                        $num = $timeslot['start_num'];
                    }
                    foreach($timeslot["registrations"] as $rid => $reg) {
                        if( $pdf->getY() > $pdf->getPageHeight() - 30 - $reg['lh'] - $reg['notes_lh'] ) {
                            $pdf->AddPage(); 
                            $pdf->SetFont('helvetica', 'B', 16);
                            $pdf->MultiCell(180, '', $section['name'] . ' - ' . $division['name'] . ' (continued...)', 0, 'L', 0, 1);
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->MultiCell(180, $name_lh, $name . ' (continued...)', 0, 'L', 0, 1);
                            $pdf->SetFont('helvetica', '', 12);
                        }
                        if( $reg['notes'] != '' ) {
                            $pdf->MultiCell($rw[0], $reg['lh'], $num, 'LTR', 'C', 0, 0);
                        } else {
                            $pdf->MultiCell($rw[0], $reg['lh'], $num, 1, 'C', 0, 0);
                        }
                        $pdf->MultiCell($rw[1], $reg['lh'], $reg['display_name'], 1, 'L', 0, 0);
                        $pdf->MultiCell($rw[2], $reg['lh'], $reg['titles'], 1, 'L', 0, 0);
                        $pdf->MultiCell($rw[3], $reg['lh'], $reg['perf_time'], 1, 'R', 0, 1);
                        if( $reg['notes'] != '' ) {
                            $pdf->SetFont('helvetica', 'I', 12);
                            $pdf->MultiCell($nw[0], $reg['notes_lh'], '', 'LBR', 'L', 0, 0);
                            $pdf->MultiCell($nw[1], $reg['notes_lh'], '', 'LB', 'L', 0, 0);
                            $pdf->MultiCell($nw[2], $reg['notes_lh'], $reg['notes'], 'BR', 'L', 0, 1);
                            $pdf->SetFont('helvetica', '', 12);
                        }
                        $num++;
                    }
                }
                $pdf->Ln(5);
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
