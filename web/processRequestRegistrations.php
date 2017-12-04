<?php
//
// Description
// -----------
// This function will process the registrations page for online music festival registrations.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get music festival request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_musicfestivals_web_processRequestRegistrations(&$ciniki, $settings, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.121', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Check there is a festival setup
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.122', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // This function does not build a page, just provides an array of blocks
    //
    $blocks = array();

    //
    // Check to make sure the customer is logged in, otherwise redirect to login page
    //
    if( !isset($ciniki['session']['customer']['id']) ) {
        $blocks[] = array(
            'type' => 'login', 
            'section' => 'login',
            'redirect' => $args['base_url'],        // Redirect back to registrations page
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Check if customer is setup for the music festival this year
    //
    $strsql = "SELECT id, ctype "
        . "FROM ciniki_musicfestival_customers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.123', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    $customer_type = 0;
    if( !isset($rc['customer']['ctype']) || $rc['customer']['ctype'] == 0 ) {
        //
        // Check if customer type was submitted
        //
        if( isset($_GET['ctype']) && in_array($_GET['ctype'], array(10,20,30)) ) {
            //
            // Add the customer to the musicfestival
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.musicfestivals.customer', array(
                'festival_id' => $args['festival_id'],
                'customer_id' => $ciniki['session']['customer']['id'],
                'ctype' => $_GET['ctype'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.124', 'msg'=>'Unable to add the customer'));
            }
            $customer_type = $_GET['t'];
        } 
        
        //
        // Ask the customer what type they are
        //
        else {
            $content = "<p>In order to better serve you, we need to know who you are.</p>";
            $content .= "<a href='" . $args['base_url'] . "?t=10'>I am a parent registering my children</a>";
            $content .= "<a href='" . $args['base_url'] . "?t=20'>I am a teacher registering my students</a>";
            $content .= "<a href='" . $args['base_url'] . "?t=30'>I am an adult registering myself</a>";

            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
    } else {
        $customer_type = $rc['customer']['ctype'];
    }

    //
    // Load the customers registrations
    //
    $strsql = "SELECT r.id, "
        . "r.teacher_customer_id, r.billing_customer_id, r.rtype, r.status, "
        . "r.display_name, r.public_name, "
        . "r.competitor1_id, r.competitor2_id, r.competitor3_id, r.competitor4_id, r.competitor5_id, "
        . "r.class_id, r.timeslot_id, r.title, r.perf_time, r.fee, r.payment_type, r.notes "
        . "FROM ciniki_musicfestival_registrations AS r "
        . "WHERE r.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND r.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "ORDER BY r.status, r.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 
                'display_name', 'public_name', 'competitor1_id', 'competitor2_id', 'competitor3_id', 
                'competitor4_id', 'competitor5_id', 'class_id', 'timeslot_id', 'title', 'perf_time', 
                'fee', 'payment_type', 'notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.126', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    //
    // Load the competitors
    //
    $strsql = "SELECT c.id, "
        . "c.name, c.parent, c.address, c.city, c.province, c.postal, "
        . "c.phone_home, c.phone_cell, c.email, c.age, c.study_level, c.instrument, c.notes "
        . "FROM ciniki_musicfestival_registrations AS r "
        . "LEFT JOIN ciniki_musicfestival_competitors AS c ON ("
            . "c.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND ("
                . "r.competitor1_id = c.id "
                . "OR r.competitor2_id = c.id "
                . "OR r.competitor3_id = c.id "
                . "OR r.competitor4_id = c.id "
                . "OR r.competitor5_id = c.id "
                . ") "
            . "AND c.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE r.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND r.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "AND r.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'parent', 'address', 'city', 'province', 'postal', 'phone_home', 
                'phone_cell', 'email', 'age', 'study_level', 'instrument', 'notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.125', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();


    //
    // Decide what should be displayed
    //
    $display = 'list';
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == '' ) {
        $display = 'form';
    } elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'new' ) {
        $display = 'form';
        $form_permalink
    }




    if( $display == 'list' ) {
    }

    //
    // Prepare the registration list
    //


    return array('stat'=>'ok', 'blocks'=>$blocks);

    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(),
        );
    $uri_split = $args['uri_split'];

    //
    // Check for music festival permalink, for archived festivals
    //
    $festival_id = 0;
    if( isset($uri_split[0]) && $uri_split[0] != '' ) {
        //
        // Check if a musicfestival
        //
        $strsql = "SELECT id, name "
            . "FROM ciniki_musicfestivals "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND status = 30 "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $uri_split[0]) . "' "
            . "ORDER BY start_date DESC "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['festival']) ) {
            $festival_id = $rc['festival']['id'];
            $uri_split = shift($uri_split);
            $page['breadcrumbs'][] = array('name'=>$rc['festival']['name'], 'url'=>$args['base_url'] . '/' . $uri_split[0]);
        }
    }

    //
    // No festival specified on the url, load the specified one in the settings, or find the more recent.
    //
    if( $festival_id == 0 ) {
        //
        // Load the festival name
        //
        $strsql = "SELECT id, name "
            . "FROM ciniki_musicfestivals "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND status = 30 "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['festival']) ) {
            $festival_id = $rc['festival']['id'];
            $page['breadcrumbs'][] = array('name'=>$rc['festival']['name'], 'url'=>$args['base_url']);
        }
    }

    //
    // Check if no festival found
    //
    if( $festival_id == 0 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.39', 'msg'=>'We could not find the requested Music Festival. Please try again or contact us for more information.'));
    }

    //
    // Get the sponsors for the festival
    //
    if( isset($ciniki['business']['modules']['ciniki.sponsors']) 
        && ($ciniki['business']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'web', 'sponsorRefList');
        $rc = ciniki_sponsors_web_sponsorRefList($ciniki, $settings, $business_id, 
            'ciniki.musicfestivals.festival', $festival_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['sponsors']) ) {
            $sponsors = $rc['sponsors'];
        }
    }

    //
    // Check if file to download
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'download' && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'web', 'fileDownload');
        $rc = ciniki_musicfestivals_web_fileDownload($ciniki, $ciniki['request']['business_id'], $festival_id, $ciniki['request']['uri_split'][1]);
        if( $rc['stat'] == 'ok' ) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            $file = $rc['file'];
            if( $file['extension'] == 'pdf' ) {
                header('Content-Type: application/pdf');
            }
//          header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
            header('Content-Length: ' . strlen($file['binary_content']));
            header('Cache-Control: max-age=0');

            print $file['binary_content'];
            exit;
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.63', 'msg'=>'The file you requested does not exist.'));
    }

    //
    // Decide what should be displayed, default to about page
    //
    $display = 'about';
    if( isset($uri_split[0]) ) {
        if( $uri_split[0] == 'about' ) {
            $display = 'about';
        } elseif( $uri_split[0] == 'adjudicators' ) {
            $display = 'adjudicators';
            $adjudicator_permalink = $uri_split[0];
        } elseif( $uri_split[0] == 'registrations' ) {
            $display = 'registrations';
            array_shift($uri_split);
        } else {
            $strsql = "SELECT id, name, permalink, primary_image_id AS image_id, synopsis, description "
                . "FROM ciniki_musicfestival_sections "
                . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $uri_split[0]) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['section']) ) {
                $section = $rc['section'];
                $display = 'section';
            } else {
                return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.48', 'msg'=>'We could not find the request page.'));
            }
        }
    }

    //
    // Load the details for the festival, and display the main page.
    //
    if( $display == 'about' ) {
        $strsql = "SELECT id, name, start_date, end_date, status, flags, primary_image_id, description "
            . "FROM ciniki_musicfestivals "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND status = 30 "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['festival']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.49', 'msg'=>'We could not find the request page.'));
        }
        $festival = $rc['festival'];
       
        if( isset($festival['primary_image_id']) && $festival['primary_image_id'] > 0 ) {
            $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$festival['primary_image_id']);
        }

        $content = $festival['description'];
        $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$content);

        //
        // Get any files
        //
        $strsql = "SELECT id, name, permalink, extension, description "
            . "FROM ciniki_musicfestival_files "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_musicfestival_files.webflags&0x01) > 0 "       // Make sure file is to be visible
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'extension', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $page['blocks'][] = array('type'=>'files', 'base_url'=>$args['base_url'] . '/download', 'files'=>$rc['files']);
        }
    }

    //
    // Process the registrations page
    //
    elseif( $display == 'registrations' && $ciniki['session']['customer']['id'] > 0 ) {
        $page['breadcrumbs'][] = array('name'=>'Registrations', 'url'=>$args['base_url'] . '/registrations');

        $args['uri_split'] = $uri_split;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'processRequestRegistrations');
        $rc = ciniki_musicfestivals_processRequestRegistrations($ciniki, $settings, $business_id, $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['blocks']) ) {
            foreach($rc['blocks'] as $block) {
                $page['blocks'][] = $block;
            }
        }
    }

    //
    // Display the section information
    //
    elseif( $display == 'section' ) {
        $page['breadcrumbs'][] = array('name'=>$section['name'], 'url'=>$args['base_url'] . '/' . $section['permalink']);
        //
        // Display the section information
        //
        $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$section['name'], 
            'aside_image_id'=>(isset($section['image_id']) && $section['image_id'] > 0  ? $section['image_id'] : 0),
            'wide'=>(isset($section['image_id']) && $section['image_id'] > 0  ? 'no' : 'yes'),
            'content'=>($section['description'] != '' ? $section['description'] : $section['synopsis'])
            );

        //
        // Get the categories and classes
        //
        $strsql = "SELECT ciniki_musicfestival_classes.id, "
            . "ciniki_musicfestival_classes.festival_id, "
            . "ciniki_musicfestival_classes.category_id, "
            . "ciniki_musicfestival_categories.id AS category_id, "
            . "ciniki_musicfestival_categories.name AS category_name, "
            . "ciniki_musicfestival_categories.primary_image_id AS category_image_id, "
            . "ciniki_musicfestival_categories.synopsis AS category_synopsis, "
            . "ciniki_musicfestival_categories.description AS category_description, "
            . "ciniki_musicfestival_classes.code, "
            . "ciniki_musicfestival_classes.name, "
            . "ciniki_musicfestival_classes.permalink, "
            . "ciniki_musicfestival_classes.sequence, "
            . "ciniki_musicfestival_classes.flags, "
            . "CONCAT('$', FORMAT(ciniki_musicfestival_classes.fee, 2)) AS fee "
            . "FROM ciniki_musicfestival_categories, ciniki_musicfestival_classes "
            . "WHERE ciniki_musicfestival_categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
            . "AND ciniki_musicfestival_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_musicfestival_categories.id = ciniki_musicfestival_classes.category_id "
            . "AND ciniki_musicfestival_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "ORDER BY ciniki_musicfestival_categories.sequence, ciniki_musicfestival_categories.name, "
                . "ciniki_musicfestival_classes.sequence, ciniki_musicfestival_classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'category_id', 
                'fields'=>array('name'=>'category_name', 'image_id'=>'category_image_id', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) ) {
            $categories = $rc['categories'];
            foreach($categories as $category) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$category['name'], 
                    'aside_image_id'=>(isset($category['image_id']) && $category['image_id'] > 0  ? $category['image_id'] : 0),
                    'wide'=>(isset($category['image_id']) && $category['image_id'] > 0  ? 'no' : 'yes'),
                    'content'=>($category['description'] != '' ? $category['description'] : $category['synopsis'])
                    );
                if( isset($category['classes']) && count($category['classes']) > 0 ) {
                    $page['blocks'][] = array('type'=>'table', 'section'=>'classes', 
                        'columns'=>array(
                            array('label'=>'', 'field'=>'code', 'class'=>''),
                            array('label'=>'', 'field'=>'name', 'class'=>''),
                            array('label'=>'Fee', 'field'=>'fee', 'class'=>'aligncenter'),
                            ),
                        'rows'=>$category['classes'],
                        );
                }
            }
        }
    }

    //
    // Display the adjudicators
    //
    elseif( $display == 'adjudicators' ) {
        $page['breadcrumbs'][] = array('name'=>'Adjudicators', 'url'=>$args['base_url'] . '/adjudicators');
        $strsql = "SELECT ciniki_musicfestival_adjudicators.id, "
            . "ciniki_musicfestival_adjudicators.customer_id, "
            . "ciniki_customers.sort_name "
            . "FROM ciniki_musicfestival_adjudicators, ciniki_customers "
            . "WHERE ciniki_musicfestival_adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND ciniki_musicfestival_adjudicators.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_musicfestival_adjudicators.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "ORDER BY ciniki_customers.sort_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'a');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerPublicDetails');
            foreach($rc['rows'] as $row) {
                $rc = ciniki_customers_web_customerPublicDetails($ciniki, $settings, $business_id, array('customer_id'=>$row['customer_id']));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $customer = $rc['customer'];
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$customer['display_name'], 
                    'aside_image_id'=>(isset($customer['image_id']) && $customer['image_id'] > 0  ? $customer['image_id'] : 0),
                    'html'=>$customer['processed_description']);
//                if( isset($customer['image_id']) && $customer['image_id'] > 0 ) {
//                    $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$customer['image_id']);
//                }
//                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$customer['display_name'], 'html'=>$customer['processed_description']);
            } 
        } else {
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>"We don't currently have any adjudicators.");
        } 
    }

    if( isset($sponsors) && count($sponsors) > 0 ) {
        $page['blocks'][] = array('type'=>'sponsors', 'section'=>'sponsors', 'title'=>'', 'sponsors'=>$sponsors);
    }

    //
    // Add the submenu
    //
    $page['submenu'] = array();
    $page['submenu']['about'] = array('name'=>'About', 'url'=>$args['base_url'] . '/about');

    //
    // Get the sections
    //
    $strsql = "SELECT name, permalink "
        . "FROM ciniki_musicfestival_sections "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        foreach($rc['rows'] as $row) {
            $page['submenu'][$row['permalink']] = array('name'=>$row['name'], 'url'=>$args['base_url'] . '/' . $row['permalink']);
        }
    }
    $page['submenu']['adjudicators'] = array('name'=>'Adjudicators', 'url'=>$args['base_url'] . '/adjudicators');
    if( isset($ciniki['session']['customer']['id']) ) {
        $page['submenu']['registrations'] = array('name'=>'Registrations', 'url'=>$args['base_url'] . '/registrations');
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
