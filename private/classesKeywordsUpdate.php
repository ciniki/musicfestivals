<?php
//
// Description
// -----------
// Update the keywords for  section or category of classes
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_classesKeywordsUpdate(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classKeywordsMake');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "classes.id AS class_id, "
        . "classes.code, "
        . "classes.name, "
        . "classes.synopsis, "
        . "classes.keywords "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    } elseif( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
    } 
    if( isset($args['festival_id']) && $args['festival_id'] > 0 ) {
        $strsql .= "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    }
    $strsql .= "ORDER BY sections.id, categories.id, classes.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('name'=>'section_name')),
        array('container'=>'categories', 'fname'=>'category_id', 'fields'=>array('name'=>'category_name')),
        array('container'=>'classes', 'fname'=>'class_id', 'fields'=>array('id'=>'class_id', 'code', 'name', 'synopsis', 'keywords')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.823', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Update the classes
    //
    foreach($sections as $section) {
        if( isset($section['categories']) ) {
            foreach($section['categories'] as $category) {
                if( isset($category['classes']) ) {
                    foreach($category['classes'] as $class) {
                        $rc = ciniki_musicfestivals_classKeywordsMake($ciniki, $tnid, [
                            'section' => $section,
                            'category' => $category,
                            'class' => $class,
                            ]);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        
                        if( $rc['keywords'] != $class['keywords'] ) {
                            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.class', $class['id'], [
                                'keywords' => $rc['keywords'],
                                ], 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.824', 'msg'=>'Unable to update the class', 'err'=>$rc['err']));
                            }
                        }
                    }
                }
                
            }
        }
    }

    return array('stat'=>'ok');
}
?>
