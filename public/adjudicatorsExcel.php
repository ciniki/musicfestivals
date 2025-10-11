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
function ciniki_musicfestivals_adjudicatorsExcel(&$ciniki) {
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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.adjudicatorsExcel');
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

    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.discipline, "
        . "customers.display_name, "
        . "adjudicators.flags, "
        . "GROUP_CONCAT(emails.email SEPARATOR ', ') AS email "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY adjudicators.id "
        . "ORDER BY customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'discipline', 'display_name', 'email', 'flags'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1184', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();
    foreach($adjudicators as $aid => $adjudicator) {
        $adjudicators[$aid]['live'] = '';
        $adjudicators[$aid]['virtual'] = '';
        if( ($adjudicator['flags']&0x01) == 0x01 || ($adjudicator['flags']&0x03) == 0 ) {
            $adjudicators[$aid]['live'] = 'yes';
        }
        if( ($adjudicators[$aid]['flags']&0x02) == 0x02 || ($adjudicator['flags']&0x03) == 0 ) {
            $adjudicators[$aid]['virtual'] = 'yes';
        }
    }

    $sheets = [
        'adjudicators' => [
            'label' => 'Adjudicators',
            'columns' => [
                ['label' => 'Adjudicator', 'field' => 'display_name'],
                ['label' => 'Email', 'field' => 'email'],
                ['label' => 'Discipline', 'field' => 'discipline'],
                ],
            'rows' => $adjudicators,
            ],
        ];

    // Virtual
    if( ($festival['flags']&0x06) > 0 ) {
        $sheets['adjudicators']['columns'][] = ['label' => 'Live', 'field' => 'live'];
        $sheets['adjudicators']['columns'][] = ['label' => 'Virtual', 'field' => 'virtual'];
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'excelGenerate');
    return ciniki_core_excelGenerate($ciniki, $args['tnid'], [
        'sheets' => $sheets,
        'download' => 'yes',
        'filename' => 'Adjudicators.xlsx'
        ]);
}
?>
