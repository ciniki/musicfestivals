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
function ciniki_musicfestivals_templates_scheduleClassesPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');

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

    $division_date_format = '%b %d';

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "categories.name AS category_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.groupname, "
        . "IFNULL(locations.id, 0) AS location_id, "
        . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name, "
        . "DATE_FORMAT(divisions.division_date, '" . ciniki_core_dbQuote($ciniki, $division_date_format) . "') AS division_date_text, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "registrations.id AS registration_id, "
        . "registrations.private_name "
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
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
    if( isset($args['division_id']) && $args['division_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
        $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, class_code, divisions.division_date, timeslots.slot_time, groupname, registrations.timeslot_sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'classes', 'fname'=>'class_code', 
            'fields'=>array('code'=>'class_code', 'name'=>'class_name', 'category_name', 'section_name'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('location_name', 'division_date_text', 'slot_time_text', 'groupname'),
            ),
        array('container'=>'registrations', 'fname'=>'registration_id', 
            'fields'=>array('id'=>'registration_id', 'private_name'),
            ),
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
        public $left_margin = 15;
        public $right_margin = 15;
        public $top_margin = 15;
        public $header_visible = 'yes';
        public $header_image = null;
        public $header_sponsor_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_visible = 'yes';
        public $footer_image = null;
        public $footer_image_height = 0;
        public $footer_msg = '';
        public $tenant_details = array();
        public $continued_str = ' (continued...)';

        public function Header() {
            if( $this->header_visible == 'yes' ) {
                //
                // Check if there is an image to be output in the header.   The image
                // will be displayed in a narrow box if the contact information is to
                // be displayed as well.  Otherwise, image is scaled to be 100% page width
                // but only to a maximum height of the header_height (set far below).
                //
                $img_width = 0;
                if( $this->header_image != null && $this->header_sponsor_image != null ) {
                    $width = $this->header_image->getImageWidth();
                    if( $width > 600 ) {
                        $this->header_image->scaleImage(600, 0);
                    }
                    $img_width = ($this->header_height-8)*2;
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 0, '150', '', false, false, 0, true);
                    $offset = $img_width - (($this->header_sponsor_image->getImageWidth()*($this->header_height-8))/$this->header_sponsor_image->getImageHeight());
                    $this->Image('@'.$this->header_sponsor_image->getImageBlob(), $this->left_margin + $img_width + (180-($img_width*2)) + $offset, 10, $img_width, $this->header_height-8, '', '', 'R', 0, '150', '', false, false, 0, true);
                    $this->SetY(10);
                    $this->SetX($this->left_margin + $img_width);
                    $this->SetFont('helvetica', 'B', 20);
                    $this->MultiCell(180-($img_width*2), 0, $this->header_title, 0, 'C', 0, 1);

                    $this->SetFont('helvetica', 'B', 14);
                    $this->setX($this->left_margin + $img_width);
                    $this->MultiCell(180-($img_width*2), 0, $this->header_sub_title, 0, 'C', 0, 1);

                    $this->SetFont('helvetica', 'B', 12);
                    $this->setX($this->left_margin + $img_width);
                    $this->MultiCell(180-($img_width*2), 0, $this->header_msg, 0, 'C', 0, 1);
            
                } else {
                    if( $this->header_image != null ) {
                        $height = $this->header_image->getImageHeight();
                        $width = $this->header_image->getImageWidth();
                        if( $width > 600 ) {
                            $this->header_image->scaleImage(600, 0);
                        }
                        $image_ratio = $width/$height;
                        $img_width = 50;
                        $available_ratio = $img_width/$this->header_height;
                        // Check if the ratio of the image will make it too large for the height,
                        // and scaled based on either height or width.
                        if( $available_ratio < $image_ratio ) {
    //                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150', '', false, false, 0);
                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 2, '150', '', false, false, 0, true);
                        } else {
                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', 2, '150');
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
                }
            } else {
                // No header
            }

        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            if( $this->footer_visible == 'yes' ) {
                if( $this->footer_image != null ) {
                    $this->SetY(-(15+$this->footer_image_height));
                    $this->Image('@'.$this->footer_image->getImageBlob(), $this->left_margin, '', 180, '', '', '', 'L', 2, '150', '', false, false, 0, true);
                    $this->Ln($this->footer_image_height);
                } else {
                    $this->SetY(-15);
                }
                $this->SetFont('helvetica', '', 10);
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
        } }

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
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.1);

    $filename = 'Schedule Classes';
    $pdf->AddPage();

    //
    // Go through the sections, divisions and classes
    //
    $w = array(20, 166);
    foreach($sections as $section) {

        $pdf->SetFont('', 'B', '14');
        $pdf->MultiCell(180, 0, $section['name'], 0, 'L', 0, 1);
        $pdf->SetFont('', '', '12');

        foreach($section['classes'] as $class) {
            
            foreach($class['timeslots'] as $timeslot) {
                $num_reg = 0;
                $names = '';
                foreach($timeslot['registrations'] as $reg) {
                    $num_reg++;
                    $names .= ($names != '' ? ', ' : '') . $reg['private_name'];
                }
                if( $names != '' ) {
                    $names = "[$num_reg] " . $names;
                }
                $txt = "{$class['category_name']} - {$class['name']} - "
                    . ($timeslot['groupname'] != '' ? "{$timeslot['groupname']} - " : '')
                    . "{$timeslot['location_name']} - {$timeslot['division_date_text']} - {$timeslot['slot_time_text']}";
//                $txt .= " - {$num_reg}";
                $lh = $pdf->getStringHeight($w[1], $txt);
                $nlh = 0;
                if( isset($args['names']) && $args['names'] == 'yes' && $names != '' ) {
                    $nlh = $pdf->getStringHeight($w[1], $names);
                }
                if( $pdf->getY() > $pdf->getPageHeight() - $lh - $nlh - 20 ) {
                    $pdf->AddPage();
                }
                $pdf->MultiCell($w[0], $lh, $class['code'], 0, 'L', 0, 0);
                $pdf->MultiCell($w[1], $lh, $txt, 0, 'L', 0, 1);
                if( isset($args['names']) && $args['names'] == 'yes' && $names != '' ) {
                    $pdf->MultiCell($w[0], $nlh, '', 'B', 'L', 0, 0);
                    $pdf->MultiCell($w[1], $nlh, $names, 'B', 'L', 0, 1);
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
