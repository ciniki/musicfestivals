<?php
//
// Description
// -----------
// This function will generate the blocks to display accolade winners for a year
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accoladeWinnersProcess(&$ciniki, $tnid, &$request, $section) {

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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.972', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
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
    if( isset($s['category-id']) && $s['category-id'] == 'All' ) {
        $strsql = "SELECT accolades.id, "
            . "subcategories.name AS subcategory_name, "
            . "accolades.name, "
            . "accolades.permalink, "
            . "IFNULL(registrations.display_name, winners.name) AS winner_name "
            . "FROM ciniki_musicfestival_accolade_subcategories AS subcategories "
            . "INNER JOIN ciniki_musicfestival_accolades AS accolades ON ("
                . "subcategories.id = accolades.subcategory_id "
                . "AND accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_accolade_winners AS winners ON ("
                . "accolades.id = winners.accolade_id "
                . "AND winners.year = '" . ciniki_core_dbQuote($ciniki, $s['year']) . "' "
                . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "winners.registration_id = registrations.id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY subcategories.name, subcategories.sequence, accolades.sequence, accolades.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'subcategories', 'fname'=>'subcategory_name', 
                'fields'=>array('name' => 'subcategory_name'),
                ),
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'permalink', 'winner_name'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1455', 'msg'=>'Unable to load winners', 'err'=>$rc['err']));
        }
        $subcategories = isset($rc['subcategories']) ? $rc['subcategories'] : array();

    } else {
        $strsql = "SELECT winners.id, "
            . "winners.discipline, "
            . "winners.awarded_amount, "
            . "subcategories.id AS subcategory_id, "
            . "subcategories.name AS subcategory_name, "
            . "accolades.name, "
            . "accolades.permalink, "
            . "IFNULL(registrations.display_name, winners.name) AS winner_name "
            . "FROM ciniki_musicfestival_accolade_subcategories AS subcategories "
            . "INNER JOIN ciniki_musicfestival_accolades AS accolades ON ("
                . "subcategories.id = accolades.subcategory_id "
                . "AND accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_accolade_winners AS winners ON ("
                . "accolades.id = winners.accolade_id "
                . "AND winners.year = '" . ciniki_core_dbQuote($ciniki, $s['year']) . "' "
                . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "winners.registration_id = registrations.id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($s['category-id']) && $s['category-id'] != '' && $s['category-id'] > 0 ) {
            $strsql .= "AND subcategories.category_id = '" . ciniki_core_dbQuote($ciniki, $s['category-id']) . "' ";
        }
        if( isset($s['display-format']) && $s['display-format'] == 'discipline-accolade-winner' ) {
            $strsql .= "ORDER BY winners.discipline, accolades.sequence, accolades.name, winner_name ";
        } else {
            $strsql .= "ORDER BY subcategories.sequence, subcategories.name, accolades.sequence, accolades.name, winner_name ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        if( isset($s['display-format']) && $s['display-format'] == 'discipline-accolade-winner' ) {
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'subcategories', 'fname'=>'discipline', 
                    'fields'=>array('id' => 'discipline', 'name' => 'discipline'),
                    ),
                array('container'=>'winners', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'permalink', 'winner_name', 'awarded_amount', 'discipline'),
                    ),
                ));

        } else {
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'subcategories', 'fname'=>'subcategory_id', 
                    'fields'=>array('id' => 'subcategory_id', 'name' => 'subcategory_name'),
                    ),
                array('container'=>'winners', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'permalink', 'winner_name', 'awarded_amount', 'discipline'),
                    ),
                ));
        }
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.939', 'msg'=>'Unable to load winners', 'err'=>$rc['err']));
        }
        $subcategories = isset($rc['subcategories']) ? $rc['subcategories'] : array();
    }

    if( $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title', 
            'title' => $s['title'],
            );
    }

    //
    // Display the winners list
    //
    foreach($subcategories as $subcategory) {
        //
        // Display a table for each category
        //
        $column_name = isset($s['table-header-1']) && $s['table-header-1'] != '' ? $s['table-header-1'] : 'Trophy/Award/Scholarship';
        $columns = [
            ['label' => $column_name, 'field' => 'name'],
            ['label' => 'Recipient', 'field' => 'winner_name'],
            ];
        if( isset($s['discipline']) && $s['discipline'] == 'yes' ) {
            $columns[] = ['label' => 'Discipline', 'field'=>'discipline'];
        }
        if( isset($s['awarded-amount']) && $s['awarded-amount'] == 'yes' ) {
            $columns[] = ['label' => 'Amount', 'field'=>'awarded_amount', 'class'=>'alignright'];
            foreach($subcategory['winners'] as $wid => $winner) {
                $subcategory['winners'][$wid]['awarded_amount'] = '';
                if( $winner['awarded_amount'] > 0 ) {
                    $subcategory['winners'][$wid]['awarded_amount'] = '$' . number_format($winner['awarded_amount'], 0);
                }
            }
        }
        $blocks[] = array(
            'type' => 'table',
            'title' => $subcategory['name'],
            'headers' => 'yes',
            'columns' => $columns,
            'rows' => $subcategory['winners'],
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
