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
function ciniki_musicfestivals_classKeywordsMake(&$ciniki, $tnid, $args) {

    //
    // Make sure the class is provided or loaded
    //
    if( isset($args['keywords']) ) {
        $keywords = $args['keywords'];
    } else {
        if( !isset($args['class']) || !isset($args['class']['keywords']) ) {
            if( isset($args['class_id']) || isset($args['class']['id']) ) {
                $strsql = "SELECT category_id, code, name, synopsis, keywords "
                    . "FROM ciniki_musicfestival_classes ";
                if( isset($args['class_id']) ) {
                    $strsql .= "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
                } else {
                    $strsql .= "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['class']['id']) . "' ";
                }
                $strsql .= "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.811', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
                }
                if( !isset($rc['class']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.812', 'msg'=>'Unable to find requested class'));
                }
                $class = $rc['class'];
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.813', 'msg'=>'No class specified'));
            }
        } else {
            $class = $args['class'];
        }

        //
        // Make sure the category is provided or loaded
        //
        if( !isset($args['category']) ) {
            if( isset($class['category_id']) ) {
                $strsql = "SELECT section_id, name "
                    . "FROM ciniki_musicfestival_categories "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $class['category_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'category');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.814', 'msg'=>'Unable to load category', 'err'=>$rc['err']));
                }
                if( !isset($rc['category']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.815', 'msg'=>'Unable to find requested category'));
                }
                $category = $rc['category'];
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.816', 'msg'=>'No class category'));
            }
        } else {
            $category = $args['category'];
        }

        //
        // Make sure the section is provided or loaded
        //
        if( !isset($args['section']) ) {
            if( isset($category['section_id']) ) {
                $strsql = "SELECT name "
                    . "FROM ciniki_musicfestival_sections "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $category['section_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.817', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
                }
                if( !isset($rc['section']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.818', 'msg'=>'Unable to find requested section'));
                }
                $section = $rc['section'];
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.819', 'msg'=>'No class section'));
            }
        } else {
            $section = $args['section'];
        }


        //
        // Build the new keywords
        //
        $keywords = strtolower($class['code'] . ' ' . $section['name'] . ' ' . $category['name'] . ' ' . $class['name']);
        // Removed synopsis because it had too many weird numbers words that aren't needed in keywords
        // . ' ' . $class['synopsis']);
        $keywords = preg_replace("/(Levels\s+[0-9]+\s*\-[0-9]+)(.*Level\s+[0-9])/i", "$2", $keywords);
    }
  
    //
    // Setup special search cases 
    //
    if( preg_match("/(grades?\/level|grade|level|age)s?\s+([0-9]+)\s*\-\s*([0-9]+)/", $keywords, $m) ) {
        $k = '';
        for($i = $m[2]; $i <= $m[3]; $i++) {
            if( $m[1] == 'grade/level' || $m[1] == 'grades/levels' ) {
                $k .= " grade-{$i} level-{$i}";
            } else {
                $k .= " {$m[1]}-{$i}";
            }
        }
        $keywords = preg_replace("/(grades?\/level|grades?|levels?|ages?)\s+([0-9]+)\s*\-\s*([0-9]+)/", $k, $keywords);
    }
    $keywords = preg_replace("/list (a|b|c|d|e)/i", "list-$1", $keywords);
    $keywords = preg_replace("/Grade\/Level (1|2|3|4|5|6|7|8|9|10)/i", "grade-$1 level-$1", $keywords);
    $keywords = preg_replace("/Grade (1|2|3|4|5|6|7|8|9|10)/i", "grade-$1", $keywords);
    $keywords = preg_replace("/Level (1|2|3|4|5|6|7|8|9|10|A|B)/i", "level-$1", $keywords);
    $keywords = preg_replace("/ages? ([0-9]+)/i", "age-$1", $keywords);
    $keywords = preg_replace("/\//", " ", $keywords); // words joined by slash separate so separately indexed

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');
    $keywords = ciniki_core_makeKeywords($ciniki, $keywords, false, ['allow-dashes'=>'yes']);
    $keywords = preg_replace("/^\- /", '', $keywords);

    //
    // Must have a space at the start which makes for small search sql
    //
    return array('stat'=>'ok', 'keywords'=>' ' . $keywords);
}
?>
