<?php
//
// Description
// -----------
// This function will genereate the form for recommendations both on website via private link, or through local website
// for a logged in adjudicator
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_recommendationFormGenerate(&$ciniki, $tnid, $request, $args) {

    $section = $args['section'];

    if( !isset($args['classes']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationClassesLoad');
        $rc = ciniki_musicfestivals_recommendationClassesLoad($ciniki, $tnid, $section);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $classes = isset($rc['classes']) ? $rc['classes'] : array();
    } else {
        $classes = $args['classes'];
    }

    //
    // Load the list of positions
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationPositions');
    $rc = ciniki_musicfestivals_recommendationPositions($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $positions = $rc['positions'];

    //
    // Generate the form fields
    //
    $form_errors = '';
    $form_sections = [ 
        'adjudicator' => [
            'id' => 'adjudicator',
            'label' => 'Adjudicator Information',
            'fields' => [],
            ],
        ];
    if( !isset($args['recommendation']['member_id']) || $args['recommendation']['member_id'] == 0 ) {
        $form_sections['adjudicator']['fields']['member_id'] = [
            'id' => 'member_id',
            'label' => 'Name of Festival',
            'ftype' => 'select',
            'options' => $args['members'],
            'complex_options' => array(
                'value' => 'id',
                'name' => 'name',
                ),
            'required' => 'yes',
            'value' => (isset($_POST['f-member_id']) ? $_POST['f-member_id'] : (isset($request['session']['ciniki.musicfestivals']['member_id']) ? $request['session']['ciniki.musicfestivals']['member_id'] : 0)),
            ];
    }
    $form_sections['adjudicator']['fields']['adjudicator_name'] = [
        'id' => 'adjudicator_name',
        'label' => "Adjudicator's Name",
        'ftype' => 'text',
        'required' => 'yes',
        'value' => '',
        ];
    if( isset($_POST['f-adjudicator_name']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_name']['value'] = $_POST['f-adjudicator_name'];
    } elseif( isset($request['session']['ciniki.musicfestivals']['adjudicator_name']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_name']['value'] = $request['session']['ciniki.musicfestivals']['adjudicator_name'];
    } elseif( isset($args['adjudicator']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_name']['value'] = $args['adjudicator']['name'];
    }
    $form_sections['adjudicator']['fields']['adjudicator_phone'] = [
        'id' => 'adjudicator_phone',
        'label' => "Adjudicator's Phone Number",
        'ftype' => 'text',
        'size' => 'small',
        'flex-basis' => '40%',
        'required' => 'yes',
        'value' => '',
        ];
    if( isset($_POST['f-adjudicator_phone']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_phone']['value'] = $_POST['f-adjudicator_phone'];
    } elseif( isset($request['session']['ciniki.musicfestivals']['adjudicator_phone']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_phone']['value'] = $request['session']['ciniki.musicfestivals']['adjudicator_phone'];
    } elseif( isset($args['adjudicator']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_phone']['value'] = $args['adjudicator']['phone'];
    }
    $form_sections['adjudicator']['fields']['adjudicator_email'] = [ 
        'id' => 'adjudicator_email',
        'label' => "Adjudicator's Email",
        'ftype' => 'text',
        'size' => 'small-medium',
        'flex-basis' => '40%',
        'required' => 'yes',
        'value' => '',
        ];
    if( isset($_POST['f-adjudicator_email']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_email']['value'] = $_POST['f-adjudicator_email'];
    } elseif( isset($request['session']['ciniki.musicfestivals']['adjudicator_email']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_email']['value'] = $request['session']['ciniki.musicfestivals']['adjudicator_email'];
    } elseif( isset($args['adjudicator']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_email']['value'] = $args['adjudicator']['email'];
    }
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
            'class_id' => $cid,
            'label' => $class['code'] . ' - ' . $class['name'],
            'fields' => array(),
            );
// Old code from 2024 to allow for 4 recommendations in some classes
//        if( in_array($class['code'], ['31012', '11007', '33009', '13007', '12007', '14007', '16007', '16031'])  ) {
//            $num_recommendations = 4;
//        } else {
//            $num_recommendations = 3;
//        } 
        $num_recommendations = 3;
        $num_alternates = 3;
        if( isset($args['adjudicator']['registrations']) ) {
            $registrations = [];
            if( isset($args['adjudicator']['registrations'][$class['code']]['registrations']) ) {
                $registrations = $args['adjudicator']['registrations'][$class['code']]['registrations'];
            }
            if( isset($args['adjudicator']['registrations']['']['registrations']) ) {
                foreach($args['adjudicator']['registrations']['']['registrations'] as $reg) {
                    $registrations[] = $reg;
                }
                uasort($registrations, function($a, $b) {   
                    return strcmp($a['name'], $b['name']);
                    });
            }
        }
        foreach($positions as $i => $position) {
//            $label = ($i == 1 ? '1st' : ($i == 2 ? '2nd' : ($i == 3 ? '3rd' : $i . 'th')));

                if( $i == 101 ) {
                    $form_sections[$cid]['fields']["break_{$i}_{$cid}"] = array(
                        'id' => "break_{$i}_{$cid}",
                        'ftype' => 'break',
                        'class' => 'break',
                        'label' => 'Alternates',
                        );
                }
                if( isset($args['existing'][$cid][$i]) ) {
                $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                    'id' => "recommendation_{$i}_{$cid}",
                    'label' => $position['label'],
                    'size' => 'small',
                    'flex-basis' => '100%',
                    'ftype' => 'text',
                    'editable' => 'no',
                    'exists' => 'yes',
                    'value' => 'Already Submitted',
                    );
            } else {
                if( isset($registrations) ) {
                    $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                        'id' => "recommendation_{$i}_{$cid}",
                        'label' => $position['label'],
                        'size' => 'small',
                        'flex-basis' => '75%',
                        'ftype' => 'select',
                        'options' => $registrations,
                        'value' => 0,
                        );
                    if( isset($_POST["f-recommendation_{$i}_{$cid}"]) ) {
                        $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"]['value'] = $_POST["f-recommendation_{$i}_{$cid}"];
                    } elseif( isset($args['recommendation']['entries'][$cid][$i]['local_reg_id']) ) {
                        $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"]['value'] = $args['recommendation']['entries'][$cid][$i]['local_reg_id'];
                    }
                } else {
                    $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                        'id' => "recommendation_{$i}_{$cid}",
                        'label' => $position['label'],
                        'size' => 'small',
                        'flex-basis' => '75%',
                        'ftype' => 'text',
                        'value' => (isset($_POST["f-recommendation_{$i}_{$cid}"]) ? $_POST["f-recommendation_{$i}_{$cid}"] : ''),
                        );
                }
                $form_sections[$cid]['fields']["recommendation_mark_{$i}_{$cid}"] = array(
                    'id' => "recommendation_mark_{$i}_{$cid}",
                    'label' => 'Mark',
                    'size' => 'tiny',
                    'flex-basis' => '10%',
                    'ftype' => 'select',
                    'options' => $mark_options,
                    'value' => '',
                    );
                if( isset($_POST["f-recommendation_mark_{$i}_{$cid}"]) ) {
                    $form_sections[$cid]['fields']["recommendation_mark_{$i}_{$cid}"]['value'] = $_POST["f-recommendation_mark_{$i}_{$cid}"];
                } elseif( isset($args['recommendation']['entries'][$cid][$i]['mark']) ) {
                    $form_sections[$cid]['fields']["recommendation_mark_{$i}_{$cid}"]['value'] = $args['recommendation']['entries'][$cid][$i]['mark'];
                }
            }
            $form_sections[$cid]['fields']["newline_{$i}_{$cid}"] = array(
                'id' => "newline_{$i}_{$cid}",
                'ftype' => 'newline',
                );
        }
/*        for($i = 101; $i <= (100+$num_alternates); $i++) {
            $label = ($i == 1 ? '1st' : ($i == 2 ? '2nd' : ($i == 3 ? '3rd' : $i . 'th')));
            if( isset($args['existing'][$cid][$i]) ) {
                $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                    'id' => "recommendation_{$i}_{$cid}",
                    'label' => $label . ' Recommendation',
                    'size' => 'small',
                    'flex-basis' => '100%',
                    'ftype' => 'text',
                    'editable' => 'no',
                    'value' => 'Already Submitted',
                    );
            } else {
                if( isset($registrations) ) {
                    $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                        'id' => "recommendation_{$i}_{$cid}",
                        'label' => $label . ' Alternate',
                        'size' => 'small',
                        'flex-basis' => '75%',
                        'ftype' => 'select',
                        'options' => $registrations,
                        'value' => 0,
                        );
                    if( isset($_POST["f-alternate_{$i}_{$cid}"]) ) {
                        $form_sections[$cid]['fields']["alternate_{$i}_{$cid}"]['value'] = $_POST["f-alternate_{$i}_{$cid}"];
                    } elseif( isset($args['recommendation']['entries'][$cid][(100+$i)]['local_reg_id']) ) {
                        $form_sections[$cid]['fields']["alternate_{$i}_{$cid}"]['value'] = $args['recommendation']['entries'][$cid][(100+$i)]['local_reg_id'];
                    }
                } else {
                    $form_sections[$cid]['fields']["alternate_{$i}_{$cid}"] = array(
                        'id' => "alternate_{$i}_{$cid}",
                        'label' => $label . ' Alternate',
                        'size' => 'small',
                        'flex-basis' => '75%',
                        'ftype' => 'text',
                        'value' => (isset($_POST["f-alternate_{$i}_{$cid}"]) ? $_POST["f-alternate_{$i}_{$cid}"] : ''),
                        );
                }
            }
            $form_sections[$cid]['fields']["alternate_mark_{$i}_{$cid}"] = array(
                'id' => "alternate_mark_{$i}_{$cid}",
                'label' => 'Mark',
                'size' => 'tiny',
                'flex-basis' => '10%',
                'ftype' => 'select',
                'options' => $mark_options,
                'value' => '',
                );
            if( isset($_POST["f-alternate_mark_{$i}_{$cid}"]) ) {
                $form_sections[$cid]['fields']["alternate_mark_{$i}_{$cid}"]['value'] = $_POST["f-alternate_mark_{$i}_{$cid}"];
            } elseif( isset($args['recommendation']['entries'][$cid][(100+$i)]['mark']) ) {
                $form_sections[$cid]['fields']["alternate_mark_{$i}_{$cid}"]['value'] = $args['recommendation']['entries'][$cid][(100+$i)]['mark'];
            }
            $form_sections[$cid]['fields']["newlineb_{$i}_{$cid}"] = array(
                'id' => "newlineb_{$i}_{$cid}",
                'ftype' => 'newline',
                );
        } */
    }
    if( isset($args['save-draft']) && $args['save-draft'] == 'yes' ) {
        $form_sections['save'] = array(
            'id' => 'save',
            'label' => '',
            'fields' => array(
                'cancel' => array(
                    'id' => 'cancel',
                    'ftype' => 'cancel', 
                    'label' => 'Cancel',
                    'url' => $args['cancel-url'], //"{$request['ssl_domain_base_url']}{$request['page']['path']}",
                    ),
                'save' => array(
                    'id' => 'save',
                    'ftype' => 'submit', 
                    'label' => 'Save Draft',
                    ),
                ),
           );
        $form_sections['submit'] = array(
            'id' => 'submit',
            'label' => 'Submit Recommendations',
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
                'submit' => array(
                    'id' => 'submit',
                    'ftype' => 'submit', 
                    'label' => 'Submit Recommendations',
                    ),
                ),
            );
    } else {
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
                    'url' => $args['cancel-url'], //"{$request['ssl_domain_base_url']}{$request['page']['path']}",
                    ),
                'submit' => array(
                    'id' => 'submit',
                    'ftype' => 'submit', 
                    'label' => 'Submit Recommendations',
                    ),
                ),
            );
    }

    return array('stat'=>'ok', 'form_errors'=>$form_errors, 'form_sections'=>$form_sections, 'classes'=>$classes);
}
?>
