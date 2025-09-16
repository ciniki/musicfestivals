<?php
//
// Description
// -----------
// 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_recommendationClassesLoad(&$ciniki, $tnid, $section) {

    //
    // Get the list of classes
    //
    $strsql = "SELECT classes.id, "
        . "classes.uuid, "
        . "classes.festival_id, "
        . "classes.category_id, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.primary_image_id AS category_image_id, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.code, "
        . "classes.name, "
        . "classes.permalink, "
        . "classes.sequence, "
        . "classes.flags, "
        . "earlybird_fee, "
        . "fee, "
        . "virtual_fee, "
        . "earlybird_plus_fee, "
        . "plus_fee "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'permalink', 
                'sequence', 'flags'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.596', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    //
    // Auto remove section name from class names
    //
    foreach($classes as $cid => $class) {
        $classes[$cid]['name'] = str_replace($section['name'] . ' - ', '', $classes[$cid]['name']);
        if( preg_match("/^([^-]+) - /", $section['name'], $m) ) {
            if( $m[1] != '' ) {
                $classes[$cid]['name'] = str_replace($m[1] . ' - ', '', $classes[$cid]['name']);
            }
        }
    }
    
    return array('stat'=>'ok', 'classes'=>$classes);
}
?>
