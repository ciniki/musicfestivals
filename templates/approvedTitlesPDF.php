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
function ciniki_musicfestivals_templates_approvedTitlesPDF(&$ciniki, $tnid, $args) {

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_details = isset($rc['details']) ? $rc['details'] : array();

    if( !isset($args['list']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1187', 'msg'=>'', 'err'=>$rc['err']));
    }
    $lists = [$args['list']];

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
        public $class_icons = [];

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
                $available_ratio = $img_width/($this->header_height-8);
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
            $this->Cell(253-$img_width, 12, $this->header_title, 0, false, ($this->header_image != null ? 'R' : 'C'), 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('helvetica', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(253-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('helvetica', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(253-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-12);
            $this->SetFont('helvetica', 'B', 8);
            $this->Cell(126.5, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 8);
            $this->Cell(126.5, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }

        public function TitlesHeader($w, $columns) {
            $this->SetFont('', 'B', '11');
            $lh = 0;
            foreach($columns as $i => $col) {
                $sh = $this->getStringHeight($w[$i], $col['label']);
                if( $sh > $lh ) {
                    $lh = $sh;
                }
            }
            foreach($columns as $i => $col) {
                $this->MultiCell($w[$i], $lh, $col['label'], 1, 'L', 1, 0);
            }
            $this->Ln($lh);
            $this->SetFont('', '', '11');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 15;
    $pdf->header_title = '';
    $pdf->header_sub_title = '';
    $pdf->header_msg = ''; //$festival['document_header_msg'];
    $pdf->footer_msg = ''; //$festival['document_footer_msg'];

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 && isset($festival['document_logo_id']) && $festival['document_logo_id'] > 0 ) {
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
    $pdf->SetTitle('Approved Titles');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->left_margin = 13;
    $pdf->right_margin = 13;
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(8);
    $pdf->SetAutoPageBreak(true, 8);

    // set font
    $pdf->SetFont('helvetica', 'BI', 11);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.1);

    foreach($lists as $lid => $list) {
        if( count($lists) == 0 ) {
            $pdf->SetTitle($list['name']);
        }
        $pdf->header_title = $list['name'];
        $pdf->AddPage();

        $columns = [];
        for($j = 1; $j < 5; $j++) {
            if( in_array($list["col{$j}_field"], ['title', 'movements', 'composer', 'source_type']) ) {
                $columns[] = [
                    'label' => $list["col{$j}_label"],
                    'field' => $list["col{$j}_field"],
                    ];
            }
        }
        if( count($columns) == 1 ) {
            $w = [253];
        } elseif( count($columns) == 2 ) {
            $w = [126.5,126.5];
        } elseif( count($columns) == 3 ) {
            $w = [84.3,84.3,84.3];
        } elseif( count($columns) == 4 ) {
            $w = [63.25,63.25,63.25,63.25];
        } elseif( count($columns) == 5 ) {
            $w = [50.6,50.6,50.6,50.6,50.6];
        }

        $pdf->SetFont('', '');
        if( isset($list['description']) && $list['description'] != '' ) {
            $pdf->writeHTMLCell(244, '', '', '', $list['description'], 0, 1, 0, true, 'L', true);
        }
        $fill = 1;
        $pdf->TitlesHeader($w, $columns);
        $fill = 0;

        foreach($list['titles'] as $title) {
            $lh = 0;
            foreach($columns as $i => $col) {
                $sh = $pdf->getStringHeight($w[$i], $title[$col['field']]);
                if( $sh > $lh ) {
                    $lh = $sh;
                }
            }
            if( $pdf->getY() > $pdf->getPageHeight() - 10 - $lh) {
                $pdf->AddPage();
                $fill = 1;
                $pdf->TitlesHeader($w, $columns);
                $fill = 0;
            }
            foreach($columns as $i => $col) {
                $pdf->MultiCell($w[$i], $lh, $title[$col['field']], 1, 'L', $fill, (($i+1) == count($columns) ? 1 : 0));
            }
//            $pdf->Ln($lh);
            $fill=!$fill;
        }
    }

    if( isset($args['download']) && $args['download'] == 'I' ) {
        $filename = $list['name'] . '.pdf';

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-Type: application/pdf');
        header('Cache-Control: max-age=0');

        $pdf->Output($filename, 'I');

        return array('stat'=>'exit');
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
