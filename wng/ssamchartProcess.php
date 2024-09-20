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
    // Load the chart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'ssamLoad');
    $rc = ciniki_musicfestivals_ssamLoad($ciniki, $tnid, $s['festival-id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ssam = $rc['ssam'];

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
                        $content .= ($content != '' ? "\n\n" : '') . $item['name'];
                        for($i = 1; $i <= 4; $i++) {
                            if( isset($item["song{$i}"]) && $item["song{$i}"] != '' ) {
                                $content .= "\n- " . $item["song{$i}"];
                            }
                        }
                    }
                }
                if( $content != '' ) {
                    $items[] = [
                        'title' => $category['name'],
                        'collapsed' => 'yes',
                        'content' => $content,
                        ];
                }
            }

            $blocks[] = array(
                'type' => 'accordion',
                'class' => 'section-' . ciniki_core_makePermalink($ciniki, $section['name']),
                'items' => $items,
                );
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
