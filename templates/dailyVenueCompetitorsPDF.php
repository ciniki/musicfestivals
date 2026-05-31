<?php
//
// Description
// ===========
// This function will produce a PDF that can be used for door entry.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_dailyVenueCompetitorsPDF(&$ciniki, $tnid, $args) {

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
//        . "divisions.location_id, "
        . "CONCAT_WS('-', divisions.location_id, divisions.division_date) AS location_id, "
        . "locations.name AS location_name, " 
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, ";
    if( isset($festival['runsheets-separate-classes']) && $festival['runsheets-separate-classes'] == 'yes' ) {
        $strsql .= "CONCAT_WS('-', timeslots.id, classes.id) AS timeslot_id, ";
    } else {
        $strsql .= "timeslots.id AS timeslot_id, ";
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, ";
    } else {
        $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
    }
//    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
    $strsql .= "timeslots.name AS timeslot_name, "
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
        . "registrations.competitor5_id, "
        . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
        . "phones.id AS phone_id, "
        . "phones.phone_label, "
        . "phones.phone_number, "
        . "competitors.id AS competitor_id, "
        . "competitors.ctype, "
        . "competitors.first AS competitor_first, "
        . "competitors.last AS competitor_last, "
        . "competitors.name AS competitor_name, "
        . "registrations.participation, "
        . "registrations.notes, "
        . "registrations.internal_notes, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
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
            . "timeslots.id = registrations.timeslot_id ";
    if( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    } else {    // default to live only
        $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    }
    $strsql .= "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS accompanists ON ("
            . "registrations.accompanist_customer_id = accompanists.id "
            . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_phones AS phones ON ("
            . "accompanists.id = phones.customer_id "
            . "AND phones.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($festival['dailyvenuecompetitors-sort-order']) && $festival['dailyvenuecompetitors-sort-order'] == 'time' ) {
        $strsql .= "ORDER BY locations.name, divisions.division_date, timeslots.slot_time, registrations.timeslot_time, competitors.last, competitors.first, competitors.name, registrations.display_name ";
    } else {
        $strsql .= "ORDER BY locations.name, divisions.division_date, competitors.last, competitors.first, competitors.name, registrations.display_name, timeslots.slot_time ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'locations', 'fname'=>'location_id', 
            'fields'=>array('date'=>'division_date_text', 'name'=>'location_name'),
            ),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'ctype',
                'first'=>'competitor_first', 'last'=>'competitor_last',
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'participation',
                'section_name', 'division_name', 'slot_time'=>'slot_time_text',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                'notes', 'internal_notes',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name', 
                'accompanist_name', 
                ),
            ),
        array('container'=>'phones', 'fname'=>'phone_id', 
            'fields'=>array('label'=>'phone_label', 'number'=>'phone_number',
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $locations = isset($rc['locations']) ? $rc['locations'] : array();

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
                    if( $width > 600 ) {
                        $this->header_image->scaleImage(600, 0);
                    }
                    $image_ratio = $width/$height;
                    $img_width = 60;
                    $available_ratio = $img_width/$this->header_height;
                    // Check if the ratio of the image will make it too large for the height,
                    // and scaled based on either height or width.
                    if( $available_ratio < $image_ratio ) {
//                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150');
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 2, '150', '', false, false, 0, true);
                    } else {
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', 2, '150');
                    }
                }

                $this->Ln(5);
                $this->SetFont('helvetica', 'B', 14);
                if( $img_width > 0 ) {
                    $this->Cell($img_width, 10, '', 0);
                }
                $this->setX($this->left_margin + $img_width);
//                $this->Cell(180-$img_width, 10, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->MultiCell(180-$img_width, 0, $this->header_title, 0, 'R', 0, 1);
//                $this->Ln(7);

                $this->SetFont('helvetica', '', 14);
                $this->setX($this->left_margin + $img_width);
                //$this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->MultiCell(180-$img_width, 0, $this->header_sub_title, 0, 'R', 0, 1);
//                $this->MultiCell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
//                $this->Ln(7);

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

        public function DivisionHeader($args, $location, $continued) {
            $fields = array();
            $fields = array('date');
            if( $continued == 'yes' ) {
                $location['date'] .= ' (continued...)';
            } 
            // Figure out how much room the location header needs
            $h = 0;
            $this->SetFont('', 'B', '16');
            foreach($fields as $field) {
                if( isset($location[$field]) && $location[$field] != '' ) {
                    $h += $this->getStringHeight(180, $location[$field]);
                }
                $this->SetFont('', '', '13');
            }
            // Check if enough room for location header and at least 1 timeslot
            if( $this->getY() > $this->getPageHeight() - $h - 80) {
                $this->AddPage();
            } elseif( $this->getY() > 80 ) {
                $this->Ln(10); 
            }
            // Output the location header
            $this->SetFont('', 'B', '16');
            $this->SetCellPaddings(3, 3, 3, 3);
            $this->SetFillColor(225);
            foreach($fields as $field) {
                $this->MultiCell(180, 0, $location[$field], 1, 'C', 1, 1);
                $this->SetFont('', '', '13');
                $this->SetCellPaddings(3, 1, 3, 3);
            }
            $this->SetFillColor(246);

            // Output the column headings
            $this->setFont('', 'B', '12');
            $this->SetCellPaddings(2, 2, 2, 2);
            if( isset($args['headings']) ) {
                $h = $this->getStringHeight($args['headings'][0]['width'], $args['headings'][0]['label']);
                $last_col = count($args['headings']) - 1;
                foreach($args['headings'] as $col => $heading) {
                    $this->MultiCell($heading['width'], $h, $heading['label'], 1, 'C', 1, ($col == $last_col ? 1 : 0));
                }
            }
            $this->setFont('', '', '12');
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
    $pdf->header_msg = '';
    $pdf->footer_msg = '';

    if( !isset($args['footerdate']) || $args['footerdate'] == 'yes' ) {
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
    $pdf->SetTitle($festival['name'] . ' - Schedule ' . $festival['competitor-label-plural']);
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

    $filename = 'Schedule ' . $festival['competitor-label-plural'];

    //
    // Go through the sections, divisions and classes
    //
    $w = array(30, 5, 145);
    $w = array(90, 90);
    if( isset($festival['dailyvenuecompetitors-sort-order']) && $festival['dailyvenuecompetitors-sort-order'] == 'time' ) {
        $w = [25, 155];
        $args['headings'] = [
            ['width' => 25, 'label' => 'Time'],
            ['width' => 155, 'label' => 'Name'],
            ];
        if( isset($festival['dailyvenuecompetitors-music-check']) && $festival['dailyvenuecompetitors-music-check'] == 'yes' ) {
            $music_col = count($w);
            $w[1] -= 20;
            $w[] = 20;
            $args['headings'][1]['width'] -= 20;
            $args['headings'][] = ['width' => 20, 'label' => 'Music'];
        }
        if( isset($festival['dailyvenuecompetitors-accompanist-info']) && $festival['dailyvenuecompetitors-accompanist-info'] == 'yes' ) {
            $accompanist_col = count($w);
            $w[1] -= 65;
            $w[] = 65;
            $args['headings'][1]['width'] -= 65;
            $args['headings'][] = ['width' => 65, 'label' => 'Accompanist'];
        }
    } else {
        $args['headings'] = [
            ['width' => 150, 'label' => 'Name'],
            ['width' => 30, 'label' => 'Time'],
            ];
        if( isset($festival['dailyvenuecompetitors-music-check']) && $festival['dailyvenuecompetitors-music-check'] == 'yes' ) {
            $music_col = count($w);
            $w[0] -= 10;
            $w[1] -= 10;
            $w[] = 20;
            $args['headings'][0]['width'] -= 10;
            $args['headings'][1]['width'] -= 10;
            $args['headings'][] = ['width' => 20, 'label' => 'Music'];
        }
        if( isset($festival['dailyvenuecompetitors-accompanist-info']) && $festival['dailyvenuecompetitors-accompanist-info'] == 'yes' ) {
            $accompanist_col = count($w);
            $w[0] -= 30;
            $w[1] -= 30;
            $w[] = 60;
            $args['headings'][0]['width'] -= 30;
            $args['headings'][1]['width'] -= 30;
            $args['headings'][] = ['width' => 60, 'label' => 'Accompanist'];
        }
    }
    if( !isset($music_col) && !isset($accompanist_col) ) {
        unset($args['headings']);
    }
    foreach($locations as $location) {
        if( count($locations) == 1 ) {
            $filename .= ' - ' . $location['name'];
        }

        if( !isset($location['competitors']) ) {
            continue;
        }

        //
        // Start a new section
        //
        $pdf->header_title = 'Schedule ' . $festival['competitor-label-plural'];
        $pdf->header_sub_title = $location['name'];
        $pdf->AddPage();
        //
        // Setup the division header
        //
        $pdf->DivisionHeader($args, $location, 'no');
        $pdf->SetFont('', '', '12');

        if( isset($festival['dailyvenuecompetitors-sort-order']) && $festival['dailyvenuecompetitors-sort-order'] == 'time' ) {
            uasort($location['competitors'], function($a, $b) {
                
                });
        }
        foreach($location['competitors'] as $competitor) {
            $times = '';
            $accompanist_info = '';
            if( isset($competitor['registrations']) ) {
                $prev_time = '';
                foreach($competitor['registrations'] as $reg) {
                    if( $reg['slot_time'] != '' && $prev_time != $reg['slot_time']) {
                        $times .= ($times != '' ? ', ' : '') . $reg['slot_time'];
                        $prev_time = $reg['slot_time'];
                    }
                    if( $reg['accompanist_name'] != '' && !str_contains($accompanist_info, $reg['accompanist_name']) ) {
                        $accompanist_info .= ($accompanist_info != '' ? "\n" : '') . $reg['accompanist_name'];
                        $phones = '';
                        if( isset($reg['phones']) ) {
                            foreach($reg['phones'] as $phone) {
                                if( !str_contains($phones, $phone['number']) ) {
                                    $phones .= ($phones != '' ? ', ' : '') . $phone['number'];
                                }
                            }
                        }
                        if( $phones != '' ) {
                            $accompanist_info .= " {$phones}";
                        }
                    }
                }
            }
            if( $competitor['ctype'] == 10 ) {
                $competitor['name'] = $competitor['last'] . ', ' . $competitor['first'];
            }

            $pdf->SetFont('', '', '12');
            $pdf->SetCellPaddings(2, 2, 2, 2);
            if( isset($festival['dailyvenuecompetitors-sort-order']) && $festival['dailyvenuecompetitors-sort-order'] == 'time' ) {
                $h = $pdf->getStringHeight($w[1], $competitor['name']);
                if( $pdf->getStringHeight($w[0], $times) > $h ) {
                    $h = $pdf->getStringHeight($w[0], $times);
                }
            } else {
                $h = $pdf->getStringHeight($w[0], $competitor['name']);
                if( $pdf->getStringHeight($w[1], $times) > $h ) {
                    $h = $pdf->getStringHeight($w[1], $times);
                }
            }
            if( isset($accompanist_col) && $pdf->getStringHeight($w[$accompanist_col], $accompanist_info) > $h ) {
                $h = $pdf->getStringHeight($w[$accompanist_col], $accompanist_info);
            }

            if( $pdf->GetY() > ($pdf->getPageHeight() - $h - 22)) {
                $pdf->AddPage();
                $pdf->DivisionHeader($args, $location, 'yes');
            }
            $pdf->SetCellPaddings(2, 2, 2, 2);

            $pdf->SetFont('', '', '12');
            if( isset($festival['dailyvenuecompetitors-sort-order']) && $festival['dailyvenuecompetitors-sort-order'] == 'time' ) {
                $pdf->MultiCell($w[0], $h, $times, 1, 'L', 0, 0);
                $pdf->MultiCell($w[1], $h, $competitor['name'], 1, 'L', 0, (!isset($music_col) && !isset($accompanist_col) ? 1 : 0));
            } else {
                $pdf->MultiCell($w[0], $h, $competitor['name'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[1], $h, $times, 1, 'L', 0, (!isset($music_col) && !isset($accompanist_col) ? 1 : 0));
            }
            if( isset($music_col) ) {
                $pdf->MultiCell($w[$music_col], $h, '', 1, 'L', 0, (!isset($accompanist_col) ? 1 : 0));
            }
            if( isset($accompanist_col) ) {
                $pdf->MultiCell($w[$accompanist_col], $h, $accompanist_info, 1, 'L', 0, 1);
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
