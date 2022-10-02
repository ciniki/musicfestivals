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
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.198', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
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
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$festivals,
                ),
            'layout' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
                'tradingcards' => 'Trading Cards',
                'imagebuttons' => 'Image Buttons',
                )),
            ),
        );

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
            ),
        );

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
                ),
            );
    }

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
