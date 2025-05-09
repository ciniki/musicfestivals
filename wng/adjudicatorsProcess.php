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
function ciniki_musicfestivals_wng_adjudicatorsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.248', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.249', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.211', 'msg'=>"No festival specified"));
    }

    //
    // Load the adjudicators
    //
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.customer_id, "
        . "customers.display_name, "
        . "customers.sort_name, "
        . "customers.permalink, "
        . "links.id AS link_id, "
        . "links.name AS link_name, "
        . "links.url AS link_url, "
        . "adjudicators.image_id, "
        . "adjudicators.flags, "
        . "adjudicators.description, "
        . "adjudicators.discipline "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_links AS links ON ("
            . "customers.id = links.customer_id "
            . "AND links.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND (adjudicators.flags&0x08) = 0 " // Visible
        . "";
    if( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'live' ) {
        $strsql .= "AND ((adjudicators.flags&0x01) = 0x01 OR (adjudicators.flags&0x03) = 0) ";
    } elseif( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'virtual' ) {
        $strsql .= "AND ((adjudicators.flags&0x02) = 0x02 OR (adjudicators.flags&0x03) = 0) ";
    }
    $strsql .= "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY customers.sort_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'permalink', 
            'fields'=>array('id', 'customer_id', 'display_name', 'flags', 'discipline', 
                'image-id'=>'image_id', 'description', 'sort_name', 'permalink',
                )),
        array('container'=>'links', 'fname'=>'link_id', 
            'fields'=>array('id', 'name'=>'link_name', 'url'=>'link_url'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.221', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    if( count($adjudicators) > 0 ) {
        //
        // Add the title block
        //
        $blocks[] = array(
            'type' => 'title', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : 'Adjudicators',
            );
        //
        // Add the adjudicators
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
            if( isset($adjudicators[$request['uri_split'][($request['cur_uri_pos']+1)]]) ) {
                $adjudicator = $adjudicators[$request['uri_split'][($request['cur_uri_pos']+1)]];
                $block = array(
                    'type' => 'contentphoto',
                    'title' => $adjudicator['display_name'],
                    'subtitle' => $adjudicator['discipline'],
                    'image-id' => $adjudicator['image-id'],
                    'content' => $adjudicator['description'],
                    'image-position' => (isset($s['image-position']) && $s['image-position'] != '' ? $s['image-position'] : ''),
                    'image-size' => (isset($s['image-size']) && $s['image-size'] != '' ? $s['image-size'] : ''),
                    );
                if( ($adjudicator['flags']&0x04) == 0x04 && isset($adjudicator['links']) ) {
                    $btn = 1;
                    foreach($adjudicator['links'] as $link) {
                        if( trim($link['url']) == '' ) {
                            continue;
                        }
                        $block["button-{$btn}-target"] = '_blank';
                        $block["button-{$btn}-page"] = 0;
                        $block["button-{$btn}-url"] = $link['url'];
                        $block["button-{$btn}-text"] = $link['name'] != '' ? $link['name'] : $link['url'];
                        $btn++;
                    }
                }

                $blocks[] = $block;
                return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
            } else {
                $blocks[] = array(
                    'type' => 'msg', 
                    'level' => 'error',
                    'content' => 'Could not find the adjudicator you requested.', 
                    );
            }
        }

        if( isset($s['layout']) && $s['layout'] == 'imagebuttons' ) {
            foreach($adjudicators as $aid => $adjudicator) {
                $adjudicators[$aid]['title'] = $adjudicator['display_name'];
                $adjudicators[$aid]['subtitle'] = $adjudicator['discipline'];
                $adjudicators[$aid]['image-ratio'] = '1-1';
                $adjudicators[$aid]['title-position'] = 'overlay-bottomhalf';
                $adjudicators[$aid]['url'] = $request['page']['path'] . '/' . $adjudicator['permalink'];
            }
            $blocks[] = array(
                'type' => 'imagebuttons',
                'items' => $adjudicators,
                );
        } 
        elseif( isset($s['layout']) && $s['layout'] == 'tradingcards' ) {
            foreach($adjudicators as $aid => $adjudicator) {
                $adjudicators[$aid]['title'] = $adjudicator['display_name'];
                $adjudicators[$aid]['subtitle'] = $adjudicator['discipline'];
//                $adjudicators[$aid]['image-position'] = 'top center';
                $adjudicators[$aid]['button-class'] = isset($s['button-class']) && $s['button-class'] != '' ? $s['button-class'] : 'button';
                $adjudicators[$aid]['button-1-text'] = 'Read Bio';
                $adjudicators[$aid]['button-1-url'] = $request['page']['path'] . '/' . $adjudicator['permalink'];
                $adjudicators[$aid]['url'] = $request['page']['path'] . '/' . $adjudicator['permalink'];
            }
            $blocks[] = array(
                'type' => 'tradingcards',
                'class' => 'musicfestival-adjudicators',
                'size' => '25',
                'items' => $adjudicators,
                );
        } 
        else {
            $side = 'right';
            foreach($adjudicators as $adjudicator) {
                $blocks[] = array(
                    'type' => 'contentphoto', 
                    'image-position' => 'top-' . $side,
                    'title' => $adjudicator['display_name'],
                    'subtitle' => $adjudicator['discipline'], 
                    'image-id' => (isset($adjudicator['image-id']) && $adjudicator['image-id'] > 0  ? $adjudicator['image-id'] : 0),
                    'image-position' => (isset($s['image-position']) && $s['image-position'] != '' ? $s['image-position'] : ''),
                    'image-size' => (isset($s['image-size']) && $s['image-size'] != '' ? $s['image-size'] : ''),
                    'content' => $adjudicator['description'],
                    );
                $side = $side == 'right' ? 'left' : 'right';
            } 
        }
    } else {
        $blocks[] = array(
            'type' => 'text', 
            'title' => isset($s['title']) ? $s['title'] : 'Adjudicators',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'content' => "We don't currently have any adjudicators.",
            );
    } 

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
