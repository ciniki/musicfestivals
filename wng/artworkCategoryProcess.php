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
function ciniki_musicfestivals_wng_artworkCategoryProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.671', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $s = $section['settings'];
    $blocks = array();

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    if( !file_exists($request['site']['cache_dir'] . '/mf' . $s['festival-id']) ) {
        mkdir($request['site']['cache_dir'] . '/mf' . $s['festival-id']);
    }

    $items = array();
    //
    // Check the cache to make sure all images are cached
    //
    foreach($s['registrations'] as $reg) {
        //
        for($i = 1; $i < 10; $i++) {
            if( isset($reg["artwork{$i}"]) && $reg["artwork{$i}"] != '' ) {
                $extension = preg_replace('/^.*\.([a-zA-Z0-9]+)$/', '$1', $reg["artwork{$i}"]);
                if( !in_array($extension, ['jpg', 'png', 'jpeg']) ) {
                    continue;
                }
                $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/files/' . $reg['uuid'][0] . '/' . $reg['uuid'] . '_artwork' . $i;
                if( file_exists($storage_filename) ) {
                    $filename = $reg['uuid'] . '.jpg';
                    $cache_filename = $request['site']['cache_dir'] . '/mf' . $s['festival-id'] . '/' . $filename;
                    $img_url = $request['site']['cache_url'] . '/mf' . $s['festival-id'] . '/' . $filename;

                    if( !file_exists($cache_filename) ) {
                        //
                        // Open the image
                        //
                        try {
                            $image = new Imagick($storage_filename);
                        } catch (Exception $e) {
                            error_log("Unable to open image for {$reg['display_name']}:{$reg['id']} artwork{$i}");
                            continue;
                        }
                        $image->scaleImage(1024, 0);
                        $image->setImageFormat('jpg');
                        $image->setImageCompressionQuality(75);
                    
                        $image->writeImage($cache_filename);
                    }
                    $items["{$reg['uuid']}-{$i}"] = [
                        'image-url' => $img_url,
                        'title-position' => 'below',
                        'title' => $reg["title{$i}"] . ' by ' . $reg['display_name'],
                        'permalink' => "{$reg['uuid']}-{$i}",
                        'url' => $request['page']['path'] . "/{$reg['uuid']}-{$i}",
                        ];
                }
            }
        }
    }

    $blocks[] = array(
        'type' => 'title',
        'title' => $s['title'],
        );

    if( count($items) > 0 ) {
        if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
            && isset($items[$request['uri_split'][($request['cur_uri_pos']+1)]])
            ) {
            $item = $items[$request['uri_split'][($request['cur_uri_pos']+1)]];
            $blocks[] = array(
                'type' => 'title',
                'level' => 2,
                'title' => $s['section']['name'] . ' - '. $s['category']['name'],
                );
            $blocks[] = array(
                'type' => 'image',
                'image-permalink' => $item['permalink'],
                'image-url' => $item['image-url'],
                'title' => $item['title'],
                'base-url' => $request['page']['path'],
                'image-list' => $items,
                );
        } else {
            $blocks[] = array(
                'type' => 'title',
                'level' => 2,
                'title' => $s['section']['name'] . ' - '. $s['category']['name'],
                );
            $blocks[] = array(
                'type' => 'imagebuttons',
                'items' => $items,
                ); 
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
