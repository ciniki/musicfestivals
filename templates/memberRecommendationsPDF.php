<?php
//
// Description
// ===========
// This method will produce a PDF of the parents recommendations.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_memberRecommendationsPDF(&$ciniki, $tnid, $args) {

    //
    // Make sure festival_id was passed in
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.764', 'msg'=>'No festival specified'));
    }

    //
    // Make sure recommendations are passed
    //
    if( !isset($args['recommendations']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.765', 'msg'=>'No recommendations specified'));
    }

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 15;
        public $right_margin = 15;
        public $top_margin = 15;
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $tenant_details = array();
        public $fill = 0;

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
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150');
//                            $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', 2, '150', '', false, false, 0, true);
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', 2, '150');
                }
            }

            $this->Ln(8);
            $this->SetFont('helvetica', 'B', 16);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell(249-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('helvetica', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(249-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('helvetica', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(249-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
            $this->SetDrawColor(128);
/*            $this->setFillColor(255, 255, 255); $this->Cell(28, 6, 'Recommended', 1, false, 'C', 1);
            $this->setFillColor(221, 241, 255); $this->Cell(28, 6, 'Issues', 1, false, 'C', 1);
            $this->setFillColor(255, 239, 221); $this->Cell(28, 6, 'Approved', 1, false, 'C', 1);
            $this->setFillColor(255, 239, 221); $this->Cell(28, 6, 'Invited', 1, false, 'C', 1);
            $this->setFillColor(206, 255, 248); $this->Cell(28, 6, 'Accepted', 1, false, 'C', 1);
            $this->setFillColor(206, 255, 248); $this->Cell(28, 6, 'Instr. Sent', 1, false, 'C', 1);
            $this->setFillColor(221, 255, 221); $this->Cell(28, 6, 'Registered', 1, false, 'C', 1);
            $this->setFillColor(255, 221, 221); $this->Cell(28, 6, 'Turned Down', 1, false, 'C', 1);
            $this->setFillColor(240, 221, 255); $this->Cell(28, 6, 'Already Rec', 1, false, 'C', 1);
            $this->setFillColor(238, 238, 238); $this->Cell(28, 6, 'Ineligible', 1, false, 'C', 1);
            $this->SetFont('helvetica', 'D', 10);
            $this->setFillColor(255, 255, 255); $this->Cell(28, 6, 'Former', 1, false, 'C', 1);

            $this->setFillColor(221, 241, 255); $this->Cell(28, 6, 'Alternate', 1, false, 'C', 1);
            $this->setFillColor(255, 239, 221); $this->Cell(28, 6, 'Accepted', 1, false, 'C', 1);
            $this->setFillColor(221, 255, 221); $this->Cell(28, 6, 'Registered', 1, false, 'C', 1);
            $this->setFillColor(255, 221, 221); $this->Cell(28, 6, 'Turned Down', 1, false, 'C', 1);
            $this->setFillColor(240, 221, 255); $this->Cell(28, 6, 'Duplicate', 1, false, 'C', 1);
            $this->setFillColor(221, 221, 221); $this->Cell(28, 6, 'Expired', 1, false, 'C', 1); */
            $this->Cell(190, 6, '', 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(53, 6, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        } 
        public function labelValue($w1, $label, $w2, $value) {
            $lh = 12;
            $border = 'TLRB';
            $lh = $this->getStringHeight($w2, $value);
            $this->SetFont('helvetica', 'B', 12);
            //$this->MultiCell($w1, $lh, $label, $border, 'R', $this->fill, 0);
            $this->MultiCell($w1, $lh, $label, $border, 'R', 1, 0);
            $this->SetFont('helvetica', '', 12);
//            $this->MultiCell($w2, $lh, $value, $border, 'L', $this->fill, 1);
            $this->MultiCell($w2, $lh, $value, $border, 'L', 0, 1);
            $this->fill = !$this->fill;
        }
        public function labelValue2($w1, $l1, $w2, $v1, $w3, $l2, $w4, $v2) {
            $lh = 12;
            $border = 'TLRB';
            $lh = $this->getStringHeight($w2, $v1);
            $lh2 = $this->getStringHeight($w4, $v2);
            if( $lh2 > $lh ) {
                $lh = $lh2;
            }
            $this->SetFont('helvetica', 'B', 12);
            $this->MultiCell($w1, $lh, $l1, $border, 'R', 1, 0);
            $this->SetFont('helvetica', '', 12);
            $this->MultiCell($w2, $lh, $v1, $border, 'L', 0, 0);
            $this->SetFont('helvetica', 'B', 12);
            $this->MultiCell($w3, $lh, $l2, $border, 'R', 1, 0);
            $this->SetFont('helvetica', '', 12);
            $this->MultiCell($w4, $lh, $v2, $border, 'L', 0, 1);
            $this->fill = !$this->fill;
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
    $pdf->header_title = $args['title'];
    $pdf->header_sub_title = $args['subtitle'];
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
    $pdf->SetTitle($args['title']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(220);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetDrawColor(128);
    $pdf->SetLineWidth(0.15);

    $filename = 'Recommendations';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(115,20,50,14,50);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', '', 11);

    $fill = 1;
    $pdf->setFont('helvetica','B');
    $pdf->MultiCell($w[0], 0, 'Class', 1, 'L', $fill, 0);
    $pdf->MultiCell($w[1], 0, 'Position', 1, 'L', $fill, 0);
    $pdf->MultiCell($w[2], 0, 'Competitor', 1, 'L', $fill, 0);
    $pdf->MultiCell($w[3], 0, 'Mark', 1, 'L', $fill, 0);
    $pdf->MultiCell($w[4], 0, 'Status', 1, 'L', $fill, 1);
    $fill = 0;
    $pdf->setFont('helvetica','');

    $classes = array();
    $prev_class = '';
    foreach($args['recommendations'] as $rec) {
        $rec['position'] = str_replace("Alternate", "Alt", $rec['position']);
        //
        // Calculate the height of recommendation
        //
        $rec['lh'] = $pdf->getStringHeight($w[0], $rec['class']);
        if( $pdf->getStringHeight($w[1], $rec['position_text']) > $rec['lh'] ) {
            $rec['lh'] = $pdf->getStringHeight($w[1], $rec['position_text']);
        }
        if( $pdf->getStringHeight($w[2], $rec['name']) > $rec['lh'] ) {
            $rec['lh'] = $pdf->getStringHeight($w[2], $rec['name']);
        }
        if( $pdf->getStringHeight($w[2], $rec['mark']) > $rec['lh'] ) {
            $rec['lh'] = $pdf->getStringHeight($w[3], $rec['mark']);
        }
/*        if( $rec['status'] == 35 ) {
            $rec['status_text'] .= ' - ' . $rec['date_invited'];
        } elseif( $rec['status'] == 50 ) {
            $rec['status_text'] .= ' - ' . $rec['reg_status_text'];
        }  */
        if( $pdf->getStringHeight($w[2], $rec['status_text']) > $rec['lh'] ) {
            $rec['lh'] = $pdf->getStringHeight($w[4], $rec['status_text']);
        }

        if( $pdf->GetY() > ($pdf->getPageHeight() - $rec['lh'] - 25) ) {
            $pdf->AddPage();
            $fill = 1;
            $pdf->setFont('helvetica','B');
            $pdf->SetFillColor(220);
            $pdf->MultiCell($w[0], 0, 'Class', 1, 'L', $fill, 0);
            $pdf->MultiCell($w[1], 0, 'Position', 1, 'R', $fill, 0);
            $pdf->MultiCell($w[2], 0, 'Competitor', 1, 'L', $fill, 0);
            $pdf->MultiCell($w[3], 0, 'Mark', 1, 'C', $fill, 0);
            $pdf->MultiCell($w[4], 0, 'Status', 1, 'L', $fill, 1);
            $fill = 0;
            $pdf->setFont('helvetica','');
        }


        $pdf->setFillColor(255, 255, 255);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationEntryStatusColour');
        $rc = ciniki_musicfestivals_recommendationEntryStatusColour($ciniki, $tnid, $rec);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        } 
        $fade = 'no';
        if( isset($rc['fill']) ) {
            $pdf->setFillColorArray($rc['fill']);
        }
        if( isset($rc['fade']) ) {
            $pdf->setFillColorArray($rc['fade']);
            $fade = 'yes';
        }
        $pdf->setFont('helvetica','');
        if( isset($rc['strike']) && $rc['strike'] == 'yes' ) {
            $pdf->setFont('helvetica','D');
        }

/*        if( $rec['cssclass'] == 'statusyellow' ) {
            $pdf->setFillColor(255, 253, 197);
        } elseif( $rec['cssclass'] == 'statusorange' ) {
            $pdf->setFillColor(255, 239, 221);
        } elseif( $rec['cssclass'] == 'statusgreen' ) {
            $pdf->setFillColor(221, 255, 221);
        } elseif( $rec['cssclass'] == 'statusred' ) {
            $pdf->setFillColor(255, 221, 221);
        } elseif( $rec['cssclass'] == 'statuspurple' ) {
            $pdf->setFillColor(240, 221, 255);
        } elseif( $rec['cssclass'] == 'statusblue' ) {
            $pdf->setFillColor(221, 241, 255);
        } elseif( $rec['cssclass'] == 'statusgrey' ) {
            $pdf->setFillColor(221, 221, 221);
        } */
        $fill = 1;
        $pdf->MultiCell($w[0], $rec['lh'], $rec['class'], 1, 'L', $fill, 0);
        if( $fade == 'yes' ) {
            $fill = 0;
        }
        $pdf->MultiCell($w[1], $rec['lh'], $rec['position_text'], 1, 'R', $fill, 0);
        $pdf->MultiCell($w[2], $rec['lh'], $rec['name'], 1, 'L', $fill, 0);
        $pdf->MultiCell($w[3], $rec['lh'], $rec['mark'], 1, 'C', $fill, 0);
        $pdf->MultiCell($w[4], $rec['lh'], $rec['status_text'], 1, 'L', $fill, 1);
//        $fill = !$fill;
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
