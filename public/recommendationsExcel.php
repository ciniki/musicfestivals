<?php
//
// Description
// -----------
// This method returns the recommendation entries in excel format.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_recommendationsExcel(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Adjudicator Recommendation'),
        'recommendation_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommendation'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

   
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'recommendationsExcel');
    $rc = ciniki_musicfestivals_templates_recommendationsExcel($ciniki, $args['tnid'], $args);  
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.611', 'msg'=>'', 'err'=>$rc['err']));
    }

    //
    // Output the excel file
    //
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $rc['filename'] . '"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($rc['excel'], 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
