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
function ciniki_musicfestivals_templates_trophyRegistrationsPDF(&$ciniki, $tnid, $args) {

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

    if( !isset($args['trophies']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.690', 'msg'=>'No trophies specified'));
    }
    $trophies = $args['trophies'];

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

        public function Header() {
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
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, 0, $this->header_height-13, 'JPEG', '', 'L', 2, '150');
                }
            }

            $this->Ln(8);
            $this->SetFont('times', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell(245-$img_width, 12, $this->header_title, 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

/*            $this->SetFont('times', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(245-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('times', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(245-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6); */
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(120, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(125, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'] . ' - Trophies';
    $pdf->header_sub_title = '';
    $pdf->header_msg = '';
    $pdf->footer_msg = '';

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 15;
    }

    //
    // Load the header image
    //
/*    if( isset($festival['document_logo_id']) && $festival['document_logo_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, $festival['document_logo_id'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    } */

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Trophies');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    $pdf->footer_msg = $dt->format("M j, Y");

    // set font
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $filename = preg_replace("/[^a-zA-Z0-9\-]/", '', $festival['name'] . ' - Trophies');

    $pdf->AddPage();

    //
    // Check if marks are to be included
    //
    if( isset($args['marks']) && $args['marks'] == 'yes' ) {
        $w = [30,80,40,80,15];
    } else {
        $w = [30,90,40,85];
    }

    $prev_category = '';
    foreach($trophies as $trophy) {
        if( $pdf->GetY() > $pdf->getPageHeight() - PDF_MARGIN_FOOTER - 70) {
            $pdf->AddPage();
        }
        if( $prev_category != $trophy['category'] && $trophy['category'] != '' ) {
            $pdf->SetCellPadding(4);
            $pdf->SetFont('helvetica', 'B', 16);
            $lh = $pdf->getStringHeight(245, $trophy['category']);
            $prev_category = $trophy['category'];
            if( $pdf->getY() > ($pdf->getPageHeight() - $lh - 55 ) ) {
                $pdf->AddPage();
            }
            $pdf->MultiCell(245, 0, $trophy['category'], 0, 'C', 1, 1);
            $pdf->SetCellPadding(1.5);
        }
        if( isset($args['marks']) && $args['marks'] == 'yes' ) {
            $pdf->SetCellPaddings(2,1.5,2,1.5);
        }

        $pdf->SetFont('helvetica', 'B', 12);
        $lh = $pdf->getStringHeight(245, $trophy['name']);
        $pdf->SetFont('helvetica', '', 12);
        $lh += $pdf->getStringHeight(245, $trophy['criteria']);
        if( $pdf->getY() > ($pdf->getPageHeight() - $lh - 20 ) ) {
            $pdf->AddPage();
        }

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->MultiCell(245, 0, $trophy['name'], 'B', 'L', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        if( $trophy['criteria'] != '' ) {
            $pdf->MultiCell(245, 0, $trophy['criteria'], 0, 'L', 0, 1);
        }
        $pdf->Ln(3);

        //
        // Output the classes
        //
        if( isset($trophy['classes']) ) {
            if( $pdf->GetY() > $pdf->getPageHeight() - PDF_MARGIN_FOOTER - 50) {
                $pdf->AddPage();
            }
            if( isset($args['marks']) && $args['marks'] == 'yes' ) {
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->MultiCell($w[0], 0, 'Class', 1, 'L', 0, 0);
                $pdf->MultiCell($w[1], 0, 'Competitor', 1, 'L', 0, 0);
                $pdf->MultiCell($w[2], 0, 'Date/Time', 1, 'L', 0, 0);
                $pdf->MultiCell($w[3], 0, 'Location', 1, 'L', 0, 0);
                $pdf->MultiCell($w[4], 0, 'Mark', 1, 'C', 0, 1);
                $pdf->SetFont('helvetica', '', 12);
            }
            foreach($trophy['classes'] as $class) {
/*                if( isset($festival['runsheets-class-format']) 
                    && $festival['runsheets-class-format'] == 'code-section-category-class' 
                    ) {
                    $name = "{$class['code']} - {$class['section_name']} - {$class['category_name']} - {$class['name']}";
                } elseif( isset($festival['runsheets-class-format']) 
                    && $festival['runsheets-class-format'] == 'code-category-class' 
                    ) {
                    $name = "{$class['code']} - {$class['category_name']} - {$class['name']}";
                } else {
                    $name = "{$class['code']} - {$class['name']}";
                } */
//                $pdf->SetFont('helvetica', 'B', 12);
//                $pdf->MultiCell(245, 0, $name, 0, 'L', 0, 1);
//                $pdf->SetFont('helvetica', '', 12);

                if( isset($class['registrations']) ) {
                    foreach($class['registrations'] as $reg) {
                        $lh = $pdf->getStringHeight($w[1], $reg['display_name']);
                        $date_time = '';
                        if( $reg['division_date_text'] != '' && $reg['slot_time_text'] != '' ) {
                            $date_time = $reg['division_date_text'] . '/' . $reg['slot_time_text'];
                        }
                        if( $pdf->getStringHeight($w[2], $date_time) > $lh ) {
                            $lh = $pdf->getStringHeight($w[2], $date_time);
                        }
                        if( $pdf->getStringHeight($w[3], $reg['location_name']) > $lh ) {
                            $lh = $pdf->getStringHeight($w[3], $reg['location_name']);
                        }

                        if( $pdf->GetY() > $pdf->getPageHeight() - PDF_MARGIN_FOOTER - $lh - 10) {
                            $pdf->AddPage();
                            $pdf->SetFont('helvetica', 'B', 14);
                            $pdf->MultiCell(245, 0, $trophy['name'] . ' (continued...)', 'B', 'L', 0, 1);
                            if( isset($args['marks']) && $args['marks'] == 'yes' ) {
                                $pdf->SetFont('helvetica', 'B', 12);
                                $pdf->MultiCell($w[0], 0, 'Class', 1, 'L', 0, 0);
                                $pdf->MultiCell($w[1], 0, 'Competitor', 1, 'L', 0, 0);
                                $pdf->MultiCell($w[2], 0, 'Date/Time', 1, 'L', 0, 0);
                                $pdf->MultiCell($w[3], 0, 'Location', 1, 'L', 0, 0);
                                $pdf->MultiCell($w[4], 0, 'Mark', 1, 'C', 0, 1);
                                $pdf->SetFont('helvetica', '', 12);
                            }
                        }

                        $pdf->MultiCell($w[0], $lh, $class['code'], 1, 'L', 0, 0);
                        $pdf->MultiCell($w[1], $lh, $reg['display_name'], 1, 'L', 0, 0);
                        $pdf->MultiCell($w[2], $lh, $date_time, 1, 'L', 0, 0);
                        if( isset($args['marks']) && $args['marks'] == 'yes' ) {
                            $pdf->MultiCell($w[3], $lh, $reg['location_name'], 1, 'L', 0, 0);
                            $pdf->MultiCell($w[4], $lh, $reg['mark'], 1, 'L', 0, 1);
                        } else {
                            $pdf->MultiCell($w[3], $lh, $reg['location_name'], 1, 'L', 0, 1);
                        }
                    }
                }
            }
            $pdf->Ln(3);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
