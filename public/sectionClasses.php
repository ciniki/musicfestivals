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
function ciniki_musicfestivals_sectionClasses($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    
    $strsql = "SELECT ciniki_musicfestival_sections.id, "
        . "ciniki_musicfestival_sections.festival_id, "
        . "ciniki_musicfestival_sections.name, "
        . "ciniki_musicfestival_sections.permalink, "
        . "ciniki_musicfestival_sections.sequence, "
        . "ciniki_musicfestival_sections.flags, "
        . "ciniki_musicfestival_sections.primary_image_id, "
        . "ciniki_musicfestival_sections.synopsis, "
        . "ciniki_musicfestival_sections.description, "
        . "ciniki_musicfestival_sections.live_end_dt, "
        . "ciniki_musicfestival_sections.virtual_end_dt, "
        . "ciniki_musicfestival_sections.edit_end_dt, "
        . "ciniki_musicfestival_sections.upload_end_dt "
        . "FROM ciniki_musicfestival_sections "
        . "WHERE ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('festival_id', 'name', 'permalink', 'sequence', 'flags', 
                'primary_image_id', 'synopsis', 'description',
                'live_end_dt', 'virtual_end_dt', 'edit_end_dt', 'upload_end_dt',
                ),
            'utctotz'=>array(
                'live_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'virtual_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'edit_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'upload_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.21', 'msg'=>'Section not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['sections'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.22', 'msg'=>'Unable to find Section'));
    }
    $section = $rc['sections'][0];

    //
    // Get the list of classes
    //
    $strsql = "SELECT categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.permalink, "
        . "categories.sequence AS category_sequence, "
        . "classes.id, "
        . "classes.code, "
        . "classes.name AS class_name, "
        . "classes.sequence AS class_sequence, "
        . "classes.level, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
        . "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'category_id', 'category_name', 'permalink', 'category_sequence', 
                'code', 'class_name', 'class_sequence', 'level', 
                'earlybird_fee', 'fee', 'virtual_fee'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.501', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    if( isset($rc['classes']) ) {
        $classes = $rc['classes'];
        $classes_ids = array();
        foreach($classes as $k => $v) {
            $classes_ids[] = $v['category_id'];
        }
    } else {
        $classes = array();
        $classes_ids = array();
    }

    //
    // Load the festival details
    //
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.start_date, "
        . "ciniki_musicfestivals.end_date, "
        . "ciniki_musicfestivals.status, "
        . "ciniki_musicfestivals.flags, "
        . "ciniki_musicfestivals.earlybird_date, "
        . "ciniki_musicfestivals.live_date, "
        . "ciniki_musicfestivals.virtual_date, "
        . "ciniki_musicfestivals.edit_end_dt, "
        . "ciniki_musicfestivals.upload_end_dt "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $section['festival_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.499', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.500', 'msg'=>'Unable to find requested festival'));
    }
    $festival = $rc['festival'];
    

    return array('stat'=>'ok', 'section'=>$section, 'classes'=>$classes, 'nplists'=>$classes_ids, 'festival'=>$festival);
}
?>