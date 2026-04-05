<?php
//
// Description
// -----------
// This function generates the list of shifts and volunteers for each locations and date
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_templates_volunteersShiftsPDF(&$ciniki, $tnid, $args) {

   
    $shifts = $args['shifts'];
    $festival = $args['festival'];

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    if( !class_exists('MYPDF') ) {
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
            public $cell_widths = [60,60,24,24,75];

            public function setHeaderImage($ciniki, $tnid, $image_id) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
                $rc = ciniki_images_loadImage($ciniki, $tnid, $image_id, 'original');
                if( $rc['stat'] == 'ok' ) {
                    $header_image = $rc['image'];
                    $height = $header_image->getImageHeight();
                    $width = $header_image->getImageWidth();
                    if( $width > 600 ) {
                        $header_image->scaleImage(600, 0);
                    }
                    $this->header_image_ratio = $width/$height;
                    //
                    // Load the image storage filename, adding as blob is REALLY slow!
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadOriginalStorageFilename');
                    $rc = ciniki_images_hooks_loadOriginalStorageFilename($ciniki, $tnid, array('image_id'=>$image_id));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.302', 'msg'=>'No image specified', 'err'=>$rc['err']));
                    }
                    $this->header_image_filename = $rc['filename'];
                }
                return array('stat'=>'ok');
            }

            public function Header() {
                if( $this->header_visible == 'yes' ) {
                    $img_width = 65;
                    $available_ratio = $img_width/$this->header_height;
                    // Check if the ratio of the image will make it too large for the height,
                    // and scaled based on either height or width.
                    if( $available_ratio < $this->header_image_ratio ) {
                        $this->Image($this->header_image_filename, $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', false, '150', '', false, false, 0, true);
                    } else {
                        $this->Image($this->header_image_filename, $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', false, '150');
                    }

                    $this->Ln(8);
                    $this->SetFont('helvetica', 'B', 14);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, 10, '', 0);
                    }
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(243-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(7);

                    $this->SetFont('helvetica', '', 14);
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(243-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                    $this->Ln(7);

                    $this->SetFont('helvetica', '', 12);
                    $this->setX($this->left_margin + $img_width);
                    $this->Cell(243-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
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
                    $this->SetFont('helvetica', '', 10);
                    $this->Cell(121.5, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
                    $this->SetFont('helvetica', '', 10);
                    $this->Cell(121.5, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                } else {
                    // No footer
                }
            }

            public function TableHeader() {
                $this->SetFont('helvetica', 'B', 12);
                $this->MultiCell($this->cell_widths[0], 0, 'Location', 1, 'L', 1, 0);
                $this->MultiCell($this->cell_widths[1], 0, 'Role', 1, 'L', 1, 0);
                $this->MultiCell($this->cell_widths[2], 0, 'Start', 1, 'L', 1, 0);
                $this->MultiCell($this->cell_widths[3], 0, 'End', 1, 'L', 1, 0);
                $this->MultiCell($this->cell_widths[4], 0, 'Volunteers', 1, 'L', 1, 1);
            }

            public function Shift($shift) {
                $this->SetFont('helvetica', '', 12);
                $lh = $this->getStringHeight($this->cell_widths[0], $shift['roomname']);
                if( $this->getStringHeight($this->cell_widths[1], $shift['role']) > $lh ) {
                    $lh = $this->getStringHeight($this->cell_widths[1], $shift['role']);
                }
                if( $this->getStringHeight($this->cell_widths[2], $shift['start_time']) > $lh ) {
                    $lh = $this->getStringHeight($this->cell_widths[2], $shift['start_time']);
                }
                if( $this->getStringHeight($this->cell_widths[3], $shift['end_time']) > $lh ) {
                    $lh = $this->getStringHeight($this->cell_widths[3], $shift['end_time']);
                }
                if( $this->getStringHeight($this->cell_widths[4], $shift['names']) > $lh ) {
                    $lh = $this->getStringHeight($this->cell_widths[4], $shift['names']);
                }
                if( $this->getY() > $this->getPageHeight() - $lh - 20 ) {
//                    $this->AddPage();
//                    $this->TableHeader();
                }
                $this->MultiCell($this->cell_widths[0], $lh, $shift['roomname'], 1, 'L', 0, 0);
                $this->MultiCell($this->cell_widths[1], $lh, $shift['role'], 1, 'L', 0, 0);
                $this->MultiCell($this->cell_widths[2], $lh, $shift['start_time'], 1, 'L', 0, 0);
                $this->MultiCell($this->cell_widths[3], $lh, $shift['end_time'], 1, 'L', 0, 0);
                $this->MultiCell($this->cell_widths[4], $lh, $shift['names'], 1, 'L', 0, 1);
            }
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 30;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = '';

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->SetFillColor(220);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.25);
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);

    //
    // Load the header image
    //
    if( isset($festival['document_logo_id']) && $festival['document_logo_id'] > 0 ) {
        $rc = $pdf->setHeaderImage($ciniki, $tnid, $festival['document_logo_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    uasort($shifts, function($a, $b) {
        if( $a['building_id'] != $b['building_id'] ) {
            return $a['building_id'] < $b['building_id'] ? -1 : 1;
        }
        if( $a['sort_shift_date'] != $b['sort_shift_date'] ) {
            return strnatcasecmp($a['sort_shift_date'], $b['sort_shift_date']);
        }
        if( $a['roomname'] != $b['roomname'] ) {
            return strnatcasecmp($a['roomname'], $b['roomname']);
        }
        if( $a['role'] != $b['role'] ) {
            return strnatcasecmp($a['role'], $b['role']);
        }
        if( $a['sort_start_time'] != $b['sort_start_time'] ) {
            return $a['sort_start_time'] < $b['sort_start_time'] ? -1 : 1;
        }
        return 0;
    });

    $prev_building_id = '';
    $prev_shift_date = '';
    foreach($shifts as $shift) {
        $shift['names'] = preg_replace("/<br>/", "\n", $shift['names']);
        if( $prev_building_id != $shift['building_id'] || $prev_shift_date != $shift['shift_date'] ) {
            $pdf->header_sub_title = 'Volunteers';
            $pdf->header_msg = $shift['building_name'] . ' - ' . $shift['shift_date'];
            $pdf->AddPage();
            $pdf->TableHeader();
        }

        $pdf->Shift($shift);

        $prev_building_id = $shift['building_id'];
        $prev_shift_date = $shift['shift_date'];
    }

    //
    // output the pdf
    //
    if( isset($args['download']) && $args['download'] == 'yes' && isset($args['filename']) ) {
        $pdf->Output($args['filename'], 'I');
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
