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
        . "locations.address1, "
        . "locations.city, "
        . "locations.province, "
        . "locations.postal, "
        . "locations.latitude, "
        . "locations.longitude "
        . "FROM ciniki_musicfestival_locations AS locations "
        . "WHERE locations.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY category, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('name'=>'category'),
            ),
        array('container'=>'locations', 'fname'=>'id', 
            'fields'=>array('id', 'category', 'name', 'address1', 'city', 'province', 'postal', 
                'latitude', 'longitude'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.761', 'msg'=>'Unable to load locations', 'err'=>$rc['err']));
    }
    $locations = isset($rc['categories']) ? $rc['categories'] : array();

    if( (isset($s['title']) && $s['title'] != '') || (isset($s['content']) && $s['content'] != '') ) {
        $blocks[] = array(
            'type' => (!isset($s['content']) || $s['content'] == '' ? 'title' : 'text'),
            'title' => isset($s['title']) ? $s['title'] : '',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'content' => isset($s['content']) ? $s['content'] : '',
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
        foreach($cat['locations'] as $location) {
            $content = $location['address1']
                . '<br/>' . $location['city'] . ', ' . $location['province'] . '  ' . $location['postal'];
            $blocks[] = array(
                'type' => 'googlemap',
                'id' => "map-" . ($map_id+1),
                'sid' => $map_id,
                'class' => 'content-view',
                'map-position' => 'bottom-right',
                'title' => $location['name'],
                'content' => $content,
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                );
            $map_id++;
        }

        
        
    }




    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
