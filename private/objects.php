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
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('name'=>'Flags', 'default'=>'0'),
            'earlybird_date'=>array('name'=>'Earlybird End Date', 'default'=>''),
            'primary_image_id'=>array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'document_logo_id'=>array('name'=>'Document Header Logo', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'document_header_msg'=>array('name'=>'Document Header Message', 'default'=>''),
            'document_footer_msg'=>array('name'=>'Document Footer Message', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['customer'] = array(
        'name' => 'Customer',
        'sync' => 'yes',
        'o_name' => 'customer',
        'o_container' => 'customers',
        'table' => 'ciniki_musicfestival_customers',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'ctype' => array('name'=>'Type', 'default'=>'0'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
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
            'flags'=>array('name'=>'Options', 'default'=>'0'),
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
            'earlybird_fee'=>array('name'=>'Earlybird Fee', 'type'=>'currency', 'default'=>'0'),
            'fee'=>array('name'=>'Fee', 'type'=>'currency', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['file'] = array(
        'name'=>'File',
        'o_name'=>'file',
        'o_container'=>'files',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_files',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'extension'=>array('name'=>'Extension'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'webflags'=>array('name'=>'Options', 'default'=>'0'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'org_filename'=>array('name'=>'Original Filename', 'default'=>''),
            'publish_date'=>array('name'=>'Publish Date', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['competitor'] = array(
        'name'=>'Competitor',
        'o_name'=>'competitor',
        'o_container'=>'competitors',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_competitors',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'billing_customer_id'=>array('name'=>'Billing Customer', 'ref'=>'ciniki.customers.customer', 'default'=>0),
            'name'=>array('name'=>'Name'),
            'public_name'=>array('name'=>'Public Name', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'parent'=>array('name'=>'Parent'),
            'address'=>array('name'=>'Address', 'default'=>''),
            'city'=>array('name'=>'City', 'default'=>''),
            'province'=>array('name'=>'Province', 'default'=>''),
            'postal'=>array('name'=>'Postal Code', 'default'=>''),
            'phone_home'=>array('name'=>'Home Phone', 'default'=>''),
            'phone_cell'=>array('name'=>'Cell Phone', 'default'=>''),
            'email'=>array('name'=>'Email', 'default'=>''),
            'age'=>array('name'=>'Age', 'default'=>''),
            'study_level'=>array('name'=>'Study/Level', 'default'=>''),
            'instrument'=>array('name'=>'Instrument', 'default'=>''),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['registration'] = array(
        'name'=>'Registration',
        'o_name'=>'registration',
        'o_container'=>'registrations',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_registrations',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'teacher_customer_id'=>array('name'=>'Teacher', 'ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'billing_customer_id'=>array('name'=>'Billing', 'ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'rtype'=>array('name'=>'Type'),
            'status'=>array('name'=>'Status'),
            'invoice_id'=>array('name'=>'Status', 'default'=>'0'),
            'display_name'=>array('name'=>'Name', 'default'=>''),
            'public_name'=>array('name'=>'Name', 'default'=>''),
            'competitor1_id'=>array('name'=>'Competitor 1', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor2_id'=>array('name'=>'Competitor 2', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor3_id'=>array('name'=>'Competitor 3', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor4_id'=>array('name'=>'Competitor 4', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor5_id'=>array('name'=>'Competitor 5', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'class_id'=>array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class'),
            'timeslot_id'=>array('name'=>'Timeslot', 'ref'=>'ciniki.musicfestivals.scheduletimeslot', 'default'=>'0'),
            'title'=>array('name'=>'Title', 'default'=>''),
            'perf_time'=>array('name'=>'Performance Time', 'default'=>''),
            'fee'=>array('name'=>'Fee', 'type'=>'currency', 'default'=>'0'),
            'payment_type'=>array('name'=>'Payment Type', 'default'=>'0'),
            'videolink'=>array('name'=>'Video Link', 'default'=>''),
            'music_orgfilename'=>array('name'=>'Music Original Filename', 'default'=>''),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['comment'] = array(
        'name'=>'Adjudication Comment',
        'o_name'=>'comment',
        'o_container'=>'comments',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_comments',
        'fields'=>array(
            'registration_id'=>array('name'=>'Registration', 'ref'=>'ciniki.musicfestivals.registration'),
            'adjudicator_id'=>array('name'=>'Adjudicator', 'ref'=>'ciniki.musicfestivals.adjudicator'),
            'comments'=>array('name'=>'Comments', 'default'=>''),
            'grade'=>array('name'=>'Grade', 'default'=>''),
            'score'=>array('name'=>'Score', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['schedulesection'] = array(
        'name'=>'Schedule Section',
        'o_name'=>'schedulesection',
        'o_container'=>'schedulesections',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_schedule_sections',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'name'=>array('name'=>'Name'),
            'adjudicator1_id'=>array('name'=>'First Adjudicator', 'id'=>'ciniki.musicfestivals.adjudicator', 'default'=>'0'),
            'adjudicator2_id'=>array('name'=>'Second Adjudicator', 'id'=>'ciniki.musicfestivals.adjudicator', 'default'=>'0'),
            'adjudicator3_id'=>array('name'=>'Third Adjudicator', 'id'=>'ciniki.musicfestivals.adjudicator', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['scheduledivision'] = array(
        'name'=>'Schedule Division',
        'o_name'=>'scheduledivision',
        'o_container'=>'scheduledivisions',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_schedule_divisions',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'ssection_id'=>array('name'=>'Section', 'ref'=>'ciniki.musicfestivals.schedulesection'),
            'name'=>array('name'=>'Name'),
            'division_date'=>array('name'=>'Date'),
            'address'=>array('name'=>'Address', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['scheduletimeslot'] = array(
        'name'=>'Schedule Time Slot',
        'o_name'=>'scheduletimeslot',
        'o_container'=>'scheduletimeslot',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_schedule_timeslots',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'sdivision_id'=>array('name'=>'Division', 'ref'=>'ciniki.musicfestivals.scheduledivision'),
            'slot_time'=>array('name'=>'Time'),
            'class1_id'=>array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class'),
            'class2_id'=>array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class', 'default'=>'0'),
            'class3_id'=>array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class', 'default'=>'0'),
            'name'=>array('name'=>'Name'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['setting'] = array(
        'name' => 'Setting',
        'sync' => 'yes',
        'o_name' => 'setting',
        'o_container' => 'settings',
        'table' => 'ciniki_musicfestival_settings',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'detail_key' => array('name'=>'Key'),
            'detail_value' => array('name'=>'Value', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
