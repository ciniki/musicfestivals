<?php
//
// Description
// ===========
// This function will produce a PDF of the recommendations for a member festival to submit to the provincials.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_recommendationsPDF(&$ciniki, $tnid, $args) {

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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

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
    // Load the festival
    //
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.start_date, "
        . "ciniki_musicfestivals.end_date, "
        . "ciniki_musicfestivals.flags, "
        . "ciniki_musicfestivals.primary_image_id, "
        . "ciniki_musicfestivals.description, "
        . "ciniki_musicfestivals.document_logo_id, "
        . "ciniki_musicfestivals.document_header_msg, "
        . "ciniki_musicfestivals.document_footer_msg, "
        . "ciniki_musicfestivals.comments_grade_label, "
        . "ciniki_musicfestivals.comments_footer_msg "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'flags', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg',
                'comments_grade_label', 'comments_footer_msg',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.109', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.110', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // FIXME: Check for provincial festival
    //
/*    $strsql = "SELECT festivals.id "
        . "FROM ciniki_musicfestival_members AS members "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "members.tnid = festivals.tnid "
            . "AND festivals.status = 30 "
            . ") "
        . "WHERE members.member_tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY festivals.end_date DESC "
        . "LIMIT 1"
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.64', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( isset($rc['festival']['id']) ) {
        $provincial_festival_id = $rc['festival']['id'];
    }
 */   

    //
    // Load the recommendations for a festival
    //
    if( isset($provincial_festival_id) && $provincial_festival_id > 0 ) {

    } else {
        $strsql = "SELECT sections.id, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name, "
            . "classes.provincials_code, "
            . "registrations.id AS registration_id, "
            . "registrations.display_name, "
            . "registrations.provincials_position, "
            . "registrations.mark "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.provincials_code <> '' "
                . "AND classes.provincials_code <> 'na' "
                . "AND classes.provincials_code <> 'NA' "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.provincials_position > 0 "
            . "AND registrations.provincials_status < 70 "
            . "ORDER BY section_name, provincials_code, provincials_position "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'class_code', 'class_name', 'category_name', 'name'=>'section_name'),
                ),
            array('container'=>'classes', 'fname'=>'provincials_code', 
                'fields'=>array('id', 'provincials_code'),
                ),
            array('container'=>'registrations', 'fname'=>'registration_id', 
                'fields'=>array('id'=>'registration_id', 'provincials_code', 'display_name', 'provincials_position', 'mark'),
                'maps'=>array('provincials_position'=>$maps['registration']['provincials_position']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.80', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();
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
        public $header_subsub_title = '';
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
                $img_width = 65;
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 10, 0, $this->header_height-8, 'JPEG', '', 'L', 2, '150');
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
            if( $this->header_subsub_title != '' ) {
                $this->SetFont('times', 'B', 14);
                $this->setX($this->left_margin + $img_width);
                $this->Cell(180-$img_width, 10, $this->header_subsub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
                $this->Ln(6);
            }

            $this->SetFont('times', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);
        }

        // Page footer
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
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = 'Provincial Recommendations';
    $pdf->header_subsub_title = '';
    $pdf->header_msg = '';
    $pdf->footer_msg = '';

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
    $pdf->SetTitle($festival['name'] . ' - Recommendations');
    $filename = $festival['name'] . '-Recommendations';
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->SetFillColor(240);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.1);
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);


    //
    // Go through the sections, divisions and classes
    //
    $w = array(50, 110, 20);
    foreach($sections as $section) {
        //
        // Start a new section
        //
        $pdf->header_subsub_title = $section['name'];
        if( count($sections) == 1 ) {
            $filename .= '_' . $section['name'];
        }
        $pdf->AddPage();

        foreach($section['classes'] as $class) {
   
            $pdf->SetCellPaddings(0, 2, 2, 2);
            $pdf->SetFont('', 'B', 12);
            $pdf->MultiCell(180, 0, $class['provincials_code'], 0, 'L', 0, 1, '', '');

            $pdf->SetCellPaddings(2, 2, 2, 2);
            $pdf->SetFont('', '', 12);
            $fill = 1;
            foreach($class['registrations'] as $registration) {
                $lh = $pdf->getStringHeight($w[0], $registration['provincials_position']);
                if( $pdf->getStringHeight($w[1], $registration['display_name']) > $lh ) {
                    $lh = $pdf->getStringHeight($w[1], $registration['display_name']);
                }
                $pdf->MultiCell($w[0], $lh, $registration['provincials_position'], 1, 'L', $fill, 0, '', '');
                $pdf->MultiCell($w[1], $lh, $registration['display_name'], 1, 'L', $fill, 0, '', '');
                $pdf->MultiCell($w[2], $lh, $registration['mark'], 1, 'C', $fill, 1, '', '');
                $fill = !$fill;
            }
            $pdf->Ln(3);
        }
    }

    if( count($sections) == 0 ) {
        $pdf->AddPage();
        $pdf->MultiCell(180, 0, 'No provincial recommendations', 0, 'L', 0, 1, '', '');
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
