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
function ciniki_musicfestivals_wng_artworkProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.671', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

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
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.672', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.673', 'msg'=>"No festival specified"));
    }

    //
    // Get the music festival details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'festivalLoad');
    $rc = ciniki_musicfestivals_wng_festivalLoad($ciniki, $tnid, $s['festival-id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load the artwork
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.permalink AS section_permalink, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.permalink AS category_permalink, "
        . "classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id AS reg_id, "
        . "registrations.uuid, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.artwork1, "
        . "registrations.artwork2, "
        . "registrations.artwork3, "
        . "registrations.artwork4, "
        . "registrations.artwork5, "
        . "registrations.artwork6, "
        . "registrations.artwork7, "
        . "registrations.artwork8 "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND (classes.flags&0x0300) > 0 "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND registrations.artwork1 <> '' "
        . "AND registrations.status >= 50 AND registrations.status < 70 "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.code, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'permalink'=>'section_permalink'),
            ),
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('id'=>'category_id', 'name'=>'category_name', 'permalink'=>'category_permalink'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'uuid', 'display_name', 'public_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                'artwork1', 'artwork2', 'artwork3', 'artwork4', 'artwork5', 'artwork6', 'artwork7', 'artwork8',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.64', 'msg'=>'Unable to load artwork', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    $blocks[] = array(
        'type' => 'title',
        'title' => $s['title'],
        );
    $images = array();

    foreach($sections as $sid => $sec) {
        foreach($sec['categories'] as $cid => $category) {
            if( isset($request['uri_split'][($request['cur_uri_pos']+2)])
                && $request['uri_split'][($request['cur_uri_pos']+1)] == $sec['permalink']
                && $request['uri_split'][($request['cur_uri_pos']+2)] == $category['permalink']
                ) {
                $section['settings']['section'] = $sec;
                $section['settings']['category'] = $category;
                $section['settings']['registrations'] = $category['registrations']; $request['cur_uri_pos']+=2;
                $request['page']['path'] .= "/{$sec['permalink']}/{$category['permalink']}";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'artworkCategoryProcess');
                return ciniki_musicfestivals_wng_artworkCategoryProcess($ciniki, $tnid, $request, $section);
            }

            $sections[$sid]['categories'][$cid]['url'] = $request['page']['path'] . '/' . $sec['permalink'] . '/' . $category['permalink'];
            $sections[$sid]['categories'][$cid]['text'] = $category['name'];
            foreach($category['registrations'] as $reg) {
                for($i = 1; $i < 10; $i++) {
                    if( isset($reg["artwork{$i}"]) && $reg["artwork{$i}"] != '' ) {
                        $extension = preg_replace('/^.*\.([a-zA-Z0-9]+)$/', '$1', $reg["artwork{$i}"]);
                        if( !in_array($extension, ['jpg', 'png', 'jpeg']) ) {
                            continue;
                        }
                        $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/files/' . $reg['uuid'][0] . '/' . $reg['uuid'] . '_artwork' . $i;
                        if( file_exists($storage_filename) ) {
                            $filename = $reg['uuid'] . '.jpg';
                            $images["{$reg['uuid']}-{$i}"] = array(
                                'storage_filename' => $storage_filename,
                                'cache_filename' => $request['site']['cache_dir'] . '/mf' . $s['festival-id'] . '/' . $filename,
                                'image-url' => $request['site']['cache_url'] . '/mf' . $s['festival-id'] . '/' . $filename,
                                'title' => $reg["title{$i}"] . ' by ' . $reg['display_name'],
                                );
                        }
                    }
                }
            }
        }
    }

    // 
    // Generate a carousel
    //
    if( count($images) > 0 ) {
        shuffle($images);
        foreach($images as $iid => $artwork) {
            if( !file_exists($artwork['cache_filename']) ) {
                //
                // Open the image
                //
                try {
                    $image = new Imagick($artwork['storage_filename']);
                } catch (Exception $e) {
                    error_log("Unable to open image for {$artwork['title']}");
                    continue;
                }
                $image->scaleImage(1024, 0);
                $image->setImageFormat('jpg');
                $image->setImageCompressionQuality(75);
            
                $image->writeImage($artwork['cache_filename']);
            }
        }
        $blocks[] = array(
            'type' => 'carousel',
            'class' => 'musicfestival-artwork',
            'speed' => 'slow',
            'titles' => 'yes',
            'image-format' => 'padded',
            'items' => $images,
            );
    }

    //
    // Output the buttons
    //
    foreach($sections as $sid => $sec) {
        $blocks[] = array(
            'type' => 'buttons',
            'title' => $sec['name'],
            'level' => 2,
            'class' => 'musicfestival-artwork',
            'items' => $sections[$sid]['categories'],
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
