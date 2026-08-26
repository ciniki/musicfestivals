<?php
//
// Description
// -----------
// Search the approved titles
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_apiClassTitleSearch(&$ciniki, $tnid, $request) {
   
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
   
    if( !isset($request['args']['s']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1678', 'msg'=>'No search string specified'));
    }
    if( !isset($request['args']['class_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1679', 'msg'=>'No class specified'));
    }
    $class_id = $request['args']['class_id'];

    if( !isset($request['args']['t']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1683', 'msg'=>'No title number flags'));
    }
    $title_num = $request['args']['t'];
    if( !isset($request['args']['f']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1681', 'msg'=>'No class flags'));
    }
    $classflags = $request['args']['f'];
    if( !isset($request['args']['tf']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1682', 'msg'=>'No class flags'));
    }
    $titleflags = $request['args']['tf'];

    //
    // Make sure customer is logged in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1680', 'msg'=>'Not logged in'));
    }

    //
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');
    $rc = ciniki_musicfestivals_titleListKeywordsMake($ciniki, $tnid, [
        'keywords' => $request['args']['s'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $request['args']['s']);
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    $limit = 50;

    //
    // search the titles
    //
    $strsql = "SELECT titles.id, "
        . "titles.list_id, "
        . "titles.fulltitle, "
        . "titles.title, "
        . "titles.opus, "
        . "titles.movements, "
        . "titles.musical, "
        . "titles.composer, "
        . "titles.arranger, "
        . "titles.source_type "
        . "FROM ciniki_musicfestival_class_titlelists AS ctl "
        . "INNER JOIN ciniki_musicfestivals_titlelists AS lists ON ("
            . "ctl.list_id = lists.id "
            . "AND lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestivals_titles AS titles ON ("
            . "lists.id = titles.list_id ";
    if( $keywords != '' ) {
        $strsql .= "AND titles.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' ";
    }
    $strsql .= "AND titles.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ctl.class_id = '" . ciniki_core_dbQuote($ciniki, $class_id) . "' "
        . "AND ctl.title_num = '" . ciniki_core_dbQuote($ciniki, $title_num) . "' "
        . "AND ctl.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY fulltitle "
        . "LIMIT " . ($limit + 1)
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'titles', 'fname'=>'id', 
            'fields'=>array('id', 'list_id', 'fulltitle', 'title', 'opus', 'movements', 'musical', 'composer', 'arranger', 'source_type'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $titles = isset($rc['titles']) ? $rc['titles'] : [];

    foreach($titles as $tid => $title) {
        //
        // Merge fields "left" and empty them if the flags for that field are not enabled for this class
        //
        if( $title['opus'] != '' && ($titleflags&0x0C00) == 0 ) {
            $titles[$tid]['title'] .= ', ' . $title['opus'];
            $titles[$tid]['opus'] = '';
        }
        if( $title['movements'] != '' && ($classflags&0x0C000000) == 0 ) {
            $titles[$tid]['title'] .= ', ' . $title['movements'];
            $titles[$tid]['movements'] = '';
        }
        if( $title['musical'] != '' && ($titleflags&0xC000) == 0 ) {
            if( $title['movements'] != '' && ($classflags&0x0C000000) > 0 ) {
                $titles[$tid]['movements'] .= ', ' . $title['musical'];
            } else {
                $titles[$tid]['title'] .= ', ' . $title['musical'];
            }
            $titles[$tid]['musical'] = '';
        }
        if( $title['composer'] != '' && ($classflags&0x30000000) == 0 ) {
            // Code also in titleMerge
            if( preg_match("/^\s*[Bb][Yy]\s+/", $title["composer"]) ) {
                $composer = ' ' . $title["composer"];
            } elseif( preg_match("/^\s*[Aa][Rr][Rr]\.\s+/", $title["composer"]) ) {    // arr. OR arranged
                $composer = ' ' . $title["composer"];
            } elseif( preg_match("/^\s*[Aa][Rr][Rr]\s+/", $title["composer"]) ) {    // arr. OR arranged
                $composer = ' ' . $title["composer"];
            } elseif( preg_match("/^\s*[Aa][Tt][Tt][Rr]\s+/", $title["composer"]) ) {    // Attr or attributed
                $composer = ' ' . $title["composer"];
            } elseif( preg_match("/^\s*[Aa][Dd][Aa][Pp]\s+/", $title["composer"]) ) {     // Adapted
                $composer = ' ' . $title["composer"];
            } else {
                $composer = ' by ' . $title["composer"];
            }
            $titles[$tid]['title'] .= $composer;
            $titles[$tid]['composer'] = '';
        }
        if( $title['arranger'] != '' && ($titleflags&0x0C0000) == 0 ) {
            if( preg_match("/^\s*[Bb][Yy]\s+/", $title["arranger"]) ) {
                $arranger = ' ' . $title["arranger"];
            } elseif( preg_match("/^\s*[Aa][Rr][Rr]\.\s+/", $title["arranger"]) ) {    // arr. OR arranged
                $arranger = ' ' . $title["arranger"];
            } elseif( preg_match("/^\s*[Aa][Rr][Rr]\s+/", $title["arranger"]) ) {    // arr. OR arranged
                $arranger = ' ' . $title["arranger"];
            } elseif( preg_match("/^\s*[Aa][Tt][Tt][Rr]\s+/", $title["arranger"]) ) {    // Attr or attributed
                $arranger = ' ' . $title["arranger"];
            } elseif( preg_match("/^\s*[Aa][Dd][Aa][Pp]\s+/", $title["arranger"]) ) {     // Adapted
                $arranger = ' ' . $title["arranger"];
            } else {
                $arranger = ', arr. ' . $title["arranger"];
            }
            if( $title['composer'] != '' && ($classflags&0x30000000) > 0 ) {
                $titles[$tid]['composer'] .= $arranger;
            } else {
                $titles[$tid]['title'] .= $arranger;
            }
            $titles[$tid]['arranger'] = '';
        }
    }

    return array('stat'=>'ok', 'titles'=>$titles);
}
?>
