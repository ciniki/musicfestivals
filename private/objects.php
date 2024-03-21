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
            'live_date'=>array('name'=>'Live Deadline', 'default'=>''),
            'virtual_date'=>array('name'=>'Virtual Deadline', 'default'=>''),
            'edit_end_dt'=>array('name'=>'Edit Titles Deadline', 'default'=>''),
            'upload_end_dt'=>array('name'=>'Upload Deadline', 'default'=>''),
            'primary_image_id'=>array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'document_logo_id'=>array('name'=>'Document Header Logo', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'document_header_msg'=>array('name'=>'Document Header Message', 'default'=>''),
            'document_footer_msg'=>array('name'=>'Document Footer Message', 'default'=>''),
            'comments_grade_label'=>array('name'=>'Comments Grade Label', 'default'=>''),
            'comments_footer_msg'=>array('name'=>'Comments PDF Footer Message', 'default'=>''),
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
            'flags'=>array('name'=>'Options', 'default'=>0),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>0),
            'discipline'=>array('name'=>'Discipline', 'default'=>''),
            'description'=>array('name'=>'Bio', 'default'=>''),
            'sig_image_id'=>array('name'=>'Signature Image', 'ref'=>'ciniki.images.image', 'default'=>0),
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
            'live_description'=>array('name'=>'Live Description', 'default'=>''),
            'virtual_description'=>array('name'=>'Virtual Description', 'default'=>''),
            'recommendations_description'=>array('name'=>'Recommendations Description', 'default'=>''),
            'live_end_dt'=>array('name'=>'Live Deadline', 'default'=>''),
            'virtual_end_dt'=>array('name'=>'Virtual Deadline', 'default'=>''),
            'edit_end_dt'=>array('name'=>'Edit Titles Deadline', 'default'=>''),
            'upload_end_dt'=>array('name'=>'Upload Deadline', 'default'=>''),
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
            'groupname'=>array('name'=>'Group', 'default'=>''),
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
            'virtual_fee'=>array('name'=>'Virtual Fee', 'type'=>'currency', 'default'=>'0'),
            'earlybird_plus_fee'=>array('name'=>'Earlybird Plus Fee', 'type'=>'currency', 'default'=>'0'),
            'plus_fee'=>array('name'=>'Plus Fee', 'type'=>'currency', 'default'=>'0'),
            'min_titles'=>array('name'=>'Minimum Titles', 'default'=>'1'),
            'max_titles'=>array('name'=>'Maximum Titles', 'default'=>'1'),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['classtag'] = array(
        'name'=>'Class Tag',
        'o_name'=>'tag',
        'o_container'=>'tags',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_class_tags',
        'fields'=>array(
            'class_id'=>array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class'),
            'tag_type'=>array('name'=>'Tag Type'),
            'tag_name'=>array('name'=>'Tag Name'),
            'tag_sort_name'=>array('name'=>'Tag Sort Name'),
            'permalink'=>array('name'=>'Permalink'),
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
            'ctype'=>array('name'=>'Competitor Type', 'default'=>'10'),
            'first'=>array('name'=>'First Name', 'default'=>''),
            'last'=>array('name'=>'Last Name', 'default'=>''),
            'name'=>array('name'=>'Name'),
            'public_name'=>array('name'=>'Public Name', 'default'=>''),
            'pronoun'=>array('name'=>'Pronoun', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'conductor'=>array('name'=>'Conductor', 'default'=>''),
            'num_people'=>array('name'=>'Number of People', 'default'=>'1'),
            'parent'=>array('name'=>'Parent', 'default'=>''),
            'address'=>array('name'=>'Address', 'default'=>''),
            'city'=>array('name'=>'City', 'default'=>''),
            'province'=>array('name'=>'Province', 'default'=>''),
            'postal'=>array('name'=>'Postal Code', 'default'=>''),
            'country'=>array('name'=>'Country', 'default'=>''),
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
            'accompanist_customer_id'=>array('name'=>'Accompanist', 'ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'member_id'=>array('name'=>'Member Festival', 'ref'=>'ciniki.musicfestivals.member', 'default'=>'0'),
            'rtype'=>array('name'=>'Type'),
            'status'=>array('name'=>'Status'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'invoice_id'=>array('name'=>'Status', 'default'=>'0'),
            'display_name'=>array('name'=>'Name', 'default'=>''),
            'public_name'=>array('name'=>'Public Name', 'default'=>''),
            'pn_display_name'=>array('name'=>'Pronoun Display Name', 'default'=>''),
            'pn_public_name'=>array('name'=>'Pronoun Public Name', 'default'=>''),
            'competitor1_id'=>array('name'=>'Competitor 1', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor2_id'=>array('name'=>'Competitor 2', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor3_id'=>array('name'=>'Competitor 3', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor4_id'=>array('name'=>'Competitor 4', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'competitor5_id'=>array('name'=>'Competitor 5', 'ref'=>'ciniki.musicfestivals.competitor', 'default'=>'0'),
            'class_id'=>array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class'),
            'timeslot_id'=>array('name'=>'Timeslot', 'ref'=>'ciniki.musicfestivals.scheduletimeslot', 'default'=>'0'),
            'timeslot_sequence'=>array('name'=>'Timeslot Sequence', 'default'=>'0'),
            'title1'=>array('name'=>'Title', 'default'=>''),
            'composer1'=>array('name'=>'Composer', 'default'=>''),
            'movements1'=>array('name'=>'Movements', 'default'=>''),
            'perf_time1'=>array('name'=>'Performance Time', 'default'=>''),
            'title2'=>array('name'=>'2nd Title', 'default'=>''),
            'composer2'=>array('name'=>'2nd Composer', 'default'=>''),
            'movements2'=>array('name'=>'2nd Movements', 'default'=>''),
            'perf_time2'=>array('name'=>'2nd Performance Time', 'default'=>''),
            'title3'=>array('name'=>'3rd Title', 'default'=>''),
            'composer3'=>array('name'=>'3rd Composer', 'default'=>''),
            'movements3'=>array('name'=>'3rd Movements', 'default'=>''),
            'perf_time3'=>array('name'=>'3rd Performance Time', 'default'=>''),
            'title4'=>array('name'=>'4th Title', 'default'=>''),
            'composer4'=>array('name'=>'4th Composer', 'default'=>''),
            'movements4'=>array('name'=>'4th Movements', 'default'=>''),
            'perf_time4'=>array('name'=>'4th Performance Time', 'default'=>''),
            'title5'=>array('name'=>'5th Title', 'default'=>''),
            'composer5'=>array('name'=>'5th Composer', 'default'=>''),
            'movements5'=>array('name'=>'5th Movements', 'default'=>''),
            'perf_time5'=>array('name'=>'5th Performance Time', 'default'=>''),
            'title6'=>array('name'=>'6th Title', 'default'=>''),
            'composer6'=>array('name'=>'6th Composer', 'default'=>''),
            'movements6'=>array('name'=>'6th Movements', 'default'=>''),
            'perf_time6'=>array('name'=>'6th Performance Time', 'default'=>''),
            'title7'=>array('name'=>'7th Title', 'default'=>''),
            'composer7'=>array('name'=>'7th Composer', 'default'=>''),
            'movements7'=>array('name'=>'7th Movements', 'default'=>''),
            'perf_time7'=>array('name'=>'7th Performance Time', 'default'=>''),
            'title8'=>array('name'=>'8th Title', 'default'=>''),
            'composer8'=>array('name'=>'8th Composer', 'default'=>''),
            'movements8'=>array('name'=>'8th Movements', 'default'=>''),
            'perf_time8'=>array('name'=>'8th Performance Time', 'default'=>''),
            'fee'=>array('name'=>'Fee', 'type'=>'currency', 'default'=>'0'),
            'payment_type'=>array('name'=>'Payment Type', 'default'=>'0'),
            'participation'=>array('name'=>'Virtual Submission', 'default'=>'0'),
            'video_url1'=>array('name'=>'Video Link 1', 'default'=>''),
            'video_url2'=>array('name'=>'Video Link 2', 'default'=>''),
            'video_url3'=>array('name'=>'Video Link 3', 'default'=>''),
            'video_url4'=>array('name'=>'Video Link 4', 'default'=>''),
            'video_url5'=>array('name'=>'Video Link 5', 'default'=>''),
            'video_url6'=>array('name'=>'Video Link 6', 'default'=>''),
            'video_url7'=>array('name'=>'Video Link 7', 'default'=>''),
            'video_url8'=>array('name'=>'Video Link 8', 'default'=>''),
            'music_orgfilename1'=>array('name'=>'Music Original Filename 1', 'default'=>''),
            'music_orgfilename2'=>array('name'=>'Music Original Filename 2', 'default'=>''),
            'music_orgfilename3'=>array('name'=>'Music Original Filename 3', 'default'=>''),
            'music_orgfilename4'=>array('name'=>'Music Original Filename 4', 'default'=>''),
            'music_orgfilename5'=>array('name'=>'Music Original Filename 5', 'default'=>''),
            'music_orgfilename6'=>array('name'=>'Music Original Filename 6', 'default'=>''),
            'music_orgfilename7'=>array('name'=>'Music Original Filename 7', 'default'=>''),
            'music_orgfilename8'=>array('name'=>'Music Original Filename 8', 'default'=>''),
            'backtrack1'=>array('name'=>'Backtrack 1', 'default'=>''),
            'backtrack2'=>array('name'=>'Backtrack 2', 'default'=>''),
            'backtrack3'=>array('name'=>'Backtrack 3', 'default'=>''),
            'backtrack4'=>array('name'=>'Backtrack 4', 'default'=>''),
            'backtrack5'=>array('name'=>'Backtrack 5', 'default'=>''),
            'backtrack6'=>array('name'=>'Backtrack 6', 'default'=>''),
            'backtrack7'=>array('name'=>'Backtrack 7', 'default'=>''),
            'backtrack8'=>array('name'=>'Backtrack 8', 'default'=>''),
            'instrument'=>array('name'=>'Instrument', 'default'=>''),
            'mark'=>array('name'=>'Mark', 'default'=>''),
            'placement'=>array('name'=>'Placement', 'default'=>''),
            'level'=>array('name'=>'Level', 'default'=>''),
            'comments'=>array('name'=>'Comments', 'default'=>''),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            'internal_notes'=>array('name'=>'Interal Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['tag'] = array(
        'name'=>'Registration Tag',
        'o_name'=>'tag',
        'o_container'=>'tags',
        'sync'=>'yes',
        'table'=>'ciniki_musicfestival_registration_tags',
        'fields'=>array(
            'registration_id'=>array('name'=>'Registration', 'ref'=>'ciniki.musicfestivals.registration'),
            'tag_type'=>array('name'=>'Tag Type'),
            'tag_name'=>array('name'=>'Tag Name'),
            'permalink'=>array('name'=>'Permalink'),
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
//            'placement'=>array('name'=>'Placement', 'default'=>''),
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
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'adjudicator1_id'=>array('name'=>'First Adjudicator', 'id'=>'ciniki.musicfestivals.adjudicator', 'default'=>'0'),
            'adjudicator2_id'=>array('name'=>'Second Adjudicator', 'id'=>'ciniki.musicfestivals.adjudicator', 'default'=>'0'),
            'adjudicator3_id'=>array('name'=>'Third Adjudicator', 'id'=>'ciniki.musicfestivals.adjudicator', 'default'=>'0'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'sponsor_settings'=>array('name'=>'Sponsor Settings', 'sfields'=>array(
                'top_sponsors_title'=>array('name'=>'Top Sponsors Title'),
                'top_sponsor_ids'=>array('name'=>'Top Sponsors Title'),
                'top_sponsors_image_ratio'=>array('name'=>'Bottom Sponsors Image Ratio'),
                'bottom_sponsors_title'=>array('name'=>'Bottom Sponsors Title'),
                'bottom_sponsors_content'=>array('name'=>'Bottom Sponsors Content'),
                'bottom_sponsor_ids'=>array('name'=>'Bottom Sponsors Title'),
                'bottom_sponsors_image_ratio'=>array('name'=>'Bottom Sponsors Image Ratio'),
                )),
            'provincial_settings'=>array('name'=>'Provincial Settings', 'sfields'=>array(
                'provincials_title' => array('name'=>'Title'),
                'provincials_content' => array('name'=>'Content'),
                'provincials_image_id' => array('name'=>'Image'),
                )),
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
            'flags'=>array('name'=>'Options', 'default'=>'0'),
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
//            'class1_id'=>array('name'=>'Class 1', 'ref'=>'ciniki.musicfestivals.class', 'default'=>'0'),
//            'class2_id'=>array('name'=>'Class 2', 'ref'=>'ciniki.musicfestivals.class', 'default'=>'0'),
//            'class3_id'=>array('name'=>'Class 3', 'ref'=>'ciniki.musicfestivals.class', 'default'=>'0'),
//            'class4_id'=>array('name'=>'Class 4', 'ref'=>'ciniki.musicfestivals.class', 'default'=>'0'),
//            'class5_id'=>array('name'=>'Class 5', 'ref'=>'ciniki.musicfestivals.class', 'default'=>'0'),
            'name'=>array('name'=>'Name'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'runsheet_notes'=>array('name'=>'Runsheet Notes', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_musicfestivals_history',
        );
    $objects['timeslotimage'] = array(
        'name' => 'Schedule Time Slot Image',
        'sync' => 'yes',
        'o_name' => 'image',
        'o_container' => 'images',
        'table' => 'ciniki_musicfestival_timeslot_images',
        'fields' => array(
            'timeslot_id' => array('name'=>'Timeslot', 'ref'=>'ciniki.musicfestivals.scheduletimeslot'),
            'title' => array('name'=>'Title', 'default'=>''),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>0),
            'sequence' => array('name'=>'Order', 'default'=>1),
            'image_id' => array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'description' => array('name'=>'Description', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['sponsor'] = array(
        'name' => 'Sponsor',
        'sync' => 'yes',
        'o_name' => 'sponsor',
        'o_container' => 'sponsors',
        'table' => 'ciniki_musicfestival_sponsors',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'name' => array('name'=>'Name'),
            'url' => array('name'=>'Website', 'default'=>''),
            'sequence' => array('name'=>'Order', 'default'=>1),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'image_id' => array('name'=>'Logo', 'ref'=>'ciniki.images.image', 'default'=>0),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['list'] = array(
        'name' => 'List',
        'sync' => 'yes',
        'o_name' => 'list',
        'o_container' => 'lists',
        'table' => 'ciniki_musicfestival_lists',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'name' => array('name'=>'Name'),
            'category' => array('name'=>'Category'),
            'intro' => array('name'=>'Introduction', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['listsection'] = array(
        'name' => 'List Section',
        'sync' => 'yes',
        'o_name' => 'listsection',
        'o_container' => 'listsections',
        'table' => 'ciniki_musicfestival_list_sections',
        'fields' => array(
            'list_id' => array('name'=>'List', 'ref'=>'ciniki.musicfestivals.list'),
            'name' => array('name'=>'Name'),
            'sequence' => array('name'=>'Order', 'default'=>'1'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['listentry'] = array(
        'name' => 'List Entry',
        'sync' => 'yes',
        'o_name' => 'listentry',
        'o_container' => 'listentries',
        'table' => 'ciniki_musicfestival_list_entries',
        'fields' => array(
            'section_id' => array('name'=>'Section', 'ref'=>'ciniki.musicfestivals.listsection'),
            'sequence' => array('name'=>'Order', 'default'=>'1'),
            'award' => array('name'=>'Award', 'default'=>''),
            'amount' => array('name'=>'Amount', 'default'=>''),
            'donor' => array('name'=>'Donor', 'default'=>''),
            'winner' => array('name'=>'Winner', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['certificate'] = array(
        'name' => 'Certificate',
        'sync' => 'yes',
        'o_name' => 'certificate',
        'o_container' => 'certificates',
        'table' => 'ciniki_musicfestival_certificates',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'name' => array('name'=>'Name'),
            'image_id' => array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'orientation' => array('name'=>'Orientation', 'default'=>''),
            'section_id' => array('name'=>'Section', 'ref'=>'ciniki.musicfestivals.section', 'default'=>0),
            'min_score' => array('name'=>'Minimum Score', 'default'=>'0'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['certfield'] = array(
        'name' => 'Certificate Field',
        'sync' => 'yes',
        'o_name' => 'field',
        'o_container' => 'fields',
        'table' => 'ciniki_musicfestival_certificate_fields',
        'fields' => array(
            'certificate_id' => array('name'=>'Certificate', 'ref'=>'ciniki.musicfestivals.certificate'),
            'name' => array('name'=>'Name'),
            'field' => array('name'=>'Field'),
            'xpos' => array('name'=>'X Position'),
            'ypos' => array('name'=>'Y Position'),
            'width' => array('name'=>'Width'),
            'height' => array('name'=>'Height'),
            'font' => array('name'=>'Font', 'default'=>''),
            'size' => array('name'=>'Size', 'default'=>''),
            'style' => array('name'=>'Style', 'default'=>''),
            'align' => array('name'=>'Align', 'default'=>'C'),
            'valign' => array('name'=>'Vertical Align', 'default'=>'M'),
            'color' => array('name'=>'Color', 'default'=>''),
            'bgcolor' => array('name'=>'Background Color', 'default'=>''),
            'text' => array('name'=>'Text', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['trophy'] = array(
        'name' => 'Trophy',
        'sync' => 'yes',
        'o_name' => 'trophy',
        'o_container' => 'trophies',
        'table' => 'ciniki_musicfestival_trophies',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'category' => array('name'=>'Category', 'default'=>''),
            'primary_image_id' => array('name'=>'Image', 'default'=>'0', 'ref'=>'ciniki.images.image'),
            'donated_by' => array('name'=>'Donated By', 'default'=>''),
            'first_presented' => array('name'=>'First Presented', 'default'=>''),
            'criteria' => array('name'=>'Criteria', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['trophywinner'] = array(
        'name' => 'Trophy Winner',
        'sync' => 'yes',
        'o_name' => 'winner',
        'o_container' => 'winners',
        'table' => 'ciniki_musicfestival_trophy_winners',
        'fields' => array(
            'trophy_id' => array('name'=>'Trophy', 'ref'=>'ciniki.musicfestivals.trophy'),
            'name' => array('name'=>'Name'),
            'year' => array('name'=>'Year', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['trophyclass'] = array(
        'name' => 'Trophy Class',
        'sync' => 'yes',
        'o_name' => 'class',
        'o_container' => 'classes',
        'table' => 'ciniki_musicfestival_trophy_classes',
        'fields' => array(
            'trophy_id' => array('name'=>'Trophy', 'ref'=>'ciniki.musicfestivals.trophy'),
            'class_id' => array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['message'] = array(
        'name' => 'Mail',
        'sync' => 'yes',
        'o_name' => 'message',
        'o_container' => 'messages',
        'table' => 'ciniki_musicfestival_messages',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'subject' => array('name'=>'Subject'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'content' => array('name'=>'Content', 'default'=>''),
            'files' => array('name'=>'Files', 'default'=>''),
            'dt_scheduled' => array('name'=>'Scheduled Date', 'default'=>''),
            'dt_sent' => array('name'=>'Sent Date', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['messageref'] = array(
        'name' => 'Mail Object',
        'sync' => 'yes',
        'o_name' => 'messageref',
        'o_container' => 'messagerefs',
        'table' => 'ciniki_musicfestival_messagerefs',
        'fields' => array(
            'message_id' => array('name'=>'message', 'ref'=>'ciniki.musicfestivals.message'),
            'object' => array('name'=>'Object'),
            'object_id' => array('name'=>'Object ID'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['member'] = array(
        'name' => 'Member Festival',
        'sync' => 'yes',
        'o_name' => 'member',
        'o_container' => 'members',
        'table' => 'ciniki_musicfestivals_members',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink'),
            'category' => array('name'=>'Category', 'default'=>''),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer', 'default'=>'0'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['festivalmember'] = array(
        'name' => 'Festival Member',
        'sync' => 'yes',
        'o_name' => 'member',
        'o_container' => 'members',
        'table' => 'ciniki_musicfestival_members',
        'fields' => array(
            'festival_id' => array('name'=>'Festival'),
            'member_id' => array('name'=>'Member'),
            'reg_start_dt' => array('name'=>'Registrations Open'),
            'reg_end_dt' => array('name'=>'Registrations Close'),
            'latedays' => array('name'=>'Late Days', 'default'=>'0'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['recommendation'] = array(
        'name' => 'Adjudicator Recommendation',
        'sync' => 'yes',
        'o_name' => 'recommendation',
        'o_container' => 'recommendations',
        'table' => 'ciniki_musicfestival_recommendations',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.musicfestivals.festival'),
            'member_id' => array('name'=>'Member Festival', 'ref'=>'ciniki.musicfestivals.member'),
            'section_id' => array('name'=>'Syllabus Section', 'ref'=>'ciniki.musicfestivals.section'),
            'adjudicator_name' => array('name'=>'Adjudicator Name'),
            'adjudicator_phone' => array('name'=>'Adjudicator Phone', 'default'=>''),
            'adjudicator_email' => array('name'=>'Adjudicator Email', 'default'=>''),
            'acknowledgement' => array('name'=>'Acknowledgement', 'default'=>''),
            'date_submitted' => array('name'=>'Date Submitted'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    $objects['recommendationentry'] = array(
        'name' => 'Adjudicator Recommendation Entry',
        'sync' => 'yes',
        'o_name' => 'entry',
        'o_container' => 'entries',
        'table' => 'ciniki_musicfestival_recommendation_entries',
        'fields' => array(
            'status' => array('name'=>'Status', 'default'=>'10'),
            'recommendation_id' => array('name'=>'Recommendation', 'ref'=>'ciniki.musicfestivals.recommendation'),
            'class_id' => array('name'=>'Class', 'ref'=>'ciniki.musicfestivals.class'),
            'position' => array('name'=>'Position', 'ref'=>'ciniki.musicfestivals.class'),
            'name' => array('name'=>'Name'),
            'mark' => array('name'=>'Mark'),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
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
    $objects['socialpost'] = array(
        'name' => 'Social Post',
        'sync' => 'yes',
        'o_name' => 'socialpost',
        'o_container' => 'socialposts',
        'table' => 'ciniki_musicfestivals_socialposts',
        'fields' => array(
            'user_id' => array('name'=>'Add By', 'ref'=>'ciniki.users.user'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'image_id' => array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'content' => array('name'=>'Content', 'default'=>''),
            'notes' => array('name'=>'Notes', 'default'=>''),
            ),
        'history_table' => 'ciniki_musicfestivals_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
