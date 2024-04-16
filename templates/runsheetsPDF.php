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

    if( isset($festival['runsheets-page-orientation']) && $festival['runsheets-page-orientation'] == 'landscape' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'compactRunSheetsPDF');
        return ciniki_musicfestivals_templates_compactRunSheetsPDF($ciniki, $tnid, $args);
    }

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "customers.display_name AS adjudicator_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, ";
    if( isset($festival['runsheets-separate-classes']) && $festival['runsheets-separate-classes'] == 'yes' ) {
        $strsql .= "CONCAT_WS('-', timeslots.id, classes.id) AS timeslot_id, ";
    } else {
        $strsql .= "timeslots.id AS timeslot_id, ";
    }
    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "timeslots.runsheet_notes, "
        . "registrations.id AS reg_id, ";
    if( isset($festival['runsheets-include-pronouns']) && $festival['runsheets-include-pronouns'] == 'yes' ) {
        $strsql .= "registrations.pn_display_name AS display_name, "
            . "registrations.pn_public_name AS public_name, ";
    } else {
        $strsql .= "registrations.display_name, "
            . "registrations.public_name, ";
    }
    $strsql .= "registrations.competitor1_id, "
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
        . "";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) ) {
        $strsql .= "trophies.id AS trophy_id, "
            . "trophies.name AS trophy_name, ";
    } else {
        $strsql .= "0 AS trophy_id, '' AS trophy_name, ";
    }
    $strsql .= "classes.code AS class_code, "
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
            . ") ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) ) {
        $strsql .= "LEFT JOIN ciniki_musicfestival_trophy_classes AS tclasses ON ("
            . "classes.id = tclasses.class_id "
            . "AND tclasses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_trophies AS trophies ON ("
            . "tclasses.trophy_id = trophies.id "
            . "AND tclasses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    }
    $strsql .= "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
//        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
        $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.division_date, slot_time, registrations.timeslot_sequence, class_code, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'ssections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator_name'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'description', 'runsheet_notes', 
                'class_code', 'class_name', 'category_name', 'syllabus_section_name',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'participation',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id',
                'notes', 'internal_notes',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                ),
            ),
        array('container'=>'trophies', 'fname'=>'trophy_id', 
            'fields'=>array('id'=>'trophy_id', 'name'=>'trophy_name'),
            ),

        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['ssections']) ? $rc['ssections'] : array();

    //
    // Load competitor notes
    //
    if( isset($festival['runsheets-competitor-notes']) && $festival['runsheets-competitor-notes'] == 'yes' ) {
        $strsql = "SELECT competitors.id, "
            . "competitors.notes "
            . "FROM ciniki_musicfestival_competitors AS competitors "
            . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND competitors.notes <> '' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'notes')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.683', 'msg'=>'Unable to load cnotes', 'err'=>$rc['err']));
        }
        $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();
    }

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
                $this->SetY(-15);
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->SetFont('helvetica', '', 10);
                $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            } else {
                // No footer
            }
        }

        public function DivisionHeader($args, $section, $division, $continued) {
            $fields = array();
/*            if( isset($args['division_header_format']) && $args['division_header_format'] != 'default' ) {
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
            } else { */
                // Default layout
                $fields = array('section');
                $division['section'] = $section['name'];
                if( $continued == 'yes' ) {
                    $division['section'] .= ' (continued...)';
                }
//            }
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
            $this->SetCellPaddings(3, 3, 3, 3);
            $this->SetFillColor(225);
            foreach($fields as $field) {
                $this->MultiCell(180, 0, $division[$field], 0, 'C', 1, 1);
                $this->SetFont('', '', '13');
            }
            $this->SetFillColor(246);
            $this->Ln(4);
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
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.1);

    $filename = 'Run Sheets';

    $newpage = 'yes';
    //
    // Go through the sections, divisions and classes
    //
    $w = array(30, 5, 145);
    foreach($sections as $section) {
        if( count($sections) == 1 ) {
            $filename .= ' - ' . $section['name'];
        }

        if( !isset($section['divisions']) ) {
            continue;
        }

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


            //
            // Start a new section
            //
            $pdf->header_title = $division['date'];
            $pdf->header_sub_title = 'Adjudicator: ' . $section['adjudicator_name'];
            $pdf->header_msg = 'Location: ' . $division['address'];
            $pdf->AddPage();

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
            if( (!isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes')
                && (!isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes') 
                ) {
                $w = array(10, 100, 15, 15, 40);
            } elseif( !isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes' ) {
                $w = array(10, 140, 15, 15);
            } elseif( !isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes' ) {
                $w = array(10, 115, 15, 40);
            } else {
                $w = array(10, 155, 15);
            }
            $cw = array(30, 150);   // Class lines
            $tw = array(10, 170);   // Title lines
            $tnw = array(10, 15, 155);   // reg notes lines
            $trw = array(22, 128);   // Trophy lines
            $prev_time = '';
            foreach($division['timeslots'] as $timeslot) {

                if( $timeslot['name'] == '' ) {
                    // FIXME: Add check for joined section/category/class
                    $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name'];
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
                // Check height required
                //
                $pdf->SetFont('', 'B', '14');
                $pdf->SetCellPadding(2);
                $h = $pdf->getStringHeight($cw[1], $name); 
                if( isset($timeslot['runsheet_notes']) && $timeslot['runsheet_notes'] != '' ) {
                    $pdf->SetFont('', '', 12);
                    $h += $pdf->getStringHeight($cw[1], $name); 
                }
                $timeslot['trophies'] = array();
                if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                    $pdf->SetFont('', 'B', 12);
                    $h += $pdf->getStringHeight($w[1], $timeslot['registrations'][0]['name']);
                    $pdf->SetFont('', '');
                    $pdf->SetCellPadding(2);
                    // FIXME: add height of titles
                    foreach($timeslot['registrations'] as $rid => $reg) {
                        for($i = 1; $i <= 8; $i++) {
                            if( $reg["title{$i}"] != '' ) {
                                $timeslot['registrations'][$rid]['last_title'] = $i;
                                $perf_time = '??';
                                if( $reg["perf_time{$i}"] != '' && is_numeric($reg["perf_time{$i}"]) ) {
                                    $perf_time = intval($reg["perf_time{$i}"]/60) 
                                        . ':' 
                                        . str_pad(($reg["perf_time{$i}"]%60), 2, '0', STR_PAD_LEFT);
                                }
                                $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
                                if( isset($rc['title']) ) {
                                    $timeslot['registrations'][$rid]["title{$i}"] = "- [{$perf_time}] " . $rc['title'];
                                }
                                $h += $pdf->getStringHeight($tw[1], $timeslot['registrations'][$rid]["title{$i}"]);
                            }
                        }
                        //
                        // Setup the notes for the registration
                        //
                        $notes = '';
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
                        if( isset($festival['runsheets-competitor-notes']) && $festival['runsheets-competitor-notes'] == 'yes'
                            && isset($competitors[$reg['competitor1_id']]['notes']) 
                            && $competitors[$reg['competitor1_id']]['notes'] != '' 
                            ) {
                            $notes .= ($notes != '' ? "\n" : '') . $competitors[$reg['competitor1_id']]['notes'];
                        }
                        if( isset($festival['runsheets-competitor-notes']) && $festival['runsheets-competitor-notes'] == 'yes'
                            && isset($competitors[$reg['competitor2_id']]['notes']) 
                            && $competitors[$reg['competitor2_id']]['notes'] != '' 
                            ) {
                            $notes .= ($notes != '' ? "\n" : '') . $competitors[$reg['competitor2_id']]['notes'];
                        }
                        if( isset($festival['runsheets-competitor-notes']) && $festival['runsheets-competitor-notes'] == 'yes'
                            && isset($competitors[$reg['competitor3_id']]['notes']) 
                            && $competitors[$reg['competitor3_id']]['notes'] != '' 
                            ) {
                            $notes .= ($notes != '' ? "\n" : '') . $competitors[$reg['competitor3_id']]['notes'];
                        }
                        if( isset($festival['runsheets-competitor-notes']) && $festival['runsheets-competitor-notes'] == 'yes'
                            && isset($competitors[$reg['competitor4_id']]['notes']) 
                            && $competitors[$reg['competitor4_id']]['notes'] != '' 
                            ) {
                            $notes .= ($notes != '' ? "\n" : '') . $competitors[$reg['competitor4_id']]['notes'];
                        }
                        $timeslot['registrations'][$rid]['combined_notes'] = $notes;
                        if( $notes != '' ) {
                            $pdf->SetCellPaddings(2,2,2,2);
                            $pdf->SetFont('', '', '11');
                            $h += $pdf->getStringHeight($tnw[2], $notes);
                        }

                        if( isset($reg['trophies']) && count($reg['trophies']) > 0 ) {
                            foreach($reg['trophies'] as $trophy) {
                                if( $trophy['name'] != '' && !in_array($trophy['name'], $timeslot['trophies']) ) {
                                    $timeslot['trophies'][] = $trophy['name'];
                                    $h += $pdf->getStringHeight($trw[1], $trophy['name']);
                                }
                            }
                        }
                    }
                } else {
                    $h += 5;
                }
               
                if( $pdf->GetY() > 70 && $pdf->GetY() > $pdf->getPageHeight() - $h - 32) {
                    $pdf->AddPage();
                    $pdf->DivisionHeader($args, $section, $division, 'yes');
                    $pdf->SetFont('', '', '12');
                }

                $pdf->SetFont('', 'B', '14');
                $pdf->SetCellPaddings(0, 1, 0, 0);
                $pdf->MultiCell($cw[0], 0, $time, 0, 'L', 0, 0);
                $pdf->MultiCell($cw[1], 0, $name, 0, 'L', 0, 1);
                $pdf->SetFont('', '', '11');

                if( isset($timeslot['trophies']) && count($timeslot['trophies']) > 0 ) {
                    foreach($timeslot['trophies'] as $tid => $trophy) {
                        $pdf->MultiCell($cw[0], 0, '', 0, 'L', 0, 0);
                        $pdf->MultiCell($trw[0], 0, ($tid == 0 ? 'Eligible for: ' : ''), 0, 'L', 0, 0);
                        $pdf->MultiCell($cw[1], 0, $trophy, 0, 'L', 0, 1);
                    }
                }
                if( isset($timeslot['runsheet_notes']) && $timeslot['runsheet_notes'] != '' ) {
                    $pdf->SetFont('', 'B', 11);
                    $pdf->MultiCell($cw[0], 0, 'Notes', 0, 'L', 0, 0);
                    $pdf->SetFont('', '', 11);
                    $pdf->MultiCell($cw[1], 0, $timeslot['runsheet_notes'], 0, 'L', 0, 1);
                }
                $pdf->Ln(2);

                if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                    $pdf->SetCellPaddings(2,2,2,2);
                    $pdf->SetFont('', 'B');
                    $pdf->MultiCell($w[0], 0, '#', 1, 'C', 1, 0);
                    $pdf->MultiCell($w[1], 0, 'Name', 1, 'L', 1, 0);
                    if( (!isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes')
                        && (!isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes')
                        ) {
                        $pdf->MultiCell($w[2], 0, 'Mark', 1, 'C', 1, 0);
                        $pdf->MultiCell($w[3], 0, 'Place', 1, 'C', 1, 0);
                        $pdf->MultiCell($w[4], 0, 'Advanced to', 1, 'C', 1, 1);
                    } elseif( !isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes' ) {
                        $pdf->MultiCell($w[2], 0, 'Mark', 1, 'C', 1, 0);
                        $pdf->MultiCell($w[3], 0, 'Place', 1, 'C', 1, 1);
                    } elseif( !isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes' ) {
                        $pdf->MultiCell($w[2], 0, 'Place', 1, 'C', 1, 0);
                        $pdf->MultiCell($w[3], 0, 'Advanced to', 1, 'C', 1, 1);
                    } else {
                        $pdf->MultiCell($w[2], 0, 'Place', 1, 'C', 1, 1);
                    }
                    $pdf->SetFont('', '');

                    $pdf->SetFont('', '', '12');
                    $num = 1;
                    foreach($timeslot['registrations'] as $reg) {
                        //
                        // Check height and see if we need new page
                        //
                        $h = 0;
                        $pdf->SetFont('', 'B');
                        $h += $pdf->getStringHeight($w[1], $reg['name']);
                        for($i = 1; $i <= 8; $i++) {
                            if( $reg["title{$i}"] != '' ) {
                                $h += $pdf->getStringHeight($tw[1], $reg["title{$i}"]);
                            }
                        }
                        if( $reg['combined_notes'] != '' ) {
                            $pdf->SetCellPaddings(2,2,2,2);
                            $pdf->SetFont('', '', '11');
                            $h += $pdf->getStringHeight($tnw[2], $reg['combined_notes']);
                        }
                        if( $pdf->GetY() > $pdf->getPageHeight() - $h - 22) {
                            // The following has been added by untested
                            $pdf->AddPage();
                            $pdf->DivisionHeader($args, $section, $division, 'yes');
                            // Set continued class
                            $pdf->SetFont('', 'B', '14');
                            $pdf->SetCellPaddings(0, 1, 0, 0);
                            $pdf->MultiCell($cw[0], 0, $time, 0, 'L', 0, 0);
                            $pdf->MultiCell($cw[1], 0, $name . ' (continued...)', 0, 'L', 0, 1);
                            $pdf->SetFont('', '', '11');
                            if( isset($timeslot['trophies']) && count($timeslot['trophies']) > 0 ) {
                                foreach($timeslot['trophies'] as $tid => $trophy) {
                                    $pdf->MultiCell($cw[0], 0, '', 0, 'L', 0, 0);
                                    $pdf->MultiCell($trw[0], 0, ($tid == 0 ? 'Eligible for: ' : ''), 0, 'L', 0, 0);
                                    $pdf->MultiCell($cw[1], 0, $trophy, 0, 'L', 0, 1);
                                }
                            }
                            if( isset($timeslot['runsheet_notes']) && $timeslot['runsheet_notes'] != '' ) {
                                $pdf->SetFont('', 'B', 11);
                                $pdf->MultiCell($cw[0], 0, 'Notes', 0, 'L', 0, 0);
                                $pdf->SetFont('', '', 11);
                                $pdf->MultiCell($cw[1], 0, $timeslot['runsheet_notes'], 0, 'L', 0, 1);
                            }
                            $pdf->Ln(2);
                            $pdf->SetCellPaddings(2,2,2,2);
                            $pdf->SetFont('', 'B', '11');
                            $pdf->MultiCell($w[0], 0, '#', 1, 'C', 1, 0);
                            $pdf->MultiCell($w[1], 0, 'Name', 1, 'L', 1, 0);
                            if( (!isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes')
                                && (!isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes')
                                ) {
                                $pdf->MultiCell($w[2], 0, 'Mark', 1, 'C', 1, 0);
                                $pdf->MultiCell($w[3], 0, 'Place', 1, 'C', 1, 0);
                                $pdf->MultiCell($w[4], 0, 'Advanced to', 1, 'C', 1, 1);
                            } elseif( !isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes' ) {
                                $pdf->MultiCell($w[2], 0, 'Mark', 1, 'C', 1, 0);
                                $pdf->MultiCell($w[3], 0, 'Advanced to', 1, 'C', 1, 1);
                            } elseif( !isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes' ) {
                                $pdf->MultiCell($w[2], 0, 'Place', 1, 'C', 1, 0);
                                $pdf->MultiCell($w[3], 0, 'Advanced to', 1, 'C', 1, 1);
                            } else {
                                $pdf->MultiCell($w[2], 0, 'Place', 1, 'C', 1, 1);
                            }
                        }
                        $pdf->SetCellPaddings(2,2,2,2);
                        $pdf->SetFont('', 'B', 12);
                        $h = $pdf->getStringHeight($w[1], $reg['name']);
                        $pdf->MultiCell($w[0], $h, $num, 'LTR', 'C', 0, 0);
                        $pdf->MultiCell($w[1], $h, $reg['name'], 'BLTR', 'L', 0, 0);
/*                        if( !isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes' ) {
                            $pdf->MultiCell($w[3], $h, '', 1, 'L', 0, 0);
                            $pdf->MultiCell($w[4], $h, '', 1, 'L', 0, 1);
                        } else {
                            $pdf->MultiCell($w[2], $h, '', 1, 'L', 0, 1);
                        } */
                        if( (!isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes')
                            && (!isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes')
                            ) {
                            $pdf->MultiCell($w[2], $h, '', 1, 'L', 0, 0);
                            $pdf->MultiCell($w[3], $h, '', 1, 'L', 0, 0);
                            $pdf->MultiCell($w[4], $h, '', 1, 'L', 0, 1);
                        } elseif( !isset($festival['runsheets-mark']) || $festival['runsheets-mark'] == 'yes' ) {
                            $pdf->MultiCell($w[2], $h, '', 1, 'L', 0, 0);
                            $pdf->MultiCell($w[3], $h, '', 1, 'L', 0, 1);
                        } elseif( !isset($festival['runsheets-advance-to']) || $festival['runsheets-advance-to'] == 'yes' ) {
                            $pdf->MultiCell($w[2], $h, '', 1, 'L', 0, 0);
                            $pdf->MultiCell($w[3], $h, '', 1, 'L', 0, 1);
                        } else {
                            $pdf->MultiCell($w[2], $h, '', 1, 'L', 0, 1);
                        }
                        $pdf->SetFont('', '');
                        $border = 'LR';
                        $pdf->SetCellPaddings(2,2,2,0);
                        $pdf->SetFont('', '', '11');
                        for($i = 1; $i <= 8; $i++) {
                            if( $reg["title{$i}"] != '' ) {
                                if( $reg['last_title'] == $i && $reg['combined_notes'] == '' ) {
                                    $border = 'LBR';
                                    if( $i == 1 ) {
                                        $pdf->SetCellPaddings(2,2,2,2);
                                    } else {
                                        $pdf->SetCellPaddings(2,0,2,2);
                                    }
                                } elseif( $i > 1 ) {
                                    $pdf->SetCellPaddings(2,0,2,0);
                                }
                                $h = $pdf->getStringHeight($tw[1], $reg["title{$i}"]);
                                $pdf->MultiCell($tw[0], $h, '', $border, 'C', 0, 0);
                                $pdf->MultiCell($tw[1], $h, $reg["title{$i}"], $border, 'L', 0, 1);
                            }
                        }
                        if( $reg['combined_notes'] != '' ) {
                            $pdf->SetCellPaddings(2,2,2,2);
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
                } 
                $pdf->Ln(5);
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
