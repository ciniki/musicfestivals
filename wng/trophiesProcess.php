<?php
//
// Description
// -----------
// This function will generate the blocks to display trophies
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_trophiesProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.363', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.364', 'msg'=>"No festival specified"));
    }
    $itemtype = 10;
    if( isset($section['itemtype']) ) {
        $itemtype = $section['itemtype'];
    }
    $s = $section['settings'];
    $blocks = array();
    $base_url = $request['page']['path'];

    //
    // 
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) 
        && isset($s['syllabus-page']) && $s['syllabus-page'] > 0 
        ) {
        $strsql = "SELECT settings "
            . "FROM ciniki_wng_sections "
            . "WHERE page_id = '" . ciniki_core_dbQuote($ciniki, $s['syllabus-page']) . "' "
            . "AND ref = 'ciniki.musicfestivals.syllabus' "
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
    }

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT category "
        . "FROM ciniki_musicfestival_trophies "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND itemtype = '" . ciniki_core_dbQuote($ciniki, $itemtype) . "' "
        . "ORDER BY category "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.musicfestivals', 'categories', 'category');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.365', 'msg'=>'Unable to load the list of ', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
   
    $buttons = array();
    foreach($categories as $category) {
        $buttons[] = array(
            'text' => $category,
            'url' => $base_url . '/' . urlencode($category),
            );
    }
    if( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title',
            'title' => $s['title'],
            );
    }
    $blocks[] = array(
        'type' => 'buttons',
        'class' => 'musicfestival-trophy-categories aligncenter',
        'list' => $buttons,
        );

    if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) ) {
        $category_permalink = urldecode($request['uri_split'][($request['cur_uri_pos']+1)]);
        $trophy_permalink = urldecode($request['uri_split'][($request['cur_uri_pos']+2)]);
        
        //
        // Get the trophies for a category
        //
        $strsql = "SELECT id, "
            . "name, "
            . "permalink "
            . "FROM ciniki_musicfestival_trophies "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND category = '" . ciniki_core_dbQuote($ciniki, $category_permalink) . "' "
            . "AND itemtype = '" . ciniki_core_dbQuote($ciniki, $itemtype) . "' "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'trophies', 'fname'=>'permalink', 
                'fields'=>array('id', 'title'=>'name', 'permalink')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.366', 'msg'=>'Unable to load trophies', 'err'=>$rc['err']));
        }
        $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();

        //
        // Get the trophy details
        //
        $strsql = "SELECT trophies.id, "
            . "trophies.name, "
            . "trophies.category, "
            . "trophies.primary_image_id, "
            . "trophies.donated_by, "
            . "trophies.first_presented, "
            . "trophies.criteria, "
            . "trophies.amount, "
            . "trophies.description "
            . "FROM ciniki_musicfestival_trophies AS trophies "
            . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND trophies.permalink = '" . ciniki_core_dbQuote($ciniki, $trophy_permalink) . "' "
            . "AND trophies.itemtype = '" . ciniki_core_dbQuote($ciniki, $itemtype) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'trophy');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.367', 'msg'=>'Unable to load trophy', 'err'=>$rc['err']));
        }
        if( !isset($rc['trophy']) ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => 'limit-width limit-width-80',
                'level' => 'error',
                'content' => 'Trophy not found',
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        $trophy = $rc['trophy'];
        $trophy['full_description'] = '';
        if( $trophy['donated_by'] != '' ) {
            $trophy['full_description'] .= '<b>Donated By:</b> ' . $trophy['donated_by'] . '<br/>';
        }
        if( $trophy['first_presented'] != '' ) {
            $trophy['full_description'] .= '<b>First Presented:</b> ' . $trophy['first_presented'] . '<br/>';
        }
        if( $trophy['criteria'] != '' ) {
            $trophy['full_description'] .= '<b>Criteria:</b> ' . $trophy['criteria'] . '<br/>';
        }
        if( $trophy['amount'] != '' ) {
            $trophy['full_description'] .= '<b>Amount:</b> ' . $trophy['amount'] . '<br/>';
        }
        if( $trophy['description'] != '' ) {
            if( $trophy['full_description'] != '' ) {   
                $trophy['full_description'] .= '<br/>';
            }
            $trophy['full_description'] .= $trophy['description'];
        }

        //
        // Get the list of winners
        //
        $strsql = "SELECT ciniki_musicfestival_trophy_winners.id, "
            . "ciniki_musicfestival_trophy_winners.trophy_id, "
            . "ciniki_musicfestival_trophy_winners.name, "
            . "ciniki_musicfestival_trophy_winners.year "
            . "FROM ciniki_musicfestival_trophy_winners "
            . "WHERE trophy_id = '" . ciniki_core_dbQuote($ciniki, $trophy['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY year DESC, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'year')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $winners = isset($rc['winners']) ? $rc['winners'] : array();

        //
        // Get the list of classes
        //
        if( isset($festival_id) && $festival_id > 0 ) {
            $strsql = "SELECT classes.id, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
                . "categories.name AS category_name, "
                . "sections.name AS section_name "
                . "FROM ciniki_musicfestival_trophy_classes AS tc "
                . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "tc.class_id = classes.id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "categories.section_id = sections.id "
                    . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE tc.trophy_id = '" . ciniki_core_dbQuote($ciniki, $trophy['id']) . "' "
                . "AND tc.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY sections.sequence, section_name, categories.sequence, category_name, "
                    . "classes.sequence, class_name, class_code "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 
                    'fields'=>array('id', 'class_code', 'class_name', 'category_name', 'section_name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $classes = isset($rc['classes']) ? $rc['classes'] : array();
        }

        $blocks[] = array(
            'type' => 'title',
            'level' => 2,
            'class' => 'limit-width limit-width-80',
            'title' => $category_permalink . ' - ' . $trophy['name'],
            );
/*        $blocks[] = array(
            'type' => 'contentphoto',
            'class' => 'content-aligntop limit-width limit-width-80',
            'image-id' => $trophy['primary_image_id'],
            'content' => $trophy['full_description'],
            ); */
        $blocks[] = array(
            'type' => 'asideimage',
            'image-id' => $trophy['primary_image_id'],
            );
        $blocks[] = array(
            'type' => 'text',
            'class' => 'content-aligntop limit-width limit-width-80',
            'content' => $trophy['full_description'],
            );
        if( isset($winners) && count($winners) > 0 ) {
            $blocks[] = array(
                'type' => 'table',
                'title' => 'Winners',
                'class' => 'fit-width musicfestival-trophy-winners',
                'headers' => 'no',
                'columns' => array(
                    array('label' => '', 'field'=>'year'),
                    array('label' => '', 'field'=>'name'),
                    ),
                'rows' => $winners,
                );
        }
        if( isset($classes) && count($classes) > 0 ) {
            $blocks[] = array(
                'type' => 'table', 
                'title' => 'Eligible Classes', 
                'class' => 'fit-width musicfestival-trophy-classes',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Section', 'field'=>'section_name'),
                    array('label' => 'Category', 'field'=>'category_name'),
                    array('label' => 'Code', 'field'=>'class_code'),
                    array('label' => 'Class', 'field'=>'class_name'),
                    ),
                'rows' => $classes,
                );
        }
        
        //
        // Add prev/next buttons
        //
        if( count($trophies) > 1 ) {
            $first_trophy = null;
            $last_trophy = null;
            foreach($trophies as $trophy) {
                if( $first_trophy == null ) {
                    $first_trophy = $trophy;
                }
                if( $last_trophy != null && $trophy['permalink'] == $trophy_permalink ) {
                    $prev = $last_trophy;
                }
                if( $last_trophy != null && $last_trophy['permalink'] == $trophy_permalink ) {
                    $next = $trophy;
                }
                $last_trophy = $trophy;
            }
            if( !isset($next) ) {
                $next = $first_trophy;
            }
            if( !isset($prev) ) {
                $prev = $last_trophy;
            }
            if( isset($next) && isset($prev) ) {
                $blocks[] = array(
                    'type' => 'buttons',
                    'class' => 'aligncenter',
                    'list' => array(
                        array('text' => 'Previous', 'url' => $base_url . '/' . $category_permalink . '/' . $prev['permalink']),
                        array('text' => 'Next', 'url' => $base_url . '/' . $category_permalink . '/' . $next['permalink']),
                        ),
                    );
            }
        }
        return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');


    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
        $category_permalink = urldecode($request['uri_split'][($request['cur_uri_pos']+1)]);

        //
        // Get the trophies for a category
        //
        $strsql = "SELECT id, "
            . "name, "
            . "permalink, "
            . "primary_image_id, "
            . "donated_by, "
            . "first_presented, "
            . "criteria, "
            . "amount, "
            . "description "
            . "FROM ciniki_musicfestival_trophies "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND category = '" . ciniki_core_dbQuote($ciniki, $category_permalink) . "' "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'trophies', 'fname'=>'permalink', 
                'fields'=>array('id', 'title'=>'name', 'permalink', 'image-id'=>'primary_image_id',
                    'donated_by', 'first_presented', 'criteria', 'amount', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.343', 'msg'=>'Unable to load trophies', 'err'=>$rc['err']));
        }
        $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();

        foreach($trophies as $tid =>$trophy) {
            $trophies[$tid]['url'] = $base_url . '/' . urlencode($category_permalink) . '/' . urlencode($trophy['permalink']);
            $trophies[$tid]['title-position'] = 'overlay-bottomhalf';

            if( isset($s['display-format']) && $s['display-format'] == 'buttons-list' ) {
                $trophies[$tid]['full_description'] = '';
                if( $trophy['donated_by'] != '' ) {
                    $trophies[$tid]['full_description'] .= '<b>Donated By:</b> ' . $trophy['donated_by'] . '<br/>';
                }
                if( $trophy['first_presented'] != '' ) {
                    $trophies[$tid]['full_description'] .= '<b>First Presented:</b> ' . $trophy['first_presented'] . '<br/>';
                }
                if( $trophy['criteria'] != '' ) {
                    $trophies[$tid]['full_description'] .= '<b>Criteria:</b> ' . $trophy['criteria'] . '<br/>';
                }
                if( $trophy['amount'] != '' ) {
                    $trophies[$tid]['full_description'] .= '<b>Amount:</b> ' . $trophy['amount'] . '<br/>';
                }
                if( $trophy['description'] != '' ) {
                    if( $trophies[$tid]['full_description'] != '' ) {   
                        $trophies[$tid]['full_description'] .= '<br/>';
                    }
                    $trophies[$tid]['full_description'] .= $trophy['description'];
                }
            }
        }

        $blocks[] = array(
            'type' => 'title',
            'level' => 1,
            'title' => $category_permalink,
            );
        if( isset($s['display-format']) && $s['display-format'] == 'buttons-list' ) {
            foreach($trophies as $trophy) {
                $blocks[] = array(
                    'type' => 'contentphoto',
                    'image-position' => 'top-right-inline',
                    'image-size' => 'large',
                    'title' => $trophy['title'],
                    'image-id' => $trophy['image-id'],
                    'content' => $trophy['full_description'],
                    );
            }
        } else {
            $blocks[] = array(
                'type' => 'imagebuttons',
                'image-version' => 'original',
                'title-position' => 'overlay-bottomhalf',
                'image-size' => 1024,
                'items' => $trophies,
                );
        }

        return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
