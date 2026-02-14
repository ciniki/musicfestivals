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
        . "customers.customer_id "
        . "FROM ciniki_musicfestivals_members AS members "
        . "LEFT JOIN ciniki_musicfestival_member_customers AS customers ON ("
            . "members.id = customers.member_id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE members.status = 10 " // Active
        . "AND (members.flags&0x01) = 0 " // Recommendation NOT submitted via local ciniki website
        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY members.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'category', 'status', 'synopsis'),
            ),
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('id'=>'customer_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.594', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();
    if( isset($_POST['f-member_id']) && isset($members[$_POST['f-member_id']]) ) {
        $member = $members[$_POST['f-member_id']];
    }

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
        // Generate the form
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'recommendationFormGenerate');
        $rc = ciniki_musicfestivals_wng_recommendationFormGenerate($ciniki, $tnid, $request, [
            'section' => $section,
            'members' => $members,
            'cancel-url' => "{$request['ssl_domain_base_url']}{$request['page']['path']}",
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1456', 'msg'=>'', 'err'=>$rc['err']));
        }
        $form_sections = $rc['form_sections'];
        $form_errors = $rc['form_errors'];
        $classes = $rc['classes'];
        $display = 'form';

        //
        // Check if form has been submitted
        //
        if( isset($_POST['action']) && $_POST['action'] == 'submit' ) {
            header("Cache-Control: no cache");

            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'recommendationSave');
            $rc = ciniki_musicfestivals_wng_recommendationSave($ciniki, $tnid, $request, [
                'form_sections' => $form_sections,
                'classes' => $classes,
                'members' => $members,
                'recommendation' => [
                    'id' => 0,
                    'festival_id' => $s['festival-id'],
                    'member_id' => $_POST['f-member_id'],
                    'section_id' => $section['id'],
                    ],
                ]);
            if( $rc['stat'] != 'ok' ) {
                if( isset($rc['form_errors']) ) {
                    $form_errors = $rc['form_errors'];
                } else {
                    $form_errors = $rc['err']['msg'];
                }
//            } else {
//                header("Location: $base_url");
//                return array('stat'=>'exit');
            }

            //
            // When no errors, save to the database
            //
            if( $form_errors == '' ) {
                $recommendation = $rc['recommendation'];
                $recommendation['section_name'] = $section['name'];
                $recommendation['member_name'] = $members[$recommendation['member_id']]['name'];
/*                //
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
                $recommendation = $recommendation_args;
                $recommendation['acknowledgement'] = 'yes';
                $recommendation['id'] = $rc['id'];
                $recommendation['entries'] = [];
                $recommendation['date_submitted'] = $dt->format('M j, Y g:i:s a');

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
                    $recommendation['entries'][$entry['class_id']][$entry['position']] = $entry;
                }

                //
                // Commit the transaction
                //
                $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
*/
                //
                // Email the submission
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationEmail');
                $rc = ciniki_musicfestivals_recommendationEmail($ciniki, $tnid, [
                    'recommendation' => $recommendation,
                    'classes' => $classes,
                    'adjudicator-subject' => 'Thank you for your submission',
                    'member-subject' => "We have received adjudicator recommendations for " . $member['name'] . ' in ' . $section['name'],
                    'members' => isset($member['customers']) ? $member['customers'] : [],
                    'notify-subject' => "Recommendations for " . $member['name'] . ' in ' . $section['name'],
                    'nofity-emails' => isset($s['notify-emails']) ? $s['notify-emails'] : '',
                    'email-type' => 'submitted',
                    ]);
                if( $rc['stat'] != 'ok' ) {
                    $blocks[] = array(
                        'type' => 'msg',
                        'level' => 'error',
                        'content' => $rc['err']['msg'],
                        );
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
