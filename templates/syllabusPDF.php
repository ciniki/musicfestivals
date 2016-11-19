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
function ciniki_musicfestivals_templates_syllabusPDF(&$ciniki, $business_id, $args) {

    //
    // Load the business details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
    $rc = ciniki_businesses_businessDetails($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $business_details = $rc['details'];
    } else {
        $business_details = array();
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
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
        . "WHERE ciniki_musicfestivals.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.37', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.38', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the sections, categories and classes
    //
    $strsql = "SELECT ciniki_musicfestival_classes.id, "
        . "ciniki_musicfestival_classes.festival_id, "
        . "ciniki_musicfestival_classes.category_id, "
        . "ciniki_musicfestival_sections.id AS section_id, "
        . "ciniki_musicfestival_sections.name AS section_name, "
        . "ciniki_musicfestival_sections.synopsis AS section_synopsis, "
        . "ciniki_musicfestival_categories.id AS category_id, "
        . "ciniki_musicfestival_categories.name AS category_name, "
        . "ciniki_musicfestival_categories.synopsis AS category_synopsis, "
        . "ciniki_musicfestival_classes.code, "
        . "ciniki_musicfestival_classes.name, "
        . "ciniki_musicfestival_classes.permalink, "
        . "ciniki_musicfestival_classes.sequence, "
        . "ciniki_musicfestival_classes.flags, "
        . "ciniki_musicfestival_classes.fee "
        . "FROM ciniki_musicfestival_sections, ciniki_musicfestival_categories, ciniki_musicfestival_classes "
        . "WHERE ciniki_musicfestival_sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . (isset($args['section_id']) ? "AND ciniki_musicfestival_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' " : "")
        . "AND ciniki_musicfestival_sections.id = ciniki_musicfestival_categories.section_id "
        . "AND ciniki_musicfestival_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_musicfestival_categories.id = ciniki_musicfestival_classes.category_id "
        . "AND ciniki_musicfestival_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_musicfestival_sections.sequence, ciniki_musicfestival_sections.name, "
            . "ciniki_musicfestival_categories.sequence, ciniki_musicfestival_categories.name, "
            . "ciniki_musicfestival_classes.sequence, ciniki_musicfestival_classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('name'=>'section_name', 'synopsis'=>'section_synopsis')),
        array('container'=>'categories', 'fname'=>'category_id', 'fields'=>array('name'=>'category_name', 'synopsis'=>'category_synopsis')),
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee')),
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
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $business_details = array();

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
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, 0, $this->header_height-5, 'JPEG', '', 'L', 2, '150');
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
    // Figure out the header business name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = $festival['document_footer_msg'];

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
        $rc = ciniki_images_loadImage($ciniki, $business_id, $festival['document_logo_id'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($business_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Syllabus');
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
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Go through the sections, categories and classes
    //
    $w = array(30, 120, 30);
    foreach($sections as $section) {
        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'] . ' Syllabus';
        $pdf->AddPage();

        //
        // Output the categories
        //
        foreach($section['categories'] as $category) {
            //
            // Check if enough room
            //
            $lh = 12;
            $s_height = $pdf->getStringHeight(180, $category['synopsis']);
            if( $pdf->getY() > $pdf->getPageHeight() - (count($category['classes']) * $lh) - 50 - $s_height) {
                $pdf->AddPage();
            } else {
                $pdf->Ln();
            }

            $pdf->SetFont('', 'B', '18');
            $pdf->Cell(180, 10, $category['name'], 0, 0, 'L', 0);
            $pdf->Ln(8);
            $pdf->SetFont('', '', '12');
            $pdf->MultiCell(180, $lh, $category['synopsis'], 0, 'L', 0, 2);
            $pdf->Ln(1);
            $fill = 1;
            $pdf->Cell($w[0], $lh, '', 0, 0, 'L', $fill);
            $pdf->Cell($w[1], $lh, '', 0, 0, 'L', $fill);
            $pdf->Cell($w[2], $lh, 'Fee', 0, 0, 'C', $fill);
            $pdf->Ln();
            
            //
            // Output the classes
            //
            $fill = 0;
            foreach($category['classes'] as $class) {
                $pdf->Cell($w[0], $lh, $class['code'], 0, 0, 'L', $fill);
                $pdf->Cell($w[1], $lh, $class['name'], 0, 0, 'L', $fill);
                $pdf->Cell($w[2], $lh, numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency), 0, 0, 'C', $fill);
                $pdf->Ln($lh);
                $fill=!$fill;
            }
        }
    }


    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
