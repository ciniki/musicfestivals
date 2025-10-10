<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_sectionCategoriesUpdate($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'find_replace_fields'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'name'=>'Fields'),
        'find'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Find String'),
        'replace'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Replace String'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionClasses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Get the list of classes in the section
    //
    $strsql = "SELECT categories.id, "
        . "categories.name, "
        . "categories.description "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    if( !isset($args['section_id']) || $args['section_id'] == 0 ) {
        // Apply to all classes in festival when section_id is zero
        $strsql .= "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    } else {
        $strsql .= "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    }
    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
    }
    $strsql .= "GROUP BY categories.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1203', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    foreach($categories as $category) {
        $update_args = array();

        if( isset($args['find_replace_fields']) && is_array($args['find_replace_fields']) && count($args['find_replace_fields']) > 0 ) {
            foreach($args['find_replace_fields'] as $field) {
                if( $field == 'description' ) {
                    $description = str_replace($args['find'], $args['replace'], $category['description']);
                    if( $description != $category['description'] ) {
                        $update_args['description'] = $description;
                    }
                }
            }
        }
        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.category', $category['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1204', 'msg'=>'Unable to update the category', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
