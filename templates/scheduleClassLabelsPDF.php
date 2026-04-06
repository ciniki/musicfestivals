<?php
//
// Description
// ===========
// This method will produce a PDF of the class codes for avery labels (labels 5160).
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_scheduleClassLabelsPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
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
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    $division_date_format = '%b %d';

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "categories.name AS category_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.groupname, "
        . "DATE_FORMAT(divisions.division_date, '" . ciniki_core_dbQuote($ciniki, $division_date_format) . "') AS division_date_text, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "registrations.id AS registration_id "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id " 
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id " 
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['division_id']) && $args['division_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
        $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, class_code, divisions.division_date, timeslots.slot_time, groupname "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'classes', 'fname'=>'class_code', 
            'fields'=>array('code'=>'class_code', 'name'=>'class_name', 'category_name', 'section_name'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('division_date_text', 'slot_time_text', 'groupname'),
            ),
        array('container'=>'registrations', 'fname'=>'registration_id', 
            'fields'=>array('id'=>'registration_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

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
    $pdf->SetTitle($festival['name'] . ' - Scheduled Class Labels');
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

    $filename = 'Scheduled Class Labels';
    $pdf->AddPage();

    //
    // Go through the sections, divisions and classes
    //
    $w = array(66.5, 66.5, 66.5);
    $col = 0;
    $row = 0;
    $size_x = 66.5;
    $x_padding = 3.25;
    $size_y = 25.4;
    $lh = 25.4;
    foreach($sections as $section) {

        $pdf->SetFont('', 'B', '20');

        foreach($section['classes'] as $class) {
            
            foreach($class['timeslots'] as $timeslot) {
                $num_reg = 0;
                foreach($timeslot['registrations'] as $reg) {
                    $num_reg++;
                }
                if( $row > 9 ) {
                    $pdf->AddPage();
                    $col = 0;
                    $row = 0;
                }
                $x = $pdf->left_margin + ($col * $size_x) + ($col * $x_padding);
                $y = $pdf->top_margin + ($row * $size_y);
                $txt = $class['code'] . ($timeslot['groupname'] != '' ? " - {$timeslot['groupname']}" : '');
                if( isset($festival['classlabels-class-name']) && $festival['classlabels-class-name'] == 'yes' ) {
                    $h1 = 9.0;
                    $pdf->SetCellPadding(1);
                    $pdf->SetCellPaddings(1,2,1,0);
                    $pdf->SetFont('helvetica', 'B', 14);
                    $pdf->MultiCell($w[$col], $h1, $txt, 0, 'C', 0, 0, $x, $y, true, 0, false, true, $h1, 'M');
                    $h2 = 16.4;
                    $pdf->SetCellPaddings(3,0,3,2);
                    $pdf->setFont('', '', '12');
                    $pdf->MultiCell($size_x, $h2, $class['name'], 0, 'C', 0, 0, $x, $y+$h1, true, 0, false, true, $h2, 'M', true);
                } else {
                    $pdf->MultiCell($size_x, $size_y, $txt, 0, 'C', 0, 0, $x, $y, true, 0, false, true, $lh, 'M');
                }
                $col++;
                if( $col > 2 ) {
                    $col = 0;
                    $row++;
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
