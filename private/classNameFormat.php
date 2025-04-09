<?php
//
// Description
// -----------
// This function will merge the title, composer and movements into 1 line
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_classNameFormat(&$ciniki, $tnid, $class) {

    $name = $class['name'];
    if( $class['format'] == 'code-section-category-class' ) {
        $name = "{$class['code']} - {$class['section']} - {$class['category']} - {$class['name']}";
    } elseif( $class['format'] == 'section-category-class' ) {
        $name = "{$class['section']} - {$class['category']} - {$class['name']}";
    } elseif( $class['format'] == 'code-category-class' ) {
        $name = "{$class['code']} - {$class['category']} - {$class['name']}";
    } elseif( $class['format'] == 'category-class' ) {
        $name = "{$class['category']} - {$class['name']}";
    } elseif( $class['format'] == 'code-class' ) {
        $name = "{$class['code']} - {$class['name']}";
    }

    return array('stat'=>'ok', 'name'=>$name);
}
?>
