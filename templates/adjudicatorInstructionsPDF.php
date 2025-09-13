<?php
//
// Description
// ===========
// Generate the PDF with the adjudicator instructions.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_adjudicatorInstructionsPDF(&$ciniki, $tnid, $args) {

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
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the festival settings
    //
    if( isset($args['festival-id']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival-id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $festival = $rc['festival'];
    } elseif( isset($args['festival']) ) {
        $festival = $args['festival'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1080', 'msg'=>'No festival'));
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
                if( $width > 600 ) {
                    $this->header_image->scaleImage(600, 0);
                }
                $image_ratio = $width/$height;
                $img_width = 60;
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, 0, '', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-10, '', '', 'L', 2, '150');
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
    $pdf->header_sub_title = 'Adjudicator Instructions';
    $pdf->header_msg = ''; //$festival['document_header_msg'];
    $pdf->footer_msg = ''; //$festival['document_footer_msg'];

    if( isset($args['virtual']) && $args['virtual'] == 'yes' && !isset($args['live']) ) {
        $pdf->header_sub_title = 'Adjudicator Virtual Instructions';
        $filename = 'Adjudicator Virtual Instructions';
    } elseif( isset($args['live']) && $args['live'] == 'yes' && !isset($args['virtual']) 
        && ($festival['flags']&0x06) > 0
        ) {
        $pdf->header_sub_title = 'Adjudicator Live Instructions';
        $filename = 'Adjudicator Live Instructions';
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
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Syllabus');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.1);

    //
    // Check if rules and regulations are to be added at the beginning
    //
    $pdf->SetCellPadding(1);
    if( isset($args['live']) && $args['live'] == 'yes' 
        && isset($festival['adjudications-live-instructions'])
        && $festival['adjudications-live-instructions'] != '' 
        ) {
        $pdf->AddPage();
//        $pdf->SetFont('', 'B', '18');
//        $pdf->MultiCell(180, 5, 'Adjudicator ' . (($festival['flags']&0x06) > 0 ? 'Live ' : '') . 'Instructions', 0, 'L', 0, 1);
        $pdf->SetFont('', '', '12');
        $pdf->writeHTMLCell(180, '', '', '', $festival['adjudications-live-instructions'], 0, 1, 0, true, 'L', true);
    }
    if( isset($args['virtual']) && $args['virtual'] == 'yes' 
        && isset($festival['adjudications-virtual-instructions'])
        && $festival['adjudications-virtual-instructions'] != '' 
        ) {
        $pdf->AddPage();
//        $pdf->SetFont('', 'B', '18');
//        $pdf->MultiCell(180, 5, 'Adjudicator Virtual Instructions', 0, 'L', 0, 1);
        $pdf->SetFont('', '', '12');
        $pdf->writeHTMLCell(180, '', '', '', $festival['adjudications-virtual-instructions'], 0, 1, 0, true, 'L', true);
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename);
}
?>
