<?php
//
// Description
// -----------
// This function will update open orders when a customer status changes
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_musicfestivals_hooks_customerNameUpdate($ciniki, $business_id, $args) {
    //
    // Get the time information for business and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Update open orders
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 && isset($args['display_name']) && $args['display_name'] != '' ) {
        //
        // Update the adjudicators with the name
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_musicfestival_adjudicators "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update any invoices where this customer is the student and not customer
        //
        if( isset($rc['rows']) ) {
            $adjudicators = $rc['rows'];

            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            foreach($adjudicators as $adjudicator) {
                if( $adjudicator['name'] != $args['display_name'] ) {
                    $permalink = ciniki_core_makePermalink($ciniki, $args['display_name']);
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.musicfestivals.adjudicator', $adjudicator['id'], array(
                        'name'=>$args['display_name'],
                        'permalink'=>$args['permalink'],
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.11', 'msg'=>'Unable to update adjudicator', 'err'=>$rc['err']));
                    }
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
