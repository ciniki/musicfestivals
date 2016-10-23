<?php
//
// Description
// ===========
// This method will return all the information about an class.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the class is attached to.
// class_id:          The ID of the class to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_classGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registrations'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.classGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Return default for new Class
    //
    if( $args['class_id'] == 0 ) {
        $seq = 1;
        if( $args['category_id'] && $args['category_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS max_sequence "
                . "FROM ciniki_musicfestival_classes "
                . "WHERE ciniki_musicfestival_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_musicfestival_classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'max');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['max']['max_sequence']) ) {
                $seq = $rc['max']['max_sequence'] + 1;
            }
        }
        $class = array('id'=>0,
            'festival_id'=>(isset($args['festival_id']) ? $args['festival_id'] : 0),
            'category_id'=>(isset($args['category_id']) ? $args['category_id'] : 0),
            'code'=>'',
            'name'=>'',
            'permalink'=>'',
            'sequence'=>$seq,
            'flags'=>'0',
            'fee'=>'',
        );
    }

    //
    // Get the details for an existing Class
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_classes.id, "
            . "ciniki_musicfestival_classes.festival_id, "
            . "ciniki_musicfestival_classes.category_id, "
            . "ciniki_musicfestival_classes.code, "
            . "ciniki_musicfestival_classes.name, "
            . "ciniki_musicfestival_classes.permalink, "
            . "ciniki_musicfestival_classes.sequence, "
            . "ciniki_musicfestival_classes.flags, "
            . "ciniki_musicfestival_classes.fee "
            . "FROM ciniki_musicfestival_classes "
            . "WHERE ciniki_musicfestival_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_musicfestival_classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.45', 'msg'=>'Class not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['classes'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.46', 'msg'=>'Unable to find Class'));
        }
        $class = $rc['classes'][0];
        $class['fee'] = numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency);
    }


    $rsp = array('stat'=>'ok', 'class'=>$class);

    //
    // Get the list of categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT ciniki_musicfestival_categories.id, "
            . "CONCAT_WS(' - ', ciniki_musicfestival_sections.name, ciniki_musicfestival_categories.name) AS name "
            . "FROM ciniki_musicfestival_sections, ciniki_musicfestival_categories "
            . "WHERE ciniki_musicfestival_sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND ciniki_musicfestival_sections.id = ciniki_musicfestival_categories.section_id "
            . "AND ciniki_musicfestival_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_musicfestival_sections.sequence, ciniki_musicfestival_sections.name, "
                . "ciniki_musicfestival_categories.sequence, ciniki_musicfestival_categories.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.40', 'msg'=>'Categories not found', 'err'=>$rc['err']));
        }
        if( isset($rc['categories']) ) {
            $rsp['categories'] = $rc['categories'];
        } else {
            $rsp['categories'] = array();
        }
    }

    return $rsp;
}
?>
