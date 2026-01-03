<?php
//
// Description
// -----------
// This script will convert the fields typename and category in ciniki_musicfestival_trophies 
// into trophy_categories and trophy_subcategories.
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
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

//
// Get the list of tenants that have trophies
//
$strsql = "SELECT DISTINCT tnid "
    . "FROM ciniki_musicfestival_trophies "
    . "ORDER BY tnid "
    . "";
$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.musicfestivals', 'tnids', 'tnid');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1094', 'msg'=>'Unable to load the list of tnid', 'err'=>$rc['err']));
}
$tnids = isset($rc['tnids']) ? $rc['tnids'] : array();

foreach($tnids as $tnid) {
    $strsql = "SELECT name "
        . "FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1098', 'msg'=>'Unable to load tenant', 'err'=>$rc['err']));
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1099', 'msg'=>'Unable to find requested tenant'));
    }
    $tenant = $rc['tenant'];
    
    error_log("processing: $tnid - {$tenant['name']}\n");
    //
    // Get the trophies for a tenant
    //
    $strsql = "SELECT id, "
        . "name, "
        . "subcategory_id, "
        . "typename, "
        . "category "
        . "FROM ciniki_musicfestival_trophies AS trophies "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY typename, category, name, id "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'trophies', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'subcategory_id', 'typename', 'category'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1095', 'msg'=>'Unable to load trophies', 'err'=>$rc['err']));
    }
    $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();

    //
    // Update each trophy
    //
    $categories = [];
    $cat_seq = 1;
    $subcat_seq = 1;
    foreach($trophies as $trophy) {
        //
        // Create category if it does not exist
        //
        if( !isset($categories[$trophy['typename']]) ) {
            error_log("  {$trophy['typename']}");
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.trophycategory', [
                'name' => $trophy['typename'],
                'permalink' => ciniki_core_makePermalink($ciniki, $trophy['typename']),
                'sequence' => $cat_seq,
                'flags' => 0x03,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                error_log(print_r($rc,true));
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1316', 'msg'=>'Unable to add the trophy category', 'err'=>$rc['err']));
            }
            $categories[$trophy['typename']] = [
                'id' => $rc['id'],
                'subcategories' => [],
                ];
            $cat_seq++;
            $subcat_seq = 1;
        }

        //
        // Create the subcategory if it does not exist
        //
        if( !isset($categories[$trophy['typename']]['subcategories'][$trophy['category']]['id']) ) {
            error_log("    {$trophy['category']}");
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.trophysubcategory', [
                'category_id' => $categories[$trophy['typename']]['id'],
                'name' => $trophy['category'],
                'permalink' => ciniki_core_makePermalink($ciniki, $trophy['category']),
                'sequence' => $subcat_seq,
                'flags' => 0x01,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                error_log(print_r($rc,true));
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1096', 'msg'=>'Unable to add the trophy category', 'err'=>$rc['err']));
            }
            $categories[$trophy['typename']]['subcategories'][$trophy['category']] = [
                'id' => $rc['id'],
                ];
            $subcategory_id = $rc['id'];
            $subcat_seq++;
        } else {
            $subcategory_id = $categories[$trophy['typename']]['subcategories'][$trophy['category']]['id'];
        }

        //
        // Update the trophy
        //
        if( $subcategory_id != $trophy['subcategory_id'] ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.trophy', $trophy['id'], [
                'subcategory_id' => $subcategory_id, 
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                error_log(print_r($rc,true));
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1317', 'msg'=>'Unable to update the trophy', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Upgrade the wng sections
    //
    $strsql = "SELECT id, ref, settings "
        . "FROM ciniki_wng_sections "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ref like 'ciniki.musicfestivals.troph%' "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'ref', 'settings')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1121', 'msg'=>'Unable to load WNG sections', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    foreach($sections as $section) {
        $settings = json_decode($section['settings'], true);
        if( isset($settings['typename']) ) {
            if( $settings['typename'] == 'All' ) {
                $settings['category-id'] = 0;
                unset($settings['typename']);
            }
            elseif( isset($categories[$settings['typename']]) ) {
                $settings['category-id'] = $categories[$settings['typename']]['id'];
                unset($settings['typename']);
            }
        }
        $json_settings = json_encode($settings);
        if( $json_settings != $section['settings'] ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.wng.section', $section['id'], [
                'settings' => $json_settings, 
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                error_log(print_r($rc,true));
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1097', 'msg'=>'Unable to update the trophy', 'err'=>$rc['err']));
            }
        }
    }

}





?>
