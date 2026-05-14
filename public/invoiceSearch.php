<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the festival is attached to.
// festival_id:          The ID of the festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_invoiceSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.invoiceSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sapos_maps = $rc['maps'];

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    // 
    // Get the invoices
    //
    $strsql = "SELECT invoices.id, "
        . "invoices.invoice_number, "
        . "invoices.status, "
        . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
        . "invoices.total_amount, "
        . "invoices.balance_amount, "
        . "registrations.display_name AS competitor_names, "
        . "customers.display_name AS customer_name "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
            . "registrations.invoice_id = invoices.id "
            . "AND invoices.invoice_type <> 20 ";
//    if( isset($args['invoice_typestatus']) && $args['invoice_typestatus'] != '' && $args['invoice_typestatus'] > 0 ) {
//        list($itype, $istatus) = explode('.', $args['invoice_typestatus']);
//        $strsql .= "AND invoices.invoice_type = '" . ciniki_core_dbQuote($ciniki, $itype) . "' ";
//        $strsql .= "AND invoices.status = '" . ciniki_core_dbQuote($ciniki, $istatus) . "' ";
//    }
    $strsql .= "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "invoices.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( is_numeric($args['start_needle']) ) {
        $strsql .= "AND ("
            . "invoices.invoice_number like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") ";
    } else {
        $strsql .= "AND ("
            . "registrations.private_name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR registrations.private_name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.display_name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.display_name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") ";
    }
    $strsql .= "ORDER BY invoice_number "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array('id', 'invoice_number', 'status', 'status_text', 'total_amount', 'balance_amount',
                'customer_name', 'competitor_names',
                ),
            'dlists'=>array('competitor_names'=>', '),
            'maps'=>array('status_text'=>$sapos_maps['invoice']['typestatus']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.564', 'msg'=>'Unable to load invoices', 'err'=>$rc['err']));
    }
    $invoices = isset($rc['invoices']) ? $rc['invoices'] : array();

    return array('stat'=>'ok', 'invoices'=>$invoices);
}
?>
