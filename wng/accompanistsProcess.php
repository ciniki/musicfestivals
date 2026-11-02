<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accompanistsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1717', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1718', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    $items = array();
    for($i = 1; $i < 100; $i++) {
        if( isset($s["name-{$i}"]) && $s["name-{$i}"] != '' ) {
            $content = "";
            if( isset($s["sections-{$i}"]) && $s["sections-{$i}"] != '' ) {
                $content .= "<div class='sections'>(" . $s["sections-{$i}"] . ")</div>";
            }
            if( isset($s["details-{$i}"]) && $s["details-{$i}"] != '' ) {
                $content .= "<div class='details'>" . $s["details-{$i}"] . "</div>";
            }
            if( isset($s["phone-{$i}"]) && $s["phone-{$i}"] != '' ) {
                $content .= "<div class='phone'>" . $s["phone-{$i}"] . "</div>";
            }
            if( isset($s["email-{$i}"]) && $s["email-{$i}"] != '' ) {
                $content .= "<div class='email'>" . $s["email-{$i}"] . "</div>";
            }
            if( isset($s["based-{$i}"]) && $s["based-{$i}"] != '' ) {
                $content .= "<div class='based'>Based in: " . $s["based-{$i}"] . "</div>";
            }
            $items[] = array(
                'title' => $s["name-{$i}"],
                'content' => $content,
                );
        }
    }

    $blocks[] = array(
        'type' => 'text',
        'level' => 1,
        'title' => $s['title'],
        'content' => $s['intro'],
        );

    $blocks[] = array(
        'type' => 'textcards',
        'class' => 'accompanists',
        'items' => $items,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
