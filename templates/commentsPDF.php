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
function ciniki_musicfestivals_templates_commentsPDF(&$ciniki, $tnid, $args) {

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
    // Load adjudicators
    //
    $strsql = "SELECT ciniki_musicfestival_adjudicators.id, "
        . "ciniki_musicfestival_adjudicators.festival_id, "
        . "ciniki_musicfestival_adjudicators.customer_id, "
        . "ciniki_customers.display_name "
        . "FROM ciniki_musicfestival_adjudicators "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_musicfestival_adjudicators.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_musicfestival_adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND ciniki_musicfestival_adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.171', 'msg'=>'Unable to get adjudicator list', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    if( isset($args['registration_id']) && $args['registration_id'] > 0 ) {
        $strsql = "SELECT 1 AS section_id, "
            . "'' AS section_name, "
            . "1 AS division_id, "
            . "'' AS division_name, "
            . "1 AS timeslot_id, "
            . "'' AS timeslot_name, "
            . "sections.adjudicator1_id AS section_adjudicator_id, "
            . "0 AS class1_id, "
            . "0 AS class2_id, "
            . "0 AS class3_id, "
            . "0 AS class4_id, "
            . "0 AS class5_id, "
            . "'' AS class1_name, "
            . "'' AS class2_name, "
            . "'' AS class3_name, "
            . "'' AS class4_name, "
            . "'' AS class5_name, "
            . "'' AS description, "
            . "registrations.id AS reg_id, "
            . "registrations.display_name, "
            . "registrations.public_name, "
            . "registrations.title1, "
            . "registrations.title2, "
            . "registrations.title3, "
            . "registrations.title4, "
            . "registrations.title5, "
            . "registrations.title6, "
            . "registrations.title7, "
            . "registrations.title8, "
            . "registrations.composer1, "
            . "registrations.composer2, "
            . "registrations.composer3, "
            . "registrations.composer4, "
            . "registrations.composer5, "
            . "registrations.composer6, "
            . "registrations.composer7, "
            . "registrations.composer8, "
            . "registrations.movements1, "
            . "registrations.movements2, "
            . "registrations.movements3, "
            . "registrations.movements4, "
            . "registrations.movements5, "
            . "registrations.movements6, "
            . "registrations.movements7, "
            . "registrations.movements8, "
            . "registrations.participation, "
            . "IFNULL(TIME_FORMAT(timeslots.slot_time, '%l:%i %p'), '') AS timeslot_time, "
            . "IFNULL(DATE_FORMAT(divisions.division_date, '%b %D, %Y'), '') AS timeslot_date, "
            . "IFNULL(classes.name, '') AS class_name, "
            . "IFNULL(classes.flags, 0) AS class_flags, "
            . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
            . "IFNULL(comments.id, 0) AS comment_id, "
            . "IFNULL(comments.adjudicator_id, 0) AS adjudicator_id, "
            . "IFNULL(comments.comments, '') AS comments, "
            . "IFNULL(comments.grade, '') AS grade, "
            . "IFNULL(comments.score, '') AS score "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "registrations.timeslot_id = timeslots.id " 
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "timeslots.sdivision_id = divisions.id " 
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                . "divisions.ssection_id = sections.id " 
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_comments AS comments ON ("
                . "registrations.id = comments.registration_id "
                . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "";
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND registrations.participation = 0 ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
    } else {
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "sections.adjudicator1_id AS section_adjudicator_id, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "timeslots.id AS timeslot_id, "
            . "timeslots.name AS timeslot_name, "
            . "timeslots.class1_id, "
            . "timeslots.class2_id, "
            . "timeslots.class3_id, "
            . "timeslots.class4_id, "
            . "timeslots.class5_id, "
            . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS timeslot_time, "
            . "DATE_FORMAT(divisions.division_date, '%b %D, %Y') AS timeslot_date, "
            . "IFNULL(class1.name, '') AS class1_name, "
            . "IFNULL(class2.name, '') AS class2_name, "
            . "IFNULL(class3.name, '') AS class3_name, "
            . "IFNULL(class4.name, '') AS class4_name, "
            . "IFNULL(class5.name, '') AS class5_name, "
            . "timeslots.name AS timeslot_name, "
            . "timeslots.description, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.display_name, "
            . "registrations.public_name, "
            . "registrations.title1, "
            . "registrations.title2, "
            . "registrations.title3, "
            . "registrations.title4, "
            . "registrations.title5, "
            . "registrations.title6, "
            . "registrations.title7, "
            . "registrations.title8, "
            . "registrations.composer1, "
            . "registrations.composer2, "
            . "registrations.composer3, "
            . "registrations.composer4, "
            . "registrations.composer5, "
            . "registrations.composer6, "
            . "registrations.composer7, "
            . "registrations.composer8, "
            . "registrations.movements1, "
            . "registrations.movements2, "
            . "registrations.movements3, "
            . "registrations.movements4, "
            . "registrations.movements5, "
            . "registrations.movements6, "
            . "registrations.movements7, "
            . "registrations.movements8, "
            . "registrations.participation, "
            . "IFNULL(classes.name, '') AS class_name, "
            . "IFNULL(classes.flags, 0) AS class_flags, "
//            . "IFNULL(classes.max_titles, 1) AS max_titles, "
            . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
            . "IFNULL(comments.id, 0) AS comment_id, "
            . "IFNULL(comments.adjudicator_id, 0) AS adjudicator_id, "
            . "IFNULL(comments.comments, '') AS comments, "
            . "IFNULL(comments.grade, '') AS grade, "
            . "IFNULL(comments.score, '') AS score "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id " 
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id " 
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
                . "timeslots.class1_id = class1.id " 
                . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
                . "timeslots.class2_id = class2.id " 
                . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
                . "timeslots.class3_id = class3.id " 
                . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class4 ON ("
                . "timeslots.class4_id = class4.id " 
                . "AND class4.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS class5 ON ("
                . "timeslots.class5_id = class5.id " 
                . "AND class5.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
/*                . "(timeslots.class1_id = registrations.class_id "  
                    . "OR timeslots.class2_id = registrations.class_id "
                    . "OR timeslots.class3_id = registrations.class_id "
                    . "OR timeslots.class4_id = registrations.class_id "
                    . "OR timeslots.class5_id = registrations.class_id "
                    . ") "
                . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) " */
                . "timeslots.id = registrations.timeslot_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_comments AS comments ON ("
                . "registrations.id = comments.registration_id "
                . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
        }
        if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
            $strsql .= "AND registrations.participation = 0 ";
        } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
        }
        if( isset($args['teacher_customer_id']) && $args['teacher_customer_id'] > 0 ) {
            $strsql .= "AND registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' ";
            $strsql .= "ORDER BY divisions.division_date, division_id, slot_time, display_name ";
        } else {
            $strsql .= "ORDER BY divisions.division_date, division_id, slot_time ";
        }
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
        array('container'=>'divisions', 'fname'=>'division_id', 'fields'=>array('id'=>'division_id', 'name'=>'division_name')),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 
                'class1_id', 'class2_id', 'class3_id', 'class4_id', 'class5_id', 'description', 
                'class1_name', 'class2_name', 'class3_name', 'class4_name', 'class5_name',
            )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 
            'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
            'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
            'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
            'class_name', 'class_flags', 'competitor2_id', 'timeslot_date', 'timeslot_time', 'participation',
            )),
        array('container'=>'comments', 'fname'=>'comment_id', 'fields'=>array('id'=>'comment_id', 'adjudicator_id', 'comments', 'grade', 'score', 'section_adjudicator_id')),
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
        /*
            // Position at 15 mm from bottom
            $this->SetY(-40);
            $this->SetFont('helvetica', 'I', 12);
            $this->Cell(45, 12, "Adjudicator's Signature ", 0, false, 'L', 0, '', 0, false);
            $this->Cell(85, 12, "", 'B', false, 'L', 0, '', 0, false);
            $this->Cell(30, 12, "Level ", 0, false, 'R', 0, '', 0, false);
            $this->Cell(20, 12, "", 'B', false, 'L', 0, '', 0, false);
            $this->Ln(14);
            
            $this->SetTextColor(128);
            $this->SetFont('helvetica', 'I', 10);
            $this->Cell(180, 10, "Levels are as follows:", 0, false, 'L', 0, '', 0, false);
            $this->Ln(6);
            $this->SetFont('helvetica', 'BI', 10);
            $this->Cell(180, 10, "G = Gold (85 and above)  S = Silver (80-84)  B = Bronze (79 and under)", 0, false, 'L', 0, '', 0, false);
            $this->SetTextColor(0);
            */
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
    $pdf->header_subsub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
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
    $pdf->SetTitle($festival['name'] . ' - Comments');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);

    $filename = 'comments';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(35, 145);
    foreach($sections as $section) {
        //
        // Start a new section
        //
        $pdf->header_sub_title = "Adjudicator's Comments";
        if( isset($args['schedulesection_id']) ) {
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $section['name']) . '_comments';
        }

        if( !isset($section['divisions']) ) {
            continue;
        }

        //
        // Output the divisions
        //
        $newpage = 'yes';
        foreach($section['divisions'] as $division) {
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) ) {
                continue;
            }
            
            //
            // Output the timeslots
            //
            foreach($division['timeslots'] as $timeslot) {
                if( !isset($timeslot['registrations']) ) {
                    continue;
                }

                foreach($timeslot['registrations'] as $reg) {
                    foreach($reg['comments'] as $comment) {
                        if( ($festival['flags']&0x20) == 0x20 
                            && isset($adjudicators[$comment['section_adjudicator_id']]['name'])
                            && $adjudicators[$comment['section_adjudicator_id']]['name'] != '' 
                            ) {
                            $pdf->header_subsub_title = $adjudicators[$comment['section_adjudicator_id']]['name'];
                        } else {
                            $pdf->header_subsub_title = '';
                        }
                        $pdf->AddPage();
                        $pdf->SetDrawColor(232);
                        $border = 'T';
                        $lh = 8;
                        //
                        // Check if timeslot date/time to be displayed
                        //
                        if( ($festival['flags']&0x40) == 0x40 
                            && $reg['timeslot_time'] != '' && $reg['timeslot_date'] != ''
                            && $reg['participation'] != 1
                            ) {
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->MultiCell($w[0], $lh, 'Date: ', $border, 'R', 0, 0, '', '');
                            $pdf->SetFont('helvetica', '', 12);
                            $pdf->MultiCell($w[1], $lh, $reg['timeslot_date'] . ' - ' . $reg['timeslot_time'], $border, 'L', 0, 0, '', '');
                            $pdf->Ln($lh);
                            $border = '';
                        }
                        $pdf->SetFont('helvetica', '', 12);
                        $lh = $pdf->getStringHeight($w[1], $reg['class_name']);
                        $pdf->SetFont('helvetica', 'B', 12);
                        $pdf->MultiCell($w[0], $lh, 'Class: ', $border, 'R', 0, 0, '', '');
                        $pdf->SetFont('helvetica', '', 12);
                        $pdf->MultiCell($w[1], $lh, $reg['class_name'], $border, 'L', 0, 0, '', '');
                        $pdf->Ln($lh-1);
                        $pdf->SetFont('helvetica', 'B', 12);

                        $border = ($reg['title1'] != '' ? '' : 'B');

                        $lh = $pdf->getNumLines($reg['name'], $w[1]) * 8;
                        if( $reg['competitor2_id'] > 0 ) {
                            $pdf->MultiCell($w[0], $lh, 'Participants: ', $border, 'R', 0, 0, '', '');
                        } else {
                            $pdf->MultiCell($w[0], $lh, 'Participant: ', $border, 'R', 0, 0, '', '');
                        }
                        $pdf->SetFont('helvetica', '', 12);
                        $pdf->MultiCell($w[1], $lh, $reg['name'], $border, 'L', 0, 0, '', '');
                        $pdf->Ln($lh);

                    
                        for($i = 1; $i <= 8; $i++) {
                            if( $reg["title{$i}"] != '' ) {
                                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x040000) ) {
                                    if( $reg["composer{$i}"] != '' ) {
                                        $reg["title{$i}"] .= ' - ' . $reg["composer{$i}"];
                                    }
                                    if( $reg["movements{$i}"] != '' ) {
                                        $reg["title{$i}"] .= ', ' . $reg["movements{$i}"];
                                    }
                                }
                                $lh = ($pdf->getNumLines($reg["title{$i}"], $w[1]) * 6) + 3;
                                $pdf->SetFont('helvetica', 'B', 12);
                                $pdf->MultiCell($w[0], $lh, 'Title: ', $border, 'R', 0, 0, '', '');
                                $pdf->SetFont('helvetica', '', 12);
                                $pdf->MultiCell($w[1], $lh, $reg["title{$i}"], $border, 'L', 0, 0, '', '');
                                $pdf->Ln($lh);
                            }
                        }
/*                        if( $reg['title1'] != '' ) {
                            $lh = ($pdf->getNumLines($reg['title1'], $w[1]) * 6) + 3;
                            if( ($reg['class_flags']&0x1000) == 0 || (($reg['class_flags']&0x1000) == 0x4000 && $reg['title1'] == '') ) {
                                $border = 'B';
                            }
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->MultiCell($w[0], $lh, 'Title: ', $border, 'R', 0, 0, '', '');
                            $pdf->SetFont('helvetica', '', 12);
                            $pdf->MultiCell($w[1], $lh, $reg['title1'], $border, 'L', 0, 0, '', '');
                            $pdf->Ln($lh);
                        }
                        if( ($reg['class_flags']&0x1000) == 0x1000 && $reg['title2'] != '' ) {
                            $lh = ($pdf->getNumLines($reg['title2'], $w[1]) * 6) + 3;
                            if( ($reg['class_flags']&0x4000) == 0 || (($reg['class_flags']&0x4000) == 0x4000 && $reg['title3'] == '') ) {
                                $border = 'B';
                            }
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->MultiCell($w[0], $lh, 'Title: ', $border, 'R', 0, 0, '', '');
                            $pdf->SetFont('helvetica', '', 12);
                            $pdf->MultiCell($w[1], $lh, $reg['title2'], $border, 'L', 0, 0, '', '');
                            $pdf->Ln($lh);
                        }
                        if( ($reg['class_flags']&0x4000) == 0x4000 && $reg['title3'] != '' ) {
                            $lh = ($pdf->getNumLines($reg['title3'], $w[1]) * 6) + 3;
                            $border = 'B';
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->MultiCell($w[0], $lh, 'Title: ', $border, 'R', 0, 0, '', '');
                            $pdf->SetFont('helvetica', '', 12);
                            $pdf->MultiCell($w[1], $lh, $reg['title3'], $border, 'L', 0, 0, '', '');
                            $pdf->Ln($lh);
                        }
*/
                        $pdf->MultiCell(180, 1, '', 'T', 'L', 0, 0, '', '');
                        if( isset($comment['comments']) && $comment['comments'] != '' ) {
                            $pdf->Ln(2);
                            $pdf->MultiCell($w[0] + $w[1], $lh, $comment['comments'], 0, 'L', 0, 1, '', '');
                        }

                        // Position at 15 mm from bottom
                        $pdf->SetDrawColor(50);
                        if( !isset($festival['comments_footer_msg']) || $festival['comments_footer_msg'] == '' ) {
                            $pdf->SetY(-25);
                        } else {
                            $pdf->SetY(-45);
                        }
                        $pdf->SetFont('helvetica', 'I', 12);
                        if( $comment['score'] != '' && isset($adjudicators[$comment['adjudicator_id']]['name']) ) {
                            $pdf->Cell(45, 12, "            Adjudicator", 0, false, 'L', 0, '', 0, false);
                            $pdf->Cell(85, 12, $adjudicators[$comment['adjudicator_id']]['name'], 'B', false, 'L', 0, '', 0, false);
                        } else {
                            $pdf->Cell(48, 12, "Adjudicator's Signature ", 0, false, 'L', 0, '', 0, false);
                            $pdf->Cell(82, 12, "", 'B', false, 'L', 0, '', 0, false);
                        }
                        if( isset($festival['comments_grade_label']) && $festival['comments_grade_label'] != '' ) {
                            $pdf->Cell(30, 12, $festival['comments_grade_label'] . ' ', 0, false, 'R', 0, '', 0, false);
                        } else {
                            $pdf->Cell(30, 12, "Mark ", 0, false, 'R', 0, '', 0, false);
                        }
                        $pdf->Cell(20, 12, $comment['score'], 'B', false, 'L', 0, '', 0, false);
                        $pdf->Ln(14);
                        
                        if( isset($festival['comments_footer_msg']) && $festival['comments_footer_msg'] != '' ) {
                            $pdf->Ln(5);
                            $pdf->SetTextColor(128);
                            $pdf->SetFont('helvetica', '', 10);
                            $pdf->Cell(180, 10, $festival['comments_footer_msg'], 0, false, 'C', 0, '', 0, false);
                        }

                        $pdf->SetTextColor(0);
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
