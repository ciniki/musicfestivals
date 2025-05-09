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
function ciniki_musicfestivals_templates_scheduleSSAWord(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
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

    //
    // Load the adjudicators
    //
/*    if( isset($args['section_adjudicator_bios'])
        && $args['section_adjudicator_bios'] == 'yes' 
        ) {
        $strsql = "SELECT adjudicators.id, "
            . "customers.display_name AS name, "
            . "adjudicators.image_id, "
            . "adjudicators.description "
            . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "adjudicators.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'name', 'image_id', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.691', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
        }
        $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();
    } */

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %e, %Y') AS division_date_text, "
        . "locations.name AS location_name, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0800) ) {
        $strsql .= "divisions.adjudicator_id, ";
    } else {
        $strsql .= "ssections.adjudicator1_id AS adjudicator_id, ";
    }
    $strsql .= "customers.display_name AS adjudicator_name, "
        . "adjudicators.image_id AS adjudicator_image_id, "
        . "adjudicators.description AS adjudicator_bio, "
        . "CONCAT_WS(' ', divisions.division_date, timeslots.slot_time) AS division_sort_key, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.groupname AS timeslot_groupname, "
        . "timeslots.description, "
        . "timeslots.start_num, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
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
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.participation, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON (";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0800) ) {
        $strsql .= "divisions.adjudicator_id = adjudicators.id ";
    } else {
        $strsql .= "ssections.adjudicator1_id = adjudicators.id ";
    }
    $strsql .= "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "( "
                . "timeslots.id = registrations.timeslot_id "
                . "OR timeslots.id = registrations.finals_timeslot_id "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
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
//        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
        $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY ssections.sequence, divisions.division_date, division_id, slot_time, registrations.timeslot_sequence, registrations.public_name "
//    $strsql .= "ORDER BY divisions.division_date, ssections.sequence, divisions.name, divisions.id, slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence, class_code, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'section_name', 'date'=>'division_date_text', 
                'location_name', 'adjudicator_id', 'adjudicator_name', 'adjudicator_image_id', 'adjudicator_bio',
                'sort_key' => 'division_sort_key',
                ),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 
                'groupname'=>'timeslot_groupname', 'time'=>'slot_time_text', 
                'start_num', 
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'participation', 
                'description', 'class_code', 'class_name', 'category_name', 'section_name'=>'syllabus_section_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();

    //
    // Build word document
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/vendor/autoload.php');

    $PHPWord = new \PhpOffice\PhpWord\PhpWord();
    $PHPWord->addTitleStyle(1, array('bold'=>true, 'size'=>20), array('spaceBefore'=>240, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(2, array('bold'=>true, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(3, array('bold'=>false, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addParagraphStyle('Divisions', array('align' => 'center', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        ));
    $PHPWord->addParagraphStyle('Locations', array('align' => 'center', 'spaceAfter' => 150, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        ));
    $PHPWord->addParagraphStyle('Timeslots', array('align' => 'center', 'spaceAfter' => 120, 'spaceBefore'=>120,
        'shading' => ['fill' => 'dddddd'],
        'lineHeight' => '1.0',
        'keepLines' => true,
        'keepNext' => true,
        ));
    $PHPWord->addParagraphStyle('Classes', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>120,
        'keepLines' => true,
        'keepNext' => true,
        ));
    $PHPWord->addParagraphStyle('Registrations', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        ));
    $PHPWord->addParagraphStyle('Adjudicators', array('align' => 'both', 'spaceAfter' => 150, 'spaceBefore'=>0,
//        'keepLines' => true,
//        'keepNext' => true,
        ));
    $PHPWord->addParagraphStyle('Divisions Break', array('align' => 'left', 'spaceAfter'=>0, 'spaceBefore'=>0,
        'keepLines' => false,
        'keepNext' => false,
        ));
    $PHPWord->addParagraphStyle('Registrations Break', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => false,
        'keepNext' => false,
        ));
//    $PHPWord->addFontStyle('Divisions Break Font', ['size'=>6, 'bold'=>false]);
    $PHPWord->addFontStyle('Division Font', ['size'=>13, 'bold'=>true]);
    $PHPWord->addFontStyle('Location Font', ['size'=>13, 'italic'=>true]);
    $PHPWord->addFontStyle('Timeslot Font', ['size'=>11, 'bold'=>true]);
    $PHPWord->addFontStyle('Adjudicators Font', ['size'=>11, 'bold'=>false]);
    $PHPWord->addFontStyle('Registrations Font', ['size'=>11, 'bold'=>false]);
/*    $style_table = array('cellMargin'=>80, 'borderColor'=>'aaaaaa', 'borderSize'=>6);
    $style_header = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'bgColor'=>'dddddd', 'valign'=>'center');
    $style_cell = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'valign'=>'center', 'bgcolor'=>'ffffff');
    $style_header_font = array('bold'=>true, 'spaceAfter'=>20);
    $style_cell_font = array();
    $style_header_pleft = array('align'=>'left');
    $style_header_pright = array('align'=>'right');
    $style_cell_pleft = array('align'=>'left');
    $style_cell_pright = array('align'=>'right'); */

    $filename = 'Schedule'; 

    $sectionWord = $PHPWord->addSection([
        'marginTop' => 600,
        'marginBottom' => 600,
        'marginLeft' => 600,
        'marginRight' => 600,
        'orientation' => 'portrait',
        'footerHeight' => 350,
        'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
        'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(5.5),
        ]);
    $header = $sectionWord->addHeader();
    $footer = $sectionWord->addFooter();
    $textRun = $footer->addTextRun(['alignment' => 'center']);
    $textRun->addTextBreak(1);
    $textRun->addField('PAGE');

    $prev_adjudicator_id = 0;
    foreach($divisions as $division) {
        if( !isset($division['timeslots']) ) {
            continue;
        }
        if( $prev_adjudicator_id != $division['adjudicator_id'] ) {
            $sectionWord = $PHPWord->addSection([
                'marginTop' => 600,
                'marginBottom' => 600,
                'marginLeft' => 600,
                'marginRight' => 600,
                'footerHeight' => 250,
                'orientation' => 'portrait',
                'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
                'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(5.5),
                ]);
            $header = $sectionWord->addHeader();
            $footer = $sectionWord->addFooter();
            $textRun = $footer->addTextRun(['alignment' => 'center']);
            $textRun->addField('PAGE');
            $header->addText(htmlspecialchars($division['section_name']), 'Division Font', 'Divisions');
            $header->addText(htmlspecialchars($division['adjudicator_name']), 'Division Font', 'Locations');
            $adjudicator = $sectionWord->addTextRun('Adjudicators');
            if( $division['adjudicator_image_id'] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadOriginalStorageFilename');
                $rc = ciniki_images_hooks_loadOriginalStorageFilename($ciniki, $tnid, [
                    'image_id' => $division['adjudicator_image_id'],
                    ]);
                if( $rc['stat'] == 'ok' ) { 
                    $src = file_get_contents($rc['filename']);
                    $adjudicator->addImage($src, [
                        'align' => 'right',
                        'width' => 125,
//                        'wrappingStyle' => 'inline',
                        'wrappingStyle'=>'square',
                        'positioning' => 'absolute',
                        'posHorizontal'    => \PhpOffice\PhpWord\Style\Image::POSITION_HORIZONTAL_RIGHT,
                        'posHorizontalRel' => 'margin',
                        'posVerticalRel' => 'line'
                        ]);
                }
            }
            $paragraphs = explode("\n", $division['adjudicator_bio']);
            foreach($paragraphs as $p) {
                if( trim($p) != '' ) {
                    $adjudicator->addText(htmlspecialchars($p), 'Adjudicators Font', 'Adjudicators');
//                    $adjudicator->addTextBreak(2, 'Adjudicators Font', 'Adjudicators');
                    $adjudicator = $sectionWord->addTextRun('Adjudicators');
                }
            }
        }
        $prev_adjudicator_id = $division['adjudicator_id'];
        $sectionWord = $PHPWord->addSection([
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 600,
            'marginRight' => 600,
            'footerHeight' => 250,
            'orientation' => 'portrait',
            'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
            'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(5.5),
            ]);
        $header = $sectionWord->addHeader();
        $footer = $sectionWord->addFooter();

        $header->addText(htmlspecialchars($division['section_name'] . ' - ' . $division['date']), 'Division Font', 'Divisions');
//        $header->addText(htmlspecialchars($division['date']), 'Division Font', 'Divisions');
        $header->addText(htmlspecialchars($division['location_name']), 'Location Font', 'Locations');
        $textRun = $footer->addTextRun(['alignment' => 'center']);
        $textRun->addField('PAGE');

        foreach($division['timeslots'] as $timeslot) {
            $prev_time = '';
            $prev_class_code = '';
            $num = 1;
            if( !isset($timeslot['registrations']) ) {
                $table = $sectionWord->addTable([
                    'borderTopSize'=>0, 
                    'borderBottomSize'=>0, 
                    'borderLeftSize'=>0, 
                    'borderRightSize'=>0, 
                    'borderColor' => 'dddddd',
                    'cellMargin' => 0,
                    'cellMarginLeft' => 0,
                    'cellMarginTop' => 0,
                    'cellMarginBottom' => 0,
                    'cellMarginRight' => 0,
                    'shading' => ['fill'=>'dddddd'],
                    'cellSpacing' => 0,
                    ]);
                $table->addRow(240, ['vAlign'=>'center', 'cantSplit'=>'true', 'shadding'=>'dddddd']);
                $table->addCell(12000, ['valign'=>'center', 'bgColor'=>'dddddd'])->addText(htmlspecialchars($timeslot['time']), 'Timeslot Font', 'Timeslots');
                $sectionWord->addText(htmlspecialchars($timeslot['name']), 'Timeslot Font', 'Classes');
                $sectionWord->addTextBreak(1, null, 'Registrations Break');
                continue;
            }
            foreach($timeslot['registrations'] as $reg) {
                if( $prev_class_code != $reg['class_code'] ) {
                    $num = 1;
                    if( $prev_time != $timeslot['time'] ) {
                        $time_text = $timeslot['time'];
                        $table = $sectionWord->addTable([
                            'borderTopSize'=>0, 
                            'borderBottomSize'=>0, 
                            'borderLeftSize'=>0, 
                            'borderRightSize'=>0, 
                            'borderColor' => 'dddddd',
                            'cellMargin' => 0,
                            'cellMarginLeft' => 0,
                            'cellMarginTop' => 0,
                            'cellMarginBottom' => 0,
                            'cellMarginRight' => 0,
                            'shading' => ['fill'=>'dddddd'],
                            'cellSpacing' => 0,
                            ]);
                        $table->addRow(240, ['vAlign'=>'center', 'cantSplit'=>'true', 'shadding'=>'dddddd']);
                        $table->addCell(12000, ['valign'=>'center', 'bgColor'=>'dddddd'])->addText(htmlspecialchars($time_text), 'Timeslot Font', 'Timeslots');
                    } else {
                        $time_text = ' ';
                    }
                    //$name = "Class " . $reg['class_code'] . ' - ' . $reg['section_name'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name'];
//                    $name = $reg['category_name'] . ' - ' . $reg['class_name'];
                    $name = $reg['class_name'];
                    if( isset($timeslot['groupname']) && $timeslot['groupname'] != '' ) {
                        $name .= ' - ' . $timeslot['groupname'];
                    }
                    $sectionWord->addText(htmlspecialchars($name), 'Timeslot Font', 'Classes');
                    $table = $sectionWord->addTable([
                        'borderTopSize'=>1, 
                        'borderBottomSize'=>1, 
                        'borderLeftSize'=>1, 
                        'borderRightSize'=>1, 
                        'borderColor' => 'ffffff',
                        'cellMargin' => 50,
                        'cellMarginLeft' => 0,
                        'cellMarginTop' => 25,
                        'cellMarginBottom' => 0,
                        'cellSpacing' => 0,
                        ]);
                }
                $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg);
                $titles = isset($rc['titles']) ? $rc['titles'] : '';
               
                $table->addRow(10, ['cantSplit'=>'true']);
//                $table->addCell(350)->addText("{$num}.", 'Registrations Font', 'Registrations');
                $table->addCell(3000)->addText(htmlspecialchars($reg['name']), 'Registrations Font', 'Registrations');
                $table->addCell(4500)->addText(htmlspecialchars($titles), 'Registrations Font', 'Registrations');

                $prev_class_code = $reg['class_code']; 
                $prev_time = $timeslot['time'];
                $num++;
            }
            $sectionWord->addTextBreak(1, null, 'Registrations Break');
        }
    }

    $sectionWord = $PHPWord->addSection([
        'marginTop' => 600,
        'marginBottom' => 600,
        'marginLeft' => 600,
        'marginRight' => 600,
        'footerHeight' => 250,
        'orientation' => 'portrait',
        'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(8.5),
        'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::inchToTwip(5.5),
        ]);
    $header = $sectionWord->addHeader();
    $footer = $sectionWord->addFooter();
    $textRun = $footer->addTextRun(['alignment' => 'center']);
    $textRun->addField('PAGE');

    return array('stat'=>'ok', 'word'=>$PHPWord, 'filename'=>$filename);
}
?>
