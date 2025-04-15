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
function ciniki_musicfestivals_wng_locationsProcess(&$ciniki, $tnid, &$request, $section) {

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
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the locations
    //
    $strsql = "SELECT locations.id, "
        . "locations.category, "
        . "locations.name, "
        . "locations.disciplines, "
        . "locations.permalink, "
        . "locations.address1, "
        . "locations.city, "
        . "locations.province, "
        . "locations.postal, "
        . "locations.latitude, "
        . "locations.longitude, "
        . "locations.description "
        . "FROM ciniki_musicfestival_locations AS locations "
        . "WHERE locations.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY category, sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('name'=>'category'),
            ),
        array('container'=>'locations', 'fname'=>'id', 
            'fields'=>array('id', 'category', 'name', 'disciplines', 'permalink', 'address1', 'city', 'province', 'postal', 
                'latitude', 'longitude', 'description'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.761', 'msg'=>'Unable to load locations', 'err'=>$rc['err']));
    }
    $locations = isset($rc['categories']) ? $rc['categories'] : array();

    $content = isset($s['intro']) ? $s['intro'] : '';
    if( count($locations) == 0 && isset($s['no-locations-intro']) && $s['no-locations-intro'] != '' ) {
        $content = $s['no-locations-intro'];
    }

    if( (isset($s['title']) && $s['title'] != '') || $content != '' ) {
        $blocks[] = array(
            'type' => ($content == '' ? 'title' : 'text'),
            'title' => isset($s['title']) ? $s['title'] : '',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'content' => $content,
            );
    }

    $map_id = 1;
    foreach($locations as $cat) {
        if( $cat['name'] != '' ) {
            $blocks[] = array(
                'type' => 'title',
                'title' => isset($cat['name']) ? $cat['name'] : '',
                'level' => $section['sequence'] == 1 ? 2 : 3,
                );
        }
        $items = [];
        foreach($cat['locations'] as $location) {
            $content = $location['address1']
                . "\n" . $location['city'] . ', ' . $location['province'] . '  ' . $location['postal'];
            $location['content'] = $content;
            if( $location['description'] != '' ) {
                $content .= ($content != '' ? "\n\n" : '') . $location['description'];
            }
            if( isset($s['display-format']) && $s['display-format'] == 'category-disciplines-name' ) {
                $location['title'] = isset($location['disciplines']) ? $location['disciplines'] : '';
                $location['subtitle'] = isset($location['name']) ? $location['name'] : '';
            } else {
                $location['title'] = $location['name'];
                $location['subtitle'] = $location['disciplines'];
            }
//            $location['url'] = "{$base_url}/{$location['permalink']}";
            $location['buttons'] = [
                [
                    'url' => "{$base_url}/{$location['permalink']}", 
                    'text' => isset($s['button-text']) && $s['button-text'] != '' ? $s['button-text'] : 'Open Map',
                    ],
                ];
            if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
                && $request['uri_split'][($request['cur_uri_pos']+1)] == $location['permalink']
                ) {
                $blocks = [];
                if( isset($s['display-format']) && $s['display-format'] == 'category-disciplines-name' ) {
                    $blocks[] = [ 
                        'type' => 'title',
                        'title' => isset($location['disciplines']) ? $location['disciplines'] : '',
                        'subtitle' => isset($location['name']) ? $location['name'] : '',
                        ];
                } else {
                    $blocks[] = [ 
                        'type' => 'title',
                        'title' => isset($location['name']) ? $location['name'] : '',
                        'subtitle' => isset($location['disciplines']) ? $location['disciplines'] : '',
                        ];
                }
                $blocks[] = [
                    'type' => 'googlemap',
                    'id' => "map-" . ($map_id+1),
                    'sid' => $map_id,
                    'class' => 'content-view',
                    'map-position' => 'bottom-right',
//                    'title' => $location['name'],
//                    'location-name' => $location['name'],
//                    'gmp-map' => 'yes',
                    'content' => $content,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    ];
                return array('stat'=>'ok', 'blocks'=>$blocks);
            }
            $items[] = $location;

/*            $block = array(
                'type' => 'googlemap',
                'id' => "map-" . ($map_id+1),
                'sid' => $map_id,
                'class' => 'content-view',
                'map-position' => 'bottom-right',
                'title' => $location['name'],
                'location-name' => $location['name'],
//                'gmp-map' => 'yes',
                'subtitle' => $location['disciplines'],
                'content' => $content,
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                );
            if( isset($s['display-format']) && $s['display-format'] == 'category-disciplines-name' ) {
                $block['title'] = isset($location['disciplines']) ? $location['disciplines'] : '';
                $block['subtitle'] = isset($location['name']) ? $location['name'] : '';
            }
            $blocks[] = $block;
            $map_id++; 
*/
        }

        $blocks[] = array(
            'type' => 'textcards',
            'class' => 'musicfestival-locations',
            'items' => $items,
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
