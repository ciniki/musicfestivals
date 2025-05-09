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
function ciniki_musicfestivals_templates_scheduleLDNWord(&$ciniki, $tnid, $args) {

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

    //
    // Load the teachers
    //
    $strsql = "SELECT customers.id, "
        . "customers.last, "
        . "customers.first "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "( "
                . "registrations.teacher_customer_id = customers.id "
                . "OR registrations.teacher2_customer_id = customers.id "
                . ") "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY customers.last, customers.first "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'last', 'first')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.923', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
    }
    $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();

    //
    // Load the accompanists
    //
    $strsql = "SELECT customers.id, "
        . "customers.last, "
        . "customers.first "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "registrations.accompanist_customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY customers.last, customers.first "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'accompanists', 'fname'=>'id', 'fields'=>array('id', 'last', 'first')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.922', 'msg'=>'Unable to load accompanists', 'err'=>$rc['err']));
    }
    $accompanists = isset($rc['accompanists']) ? $rc['accompanists'] : array();

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %e, %Y') AS division_date_text, "
        . "locations.name AS location_name, "
        . "divisions.adjudicator_id, "
        . "customers.display_name AS adjudicator_name, "
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
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
            . "divisions.adjudicator_id = adjudicators.id "
            ."AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
    $strsql .= "ORDER BY divisions.division_date, ssections.sequence, divisions.name, divisions.id, slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence, class_code, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'section_name', 'date'=>'division_date_text', 
                'location_name', 'adjudicator_id', 'adjudicator_name',
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
                'description', 'class_code', 'class_name', 'category_name', 'section_name', 
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
//    require_once($ciniki['config']['core']['lib_dir'] . '/PHPWord/bootstrap.php');
//    require_once($ciniki['config']['core']['lib_dir'] . '/PHPWord/src/PhpWord/Autoloader.php');
//    \PhpOffice\PhpWord\Autoloader::register();

    $PHPWord = new \PhpOffice\PhpWord\PhpWord();
    $PHPWord->addTitleStyle(1, array('bold'=>true, 'size'=>20), array('spaceBefore'=>240, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(2, array('bold'=>true, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(3, array('bold'=>false, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
/*    $PHPWord->addParagraphStyle('Dates', array('align' => 'center', 'spaceAfter' => 0, 'spaceBefore'=>120,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        'borderSize' => 3,
        'borderColor' => '000000',
        'space' => ['before' => 120, 'after' => 0],
        )); */
    $PHPWord->addParagraphStyle('Divisions', array('align' => 'center', 'spaceAfter' => 0, 'spaceBefore'=>0,
//        'shading' => ['fill' => 'aaaaaa'],
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        'borderSize' => 3,
        'borderColor' => '000000',
        ));
/*    $PHPWord->addParagraphStyle('Adjudicators', array('align' => 'center', 'spaceAfter' => 120, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        'borderSize' => 3,
        'borderColor' => '000000',
        'space' => ['before' => 0, 'after' => 120],
        )); */
    $PHPWord->addParagraphStyle('Timeslots', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>120,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        'tabs' => array(
            new \PhpOffice\PhpWord\Style\Tab('right', 3250),
            )),
    );
    $PHPWord->addParagraphStyle('Category Name', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        'tabs' => array(
            new \PhpOffice\PhpWord\Style\Tab('left', 1500),
            )),
    );
    $PHPWord->addParagraphStyle('Class Name', array('align' => 'left', 'spaceAfter' => 120, 'spaceBefore'=>0,
        'keepLines' => true,
        'keepNext' => true,
        'indentation' => [],
        'tabs' => array(
            new \PhpOffice\PhpWord\Style\Tab('left', 1500),
            )),
    );
    $PHPWord->addParagraphStyle('Registrations', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>0,
        'indentation' => ['left' => 360, 'hanging' => 360],
        'keepLines' => true,
        'keepNext' => true,
        'tabs' => array(
           new \PhpOffice\PhpWord\Style\Tab('left', 360),
           new \PhpOffice\PhpWord\Style\Tab('left', 480),
           ),
        ));
/*    $PHPWord->addNumberingStyle('Registrations List', array(
        'type' => 'singleLevel',
        'start' => 1,
        'levels' => [
            ['format' => 'decimal', 'text' => '%1.', 'start'=>1,'restart'=>true, 'left' => 120, 'hanging' => 120, 'tabPos'=>120],
            ], 
        )); */
    $PHPWord->addParagraphStyle('Registrations Break', array('align' => 'left', 'spaceAfter' => 120, 'spaceBefore'=>0,
        'keepLines' => false,
        'keepNext' => false,
        ));
//    $PHPWord->addParagraphStyle('pTitles', array('align' => 'left', 'spaceAfter' => 0));
    $PHPWord->addFontStyle('Division Font', ['size'=>11, 'bold'=>true]);
    $PHPWord->addFontStyle('Location Font', ['size'=>11, 'bold'=>true]);
    $PHPWord->addFontStyle('Adjudicator Font', ['bold'=>false]);
    $PHPWord->addFontStyle('Timeslot Font', ['bold'=>true]);
    $PHPWord->addFontStyle('Class Font', ['bold'=>true]);
//    $PHPWord->addParagraphStyle('pNotes', ['align' => 'left', 'spaceAfter' => 0, 'indentation' => ['left' => 500]]);
    $style_table = array('cellMargin'=>80, 'borderColor'=>'aaaaaa', 'borderSize'=>6);
    $style_header = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'bgColor'=>'dddddd', 'valign'=>'center');
    $style_cell = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'valign'=>'center', 'bgcolor'=>'ffffff');
    $style_header_font = array('bold'=>true, 'spaceAfter'=>20);
    $style_cell_font = array();
    $style_header_pleft = array('align'=>'left');
    $style_header_pright = array('align'=>'right');
    $style_cell_pleft = array('align'=>'left');
    $style_cell_pright = array('align'=>'right');

    $sectionWord = $PHPWord->addSection([
        'marginTop' => 500,
        'marginBottom' => 500,
        'marginLeft' => 500,
        'marginRight' => 500,
        'orientation' => 'portrait',
        'colsNum' => 3,
        'colsSpace' => 500,
        'paperSize' => 'Letter',
        ]);
//    $sectionWord->setMarginLeft(5);
//    $sectionWord->setMarginRight(5);

    $filename = 'Schedule'; 
    $newpage = 'yes';
    $continued_str = ' (continued...)';

    //
    // Add the teachers
    //
    if( isset($teachers) && count($teachers) > 0 ) {
        $sectionWord->addText(htmlspecialchars('Teachers'), 'Division Font', 'Divisions');
        foreach($teachers as $teacher) {
            $name = $teacher['last'];
            $name .= ($name != '' ? ', ' : '') . $teacher['first'];
            $sectionWord->addText(htmlspecialchars("{$name}"), 'Registration Font', 'Registrations');
        }
        $sectionWord->addTextBreak(1, null, 'Registrations Break');
    }

    //
    // Add the accompanists
    //
    if( isset($accompanists) && count($accompanists) > 0 ) {
        $sectionWord->addText(htmlspecialchars('Adjudicators'), 'Division Font', 'Divisions');
        foreach($accompanists as $accompanist) {
            $name = $accompanist['last'];
            $name .= ($name != '' ? ', ' : '') . $accompanist['first'];
            $sectionWord->addText(htmlspecialchars("{$name}"), 'Registration Font', 'Registrations');
        }
        $sectionWord->addPageBreak();
    }
    foreach($divisions as $division) {
        if( !isset($division['timeslots']) ) {
            continue;
        }
        $sectionWord->addText(htmlspecialchars($division['date']), 'Division Font', 'Divisions');
        $sectionWord->addText(htmlspecialchars($division['location_name']), 'Location Font', 'Divisions');
        $sectionWord->addText(htmlspecialchars("Adjudicator: " . $division['adjudicator_name']), 'Adjudicator Font', 'Divisions');

        foreach($division['timeslots'] as $timeslot) {
            $prev_class_code = '';
            $num = 1;
            if( !isset($timeslot['registrations']) ) {
                continue;
            }
//            $list_style->setRestart(1);
            foreach($timeslot['registrations'] as $reg) {
                if( $prev_class_code != $reg['class_code'] ) {
                    $sectionWord->addText(htmlspecialchars($timeslot['time'] . "\tClass: " . $reg['class_code']), 'Timeslot Font', 'Timeslots');
                    $sectionWord->addText(htmlspecialchars($reg['section_name']), 'Class Font', 'Category Name');
                    $sectionWord->addText(htmlspecialchars($reg['category_name'] . ' - ' . $reg['class_name']), 'Class Font', 'Class Name');
                }
                $sectionWord->addText(htmlspecialchars("{$num}.\t{$reg['name']}"), 'Registration Font', 'Registrations');
//                $sectionWord->addListItem(htmlspecialchars("{$reg['name']}"), 0, 'Registration Font', 'Registrations List', 'Registrations'); 
                $prev_class_code = $reg['class_code']; 
                $num++;
            }
//            $sectionWord->addText("\n");
            $sectionWord->addTextBreak(1, null, 'Registrations Break');
        }
    }


    return array('stat'=>'ok', 'word'=>$PHPWord, 'filename'=>$filename);
}
?>
