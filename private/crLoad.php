<?php
//
// Description
// -----------
// This function will load all the details for a change request
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_crLoad(&$ciniki, $tnid, $args) {

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
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    
    //
    // Load the CR
    //
    $strsql = "SELECT crs.id, "
        . "crs.cr_number, "
        . "crs.status, "
        . "crs.status AS status_text, "
        . "crs.customer_id, "
        . "IFNULL(customers.display_name, '') AS customer_name, "
        . "crs.dt_submitted, "
        . "crs.dt_submitted AS dt_submitted_text, "
        . "crs.dt_completed, "
        . "crs.dt_completed AS dt_completed_text, "
        . "crs.content "
        . "FROM ciniki_musicfestival_crs AS crs "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "crs.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE crs.id = '" . ciniki_core_dbQuote($ciniki, $args['cr_id']) . "' "
        . "AND crs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY crs.dt_submitted DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'crs', 'fname'=>'id', 
            'fields'=>array(
                'id', 'cr_number', 'status', 'status_text', 'customer_id', 'customer_name',
                'dt_submitted', 'dt_submitted_text', 
                'dt_completed', 'dt_completed_text', 'content',
                ),
            'maps'=>array('status_text' => $maps['cr']['status']),
            'utctotz'=>array(
                'dt_submitted_text' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'dt_completed_text' => array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1086', 'msg'=>'Unable to load crs', 'err'=>$rc['err']));
    }
    if( !isset($rc['crs'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1087', 'msg'=>'Unable to load crs', 'err'=>$rc['err']));
    }
    $cr = $rc['crs'][0];

    if( isset($args['details']) && $args['details'] == 'yes' ) {
        $cr['details'] = [
            ['label' => 'ID #', 'value'=>sprintf("%04d", $cr['cr_number'])],
            ['label' => 'Status', 'value'=>$cr['status_text']],
            ['label' => 'From', 'value'=>$cr['customer_name']],
            ['label' => 'Submitted', 'value'=>$cr['dt_submitted_text']],
            ];
        if( $cr['status'] == 70 ) {
            $cr['details'][] = ['label' => 'Completed', 'value'=>$cr['dt_completed_text']];
        }
        $cr['details'][] = ['label' => 'Request', 'value'=>$cr['content']];
    }

    //
    // Load the invoices
    //
    if( (isset($args['invoices']) && $args['invoices'] == 'yes')
        || (isset($args['emails']) && $args['emails'] == 'yes')
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'objectItemList');
        $rc = ciniki_sapos_hooks_objectItemList($ciniki, $tnid, [
            'object' => 'ciniki.musicfestivals.cr',
            'object_id' => $cr['id'],
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1089', 'msg'=>'Unable to load invoices', 'err'=>$rc['err']));
        }
        $cr['invoice_items'] = isset($rc['items']) ? $rc['items'] : array();
    }

    // 
    // Load the Emails
    //
    if( isset($args['emails']) && $args['emails'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
        $rc = ciniki_mail_hooks_objectMessages($ciniki, $tnid, [
            'object' => 'ciniki.musicfestivals.cr',
            'object_id' => $cr['id'],
            'xml' => 'no',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1088', 'msg'=>'Unable to load emails', 'err'=>$rc['err']));
        }
        $cr['emails'] = isset($rc['messages']) ? $rc['messages'] : array();
        
        //
        // Load any invoice emails
        //
        $invoice_ids = [];
        foreach($cr['invoice_items'] as $item) {
            if( $item['invoice_id'] > 0 && !in_array($item['invoice_id'], $invoice_ids) ) {
                $invoice_ids[] = $item['invoice_id'];
            }
        }
        if( count($invoice_ids) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
            $rc = ciniki_mail_hooks_objectMessages($ciniki, $tnid, [
                'object' => 'ciniki.sapos.invoice',
                'object_ids' => $invoice_ids,
                'xml' => 'no',
                ]);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1120', 'msg'=>'Unable to load emails', 'err'=>$rc['err']));
            }
            if( isset($rc['messages']) && count($rc['messages']) > 0 ) {
                $cr['emails'] = array_merge($cr['emails'], $rc['messages']);
            }
        }

        usort($cr['emails'], function($a, $b) {
            if( $a['ts_date_sent'] == $b['ts_date_sent'] ) {
                return 0;
            }
            return $a['ts_date_sent'] < $b['ts_date_sent'] ? 1 : -1;
            });
    }

    return array('stat'=>'ok', 'cr'=>$cr);
}
?>
