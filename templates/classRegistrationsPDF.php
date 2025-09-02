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
function ciniki_musicfestivals_templates_classRegistrationsPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

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
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT classes.id AS class_id, "
        . "classes.sequence AS cla_seq, "
        . "classes.flags AS class_flags, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds, "
        . "categories.sequence AS cat_seq, "
        . "categories.name AS cat_name, "
        . "sections.sequence AS sec_seq, "
        . "sections.name AS sec_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.movements1, "
        . "registrations.composer1, "
        . "registrations.perf_time1, "
        . "registrations.title2, "
        . "registrations.movements2, "
        . "registrations.composer2, "
        . "registrations.perf_time2, "
        . "registrations.title3, "
        . "registrations.movements3, "
        . "registrations.composer3, "
        . "registrations.perf_time3, "
        . "registrations.title4, "
        . "registrations.movements4, "
        . "registrations.composer4, "
        . "registrations.perf_time4, "
        . "registrations.title5, "
        . "registrations.movements5, "
        . "registrations.composer5, "
        . "registrations.perf_time5, "
        . "registrations.title6, "
        . "registrations.movements6, "
        . "registrations.composer6, "
        . "registrations.perf_time6, "
        . "registrations.title7, "
        . "registrations.movements7, "
        . "registrations.composer7, "
        . "registrations.perf_time7, "
        . "registrations.title8, "
        . "registrations.movements8, "
        . "registrations.composer8, "
        . "registrations.perf_time8, "
        . "registrations.participation, "
        . "registrations.notes, "
        . "competitors.id AS competitor_id, "
        . "competitors.notes AS competitor_notes "
        . "FROM ciniki_musicfestival_classes AS classes "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id ";
        if( isset($args['section_id']) && $args['section_id'] > 0 ) {
            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
        }
        $strsql .= "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
            . "(registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sec_seq, cat_seq, cla_seq, class_code, class_name, reg_id, competitor_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'cat_seq', 'cat_name', 'sec_seq', 'sec_name', 
                'code'=>'class_code', 'name'=>'class_name',
                'flags'=>'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 
                'title1', 'movements1', 'composer1', 'perf_time1', 
                'title2', 'movements2', 'composer2', 'perf_time2', 
                'title3', 'movements3', 'composer3', 'perf_time3', 
                'title4', 'movements4', 'composer4', 'perf_time4', 
                'title5', 'movements5', 'composer5', 'perf_time5', 
                'title6', 'movements6', 'composer6', 'perf_time6', 
                'title7', 'movements7', 'composer7', 'perf_time7', 
                'title8', 'movements8', 'composer8', 'perf_time8', 
                'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                'notes', 'participation',
            )),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'notes'=>'competitor_notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $classes = $rc['classes'];
        foreach($classes as $cid => $class) {
            $classes[$cid]['num_reg'] = count($class['registrations']);
            $class_seconds = 0;
            if( isset($class['registrations']) ) {
                foreach($class['registrations'] AS $rid => $reg) {
                    //
                    // Get titles
                    //
                    $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, [
//                        'times' => 'startsum', 
                        'basicnumbers' => 'yes',
                        ]);
                    $classes[$cid]['registrations'][$rid]['titles'] = $rc['titles'];
                    $classes[$cid]['registrations'][$rid]['perf_time'] = $rc['perf_time'];
                    $classes[$cid]['registrations'][$rid]['perf_time_seconds'] = $rc['perf_time_seconds'];
                    $class_seconds += $rc['perf_time_seconds']; 
                }
            }
            if( $class['schedule_at_seconds'] > 0 ) {
                $class_seconds += $class['schedule_at_seconds']; 
                // add addition registration seconds for each reg after first
                if( $class['schedule_ata_seconds'] > 0 && $classes[$cid]['num_reg'] > 1 ) {
                    $class_seconds += ($class['schedule_ata_seconds'] * ($classes[$cid]['num_reg']-1)); 
                }
            }
            $classes[$cid]['duration'] = '';
            if( $class_seconds > 0 ) {
                $classes[$cid]['duration'] = ' [' . intval($class_seconds/60) . ':' . str_pad($class_seconds%60, 2, '0', STR_PAD_LEFT) . ']';
            }

/*            if( isset($class['registrations']) ) {
                foreach($class['registrations'] AS $rid => $reg) {
                    if( $reg['perf_time1'] != '' && $reg['perf_time1'] > 0 ) {
                        $classes[$cid]['registrations'][$rid]['perf_time1'] = intval($reg['perf_time1']/60) . ':' . str_pad($reg['perf_time1'] % 60, 2, '0', STR_PAD_LEFT);
                    }
                    if( $reg['perf_time2'] != '' && $reg['perf_time2'] > 0 ) {
                        $classes[$cid]['registrations'][$rid]['perf_time2'] = intval($reg['perf_time2']/60) . ':' . str_pad($reg['perf_time2'] % 60, 2, '0', STR_PAD_LEFT);
                    }
                    if( $reg['perf_time3'] != '' && $reg['perf_time3'] > 0 ) {
                        $classes[$cid]['registrations'][$rid]['perf_time3'] = intval($reg['perf_time3']/60) . ':' . str_pad($reg['perf_time3'] % 60, 2, '0', STR_PAD_LEFT);
                    }
                }
            } */
        }
    } else {
        $classes = array();
    }

/*    usort($classes, function($a, $b) {
        if( $a['sec_seq'] == $b['sec_seq'] ) {
            if( $a['cat_seq'] == $b['cat_seq'] ) {
                if( $a['code'] == $b['code'] ) {
                    strcasecmp($a['code'], $b['code']);
                }
            }
        }
        if( $a['cat_seq'] == $b['cat_seq'] ) {
        if( $a['num_reg'] == $b['num_reg'] ) {
            strcasecmp($a['code'], $b['code']);
        }
        return ($a['num_reg'] < $b['num_reg'] ? 1 : -1);
    }); */

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
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, '', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, 0, $this->header_height-13, '', '', 'L', 2, '150');
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

            $this->SetFont('times', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
    $pdf->SetTitle($festival['name'] . ' - Schedule');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    $pdf->footer_msg = $dt->format("M j, Y");

    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $filename = 'class_registrations';

    $pdf->AddPage();
    //
    // Go through the sections, divisions and classes
    //
    $cw = array(25, 5, 150);
    $w = array(10, 60, 98, 12);
    $nw = array(20, 160);
    $lh = 6;
    $border = '';
    foreach($classes as $class) {
        if( $pdf->getY() > ($pdf->getPageHeight() - 60 ) ) {
            $pdf->AddPage();
        }
        $pdf->SetFont('', 'B', 14);
        $pdf->MultiCell($cw[0] + $cw[1] + $cw[2], $lh, $class['code'] . ' - ' . $class['name'] . $class['duration'], $border, 'L', 0, 1);
//        $pdf->Cell($cw[1], $lh, '', $border, 0, 'R', 0);
//        $pdf->Cell($cw[2], $lh, $class['name'], $border, 1, 'L', 0);
        $pdf->SetFont('', '', 12);

        //
        // Get the sizing for rows
        //
        $reg_list = array();
        $reg_list_height = 0;
        $pdf->SetCellPadding(0.25);
        $class_seconds = 0;
        $pdf->SetCellPadding(1);
        foreach($class['registrations'] as $reg) {
            $row = array();
            $row['name'] = $reg['name'];
            $row['participation'] = $reg['participation'];
            $row['name_height'] = $pdf->getStringHeight($w[1]-1, $row['name']);
            $row['height'] = $row['name_height'];
            $class_seconds += $reg['perf_time_seconds'];
            $row['titles'] = $reg['titles'];
            $row['perf_time'] = $reg['perf_time'];

/*            $row['title1'] = $reg['title1'];
            $row['perf_time1'] = $reg['perf_time1'];
            $row['title1_height'] = $pdf->getStringHeight($w[2], $row['title1']);
            $row['titles_height'] = $row['title1_height'];
            if( $reg['title2'] != '' ) {
                $row['title2'] = $reg['title2'];
                $row['perf_time2'] = $reg['perf_time2'];
                $row['title2_height'] = $pdf->getStringHeight($w[2], $row['title2']);
                $row['titles_height'] = $row['title2_height'];
            }
            if( $reg['title3'] != '' ) {
                $row['title3'] = $reg['title3'];
                $row['perf_time3'] = $reg['perf_time3'];
                $row['title3_height'] = $pdf->getStringHeight($w[2], $row['title3']);
                $row['titles_height'] = $row['title3_height'];
            } */
            $row['titles_height'] = $pdf->getStringHeight($w[2], $row['titles']);
            if( $row['titles_height'] > $row['height'] ) {
                $row['height'] = $row['titles_height'];
            }
            $row['notes'] = $reg['notes'];
            if( isset($reg['competitors']) ) {
                foreach($reg['competitors'] as $competitor) {
                    if( $competitor['notes'] != '' ) {
                        $row['notes'] .= ($row['notes'] != '' ? "\n" : '') . $competitor['notes'];
                    }
                }
            }
            $row['notes_height'] = $pdf->getStringHeight($nw[1], $row['notes']);
            $reg_list[] = $row;
            $reg_list_height += $row['height']; 
        } 
        $pdf->SetCellPadding(1);

//        foreach($class['registrations'] as $reg) {
        foreach($reg_list as $row) {
/*            $notes = $reg['notes'];
            if( isset($reg['competitors']) ) {
                foreach($reg['competitors'] as $competitor) {
                    if( $competitor['notes'] != '' ) {
                        $notes .= ($notes != '' ? "\n" : '') . $competitor['notes'];
                    }
                }
            } */
/*            if( $reg['title2'] != '' ) {
                $reg['title1'] .= "<br/>" . $reg['title2'];
                $reg['perf_time1'] .= "<br/>" . $reg['perf_time2'];
            }
            if( $reg['title3'] != '' ) {
                $reg['title1'] .= "<br/>" . $reg['title3'];
                $reg['perf_time1'] .= "<br/>" . $reg['perf_time3'];
            } */
/*            $d_height = $pdf->getStringHeight($w[1], $reg['name']);
            if( $pdf->getStringHeight($w[2], $reg['title1']) > $d_height ) {
                $d_height = $pdf->getStringHeight($w[2], $reg['title1']);
            }
            if( $reg['title2'] != '' ) {
                $d_height += $pdf->getStringHeight($w[2], $reg['title2']);
            }
            if( $reg['title3'] != '' ) {
                $d_height += $pdf->getStringHeight($w[2], $reg['title3']);
            }
            $n_height = 0;
            if( $notes != '' ) {
                $n_height = $pdf->getStringHeight($nw[1], $notes);
            } */

//            if( $pdf->getY() > ($pdf->getPageHeight() - 30 - $d_height - $n_height) ) {
            if( $pdf->getY() > ($pdf->getPageHeight() - 30 - $row['height'] - $row['notes_height']) ) {
                $pdf->AddPage();
                $pdf->SetFont('', 'B', 14);
                $pdf->Cell($cw[0], $lh, $class['code'], $border, 0, 'R', 0);
                $pdf->Cell($cw[1], $lh, '', $border, 0, 'L', 0);
                $pdf->Cell($cw[2], $lh, $class['name'] . ' (continued...)', $border, 1, 'L', 0);
                $pdf->SetFont('', '', 12);
            }
   
            //$pdf->writeHTMLCell($w[0], $d_height, '', '', '', '', 0, false, true, 'L', 1);
            //$pdf->writeHTMLCell($w[1], $d_height, '', '', $reg['name'], 0, 0, false, true, 'L', 1);
            //$pdf->writeHTMLCell($w[2], $d_height, '', '', $reg['title1'], 0, 0, false, true, 'L', 1);
            //$pdf->writeHTMLCell($w[3], $d_height, '', '', $reg['perf_time1'], 0, 1, false, true, 'L', 1);
            $pdf->SetFont('', 'B', 12);
            $pdf->MultiCell($w[0], $row['height'], ($row['participation'] == 2 ? '[+]' : ''), 'T', 'R', 0, 0);
            $pdf->SetFont('', '', 12);
            $pdf->MultiCell($w[1], $row['height'], $row['name'], 'T', 'L', 0, 0);
            $pdf->MultiCell($w[2], $row['height'], $row['titles'], 'T', 'L', 0, 0);
            $pdf->MultiCell($w[3], $row['height'], $row['perf_time'], 'T', 'R', 0, 1);
/*            $pdf->MultiCell($w[3], $row['title1_height'], $row['perf_time1'], 0, 'R', 0, 0);
            if( $row['name_height'] > $row['title1_height'] ) {
                $pdf->Ln($row['name_height']-2);
            } else {
                $pdf->Ln($row['title1_height']-2);
            }
            if( isset($row['title2']) && $row['title2'] != '' ) {
//                $pdf->MultiCell($w[0] + $w[1], '', '', 0, 'L', 0, 0);
                $pdf->MultiCell($w[2], $row['title2_height'], $row['title2'], 0, 'L', 0, 0);
                $pdf->MultiCell($w[3], $row['title2_height'], $row['perf_time2'], 0, 'R', 0, 1);
            }
            if( isset($row['title3']) && $row['title3'] != '' ) {
//                $pdf->MultiCell($w[0] + $w[1], '', '', 0, 'L', 0, 0);
                $pdf->MultiCell($w[2], $row['title3_height'], $row['title3'], 0, 'L', 0, 0);
                $pdf->MultiCell($w[3], $row['title3_height'], $row['perf_time3'], 0, 'R', 0, 1);
            }
*/
            if( $row['notes'] != '' ) {
//                $pdf->Ln(2);
                $pdf->MultiCell($nw[0], '', '', 0, 'L', 0, 0);
                $pdf->MultiCell($nw[1], '', '**' . $row['notes'], 0, 'L', 0, 1);
//                $pdf->Ln(3);
//                $pdf->writeHTMLCell($nw[0], $row['notes_height']+2, '', '', '', 0, 0, false, true, 'L', 1);
//                $pdf->writeHTMLCell($nw[1], $row['notes_height']+2, '', '', '**' . preg_replace("/\n/", "<br/>", $row['notes']), 0, 1, false, true, 'L', 1);
            } else {
//                $pdf->Ln(3);
            }
        }
        $pdf->MultiCell(180, 5, '', 'T', 'L', 0, 1);
//        $pdf->Ln();
    }
    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
