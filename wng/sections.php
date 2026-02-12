<?php
//
// Description
// -----------
// Return the list of sections available from the music festival module
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure forms module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.208', 'msg'=>'Module not enabled'));
    }

    $sections = array();

    //
    // Load the list of festivals in descending order
    //
    $strsql = "SELECT festivals.id, "
        . "festivals.name, "
        . "festivals.flags, "
        . "festivals.start_date "
        . "FROM ciniki_musicfestivals AS festivals "
        . "WHERE festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY festivals.start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 'fields'=>array('id', 'name', 'flags')), //, 'provincial_festival_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.351', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
    }
    $festivals = isset($rc['festivals']) ? $rc['festivals'] : array();
    $virtual_festivals = 'no';
    foreach($festivals as $festival) {
        if( ($festival['flags']&0x02) == 0x02 ) {
            $virtual_festivals = 'yes';
        }
    }

    if( isset($festivals[0]) ) {
        $festival = $festivals[0];
        //
        // Get the additional settings
        //
        $strsql = "SELECT detail_key, detail_value "
            . "FROM ciniki_musicfestival_settings "
            . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.741', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
        }
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }
    }

    //
    // Get the list of connected provincial festivals
    //
    $strsql = "SELECT festivals.id, "
        . "CONCAT_WS(' - ', tenants.name, festivals.name) AS name "
        . "FROM ciniki_musicfestivals_members AS members "
        . "INNER JOIN ciniki_musicfestival_members AS fmembers ON ("
            . "members.id = fmembers.member_id "
            . "AND members.tnid = fmembers.tnid "
            . ") "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "fmembers.festival_id = festivals.id "
            . "AND fmembers.tnid = festivals.tnid "
            . ") "
        . "INNER JOIN ciniki_tenants AS tenants ON ("
            . "festivals.tnid = tenants.id "
            . ") "
        . "WHERE members.member_tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'provincials', 'fname'=>'id', 
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1004', 'msg'=>'Unable to load provincials', 'err'=>$rc['err']));
    }
    $provincial_festivals = isset($rc['provincials']) ? $rc['provincials'] : array();

    //
    // Get the list of available Approved Title Lists
    //
    $strsql = "SELECT lists.id, "
        . "CONCAT_WS(' - ', tenants.name, lists.name) AS name, "
        . "IF(lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "', 1, 2) AS sortrank "
        . "FROM ciniki_musicfestivals_titlelists AS lists "
        . "INNER JOIN ciniki_tenants AS tenants ON ("
            . "lists.tnid = tenants.id "
            . ") "
        . "WHERE (lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . " OR (lists.flags&0x01) = 0x01 " // public shared
            . ") "
        . "ORDER BY sortrank, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'titlelists', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1165', 'msg'=>'Unable to load titlelists', 'err'=>$rc['err']));
    }
    $titlelists = isset($rc['titlelists']) ? $rc['titlelists'] : array();

    //
    // Get the syllabuses (festival_id - Syllabus), this is used for festival that have multiple syllabuses
    //
    $strsql = "SELECT syllabuses.id, "
        . "syllabuses.name, "
//    $strsql = "SELECT DISTINCT festivals.id, "
//        . "CONCAT_WS('-', festivals.id, sections.syllabus) AS sid, "
        . "festivals.name AS festival_name, "
        . "festivals.flags, "
        . "festivals.start_date "
        . "FROM ciniki_musicfestivals AS festivals "
        . "LEFT JOIN ciniki_musicfestival_syllabuses AS syllabuses ON ("
            . "festivals.id = syllabuses.festival_id "
            . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY festivals.start_date DESC, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'syllabuses', 'fname'=>'id', 
            'fields'=>array('id', 'flags', 'name', 'festival_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.747', 'msg'=>'Unable to load syllabuses', 'err'=>$rc['err']));
    }
    $syllabuses = isset($rc['syllabuses']) ? $rc['syllabuses'] : array();
    foreach($syllabuses as $sid => $syllabus) {
        if( ($syllabus['flags']&0x0800) == 0x0800 ) {
            $syllabuses[$sid]['name'] = $syllabus['festival_name'] . ' - ' . $syllabus['name'];
        } else {
            $syllabuses[$sid]['name'] = $syllabus['festival_name'];
        }
    }

    //
    // Section to display the syllabus
    //
    $sections['ciniki.musicfestivals.syllabus'] = array(
        'name' => 'Syllabus',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'syllabus-id' => array('label'=>'Syllabus', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$syllabuses,
                ),
            'layout' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                'tradingcards' => 'Trading Cards',
                'imagebuttons' => 'Image Buttons',
                'buttons' => 'Buttons',
                'groups' => 'Groups - Table',
                'groupbuttons' => 'Groups - Buttons',
                'classlist' => 'Categories and Class Lists',
                'pricelist' => 'Price List',
                )),
            'image-ratio' => array('label' => 'Image Ratio (Image Buttons Only)', 
                'type'=>'select', 
                'default'=>'4-3', 
                'options'=>array(
                    '2-1' => 'Panoramic',
                    '16-9' => 'Letterbox',
                    '6-4' => 'Wider',
                    '4-3' => 'Wide',
                    '1-1' => 'Square',
                    '3-4' => 'Tall',
                    '4-6' => 'Taller',
                )), 
            'title-position' => array('label' => 'Title Position (Image Buttons Only)', 
                'type'=>'select', 
                'default'=>'overlay-bottomhalf', 
                'options'=>array(
                    'above' => 'Above',
                    'overlay-top' => 'Overlay Top',
                    'overlay-tophalf' => 'Overlay Top Half',
                    'overlay-center' => 'Centered',
                    'overlay-bottomhalf' => 'Bottom Half',
                    'overlay-bottom' => 'Bottom',
                    'below' => 'Below',
                )),
            'live-search' => array('label'=>'Class Search', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'top' => 'Yes',
                // In future can add bottom/both as options if needed
                )),
            'display-accolades' => array('label'=>'Show Accolades', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'yes' => 'Yes',
                )),
/*            'syllabus-pdf' => array('label'=>'Complete Syllabus PDF Download', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Off',
                'top' => 'Top',
                'bottom' => 'Bottom',
                'both' => 'Both',
                )), */
/*            'section-pdf' => array('label'=>'Section PDF Download', 'type'=>'toggle', 'default'=>'no', 'separator'=>'yes',
                'toggles'=>array(
                    'no' => 'Off',
                    'top' => 'Top',
                    'bottom' => 'Bottom',
                    'both' => 'Both',
                )),*/
            'syllabus-top-button-1-pdf' => array('label'=>'Top Syllabus PDF Download', 'type'=>'toggle', 'default'=>'no', 'separator'=>'yes',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    ),
                ),
            'syllabus-top-button-2-page' => array('label'=>'Top Button 2', 'type'=>'select', 'pages'=>'yes'),
            'syllabus-top-button-2-text' => array('label'=>'Text', 'type'=>'text'),
            'syllabus-top-button-2-url' => array('label'=>'URL', 'type'=>'text'),
            'syllabus-top-button-3-page' => array('label'=>'Top Button 3', 'type'=>'select', 'pages'=>'yes'),
            'syllabus-top-button-3-text' => array('label'=>'Text', 'type'=>'text'),
            'syllabus-top-button-3-url' => array('label'=>'URL', 'type'=>'text'),
            'syllabus-top-button-4-page' => array('label'=>'Top Button 4', 'type'=>'select', 'pages'=>'yes'),
            'syllabus-top-button-4-text' => array('label'=>'Text', 'type'=>'text'),
            'syllabus-top-button-4-url' => array('label'=>'URL', 'type'=>'text'),
            'syllabus-bottom-button-1-pdf' => array('label'=>'Bottom Syllabus PDF Download', 'type'=>'toggle', 'default'=>'no', 'separator'=>'yes',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    ),
                ),
            'syllabus-bottom-button-2-page' => array('label'=>'Bottom Button 2', 'type'=>'select', 'pages'=>'yes'),
            'syllabus-bottom-button-2-text' => array('label'=>'Text', 'type'=>'text'),
            'syllabus-bottom-button-2-url' => array('label'=>'URL', 'type'=>'text'),
            'syllabus-bottom-button-3-page' => array('label'=>'Bottom Button 3', 'type'=>'select', 'pages'=>'yes'),
            'syllabus-bottom-button-3-text' => array('label'=>'Text', 'type'=>'text'),
            'syllabus-bottom-button-3-url' => array('label'=>'URL', 'type'=>'text'),
            'syllabus-bottom-button-4-page' => array('label'=>'Bottom Button 4', 'type'=>'select', 'pages'=>'yes'),
            'syllabus-bottom-button-4-text' => array('label'=>'Text', 'type'=>'text'),
            'syllabus-bottom-button-4-url' => array('label'=>'URL', 'type'=>'text'),
            'section-top-button-1-pdf' => array('label'=>'Top Section PDF Download', 'type'=>'toggle', 'default'=>'no', 'separator'=>'yes',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    ),
                ),
            'section-top-button-2-page' => array('label'=>'Top Button 2', 'type'=>'select', 'pages'=>'yes'),
            'section-top-button-2-text' => array('label'=>'Text', 'type'=>'text'),
            'section-top-button-2-url' => array('label'=>'URL', 'type'=>'text'),
            'section-top-button-3-page' => array('label'=>'Top Button 3', 'type'=>'select', 'pages'=>'yes'),
            'section-top-button-3-text' => array('label'=>'Text', 'type'=>'text'),
            'section-top-button-3-url' => array('label'=>'URL', 'type'=>'text'),
            'section-top-button-4-page' => array('label'=>'Top Button 4', 'type'=>'select', 'pages'=>'yes'),
            'section-top-button-4-text' => array('label'=>'Text', 'type'=>'text'),
            'section-top-button-4-url' => array('label'=>'URL', 'type'=>'text'),
            'section-bottom-button-1-pdf' => array('label'=>'Bottom Section PDF Download', 'type'=>'toggle', 'default'=>'no', 'separator'=>'yes',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    ),
                ),
            'section-bottom-button-2-page' => array('label'=>'Bottom Button 2', 'type'=>'select', 'pages'=>'yes'),
            'section-bottom-button-2-text' => array('label'=>'Text', 'type'=>'text'),
            'section-bottom-button-2-url' => array('label'=>'URL', 'type'=>'text'),
            'section-bottom-button-3-page' => array('label'=>'Bottom Button 3', 'type'=>'select', 'pages'=>'yes'),
            'section-bottom-button-3-text' => array('label'=>'Text', 'type'=>'text'),
            'section-bottom-button-3-url' => array('label'=>'URL', 'type'=>'text'),
            'section-bottom-button-4-page' => array('label'=>'Bottom Button 4', 'type'=>'select', 'pages'=>'yes'),
            'section-bottom-button-4-text' => array('label'=>'Text', 'type'=>'text'),
            'section-bottom-button-4-url' => array('label'=>'URL', 'type'=>'text'),
            ),
        );
 
    if( $virtual_festivals == 'yes' ) {
        $sections['ciniki.musicfestivals.syllabus']['settings']['display-live-virtual'] = array(
            'label'=>'Classes', 
            'type'=>'toggle', 'default'=>'all', 'separator'=>'yes', 'toggles'=>array(
                'all' => 'All',
                'live' => 'Live',
                'virtual' => 'Virtual',
                ));
        // Provincials Only for now
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
            $sections['ciniki.musicfestivals.syllabus']['settings']['participation-label'] = array(
                'label'=>'Participation Label', 'type'=>'select', 'options'=>array(
                    'none' => 'None',
                    'after-class' => 'After Class Name',
                    ));
        }
    }

    //
    // Section to display the syllabus PDF download buttons for each section
    //
    $sections['ciniki.musicfestivals.syllabuspdfs'] = array(
        'name' => 'Syllabus PDFs',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'syllabus-id' => array('label'=>'Syllabus', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$syllabuses,
                ),
            ),
        );

    //
    // Option to show only 1 section of the syllabus
    //
    $strsql = "SELECT sections.id, "
        . "CONCAT_WS(' - ', festivals.name, sections.name) AS name "
        . "FROM ciniki_musicfestivals AS festivals "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "festivals.id = sections.festival_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY festivals.start_date DESC, sections.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $syllabus_sections = isset($rc['sections']) ? $rc['sections'] : array();

    $sections['ciniki.musicfestivals.syllabussection'] = array(
        'name' => 'Syllabus Section',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'section-id' => array('label'=>'Syllabus Section', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$syllabus_sections,
                ),
            'layout' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                'classlist' => 'Categories and Class Lists',
                'pricelist' => 'Price List',
                )),
/*            'image-ratio' => array('label' => 'Image Ratio (Image Buttons Only)', 
                'type'=>'select', 
                'default'=>'4-3', 
                'options'=>array(
                    '2-1' => 'Panoramic',
                    '16-9' => 'Letterbox',
                    '6-4' => 'Wider',
                    '4-3' => 'Wide',
                    '1-1' => 'Square',
                    '3-4' => 'Tall',
                    '4-6' => 'Taller',)), 'title-position' => array('label' => 'Title Position (Image Buttons Only)', 
               'type'=>'select', 
                'default'=>'overlay-bottomhalf', 
                'options'=>array(
                    'above' => 'Above',
                    'overlay-top' => 'Overlay Top',
                    'overlay-tophalf' => 'Overlay Top Half',
                    'overlay-center' => 'Centered',
                    'overlay-bottomhalf' => 'Bottom Half',
                    'overlay-bottom' => 'Bottom',
                    'below' => 'Below',
                )),
            'live-search' => array('label'=>'Class Search', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'top' => 'Yes',
                // In future can add bottom/both as options if needed
                )), */
            'section-pdf' => array('label'=>'Section PDF Download', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Off',
                'top' => 'Top',
                'bottom' => 'Bottom',
                'both' => 'Both',
                )),
            ),
        );
    //
    // Section to display the file download for a festival - deprecated
    //
/*    $sections['ciniki.musicfestivals.files'] = array(
        'name' => 'Files',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            ),
        ); */

    //
    // Section to display the adjudicators for a festival
    //
    $sections['ciniki.musicfestivals.adjudicators'] = array(
        'name' => 'Adjudicators',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
//            'layout' => array('label'=>'Format', 'type'=>'toggle', 'default'=>'contentphoto', 
//                'toggles'=>array('contentphoto'=>'Bio & Photos', 'imagebutton'=>'Image Buttons'),
//                ),
            'no-adjudicators-content' => array('label'=>'No Adjudicators Message', 'type'=>'htmlarea'),
            'layout' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                'contentphoto' => 'Content + Photo',
                'imagebuttons' => 'Image Buttons',
                'tradingcards' => 'Trading Cards',
                )),
            'image-position'=>array('label'=>'Image Position', 'type'=>'select', 'default'=>'top-right', 'options'=>array(
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                )),
            'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>array(
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                )),
            ),
        );
    if( $virtual_festivals == 'yes' ) {
        $sections['ciniki.musicfestivals.adjudicators']['settings']['display-live-virtual'] = array(
            'label'=>'Classes', 
            'type'=>'toggle', 'default'=>'all', 'toggles'=>array(
                'all' => 'All',
                'live' => 'Live',
                'virtual' => 'Virtual',
                ));
    }

    //
    // Section to display the schedules for a festival
    //
    $sections['ciniki.musicfestivals.scheduleoverview'] = array(
        'name' => 'Schedule Overview',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'notreleased' => array('label'=>'Not Released Intro', 'type'=>'htmlarea'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            ),
        );

    //
    // Overview days only available to provincials for now
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $sections['ciniki.musicfestivals.scheduleoverviewdays'] = array(
            'name' => 'Schedule Overview Days',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                ),
            );
    }

    //
    // Section to display the schedules for a festival
    //
    $sections['ciniki.musicfestivals.schedules'] = array(
        'name' => 'Schedules',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'notreleased' => array('label'=>'Not Released Intro', 'type'=>'htmlarea'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            'layout' => array('label'=>'Layout', 'type'=>'select', 'default'=>'',
                'options'=>array(
                    'section-buttons' => 'Section Buttons',
                    'division-buttons' => 'Section - Division Buttons By Date',
                    'division-buttons-name' => 'Section - Division Buttons By Name',
//                    'division-grouped-buttons' => 'Section - Division Grouped Buttons',
                    'date-buttons' => 'Date - Division Buttons',
                    )),
            'division-dates' => array('label'=>'Division Dates', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'division-locations' => array('label'=>'Location Names', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'live-search' => array('label'=>'Search', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'top' => 'Yes',
                // In future can add bottom/both as options if needed
                )),
            'today-divisions' => array('label'=>'Todays Schedule', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'yes' => 'Yes',
                )),
            'ipv' => array('label'=>'Live/Virtual', 'type'=>'toggle', 'default'=>'all', 'separator'=>'yes',
                'toggles'=>array(
                    'all' => 'All',
                    'inperson' => 'Live',
                    'virtual' => 'Virtual',
                    )),
            'separate-classes' => array('label'=>'Separate Classes', 'type'=>'toggle', 'default'=>'no', 
                'toggles'=>array('no'=>'No', 'yes'=>'Yes'),
                ), 
            'class-format'=>array('label'=>'Class Format', 'type'=>'select', 'default'=>'default', 'options'=>array(
                'default'=>'Code - Class', 
                'section-category-class'=>'Section - Category - Class',
                'category-class'=>'Category - Class',
                'code-section-category-class'=>'Code - Section - Category - Class',
                'code-category-class'=>'Code - Category - Class',
                )),
            'titles' => array('label'=>'Titles', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'competitor-numbering' => array('label'=>'Competitor Numbering', 'type'=>'toggle', 'default'=>'no', 
                'toggles'=>array('no'=>'No', 'yes'=>'Yes'),
                ), 
            'names' => array('label'=>'Full Names', 'type'=>'toggle', 'default'=>'public',
                'toggles'=>array(
                    'public' => 'No',
                    'private' => 'Yes',
                    )),
            'video_urls' => array('label'=>'Virtual Video URLs', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'locations-name' => array('label'=>'Locations Name', 'type'=>'toggle', 'default'=>'yes', 
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                )),
            'locations-label' => array('label'=>'Locations Label', 'type'=>'toggle', 'default'=>'no', 
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                )),
            'locations-page' => array('label'=>'Locations Page', 'type'=>'select', 'pages'=>'yes'),
            'adjudicators-name' => array('label'=>'Adjudicators Name', 'type'=>'toggle', 'default'=>'no', 
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                )),
            'adjudicators-label' => array('label'=>'Adjudicators Label', 'type'=>'toggle', 'default'=>'no', 
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                )),
            'adjudicators-page' => array('label'=>'Adjudicators Page', 'type'=>'select', 'pages'=>'yes'),
            'section-pdf' => array('label'=>'Section PDF Download', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Off',
                'top' => 'Top',
                'bottom' => 'Bottom',
                'both' => 'Both',
                )),
            'complete-pdf' => array('label'=>'Complete PDF Download', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Off',
                'top' => 'Top',
                'bottom' => 'Bottom',
                'both' => 'Both',
                )),
            'complete-button-2-page' => array('label'=>'Button 2', 'type'=>'select', 'pages'=>'yes'),
            'complete-button-2-text' => array('label'=>'Text', 'type'=>'text'),
            'complete-button-2-url' => array('label'=>'URL', 'type'=>'text'),
            'division_header_format' => array('label'=>'PDF Division Header Format', 'type'=>'select', 'default'=>'default', 
                'options'=>array(
                    'default' => 'Date-Division, Address', 
                    'name-adjudicator-location' => 'Division, Adjudicator, Location',
                    'date-adjudicator-location' => 'Date, Adjudicator, Location',
                    'date-name-adjudicator-location' => 'Date, Division, Adjudicator, Location',
                    'name-date-adjudicator-location' => 'Division, Date, Adjudicator, Location',
                    )),
            'division_header_labels' => array('label'=>'PDF Division Header Labels', 'type'=>'toggle', 'default'=>'no', 
                'toggles'=>array(
                    'no'=>'No', 
                    'yes'=>'Yes',
                    )),
                // With section name in header you must have break between each section, 
                // this option is only good for print when embedding in another document, not good for website downloads
//            'section_page_break' => array('label'=>'PDF Section Page Break', 'type'=>'toggle', 'default'=>'no',
//                'toggles'=>array(
//                    'no' => 'No',
//                    'yes' => 'Yes',
//                    )),
            ),
        );

    //
    // Section to display the results for a festival
    //
    $sections['ciniki.musicfestivals.results'] = array(
        'name' => 'Results',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'notreleased' => array('label'=>'Not Released Intro', 'type'=>'htmlarea'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            'layout' => array('label'=>'Layout', 'type'=>'select', 'default'=>'',
                'options'=>array(
                    'section-buttons' => 'Section Buttons',
                    'section-grouped-buttons' => 'Section Grouped Buttons',
                    'division-buttons' => 'Section - Division Buttons',
                    'division-grouped-buttons' => 'Section - Division Grouped Buttons',
                    'date-buttons' => 'Date - Division Buttons',
                    )),
            'division-dates' => array('label'=>'Division Dates', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'ipv' => array('label'=>'Live/Virtual', 'type'=>'toggle', 'default'=>'all', 'separator'=>'yes',
                'toggles'=>array(
                    'all' => 'All',
                    'inperson' => 'Live',
                    'virtual' => 'Virtual',
                    )),
            'separate-classes' => array('label'=>'Separate Classes', 'type'=>'toggle', 'default'=>'no', 
                'toggles'=>array('no'=>'No', 'yes'=>'Yes'),
                ), 
            'class-format'=>array('label'=>'Class Format', 'type'=>'select', 'default'=>'default', 'options'=>array(
                'default'=>'Code - Class', 
                'section-category-class'=>'Section - Category - Class',
                'category-class'=>'Category - Class',
                'code-section-category-class'=>'Code - Section - Category - Class',
                'code-category-class'=>'Code - Category - Class',
                )),
            'titles' => array('label'=>'Titles', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'names' => array('label'=>'Full Names', 'type'=>'toggle', 'default'=>'public',
                'toggles'=>array(
                    'public' => 'No',
                    'private' => 'Yes',
                    )),
            'video_urls' => array('label'=>'Virtual Video URLs', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'mark' => array('label'=>'Show Marks', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'min-mark' => array('label'=>'Mininum Mark', 'type'=>'text', 'size'=>'small'),
            'placement' => array('label'=>'Show Placement', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'level' => array('label'=>'Show Level', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            ),
        );
    if( isset($festival['comments-mark-label']) && $festival['comments-mark-label'] != '' ) {
        $sections['ciniki.musicfestivals.results']['settings']['mark']['label'] = 'Show ' . $festival['comments-mark-label'];
    }
    if( isset($festival['comments-placement-label']) && $festival['comments-placement-label'] != '' ) {
        $sections['ciniki.musicfestivals.results']['settings']['placement']['label'] = 'Show ' . $festival['comments-placement-label'];
    }
    if( isset($festival['comments-level-label']) && $festival['comments-level-label'] != '' ) {
        $sections['ciniki.musicfestivals.results']['settings']['level']['label'] = 'Show ' . $festival['comments-level-label'];
    }

    //
    // Provincial results for local festival
    //
    if( count($provincial_festivals) > 0 ) {
        $sections['ciniki.musicfestivals.provincialresults'] = array(
            'name' => 'Provincial Results',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
                'notreleased' => array('label'=>'Not Released Intro', 'type'=>'htmlarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$provincial_festivals,
                    ),
                'layout' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                    'buttons' => 'Buttons -> Subpages',
                    'singlepage' => 'Single Page',
                    )),
                'names' => array('label'=>'Full Names', 'type'=>'toggle', 'default'=>'public',
                    'toggles'=>array(
                        'public' => 'No',
                        'private' => 'Yes',
                        )),
                ),
            );
    }

    //
    // Section to display the results the same as syllabus syllabus
    //
    $sections['ciniki.musicfestivals.syllabusresults'] = array(
        'name' => 'Syllabus Results',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'syllabus-id' => array('label'=>'Syllabus', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$syllabuses,
                ),
            'layout' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                'tradingcards' => 'Trading Cards',
                'imagebuttons' => 'Image Buttons',
                'buttons' => 'Buttons',
                'groups' => 'Groups - Table',
                'groupbuttons' => 'Groups - Buttons',
                'classlist' => 'Categories and Class Lists',
                )),
            'image-ratio' => array('label' => 'Image Ratio (Image Buttons Only)', 
                'type'=>'select', 
                'default'=>'4-3', 
                'options'=>array(
                    '2-1' => 'Panoramic',
                    '16-9' => 'Letterbox',
                    '6-4' => 'Wider',
                    '4-3' => 'Wide',
                    '1-1' => 'Square',
                    '3-4' => 'Tall',
                    '4-6' => 'Taller',)), 'title-position' => array('label' => 'Title Position (Image Buttons Only)', 
                'type'=>'select', 
                'default'=>'overlay-bottomhalf', 
                'options'=>array(
                    'above' => 'Above',
                    'overlay-top' => 'Overlay Top',
                    'overlay-tophalf' => 'Overlay Top Half',
                    'overlay-center' => 'Centered',
                    'overlay-bottomhalf' => 'Bottom Half',
                    'overlay-bottom' => 'Bottom',
                    'below' => 'Below',
                )),
            'class-format'=>array('label'=>'Class Format', 'type'=>'select', 'default'=>'code-category-class', 'options'=>array(
                'default'=>'Code - Class', 
                'section-category-class'=>'Section - Category - Class',
                'category-class'=>'Category - Class',
                'code-section-category-class'=>'Code - Section - Category - Class',
                'code-category-class'=>'Code - Category - Class',
                )), 
            'titles' => array('label'=>'Titles', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'names' => array('label'=>'Full Names', 'type'=>'toggle', 'default'=>'public',
                'toggles'=>array(
                    'public' => 'No',
                    'private' => 'Yes',
                    )),
            'video_urls' => array('label'=>'Virtual Video URLs', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'mark' => array('label'=>'Show Marks', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'min-mark' => array('label'=>'Mininum Mark', 'type'=>'text', 'size'=>'small'),
            'placement' => array('label'=>'Show Placement', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
            'level' => array('label'=>'Show Level', 'type'=>'toggle', 'default'=>'no',
                'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
/*            'live-search' => array('label'=>'Class Search', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'top' => 'Yes',
                // In future can add bottom/both as options if needed
                )), */
            ),
        );
    if( $virtual_festivals == 'yes' ) {
        $sections['ciniki.musicfestivals.syllabusresults']['settings']['display-live-virtual'] = array(
            'label'=>'Classes', 
            'type'=>'toggle', 'default'=>'all', 'toggles'=>array(
                'all' => 'All',
                'live' => 'Live',
                'virtual' => 'Virtual',
                ));
    }
    if( isset($festival['comments-mark-label']) && $festival['comments-mark-label'] != '' ) {
        $sections['ciniki.musicfestivals.syllabusresults']['settings']['mark']['label'] = 'Show ' . $festival['comments-mark-label'];
    }
    if( isset($festival['comments-placement-label']) && $festival['comments-placement-label'] != '' ) {
        $sections['ciniki.musicfestivals.syllabusresults']['settings']['placement']['label'] = 'Show ' . $festival['comments-placement-label'];
    }
    if( isset($festival['comments-level-label']) && $festival['comments-level-label'] != '' ) {
        $sections['ciniki.musicfestivals.syllabusresults']['settings']['level']['label'] = 'Show ' . $festival['comments-level-label'];
    }

    //
    // Section to display the photos for a festival
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x04) ) {
        $sections['ciniki.musicfestivals.timeslotphotos'] = array(
            'name' => 'Photos',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                'name-format' => array('label'=>'Name Format', 'type'=>'toggle', 'default'=>'section-division', 'toggles'=>array(
                    'section-division'=>'Section - Division',
                    'division'=>'Division',
                    )),
                ),
            );
    }

    //
    // Section to display the photos for a festival
    //
    $sections['ciniki.musicfestivals.artwork'] = array(
        'name' => 'Artwork Submissions',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            ),
        );

    //
    // Section to display lists for 
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x20) ) {
        //
        // Get the list of categories available
        //
        $strsql = "SELECT DISTINCT category, category "
            . "FROM ciniki_musicfestival_lists "
            . "WHERE ciniki_musicfestival_lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY category "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'categories', 'category');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.209', 'msg'=>'Unable to load the list of festivals', 'err'=>$rc['err']));
        }
        $categories = isset($rc['categories']) ? $rc['categories'] : array();

        if( count($categories) > 0 ) {
            $sections['ciniki.musicfestivals.categorylists'] = array(
                'name' => 'Category Lists',
                'module' => 'Music Festivals',
                'settings' => array(
                    'title' => array('label'=>'Title', 'type'=>'text'),
                    'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                        'complex_options'=>array('value'=>'id', 'name'=>'name'),
                        'options'=>$festivals,
                        ),
                    'category' => array('label'=>'Category', 'type'=>'select', 'options'=>$categories),
                    'amount-visible' => array('label'=>'Amount Visible', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
                        'no' => 'No',
                        'yes' => 'Yes',
                        )),
                    'donor-visible' => array('label'=>'Donor Visible', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
                        'no' => 'No',
                        'yes' => 'Yes',
                        )),
                    ),
                );
        }
    }

    //
    // Section to display the sponsors for a festival
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x10) ) {
        $strsql = "SELECT DISTINCT tags.tag_name AS id, tags.tag_name AS value "
            . "FROM ciniki_musicfestival_sponsor_tags AS tags "
            . "INNER JOIN ciniki_musicfestival_sponsors AS sponsors ON ("
                . "tags.sponsor_id = sponsors.id "
                . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND tags.tag_type = 10 "
            . "ORDER BY tags.tag_type, tags.tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'tags');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sponsor_tags = isset($rc['tags']) ? $rc['tags'] : array();

        $sections['ciniki.musicfestivals.sponsors'] = array(
            'name' => 'Sponsors',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                'display-format' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                    'images' => 'Sponsor Logo Images Only',
                    'contentphoto' => 'Content Photo List',
                    'text' => 'No Images - Text Only',
                    'names' => 'Name List',
                    )),
                'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'medium', 'toggles'=>array(    
                    'xsmall' => 'X-Small',
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                    'xlarge' => 'X-Large',
                    )),
//                'level' => array('label'=>'Sponsor Level **deprecated**', 'type'=>'toggle', 'default'=>'1', 
//                    'toggles'=>array('1'=>'1', '2'=>'2'),
//                    ),
                'tag' => array('label'=>'Sponsor Tag', 'type'=>'select', 
                    'options'=>$sponsor_tags,
                    ),
                'column-size'=>array('label'=>'Text Columns', 'type'=>'toggle', 'default'=>'large', 'toggles'=>array(    
                    'none' => 'None',
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                    )),
                ),
            );
    }

    //
    // Section to display accolades
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) ) {
        //
        // Get the list of categories
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_musicfestival_accolade_categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.855', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $categories = isset($rc['categories']) ? $rc['categories'] : array();

        //
        // Display list of accolades
        //
        $sections['ciniki.musicfestivals.accolades'] = array(
            'name' => 'Accolades',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'display-format' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                    'buttons-imagebuttons-accolade' => 'Category Buttons - Image Buttons - Accolade',
                    'buttons-buttons-accolade' => 'Category Buttons - Accolade Buttons - Accolade',
                    'buttons-list' => 'Category Buttons - List',
                    'buttons-textcards' => 'Category Buttons - Text Cards',
                    )),
                'display-winners' => array('label'=>'Show Winners', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
                'display-classes' => array('label'=>'Show Classes', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
//                'syllabus-page' => array('label'=>'Syllabus Page', 'type'=>'select', 'pages'=>'yes'),
                ),
            );
//        if( count($categories) > 1 ) { 
            array_unshift($categories, ['name'=>'All']);
            $sections['ciniki.musicfestivals.accolades']['settings']['category-id'] = array(
                'label' => 'Category',
                'type' => 'select',
                'complex_options' => array('value'=>'id', 'name'=>'name'),
                'options' => $categories,
                );
//        }
        //
        // Display list of winners
        //
        $sections['ciniki.musicfestivals.accoladewinners'] = array(
            'name' => 'Accolade Recipients',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'year' => array('label'=>'Year', 'type'=>'text'),
//                'display-format' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
//                    'buttons-imagebuttons-accolade' => 'Category Buttons - Image Buttons - Accolade',
//                    'buttons-buttons-accolade' => 'Category Buttons - Accolade Buttons - Accolade',
//                    'buttons-list' => 'Buttons - List',
//                    )),
//                'syllabus-page' => array('label'=>'Syllabus Page', 'type'=>'select', 'pages'=>'yes'),
                ),
            );
//        if( count($categories) > 1 ) { 
            $sections['ciniki.musicfestivals.accoladewinners']['settings']['category-id'] = array(
                'label' => 'Category',
                'type' => 'select',
                'complex_options' => array('value'=>'id', 'name'=>'name'),
                'options' => $categories,
                );
//        }
    }

    //
    // Add the location to the website
    //
    $sections['ciniki.musicfestivals.locations'] = array(
        'name' => 'Locations',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'intro' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'no-locations-intro' => array('label'=>'No Locations Intro', 'type'=>'htmlarea'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            'display-format' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                'category-name' => 'Category, Building',
                'category-name-rooms' => 'Category, Building, Room - Discipline',
                'category-disciplines-name' => 'Category, Disciplines, Building - Room',
                )),
            'image-ratio' => array('label' => 'Image Ratio (Image Buttons Only)', 
                'type'=>'select', 
                'default'=>'4-3', 
                'options'=>array(
                    '2-1' => 'Panoramic',
                    '16-9' => 'Letterbox',
                    '6-4' => 'Wider',
                    '4-3' => 'Wide',
                    '1-1' => 'Square',
                    '3-4' => 'Tall',
                    '4-6' => 'Taller',
                )), 
            'button-text' => array('label'=>'Button Text', 'type'=>'text'),
            ),
        );

    //
    // Options for Provincials
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        //
        // Load categories available
        //
        $strsql = "SELECT DISTINCT members.category "
            . "FROM ciniki_musicfestivals_members AS members "
            . "WHERE members.status < 90 "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'category', 'fields'=>array('id'=>'category', 'name'=>'category')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.650', 'msg'=>'Unable to load member categories', 'err'=>$rc['err']));
        }
        $categories = isset($rc['categories']) ? $rc['categories'] : array();

        $sections['ciniki.musicfestivals.members'] = array(
            'name' => 'Members',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                'display-format' => array('label'=>'Display', 'type'=>'toggle', 'default'=>'both', 'toggles'=>array(
                    'alpha' => 'Alphabetical',
                    'categories' => 'Categories',
                    'both' => 'Both',
                    )),
                'display-synopsis' => array('label'=>'Include Synopsis', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
                'display-deadlines' => array('label'=>'Include Deadlines', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    )),
                ),
            );
        array_unshift($categories, array('id'=>0, 'name'=>'None'));
        for($i = 1; $i < 10; $i++) {
            $sections['ciniki.musicfestivals.members']['settings']["category-{$i}"] = array(
                'label' => "Category {$i}", 
                'type' => 'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options' => $categories,
                );
        }

        $sections['ciniki.musicfestivals.recommendations'] = array(
            'name' => 'Recommendation Forms',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                'notify-emails' => array('label'=>'Notify Email', 'type'=>'text'),
                ),
            );

        $sections['ciniki.musicfestivals.memberdeadlines'] = array(
            'name' => 'Entry Deadlines',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                ),
            );

        $sections['ciniki.musicfestivals.ssamchart'] = array(
            'name' => 'SSAM Chart',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                ),
            );
    } 

    if( isset($festival['provincial-festival-id']) && $festival['provincial-festival-id'] > 0 ) {
        //
        // Get the list of provincial orgs
        //
        $strsql = "SELECT tenants.id, "
            . "tenants.name "
            . "FROM ciniki_tenant_modules AS modules "
            . "INNER JOIN ciniki_tenants AS tenants ON ("
                . "modules.tnid = tenants.id "
                . "AND tenants.status = 1 "
                . ") "
            . "WHERE modules.package = 'ciniki' "
            . "AND modules.module = 'musicfestivals' "
            . "AND (modules.flags&0x010000) = 0x010000 "  // Provincials Tenant
            . "ORDER BY tenants.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'tenants', 'fname'=>'id', 
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.853', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
        }
        $provincials = isset($rc['tenants']) ? $rc['tenants'] : array();
        array_unshift($provincials, ['id'=>0, 'name'=>'None']);
        
        $sections['ciniki.musicfestivals.provincialsssamchart'] = array(
            'name' => 'Provincials SSAM Chart',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
                'provincial-tnid' => array('label'=>'Provincials', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$provincials,
                    ),
                ),
            );
    }
    
    $sections['ciniki.musicfestivals.rules'] = array(
        'name' => 'Rules & Regulations',
        'module' => 'Music Festivals',
        'settings' => array(
            'syllabus-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$syllabuses,
                ),
            ),
        );

    $sections['ciniki.musicfestivals.countdown'] = array(
        'name' => 'Countdown',
        'module' => 'Music Festivals',
        'settings' => array(
            'open-title' => array('label'=>'Title', 'type'=>'text'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            'closed-title' => array('label'=>'Closed Title', 'type'=>'text'),
            'closed-content' => array('label'=>'Closed Message', 'type'=>'textarea'),
            ),
        );

    //
    // Approved Titles Lists
    //
    $sections['ciniki.musicfestivals.approvedtitles'] = array(
        'name'=>'Approved Titles',
        'module' => 'Music Festivals',
        'settings'=>array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'htmlarea'),
            'search'=>array('label'=>'Search', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(    
                'no' => 'No',
                'yes' => 'Yes',
                )),
            'download-lists'=>array('label'=>'Download Lists', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(    
                'no' => 'No',
                'yes' => 'Yes',
                )),
            ),
        'repeats' => array(
            'label' => 'Approved Title Lists',
            'headerValues' => array('Lists'),
            'cellClasses' => array(''),
            'dataMaps' => array('list-id'),
            'addTxt' => 'Add List',
            'fields' => array(
                'list-id' => array('label'=>'list', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$titlelists,
                    ),
                )),
        );


    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
