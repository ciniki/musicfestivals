<?php
//
// Description
// ===========
// This method will return all the information about an category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the category is attached to.
// category_id:          The ID of the category to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_categoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'classes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Classes'),
        'sections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.categoryGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Return default for new Category
    //
    if( $args['category_id'] == 0 ) {
        $seq = 1;
        if( $args['section_id'] && $args['section_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS max_sequence "
                . "FROM ciniki_musicfestival_categories "
                . "WHERE ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_musicfestival_categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'max');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['max']['max_sequence']) ) {
                $seq = $rc['max']['max_sequence'] + 1;
            }
        }
        $category = array('id'=>0,
            'festival_id'=>(isset($args['festival_id']) ? $args['festival_id'] : 0),
            'section_id'=>(isset($args['section_id']) ? $args['section_id'] : 0),
            'name'=>'',
            'permalink'=>'',
            'sequence'=>$seq,
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Category
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_categories.id, "
            . "ciniki_musicfestival_categories.festival_id, "
            . "ciniki_musicfestival_categories.section_id, "
            . "ciniki_musicfestival_categories.name, "
            . "ciniki_musicfestival_categories.permalink, "
            . "ciniki_musicfestival_categories.sequence, "
            . "ciniki_musicfestival_categories.primary_image_id, "
            . "ciniki_musicfestival_categories.synopsis, "
            . "ciniki_musicfestival_categories.description "
            . "FROM ciniki_musicfestival_categories "
            . "WHERE ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'section_id', 'name', 'permalink', 'sequence', 'primary_image_id', 'synopsis', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.34', 'msg'=>'Category not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['categories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.35', 'msg'=>'Unable to find Category'));
        }
        $category = $rc['categories'][0];

        //
        // Return the list of classes for this category if requested
        //
        if( isset($args['classes']) && $args['classes'] == 'yes' ) {
            $strsql = "SELECT ciniki_musicfestival_classes.id, "
                . "ciniki_musicfestival_classes.festival_id, "
                . "ciniki_musicfestival_classes.category_id, "
                . "ciniki_musicfestival_classes.code, "
                . "ciniki_musicfestival_classes.name, "
                . "ciniki_musicfestival_classes.permalink, "
                . "ciniki_musicfestival_classes.sequence, "
                . "ciniki_musicfestival_classes.flags, "
                . "ciniki_musicfestival_classes.earlybird_fee, "
                . "ciniki_musicfestival_classes.fee, "
                . "ciniki_musicfestival_classes.virtual_fee, "
                . "ciniki_musicfestival_classes.earlybird_plus_fee, "
                . "ciniki_musicfestival_classes.plus_fee "
                . "FROM ciniki_musicfestival_classes "
                . "WHERE ciniki_musicfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_musicfestival_classes.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
                . "ORDER BY sequence, code, name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 
                        'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['classes']) ) {
                $category['classes'] = $rc['classes'];
                foreach($category['classes'] as $iid => $class) {
                    $category['classes'][$iid]['earlybird_fee'] = number_format($class['earlybird_fee'], 2);
                    $category['classes'][$iid]['fee'] = number_format($class['fee'], 2);
                    $category['classes'][$iid]['virtual_fee'] = number_format($class['virtual_fee'], 2);
                    $nplists['classes'][] = $class['id'];
                }
            } else {
                $category['classes'] = array();
            }
        }
    }

    $rsp = array('stat'=>'ok', 'category'=>$category);

    //
    // Get the list of sections
    //
    if( isset($args['sections']) && $args['sections'] == 'yes' ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_musicfestival_sections "
            . "WHERE ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $category['festival_id']) . "' "
            . "ORDER BY sequence, name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.40', 'msg'=>'Sections not found', 'err'=>$rc['err']));
        }
        if( isset($rc['sections']) ) {
            $rsp['sections'] = $rc['sections'];
        } else {
            $rsp['sections'] = array();
        }
    }
    
    return $rsp;
}
?>
