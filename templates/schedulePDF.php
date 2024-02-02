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
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "timeslots.class4_id, "
        . "timeslots.class5_id, "
        . "IFNULL(class1.name, '') AS class1_name, "
        . "IFNULL(class2.name, '') AS class2_name, "
        . "IFNULL(class3.name, '') AS class3_name, "
        . "IFNULL(class4.name, '') AS class4_name, "
        . "IFNULL(class5.name, '') AS class5_name, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.participation "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
            . "timeslots.class2_id = class2.id " 
            . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class4 ON ("
            . "timeslots.class4_id = class4.id " 
            . "AND class4.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class5 ON ("
            . "timeslots.class5_id = class5.id " 
            . "AND class5.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
/*            . "(timeslots.class1_id = registrations.class_id "  
                . "OR timeslots.class2_id = registrations.class_id "
                . "OR timeslots.class3_id = registrations.class_id "
                . "OR timeslots.class4_id = registrations.class_id "
                . "OR timeslots.class5_id = registrations.class_id "
                . ") "
            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) " */
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY divisions.division_date, division_id, slot_time, registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'class1_id', 'class2_id', 'class3_id', 'class4_id', 'class5_id', 'description', 
                'class1_name', 'class2_name', 'class3_name', 'class4_name', 'class5_name',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title1', 'title2', 'title3', 'participation'),
            ),
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
                $this->SetFont('times', 'B', 20);
                if( $img_width > 0 ) {
                    $this->Cell($img_width, 10, '', 0);
                }
                $this->setX($this->left_margin + $img_width);
                $this->Cell(180-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->Ln(7);

                $this->SetFont('times', 'B', 14);
                $this->setX($this->left_margin + $img_width);
                $this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->Ln(6);

                $this->SetFont('times', 'B', 12);
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
    $pdf->SetFont('times', 'BI', 10);
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
    $w = array(30, 5, 145);
    foreach($sections as $section) {
        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'] . ' Schedule';
        if( isset($args['schedulesection_id']) ) {
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $section['name']) . '_schedule';
        }
        $pdf->AddPage();

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
            //
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
                $newpage = 'yes';
            } elseif( $newpage == 'no' ) {
                $pdf->Ln(15);
            }
            $newpage = 'no';

            $pdf->SetFont('', 'B', '16');
            if( $pdf->getStringWidth($division['date'] . ' - ' . $division['name'], '', 'B', 16) > 180 ) {
                $pdf->MultiCell(180, 10, $division['date'] . "\n" . $division['name'], 0, 'L', 0);
            } else {
                $pdf->Cell(180, 10, $division['date'] . ' - ' . $division['name'], 0, 0, 'L', 0);
                $pdf->Ln(10);
            }
            $pdf->SetFont('', '', '12');
            if( $address != '' ) {
                $pdf->MultiCell(180, '', $address, 0, 'L', 0, 2);
            }
            $fill = 1;
            
            //
            // Output the timeslots
            //
            $fill = 0;
            $border = 'T';
            foreach($division['timeslots'] as $timeslot) {
                $name = $timeslot['name'];
                $description = $timeslot['description'];
                $reg_list = array();
                $reg_list_height = 0;
                if( $timeslot['class1_id'] > 0 ) {
                    if( $name == '' && $timeslot['class1_name'] != '' ) {
                        $name = $timeslot['class1_name'];
                    }
                    if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                        $pdf->SetFont('', '', '12');
                        $pdf->SetCellPadding(0);
                        foreach($timeslot['registrations'] as $reg) {
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
                                $row['title1'] = $reg['title1'];
                                $row['titles_height'] = $pdf->getStringHeight($row['title_width'], $row['title1']);
                                if( $reg['title2'] != '' ) {
                                    $row['title2'] = $reg['title2'];
                                    $row['titles_height'] += $pdf->getStringHeight($row['title_width'], $row['title2']);
                                }
                                if( $reg['title3'] != '' ) {
                                    $row['title3'] = $reg['title3'];
                                    $row['titles_height'] += $pdf->getStringHeight($row['title_width'], $row['title3']);
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
                    }
                }
                if( ($reg_list_height > 0 && $pdf->getY() > ($pdf->getPageHeight() - 37 - $reg_list_height)) 
                    || ($reg_list_height == 0 && $pdf->getY() > ($pdf->getPageHeight() - 40)) 
                    ) {
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
                } else {
                    $pdf->Ln(5);
                }

                $pdf->SetFont('', 'B');
                $lh = $pdf->getStringHeight($w[2], $name);
                $pdf->Multicell($w[0], $lh, $timeslot['time'], $border, 'R', 0, 0);
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
                        if( $row['title1'] != '' ) {
                            $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                            $pdf->MultiCell($row['title_width'], '', $row['title1'], 0, 'L', 0, 1);
                        } else {
                            $pdf->Ln(5);
                        }
                        if( isset($row['title2']) && $row['title2'] != '' ) {
                            $pdf->MultiCell($w[0] + $w[1] + $row['name_width'] + 1, $row['height'], '', 0, 'L', 0, 0);
                            $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                            $pdf->MultiCell($row['title_width'], '', $row['title2'], 0, 'L', 0, 1);
                        }
                        if( isset($row['title3']) && $row['title3'] != '' ) {
                            $pdf->MultiCell($w[0] + $w[1] + $row['name_width'] + 1, $row['height'], '', 0, 'L', 0, 0);
                            $pdf->MultiCell($row['dash_width'], '', '-', 0, 'C', 0, 0);
                            $pdf->MultiCell($row['title_width'], '', $row['title3'], 0, 'L', 0, 1);
                        }
                        if( $row['name_height'] > $row['titles_height'] ) {    
                            $pdf->Ln($row['name_height'] - $row['titles_height']);
                        }
                    } else {
                        $pdf->MultiCell($w[2], $row['height'], $row['name'], 0, 'L', 0, 1);
                    }
                    $pdf->Ln(1);
                }
                $pdf->SetCellPadding(1);

                $fill=!$fill;
                $border = 'T';
            }
            $pdf->Cell($w[0]+$w[1]+$w[2], 1, '', 'B', 0, 'R', 0);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
