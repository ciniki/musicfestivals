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
function ciniki_musicfestivals_wng_sponsorsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.227', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.228', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.229', 'msg'=>"No festival specified"));
    }

    //
    // Load the sponsors
    //
    if( isset($s['tag']) && $s['tag'] != '' ) {
        $strsql = "SELECT sponsors.id, "
            . "sponsors.name, "
            . "sponsors.image_id, "
            . "sponsors.url, "
            . "sponsors.description "
            . "FROM ciniki_musicfestival_sponsor_tags AS tags "
            . "INNER JOIN ciniki_musicfestival_sponsors AS sponsors ON ("
                . "tags.sponsor_id = sponsors.id "
                . "AND sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
                . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $s['tag']) . "' "
            . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sponsors.sequence, sponsors.name "
            . "";
    } else {
        // **deprecated** will be going away
        $strsql = "SELECT sponsors.id, "
            . "sponsors.name, "
            . "sponsors.image_id, "
            . "sponsors.url, "
            . "sponsors.description "
            . "FROM ciniki_musicfestival_sponsors AS sponsors "
            . "WHERE sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
        if( isset($s['level']) && $s['level'] == 1 ) {
            $strsql .= "AND (sponsors.flags&0x01) = 0x01 ";
        } elseif( isset($s['level']) && $s['level'] == 2 ) {
            $strsql .= "AND (sponsors.flags&0x02) = 0x02 ";
        } elseif( isset($s['level']) && $s['level'] == 3 ) {
            $strsql .= "AND (sponsors.flags&0x03) = 0x03 ";
        } 
        $strsql .= "ORDER BY sponsors.sequence, sponsors.name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sponsors', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'title'=>'name', 'image-id'=>'image_id', 'url', 'description'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.250', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();

    if( count($sponsors) > 0 ) {
        //
        // Add the title block
        //
        if( isset($s['title']) && $s['title'] != '' ) {
            $blocks[] = array(
                'type' => 'title', 
                'level' => 2,
                'class' => 'sponsors',
                'title' => $s['title'],
                );
        }

        //
        // Display as a content photo list
        //
        if( isset($s['display-format']) && $s['display-format'] == 'contentphoto' ) {
            foreach($sponsors as $sponsor) {
                $block = array(
                    'type' => 'contentphoto',
                    'title' => $sponsor['name'],
                    'image-id' => $sponsor['image-id'],
                    'content' => $sponsor['description'],
                    'image-size' => $s['image-size'],
                    'image-position' => 'top-left',
                    );
                if( $block['image-size'] == 'xsmall' ) {
                    $block['image-size'] = 'tiny';
                }
                if( $block['image-size'] == 'xlarge' ) {
                    $block['image-size'] = 'half';
                }
                if( $sponsor['url'] != '' ) {
                    $block['button-1-text'] = 'Visit Website';
                    $block['button-1-url'] = $sponsor['url'];
                }
                $blocks[] = $block;
            }
        } elseif( isset($s['display-format']) && $s['display-format'] == 'text' ) {
            $blocks[] = array(
                'type' => 'textcards',
                'level' => 3,
                'class' => 'aligncenter musicfestival-sponsors',
                'items' => $sponsors,
                );

        } else {
            //
            // Display as list of sponsor images
            //
            $blocks[] = array(
//                'type' => 'imagescroll', 
                'type' => 'sponsors', 
//                'padding' => '#ffffff',
            //'speed' => isset($s['speed']) ? $s['speed'] : 'medium',
                'class' => 'sponsors image-size-' . (isset($s['image-size']) ? $s['image-size'] : 'medium'),
                'items' => $sponsors,
                );
        }
    } 

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
