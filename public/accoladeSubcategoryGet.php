<?php
//
// Description
// ===========
// This method will return all the information about an accolade subcategory.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the accolade subcategory is attached to.
// subcategory_id:          The ID of the accolade subcategory to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_accoladeSubcategoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'subcategory_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Accolade Subcategory'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeSubcategoryGet');
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
    // Return default for new Accolade Subcategory
    //
    if( $args['subcategory_id'] == 0 ) {
        $accoladesubcategory = array('id'=>0,
            'category_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'flags'=>0x01,
            'sequence'=>'1',
            'image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Accolade Subcategory
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_accolade_subcategories.id, "
            . "ciniki_musicfestival_accolade_subcategories.category_id, "
            . "ciniki_musicfestival_accolade_subcategories.name, "
            . "ciniki_musicfestival_accolade_subcategories.permalink, "
            . "ciniki_musicfestival_accolade_subcategories.flags, "
            . "ciniki_musicfestival_accolade_subcategories.sequence, "
            . "ciniki_musicfestival_accolade_subcategories.image_id, "
            . "ciniki_musicfestival_accolade_subcategories.synopsis, "
            . "ciniki_musicfestival_accolade_subcategories.description "
            . "FROM ciniki_musicfestival_accolade_subcategories "
            . "WHERE ciniki_musicfestival_accolade_subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_accolade_subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accoladesubcategories', 'fname'=>'id', 
                'fields'=>array('category_id', 'name', 'permalink', 'flags', 'sequence', 'image_id', 'synopsis', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1114', 'msg'=>'Accolade Subcategory not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['accoladesubcategories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1115', 'msg'=>'Unable to find Accolade Subcategory'));
        }
        $accoladesubcategory = $rc['accoladesubcategories'][0];
    }

    $rsp = array('stat'=>'ok', 'accoladesubcategory'=>$accoladesubcategory);

    //
    // Get the list of categories
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_musicfestival_accolade_categories AS categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY categories.sequence, categories.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1117', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();

    return $rsp;
}
?>
