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
function ciniki_musicfestivals_templates_schedulePDF(&$ciniki, $tnid, $args) {

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.113', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.114', 'msg'=>'Unable to find Festival'));
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
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "customers.display_name AS adjudicator, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, ";
    if( isset($festival['schedule-separate-classes']) && $festival['schedule-separate-classes'] == 'yes' ) {
        $strsql .= "CONCAT_WS('-', timeslots.id, classes.id) AS timeslot_id, ";
    } else {
        $strsql .= "timeslots.id AS timeslot_id, ";
    }
    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
/*        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "timeslots.class4_id, "
        . "timeslots.class5_id, "
        . "IFNULL(class1.name, '') AS class1_name, "
        . "IFNULL(class2.name, '') AS class2_name, "
        . "IFNULL(class3.name, '') AS class3_name, "
        . "IFNULL(class4.name, '') AS class4_name, "
        . "IFNULL(class5.name, '') AS class5_name, " */
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
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
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.participation, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
            . "ssections.adjudicator1_id = adjudicators.id "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
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
    $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.division_date, slot_time, registrations.timeslot_sequence, class_code, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address', 'adjudicator'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'description', 'class_code', 'class_name', 'category_name', 'syllabus_section_name',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'participation',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
        public $header_visible = 'yes';
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_visible = 'yes';
        public $footer_msg = '';
        public $tenant_details = array();

        public function Header() {
            if( $this->header_visible == 'yes' ) {
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
                    $image_ratio = $width/$height;
                    $img_width = 60;
                    $available_ratio = $img_width/$this->header_height;
                    // Check if the ratio of the image will make it too large for the height,
                    // and scaled based on either height or width.
                    if( $available_ratio < $image_ratio ) {
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, 'JPEG', '', 'L', 2, '150');
                    } else {
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, 'JPEG', '', 'L', 2, '150');
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
                $this->Ln(6);
            } else {
                // No header
            }

        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            if( $this->footer_visible == 'yes' ) {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->SetFont('helvetica', '', 10);
                $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            } else {
                // No footer
            }
        }

        //
        // Print the division header
        //
        public function DivisionHeader($args, $section, $division, $continued) {
            $fields = array();
            if( isset($args['division_header_format']) && $args['division_header_format'] != 'default' ) {
                $fields = explode('-', $args['division_header_format']);
                if( $continued == 'yes' ) {
                    $division[$fields[0]] .= ' (continued...)';
                }
                if( isset($args['division_header_labels']) && $args['division_header_labels'] == 'yes' ) {
                    foreach($fields as $fid => $field) {
                        if( $fid == 0 ) {
                            continue;   // No label on first field
                        }
                        if( $field == 'date' ) {
                            $division[$field] = 'Date: ' . $division[$field];
                        } elseif( $field == 'name' ) {
                            $division[$field] = 'Section: ' . $division[$field];
                        } elseif( $field == 'adjudicator' ) {
                            $division[$field] = 'Adjudicator: ' . $division[$field];
                        } elseif( $field == 'address' ) {
                            $division[$field] = 'Location: ' . $division[$field];
                        }
                    }
                }
            } else {
                // Default layout
                $fields = array('date-name', 'address');
                $division['date-name'] = $division['date'] . ' - ' . $division['name'];
                if( $continued == 'yes' ) {
                    $division['date-name'] .= ' (continued...)';
                }
            }
            // Figure out how much room the division header needs
            $h = 0;
            $this->SetFont('', 'B', '16');
            foreach($fields as $field) {
                if( isset($division[$field]) && $division[$field] != '' ) {
                    $h += $this->getStringHeight(180, $division[$field]);
                }
                $this->SetFont('', '', '13');
            }
            // Check if enough room for division header and at least 1 timeslot
            if( $this->getY() > $this->getPageHeight() - $h - 80) {
                $this->AddPage();
            } elseif( $this->getY() > 80 ) {
                $this->Ln(10); 
            }
            // Output the division header
            $this->SetFont('', 'B', '16');
            $this->SetCellPaddings(0, 0.5, 0, 0.5);
            foreach($fields as $field) {
                $this->MultiCell(180, 0, $division[$field], 0, 'L', 0, 1);
                $this->SetFont('', '', '13');
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

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
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
    $pdf->SetTitle($festival['name'] . ' - Schedule');
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
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $filename = 'schedule';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(25, 5, 150);
    foreach($sections as $section) {
        if( !isset($section['divisions']) ) {
            continue;
        }
        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'] . ' Schedule';
        if( isset($args['schedulesection_id']) ) {
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $section['name']) . '_schedule';
        }
        if( $pdf->PageNo() == 0 || !isset($args['section_page_break']) || $args['section_page_break'] == 'yes' ) {
            $pdf->AddPage();
        }

        //
        // Output the divisions
        //
//        $newpage = 'yes';
        foreach($section['divisions'] as $division) {
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) ) {
                continue;
            }

            $pdf->DivisionHeader($args, $section, $division, 'no');
            $pdf->Ln(1);

/*            //
            // Check if enough room
            //
            $lh = 9;
            $address = '';
            if( $division['address'] != '' ) {
                $s_height = $pdf->getStringHeight(180, $division['address']);
                $address = $division['address'];
            } else {
                $s_height = 0;
            }
            if( $pdf->getY() > $pdf->getPageHeight() - $lh - 80 - $s_height) {
                $pdf->AddPage();
//                $newpage = 'yes';
            } else { //if( $newpage == 'no' ) {
                $pdf->Ln(15);
            }
//            $newpage = 'no';

            $pdf->SetFont('', 'B', '16');
            if( $pdf->getStringWidth($division['date'] . ' - ' . $division['name'], '', 'B', 16) > 180 ) {
                if( isset($args['swap_date_title']) && $args['swap_date_title'] == 'yes' ) {
                    $pdf->MultiCell(180, 10, $division['name'] . "\n" . $division['date'], 0, 'L', 0);
                } else {
                    $pdf->MultiCell(180, 10, $division['date'] . "\n" . $division['name'], 0, 'L', 0);
                }
            } else {
                if( isset($args['swap_date_title']) && $args['swap_date_title'] == 'yes' ) {
                    $pdf->Cell(180, 10, $division['name'] . ' - ' . $division['date'], 0, 0, 'L', 0);
                } else {
                    $pdf->Cell(180, 10, $division['date'] . ' - ' . $division['name'], 0, 0, 'L', 0);
                }
                $pdf->Ln(10);
            }
            $pdf->SetFont('', '', '12');
            if( $address != '' ) {
                $pdf->MultiCell(180, '', $address, 0, 'L', 0, 2);
            }
            $fill = 1;
*/            
            $pdf->SetFont('', '', '12');
            //
            // Output the timeslots
            //
            $fill = 0;
            $border = 'T';
            $prev_time = '';
            foreach($division['timeslots'] as $timeslot) {
                $name = $timeslot['name'];
                if( $name == '' && $timeslot['class_name'] != '' ) {
                    $name = $timeslot['class_name'];
                }
                if( isset($festival['schedule-separate-classes']) && $festival['schedule-separate-classes'] == 'yes' 
                    && $timeslot['class_code'] != '' 
                    ) {
                    if( isset($festival['schedule-class-format']) 
                        && $festival['schedule-class-format'] == 'code-section-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['syllabus_section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } elseif( isset($festival['schedule-class-format']) 
                        && $festival['schedule-class-format'] == 'code-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } else {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
                    }
                }
                $time = $timeslot['time'];
                if( $prev_time == $time ) {
                    $time = '';
                    $border = '';
                } else {
                    $prev_time = $time;
                }

                $description = $timeslot['description'];
                $reg_list = array();
                $reg_list_height = 0;
                if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                    $pdf->SetFont('', '', '12');
                    $pdf->SetCellPadding(0);
                    foreach($timeslot['registrations'] as $rid => $reg) {
                        $row = array();
                        if( isset($args['names']) && $args['names'] == 'private' ) {
                            $row['name'] = $reg['name'];
                        } else {
                            $row['name'] = $reg['public_name'];
                        }
                        $row['participation'] = $reg['participation'];
                        if( isset($args['titles']) && $args['titles'] == 'yes' ) {
                            $row['dash_width'] = $pdf->getStringWidth('-', '', '') + 3;
                            $row['name_width'] = $pdf->getStringWidth($row['name'], '', '') + 0.25;
                            $row['title_width'] = $pdf->getStringWidth($reg['title1'], '', '') + 0.25;
                            if( ($row['name_width'] + $row['dash_width'] + $row['title_width']) > $w[2] 
                                && $row['name_width'] > ($w[2]*0.5) 
                                ) {
                                $row['name_width'] = ($w[2]*0.5);
                            }
                            $row['title_width'] = $w[2] - $row['name_width'] - $row['dash_width'] - 1;
                            $row['name_height'] = $pdf->getStringHeight(($row['name_width']), $row['name']);
                            $row['height'] = $row['name_height'];
//                            $row['title1'] = $reg['title1'];
//                            $row['titles_height'] = $pdf->getStringHeight($row['title_width'], $row['title1']);
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
                            if( $row['titles_height'] > $row['height'] ) {
                                $row['height'] = $row['titles_height'];
                            }
                        } else {
                            $row['name_width'] = $w[2];
                            $row['title_width'] = 0;
                            $row['height'] = $pdf->getStringHeight($w[2], $row['name']);
                        }
                        $reg_list[] = $row;
                        $reg_list_height += $row['height'];
                    }
                    $pdf->SetCellPadding(1);
                    $pdf->SetCellPaddings(1,2,1,1);
                }
                if( ($reg_list_height > 0 && $pdf->getY() > ($pdf->getPageHeight() - 37 - $reg_list_height)) 
                    || ($reg_list_height == 0 && $pdf->getY() > ($pdf->getPageHeight() - 40)) 
                    ) {
                    /*
                    $pdf->setCellPadding(0);
                    $pdf->SetFont('', '', '12');
                    $pdf->Cell(180, '', '', 'B', 0, 'R', 0);
                    $pdf->setCellPadding(1);
                    $pdf->AddPage();
                    $pdf->SetFont('', 'B', '16');
                    if( $pdf->getStringWidth($division['date'] . ' - ' . $division['name'] . ' (continued...)', '', 'B', 16) > 180 ) {
                        $pdf->MultiCell(180, 10, $division['date'] . "\n" . $division['name'] . ' (continued...)', 0, 'L', 0);
                    } else {
                        $pdf->Cell(180, 10, $division['date'] . ' - ' . $division['name'] . ' (continued...)', 0, 0, 'L', 0);
                        $pdf->Ln(10);
                    }
                    $pdf->SetFont('', '', '12');
                    if( $address != '' ) {
                        $pdf->MultiCell(180, '', $address, 0, 'L', 0, 2);
                        $pdf->Ln(5);
                    } 
                    */
                    $pdf->DivisionHeader($args, $section, $division, 'yes');
                    $pdf->SetFont('', '', '12');
                    $pdf->Ln(1);
                    $border = 'T';
                    $pdf->SetCellPaddings(1,2,1,1);
                } else {
                    if( $time != '' ) {
                        $pdf->SetCellPaddings(1,3,1,1);
                    } else {
                        $pdf->SetCellPaddings(1,0,1,1);
                    }
                }
                $pdf->Ln(2);

                $pdf->SetFont('', 'B');
                $lh = $pdf->getStringHeight($w[2], $name);
                $pdf->Multicell($w[0], $lh, $time, $border, 'R', 0, 0);
                $pdf->Multicell($w[1], $lh, '', $border, 'R', 0, 0);
                $pdf->Multicell($w[2], $lh, $name, $border, 'L', 0, 1);
                $pdf->SetFont('', '');
   
                $pdf->SetCellPadding(0);
                foreach($reg_list as $row) {
                    $pdf->MultiCell($w[0], $row['height'], '', 0, 'L', 0, 0);
                    if( $row['participation'] == 2 ) {
                        $pdf->MultiCell($w[1]+1, $row['height'], '+', 0, 'C', 0, 0);
                    } else {
                        $pdf->MultiCell($w[1]+1, $row['height'], '', 0, 'L', 0, 0);
                    }
                    if( isset($args['titles']) && $args['titles'] == 'yes' ) {
                        $pdf->MultiCell($row['name_width'], $row['name_height'], $row['name'], 0, 'L', 0, 0);
                        if( isset($row['title1']) && $row['title1'] != '' ) {
                            $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                            $pdf->MultiCell($row['title_width'], '', $row['title1'], 0, 'L', 0, 1);
                        } else {
                            $pdf->Ln(5);
                        }
                        for($i = 2; $i <= 8; $i++) {
                            if( isset($row["title{$i}"]) && $row["title{$i}"] != '' ) {
                                $pdf->MultiCell($w[0] + $w[1] + $row['name_width'] + 1, $row['height'], '', 0, 'L', 0, 0);
                                $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                                $pdf->MultiCell($row['title_width'], '', $row["title{$i}"], 0, 'L', 0, 1);
                            }
                        }
                        if( $row['name_height'] > $row['titles_height'] ) {    
                            
                           // $pdf->Ln($row['name_height'] - $row['titles_height']);
                        }
                    } else {
                        $pdf->MultiCell($w[2], $row['height'], $row['name'], 0, 'L', 0, 1);
                    }
                    $pdf->Ln(1);
                }
                $pdf->SetCellPadding(1);

                $fill=!$fill;
                $border = 'T';
                $pdf->SetCellPaddings(1,3,1,1);
            }
            $pdf->SetCellPaddings(0,0,0,0);
            $pdf->Ln(2);
            $pdf->Cell($w[0]+$w[1]+$w[2], 0, '', 'T', 1, 'R', 0);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
