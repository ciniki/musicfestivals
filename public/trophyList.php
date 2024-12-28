<?php
//
// Description
// -----------
// This method will return the list of Trophys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Trophy for.
//
// Returns
// -------
//
function ciniki_musicfestivals_trophyList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'typename'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.trophyList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of trophies
    //
    if( isset($args['class_id']) && $args['class_id'] > 0 ) {
        $strsql = "SELECT trophies.id, "
            . "trophies.name, "
            . "trophies.typename, "
            . "trophies.category, "
            . "trophies.donated_by, "
            . "trophies.first_presented, "
            . "trophies.amount, "
            . "trophies.criteria, "
            . "classes.class_id "
            . "FROM ciniki_musicfestival_trophies AS trophies "
            . "LEFT JOIN ciniki_musicfestival_trophy_classes AS classes ON ("
                . "trophies.id = classes.trophy_id "
                . "AND classes.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['typename']) && $args['typename'] != '' && $args['typename'] != 'All' ) {
            $strsql .= "AND trophies.typename = '" . ciniki_core_dbQuote($ciniki, $args['typename']) . "' ";
        }
        if( isset($args['category']) && $args['category'] != '' && $args['category'] != 'All' ) {
            $strsql .= "AND trophies.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
        }
        $strsql .= "HAVING ISNULL(classes.class_id) "
            . "ORDER BY typename, trophies.category, trophies.name "
            . "";
    } else {
        $strsql = "SELECT trophies.id, "
            . "trophies.name, "
            . "trophies.typename, "
            . "trophies.category, "
            . "trophies.donated_by, "
            . "trophies.first_presented, "
            . "trophies.amount, "
            . "trophies.criteria "
            . "FROM ciniki_musicfestival_trophies AS trophies "
            . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['typename']) && $args['typename'] != '' && $args['typename'] != 'All' ) {
            $strsql .= "AND trophies.typename = '" . ciniki_core_dbQuote($ciniki, $args['typename']) . "' ";
        }
        if( isset($args['category']) && $args['category'] != '' && $args['category'] != 'All' ) {
            $strsql .= "AND trophies.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
        }
        $strsql .= "ORDER BY typename, category, name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'trophies', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'typename', 'category', 'donated_by', 'first_presented', 'amount', 'criteria')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();
    $trophy_ids = array();
    foreach($trophies as $iid => $trophy) {
        $trophy_ids[] = $trophy['id'];
    }

    //
    // Get the list of types
    //
    $strsql = "SELECT DISTINCT trophies.typename "
        . "FROM ciniki_musicfestival_trophies AS trophies "
        . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY trophies.typename "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'types', 'fname'=>'typename', 'fields'=>array('name'=>'typename')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.902', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $types = isset($rc['types']) ? $rc['types'] : array();
    array_unshift($types, ['name'=>'All']);

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT trophies.category "
        . "FROM ciniki_musicfestival_trophies AS trophies "
        . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['typename']) && $args['typename'] != '' && $args['typename'] != 'All' ) {
        $strsql .= "AND trophies.typename = '" . ciniki_core_dbQuote($ciniki, $args['typename']) . "' ";
    }
    $strsql .= "ORDER BY trophies.category "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.903', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    array_unshift($categories, ['name'=>'All']);

    return array('stat'=>'ok', 'trophies'=>$trophies, 'trophy_types'=>$types, 'trophy_categories'=>$categories, 'nplist'=>$trophy_ids);
}
?>
