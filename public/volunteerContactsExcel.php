<?php
//
// Description
// -----------
// This method will return the excel export of all contacts related to registrations.
// This is use for importing into mailing lists
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerContactsExcel($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerContactsExcel');
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
    $filename = $festival['year'] . ' Volunteers';

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $args['tnid'], $festival);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $strsql = "SELECT volunteers.id, "
        . "volunteers.customer_id "
        . "FROM ciniki_musicfestival_volunteers AS volunteers "
        . "WHERE volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND volunteers.status < 80 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'volunteers', 'fname'=>'id', 'fields'=>array('id', 'customer_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1578', 'msg'=>'Unable to load volunteers', 'err'=>$rc['err']));
    }
    $volunteer_ids = isset($rc['volunteers']) ? $rc['volunteers'] : array();

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');

    foreach($volunteer_ids as $volunteer) {
        
        if( $volunteer['customer_id'] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], [
                'customer_id' => $volunteer['customer_id'], 
                'phones'=>'yes', 
                'emails'=>'yes',
                ]);
            if( isset($rc['customer']) ) {
                $cust = $rc['customer'];
                $cust['email'] = '';
                $cust['phone_cell'] = '';
                if( isset($cust['emails'][0]['address']) ) {
                    $cust['email'] = $cust['emails'][0]['address'];
                }
                if( isset($cust['phones']) ) {
                    foreach($cust['phones'] as $phone) {
                        if( preg_match("/cell/i", $phone['phone_label']) ) {
                            $cust['phone_cell'] = $phone['phone_number'];
                        }
                    }
                }
                $volunteers[$volunteer['customer_id']] = $cust;
            }
        }
    }

    //
    // Sort the contacts
    //
    uasort($volunteers, function($a, $b) {
        if( $a['first'] == $b['first'] ) {
            return strnatcasecmp($a['last'], $b['last']);
        }
        return strnatcasecmp($a['first'], $b['first']);
        });

    //
    // Export to excel
    //
    ini_set('memory_limit', '1024M');
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $row = 1;
    foreach($volunteers as $customer) {
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Volunteer', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'ciniki.musicfestivals.customer.' . $customer['id'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['email'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['first'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['last'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['phone_cell'], false);
        $row++;
    }

    $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('F')->setAutoSize(true);

    //
    // Output the excel file
    //
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
