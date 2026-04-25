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
function ciniki_musicfestivals_contactsExcel($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Section'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Class'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'accompanist_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accompanist'),
        'registration_tag'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registration Tag'),
        'member_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member'),
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.registrationsExcel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    $filename = $festival['year'] . ' Registrations';

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $args['tnid'], $festival);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.fee AS reg_fee, "
        . "registrations.flags, "
        . "registrations.status, "
        . "registrations.status AS status_text, "
        . "registrations.participation, "
        . "registrations.instrument, "
        . "registrations.internal_notes, "
        . "registrations.notes AS reg_notes, "
        . "registrations.billing_customer_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.teacher2_customer_id, "
        . "registrations.accompanist_customer_id, "
        . "registrations.member_id, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "IFNULL(ctypes.ctype, 0) AS custtype, "
        . "competitors.id AS competitor_id, "
        . "competitors.ctype, "
        . "competitors.name AS competitor_name, "
        . "competitors.first AS competitor_first, "
        . "competitors.last AS competitor_last, "
        . "competitors.flags AS competitor_flags, "
        . "competitors.pronoun, "
        . "competitors.parent, "
        . "competitors.address, "
        . "competitors.city, "
        . "competitors.province, "
        . "competitors.postal, "
        . "competitors.phone_home, "
        . "competitors.phone_cell, "
        . "competitors.email, "
        . "competitors.etransfer_email, "
        . "competitors.age AS cage, "
        . "competitors.study_level, "
        . "competitors.notes "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id ";
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
        $strsql .= "AND registrations.participation = 0 ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_customers AS ctypes ON ("
            . "registrations.billing_customer_id = ctypes.customer_id "
            . "AND ctypes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
            . "("
                . "registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    }
    if( isset($args['class_id']) && $args['class_id'] > 0 ) {
        $strsql .= "AND classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
    }
    $strsql .= "ORDER BY sections.id, classes.code, registrations.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'section_id', 'billing_customer_id', 'custtype', 
                'teacher_customer_id', 'accompanist_customer_id', 'member_id', 
                'display_name', 'section_name', 'category_id', 'category_name', 'class_code', 'class_name', 
                'fee'=>'reg_fee', 'invoice_number', 
                'participation', 'internal_notes', 'notes'=>'reg_notes', 'flags', 'status', 'status_text',
                'mark', 'placement', 'level', 'instrument',
                ),
            'maps'=>array(
                'status_text'=>$maps['registration']['status'],
                ),
            ),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'ctype', 'name'=>'competitor_name', 
                'first'=>'competitor_first', 'last'=>'competitor_last', 'flags'=>'competitor_flags',
                'pronoun', 'parent', 'address', 'city', 'province', 'postal', 'section_name',
                'phone_home', 'phone_cell', 'email', 'etransfer_email', 'cage', 'study_level', 'notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $registrations = $rc['registrations'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');

    $competitors = [];
    $teachers = [];
    $accompanists = [];
    $parents = [];


    foreach($registrations as $reg) {
        
        if( isset($reg['competitors']) ) {
            foreach($reg['competitors'] as $competitor) {
                if( $competitor['id'] > 0 && !isset($competitors[$competitor['id']]) ) {
                    $competitors[$competitor['id']] = $competitor;
                }
            }
        }
        if( $reg['teacher_customer_id'] > 0 && !isset($teachers[$reg['teacher_customer_id']]) ) {
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], [
                'customer_id' => $reg['teacher_customer_id'], 
                'phones'=>'yes', 
                'emails'=>'yes',
                ]);
            if( isset($rc['customer']) ) {
                $cust = $rc['customer'];
                $cust['section_name'] = $reg['section_name'];
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
                $teachers[$reg['teacher_customer_id']] = $cust;
            }
        }
        if( $reg['accompanist_customer_id'] > 0 && !isset($accomopanists[$reg['accompanist_customer_id']]) ) {
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], [
                'customer_id' => $reg['accompanist_customer_id'], 
                'phones'=>'yes', 
                'emails'=>'yes',
                ]);
            if( isset($rc['customer']) ) {
                $cust = $rc['customer'];
                $cust['section_name'] = $reg['section_name'];
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
                $accompanists[$reg['accompanist_customer_id']] = $cust;
            }
        }
        if( $reg['billing_customer_id'] != $reg['teacher_customer_id'] && $reg['custtype'] == 30 ) {
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], [
                'customer_id' => $reg['billing_customer_id'], 
                'phones'=>'yes', 
                'emails'=>'yes',
                ]);
            if( isset($rc['customer']) ) {
                $cust = $rc['customer'];
                $cust['section_name'] = $reg['section_name'];
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
                $parents[$reg['billing_customer_id']] = $cust;
            }
        }
    }

    //
    // Export to excel
    //
    ini_set('memory_limit', '1024M');
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $row = 1;
    foreach($competitors as $competitor) {
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'ciniki.musicfestivals.competitor.' . $competitor['id'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['email'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['first'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['last'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['section_name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['phone_cell'], false);
        $row++;
    }

    foreach($teachers as $customer) {
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'ciniki.musicfestivals.customer.' . $customer['id'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['email'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['first'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['last'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['section_name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['phone_cell'], false);
        $row++;
    }

    foreach($parents as $customer) {
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'ciniki.musicfestivals.customer.' . $customer['id'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['email'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['first'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['last'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['section_name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['phone_cell'], false);
        $row++;
    }

    foreach($accompanists as $customer) {
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Accompanist', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'ciniki.musicfestivals.customer.' . $customer['id'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['email'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['first'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['last'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['section_name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['phone_cell'], false);
        $row++;
    }

    $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('F')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('G')->setAutoSize(true);

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
