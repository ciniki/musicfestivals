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
    $strsql = "SELECT id, name "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.351', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
    }
    $festivals = isset($rc['festivals']) ? $rc['festivals'] : array();

    //
    // Section to display the syllabus
    //
    $sections['ciniki.musicfestivals.syllabus'] = array(
        'name' => 'Syllabus',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'textarea'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            'layout' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                'tradingcards' => 'Trading Cards',
                'imagebuttons' => 'Image Buttons',
                'buttons' => 'Buttons',
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
            'section-pdf' => array('label'=>'Section PDF Download', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Off',
                'top' => 'Top',
                'bottom' => 'Bottom',
                'both' => 'Both',
                )),
            'syllabus-pdf' => array('label'=>'Complete Syllabus PDF Download', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Off',
                'top' => 'Top',
                'bottom' => 'Bottom',
                'both' => 'Both',
                )),
            ),
        );
    
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x4000) ) {
        $sections['ciniki.musicfestivals.syllabus']['settings']['display-live-virtual'] = array(
            'label'=>'Classes', 
            'type'=>'toggle', 'default'=>'all', 'toggles'=>array(
                'all' => 'All',
                'live' => 'Live',
                'virtual' => 'Virtual',
                ));
    }

    //
    // Section to display the file download for a festival
    //
    $sections['ciniki.musicfestivals.files'] = array(
        'name' => 'Files',
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
            'layout' => array('label'=>'Format', 'type'=>'toggle', 'default'=>'contentphoto', 
                'toggles'=>array('contentphoto'=>'Bio & Photos', 'imagebutton'=>'Image Buttons'),
                ),
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
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x4000) ) {
        $sections['ciniki.musicfestivals.adjudicators']['settings']['display-live-virtual'] = array(
            'label'=>'Classes', 
            'type'=>'toggle', 'default'=>'all', 'toggles'=>array(
                'all' => 'All',
                'live' => 'Live',
                'virtual' => 'Virtual',
                ));
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
                ),
            );
    }

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
        $sections['ciniki.musicfestivals.sponsors'] = array(
            'name' => 'Sponsors',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'medium', 'toggles'=>array(    
                    'xsmall' => 'X-Small',
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                    'xlarge' => 'X-Large',
                    )),
                'level' => array('label'=>'Sponsor Level', 'type'=>'toggle', 'default'=>'1', 'toggles'=>array('1'=>'1', '2'=>'2')),
                ),
            );
    }

    //
    // Section to display trophies
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) ) {
        $sections['ciniki.musicfestivals.trophies'] = array(
            'name' => 'Trophies',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'syllabus-page' => array('label'=>'Syllabus Page', 'type'=>'select', 'pages'=>'yes'),
                ),
            );
    }

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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.351', 'msg'=>'Unable to load member categories', 'err'=>$rc['err']));
        }
        $categories = isset($rc['categories']) ? $rc['categories'] : array();

        $sections['ciniki.musicfestivals.members'] = array(
            'name' => 'Members',
            'module' => 'Music Festivals',
            'settings' => array(
                'title' => array('label'=>'Title', 'type'=>'text'),
                'content' => array('label'=>'Intro', 'type'=>'textarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                'display-format' => array('label'=>'Display', 'type'=>'toggle', 'default'=>'both', 'toggles'=>array(
                    'alpha' => 'Alphabetical',
                    'categories' => 'Categories',
                    'both' => 'Both',
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
                'content' => array('label'=>'Intro', 'type'=>'textarea'),
                'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$festivals,
                    ),
                'notify-emails' => array('label'=>'Notify Email', 'type'=>'text'),
                ),
            );
    }

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
