<?php
//
// Description
// ===========
// This method will return all the information about an mail.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the mail is attached to.
// message_id:          The ID of the mail to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_messageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'allrefs'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Return All References'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'schedule_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule'),
        'division_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division'),
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if action to add object
    //
    if( isset($args['action']) && $args['action'] == 'addref' 
        && isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageref', $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.537', 'msg'=>'Unable to add the recipients', 'err'=>$rc['err']));
        }
    }
    //
    // Check if action to add object
    //
    elseif( isset($args['action']) && $args['action'] == 'removeref' 
        && isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {
        $strsql = "SELECT id, uuid "
            . "FROM ciniki_musicfestival_messagerefs "
            . "WHERE message_id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
            . "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'ref');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.490', 'msg'=>'Unable to load recipients', 'err'=>$rc['err']));
        }
        $rows = isset($rc['rows']) ? $rc['rows'] : array();
     
        foreach($rows as $row) {
            //
            // Remove ref
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.messageref', $row['id'], $row['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.538', 'msg'=>'Unable to remove the recipients', 'err'=>$rc['err']));
            }
        }
    } 
    //
    // Check if there is an update the message flags
    //
    elseif( isset($args['action']) && $args['action'] == 'updateflags' 
        && isset($args['flags']) && $args['flags'] != '' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.message', $args['message_id'], array('flags'=>$args['flags']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.498', 'msg'=>'Unable to update the message', 'err'=>$rc['err']));
        }
    }
    elseif( isset($args['action']) && $args['action'] == 'extractrecipients' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageExtractRecipients');
        $rc = ciniki_musicfestivals_messageExtractRecipients($ciniki, $args['tnid'], $args['message_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.555', 'msg'=>'Unable to extract recipients', 'err'=>$rc['err']));
        }
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Mail
    //
    if( $args['message_id'] == 0 ) {
        $message = array(
            'id'=>0,
            'festival_id'=>'',
            'subject'=>'',
            'status'=>'10',
            'flags' => 0,
            'content'=>'',
            'dt_scheduled'=>'',
            'dt_sent'=>'',
            'objects' => array(),
            'details' => array(
                array('label' => 'Status', 'value' => 'Draft'),
                array('label' => '# Competitors', 'value' => '0'),
                array('label' => '# Teachers', 'value' => '0'),
                ),
        );
    }

    //
    // Get the details for an existing Mail
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageLoad');
        $rc = ciniki_musicfestivals_messageLoad($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.475', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
        }
        
        if( isset($args['section_id']) && $args['section_id'] > 0 
            && isset($rc['sections'][$args['section_id']]['categories']) 
            ) {
            $rc['categories'] = $rc['sections'][$args['section_id']]['categories'];
            if( isset($args['category_id']) && $args['category_id'] > 0 
                && isset($rc['sections'][$args['section_id']]['categories'][$args['category_id']]['classes']) 
                ) {
                $rc['classes'] = $rc['sections'][$args['section_id']]['categories'][$args['category_id']]['classes'];
            }
        }
        if( isset($args['schedule_id']) && $args['schedule_id'] > 0 
            && isset($rc['schedule'][$args['schedule_id']]['divisions']) 
            ) {
            $rc['divisions'] = $rc['schedule'][$args['schedule_id']]['divisions'];
            if( isset($args['division_id']) && $args['division_id'] > 0 
                && isset($rc['schedule'][$args['schedule_id']]['divisions'][$args['division_id']]['timeslots']) 
                ) {
                $rc['timeslots'] = $rc['schedule'][$args['schedule_id']]['divisions'][$args['division_id']]['timeslots'];
            }
        }
        foreach(['sections', 'categories', 'classes', 'schedule', 'divisions', 'timeslots', 'teachers', 'competitors'] as $s) {
            if( isset($rc[$s]) ) {
                $rc[$s] = array_values($rc[$s]);
            }
        }
        //
        // Sort the objects
        //
        uasort($rc['message']['objects'], function($a, $b) {
            if( $a['seq'] == $b['seq'] ) {
                return strcmp($a['label'], $b['label']);
            }
            return $a['seq'] < $b['seq'] ? -1 : 1;
            });
        $rc['message']['objects'] = array_values($rc['message']['objects']);

        
        $rc['message']['send'] = 'no';
        if( $rc['message']['status'] == '10' 
            && ($rc['message']['num_competitors'] > 0 || $rc['message']['num_teachers'] > 0 )
            ) {
            $rc['message']['send'] = 'yes';
        }

        // 
        // Sort included/added to top when mail already sent/scheduled
        //
        if( $rc['message']['status'] > 10 ) {
            if( isset($rc['teachers']) ) {
                foreach($rc['teachers'] as $tid => $teacher) {
                    if( !isset($teacher['added']) && !isset($teacher['included']) && !isset($teacher['students']) ) {
                        unset($rc['teachers'][$tid]);
                    }
                }
            }
            if( isset($rc['competitors']) ) {
                foreach($rc['competitors'] as $cid => $competitor) {
                    if( !isset($competitor['added']) && !isset($competitor['included']) ) {
                        unset($rc['competitors'][$cid]);
                    }
                }
            }
        } else {
            if( isset($rc['teachers']) ) {
                uasort($rc['teachers'], function($a, $b) {
                    if( isset($a['added']) && isset($b['added']) ) {
                        return strcmp($a['name'], $b['name']);
                    } elseif( isset($a['added']) ) {
                        return -1;
                    } elseif( isset($b['added']) ) {
                        return 1;
                    }
                    if( isset($a['students']) && isset($b['students']) ) {
                        return strcmp($a['name'], $b['name']);
                    } elseif( isset($a['students']) ) {
                        return -1;
                    } elseif( isset($b['students']) ) {
                        return 1;
                    }
                    if( isset($a['included']) && isset($b['included']) ) {
                        return strcmp($a['name'], $b['name']);
                    }
                    elseif( isset($a['included']) ) {
                        return -1;
                    } 
                    elseif( isset($b['included']) ) {
                        return 1;
                    }
                    return strcmp($a['name'], $b['name']);
                });
                $rc['teachers'] = array_values($rc['teachers']);
            }
            if( isset($rc['competitors']) ) {
                uasort($rc['competitors'], function($a, $b) {
                    if( isset($a['added']) && isset($b['added']) ) {
                        return strcmp($a['name'], $b['name']);
                    } elseif( isset($a['added']) ) {
                        return -1;
                    } elseif( isset($b['added']) ) {
                        return 1;
                    }
                    if( isset($a['included']) && isset($b['included']) ) {
                        return strcmp($a['name'], $b['name']);
                    }
                    elseif( isset($a['included']) ) {
                        return -1;
                    } 
                    elseif( isset($b['included']) ) {
                        return 1;
                    }
                    return strcmp($a['name'], $b['name']);
                });
                $rc['competitors'] = array_values($rc['competitors']);
            }
        }

        return $rc;

    }

    return array('stat'=>'ok', 'message'=>$message);
}
?>
