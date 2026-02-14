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
// Get the list of syllabus sections
//
$strsql = "SELECT id, tnid, festival_id, syllabus_id, syllabus "
    . "FROM ciniki_musicfestival_sections "
    . "ORDER BY tnid, festival_id, syllabus "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'festival_id', 'syllabus_id', 'syllabus')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1447', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
}
$sections = isset($rc['sections']) ? $rc['sections'] : array();

$existing = [];

$wngrefs = [];
foreach($sections as $section) {
    if( $section['syllabus_id'] == 0 ) {
        if( $section['syllabus'] == '' ) {
            $section['syllabus'] = 'Syllabus';
        }
        if( !isset($existing[$section['festival_id']][$section['syllabus']]) ) {
            $permalink = ciniki_core_makePermalink($ciniki, $section['syllabus']);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $section['tnid'], 'ciniki.musicfestivals.syllabus', [
                'festival_id' => $section['festival_id'],
                'name' => $section['syllabus'],
                'permalink' => $permalink,
                ], 0x04);
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1443', 'msg'=>'Unable to add the adjudicatorref', 'err'=>$rc['err']));
            }
            if( $rc['stat'] == 'exists' ) {
                error_log(print_r($rc,true));
            }
            $existing[$section['festival_id']][$section['syllabus']] = $rc['id'];
        }
        //
        // Update section with new syllabus id
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $section['tnid'], 'ciniki.musicfestivals.section', $section['id'], [
            'syllabus_id' => $existing[$section['festival_id']][$section['syllabus']],
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1441', 'msg'=>'Unable to update the section', 'err'=>$rc['err']));
        }
        
        // Add what would be old syllabus-id and map to new syllabus_id
        if( !isset($wngrefs[$section['festival_id']]) ) {
            $wngrefs[$section['festival_id']] = $existing[$section['festival_id']][$section['syllabus']];
        }
        if( !isset($wngrefs[$section['festival_id'] . '-' . $section['syllabus']]) ) {
            $wngrefs[$section['festival_id'] . '-' . $section['syllabus']] = $existing[$section['festival_id']][$section['syllabus']];
        }
    }
}

//
// Get the list of wng sections that have syllabus-id
//
$strsql = "SELECT sections.id, "
    . "sections.tnid, "
    . "sections.settings "
    . "FROM ciniki_wng_sections AS sections "
    . "WHERE sections.ref in ('ciniki.musicfestivals.syllabus', 'ciniki.musicfestivals.syllabuspdfs', 'ciniki.musicfestivals.syllabusresults') "
    . "ORDER BY tnid, id "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'settings')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1446', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
}
$wngsections = isset($rc['sections']) ? $rc['sections'] : array();
foreach($wngsections as $sid => $section) {
    $settings = json_decode($section['settings'], true);
    if( isset($settings['syllabus-id']) && isset($wngrefs[$settings['syllabus-id']]) ) {
        $settings['syllabus-id'] = $wngrefs[$settings['syllabus-id']];
        //
        // Update the settings
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $section['tnid'], 'ciniki.wng.section', $section['id'], [
            'settings' => json_encode($settings),
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1442', 'msg'=>'Unable to update the section', 'err'=>$rc['err']));
        }
        
    }
}

?>
