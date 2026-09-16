<?php
//
// Description
// -----------
// This function is the next generation of form for recommendations. This is a simplified process for the form, 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_accountRecommendationFormGenerate(&$ciniki, $tnid, $request, $args) {

    $section = $args['section'];

/*    if( !isset($args['classes']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationClassesLoad');
        $rc = ciniki_musicfestivals_recommendationClassesLoad($ciniki, $tnid, $section);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $classes = isset($rc['classes']) ? $rc['classes'] : array();
    } else {
        $classes = $args['classes'];
    } 
*/

    if( !isset($args['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.883', 'msg'=>'Missing class'));
    }
    $class = $args['class'];

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
    $form_sections['adjudicator']['fields']['adjudicator_name'] = [
        'id' => 'adjudicator_name',
        'label' => "Adjudicator's Name",
        'ftype' => 'text',
        'size' => 'large',
        'required' => 'yes',
        'editable' => isset($args['edit-name']) ? $args['edit-name'] : 'yes',
        'value' => '',
        ];
    if( isset($_POST['f-adjudicator_name']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_name']['value'] = $_POST['f-adjudicator_name'];
    } elseif( isset($request['session']['ciniki.musicfestivals']['adjudicator_name']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_name']['value'] = $request['session']['ciniki.musicfestivals']['adjudicator_name'];
    } elseif( isset($args['adjudicator']) ) {
        $form_sections['adjudicator']['fields']['adjudicator_name']['value'] = $args['adjudicator']['name'];
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
    } elseif( isset($args['recommendation']['adjudicator_email']) && $args['recommendation']['adjudicator_email'] != '' ) {
        $form_sections['adjudicator']['fields']['adjudicator_email']['value'] = $args['recommendation']['adjudicator_email'];
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
//    foreach($classes as $cid => $class) {
    $cid = $class['id'];
        $form_sections["{$cid}"] = array(
            'id' => "{$cid}",
            'class_id' => $cid,
            'label' => $class['code'] . ' - ' . $class['name'],
            'fields' => array(),
            );
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
            }
            uasort($registrations, function($a, $b) {   
                if( $a['name'] == $b['name'] ) {
                    if( $a['class'] == $b['class'] ) {
                        return strcmp($a['title'], $b['title']);
                    }
                    return strcmp($a['class'], $b['class']);
                }
                return strcmp($a['name'], $b['name']);
                });
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
//                if( isset($registrations) ) {
                    $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                        'id' => "recommendation_{$i}_{$cid}",
                        'label' => $position['label'],
                        'size' => 'small',
                        'flex-basis' => '75%',
                        'ftype' => 'dropdown',
                        'options' => $registrations,
                        'blank' => 'yes',
                        'blank-label' => '',
                        'option-line-1' => 'name',
                        'option-line-2' => 'class',
                        'option-line-3' => 'title',
                        'searchable' => 'yes',
                        'value' => '',
                        'onchange' => "C.form.setMark({$i},{$cid});",
                        );
                    if( isset($_POST["f-recommendation_{$i}_{$cid}"]) ) {
                        $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"]['value'] = $_POST["f-recommendation_{$i}_{$cid}"];
                    } elseif( isset($args['recommendation']['entries'][$cid][$i]['local_reg_id']) ) {
                        $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"]['value'] = $args['recommendation']['entries'][$cid][$i]['local_reg_id'];
                    }
/*                } else {
                    $form_sections[$cid]['fields']["recommendation_{$i}_{$cid}"] = array(
                        'id' => "recommendation_{$i}_{$cid}",
                        'label' => $position['label'],
                        'size' => 'small',
                        'flex-basis' => '75%',
                        'ftype' => 'text',
                        'value' => (isset($_POST["f-recommendation_{$i}_{$cid}"]) ? $_POST["f-recommendation_{$i}_{$cid}"] : ''),
                        );
                } */
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
//    }
    if( isset($args['save-draft']) && $args['save-draft'] == 'yes' ) {
        $form_sections['save'] = array(
            'id' => 'save',
            'label' => 'Save Draft',
            'fields' => array(
//                'cancel' => array(
//                    'id' => 'cancel',
//                    'ftype' => 'cancel', 
//                    'label' => 'Cancel',
//                    'url' => $args['cancel-url'], //"{$request['ssl_domain_base_url']}{$request['page']['path']}",
//                    ),
                'save_label' => array(
                    'id' => 'save_label',
                    'ftype' => 'content', 
                    'label' => 'Save Draft',
                    'description' => 'You can save your recommendations and come back to finish it later.<br><br><b>This will NOT submit to Provincials</b>.',
                    'required' => 'yes',
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

    return array('stat'=>'ok', 'form_errors'=>$form_errors, 'form_sections'=>$form_sections);
}
?>
