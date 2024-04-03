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
function ciniki_musicfestivals_templates_programPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');

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
        . "ciniki_musicfestivals.flags, "
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
            'fields'=>array('name', 'permalink', 'flags', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.111', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.112', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the settings for the festival
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "ssections.adjudicator1_id, "
        . "ssections.adjudicator2_id, "
        . "ssections.adjudicator3_id, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, ";
    if( isset($festival['program-separate-classes']) && $festival['program-separate-classes'] == 'yes' ) {
        $strsql .= "CONCAT_WS('-', timeslots.id, classes.id) AS timeslot_id, ";
    } else {
        $strsql .= "timeslots.id AS timeslot_id, ";
    }
    $strsql .= "IFNULL(classes.id, 0) AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name, "
        . "timeslots.name AS timeslot_name, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
//        . "'' AS title "
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
        . "registrations.movements8 "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes on ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories on ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections on ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
//    $strsql .= "AND sections.id = 57 ";
    $strsql .= "ORDER BY ssections.sequence, divisions.division_date, division_id, slot_time, registrations.timeslot_sequence, registrations.public_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator1_id', 'adjudicator2_id', 'adjudicator3_id',
            )),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address',
            )),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'class_id', 'class_code', 'class_name', 'description', 'category_name', 'syllabus_section_name', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['sections']) ) {
        $sections = $rc['sections'];
    } else {
        $sections = array();
    }

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 12;
        public $right_margin = 12;
        public $top_margin = 12;
        public $footer_margin = 0;
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $tenant_details = array();
        public $fw = 116;

        public function Header() {
/*            $this->Ln(8);
            $this->SetFont('times', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell($fw-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('times', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell($fw-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('times', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell($fw-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6); */
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
           // $this->Cell($this->fw, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->Cell($this->fw, 10, 'Page ' . $this->pageNo(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'STATEMENT', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = '';

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Program');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(0);

    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $filename = 'program';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(20, 2, 94);
//    $w2 = array(7, 109);
    $w2 = array(7, 2, 105);
    $fw = 116;
    $prev_adjudicator_id = 0;
    foreach($sections as $section) {
        //
        // Add the adjudicator(s)
        //
        if( isset($section['adjudicator1_id']) && $section['adjudicator1_id'] > 0 
            && $prev_adjudicator_id != $section['adjudicator1_id'] 
            ) {
            $strsql = "SELECT c.display_name AS name, "
                . "a.image_id, "
                . "a.description "
                . "FROM ciniki_musicfestival_adjudicators AS a "
                . "LEFT JOIN ciniki_customers AS c ON ("
                    . "a.customer_id = c.id "
                    . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE a.id = '" . ciniki_core_dbQuote($ciniki, $section['adjudicator1_id']) . "' "
                . "AND a.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'customer');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']['description']) && $rc['customer']['description'] != '' ) {
                $bio = $rc['customer']['description'];
                $pdf->AddPage();
                
                //
                // Add Title
                //
                $pdf->SetFont('', 'B', '16');
                $pdf->Cell($fw, 10, $section['name'], 0, 0, 'C', 0);
                $pdf->Ln(9);
                $pdf->SetFont('', '', '14');
                //$pdf->Cell($fw, 10, 'Adjudicator ' . $rc['customer']['name'], 0, 'B', 'C', 0);
                $pdf->MultiCell($fw, 10, 'Adjudicator ' . $rc['customer']['name'], 0, 'C', 0, 1);
                $pdf->Ln(4);

                //
                // Add image
                //
                if( isset($rc['customer']['image_id']) && $rc['customer']['image_id'] > 0 ) {
                    $rc = ciniki_images_loadImage($ciniki, $tnid, $rc['customer']['image_id'], 'original');
                    if( $rc['stat'] == 'ok' ) {
                        $image = $rc['image'];
                        $height = $image->getImageHeight();
                        $width = $image->getImageWidth();
                        $image_ratio = $width/$height;
                        $img_width = 53; 
                        $h = ($height/$width) * $img_width;
                        $y = $pdf->getY();
                        $pdf->Image('@'.$image->getImageBlob(), ($fw/2) + $pdf->left_margin + 3, $y, $img_width, 0, 'JPEG', '', 'TL', 2, '150');
                        $pdf->setPageRegions(array(array('page'=>'', 'xt'=>($fw/2) + $pdf->left_margin, 'yt'=>$y, 'xb'=>($fw/2) + $pdf->left_margin, 'yb'=>$y+$h+2, 'side'=>'R')));
                        $pdf->setY($y-2.5);
                    }
                }

                //
                // Add full bio
                //
                $pdf->SetFont('', '', 11);
                $pdf->MultiCell($fw, 10, $bio . "\n", 0, 'J', false, 1, '', '', true, 0, false, true, 0, 'T', false);
            }
            $prev_adjudicator_id = $section['adjudicator1_id'];
        }

        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'] . ' Schedule';
        if( isset($args['schedulesection_id']) ) {
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $section['name']) . '_program';
        }

        //
        // Output the divisions
        //
        $newpage = 'yes';
        foreach($section['divisions'] as $division) {
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) ) {
                continue;
            }
            if( $pdf->GetY() > 100 ) {
                $pdf->AddPage();
            } else {
                $pdf->Ln(10);
            }
            //
            // Check if enough room
            //
            $lh = 9;
            $address = '';
            if( $division['address'] != '' ) {
                $s_height = $pdf->getStringHeight($fw, $division['address']);
                $address = $division['address'];
            } else {
                $s_height = 0;
            }
/*            if( $pdf->getY() > $pdf->getPageHeight() - $lh - 40 - $s_height) {
                $pdf->AddPage();
                $newpage = 'yes';
            } elseif( $newpage == 'no' ) {
                $pdf->Ln();
            }  */
            $newpage = 'no';

            $pdf->SetFont('', 'B', '14');
            if( $pdf->getStringWidth($division['date'] . ' - ' . $division['name'], '', 'B', 14) > $fw ) {
                $pdf->MultiCell($fw, 10, $division['date'] . "\n" . $division['name'], 0, 'C', 0);
            } else {
                $pdf->Cell($fw, 10, $division['date'] . ' - ' . $division['name'], 0, 0, 'C', 0);
                $pdf->Ln(8);
            }
            $pdf->SetFont('', '', '12');
            if( $address != '' ) {
                $pdf->MultiCell($fw, $lh, $address, 0, 'C', 0, 2);
                $pdf->Ln(2);
            }
            $fill = 1;
            
            //
            // Output the timeslots
            //
            $fill = 0;
            $border = 'T';
            $c = 1;
            foreach($division['timeslots'] as $timeslot) {
                $name = $timeslot['name'];
                $description = $timeslot['description'];
                $reg_list = array();
                $reg_list_height = 0;
                if( isset($timeslot['class_id']) && $timeslot['class_id'] > 0 ) {  
                    if( $name == '' && $timeslot['class_name'] != '' ) {
                        $name = $timeslot['class_name'];
                    }
                    if( isset($festival['program-separate-classes']) && $festival['program-separate-classes'] == 'yes' 
                        && $timeslot['class_code'] != '' 
                        ) {
                        if( isset($festival['program-class-format']) 
                            && $festival['program-class-format'] == 'code-section-category-class' 
                            ) {
                            $name = $timeslot['class_code'] . ' - ' . $timeslot['syllabus_section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                        } elseif( isset($festival['program-class-format']) 
                            && $festival['program-class-format'] == 'code-category-class' 
                            ) {
                            $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                        } elseif( isset($festival['program-class-format']) 
                            && $festival['program-class-format'] == 'code-class' 
                            ) {
                            $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
                        } else {
                            $name = $timeslot['class_name']; 
                        }
                    }

                    $pdf->SetCellPadding(0);
                    foreach($timeslot['registrations'] as $rid => $reg) {
                        $row = array();
                        $row['name'] = $reg['name'];
                        $row['dash_width'] = $pdf->getStringWidth('-', '', '') + 3;
                        $row['name_width'] = $pdf->getStringWidth($row['name'], '', '') + 0.25;
                        $row['title_width'] = $pdf->getStringWidth($reg['title1'], '', '') + 0.25;
                        if( ($row['name_width'] + $row['dash_width'] + $row['title_width']) > $w2[2] 
                            && $row['name_width'] > ($w2[2]*0.4) 
                            ) {
                            $row['name_width'] = ($w2[2]*0.4);
                        }
                        $row['title_width'] = $w2[2] - $row['name_width'] - $row['dash_width'] - 1;
                        $row['name_height'] = $pdf->getStringHeight($row['name_width'], $row['name']);
                        $row['height'] = $row['name_height'];
//                        $row['title1'] = $reg['title1'];
//                        $row['titles_height'] = $pdf->getStringHeight($row['title_width'], $row['title1']);
                        $row['titles_height'] = 0;
                        for($i = 1; $i <= 8; $i++ ) {
                            if( isset($reg["title{$i}"]) && $reg["title{$i}"] != '' ) {
                                $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
                                if( isset($rc['title']) ) {
                                    $timeslot['registrations'][$rid]["title{$i}"] = $rc['title'];
                                }
                                $row["title{$i}"] = $timeslot['registrations'][$rid]["title{$i}"];
                                if( isset($args['video_urls']) && $args['video_urls'] == 'yes' 
                                    && isset($reg["video_url{$i}"]) && $reg["video_url{$i}"] != '' 
                                    ) {
                                    $row["title{$i}"] .= ' ' . $reg["video_url{$i}"];
                                }
                                $row['titles_height'] += $pdf->getStringHeight($row['title_width'], $row["title{$i}"]);
                            }
                        }
/*                        if( $reg['title2'] != '' ) {
                            $row['title2'] = $reg['title2'];
                            $row['titles_height'] += $pdf->getStringHeight($row['title_width'], $row['title2']);
                        }
                        if( $reg['title3'] != '' ) {
                            $row['title3'] = $reg['title3'];
                            $row['titles_height'] += $pdf->getStringHeight($row['title_width'], $row['title3']);
                        } */
                        if( $row['titles_height'] > $row['height'] ) {
                            $row['height'] = $row['titles_height'];
                        }
                        $reg_list[] = $row;
                        $reg_list_height += $row['height'];
                    }
                    $pdf->SetCellPadding(1);

/*                    if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                        if( $description != '' ) {
                            $description .= "\n\n";
                        }
                        foreach($timeslot['registrations'] as $reg) {
                            $description .= ($description != '' ? "\n" : '') . $reg['name'] . ($reg['title1'] != '' ? ' - ' . $reg['title1'] : '');
                        }
                    } */
                }

//                if( $description != '' ) {
                if( ($reg_list_height > 0 && $pdf->getY() > ($pdf->getPageHeight() - 37 - $reg_list_height)) 
                    || ($reg_list_height == 0 && $pdf->getY() > ($pdf->getPageHeight() - 40)) 
                    ) {
//                    $d_height = $pdf->getStringHeight($w2[1], $description);
//                    if( $pdf->getY() > $pdf->getPageHeight() - 40 - $d_height) {
                        $pdf->AddPage();
                        $pdf->SetFont('', 'B', '12');
                        // $pdf->Cell($fw, 10, $division['name'] . ' - ' . $division['date'] . ' (continued...)', 0, 0, 'L', 0);
                        if( $pdf->getStringWidth($division['date'] . ' - ' . $division['name'] . ' (continued...)', '', 'B', 12) > $fw ) {
                            $pdf->MultiCell($fw, 10, $division['date'] . "\n" . $division['name'] . ' (continued...)', 0, 'C', 0);
                        } else {
                            $pdf->Cell($fw, 10, $division['date'] . ' - ' . $division['name'] . ' (continued...)', 0, 0, 'C', 0);
                            $pdf->Ln(8);
                        }
                        $pdf->SetFont('', '', '12');
                        if( $address != '' ) {
                            $pdf->MultiCell($fw, $lh, $address, 0, 'C', 0, 1);
//                            $pdf->Ln($lh);
                        }
//                    }
                }
                
                $pdf->SetFont('', 'B');
                $pdf->MultiCell($w[0], $lh, $timeslot['time'], $border, 'R', 0, 0);
//                $pdf->Cell($w[0], $lh, $timeslot['time'], $border, 0, 'R', 0);
//                $pdf->Cell($w[1], $lh, '', $border, 0, 'R', 0);
                $pdf->MultiCell($w[1], $lh, '', $border, 'R', 0, 0);
                $n_height = $pdf->getStringHeight($w[2], $name);
                $pdf->MultiCell($w[2], $n_height, $name, $border, 'L', 0, 1);
                $pdf->SetFont('', '');
//                $pdf->Ln($lh);
    
                $pdf->SetCellPadding(0);
                foreach($reg_list as $row) {
                    $pdf->MultiCell($w2[0], $row['height'], '', 0, 'L', 0, 0);
                    $pdf->MultiCell($w2[1]+1, $row['height'], '', 0, 'L', 0, 0);
/*                    $pdf->MultiCell($row['name_width'], $row['name_height'], $row['name'], 0, 'L', 0, 0);
                    if( $row['title1'] != '' ) {
                        $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                        $pdf->MultiCell($row['title_width'], '', $row['title1'], 0, 'L', 0, 1);
                    } else {
                        $pdf->Ln(5);
                    } */
//                    if( isset($args['titles']) && $args['titles'] == 'yes' ) {
                        $pdf->MultiCell($row['name_width'], $row['name_height'], $row['name'], 0, 'L', 0, 0);
                        if( isset($row['title1']) && $row['title1'] != '' ) {
                            $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                            $pdf->MultiCell($row['title_width'], '', $row['title1'], 0, 'L', 0, 1);
                        } else {
                            $pdf->Ln(5);
                        }
                        for($i = 2; $i <= 8; $i++) {
                            if( isset($row["title{$i}"]) && $row["title{$i}"] != '' ) {
                                $pdf->MultiCell($w2[0] + $w2[1] + $row['name_width'] + 1, $row['height'], '', 0, 'L', 0, 0);
                                $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                                $pdf->MultiCell($row['title_width'], '', $row["title{$i}"], 0, 'L', 0, 1);
                            }
                        }
                        if( $row['titles_height'] < $row['name_height'] ) {
                            $pdf->Ln($row['name_height'] - $row['titles_height']);
                        }
                        if( $row['name_height'] > $row['titles_height'] ) {    
                            
                           // $pdf->Ln($row['name_height'] - $row['titles_height']);
                        }
//                    } else {
//                        $pdf->MultiCell($w[2], $row['height'], $row['name'], 0, 'L', 0, 1);
//                    }
                    $pdf->Ln(1);
/*                    if( isset($row['title2']) && $row['title2'] != '' ) {
                        $pdf->MultiCell($w2[0] + $w2[1] + $row['name_width'] + 1, $row['height'], '', 0, 'L', 0, 0);
                        $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                        $pdf->MultiCell($row['title_width'], '', $row['title2'], 0, 'L', 0, 1);
                    }
                    if( isset($row['title3']) && $row['title3'] != '' ) {
                        $pdf->MultiCell($w2[0] + $w2[1] + $row['name_width'] + 1, $row['height'], '', 0, 'L', 0, 0);
                        $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                        $pdf->MultiCell($row['title_width'], '', $row['title3'], 0, 'L', 0, 1);
                    }
                    if( $row['name_height'] > $row['titles_height'] ) {    
                        $pdf->Ln($row['name_height'] - $row['titles_height']);
                    }
                    $pdf->Ln(1); */
                }
                $pdf->SetCellPadding(1);

/*                if( $description != '' ) {
                    $pdf->writeHTMLCell($w2[0], $d_height, '', '', '', '', 0, false, true, 'L', 1);
                    $pdf->writeHTMLCell($w2[1], $d_height, '', '', preg_replace("/\n/", "<br/>", $description), '', 0, false, true, 'L', 1);
                    $pdf->Ln();
                } */
                if( $c < count($division['timeslots']) ) {
                    $pdf->Ln(5);
                }

                $fill=!$fill;
                $border = 'T';
                $c++;
            }
//            $pdf->Cell($w[0]+$w[1]+$w[2], 1, '', 'T', 0, 'R', 0);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
