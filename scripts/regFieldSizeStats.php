<?php
//
// Description
// -----------
// This script will upgrade the titles to be a single title, and other information into title pieces
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
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

//
// Get the list of timeslots and their registrations
//
$strsql = "SELECT id, "
    . "tnid, "
    . "max(length(title1)) AS title1_max, "
    . "max(length(title2)) AS title2_max, "
    . "max(length(title3)) AS title3_max, "
    . "max(length(title4)) AS title4_max, "
    . "max(length(title5)) AS title5_max, "
    . "max(length(title6)) AS title6_max, "
    . "max(length(title7)) AS title7_max, "
    . "max(length(title8)) AS title8_max, "
    . "max(length(movements1)) AS movements1_max, "
    . "max(length(movements2)) AS movements2_max, "
    . "max(length(movements3)) AS movements3_max, "
    . "max(length(movements4)) AS movements4_max, "
    . "max(length(movements5)) AS movements5_max, "
    . "max(length(movements6)) AS movements6_max, "
    . "max(length(movements7)) AS movements7_max, "
    . "max(length(movements8)) AS movements8_max, "
    . "max(length(composer1)) AS composer1_max, "
    . "max(length(composer2)) AS composer2_max, "
    . "max(length(composer3)) AS composer3_max, "
    . "max(length(composer4)) AS composer4_max, "
    . "max(length(composer5)) AS composer5_max, "
    . "max(length(composer6)) AS composer6_max, "
    . "max(length(composer7)) AS composer7_max, "
    . "max(length(composer8)) AS composer8_max, "
    . "max(length(video_url1)) AS video_url1_max, "
    . "max(length(video_url2)) AS video_url2_max, "
    . "max(length(video_url3)) AS video_url3_max, "
    . "max(length(video_url4)) AS video_url4_max, "
    . "max(length(video_url5)) AS video_url5_max, "
    . "max(length(video_url6)) AS video_url6_max, "
    . "max(length(video_url7)) AS video_url7_max, "
    . "max(length(video_url8)) AS video_url8_max, "
    . "max(length(music_orgfilename1)) AS music_orgfilename1_max, "
    . "max(length(music_orgfilename2)) AS music_orgfilename2_max, "
    . "max(length(music_orgfilename3)) AS music_orgfilename3_max, "
    . "max(length(music_orgfilename4)) AS music_orgfilename4_max, "
    . "max(length(music_orgfilename5)) AS music_orgfilename5_max, "
    . "max(length(music_orgfilename6)) AS music_orgfilename6_max, "
    . "max(length(music_orgfilename7)) AS music_orgfilename7_max, "
    . "max(length(music_orgfilename8)) AS music_orgfilename8_max, "
    . "max(length(backtrack1)) AS backtrack1_max, "
    . "max(length(backtrack2)) AS backtrack2_max, "
    . "max(length(backtrack3)) AS backtrack3_max, "
    . "max(length(backtrack4)) AS backtrack4_max, "
    . "max(length(backtrack5)) AS backtrack5_max, "
    . "max(length(backtrack6)) AS backtrack6_max, "
    . "max(length(backtrack7)) AS backtrack7_max, "
    . "max(length(backtrack8)) AS backtrack8_max, "
    . "max(length(artwork1)) AS artwork1_max, "
    . "max(length(artwork2)) AS artwork2_max, "
    . "max(length(artwork3)) AS artwork3_max, "
    . "max(length(artwork4)) AS artwork4_max, "
    . "max(length(artwork5)) AS artwork5_max, "
    . "max(length(artwork6)) AS artwork6_max, "
    . "max(length(artwork7)) AS artwork7_max, "
    . "max(length(artwork8)) AS artwork8_max "
    . "FROM ciniki_musicfestival_registrations AS registrations "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'reg');
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}
    error_log(print_r($rc,true));




?>
