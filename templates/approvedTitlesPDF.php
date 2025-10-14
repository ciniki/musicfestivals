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

/*    //
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load the sections, categories and classes
    //
    $strsql = "SELECT classes.id, "
        . "classes.festival_id, "
        . "classes.category_id, "
        . "sections.id AS section_id, "
        . "sections.syllabus_id, "
        . "sections.name AS section_name, "
        . "sections.synopsis AS section_synopsis, "
        . "sections.description AS section_description, "
        . "sections.live_description AS section_live_description, "
        . "sections.virtual_description AS section_virtual_description, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.code, "
        . "classes.name, "
        . "classes.permalink, "
        . "classes.icon_image_id, "
        . "classes.sequence, "
        . "classes.synopsis as class_synopsis, "
        . "classes.flags, "
        . "classes.feeflags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee, "
        . "syllabuses.sections_description "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_syllabuses AS syllabuses ON ("
            . "sections.syllabus_id = syllabuses.id "
            . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id ";
    if( isset($args['groupname']) && $args['groupname'] != '' ) {
        $strsql .= "AND categories.groupname = '" . ciniki_core_dbQuote($ciniki, $args['groupname']) . "' ";
    }
        $strsql .= "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id ";
    if( isset($args['live-virtual']) && $args['live-virtual'] == 'live' ) {
        $strsql .= "AND classes.fee > 0 ";
    } elseif( isset($args['live-virtual']) && $args['live-virtual'] == 'virtual' ) {
        $strsql .= "AND classes.virtual_fee > 0 ";
    }
    $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (sections.flags&0x01) = 0 "  // Visible
        . "";
    if( isset($args['section_id']) && $args['section_id'] != '' && $args['section_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    } 
    if( isset($args['syllabus_id']) ) {
        $strsql .= "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $args['syllabus_id']) . "' ";
    }
    $strsql .= "ORDER BY sections.sequence, sections.name, "
            . "categories.sequence, categories.name, "
            . "classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('name'=>'section_name', 'synopsis'=>'section_synopsis', 'description'=>'section_description',
                'syllabus_id', 'sections_description',
                'live_description'=>'section_live_description', 'virtual_description'=>'section_virtual_description',
                )),
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'icon_image_id',
                'sequence', 'flags', 'feeflags',
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee', 'synopsis'=>'class_synopsis')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Check if rules & regs should be loaded
    //
    if( isset($festival['syllabus-rules-include']) && $festival['syllabus-rules-include'] != 'no' ) {
        if( isset($args['syllabus_id']) ) {
            $syllabus_id = $args['syllabus_id'];
        } elseif( isset($sections[0]['syllabus_id']) ) {
            $syllabus_id = $sections[0]['syllabus_id'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.37', 'msg'=>'No syllabus specified'));
        }
        $strsql = "SELECT syllabuses.id, "
            . "syllabuses.name, "
            . "syllabuses.rules "
            . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
            . "WHERE syllabuses.id = '" . ciniki_core_dbQuote($ciniki, $syllabus_id) . "' "
            . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'syllabus');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.38', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
        }
        if( !isset($rc['syllabus']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1181', 'msg'=>'Unable to find requested syllabus'));
        }
        $syllabus = $rc['syllabus'];
    }
*/
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
