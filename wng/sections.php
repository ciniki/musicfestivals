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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'festivals', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.209', 'msg'=>'Unable to load the list of festivals', 'err'=>$rc['err']));
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
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 'options'=>$festivals),
            'format' => array('label'=>'Format', 'type'=>'select', 'options'=>array(
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
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 'options'=>$festivals),
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
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 'options'=>$festivals),
            ),
        );

    //
    // Section to display the sponsors for a festival
    //
    $sections['ciniki.musicfestivals.sponsors'] = array(
        'name' => 'Sponsors',
        'module' => 'Music Festivals',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'festival-id' => array('label'=>'Festival', 'type'=>'select', 'options'=>$festivals),
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


    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
