<?php
//
// Description
// ===========
// This method will return all the information about an approved title list.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the approved title list is attached to.
// list_id:          The ID of the approved title list to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_titleSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');
    $rc = ciniki_musicfestivals_titleListKeywordsMake($ciniki, $args['tnid'], [
        'keywords' => $args['start_needle'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $args['start_needle']);
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    
    //
    // Search the titles
    $strsql = "SELECT titles.id, "
        . "titles.title, "
        . "titles.opus, "
        . "titles.movements, "
        . "titles.musical, "
        . "titles.composer, "
        . "titles.arranger, "
        . "titles.source_type, "
        . "GROUP_CONCAT(lists.name SEPARATOR ', ') AS lists "
        . "FROM ciniki_musicfestivals_titles AS titles "
        . "INNER JOIN ciniki_musicfestivals_titlelists_titles AS tlt ON ("
            . "titles.id = tlt.title_id "
            . "AND tlt.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestivals_titlelists AS lists ON ("
            . "tlt.list_id = lists.id "
            . "AND lists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE titles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND titles.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
        . "GROUP BY titles.id "
        . "ORDER by title, opus, movements, musical, composer, arranger, source_type, lists.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'titles', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'opus', 'movements', 'musical', 'composer', 'arranger', 'source_type', 'lists')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $titles = isset($rc['titles']) ? $rc['titles'] : array();
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    foreach($titles as $tid => $title) {
        $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $title, '');
        $titles[$tid]['fulltitle'] = $rc['title'];
    }

    return array('stat'=>'ok', 'results'=>$titles);
}
?>
