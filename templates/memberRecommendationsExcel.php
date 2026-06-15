<?php
//
// Description
// -----------
// This function will generate an excel file of the recommendations results.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_templates_memberRecommendationsExcel(&$ciniki, $tnid, $args) {

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

    $filename = 'Provincial Recommendations';

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
    // Export to excel
    //
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $num = 0;
    $col = 0;
    $row = 1;

    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Class', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Position', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Status', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Adjudicator', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Mark', false);
    $objPHPExcelWorksheet->getStyle('A1:F1')->getFont()->setBold(true);
    $row++;

    foreach($args['recommendations'] as $rec) {
        
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['class'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['position_text'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['mark'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['status_text'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['adjudicator_name'], false);


        $color = '';
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationEntryStatusColour');
        $rc = ciniki_musicfestivals_recommendationEntryStatusColour($ciniki, $tnid, $rec);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        } 
        $fade = 'no';
        if( isset($rc['fill']) ) {
            $color = sprintf("%02X%02x%02X", $rc['fill'][0], $rc['fill'][1], $rc['fill'][2]);
        }
        if( isset($rc['fade']) ) {
            $color = sprintf("%02X%02x%02X", $rc['fade'][0], $rc['fade'][1], $rc['fade'][2]);
            $fade = 'yes';
        }
        $strike = 'no';
        if( isset($rc['strike']) && $rc['strike'] == 'yes' ) {
            $strike = 'yes';
        }

/*        if( str_contains($rec['cssclass'], 'statuslinethrough') ) {
            $strike = 'yes';
        }
        if( str_contains($rec['cssclass'], 'statusyellow') ) {
            $color = 'FFFDC5';
        } elseif( str_contains($rec['cssclass'], 'statusorange') ) {
            $color = 'FFEFDD';
        } elseif( str_contains($rec['cssclass'], 'statusgreen') ) {
            $color = 'DDFFDD';
        } elseif( str_contains($rec['cssclass'], 'statusred') ) {
            $color = 'FFDDDD';
        } elseif( str_contains($rec['cssclass'], 'statuspurple') ) {
            $color = 'F0DDFF';
        } elseif( str_contains($rec['cssclass'], 'statusblue') ) {
            $color = 'DDF1FF';
        } elseif( str_contains($rec['cssclass'], 'statusgrey') ) {
            $color = 'EEEEEE';
        } */
        if( $color != '' && $fade == 'no' ) {
            $objPHPExcelWorksheet->getStyle("A{$row}:F{$row}")->applyFromArray(
                array('fill'=>array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID, 
                    'color' => array('rgb' => $color),
                    )));
        } elseif( $color != '' && $fade == 'yes' ) {
            $objPHPExcelWorksheet->getStyle("A{$row}:A{$row}")->applyFromArray(
                array('fill'=>array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID, 
                    'color' => array('rgb' => $color),
                    )));
        }
        if( $strike == 'yes' ) {
            $objPHPExcelWorksheet->getStyle("A{$row}:F{$row}")->getFont()->setStrikethrough(true);
        }
        $row++;
    }

    for($i = 0; $i < $col; $i++) {
        $objPHPExcelWorksheet->getColumnDimension(chr($i+65))->setAutoSize(true);
    }
    $objPHPExcelWorksheet->freezePaneByColumnAndRow(0, 2);

    return array('stat'=>'ok', 'excel'=>$objPHPExcel, 'filename'=>$filename);
}
?>
