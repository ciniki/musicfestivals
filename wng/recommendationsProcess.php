<?php
//
// Description
// -----------
// This function will generate the blocks to display recommendation forms for adjudicators to submit 
// entries to provincials
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_recommendationsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.592', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.593', 'msg'=>"No festival specified"));
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

    $num_recommendations = 3;
    $num_alternates = 3;

    //
    // Get the list of member festivals and their details
    //
    $strsql = "SELECT members.id, "
        . "members.name, "
        . "members.permalink, "
        . "members.category, "
        . "members.status, "
        . "members.synopsis, "
        . "members.customer_id "
        . "FROM ciniki_musicfestivals_members AS members "
        . "WHERE members.status = 10 " // Active
        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY members.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'category', 'status', 'synopsis', 'customer_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.594', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    //
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.permalink, "
        . "sections.name, "
        . "sections.recommendations_description AS description "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'permalink', 'fields'=>array('id', 'permalink', 'name', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.595', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();


    if( (isset($s['title']) && $s['title'] != '') || (isset($s['content']) && $s['content'] != '') ) {
        $blocks[] = array(
            'type' => (!isset($s['content']) || $s['content'] == '' ? 'title' : 'text'),
            'title' => isset($s['title']) ? $s['title'] : '',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'content' => isset($s['content']) ? $s['content'] : '',
            );
    }


    //
    // Processing
    //
    $display = 'sectionlist';


    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && isset($sections[$request['uri_split'][($request['cur_uri_pos']+1)]])
        ) {
        $section = $sections[$request['uri_split'][($request['cur_uri_pos']+1)]];

        //
        // Get the list of classes
        //
        $strsql = "SELECT classes.id, "
            . "classes.uuid, "
            . "classes.festival_id, "
            . "classes.category_id, "
            . "categories.id AS category_id, "
            . "categories.name AS category_name, "
            . "categories.primary_image_id AS category_image_id, "
            . "categories.synopsis AS category_synopsis, "
            . "categories.description AS category_description, "
            . "classes.code, "
            . "classes.name, "
            . "classes.permalink, "
            . "classes.sequence, "
            . "classes.flags, "
            . "earlybird_fee, "
            . "fee, "
            . "virtual_fee, "
            . "earlybird_plus_fee, "
            . "plus_fee "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'permalink', 
                    'sequence', 'flags'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.596', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
        }
        $classes = isset($rc['classes']) ? $rc['classes'] : array();

        //
        // Auto remove section name from class names
        //
        foreach($classes as $cid => $class) {
            $classes[$cid]['name'] = str_replace($section['name'] . ' - ', '', $classes[$cid]['name']);
            if( preg_match("/^([^-]+) - /", $section['name'], $m) ) {
                if( $m[1] != '' ) {
                    $classes[$cid]['name'] = str_replace($m[1] . ' - ', '', $classes[$cid]['name']);
                }
            }
        }
        
        //
        // Generate the form fields
        //
        $form_errors = '';
        $form_sections = array(
            'adjudicator' => array(
                'id' => 'adjudicator',
                'label' => 'Adjudicator Information',
                'fields' => array(
                    'member_id' => array(
                        'id' => 'member_id',
                        'label' => 'Name of Festival',
                        'ftype' => 'select',
                        'options' => $members,
                        'complex_options' => array(
                            'value' => 'id',
                            'name' => 'name',
                            ),
                        'required' => 'yes',
                        'value' => (isset($_POST['f-member_id']) ? $_POST['f-member_id'] : (isset($request['session']['ciniki.musicfestivals']['member_id']) ? $request['session']['ciniki.musicfestivals']['member_id'] : 0)),
                        ),
                    'adjudicator_name' => array(
                        'id' => 'adjudicator_name',
                        'label' => "Adjudicator's Name",
                        'ftype' => 'text',
                        'required' => 'yes',
                        'value' => (isset($_POST['f-adjudicator_name']) ? $_POST['f-adjudicator_name'] : (isset($request['session']['ciniki.musicfestivals']['adjudicator_name']) ? $request['session']['ciniki.musicfestivals']['adjudicator_name'] : '')),
                        ),
                    'adjudicator_phone' => array(
                        'id' => 'adjudicator_phone',
                        'label' => "Adjudicator's Phone Number",
                        'ftype' => 'text',
                        'size' => 'small',
                        'flex-basis' => '40%',
                        'required' => 'yes',
                        'value' => (isset($_POST['f-adjudicator_phone']) ? $_POST['f-adjudicator_phone'] : (isset($request['session']['ciniki.musicfestivals']['adjudicator_phone']) ? $request['session']['ciniki.musicfestivals']['adjudicator_phone'] : '')),
                        ),
                    'adjudicator_email' => array(
                        'id' => 'adjudicator_email',
                        'label' => "Adjudicator's Email",
                        'ftype' => 'text',
                        'size' => 'small-medium',
                        'flex-basis' => '40%',
                        'required' => 'yes',
                        'value' => (isset($_POST['f-adjudicator_email']) ? $_POST['f-adjudicator_email'] : (isset($request['session']['ciniki.musicfestivals']['adjudicator_email']) ? $request['session']['ciniki.musicfestivals']['adjudicator_email'] : '')),
                        ),
                    ),
                ),
            );
        $mark_options = array(
            '85' => '85',
            '86' => '86',
            '87' => '87',
            '88' => '88',
            '89' => '89',
            '90' => '90',
            '91' => '91',
            '92' => '92',
            '93' => '93',
            '94' => '94',
            '95' => '95',
            '96' => '96',
            '97' => '97',
            '98' => '98',
            '99' => '99',
            '100' => '100',
            );
        foreach($classes as $cid => $class) {
            $form_sections[$cid] = array(
                'id' => "class_{$cid}",
                'label' => $class['code'] . ' - ' . $class['name'],
                'fields' => array(),
                );
            for($i = 1; $i <= $num_recommendations; $i++) {
                $label = ($i == 1 ? '1st' : ($i == 2 ? '2nd' : ($i == 3 ? '3rd' : $i . 'th')));

                $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                    'id' => "recommendation_{$i}_{$cid}",
                    'label' => $label . ' Recommendation',
                    'size' => 'small',
                    'flex-basis' => '75%',
                    'ftype' => 'text',
                    'value' => (isset($_POST["f-recommendation_{$i}_{$cid}"]) ? $_POST["f-recommendation_{$i}_{$cid}"] : ''),
                    );
                $form_sections[$cid]['fields']["recommendation_mark_{$i}_{$cid}"] = array(
                    'id' => "recommendation_mark_{$i}_{$cid}",
                    'label' => 'Mark',
                    'size' => 'tiny',
                    'flex-basis' => '10%',
                    'ftype' => 'select',
                    'options' => $mark_options,
                    'value' => (isset($_POST["f-recommendation_mark_{$i}_{$cid}"]) ? $_POST["f-recommendation_mark_{$i}_{$cid}"] : ''),
                    );
                $form_sections[$cid]['fields']["newline_{$i}_{$cid}"] = array(
                    'id' => "newline_{$i}_{$cid}",
                    'ftype' => 'newline',
                    );
            }
            $form_sections[$cid]['fields']["break_{$i}_{$cid}"] = array(
                'id' => "break_{$i}_{$cid}",
                'ftype' => 'break',
                'class' => 'break',
                'label' => 'Alternates',
                );
            for($i = 1; $i <= $num_alternates; $i++) {
                $label = ($i == 1 ? '1st' : ($i == 2 ? '2nd' : ($i == 3 ? '3rd' : $i . 'th')));
                $form_sections[$cid]['fields']["alternate_{$i}_{$cid}"] = array(
                    'id' => "alternate_{$i}_{$cid}",
                    'label' => $label . ' Alternate',
                    'size' => 'small',
                    'flex-basis' => '75%',
                    'ftype' => 'text',
                    'value' => (isset($_POST["f-alternate_{$i}_{$cid}"]) ? $_POST["f-alternate_{$i}_{$cid}"] : ''),
                    );
                $form_sections[$cid]['fields']["alternate_mark_{$i}_{$cid}"] = array(
                    'id' => "alternate_mark_{$i}_{$cid}",
                    'label' => 'Mark',
                    'size' => 'tiny',
                    'flex-basis' => '10%',
                    'ftype' => 'select',
                    'options' => $mark_options,
                    'value' => (isset($_POST["f-alternate_mark_{$i}_{$cid}"]) ? $_POST["f-alternate_mark_{$i}_{$cid}"] : ''),
                    );
                $form_sections[$cid]['fields']["newlineb_{$i}_{$cid}"] = array(
                    'id' => "newlineb_{$i}_{$cid}",
                    'ftype' => 'newline',
                    );
            }
        }
        $form_sections['submit'] = array(
            'id' => 'submit',
            'label' => 'Submit',
            'fields' => array(
                'acknowledgement_label' => array(
                    'id' => 'acknowledgement_label',
                    'ftype' => 'content', 
                    'label' => 'Acknowledgement',
                    'description' => '',
                    'required' => 'yes',
                    ),
                'acknowledgement' => array(
                    'id' => 'acknowledgement',
                    'ftype' => 'checkbox', 
                    'label' => 'I acknowledge that I am the adjudicator for this discipline, and am recommending the participants that I feel are the best qualified to participate in the OMFA Provincial Finals.',
                    'required' => 'yes',
                    'value' => (isset($_POST["f-acknowledgement"]) ? $_POST["f-acknowledgement"] : ''),
                    ),
                'cancel' => array(
                    'id' => 'cancel',
                    'ftype' => 'cancel', 
                    'label' => 'Cancel',
                    'url' => "{$request['ssl_domain_base_url']}{$request['page']['path']}",
                    ),
                'submit' => array(
                    'id' => 'submit',
                    'ftype' => 'submit', 
                    'label' => 'Submit Recommendations',
                    ),
                ),
            );
        $display = 'form';

        //
        // Check if form has been submitted
        //
        if( isset($_POST['action']) && $_POST['action'] == 'submit' ) {
            header("Cache-Control: no cache");
//            session_cache_limiter("private_no_expire");
            //
            // Check adjudicator section
            //
            $email_content = "Section: {$section['name']}<br/>";
            $recommendation_args = array(
                'festival_id' => $s['festival-id'],
                'section_id' => $section['id'],
                );
            if( !isset($_POST['f-member_id']) || trim($_POST['f-member_id']) == 0 ) {
                $form_errors .= "You must specify the Name of Festival.<br/>";
            } else {
                $recommendation_args['member_id'] = $_POST['f-member_id'];
                $request['session']['ciniki.musicfestivals']['member_id'] = $_POST['f-member_id'];
                $member = $members[$_POST["f-member_id"]];
                $email_content .= "Festival: " . $member['name'] . "<br/>";
            }
            if( !isset($_POST['f-adjudicator_name']) || trim($_POST['f-adjudicator_name']) == '' ) {
                $form_errors .= "You must specify a Adjudicator Name.<br/>";
            } else {
                $recommendation_args['adjudicator_name'] = trim($_POST['f-adjudicator_name']);
                $request['session']['ciniki.musicfestivals']['adjudicator_name'] = trim($_POST['f-adjudicator_name']);
                $email_content .= "Adjudicator Name: " . $_POST['f-adjudicator_name'] . "<br/>";
            }
            if( !isset($_POST['f-adjudicator_phone']) || trim($_POST['f-adjudicator_phone']) == '' ) {
                $form_errors .= "You must specify a Adjudicator Phone Number.<br/>";
            } else {
                $recommendation_args['adjudicator_phone'] = trim($_POST['f-adjudicator_phone']);
                $request['session']['ciniki.musicfestivals']['adjudicator_phone'] = trim($_POST['f-adjudicator_phone']);
                $email_content .= "Adjudicator Phone: " . $_POST['f-adjudicator_phone'] . "<br/>";
            }
            if( !isset($_POST['f-adjudicator_email']) || trim($_POST['f-adjudicator_email']) == '' ) {
                $form_errors .= "You must specify a Adjudicator's Email.<br/>";
            } else {
                $recommendation_args['adjudicator_email'] = trim($_POST['f-adjudicator_email']);
                $request['session']['ciniki.musicfestivals']['adjudicator_email'] = trim($_POST['f-adjudicator_email']);
                $email_content .= "Adjudicator Email: " . $_POST['f-adjudicator_email'] . "<br/>";
            }
            if( !isset($_POST['f-acknowledgement']) || $_POST['f-acknowledgement'] != 'on' ) {
                $form_errors .= "You must accept the Acknowledgement.<br/>";
            } else {
                $recommendation_args['acknowledgement'] = 'yes';
                $email_content .= "Acknowledgement: yes<br/>";
            }
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $recommendation_args['date_submitted'] = $dt->format('Y-m-d H:i:s');
            $dt->setTimezone(new DateTimezone($intl_timezone));
            $email_content .= "Date Submitted: " . $dt->format('M j, Y g:i:s a') . "<br/>";
            $email_content .= "<br/>";
           
            //
            // Check to make sure at least 1 class is specified
            //
            $entries = array();
            foreach($classes as $cid => $class) {
                $class_entries = array();
                $class_email_content = "";
                if( isset($_POST["f-recommendation_1_{$cid}"]) && trim($_POST["f-recommendation_1_{$cid}"]) != '' ) {
                    if( !isset($_POST["f-recommendation_mark_1_{$cid}"]) || trim($_POST["f-recommendation_mark_1_{$cid}"]) == '' ) {
                        $form_errors .= "You must specify a Mark for your 1st Recommendation in " . $class['name'] . ".<br/>";
                    } else {
                        $class_entries[] = array(
                            'class_id' => $class['id'],
                            'position' => 1,
                            'name' => $_POST["f-recommendation_1_{$cid}"],
                            'mark' => $_POST["f-recommendation_mark_1_{$cid}"],
                            );
//                        $class_args['recommendation_1'] = $_POST["f-recommendation_1_{$cid}"];
//                        $class_args['recommendation_mark_1'] = $_POST["f-recommendation_mark_1_{$cid}"];
                        $class_email_content .= "1st Recommendation: " . $_POST["f-recommendation_1_{$cid}"] . ", mark: " . $_POST["f-recommendation_mark_1_{$cid}"] . "<br/>";
                    }
                }
                if( isset($_POST["f-recommendation_2_{$cid}"]) && trim($_POST["f-recommendation_2_{$cid}"]) != '' ) {
                    if( !isset($_POST["f-recommendation_mark_2_{$cid}"]) || trim($_POST["f-recommendation_mark_2_{$cid}"]) == '' ) {
                        $form_errors .= "You must specify a Mark for your 2nd Recommendation in " . $class['name'] . ".<br/>";
                    } else {
                        $class_entries[] = array(
                            'class_id' => $class['id'],
                            'position' => 2,
                            'name' => $_POST["f-recommendation_2_{$cid}"],
                            'mark' => $_POST["f-recommendation_mark_2_{$cid}"],
                            );
//                        $class_args['recommendation_2'] = $_POST["f-recommendation_2_{$cid}"];
//                        $class_args['recommendation_mark_2'] = $_POST["f-recommendation_mark_2_{$cid}"];
                        $class_email_content .= "2nd Recommendation: " . $_POST["f-recommendation_2_{$cid}"] . ", mark: " . $_POST["f-recommendation_mark_2_{$cid}"] . "<br/>";
                    }
                }
                if( isset($_POST["f-recommendation_3_{$cid}"]) && trim($_POST["f-recommendation_3_{$cid}"]) != '' ) {
                    if( !isset($_POST["f-recommendation_mark_3_{$cid}"]) || trim($_POST["f-recommendation_mark_3_{$cid}"]) == '' ) {
                        $form_errors .= "You must specify a Mark for your 3rd Recommendation in " . $class['name'] . ".<br/>";
                    } else {
                        $class_entries[] = array(
                            'class_id' => $class['id'],
                            'position' => 3,
                            'name' => $_POST["f-recommendation_3_{$cid}"],
                            'mark' => $_POST["f-recommendation_mark_3_{$cid}"],
                            );
//                        $class_args['recommendation_3rd'] = $_POST["f-recommendation_3_{$cid}"];
//                        $class_args['recommendation_mark_3rd'] = $_POST["f-recommendation_mark_3_{$cid}"];
                        $class_email_content .= "3rd Recommendation: " . $_POST["f-recommendation_3_{$cid}"] . ", mark: " . $_POST["f-recommendation_mark_3_{$cid}"] . "<br/>";
                    }
                }
                if( isset($_POST["f-alternate_1_{$cid}"]) && trim($_POST["f-alternate_1_{$cid}"]) != '' ) {
                    if( !isset($_POST["f-alternate_mark_1_{$cid}"]) || trim($_POST["f-alternate_mark_1_{$cid}"]) == '' ) {
                        $form_errors .= "You must specify a Mark for your 1st Alternate in " . $class['name'] . ".<br/>";
                    } else {
                        $class_entries[] = array(
                            'class_id' => $class['id'],
                            'position' => 101,
                            'name' => $_POST["f-alternate_1_{$cid}"],
                            'mark' => $_POST["f-alternate_mark_1_{$cid}"],
                            );
//                        $class_args['alternate_1'] = $_POST["f-alternate_1_{$cid}"];
//                        $class_args['alternate_mark_1'] = $_POST["f-alternate_mark_1_{$cid}"];
                        $class_email_content .= "1st Alternate: " . $_POST["f-alternate_1_{$cid}"] . ", mark: " . $_POST["f-alternate_mark_1_{$cid}"] . "<br/>";
                    }
                }
                if( isset($_POST["f-alternate_2_{$cid}"]) && trim($_POST["f-alternate_2_{$cid}"]) != '' ) {
                    if( !isset($_POST["f-alternate_mark_2_{$cid}"]) || trim($_POST["f-alternate_mark_2_{$cid}"]) == '' ) {
                        $form_errors .= "You must specify a Mark for your 2nd Alternate in " . $class['name'] . ".<br/>";
                    } else {
                        $class_entries[] = array(
                            'class_id' => $class['id'],
                            'position' => 102,
                            'name' => $_POST["f-alternate_2_{$cid}"],
                            'mark' => $_POST["f-alternate_mark_2_{$cid}"],
                            );
//                        $class_args['alternate_2'] = $_POST["f-alternate_2_{$cid}"];
//                        $class_args['alternate_mark_2'] = $_POST["f-alternate_mark_2_{$cid}"];
                        $class_email_content .= "2nd Alternate: " . $_POST["f-alternate_2_{$cid}"] . ", mark: " . $_POST["f-alternate_mark_2_{$cid}"] . "<br/>";
                    }
                }
                if( isset($_POST["f-alternate_3_{$cid}"]) && trim($_POST["f-alternate_3_{$cid}"]) != '' ) {
                    if( !isset($_POST["f-alternate_mark_3_{$cid}"]) || trim($_POST["f-alternate_mark_3_{$cid}"]) == '' ) {
                        $form_errors .= "You must specify a Mark for your 3rd Alternate in " . $class['name'] . ".<br/>";
                    } else {
                        $class_entries[] = array(
                            'class_id' => $class['id'],
                            'position' => 103,
                            'name' => $_POST["f-alternate_3_{$cid}"],
                            'mark' => $_POST["f-alternate_mark_3_{$cid}"],
                            );
//                        $class_args['alternate_3rd'] = $_POST["f-alternate_3_{$cid}"];
//                        $class_args['alternate_mark_3rd'] = $_POST["f-alternate_mark_3_{$cid}"];
                        $class_email_content .= "3rd Alternate: " . $_POST["f-alternate_3_{$cid}"] . ", mark: " . $_POST["f-alternate_mark_3_{$cid}"] . "<br/>";
                    }
                }

                //
                // Check if at least 1 recommendation or alternate specified
                //
                if( count($class_entries) > 0 ) {
                    foreach($class_entries as $entry) {
                        $entries[] = $entry;
                    }
                    $email_content .= "<b>{$class['code']} - {$class['name']}</b><br/>"
                        . $class_email_content
                        . "<br/>";
                }
            }

            if( count($entries) == 0 && $form_errors == '' ) {
                $form_errors .= "You must specify at least 1 class recommendation.<br/>";
            }

            //
            // When no errors, save to the database
            //
            if( $form_errors == '' ) {
                //
                // Start transaction
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
                $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            
                //
                // Add the submission
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.recommendation', $recommendation_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $form_errors .= "We had an expected error, please contact us for help.";
                    error_log(print_r($rc, true));
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                }
                $recommendation_id = $rc['id'];

                foreach($entries as $entry) {
                    if( $form_errors == '' ) {
                        $entry['recommendation_id'] = $recommendation_id;
                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.recommendationentry', $entry, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $form_errors .= "We had an expected error, please contact us for help.";
                            error_log(print_r($rc, true));
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                        }
                    }
                }

                //
                // Commit the transaction
                //
                $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }

                //
                // Send an email to the adjudicator
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                    'object' => 'ciniki.musicfestivals.recommendation',
                    'object_id' => $recommendation_id,
                    'subject' => "Thank you for your submission", // [{$recommendation_args['adjudicator_email']}]",
                    'html_content' => $email_content,
                    'customer_name' => $recommendation_args['adjudicator_name'],
                    'customer_email' => $recommendation_args['adjudicator_email'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    $blocks[] = array(
                        'type' => 'msg',
                        'level' => 'error',
                        'content' => 'Your recommendations have been saved, but we were unable to send you followup. Please contact us if you have any questions.',
                        );
                } else {
                    $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
                }

                //
                // Check for the member email address
                //
                if( isset($member) && $member['customer_id'] > 0 ) {
                    //
                    // Lookup customer record
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
                    $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, 
                        array('customer_id'=>$member['customer_id'], 'phones'=>'no', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    if( isset($rc['customer']['emails'][0]['email']['address']) ) {
                        $customer = $rc['customer'];
                        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                            'object' => 'ciniki.musicfestivals.recommendation',
                            'object_id' => $recommendation_id,
                            'subject' => "We have received adjudicator recommendations for " . $member['name'], // [{$customer['emails'][0]['email']['address']}]",
                            'html_content' => $email_content,
                            'customer_id' => $member['customer_id'],
                            'customer_name' => $customer['display_name'],
                            'customer_email' => $customer['emails'][0]['email']['address'],
                            ));
                        if( $rc['stat'] != 'ok' ) {
                            error_log("Unable to email member contact for recommendation: " . $recommedation_id);
                        } else {
                            $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
                        }
                    } else {
                        error_log("No member contact info found for recommendation: " . $recommendation_id);
                    }
                }

                //
                // Send an email to the emails specified
                //
                if( isset($s['notify-emails']) && $s['notify-emails'] != '' ) {
                    $emails = explode(',', $s['notify-emails']);
                    foreach($emails as $email) {
                        $email = trim($email);
                        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                            'object' => 'ciniki.musicfestivals.recommendation',
                            'object_id' => $recommendation_id,
                            'subject' => "Adjudicators Recommendation: " . $member['name'] . " - " . $section['name'],
                            'html_content' => $email_content,
                            'customer_email' => $email,
                            ));
                        if( $rc['stat'] != 'ok' ) {
                            $blocks[] = array(
                                'type' => 'msg',
                                'level' => 'error',
                                'content' => 'Your recommendations have been saved, but we were unable to send you followup. Please contact us if you have any questions.',
                                );
                        } else {
                            $ciniki['emailqueue'][] = array('mail_id' => $rc['id'], 'tnid'=>$tnid);
                        }
                    }
                }

                $request['session']['ciniki.musicfestivals']['recommendation-submit-msg'] = 'Thank you for your recommendations for ' . $section['name'];
                header("Location: {$request['ssl_domain_base_url']}{$request['page']['path']}");
                return array('stat'=>'exit');
            }
        }
    }

    if( $display == 'form' ) {

        if( $form_errors != '' ) {
            $form_errors = "<b>Please correct the following errors:</b><br/><br/>" . $form_errors;
        }
        $blocks[] = array(
            'type' => 'text',
            'level' => 2,
            'title' => $section['name'] . ' Recommendations',
            'content' => $section['description'],
            );
        $blocks[] = array(
            'type' => 'form',
            'form-id' => 'section-form',
            'class' => 'limit-width limit-width-90 musicfestival-recommendations',
            'problem-list' => $form_errors,
            'cancel-label' => 'Cancel',
            'section-selector' => 'yes',
            'form-sections' => $form_sections,
            );
    } 
    elseif( $display == 'sectionlist' ) {
       
        if( isset($request['session']['ciniki.musicfestivals']['recommendation-submit-msg']) ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'success',
                'content' => $request['session']['ciniki.musicfestivals']['recommendation-submit-msg'],
                );
            unset($request['session']['ciniki.musicfestivals']['recommendation-submit-msg']);
        }


        $groups = array();
        foreach($sections as $sid => $section) {
            $group_name = $section['name'];
            $button_label = 'Submit Recommendations';
            if( !isset($groups[$group_name]) ) {
                $groups[$group_name] = array( 
                    'title' => $group_name,
                    'buttons' => "<a class='button' href='{$request['ssl_domain_base_url']}{$request['page']['path']}/{$section['permalink']}'>{$button_label}</a>&nbsp;&nbsp;",
                    );
            } else {
                $groups[$group_name]['buttons'] .= " <a class='button' href='{$request['ssl_domain_base_url']}{$request['page']['path']}/{$section['permalink']}'>{$button_label}</a>";
            }
        }
        $blocks[] = array(
            'type' => 'table', 
            'section' => 'syllabus', 
            'headers' => 'no',
            'class' => 'fold-at-40 musicfestival-syllabus',
            'columns' => array(
                array('label'=>'Section', 'fold-label'=>'', 'field'=>'title', 'class'=>'section-title'),
                array('label'=>'Buttons', 'fold-label'=>'', 'field'=>'buttons', 'class'=>'alignleft fold-alignleft buttons'),
                ),
            'rows' => $groups,
            );
    }



    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
