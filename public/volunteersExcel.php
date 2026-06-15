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
function ciniki_musicfestivals_volunteersExcel($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteersExcel');
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
        . "volunteers.customer_id, "
        . "volunteers.notes, "
        . "volunteers.internal_notes, "
        . "assignments.id AS assignment_id, "
        . "shifts.shift_date, "
        . "TIME_TO_SEC(shifts.start_time) AS start_seconds, "
        . "TIME_TO_SEC(shifts.end_time) AS end_seconds "
        . "FROM ciniki_musicfestival_volunteers AS volunteers "
        . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
            . "volunteers.id = assignments.volunteer_id "
            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_volunteer_shifts AS shifts ON ("
            . "assignments.shift_id = shifts.id "
            . "AND shifts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND volunteers.status < 80 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'volunteers', 'fname'=>'id', 'fields'=>array('id', 'customer_id', 'notes', 'internal_notes')),
        array('container'=>'assignments', 'fname'=>'assignment_id', 
            'fields'=>array('id'=>'assignment_id', 'shift_date', 'start_seconds', 'end_seconds'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1622', 'msg'=>'Unable to load volunteers', 'err'=>$rc['err']));
    }
    $volunteer_ids = isset($rc['volunteers']) ? $rc['volunteers'] : array();

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');

    foreach($volunteer_ids as $volunteer) {
        
        if( $volunteer['customer_id'] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], [
                'customer_id' => $volunteer['customer_id'], 
                'phones'=>'yes', 
                'emails'=>'yes',
                'addresses'=>'yes',
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
                $cust['address1'] = '';
                if( isset($cust['addresses']) ) {
                    $i = 1;
                    foreach($cust['addresses'] as $addr) {
                        $cust["address{$i}"] = '';
                        if( $addr['address1'] != '' ) {
                            $cust["address{$i}"] .= $addr['address1'];
                        }
                        if( $addr['address2'] != '' ) {
                            $cust["address{$i}"] .= ($cust["address{$i}"] != '' ? ', ' : '') . $addr['address2'];
                        }
                        if( $addr['city'] != '' ) {
                            $cust["address{$i}"] .= ($cust["address{$i}"] != '' ? ', ' : '') . $addr['city'];
                        }
                        if( $addr['province'] != '' ) {
                            $cust["address{$i}"] .= ($cust["address{$i}"] != '' ? ', ' : '') . $addr['province'];
                        }
                        if( $addr['postal'] != '' ) {
                            $cust["address{$i}"] .= ($cust["address{$i}"] != '' ? '  ' : '') . $addr['postal'];
                        }
                        $i++;
                    }
                }
                //
                // Calculate number of shifts and total hours
                //
                $cust['num_shifts'] = 0;
                $seconds = 0;
                if( isset($volunteer['assignments']) ) {
                    foreach($volunteer['assignments'] as $shift) {
                        $cust['num_shifts']++;
                        if( $shift['end_seconds'] < $shift['start_seconds'] ) {
                            $seconds += (86400 - $shift['start_seconds']) + $shift['end_seconds'];
                        } else {
                            $seconds += $shift['end_seconds'] - $shift['start_seconds'];
                        }
                    }
                }
                $cust['hours'] = round(floor($seconds/60)/60, 2);
                $cust['notes'] = $volunteer['notes'];
                $cust['internal_notes'] = $volunteer['internal_notes'];

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
    $col = 0;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'First', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Last', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Shifts', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Hours', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Notes', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Internal Notes', false);

    $row = 2;
    foreach($volunteers as $customer) {
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['first'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['last'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['email'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['phone_cell'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['address1'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['num_shifts'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['hours'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['notes'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['internal_notes'], false);
        $row++;
    }

    $objPHPExcelWorksheet->getStyle('A1:I1')->getFont()->setBold(true);
    $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('F')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('G')->setAutoSize(true);
//    $objPHPExcelWorksheet->getColumnDimension('H')->setAutoSize(true);
//    $objPHPExcelWorksheet->getColumnDimension('I')->setAutoSize(true);
    $objPHPExcelWorksheet->freezePane("A2");

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
