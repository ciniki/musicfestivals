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

    $filename = 'Results';

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
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Mark', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Adjudicator', false);
    $objPHPExcelWorksheet->getStyle('A1:F1')->getFont()->setBold(true);
    $row++;

    foreach($args['recommendations'] as $rec) {
        
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['class'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['position'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['status_text'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['mark'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $rec['adjudicator_name'], false);

        $color = '';
        if( $rec['cssclass'] == 'statusyellow' ) {
            $color = 'FFFDC5';
        } elseif( $rec['cssclass'] == 'statusorange' ) {
            $color = 'FFEFDD';
        } elseif( $rec['cssclass'] == 'statusgreen' ) {
            $color = 'DDFFDD';
        } elseif( $rec['cssclass'] == 'statusred' ) {
            $color = 'FFDDDD';
        } elseif( $rec['cssclass'] == 'statuspurple' ) {
            $color = 'F0DDFF';
        } elseif( $rec['cssclass'] == 'statusblue' ) {
            $color = 'DDF1FF';
        } elseif( $rec['cssclass'] == 'statusgrey' ) {
            $color = 'EEEEEE';
        }
        if( $color != '' ) {
            $objPHPExcelWorksheet->getStyle("A{$row}:F{$row}")->applyFromArray(
                array('fill'=>array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID, 
                    'color' => array('rgb' => $color),
                    )));
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
