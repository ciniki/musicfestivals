<?php
//
// Description
// ===========
// This function will generate a Word document of the syllabus.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_syllabusWord(&$ciniki, $tnid, $args) {

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
        . "classes.plus_fee "
        . "FROM ciniki_musicfestival_sections AS sections "
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
                'syllabus_id', 
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
    if( isset($rc['sections']) ) {
        $sections = $rc['sections'];
    } else {
        $sections = array();
    }

    //
    // Build word document
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/vendor/autoload.php');

    $PHPWord = new \PhpOffice\PhpWord\PhpWord();
    $PHPWord->setDefaultFontSize(12);
    \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
    $PHPWord->addTitleStyle(1, array('bold'=>true, 'size'=>20), array('spaceBefore'=>240, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(2, array('bold'=>true, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(3, array('bold'=>false, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addFontStyle('Classes Font', ['size'=>12, 'bold'=>false]);
    $PHPWord->addParagraphStyle('Classes', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
//        'keepNext' => true,
        'indentation' => [],
        ));
    $PHPWord->addParagraphStyle('ClassesWithSynopsis', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        ));
    $PHPWord->addParagraphStyle('Fees', array('align' => 'right', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
        'indentation' => [],
        ));
    $PHPWord->addFontStyle('Synopsis Font', ['size'=>12, 'bold'=>false, 'italic'=>true]);
    $PHPWord->addParagraphStyle('Synopsis', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
//        'keepNext' => true,
        'indentation' => [],
        ));

    $filename = 'Syllabus'; 

    $sectionWord = $PHPWord->addSection([
        'marginTop' => 1000,
        'marginBottom' => 1000,
        'marginLeft' => 1000,
        'marginRight' => 1000,
        'orientation' => 'portrait',
        'paperSize' => 'Letter',
        ]);
    $header = $sectionWord->addHeader();
    $footer = $sectionWord->addFooter();
    $textRun = $footer->addTextRun(['alignment' => 'center']);
    $textRun->addField('PAGE');

    foreach($sections as $section) {
//        if( $section['name'] == 'School Choirs' ) {
//            break;
//        }
        $sectionWord = $PHPWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'orientation' => 'portrait',
            'paperSize' => 'Letter',
            ]);
        $header = $sectionWord->addHeader();
        $footer = $sectionWord->addFooter();
        $textRun = $footer->addTextRun(['alignment' => 'center']);
        $textRun->addField('PAGE');

        $sectionWord->addTitle(htmlspecialchars($section['name']), 1);
        if( $section['description'] != '' ) {
            $section['description'] = preg_replace("/<br\>/", "<br />", $section['description']); 
            $section['description'] = preg_replace("/ &nbsp;/", "", $section['description']); 
            $section['description'] = preg_replace("/&nbsp;/", " ", $section['description']); 
            PhpOffice\PhpWord\Shared\Html::addHtml($sectionWord, $section['description'], false, true);
        }
       
        foreach($section['categories'] as $category) {
            $sectionWord->addTitle(htmlspecialchars($category['name']), 2);
            if( $category['description'] != '' ) {
                $category['description'] = preg_replace("/<br\>/", "<br />", $category['description']); 
                $category['description'] = preg_replace("/ &nbsp;/", "", $category['description']); 
                $category['description'] = preg_replace("/&nbsp;/", " ", $category['description']); 
                PhpOffice\PhpWord\Shared\Html::addHtml($sectionWord, $category['description'], false, true);
            }

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
            foreach($category['classes'] as $class) {
                $table->addRow(10, ['cantSplit'=>'false']);
                $pstyle = ($class['synopsis'] != '' ? 'ClassesWithSynopsis' : 'Classes');
                $table->addCell(1000)->addText("{$class['code']}", 'Classes Font', $pstyle);
                $class['name'] = preg_replace("/\&amp;/", '&', $class['name']);
                $table->addCell(9000)->addText(htmlspecialchars($class['name']), 'Classes Font', $pstyle);
                $table->addCell(1000)->addText(htmlspecialchars('$' . number_format($class['fee'], 0)), 'Classes Font', 'Fees');
                if( $class['synopsis'] != '' ) {
                    $table->addRow(10, ['cantSplit'=>'false']);
                    $table->addCell(1000)->addText("", 'Classes Font', 'Classes');
                    $cell = $table->addCell(9000);
                    $class['synopsis'] = preg_replace("/<br\>/", "<br />", $class['synopsis']); 
                    $class['synopsis'] = preg_replace("/ &nbsp;/", "", $class['synopsis']); 
                    $class['synopsis'] = preg_replace("/&nbsp;/", " ", $class['synopsis']); 
                    $class['synopsis'] = preg_replace("/<p>/", "<p style='margin-bottom: 0px;'><i>", $class['synopsis']); 
                    $class['synopsis'] = preg_replace("/<\/p>/", "</i></p>", $class['synopsis']); 
                    PhpOffice\PhpWord\Shared\Html::addHtml($cell, $class['synopsis'], false, true);
                    $table->addCell(1000)->addText("", 'Classes Font', 'Classes');
                } else {
//                    $table->addRow(3, ['cantSplit'=>'false']);
                }
            
            }
            
        }
    }


/*
    foreach($divisions as $division) {
        if( !isset($division['timeslots']) ) {
            continue;
        }
        $sectionWord = $PHPWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'orientation' => 'portrait',
            'paperSize' => 'Letter',
            ]);
        $header = $sectionWord->addHeader();
        $footer = $sectionWord->addFooter();

        $header->addText(htmlspecialchars($division['section_name'] . ' - Adjudicator: ' . $division['adjudicator_name']), 'Division Font', 'Divisions');
        $header->addText(htmlspecialchars($division['date']), 'Division Font', 'Divisions');
        $header->addText(htmlspecialchars($division['location_name']), 'Division Font', 'Locations');
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
                $table->addCell(12000, ['valign'=>'center', 'bgColor'=>'dddddd'])->addText(htmlspecialchars($timeslot['name']), 'Timeslot Font', 'Timeslots');
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
                    $name = "Class " . $reg['class_code'] . ' - ' . $reg['section_name'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name'];
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
                $table->addCell(350)->addText("{$num}.", 'Registrations Font', 'Registrations');
                $table->addCell(4000)->addText(htmlspecialchars($reg['name']), 'Registrations Font', 'Registrations');
                $table->addCell(8000)->addText(htmlspecialchars($titles), 'Registrations Font', 'Registrations');

                $prev_class_code = $reg['class_code']; 
                $prev_time = $timeslot['time'];
                $num++;
            }
            $sectionWord->addTextBreak(1, null, 'Registrations Break');
        }
    }
*/
    $sectionWord = $PHPWord->addSection([
        'marginTop' => 1000,
        'marginBottom' => 1000,
        'marginLeft' => 1000,
        'marginRight' => 1000,
        'orientation' => 'portrait',
        'paperSize' => 'Letter',
        ]);
    $header = $sectionWord->addHeader();
    $footer = $sectionWord->addFooter();
    $textRun = $footer->addTextRun(['alignment' => 'center']);
    $textRun->addField('PAGE');

    return array('stat'=>'ok', 'word'=>$PHPWord, 'filename'=>$filename);
}
?>
