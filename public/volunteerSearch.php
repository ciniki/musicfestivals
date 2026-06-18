<?php
//
// Description
// -----------
// This method searchs for a Volunteers for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Volunteer for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
    // Get the list of volunteers
    //
    $strsql = "SELECT DISTINCT volunteers.id, "
        . "volunteers.festival_id, "
        . "volunteers.customer_id, "
        . "customers.display_name, "
        . "volunteers.shortname, "
        . "volunteers.status, "
        . "volunteers.status AS status_text, "
        . "volunteers.local_festival_id, "
        . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS name "
        . "FROM ciniki_musicfestival_volunteers AS volunteers "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "volunteers.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_phones AS phones ON ("
            . "customers.id = phones.customer_id "
            . "AND phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "volunteers.shortname LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR volunteers.shortname LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR phones.phone_number LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR phones.phone_number LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR emails.email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR emails.email LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'volunteers', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'customer_id', 'display_name', 'shortname', 'status', 'status_text', 'local_festival_id'),
            'maps'=>array('status_text'=>$maps['volunteer']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $volunteers = isset($rc['volunteers']) ? $rc['volunteers'] : array();

    $volunteer_ids = [];
    $customer_ids = [];
    foreach($volunteers as $v) {
        $volunteer_ids[] = $v['id'];
        if( $v['customer_id'] > 0 ) {
            $customer_ids[] = $v['customer_id'];
        }
    }

    //
    // Load Phones
    //
    if( isset($customer_ids) && count($customer_ids) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
        $strsql = "SELECT phones.customer_id, "
            . "phones.id, "
            . "phones.phone_label, "
            . "phones.phone_number "
            . "FROM ciniki_customer_phones AS phones "
            . "WHERE phones.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY phones.customer_id, phones.phone_label "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'phones', 'fname'=>'id', 'fields'=>array('id', 'phone_label', 'phone_number')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1607', 'msg'=>'Unable to load phones', 'err'=>$rc['err']));
        }
        $phones = isset($rc['customers']) ? $rc['customers'] : array();
    }

    //
    // Load Emails
    //
    if( isset($customer_ids) && count($customer_ids) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
        $strsql = "SELECT emails.customer_id, "
            . "emails.id, "
            . "emails.email "
            . "FROM ciniki_customer_emails AS emails "
            . "WHERE emails.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY emails.customer_id, emails.email "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'emails', 'fname'=>'id', 'fields'=>array('id', 'email')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1657', 'msg'=>'Unable to load emails', 'err'=>$rc['err']));
        }
        $emails = isset($rc['customers']) ? $rc['customers'] : array();
    }

    if( isset($volunteers) ) {
        //
        // Calculate hours
        //
        foreach($volunteers as $vid => $volunteer) {
            //
            // Check if phones exist and merge
            //
            $volunteers[$vid]['phones'] = '';
            $volunteers[$vid]['emails'] = '';
            if( isset($phones[$volunteer['customer_id']]['phones']) ) {
                foreach($phones[$volunteer['customer_id']]['phones'] as $phone) {
                    $volunteers[$vid]['phones'] .= ($volunteers[$vid]['phones'] != '' ? ', ' : '')
                        . (count($phones[$volunteer['customer_id']]['phones']) > 1 ? $phone['phone_label'] . ': ' : '')
                        . $phone['phone_number'];
                }
            }
            if( isset($emails[$volunteer['customer_id']]['emails']) ) {
                foreach($emails[$volunteer['customer_id']]['emails'] as $email) {
                    $volunteers[$vid]['emails'] .= ($volunteers[$vid]['emails'] != '' ? ', ' : '') . $email['email'];
                }
            }
        }
    }

    return array('stat'=>'ok', 'volunteers'=>$volunteers);
}
?>
