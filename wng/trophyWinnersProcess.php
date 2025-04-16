<?php
//
// Description
// -----------
// This function will generate the blocks to display trophy winners for a year
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_trophyWinnersProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.937', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.938', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();
    $base_url = $request['page']['path'];

    if( !isset($s['year']) || $s['year'] == '' ) {
        return array('stat'=>'ok');
    }

    //
    // 
/*    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) 
        && isset($s['syllabus-page']) && $s['syllabus-page'] > 0 
        ) {
        $strsql = "SELECT settings "
            . "FROM ciniki_wng_sections "
            . "WHERE page_id = '" . ciniki_core_dbQuote($ciniki, $s['syllabus-page']) . "' "
            . "AND ref like '%.%.syllabus' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sequence "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.561', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
        }
        if( isset($rc['section']) ) {
            $settings = json_decode($rc['section']['settings'], true);
            if( isset($settings['syllabus-id']) && preg_match("/^([0-9]+)(\-|$)/", $settings['syllabus-id'], $m) ) {
                $festival_id = $m[1];
            }
            elseif( isset($settings['festival-id']) ) {
                $festival_id = $settings['festival-id'];
            }
        }
    } */


    //
    // Get the list of winners for the year
    //
    $strsql = "SELECT trophies.id, "
        . "trophies.category, "
        . "trophies.name, "
        . "trophies.permalink, "
        . "winners.name AS winner_name "
        . "FROM ciniki_musicfestival_trophies AS trophies "
        . "INNER JOIN ciniki_musicfestival_trophy_winners AS winners ON ("
            . "trophies.id = winners.trophy_id "
            . "AND winners.year = '" . ciniki_core_dbQuote($ciniki, $s['year']) . "' "
            . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($s['typename']) && $s['typename'] != '' && $s['typename'] != 'All' ) {
        $strsql .= "AND trophies.typename = '" . ciniki_core_dbQuote($ciniki, $s['typename']) . "' ";
    }
    $strsql .= "ORDER BY trophies.category, trophies.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('name' => 'category'),
            ),
        array('container'=>'winners', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'winner_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.939', 'msg'=>'Unable to load winners', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    if( $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title', 
            'title' => $s['title'],
            );
    }

    //
    // Display the winners list
    //
    foreach($categories as $category) {
        //
        // Display a table for each category
        //
        $blocks[] = array(
            'type' => 'table',
            'title' => $category['name'],
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Trophy/Award', 'field' => 'name'),
                array('label' => 'Winner', 'field' => 'winner_name'),
                ),
            'rows' => $category['winners'],
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
