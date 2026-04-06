<?php
//
// Description
// -----------
// This functions the award letters for accolade winners
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_templates_accoladesAwardLettersPDF(&$ciniki, $tnid, $args) {

   
    $festival = $args['festival'];

    //
    // Load the tenant settings
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

    if( !class_exists('MYPDF') ) {
        class MYPDF extends TCPDF {
            //Page header
            public $left_margin = 18;
            public $right_margin = 18;
            public $top_margin = 15;
            public $header_visible = 'yes';
            public $header_image = null;
            public $header_info = '';
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
                    $info_width = 180 - 65;
                    $available_ratio = $img_width/$this->header_height;
                    $this->setFont('helvetica', '', 10);
                    $this->SetCellPadding(1);
                    $lh = $this->getStringHeight($info_width, $this->header_info);
                    // Check if the ratio of the image will make it too large for the height,
                    // and scaled based on either height or width.
                    if( $available_ratio < $this->header_image_ratio ) {
                        $this->Image($this->header_image_filename, $this->left_margin, 10, $img_width, $this->header_height-8, '', '', 'L', false, '150', '', false, false, 0, true);
                    } else {
                        $this->Image($this->header_image_filename, $this->left_margin, 10, 0, $this->header_height-8, '', '', 'L', false, '150');
                    }
    
                    $this->Ln(5);
                    $this->setX($this->left_margin + $img_width);
                    $this->MultiCell($info_width, $lh, $this->header_info, 0, 'R', 0, 0, '', '', true, 0, true);
                } else {
                    // No header
                }
            }

            // Page footer
            public function Footer() {
                // Position at 15 mm from bottom
                if( $this->footer_visible == 'yes' && $this->footer_msg != '' ) {
                    $this->SetCellPadding(2);
                    $this->SetFont('helvetica', '', 10);
                    $lh = $this->getStringHeight(180, $this->footer_msg);
                    $this->SetY(-($lh+12));
                    $this->MultiCell(180, 0, $this->footer_msg, 0, 'L', 0, 0, '', '', true, 0, true);
                } else {
                    // No footer
                }
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
    $pdf->header_height = 35;
    $pdf->header_info = '';
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

    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    foreach($args['winners'] as $winner) {
        $pdf->header_info = $winner['awarded_pdf_header'];
        $pdf->footer_msg = $winner['awarded_pdf_footer'];
        $pdf->AddPage();

        $content = $winner['awarded_pdf_content'];
        //
        // Replace Substitutions
        //
        $content = str_replace('{_date_}', $dt->format('F j, Y'), $content);
        $content = str_replace('{_name_}', $winner['private_name'], $content);
        $content = str_replace('{_accolade_}', $winner['accolade_name'], $content);
        if( $winner['awarded_amount'][0] != '$' && $winner['awarded_amount'] > 0 ) {
            $winner['awarded_amount'] = '$' . number_format($winner['awarded_amount'], 0);
        }
        $content = str_replace('{_awardedamount_}', $winner['awarded_amount'], $content);
        $content = str_replace('{_discipline_}', $winner['discipline'], $content);
        $content = preg_replace("/<p( style=\"[^\"]+\"|)>{_thankyou_}<\/p>/", "{_thankyou_}", $content);
        $content = str_replace('{_thankyou_}', $winner['donor_thankyou_info'], $content);
        $pdf->setFont('helvetica', '', 12);

        $pdf->MultiCell(180, 0, $content, 0, 'L', false, 1, '', '', true, 0, true);
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
