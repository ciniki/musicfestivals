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

    if( isset($s['display-format']) && $s['display-format'] == 'category-disciplines-name' ) {
        //
        // Load the locations
        //
        $strsql = "SELECT rooms.id, "
            . "buildings.category, "
            . "rooms.name, "
            . "rooms.disciplines, "
            . "rooms.permalink, "
            . "buildings.address1, "
            . "buildings.city, "
            . "buildings.province, "
            . "buildings.postal, "
            . "buildings.latitude, "
            . "buildings.longitude, "
            . "buildings.image_id, "
            . "buildings.description "
            . "FROM ciniki_musicfestival_locations AS rooms "
            . "INNER JOIN ciniki_musicfestival_buildings AS buildings ON ("
                . "rooms.building_id = buildings.id "
                . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE rooms.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY buildings.category, buildings.sequence, buildings.name, rooms.sequence, buildings.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'category', 
                'fields'=>array('name'=>'category'),
                ),
            array('container'=>'locations', 'fname'=>'id', 
                'fields'=>array('id', 'category', 'name', 'disciplines', 'permalink', 'address1', 'city', 'province', 'postal', 
                    'latitude', 'longitude', 'image-id'=>'image_id', 'description'),
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
                    $location['title'] = isset($location['disciplines']) ? $location['disciplines'] : '';
                    $location['subtitle'] = isset($location['name']) ? $location['name'] : '';
                $location['buttons'] = [
                    [
                        'url' => "{$base_url}/{$location['permalink']}", 
                        'text' => isset($s['button-text']) && $s['button-text'] != '' ? $s['button-text'] : 'Open Map',
                        ],
                    ];
                if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
                    && $request['uri_split'][($request['cur_uri_pos']+1)] == $location['permalink']
                    ) {
                    $blocks = []; // Reset blocks, remove existing page title
                    if( isset($location['disciplines']) && $location['disciplines'] != '' ) {
                        $blocks[] = [ 
                            'type' => 'title',
                            'title' => isset($location['disciplines']) ? $location['disciplines'] : '',
                            'subtitle' => isset($location['name']) ? $location['name'] : '',
                            ];
                    } else {
                        $blocks[] = [ 
                            'type' => 'title',
                            'title' => isset($location['name']) ? $location['name'] : '',
                            ];
                    }
                    $blocks[] = [
                        'type' => 'googlemap',
                        'id' => "map-" . ($map_id+1),
                        'sid' => $map_id,
                        'class' => 'content-view',
                        'map-position' => 'bottom-right',
                        'content' => $content,
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                        ];
                    return array('stat'=>'ok', 'blocks'=>$blocks);
                }
                $items[] = $location;
            }

            $blocks[] = array(
                'type' => 'tradingcards',
                'class' => 'musicfestival-locations',
                'size' => '30',
                'image-ratio' => isset($s['image-ratio']) && $s['image-ratio'] != '' ? $s['image-ratio'] : '4-3',
                'items' => $items,
                );
        }
    }
    else {
        //
        // Load the locations
        //
        $strsql = "SELECT buildings.id, "
            . "buildings.category, "
            . "buildings.name, "
            . "buildings.permalink, "
            . "buildings.address1, "
            . "buildings.city, "
            . "buildings.province, "
            . "buildings.postal, "
            . "buildings.latitude, "
            . "buildings.longitude, "
            . "buildings.image_id, "
            . "buildings.description, "
            . "rooms.id AS room_id, "
            . "rooms.roomname, "
            . "rooms.disciplines "
            . "FROM ciniki_musicfestival_buildings AS buildings "
            . "LEFT JOIN ciniki_musicfestival_locations AS rooms ON ("
                . "buildings.id = rooms.building_id "
                . "AND rooms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE buildings.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND buildings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY buildings.category, buildings.sequence, buildings.name, rooms.sequence, buildings.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'category', 
                'fields'=>array('name'=>'category'),
                ),
            array('container'=>'buildings', 'fname'=>'id', 
                'fields'=>array('id', 'category', 'name', 'permalink', 'address1', 'city', 'province', 'postal', 
                    'latitude', 'longitude', 'image-id'=>'image_id', 'description'),
                ),
            array('container'=>'rooms', 'fname'=>'room_id', 
                'fields'=>array('id'=>'room_id', 'roomname', 'disciplines'),
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
            foreach($cat['buildings'] as $building) {
                $content = $building['address1']
                    . "\n" . $building['city'] . ', ' . $building['province'] . '  ' . $building['postal'];
                $building['content'] = $content;
                if( $building['description'] != '' ) {
                    $content .= ($content != '' ? "\n\n" : '') . $building['description'];
                }
                $building['title'] = $building['name'];
                $building['subtitle'] = '';
                if( isset($s['display-format']) && $s['display-format'] == 'category-name-rooms' ) {
                    foreach($building['rooms'] as $room) {
                        $name = $room['roomname'];
                        if( $room['disciplines'] != '' ) {
                            $name .= ($name != '' ? ' - ' : '') . $room['disciplines'];
                        }
                        if( $name != '' ) {
                            $building['subtitle'] .= ($building['subtitle'] != '' ? '<br>' : '') . $name;
                        }
                    }
                }
                $building['buttons'] = [
                    [
                        'url' => "{$base_url}/{$building['permalink']}", 
                        'text' => isset($s['button-text']) && $s['button-text'] != '' ? $s['button-text'] : 'Open Map',
                        ],
                    ];
                if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
                    && $request['uri_split'][($request['cur_uri_pos']+1)] == $building['permalink']
                    ) {
                    $blocks = [];
                    $blocks[] = [ 
                        'type' => 'title',
                        'title' => isset($building['name']) ? $building['name'] : '',
//                        'subtitle' => isset($location['disciplines']) ? $location['disciplines'] : '',
                        ];
                    $blocks[] = [
                        'type' => 'googlemap',
                        'id' => "map-" . ($map_id+1),
                        'sid' => $map_id,
                        'class' => 'content-view',
                        'map-position' => 'bottom-right',
                        'content' => $content,
                        'latitude' => $building['latitude'],
                        'longitude' => $building['longitude'],
                        ];
                    return array('stat'=>'ok', 'blocks'=>$blocks);
                }
                $items[] = $building;
            }

            $blocks[] = array(
                'type' => 'tradingcards',
                'class' => 'musicfestival-locations',
                'size' => '30',
                'image-ratio' => isset($s['image-ratio']) && $s['image-ratio'] != '' ? $s['image-ratio'] : '4-3',
                'items' => $items,
                );
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
