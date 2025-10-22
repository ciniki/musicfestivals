<?php
//
// Description
// -----------
//

//
// This script should run as www-data and will create the setup for an apache ssl domain
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
ciniki_core_loadMethod($ciniki, 'ciniki', 'cron', 'private', 'logMsg');
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

if( isset($argv[1]) && $argv[1] != '' ) {
    $tnid = $argv[1];
} else {
    print_usage($argv);
}
if( isset($argv[2]) && $argv[2] != '' ) {
    $festival_id = $argv[2];
} else {
    print_usage($argv);
}
if( isset($argv[3]) && $argv[3] != '' ) {
    $start = $argv[3];
} else {
    print_usage($argv);
}
if( isset($argv[4]) && $argv[4] != '' ) {
    $section_inc = $argv[4];
} else {
    print_usage($argv);
}
if( isset($argv[5]) && $argv[5] != '' ) {
    $category_inc = $argv[5];
} else {
    print_usage($argv);
}
$section_id = 0;
if( isset($argv[6]) && $argv[6] != '' ) {
    $section_id = $argv[6];
}
$category_id = 0;
if( isset($argv[7]) && $argv[7] != '' ) {
    $category_id = $argv[7];
}

//
// Load the festival
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
$rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $festival_id);
if( $rc['stat'] != 'ok' ) {
    return $rc;
}
$festival = $rc['festival'];

$code = 10001;

//
// Load syllabus
//
$strsql = "SELECT "
    . "syllabuses.id AS syllabus_id, "
    . "syllabuses.name AS syllabus_name, "
    . "sections.id AS section_id, "
    . "sections.name AS section_name, "
    . "categories.id AS category_id, "
    . "categories.name AS category_name, "
    . "classes.id AS class_id, "
    . "classes.code AS class_code, "
    . "classes.name AS class_name, "
    . "classes.permalink "
    . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
    . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
        . "syllabuses.id = sections.syllabus_id "
        . ($section_id > 0 ? "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $section_id) . "' " : '')
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . ") "
    . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
        . "sections.id = categories.section_id "
        . ($category_id > 0 ? "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $category_id) . "' " : '')
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . ") "
    . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
        . "categories.id = classes.category_id "
        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . ") "
    . "WHERE syllabuses.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
    . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "ORDER BY syllabuses.sequence, syllabuses.name, sections.sequence, sections.name, categories.sequence, categories.name, "
        . "classes.sequence, classes.code, classes.name "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'customer.omfa', array(
    array('container'=>'sections', 'fname'=>'section_id', 
        'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
        ),
    array('container'=>'categories', 'fname'=>'category_id', 
        'fields'=>array('id'=>'category_id', 'name'=>'category_name'),
        ),
    array('container'=>'classes', 'fname'=>'class_id', 
        'fields'=>array('id'=>'class_id', 'code'=>'class_code', 'name'=>'class_name', 'permalink'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'customer.omfa.13', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
}
$sections = isset($rc['sections']) ? $rc['sections'] : array();

$code = $start;
$prev_section_id = '';
foreach($sections as $sid => $section) {
    foreach($section['categories'] as $cid => $category) {
        if( $prev_section_id != '' ) {
            if( $prev_section_id == $sid ) {
                if( $category_inc > 0 ) {
                    $code = ((floor($code/$category_inc) + 1) * $category_inc);
                }
            } else {
                if( $section_inc > 0 ) {
                    $code = ((floor($code/$section_inc) + 1) * $section_inc) + $category_inc;
                }
            }
        }
        foreach($category['classes'] as $clid => $class) {
            $permalink = ciniki_core_makePermalink($ciniki, $code . ' - ' . $class['name']);
            if( $class['code'] != $code || $permalink != $class['permalink'] ) {
                print "    Update {$class['code']} -> {$code} {$class['name']}\n";
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.class', $class['id'], [
                    'code' => $code,
                    'permalink' => $permalink,
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    error_log(print_r($rc,true));
                    exit;
                }  
            }
            $code++;
        }
        $prev_section_id = $sid;
    }
}

print "Done\n";

function print_usage($argv) {
    print "php {$argv[0]} <tnid> <festival_id> <start> <section inc> <category inc> [section_id [category_id]]\n";
    exit;
}
?>
