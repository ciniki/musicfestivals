<?php
//
// Description
// -----------
// This function will generate the profile form for volunteers to signup or update their info.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountVolunteerProfileProcess(&$ciniki, $tnid, &$request, $args) {

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/volunteer';
    $festival = $args['festival'];

    //
    // Build the fields
    //
    $blocks[] = [
        'type' => 'title',
        'level' => 2,
        'title' => 'Profile',
        ];

    if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' 
        && isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel'
        ) {
        if( isset($args['signup']) && $args['signup'] == 'yes' ) {
            header("Location: " . $request['ssl_domain_base_url'] . '/account');
            return array('stat'=>'exit');
        }
        header("Location: " . $base_url);
        return array('stat'=>'exit');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountContactProcess');
    $rc = ciniki_customers_wng_accountContactProcess($ciniki, $tnid, $request, [
        'ref' => 'ciniki.musicfestivals.volunteer',
        'editable' => isset($args['editable']) ? $args['editable'] : 'no',
        'redirect-url' => '',
        'business-name' => 'no',
        'phone-labels' => 'Cell,Home',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $fields = $rc['fields'];
    $problem_list = isset($rc['problems']) ? $rc['problems'] : '';

    //
    // Check if this is a signup
    // 
    if( (!isset($args['volunteer']['id']) || $args['volunteer']['id'] == 0) 
        && isset($_POST['f-action']) && $_POST['f-action'] == 'update'
        && $problem_list == ''
        ) {
        //
        // Add the volunteer to the database
        //
        $args['volunteer'] = [
            'festival_id' => $festival['id'],
            'customer_id' => $request['session']['customer']['id'],
            'status' => 10,
            'shortname' => '',
            'local_festival_id' => isset($_POST['f-local_festival_id']) ? $_POST['f-local_festival_id'] : 0,
            'notes' => isset($_POST['f-notes']) ? $_POST['f-notes'] : '',
            ];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.volunteer', $args['volunteer'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
        $args['volunteer']['id'] = $rc['id'];
    }

    //
    // Add the musicfestival fields
    //
    if( isset($festival['volunteers-availability-days']) && $festival['volunteers-availability-days'] != '' ) {
        $fields['days'] = [
            'id' => 'days',
            'ftype' => 'break',
            'label' => 'Availability',
            ];
        $days = preg_split('/\s*\n\s*/', trim($festival['volunteers-availability-days']));
        $volunteer_days = isset($args['volunteer']['available_days']) ? explode('::', $args['volunteer']['available_days']) : [];
        if( count($days) > 0 && $args['editable'] == 'yes' ) {
            $updated = 'no';
            foreach($days as $day) {
                $id = 'days-' . preg_replace("/[^a-zA-Z0-9_\-]/", '', $day);
                $fields[$id] = [
                    'id' => $id,
                    'ftype' => 'checkbox',
                    'label' => $day,
                    'class' => 'small',
                    'value' => (in_array($day, $volunteer_days) ? 'on' : ''),
                    ];
                if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
                    if( isset($_POST["f-{$id}"]) && $_POST["f-{$id}"] == 'on' ) {
                        $fields[$id]['value'] = 'on';
                        if( !in_array($day, $volunteer_days) ) {
                            $volunteer_days[] = $day;
                            $updated = 'yes';
                        }
                    }
                    elseif( (!isset($_POST["f-{$id}"]) || $_POST["f-{$id}"] != 'on') ) {
                        $fields[$id]['value'] = '';
                        if( in_array($day, $volunteer_days) ) {
                            unset($volunteer_days[array_search($day, $volunteer_days)]);
                            $updated = 'yes';
                        }
                    }
                }
            }
            if( $updated == 'yes' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
                $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $tnid,
                    'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
                    'volunteer_id', $args['volunteer']['id'], 10, $volunteer_days);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list = "Unable to update days";
                }
            }
        } elseif( count($days) > 0 ) {
            $days_list = '';
            foreach($days as $day) {
                if( in_array($day, $volunteer_days) ) {
                    $days_list .= ($days_list != '' ? ', ' : '') . $day;
                }
            }
            if( $days_list != '' ) {
                $fields['days'] = [
                    'id' => 'days',
                    'ftype' => 'text',
                    'label' => 'Availability',
                    'size' => 'large',
                    'editable' => 'no',
                    'value' => $days_list,
                    ];
            }
        }

        if( isset($festival['volunteers-availability-times']) && $festival['volunteers-availability-times'] != '' ) {
            $times = preg_split('/\s*\n\s*/', trim($festival['volunteers-availability-times']));
            $volunteer_times = isset($args['volunteer']['available_times']) ? explode('::', $args['volunteer']['available_times']) : [];
            if( count($times) > 0 && $args['editable'] == 'yes' ) {
                $fields['times'] = [
                    'id' => 'times',
                    'ftype' => 'content',
                    'label' => 'Times',
                    ];
                $updated = 'no';
                foreach($times as $time) {
                    $id = 'time-' . preg_replace("/[^a-zA-Z0-9_\-]/", '', $time);
                    $fields[$id] = [
                        'id' => $id,
                        'ftype' => 'checkbox',
                        'label' => $time,
                        'class' => 'small',
                        'value' => (in_array($time, $volunteer_times) ? 'on' : ''),
                        ];
                    if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
                        if( isset($_POST["f-{$id}"]) && $_POST["f-{$id}"] == 'on' ) {
                            $fields[$id]['value'] = 'on';
                            if( !in_array($time, $volunteer_times) ) {
                                $volunteer_times[] = $time;
                                $updated = 'yes';
                            }
                        }
                        elseif( (!isset($_POST["f-{$id}"]) || $_POST["f-{$id}"] != 'on') ) {
                            $fields[$id]['value'] = '';
                            if( in_array($time, $volunteer_times) ) {
                                unset($volunteer_times[array_search($time, $volunteer_times)]);
                                $updated = 'yes';
                            }
                        }
                    }
                }
                if( $updated == 'yes' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
                    $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $tnid,
                        'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
                        'volunteer_id', $args['volunteer']['id'], 20, $volunteer_times);
                    if( $rc['stat'] != 'ok' ) {
                        $problem_list = "Unable to update availability times";
                    }
                }
            } elseif( count($times) > 0 ) {
                $times_list = '';
                foreach($times as $time) {
                    if( in_array($time, $volunteer_times) ) {
                        $times_list .= ($times_list != '' ? ', ' : '') . $time;
                    }
                }
                if( $times_list != '' ) {
                    $fields['times'] = [
                        'id' => 'times',
                        'ftype' => 'text',
                        'label' => 'Times',
                        'size' => 'large',
                        'editable' => 'no',
                        'value' => $times_list,
                        ];
                }
            }

        }
    }

    if( isset($festival['volunteers-skills']) && $festival['volunteers-skills'] != '' ) {
        $skills = preg_split('/\s*,\s*/', trim($festival['volunteers-skills']));
        $volunteer_skills = isset($args['volunteer']['skills']) ? explode('::', $args['volunteer']['skills']) : [];
        if( count($skills) > 0 && $args['editable'] == 'yes' ) {
            $fields['skills'] = [
                'id' => 'skills',
                'ftype' => 'break',
                'label' => 'Skills',
                ];
            $updated = 'no';
            foreach($skills as $skill) {
                $id = 'skill-' . preg_replace("/[^a-zA-Z0-9_\-]/", '', $skill);
                $fields[$id] = [
                    'id' => $id,
                    'ftype' => 'checkbox',
                    'label' => $skill,
                    'class' => 'small',
                    'value' => (in_array($skill, $volunteer_skills) ? 'on' : ''),
                    ];
                if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
                    if( isset($_POST["f-{$id}"]) && $_POST["f-{$id}"] == 'on' ) {
                        $fields[$id]['value'] = 'on';
                        if( !in_array($skill, $volunteer_skills) ) {
                            $volunteer_skills[] = $skill;
                            $updated = 'yes';
                        }
                    }
                    elseif( (!isset($_POST["f-{$id}"]) || $_POST["f-{$id}"] != 'on') ) {
                        $fields[$id]['value'] = '';
                        if( in_array($skill, $volunteer_skills) ) {
                            unset($volunteer_skills[array_search($skill, $volunteer_skills)]);
                            $updated = 'yes';
                        }
                    }
                }
            }
            if( $updated == 'yes' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
                $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $tnid,
                    'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
                    'volunteer_id', $args['volunteer']['id'], 30, $volunteer_skills);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list = "Unable to update skills";
                }
            }
        } elseif( count($skills) > 0 ) {
            $skill_list = '';
            foreach($skills as $skill) {
                if( in_array($skill, $volunteer_skills) ) {
                    $skill_list .= ($skill_list != '' ? ', ' : '') . $skill;
                }
            }
            if( $skill_list != '' ) {
                $fields['skills'] = [
                    'id' => 'skills',
                    'ftype' => 'text',
                    'label' => 'Skills',
                    'size' => 'large',
                    'editable' => 'no',
                    'value' => $skill_list,
                    ];
            }
        }
    }
    
    if( isset($festival['volunteers-disciplines']) && $festival['volunteers-disciplines'] != '' ) {
        $disciplines = preg_split('/\s*,\s*/', trim($festival['volunteers-disciplines']));
        $volunteer_disciplines = isset($args['volunteer']['disciplines']) ? explode('::', $args['volunteer']['disciplines']) : [];
        if( count($disciplines) > 0 && $args['editable'] == 'yes' ) {
            $fields['disciplines'] = [
                'id' => 'disciplines',
                'ftype' => 'break',
                'label' => 'Preferred Disciplines',
                ];
            $updated = 'no';
            foreach($disciplines as $discipline) {
                $id = 'discipline-' . preg_replace("/[^a-zA-Z0-9_\-]/", '', $discipline);
                $fields[$id] = [
                    'id' => $id,
                    'ftype' => 'checkbox',
                    'label' => $discipline,
                    'class' => 'small',
                    'value' => (in_array($discipline, $volunteer_disciplines) ? 'on' : ''),
                    ];
                if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
                    if( isset($_POST["f-{$id}"]) && $_POST["f-{$id}"] == 'on' ) {
                        $fields[$id]['value'] = 'on';
                        if( !in_array($discipline, $volunteer_disciplines) ) {
                            $volunteer_disciplines[] = $discipline;
                            $updated = 'yes';
                        }
                    }
                    elseif( (!isset($_POST["f-{$id}"]) || $_POST["f-{$id}"] != 'on') ) {
                        $fields[$id]['value'] = '';
                        if( in_array($discipline, $volunteer_disciplines) ) {
                            unset($volunteer_disciplines[array_search($discipline, $volunteer_disciplines)]);
                            $updated = 'yes';
                        }
                    }
                }
            }
            if( $updated == 'yes' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
                $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.musicfestivals', 'volunteertag', $tnid,
                    'ciniki_musicfestival_volunteer_tags', 'ciniki_musicfestivals_history',
                    'volunteer_id', $args['volunteer']['id'], 35, $volunteer_disciplines);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list = "Unable to update disciplines";
                }
            }
        } elseif( count($disciplines) > 0 ) {
            $discipline_list = '';
            foreach($disciplines as $discipline) {
                if( in_array($discipline, $volunteer_disciplines) ) {
                    $discipline_list .= ($discipline_list != '' ? ', ' : '') . $discipline;
                }
            }
            if( $discipline_list != '' ) {
                $fields['disciplines'] = [
                    'id' => 'disciplines',
                    'ftype' => 'text',
                    'label' => 'Preferred Disciplines',
                    'size' => 'large',
                    'editable' => 'no',
                    'value' => $discipline_list,
                    ];
            }
        }
    }

    //
    // Check what to do with form
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' && $problem_list == '' ) {
    
        if( isset($args['signup']) && $args['signup'] == 'yes' ) {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'success',
                'content' => (isset($festival['volunteers-applied-msg']) && $festival['volunteers-applied-msg'] != '' 
                    ? $festival['volunteers-applied-msg'] 
                    : 'Thank you for applying to be a volunteer, your application is being reviewed.'),
                ];
            $blocks[] = [
                'type' => 'buttons',
                'class' => 'aligncenter',
                'items' => [['url' => '/account', 'text' => 'Continue']],
                ];
        } else {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'success',
                'content' => 'Thank you for updating your profile.',
                ];
            $blocks[] = [
                'type' => 'buttons',
                'class' => 'aligncenter',
                'items' => [['url' => $base_url, 'text' => 'Continue']],
                ];
        }
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Decide what to display
    //
    if( isset($args['editable']) && $args['editable'] == 'yes' ) {
        $blocks[] = array(
            'type' => 'form',
            'guidelines' => '',
            'title' => 'Volunteer Profile',
            'class' => 'limit-width limit-width-60',
            'problem-list' => $problem_list,
            'cancel-label' => 'Cancel',
            'submit-label' => 'Save',
            'fields' => $fields,
            );
    }
    else {
        $blocks[] = array(
            'type' => 'form',
            'guidelines' => '',
            'title' => 'Contact Info',
            'class' => 'limit-width limit-width-60 viewonly',
            'problem-list' => '',
            'submit-hide' => 'yes',
            'fields' => $fields,
            );
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'limit-width limit-width-60 aligncenter',
            'list' => array(
                array('url' => $base_url . '/edit', 'text' => 'Edit Profile'),
                ),
            );
    }
   
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
