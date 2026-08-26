<?php
//
// Description
// -----------
// Update the keywords for a class
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_titleListKeywordsMake(&$ciniki, $tnid, $args) {

    //
    // Make sure the title is provided or loaded
    //
    if( isset($args['keywords']) ) {
        $keywords = $args['keywords'];
    } else {
        if( !isset($args['title']) ) {
            if( isset($args['title_id']) || isset($args['title']['id']) ) {
                $strsql = "SELECT id, title, opus, movements, musical, composer, arranger, source_type, keywords "
                    . "FROM ciniki_musicfestivals_titles ";
                if( isset($args['title_id']) ) {
                    $strsql .= "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['title_id']) . "' ";
                } else {
                    $strsql .= "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['title']['id']) . "' ";
                }
                $strsql .= "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'title');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1649', 'msg'=>'Unable to load title', 'err'=>$rc['err']));
                }
                if( !isset($rc['title']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1650', 'msg'=>'Unable to find requested title'));
                }
                $title = $rc['title'];
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1651', 'msg'=>'No title specified'));
            }
        } else {
            $title = $args['title'];
        }

        //
        // Build the new keywords
        //
        $keywords = '';
        foreach(['title', 'opus', 'movements', 'musical', 'composer', 'arranger', 'source_type'] as $field) {
            if( isset($title[$field]) ) {
                $keywords .= ($keywords != '' ? ' ' : '') . $title[$field];
            }
        }
        $keywords = strtolower($keywords);
    }

    //
    // Generate a new keywords string. This must be custom and not using the core/makeKeywords. 
    // These keywords should include and, I, I'm , etc as they could easily be used in searching song titles
    // 
    $keywords = preg_replace('/[^a-zA-Z0-9\']/', ' ', $keywords);
    $keywords = preg_replace('/\s\s/', ' ', $keywords);
    $keywords = strtolower($keywords);
    $words = explode(' ', $keywords);

    //
    // Remove 2 letter words, and common words
    //
    foreach($words as $wid => $word) {
        if( strlen($word) > 2 && substr($word, -1) == 's' && substr($word, -2) != 'ss' ) {
            $words[$wid] = rtrim($words[$wid], 's');
        }
    }

    //
    // Sort the words
    //
    sort($words);

    $keywords = implode(' ', array_unique($words));

    //
    // Must have a space at the start which makes for small search sql
    //
    return array('stat'=>'ok', 'keywords'=>' ' . $keywords);
}
?>
