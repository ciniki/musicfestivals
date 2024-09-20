<?php
//
// Description
// -----------
// This function will generate the blocks to display member festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_ssamchartProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.759', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.760', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();
    $base_url = $request['page']['path'];

    
    //
    // Load the festival if not specified
    //
    if( !isset($s['festival-id']) && isset($s['provincial-tnid']) ) {
        $strsql = "SELECT festivals.id, festivals.tnid "
            . "FROM ciniki_musicfestivals AS festivals "
            . "INNER JOIN ciniki_musicfestival_settings AS settings ON ("
                . "festivals.id = settings.festival_id "
                . "AND detail_key = 'content-ssam-chart' "
                . "AND festivals.tnid = settings.tnid "
                . ") "
            . "WHERE festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $s['provincial-tnid']) . "' "
            . "ORDER BY festivals.start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.854', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
        }
        if( !isset($rc['festival']) ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error', 
                'message' => 'No SSAM chart available',
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        $festival = $rc['festival'];

        //
        // Load the chart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'ssamLoad');
        $rc = ciniki_musicfestivals_ssamLoad($ciniki, $festival['tnid'], $festival['id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ssam = $rc['ssam'];

    } else {
        //
        // Load the chart
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'ssamLoad');
        $rc = ciniki_musicfestivals_ssamLoad($ciniki, $tnid, $s['festival-id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ssam = $rc['ssam'];


    }

    //
    // Generate title/content to the ssamchart
    //
    $content = isset($s['content']) ? $s['content'] : '';
    if( (isset($s['title']) && $s['title'] != '') || $content != '' ) {
        $blocks[] = array(
            'type' => ($content == '' ? 'title' : 'text'),
            'title' => isset($s['title']) ? $s['title'] : '',
            'level' => isset($section['sequence']) && $section['sequence'] == 1 ? 1 : 2,
            'content' => $content,
            );
    }

    //
    // 
    if( isset($ssam['sections']) ) {
        foreach($ssam['sections'] as $section) {
            $content = isset($section['content']) ? $section['content'] : '';
            $blocks[] = array(
                'type' => ($content == '' ? 'title' : 'text'),
                'title' => $section['name'],
                'level' => 2,
                'content' => $content,
                );

            $items = [];
            foreach($section['categories'] as $category) {
                $content = '';
                if( isset($category['items']) ) {
                    foreach($category['items'] as $item) {
                        $content .= "<div class='musicfestival-ssam-chart-title'><span class='musicfestival-ssam-chart-title'>" . $item['name'] . "</span>";
                        $songs = '';
                        for($i = 1; $i <= 4; $i++) {
                            if( isset($item["song{$i}"]) && $item["song{$i}"] != '' ) {
                                $songs .= "<li class='musicfestival-ssam-chart-song'>" . $item["song{$i}"] . "</li>";
                            }
                        }
                        if( $songs != '' ) {
                            $content .= "<ul class='musicfestival-ssam-chart-songs'>" . $songs . "</ul>";
                        }
                        $content .= "</div>";
                    }
                }
                if( $content != '' ) {
                    $items[] = [
                        'title' => $category['name'],
                        'collapsed' => 'yes',
                        'html' => $content,
                        ];
                }
            }

            $blocks[] = array(
                'type' => 'accordion',
                'class' => 'musicfestival-ssam-chart',
                'items' => $items,
                );
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
