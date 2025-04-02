<?php
//
// Description
// ===========
// This function will produce a PDF with the list of competitors, the classes they competited in and their marks
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_competitorClassMarksPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

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

    //
    // Load competitor list
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.first, "
        . "competitors.last, "
        . "competitors.name, "
        . "competitors.ctype, "
        . "registrations.mark, "
        . "classes.id AS class_id, "
        . "classes.code "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND ("
                . "competitors.id = registrations.competitor1_id "
                . "OR competitors.id = registrations.competitor2_id "
                . "OR competitors.id = registrations.competitor3_id "
                . "OR competitors.id = registrations.competitor4_id "
                . "OR competitors.id = registrations.competitor5_id "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY competitors.id, code "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'first', 'last', 'name', 'ctype')
            ),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'code', 'mark')
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.918', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();
    foreach($competitors as $cid =>$competitor) {
        if( $competitor['ctype'] == 10 ) {
            $competitors[$cid]['name'] = $competitor['last'] . ', ' . $competitor['first'];
        }
    }

    uasort($competitors, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
        });
    
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
//                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, 'JPEG', '', 'L', 2, '150');
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, $this->header_height-8, 'JPEG', '', 'L', 2, '150', '', false, false, 0, true);
                    } else {
                        $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, 'JPEG', '', 'L', 2, '150');
                    }
                }

                $this->Ln(5);
                $this->SetFont('helvetica', 'B', 14);
                if( $img_width > 0 ) {
                    $this->Cell($img_width, 10, '', 0);
                }
                $this->setX($this->left_margin + $img_width);
//                $this->Cell(180-$img_width, 10, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->MultiCell(180-$img_width, 0, $this->header_title, 0, 'R', 0, 1);
//                $this->Ln(7);

                $this->SetFont('helvetica', '', 14);
                $this->setX($this->left_margin + $img_width);
                //$this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->MultiCell(180-$img_width, 0, $this->header_sub_title, 0, 'R', 0, 1);
//                $this->MultiCell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
//                $this->Ln(7);

                $this->SetFont('helvetica', '', 12);
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
    $pdf->header_msg = '';
    $pdf->footer_msg = '';

    if( !isset($args['footerdate']) || $args['footerdate'] == 'yes' ) {
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
    $pdf->SetTitle($festival['name']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(220);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128);
    $pdf->SetLineWidth(0.1);
    $pdf->SetCellPaddings(2, 2, 2, 2);

    $filename = 'Competitor Class Marks';
    $pdf->header_sub_title = 'Competitor Class Marks';

    $w = array(49, 24, 14);

    $pdf->AddPage();
    $start_y = $pdf->GetY();

    $pdf->SetFont('', 'B', '10');
    $pdf->MultiCell($w[0], 0, 'Name', 1, 'L', 1, 0);
//    $pdf->MultiCell($w[1], 0, '#', 1, 'L', 1, 0);
    $pdf->MultiCell($w[1], 0, 'Class', 1, 'L', 1, 0);
    $pdf->MultiCell($w[2], 0, 'Mark', 1, 'L', 1, 1);
    $first_entry = 'yes';

    //
    // Go through the sections, divisions and classes
    //
    $offset_x = $pdf->left_margin;
    foreach($competitors as $competitor) {
        if( !isset($competitor['classes']) ) {
            continue;
        }
        $pdf->SetFont('', '', '10');
        $h = ((1+count($competitor['classes'])) * $pdf->getStringHeight($w[0], 'A')) 
            + $pdf->getStringHeight($w[0], $competitor['name'])
            + $pdf->getStringHeight($w[0], 'Num Entries');
        if( $pdf->GetY() > ($pdf->getPageHeight() - $h - 15)) {
            $pdf->SetFont('', 'B', '10');
            if( $offset_x > $pdf->left_margin) {
                $pdf->AddPage();
                $offset_x = $pdf->left_margin;
            } else {
                $offset_x = $pdf->left_margin + 93;
                $pdf->SetY($start_y);
            }
            $pdf->SetX($offset_x);
            $pdf->MultiCell($w[0], 0, 'Name', 1, 'L', 1, 0);
            $pdf->MultiCell($w[1], 0, 'Class', 1, 'L', 1, 0);
            $pdf->MultiCell($w[2], 0, 'Mark', 1, 'L', 1, 1);
//            $first_entry = 'yes';
        } elseif( $first_entry == 'yes' ) {
            $first_entry = 'no';
        } else {
            $pdf->Ln(3);
        }

        $pdf->SetFont('', '', '10');
        $i = 0;
        $marks = 0;
        $num_entries_with_marks = 0;
        foreach($competitor['classes'] as $class) {
            $pdf->SetX($offset_x);
            $h = $pdf->getStringHeight($w[0], $competitor['name']);
            $pdf->MultiCell($w[0], $h, ($i > 0 ? '' : $competitor['name']), 1, 'L', 0, 0);
            $pdf->MultiCell($w[1], $h, $class['code'], 1, 'L', 0, 0);
            $pdf->MultiCell($w[2], $h, $class['mark'], 1, 'L', 0, 1);
            if( $class['mark'] != '' && is_numeric($class['mark']) && $class['mark'] > 0 ) {
                $marks += $class['mark'];
                $num_entries_with_marks++;
            }
            $i++;
        }

        $pdf->SetX($offset_x);
        $h = $pdf->getStringHeight($w[0], 'Num Entries');
        $pdf->MultiCell($w[0], $h, 'Total Entries/Avg', 1, 'R', 0, 0);
        $pdf->MultiCell($w[1], $h, count($competitor['classes']), 1, 'L', 0, 0);
        if( $marks > 0 ) {
            $pdf->MultiCell($w[2], $h, round(($marks/$num_entries_with_marks), 1), 1, 'L', 0, 1);
        } else {
            $pdf->MultiCell($w[2], $h, '', 1, 'L', 0, 1);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
