<?php
//
// Description
// ===========
// This method will produce a PDF of the class codes for avery labels (labels 5162).
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_accoladesRecipientLabelsPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
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
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 5;
        public $right_margin = 4;
        public $top_margin = 12;
        public $header_visible = 'yes';
        public $header_image = null;
        public $header_sponsor_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_visible = 'no';
        public $footer_image = null;
        public $footer_image_height = 0;
        public $footer_msg = '';
        public $tenant_details = array();
        public $continued_str = ' (continued...)';

        public function Header() {
        }
        public function Footer() {
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 13;

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($args['festival']['name'] . ' - Accolade Recipient Labels');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);
    $pdf->SetAutoPageBreak(true, 0);

    // set font
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $pdf->AddPage();

    //
    // Go through the sections, divisions and classes
    //
    $col = 0;
    $row = 0;
    // Default avery template 5162
    $pdf->left_margin = 4;
    $pdf->right_margin = 4;
    $pdf->top_margin = 21.25;
    $num_cols = 2;
    $num_rows = 7;
    $size_x = 101.4;
    $x_padding = 5;
    $size_y = 33.85;
    $pdf->SetCellPaddings(5, 2, 5, 2);
    $pdf->SetFont('helvetica', '', 12);
    if( isset($args['template']) && $args['template'] == '5161' ) {
        $pdf->left_margin = 4;
        $pdf->right_margin = 4;
        $pdf->top_margin = 12.85;
        $num_cols = 2;
        $num_rows = 10;
        $size_x = 101.4;
        $x_padding = 5;
        $size_y = 25.37;
        $pdf->SetCellPaddings(5, 2, 5, 2);
        $pdf->SetFont('helvetica', '', 12);
    }

    foreach($args['competitors'] as $competitor) {

        if( $row >= $num_rows ) {
            $pdf->AddPage();
            $col = 0;
            $row = 0;
        }
        $x = $pdf->left_margin + ($col * $size_x) + ($col * $x_padding);
        $y = $pdf->top_margin + ($row * $size_y);
        
        $txt = $competitor['name'];
        if( $competitor['address'] != '' ) {
            $txt .= ($txt != '' ? "\n" : '') . $competitor['address'];
        }
        $city = $competitor['city'];
        if( $competitor['province'] != '' ) {
            $city .= ($city != '' ? ', ' : '') . $competitor['province'];
        }
        if( $competitor['postal'] != '' ) {
            $city .= ($city != '' ? '  ' : '') . $competitor['postal'];
        }
        if( $city != '' ) {
            $txt .= ($txt != '' ? "\n" : '') . $city;
        }

        $pdf->MultiCell($size_x, $size_y, $txt, 1, 'L', 0, 0, $x, $y, true, 0, false, true, $size_y, 'M');

        $col++;
        if( $col >= $num_cols ) {
            $col = 0;
            $row++;
        }
    }

    if( isset($args['download']) && $args['download'] == 'yes' ) {
        $pdf->Output($args['filename'], 'I');
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
