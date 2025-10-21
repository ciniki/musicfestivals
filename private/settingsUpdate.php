<?php
//
// Description
// -----------
// This function will update the festival settings from the supplier array.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// festival_id:     The ID of the festival
// args:            The array to search for the settings in.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_settingsUpdate(&$ciniki, $tnid, $festival_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

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
    // Get the current settings
    //
    $strsql = "SELECT id, uuid, detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'settings', 'fname'=>'detail_key', 'fields'=>array('id', 'uuid', 'detail_key', 'detail_value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = array();
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }

    // FIXME: Some of the settings in db ciniki_musicfestivals need to move into settings
    //
    // Check for any settings and add/update
    //
    $valid_settings = array(
        'syllabus-rules-include', 
        'syllabus-class-icons', 
        'syllabus-section-images',
        'program-separate-classes',
        'program-class-format',
        'schedule-division-header-format',
        'schedule-division-header-labels',
        'schedule-separate-classes',
        'schedule-class-format',
        'schedule-names',
        'schedule-include-pronouns',
        'schedule-titles',
        'schedule-video-urls',
        'schedule-header',
        'schedule-footer',
        'schedule-footerdate',
        'schedule-section-page-break',
        'schedule-division-page-break',
        'schedule-date-format',
        'schedule-continued-label',
        'schedule-word-template',
        'runsheets-page-orientation',
        'runsheets-include-pronouns',
        'runsheets-separate-classes',
        'runsheets-class-format',
        'runsheets-timeslot-description',
        'runsheets-timeslot-singlepage',
        'runsheets-perftime-show',
        'runsheets-mark',
        'runsheets-advance-to',
        'runsheets-internal-notes',
        'runsheets-registration-runnotes',
        'runsheets-registration-notes',
        'runsheets-competitor-notes',
        'runsheets-competitor-age',
        'runsheets-competitor-city',
        'runsheets-footer-msg',
        'comments-header-adjudicator',
        'comments-include-pronouns',
        'comments-class-format',
        'comments-timeslot-datetime',
        'comments-mark-ui',
        'comments-mark-adjudicator',
        'comments-mark-competitor',
        'comments-mark-pdf',
        'comments-mark-label',
        'comments-placement-ui',
        'comments-placement-adjudicator',
        'comments-placement-competitor',
        'comments-placement-pdf',
        'comments-placement-label',
        'comments-placement-autofill',
        'comments-placement-options',
        'comments-level-ui',
        'comments-level-adjudicator',
        'comments-level-competitor',
        'comments-level-pdf',
        'comments-level-label',
        'comments-level-autofill',
        'comments-adjudicator-signature',
        'comments-adjudicator-fontsig',
        'comments-footer-msg',
        'comments-paper-size',
        'comments-sorting',
        'adjudications-online-live',
        'adjudications-runsheets-live',
        'adjudications-runsheets-virtual',
        'adjudications-online-intro',
        'adjudications-virtual-instructions',
        'adjudications-live-instructions',
        'adjudications-online-review',
        'certificates-include-pronouns',
        'certificates-class-format',
        'certificates-use-group-numpeople',
        'certificates-sorting',
        'age-restriction-msg',
        'waiver-general-name',
        'waiver-general-title',
        'waiver-general-msg',
        'waiver-photo-status',
        'waiver-photo-title',
        'waiver-photo-msg',
        'waiver-photo-option-yes',
        'waiver-photo-option-no',
        'waiver-name-status',
        'waiver-name-title',
        'waiver-name-msg',
        'waiver-name-option-yes',
        'waiver-name-option-no',
        'waiver-second-name',
        'waiver-second-title',
        'waiver-second-msg',
        'waiver-third-name',
        'waiver-third-title',
        'waiver-third-msg',
        'president-name',
        'inperson-choice-msg',
        'virtual-choice-msg',
        'customer-type-intro-msg',
        'customer-type-parent-button-label',
        'customer-type-teacher-button-label',
        'customer-type-adult-button-label',
        'registration-parent-msg',
        'registration-teacher-msg',
        'registration-adult-msg',
        'registration-participation-label',
        'registration-class-participation',
        'registration-title-label',
        'registration-composer-label',
        'registration-movements-label',
        'registration-length-label',
        'registration-length-format',
        'registration-status-31-label',
        'registration-status-32-label',
        'registration-status-33-label',
        'registration-status-34-label',
        'registration-status-35-label',
        'registration-status-36-label',
        'registration-status-37-label',
        'registration-status-38-label',
        'registration-status-50-label',
        'registration-status-51-label',
        'registration-status-52-label',
        'registration-status-53-label',
        'registration-status-54-label',
        'registration-status-55-label',
        'registration-status-5-colour',
        'registration-status-10-colour',
        'registration-status-31-colour',
        'registration-status-32-colour',
        'registration-status-33-colour',
        'registration-status-34-colour',
        'registration-status-35-colour',
        'registration-status-36-colour',
        'registration-status-37-colour',
        'registration-status-38-colour',
        'registration-status-50-colour',
        'registration-status-51-colour',
        'registration-status-52-colour',
        'registration-status-53-colour',
        'registration-status-54-colour',
        'registration-status-55-colour',
        'registration-status-70-colour',
        'registration-status-75-colour',
        'registration-status-80-colour',
        'registration-notes-enable',
        'registration-crs-enable',
        'registration-crs-deadline',
        'registration-scrutineers-enable',
        'registration-scrutineers-status-10',
        'registration-scrutineers-status-31',
        'registration-scrutineers-status-32',
        'registration-scrutineers-status-33',
        'registration-scrutineers-status-34',
        'registration-scrutineers-status-35',
        'registration-scrutineers-status-36',
        'registration-scrutineers-status-37',
        'registration-scrutineers-status-38',
        'registration-scrutineers-status-39',
        'registration-scrutineers-status-50',
        'registration-scrutineers-status-51',
        'registration-scrutineers-status-52',
        'registration-scrutineers-status-53',
        'registration-scrutineers-status-54',
        'registration-scrutineers-status-55',
        'ui-registrations-class-format',
        'ui-registrations-count-status-5',
        'ui-registrations-count-status-70',
        'ui-registrations-count-status-75',
        'ui-registrations-count-status-77',
        'ui-registrations-count-status-80',
        'provincials-status-r30-colour',
        'provincials-status-r35-colour',
        'provincials-status-r50-colour',
        'provincials-status-r55-colour',
        'provincials-status-r60-colour',
        'provincials-status-r70-colour',
        'provincials-status-r90-colour',
        'provincials-status-a30-colour',
        'provincials-status-a35-colour',
        'provincials-status-a50-colour',
        'provincials-status-a55-colour',
        'provincials-status-a60-colour',
        'provincials-status-a70-colour',
        'provincials-status-a90-colour',
        'competitor-label-singular',
        'competitor-label-plural',
        'competitor-parent-msg',
        'competitor-teacher-msg',
        'competitor-adult-msg',
        'competitor-individual-age',
        'competitor-individual-age-label',
        'competitor-individual-study-level',
        'competitor-individual-study-level-label',
        'competitor-individual-last-exam',
        'competitor-individual-last-exam-label',
        'competitor-individual-instrument',
        'competitor-individual-phone-cell-label',
        'competitor-individual-phone-home',
        'competitor-individual-phone-home-label',
        'competitor-individual-email-confirm',
        'competitor-individual-etransfer-email',
        'competitor-individual-etransfer-email-label',
        'competitor-individual-etransfer-email-confirm',
        'competitor-individual-notes-enable',
        'competitor-group-age',
        'competitor-group-age-label',
        'competitor-group-parent-msg',
        'competitor-group-teacher-msg',
        'competitor-group-adult-msg',
        'competitor-group-instrument',
        'competitor-group-phone-cell-label',
        'competitor-group-phone-home',
        'competitor-group-email-confirm',
        'competitor-group-etransfer-email',
        'competitor-group-etransfer-email-label',
        'competitor-group-etransfer-email-confirm',
        'competitor-group-notes-enable',
        'provincial-festival-id',
        'scheduling-age-show',
        'scheduling-group-size-show',
        'scheduling-draft-show',
        'scheduling-disqualified-show',
        'scheduling-withdrawn-show',
        'scheduling-cancelled-show',
        'scheduling-teacher-show',
        'scheduling-accompanist-show',
        'scheduling-at-times',
        'advanced-scheduler-num-divisions',
        'advanced-scheduler-unscheduled-column',
        'advanced-scheduler-timeslot-descriptions',
        'advanced-scheduler-titles-show',
        'scheduling-seconds-show',
        'scheduling-timeslot-length',
        'scheduling-timeslot-startnum',
        'scheduling-timeslot-linking',
        'scheduling-linked-playoffs-placement',
        'scheduling-perftime-rounding',
        'scheduling-timeslot-autoshift',
        'scheduling-division-shortnames',
        'scheduling-timeslot-shortnames',
        'locations-categories',
        'locations-disciplines',
        'accolades-footer-msg',
        'accolades-include-descriptions',
        'accolades-include-donatedby',
        'accolades-include-amount',
        );
    foreach($valid_settings as $field) {
        if( isset($args[$field]) ) {
            if( $field == 'registration-crs-deadline' ) {
                try {
                    $dt = new DateTime($args[$field], new DateTimezone($intl_timezone));
                    $dt->setTimezone(new DateTimezone('UTC'));
                } catch(Exception $e) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1093', 'msg'=>'Invalid Date Format: ' . $args[$field], 'pmsg'=>$e->getMessage()));
                }
                $args[$field] = $dt->format('Y-m-d H:i:s');
            }
            if( isset($settings[$field]['detail_value']) && $settings[$field]['detail_value'] != $args[$field] ) {
                //
                // Update the setting
                //
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.setting', $settings[$field]['id'],
                    array('detail_value'=>$args[$field]), 
                    0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            } else if( !isset($settings[$field]) ) {
                //
                // Add the setting
                //
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.setting', 
                    array('festival_id'=>$festival_id, 'detail_key'=>$field, 'detail_value'=>$args[$field]), 
                    0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
            //
            // Check if other updates should be run
            //
            if( $field == 'scheduling-division-shortnames' && $args[$field] != 'manual' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'shortnamesUpdate');
                $rc = ciniki_musicfestivals_shortnamesUpdate($ciniki, $tnid, [
                    'type' => 'division',
                    'format' => $args[$field],
                    'festival_id' => $festival_id,
                    ]);
            }
            elseif( $field == 'scheduling-timeslot-shortnames' && $args[$field] != 'manual' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'shortnamesUpdate');
                $rc = ciniki_musicfestivals_shortnamesUpdate($ciniki, $tnid, [
                    'type' => 'timeslot',
                    'format' => $args[$field],
                    'festival_id' => $festival_id,
                    ]);
            }
        }
    }

    return array('stat'=>'ok');
}
?>
