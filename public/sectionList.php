<?php
//
// Description
// -----------
// This method will return the list of Sections for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Section for.
//
// Returns
// -------
//
function ciniki_musicfestivals_sectionList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'syllabus_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.festival_id, "
        . "sections.name, "
        . "sections.permalink, "
        . "sections.sequence, "
        . "sections.flags, "
        . "sections.live_end_dt, "
        . "sections.virtual_end_dt, "
        . "sections.latefees_start_amount, "
        . "sections.latefees_daily_increase, "
        . "sections.latefees_days, "
        . "sections.adminfees_amount, "
        . "sections.scrutineer1_id, "
        . "IFNULL(scrutineer1.display_name, '') AS scrutineer1_name "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "LEFT JOIN ciniki_customers AS scrutineer1 ON ("
            . "sections.scrutineer1_id = scrutineer1.id "
            . "AND scrutineer1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    if( isset($args['syllabus_id']) && $args['syllabus_id'] > 0 ) {
        $strsql .= "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $args['syllabus_id']) . "' ";
    }
    $strsql .= "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'permalink', 'sequence', 'flags',
                'live_end_dt', 'virtual_end_dt', 
                'latefees_start_amount', 'latefees_daily_increase', 'latefees_days', 'adminfees_amount',
                'scrutineer1_id', 'scrutineer1_name',
                ),
            'naprices'=>array(
                'latefees_start_amount', 'latefees_daily_increase', 'adminfees_amount',
                ),
            'utctotz'=>array(
                'live_end_dt'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                'virtual_end_dt'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['sections']) ) {
        $sections = $rc['sections'];
        $section_ids = array();
        foreach($sections as $iid => $section) {
            $section_ids[] = $section['id'];
            $sections[$iid]['latefees_text'] = '';
            if( ($section['flags']&0x10) == 0x10 ) {
                $sections[$iid]['latefees_text'] = 'per Invoice';
            }
            if( ($section['flags']&0x20) == 0x20 ) {
                $sections[$iid]['latefees_text'] = 'per Registration';
            }
            if( ($section['flags']&0x30) == 0 ) {
                $sections[$iid]['latefees_start_amount'] = '';
                $sections[$iid]['latefees_daily_increase'] = '';
                $sections[$iid]['latefees_days'] = '';
            }
            if( ($section['flags']&0x40) == 0x40 ) {
                $sections[$iid]['adminfees_text'] = 'per Invoice';
            }
            if( ($section['flags']&0x80) == 0x80 ) {
                $sections[$iid]['adminfees_text'] = 'per Registration';
            }
            if( ($section['flags']&0xC0) == 0 ) {
                $sections[$iid]['adminfees_amount'] = '';
            }
        }
    } else {
        $sections = array();
        $section_ids = array();
    }

    return array('stat'=>'ok', 'sections'=>$sections, 'nplist'=>$section_ids);
}
?>
