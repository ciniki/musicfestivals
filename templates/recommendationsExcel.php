<?php
//
// Description
// ===========
// This function will generate an Excel file with adjudicator recommendations
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_recommendationsExcel(&$ciniki, $tnid, $args) {

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

    $filename = 'Recommendations';

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
        . "ciniki_musicfestivals.primary_image_id, "
        . "ciniki_musicfestivals.description, "
        . "ciniki_musicfestivals.document_logo_id, "
        . "ciniki_musicfestivals.document_header_msg, "
        . "ciniki_musicfestivals.document_footer_msg "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.651', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.652', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];


    //
    // Get the recommendation entries
    //
    $strsql = "SELECT entries.id, "
        . "entries.position, "
        . "entries.name, "
        . "entries.mark, "
        . "recommendations.id AS recommendation_id, "
        . "recommendations.date_submitted, "
        . "members.name AS member_name, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
            . "entries.recommendation_id = recommendations.id "
            . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
            . "recommendations.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "entries.class_id = classes.id "
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
        . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['recommendation_id']) && $args['recommendation_id'] > 0 ) {
        $strsql .= "AND recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' ";
    } elseif( isset($args['class_id']) && $args['class_id'] > 0 ) {
        $strsql .= "AND classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
    } elseif( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    } elseif( isset($args['member_id']) && $args['member_id'] > 0 ) {
        $strsql .= "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' ";
    }
    $strsql .= "ORDER BY sections.sequence, categories.sequence, categories.name, classes.sequence, classes.name, entries.position "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
        array('container'=>'classes', 'fname'=>'class_id', 'fields'=>array('id'=>'class_id', 'code'=>'class_code', 'name'=>'class_name')),
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'recommendation_id', 'position', 'name', 'mark',
                'date_submitted', 'member_name'),
            'utctotz'=>array(
                'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.655', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Export to excel
    //
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $col = 0;
    $row = 1;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Section', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Class', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Name', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Mark', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Position', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Festival', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Submitted', false);
    $objPHPExcelWorksheet->getStyle('A1:G1')->getFont()->setBold(true);
    $row++;

    $num = 0;
    foreach($sections as $section) {
        if( isset($args['section_id']) && $args['section_id'] == $section['id'] ) {
            $filename .= ' - ' . $section['name'];
        }
       
        foreach($section['classes'] as $class) {
            if( isset($args['class_id']) && $args['class_id'] == $class['id'] ) {
                $filename .= ' - ' . $class['code'] . ' - ' . $class['name'];
            }

            foreach($class['entries'] as $entry) {
                switch($entry['position']) {
                    case 1: $entry['position'] = '1st Recommendation'; break;
                    case 2: $entry['position'] = '2nd Recommendation'; break;
                    case 3: $entry['position'] = '3rd Recommendation'; break;
                    case 101: $entry['position'] = '1st Alternate'; break;
                    case 102: $entry['position'] = '2nd Alternate'; break;
                    case 103: $entry['position'] = '3rd Alternate'; break;
                }
                $col = 0;
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $section['name'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $class['code'] . ' - ' . $class['name'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $entry['name'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $entry['mark'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $entry['position'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $entry['member_name'], false);
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $entry['date_submitted'], false);

                $row++;
            }
            $row++;
        }
    }

    for($i = 0; $i < 7; $i++) {
        $objPHPExcelWorksheet->getColumnDimension(chr($i+65))->setAutoSize(true);
    }
    $objPHPExcelWorksheet->freezePaneByColumnAndRow(0, 2);

    return array('stat'=>'ok', 'excel'=>$objPHPExcel, 'filename'=>$filename . '.xls');
}
?>
