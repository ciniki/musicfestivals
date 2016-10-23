<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_objects($ciniki) {
    
    $objects = array();
    $objects['festival'] = array(
        'name'=>'Festival',
        'o_name'=>'festival',
        'o_container'=>'festivals',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestivals',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'start_date'=>array('name'=>'Start'),
            'end_date'=>array('name'=>'End'),
            'primary_image_id'=>array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'document_logo_id'=>array('name'=>'Document Header Logo', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'document_header_msg'=>array('name'=>'Document Header Message', 'default'=>''),
            'document_footer_msg'=>array('name'=>'Document Footer Message', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['adjudicator'] = array(
        'name'=>'Adjudicator',
        'o_name'=>'adjudicator',
        'o_container'=>'adjudicators',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_adjudicators',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['section'] = array(
        'name'=>'Section',
        'o_name'=>'section',
        'o_container'=>'sections',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_sections',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'primary_image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['category'] = array(
        'name'=>'Category',
        'o_name'=>'category',
        'o_container'=>'categories',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_categories',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'section_id'=>array('name'=>'Section', 'ref'=>'ciniki.musicfestivals.section'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'primary_image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['class'] = array(
        'name'=>'Class',
        'o_name'=>'class',
        'o_container'=>'classes',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_classes',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'category_id'=>array('name'=>'Category', 'ref'=>'ciniki.musicfestivals.category'),
            'code'=>array('name'=>'Code'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'fee'=>array('name'=>'Fee', 'type'=>'currency', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
