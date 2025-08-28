<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_festivalSyllabusExcel($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'syllabus_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalSyllabusExcel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load the syllabus
    //
    $strsql = "SELECT classes.id, "
        . "sections.name AS section_name, "
        . "categories.name AS category_name, "
        . "classes.code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "classes.feeflags, "
        . "classes.titleflags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee, "
        . "classes.min_competitors, "
        . "classes.max_competitors, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "classes.provincials_code, "
        . "classes.synopsis, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    if( isset($args['syllabus_id']) && $args['syllabus_id'] > 0 ) {
        $strsql .= "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $args['syllabus_id']) . "' ";
    }
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    }
    $strsql .= "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.code "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'section_name', 'category_name', 'code', 'class_name', 'flags'=>'class_flags', 'feeflags', 'titleflags', 
                'earlybird_fee', 'fee', 'virtual_fee', 
                'earlybird_plus_fee', 'plus_fee', 'min_competitors', 'max_competitors', 'min_titles', 
                'max_titles', 'provincials_code', 'synopsis', 'schedule_seconds', 'schedule_at_seconds', 
                'schedule_ata_seconds'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1083', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    foreach($classes as $cid => $class) {
        if( ($class['flags']&0xC000) == 0x4000 ) {
            $classes[$cid]['competitor_type'] = 'individual';
        } elseif( ($class['flags']&0xC000) == 0x8000 ) {
            $classes[$cid]['competitor_type'] = 'group';
        } else {
            $classes[$cid]['competitor_type'] = 'either';
        }
        $classes[$cid]['instrument'] = ($class['flags']&0x01) == 0x01 ? 'yes' : '';
        if( ($class['flags']&0x3000) == 0x1000 ) {
            $classes[$cid]['accompanist'] = 'required';
        } elseif( ($class['flags']&0x3000) == 0x2000 ) {
            $classes[$cid]['accompanist'] = 'optional';
        } else {
            $classes[$cid]['accompanist'] = '';
        }
        if( ($class['flags']&0x0C000000) == 0x04000000 ) {
            $classes[$cid]['movements'] = 'required';
        } elseif( ($class['flags']&0x0C000000) == 0x08000000 ) {
            $classes[$cid]['movements'] = 'optional';
        } else {
            $classes[$cid]['movements'] = '';
        }
        if( ($class['flags']&0x30000000) == 0x10000000 ) {
            $classes[$cid]['composer'] = 'required';
        } elseif( ($class['flags']&0x30000000) == 0x20000000 ) {
            $classes[$cid]['composer'] = 'optional';
        } else {
            $classes[$cid]['composer'] = '';
        }
        $classes[$cid]['mark'] = ($class['flags']&0x0100) == 0x0100 ? 'yes' : '';
        $classes[$cid]['placement'] = ($class['flags']&0x0200) == 0x0200 ? 'yes' : '';
        $classes[$cid]['level'] = ($class['flags']&0x0400) == 0x0400 ? 'yes' : '';
    }

    $columns = [
        ['label' => 'Section', 'field' => 'section_name'],
        ['label' => 'Category', 'field' => 'category_name'],
        ['label' => 'Class Code', 'field' => 'code'],
        ['label' => 'Class Name', 'field' => 'class_name'],
        ];
    if( ($festival['flags']&0x20) == 0x20 ) {
        $columns[] = ['label' => 'Earlybird Fee', 'field'=>'earlybird_fee', 'format'=>'currency'];
    }
    if( ($festival['flags']&0x04) == 0x04 ) {
        $columns[] = ['label' => 'Live Fee', 'field'=>'fee', 'format'=>'currency'];
        $columns[] = ['label' => 'Virtual Fee', 'field'=>'virtual_fee', 'format'=>'currency'];
    } else {
        $columns[] = ['label' => 'Fee', 'field'=>'fee', 'format'=>'currency'];
    }
    if( ($festival['flags']&0x10) == 0x10 ) {
        if( ($festival['flags']&0x20) == 0x20 ) {
            $columns[] = ['label' => 'Earlybird Plus', 'field'=>'earlybird_plus_fee', 'format'=>'currency'];
        }
        $columns[] = ['label' => 'Plus Fee', 'field'=>'plus_fee', 'format'=>'currency'];
    }
    $columns[] = ['label' => 'Min Competitors', 'field'=>'min_competitors'];
    $columns[] = ['label' => 'Max Competitors', 'field'=>'max_competitors'];
    $columns[] = ['label' => 'Competitor Type', 'field'=>'competitor_type'];
    $columns[] = ['label' => 'Instrument', 'field'=>'instrument'];
    $columns[] = ['label' => 'Accompanist', 'field'=>'accompanist'];
    $columns[] = ['label' => 'Min Titles', 'field'=>'min_titles'];
    $columns[] = ['label' => 'Max Titles', 'field'=>'max_titles'];
    $columns[] = ['label' => 'Movements', 'field'=>'movements'];
    $columns[] = ['label' => 'Composer', 'field'=>'composer'];
    $columns[] = ['label' => 'Mark', 'field'=>'mark'];
    $columns[] = ['label' => 'Placement', 'field'=>'placement'];
    $columns[] = ['label' => 'Level', 'field'=>'level'];

    $sheets = [
        'classes' => [
            'label' => 'Syllabus',
            'columns' => $columns,
            'rows' => $classes,
            ],
        ];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'excelGenerate');
    return ciniki_core_excelGenerate($ciniki, $args['tnid'], [
        'sheets' => $sheets,
        'download' => 'yes',
        'filename' => 'Syllabus.xlsx'
        ]);
}
?>
