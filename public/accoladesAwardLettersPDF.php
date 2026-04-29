<?php
//
// Description
// ===========
// This method will send the invites to provincials to approved entries
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the adjudicator recommendation is attached to.
// recommendation_id:          The ID of the adjudicator recommendation to get the details for.
// // Returns // -------
//
function ciniki_musicfestivals_accoladesAwardLettersPDF(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'accolade_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accolade'),
        'winner_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Winner'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladesAwardedSend');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

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
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1540', 'msg'=>'No festival specified'));
    }
    $festival = $rc['festival'];

    //
    // Get the list of winners
    //
    $strsql = "SELECT winners.id, "
        . "winners.flags, "
        . "winners.registration_id, "
        . "winners.awarded_amount, "
        . "winners.discipline, "
        . "accolades.id AS accolade_id, "
        . "accolades.name AS accolade_name, "
        . "accolades.donor_thankyou_info, "
        . "categories.awarded_pdf_header, "
        . "categories.awarded_pdf_content, "
        . "categories.awarded_pdf_footer, "
        . "registrations.private_name, "
        . "registrations.teacher_customer_id, "
        . "registrations.teacher2_customer_id, "
        . "registrations.billing_customer_id, "
        . "registrations.parent_customer_id, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
        . "registrations.competitor5_id "
        . "FROM ciniki_musicfestival_accolade_winners AS winners "
        . "INNER JOIN ciniki_musicfestival_accolades AS accolades ON ("
            . "winners.accolade_id = accolades.id "
            . "AND accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
            . "accolades.subcategory_id = subcategories.id "
            . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_accolade_categories AS categories ON ("
            . "subcategories.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "winners.registration_id = registrations.id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND winners.year = '" . ciniki_core_dbQuote($ciniki, $festival['year']) . "' "
        . "AND (winners.flags&0x01) = 0 " // unsent
        . "AND winners.registration_id > 0 ";
    if( isset($args['winner_id']) && $args['winner_id'] != '' && $args['winner_id'] > 0 ) {
        $strsql .= "AND winners.id = '" . ciniki_core_dbQuote($ciniki, $args['winner_id']) . "' ";
    }
    if( isset($args['accolade_id']) && $args['accolade_id'] != '' && $args['accolade_id'] > 0 ) {
        $strsql .= "AND accolades.id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_id']) . "' ";
    }
    if( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 ) {
        $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
    }
    if( isset($args['subcategory_id']) && $args['subcategory_id'] != '' && $args['subcategory_id'] > 0 ) {
        $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' ";
    }
    $strsql .= "ORDER BY registrations.private_name ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'winners', 'fname'=>'id', 
            'fields'=>array('id', 'flags', 'registration_id', 'awarded_amount', 'discipline', 
                'accolade_name', 'donor_thankyou_info', 
                'awarded_pdf_header', 'awarded_pdf_content', 'awarded_pdf_footer',
                'private_name', 'billing_customer_id', 'parent_customer_id', 
                'teacher_customer_id', 'teacher2_customer_id',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1541', 'msg'=>'Unable to load winners', 'err'=>$rc['err']));
    }
    $winners = isset($rc['winners']) ? $rc['winners'] : array();

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'accoladesAwardLettersPDF');
    return ciniki_musicfestivals_templates_accoladesAwardLettersPDF($ciniki, $args['tnid'], [
        'festival' => $festival,
        'winners' => $winners,
        'download' => 'yes',
        'filename' => 'Accolade Letter.pdf',
        ]);
}
?>
