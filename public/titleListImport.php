<?php
//
// Description
// -----------
// This method will import an excel file into a title list
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
require '/ciniki/ciniki-lib/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

function ciniki_musicfestivals_titleListImport(&$ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'list_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Approved Title List'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleListUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1160', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

    //
    // Make sure a file was submitted
    //
    if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1166', 'msg'=>'No file specified.'));
    }

    ini_set('memory_limit', '4096M');

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['uploadfile']['tmp_name']);
    $section_sequence = 1;
    for($sheet = 0; $sheet < 1; $sheet++) {
        $objWorksheet = $spreadsheet->getSheet($sheet);

        $start = 2;
        $size = 10000;

        $numRows = $objWorksheet->getHighestRow(); // e.g. 10
        $columns = [];
        //
        // Find the column headers
        //
        for($row = $start; $row <= ($start + ($size-1)) && $row <= $numRows; $row++) {
            for($col = 1; $col < 5; $col++) {
                $val = strtolower(trim($objWorksheet->getCell([$col, $row])->getValue()));
                if( strlen($val) > 100 ) { 
                    continue;
                }
                if( preg_match("/(title|movement|composer|source type)/", $val, $m) ) {
                    $columns[$m[1]] = $col;
                }
            }
            if( count($columns) > 0 ) {
                break;
                $start = $row++;
            }
        }

        if( !isset($columns['title']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1161', 'msg'=>'Missing title column, unable to import'));
        }
        if( !isset($columns['movement']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1162', 'msg'=>'Missing movement column, unable to import'));
        }
        if( !isset($columns['composer']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1163', 'msg'=>'Missing composer column, unable to import'));
        }

        //
        // Load the titles
        //
        $prev_movements;
        for($row = $start; $row <= ($start + ($size-1)) && $row <= $numRows; $row++) {
            $title = [
                'list_id' => $args['list_id'],
                'title' => trim($objWorksheet->getCell([$columns['title'], $row])->getValue()),
                ];
            if( $title['title'] == '' ) {
                continue;
            }
            if( isset($columns['movement']) ) {
                $title['movements'] = trim($objWorksheet->getCell([$columns['movement'], $row])->getValue());
                if( $title['movements'] == '' && $columns['movement'] == 1 ) {
                    $title['movements'] = $prev_movements;
                } else {
                    $prev_movements = $title['movements'];
                }
            }
            if( isset($columns['composer']) ) {
                $title['composer'] = trim($objWorksheet->getCell([$columns['composer'], $row])->getValue());
            }
            if( isset($columns['source type']) ) {
                $title['source_type'] = trim($objWorksheet->getCell([$columns['source type'], $row])->getValue());
            }

            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.title', $title, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1164', 'msg'=>'Unable to add the title', 'err'=>$rc['err']));
            }
            
        }
    }

    return array('stat'=>'ok');
}
?>
