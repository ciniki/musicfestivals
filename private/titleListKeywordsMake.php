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
                $strsql = "SELECT id, title, movements, composer, source_type, keywords "
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
        $keywords = strtolower($title['title'] . ' ' . $title['movements'] . ' ' . $title['composer'] . ' ' . $title['source_type']);
    }

    //
    // Generate the new keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');
    $keywords = ciniki_core_makeKeywords($ciniki, $keywords, false);

    //
    // Must have a space at the start which makes for small search sql
    //
    return array('stat'=>'ok', 'keywords'=>' ' . $keywords);
}
?>
