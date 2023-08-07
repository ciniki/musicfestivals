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
    $s = $section['settings'];
    $blocks = array();
    $base_url = $request['page']['path'];

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT category "
        . "FROM ciniki_musicfestival_trophies "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
            . "trophies.description "
            . "FROM ciniki_musicfestival_trophies AS trophies "
            . "WHERE trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND trophies.permalink = '" . ciniki_core_dbQuote($ciniki, $trophy_permalink) . "' "
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

        $blocks[] = array(
            'type' => 'title',
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
        if( count($winners) > 0 ) {
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


    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
        $category_permalink = urldecode($request['uri_split'][($request['cur_uri_pos']+1)]);

        //
        // Get the trophies for a category
        //
        $strsql = "SELECT id, "
            . "name, "
            . "permalink, "
            . "primary_image_id "
            . "FROM ciniki_musicfestival_trophies "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND category = '" . ciniki_core_dbQuote($ciniki, $category_permalink) . "' "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'trophies', 'fname'=>'permalink', 
                'fields'=>array('id', 'title'=>'name', 'permalink', 'image-id'=>'primary_image_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.343', 'msg'=>'Unable to load trophies', 'err'=>$rc['err']));
        }
        $trophies = isset($rc['trophies']) ? $rc['trophies'] : array();

        foreach($trophies as $tid =>$trophy) {
            $trophies[$tid]['url'] = $base_url . '/' . urlencode($category_permalink) . '/' . urlencode($trophy['permalink']);
            $trophies[$tid]['title-position'] = 'overlay-bottomhalf';
        }

        $blocks[] = array(
            'type' => 'title',
            'level' => 1,
            'class' => 'limit-width limit-width-80',
            'title' => $category_permalink,
            );
        $blocks[] = array(
            'type' => 'imagebuttons',
            'class' => 'limit-width limit-width-80',
            'image-version' => 'original',
            'title-position' => 'overlay-bottomhalf',
            'image-size' => 1024,
            'items' => $trophies,
            );

    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
