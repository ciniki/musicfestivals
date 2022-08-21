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
function ciniki_musicfestivals_wng_timeslotPhotosProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.219', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.220', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.246', 'msg'=>"No festival specified"));
    }

    $festival_permalink = '';
    $division_permalink = '';
    if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) ) { 
        $festival_permalink = $request['uri_split'][($request['cur_uri_pos']+1)];
        $division_permalink = $request['uri_split'][($request['cur_uri_pos']+2)];
    }
    $image_permalink = '';
    if( isset($request['uri_split'][($request['cur_uri_pos']+3)]) ) { 
        $image_permalink = $request['uri_split'][($request['cur_uri_pos']+3)];
    }

    //
    // Load the photos organized by division
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "festivals.permalink AS festival_permalink, "
        . "images.id AS timeslot_image_id, "
        . "images.image_id, "
        . "images.permalink, "
        . "images.title, "
        . "images.description "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "sections.festival_id = festivals.id "
            . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_timeslot_images AS images ON ("
            . "timeslots.id = images.timeslot_id "
            . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sections.name, divisions.division_date, divisions.name, images.sequence, images.title "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'text'=>'division_name', 'image-id'=>'image_id',
                'festival_permalink'
                )),
        array('container'=>'images', 'fname'=>'permalink', 
            'fields'=>array('id'=>'timeslot_image_id', 'image-id'=>'image_id', 'permalink', 'title', 'description'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.251', 'msg'=>'Unable to load images', 'err'=>$rc['err']));
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();
    foreach($divisions as $did => $division) {
        $divisions[$did]['permalink'] = ciniki_core_makePermalink($ciniki, trim($division['text']));
        $divisions[$did]['url'] = $request['page']['path'] 
            . '/' . $divisions[$did]['festival_permalink']
            . '/' . $divisions[$did]['permalink'];
        if( $divisions[$did]['festival_permalink'] == $festival_permalink
            && $divisions[$did]['permalink'] == $division_permalink 
            ) {
            $division['permalink'] = ciniki_core_makePermalink($ciniki, trim($division['festival_permalink'])) 
                . '/' . ciniki_core_makePermalink($ciniki, trim($division['text']));
//            $division['permalink'] = ciniki_core_makePermalink($ciniki, trim($division['text']));
            $division['url'] = $request['page']['path'] 
                . '/' . $divisions[$did]['festival_permalink']
                . '/' . $divisions[$did]['permalink'];
            $selected_division = $division;
            if( $image_permalink != '' && isset($division['images'][$image_permalink]) ) {
                $selected_image = $division['images'][$image_permalink];
            }
        }
    }

    if( isset($selected_image) ) {
        //
        // Add the title block
        //
        $blocks[] = array(
            'type' => 'title', 
            'title' => isset($s['title']) ? $s['title'] : 'Festival Photos - ' . $selected_division['title'],
            );
        $blocks[] = array(
            'type' => 'image',
            'image-id' => $selected_image['image-id'],
            'image-permalink' => $selected_image['permalink'],
            'image-list' => $selected_division['images'],
            'base-url' => $request['page']['path'] . '/' . $selected_division['permalink'],
            );

        return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
    }
    elseif( isset($selected_division) ) {
        //
        // Add the title block
        //
        $blocks[] = array(
            'type' => 'title', 
            'title' => isset($s['title']) ? $s['title'] : 'Festival Photos - ' . $selected_division['title'],
            );
        foreach($selected_division['images'] as $iid => $image) {
            $selected_division['images'][$iid]['url'] = $request['page']['path'] . '/' . $selected_division['permalink'] . '/' . $image['permalink'];
        }

        $blocks[] = array(
            'type' => 'gallery',
            'layout' => 'originals',
            'items' => $selected_division['images'],
            );

        return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');

    }
    elseif( count($divisions) > 0 ) {
         
        //
        // Add the title block
        //
        $blocks[] = array(
            'type' => 'title', 
            'title' => isset($s['title']) ? $s['title'] : 'Festival Photos',
            );
        
        //
        // Add the adjudicators
        //
        $blocks[] = array(
            //'type' => 'imagebuttons',
            'type' => 'buttons',
            'image-ratio' => '4-3',
            'title-position' => 'overlay-bottomhalf',
            //'items' => $divisions,
            'list' => $divisions,
            );
    } else {
        $blocks[] = array(
            'type' => 'text', 
            'title' => isset($s['title']) ? $s['title'] : 'Festival Photos',
            'content' => "We don't currently have any photos for this festival.",
            );
    } 

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
