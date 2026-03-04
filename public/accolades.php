<?php
//
// Description
// ===========
// This method will return the provincial recommendations info for a festival
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
function ciniki_musicfestivals_accolades($ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'subcategories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategories'),
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'accolades'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accolades'),
        'accolade_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accolade'),
        'recipients'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recipients'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteers');
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
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1404', 'msg'=>'No festival specified'));
    }
    $festival = $rc['festival'];

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    $date_format = 'D, M j, Y';

    $rsp = array('stat'=>'ok', 'nplists'=>[]);
    //
    // Load the load festival and provincials festival info
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        //
        // Get the list of categories and subcategories
        //
        $strsql = "SELECT id, sequence, name "
            . "FROM ciniki_musicfestival_accolade_categories AS categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'sequence', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1179', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : [];
        array_unshift($rsp['categories'], ['id'=>0, 'sequence'=>0, 'name'=>'All']);

        $rsp['subcategories'] = [];
        if( isset($args['category_id']) && $args['category_id'] > 0 ) {
            $strsql = "SELECT id, sequence, name "
                . "FROM ciniki_musicfestival_accolade_subcategories AS subcategories "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
                . "ORDER BY sequence, name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'subcategories', 'fname'=>'id', 'fields'=>array('id', 'sequence', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1180', 'msg'=>'Unable to load subcategories', 'err'=>$rc['err']));
            }
            $rsp['subcategories'] = isset($rc['subcategories']) ? $rc['subcategories'] : array();
            array_unshift($rsp['subcategories'], ['id'=>0, 'sequence'=>0, 'name'=>'All']);
        }
    }

    //
    // Get the list of accolades
    //
    if( isset($args['accolades']) && $args['accolades'] == 'yes' ) {
        $strsql = "SELECT accolades.id, "
            . "accolades.subcategory_id, "
            . "accolades.name, "
            . "categories.name AS category_name, "
            . "subcategories.name AS subcategory_name, "
            . "accolades.donated_by, "
            . "accolades.first_presented, "
            . "accolades.amount, "
            . "accolades.criteria "
            . "FROM ciniki_musicfestival_accolades AS accolades "
            . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
                . "accolades.subcategory_id = subcategories.id "
                . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_accolade_categories AS categories ON ("
                . "subcategories.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 ) {
            $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
        if( isset($args['subcategory_id']) && $args['subcategory_id'] != '' && $args['subcategory_id'] > 0 ) {
            $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' ";
        }
        $strsql .= "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accolades', 'fname'=>'id', 
                'fields'=>array('id', 'subcategory_id', 'category_name', 'subcategory_name', 'name', 'donated_by', 'first_presented', 'amount', 
                    'criteria'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.623', 'msg'=>'Unable to load accolades', 'err'=>$rc['err']));
        }
        $rsp['accolades'] = isset($rc['accolades']) ? $rc['accolades'] : array();
        $rsp['nplists']['accolades'] = [];
        foreach($rsp['accolades'] as $aid => $accolade) {
            $rsp['nplists']['accolades'][] = $accolade['id'];
        }
    }

    //
    // Get the list of recipients
    //
    if( isset($args['recipients']) && $args['recipients'] == 'yes' ) {
        $strsql = "SELECT winners.id, "
            . "winners.flags, "
            . "IFNULL(registrations.display_name, winners.name) AS recipient_name, "
            . "winners.awarded_amount, "
            . "accolades.id AS accolade_id, "
            . "accolades.name, "
            . "accolades.subcategory_id, "
            . "categories.name AS category_name, "
            . "subcategories.name AS subcategory_name "
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
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "winners.registration_id = registrations.id "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['accolade_id']) && $args['accolade_id'] != '' && $args['accolade_id'] > 0 ) {
            $strsql .= "AND accolades.id = '" . ciniki_core_dbQuote($ciniki, $args['accolade_id']) . "' ";
        }
        if( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 ) {
            $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
        if( isset($args['subcategory_id']) && $args['subcategory_id'] != '' && $args['subcategory_id'] > 0 ) {
            $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' ";
        }
        if( isset($args['festival_id']) && $args['festival_id'] > 0 ) {
            $strsql .= "AND winners.year = '" . ciniki_core_dbQuote($ciniki, $festival['year']) . "' ";
        }
        $strsql .= "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, recipient_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'recipients', 'fname'=>'id', 
                'fields'=>array('id', 'flags', 'recipient_name', 'awarded_amount', 'accolade_id', 'name', 'subcategory_id',
                    'category_name', 'subcategory_name',),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1128', 'msg'=>'Unable to load recipients', 'err'=>$rc['err']));
        }
        $rsp['recipients'] = isset($rc['recipients']) ? $rc['recipients'] : array();
        $nplists['accolades'] = [];
        if( count($rsp['recipients']) > 0 ) {
            $rsp['totals']['recipients'] = ['awarded_amount' => 0];
            foreach($rsp['recipients'] as $rid => $recipient) {
                $rsp['nplists']['accolades'][] = $recipient['id'];
                $rsp['totals']['recipients']['awarded_amount'] += $recipient['awarded_amount'];
            }
        }
    }

    return $rsp;
}
?>
