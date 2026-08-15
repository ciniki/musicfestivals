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
        'layout'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Layout'),
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

    //
    // Check the format desired
    //
    if( isset($args['layout']) && $args['layout'] == 'contacts' ) {
        $strsql = "SELECT adjudicators.id, "
            . "adjudicators.customer_id, "
            . "adjudicators.discipline, "
            . "adjudicators.flags "
            . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
            . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY adjudicators.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'customer_id', 'discipline', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.419', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
        }
        $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        foreach($adjudicators as $aid => $adjudicator) {
            $adjudicators[$aid]['label'] = 'Adjudicator';
            $adjudicators[$aid]['object_id'] = 'ciniki.customers.customer.' . $adjudicator['customer_id'];
            $adjudicators[$aid]['live'] = '';
            $adjudicators[$aid]['virtual'] = '';
            if( ($adjudicator['flags']&0x01) == 0x01 || ($adjudicator['flags']&0x03) == 0 ) {
                $adjudicators[$aid]['live'] = 'yes';
            }
            if( ($adjudicators[$aid]['flags']&0x02) == 0x02 || ($adjudicator['flags']&0x03) == 0 ) {
                $adjudicators[$aid]['virtual'] = 'yes';
            }
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], [
                'customer_id' => $adjudicator['customer_id'], 
                'phones'=>'yes', 
                'emails'=>'yes',
                ]);
            if( isset($rc['customer']) ) {
                $cust = $rc['customer'];
                $adjudicators[$aid]['email'] = '';
                $adjudicators[$aid]['phone_cell'] = '';
                $adjudicators[$aid]['first'] = $cust['first'];
                $adjudicators[$aid]['last'] = $cust['last'];
                if( isset($cust['emails'][0]['address']) ) {
                    $adjudicators[$aid]['email'] = $cust['emails'][0]['address'];
                }
                if( isset($cust['phones']) ) {
                    foreach($cust['phones'] as $phone) {
                        if( preg_match("/cell/i", $phone['phone_label']) ) {
                            $adjudicators[$aid]['phone_cell'] = $phone['phone_number'];
                        }
                    }
                }
            }
        }

        $sheets = [
            'adjudicators' => [
                'label' => 'Adjudicators',
                'headers' => 'no',
                'columns' => [
                    ['label' => 'Label', 'field' => 'label'],
                    ['label' => 'ID', 'field' => 'object_id'],
                    ['label' => 'Email', 'field' => 'email'],
                    ['label' => 'First', 'field' => 'first'],
                    ['label' => 'Last', 'field' => 'last'],
                    ['label' => 'Discipline', 'field' => 'discipline'],
                    ['label' => 'Cell', 'field' => 'phone_cell'],
                    ],
                'rows' => $adjudicators,
                ],
            ];

        // Virtual
        if( ($festival['flags']&0x06) > 0 ) {
            $sheets['adjudicators']['columns'][] = ['label' => 'Live', 'field' => 'live'];
            $sheets['adjudicators']['columns'][] = ['label' => 'Virtual', 'field' => 'virtual'];
        }
    } else {
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1424', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
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
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'excelGenerate');
    return ciniki_core_excelGenerate($ciniki, $args['tnid'], [
        'sheets' => $sheets,
        'download' => 'yes',
        'filename' => 'Adjudicators.xlsx'
        ]);
}
?>
