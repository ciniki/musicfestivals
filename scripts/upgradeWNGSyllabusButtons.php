<?php
//
// Description
// -----------
// This script will setup the timeslot_id for each registration even
// when NOT a split class.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');


//
// Get the list of WNG sections that are for the syllabus
//
$strsql = "SELECT id, tnid, settings "
    . "FROM ciniki_wng_sections AS sections "
    . "WHERE ref = 'ciniki.musicfestivals.syllabus' "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'settings')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1084', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
}
$sections = isset($rc['sections']) ? $rc['sections'] : array();

foreach($sections as $section) {
    
    $settings = json_decode($section['settings'], true);

    if( isset($settings['syllabus-pdf']) ) {
        if( $settings['syllabus-pdf'] == 'top' || $settings['syllabus-pdf'] == 'both' ) {
            $settings["syllabus-top-button-1-pdf"] = 'yes';
        }
        if( $settings['syllabus-pdf'] == 'bottom' || $settings['syllabus-pdf'] == 'both' ) {
            $settings["syllabus-bottom-button-1-pdf"] = 'yes';
        }
        unset($settings["syllabus-pdf"]);
    }

    foreach(['top', 'bottom'] as $tp) {
        foreach(['button-1-pdf', 'button-2-page', 'button-2-text', 'button-2-url'] as $field) {
            if( isset($settings["{$tp}-{$field}"]) ) {
                $settings["section-{$tp}-{$field}"] = $settings["{$tp}-{$field}"];
                unset($settings["{$tp}-{$field}"]);
            }
        }
    }
    $encoded_settings = json_encode($settings);
    if( $encoded_settings != $section['settings'] ) {
        print_r($settings);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $section['tnid'], 'ciniki.wng.section', $section['id'], [
            'settings' => $encoded_settings,
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1085', 'msg'=>'Unable to update the section', 'err'=>$rc['err']));
        } 
    }
}

?>
