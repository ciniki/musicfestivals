<?php
//
// Description
// ===========
// This method will return all the information about an list entry.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the list entry is attached to.
// listentry_id:          The ID of the list entry to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_listEntryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'listentry_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'List Entry'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List Section'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.listEntryGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new List Entry
    //
    if( $args['listentry_id'] == 0 ) {
        $listentry = array(
            'id' => 0,
            'section_id' => '',
            'sequence' => $seq,
            'award' => '',
            'amount' => '',
            'donor' => '',
            'winner' => '',
        );
        if( isset($args['section_id']) && $args['section_id'] > 0 ) {
            //
            // Get the next sequence number
            //
            $strsql = "SELECT MAX(sequence) AS num "
                . "FROM ciniki_musicfestival_list_entries "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals','item');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $seq = (isset($rc['item']['num']) ? $rc['item']['num'] + 1 : 1);
           
            //
            // Get the last entry details for this section
            //
            $strsql = "SELECT award, donor "
                . "FROM ciniki_musicfestival_list_entries "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "ORDER BY date_added DESC "
                . "LIMIT 1 "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals','item');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $listentry['award'] = (isset($rc['item']['award']) ? $rc['item']['award'] : '');
            $listentry['donor'] = (isset($rc['item']['donor']) ? $rc['item']['donor'] : '');
        }
    }

    //
    // Get the details for an existing List Entry
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_list_entries.id, "
            . "ciniki_musicfestival_list_entries.section_id, "
            . "ciniki_musicfestival_list_entries.sequence, "
            . "ciniki_musicfestival_list_entries.award, "
            . "ciniki_musicfestival_list_entries.amount, "
            . "ciniki_musicfestival_list_entries.donor, "
            . "ciniki_musicfestival_list_entries.winner "
            . "FROM ciniki_musicfestival_list_entries "
            . "WHERE ciniki_musicfestival_list_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_list_entries.id = '" . ciniki_core_dbQuote($ciniki, $args['listentry_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'listentries', 'fname'=>'id', 
                'fields'=>array('section_id', 'sequence', 'award', 'amount', 'donor', 'winner'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.276', 'msg'=>'List Entry not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['listentries'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.277', 'msg'=>'Unable to find List Entry'));
        }
        $listentry = $rc['listentries'][0];
    }

    return array('stat'=>'ok', 'listentry'=>$listentry);
}
?>
