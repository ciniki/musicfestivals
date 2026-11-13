<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_volunteersDefaultEmails(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.provincialsDefaultEmails');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_details = isset($rc['details']) ? $rc['details'] : array();

    $emails = [
        'volunteers-email-approved-subject'=>'Your Volunteer Application is Approved',
        'volunteers-email-approved-message'=>"<p>Hello {_firstname_},</p>\n"
            . "<p>Your application has been approved...welcome to the volunteer team!</p>\n"
            . "<p>If you have any questions or require assistance contact us.</p>\n"
            . "<p>We looking forward to seeing you at the festival!</p>"
            . "",
        'volunteers-email-shift-assigned-subject'=>'Your Volunteer Shift is Approved',
        'volunteers-email-shift-assigned-message'=>"<p>Hello {_firstname_},</p>\n"
            . "<p>Thank you for signing up for your volunteer shift!&nbsp; &nbsp;Please mark your calendar - you have been approved for the following shift:</p>\n"
            . "<p><strong>Date:</strong> {_shiftdate_}</p>\n"
            . "<p><strong>Time:</strong> {_starttime_} to&nbsp;{_endtime_}</p>\n"
            . "<p><strong>Volunteer Role:</strong> {_role_}</p>\n"
            . "<p><strong>Location:</strong> {_location_}</p>\n"
            . "<p>If you are unable to attend your shift please notify us at your earliest convenience.</p>\n"
            . "<p>Thank you for being a part of the {$tenant_details['name']} Team!</p>"
            . "",
        'volunteers-email-shift-changed-subject'=>'Your Volunteer Shift Has Been Updated',
        'volunteers-email-shift-changed-message'=>"<p>Hello {_firstname_},</p>\n"
            . "<p>A scheduling change has been made that affects your volunteer shift.&nbsp; Please review the following details:</p>\n"
            . "<p><strong>Date:</strong> {_shiftdate_}</p>\n"
            . "<p><strong>Time:</strong> {_starttime_} to {_endtime_}</p>\n"
            . "<p><strong>Volunteer Role:</strong> {_role_}</p>\n"
            . "<p><strong>Location:</strong> {_location_}</p>\n"
            . "<p>If you are unable to attend your shift please notify us at your earliest convenience.</p>\n"
            . "<p>Thank you for being a part of the {$tenant_details['name']} Team!</p>"
            . "",
        'volunteers-email-shift-cancelled-subject'=>'Your Volunteer Shift Has Been Cancelled',
        'volunteers-email-shift-cancelled-message'=>"<p>Hello {_firstname_},</p>\n"
            . "<p>Your volunteer shift below has been cancelled.&nbsp; We apologize for any inconvenience.&nbsp; You are welcome to sign up for another available shift on this date by logging into your festival account.</p>\n"
            . "<p><strong>Cancelled Shift:</strong></p>\n"
            . "<p><strong>Date:</strong> {_shiftdate_}</p>\n"
            . "<p><strong>Time:</strong> {_starttime_} to {_endtime_}</p>\n"
            . "<p><strong>Volunteer Role:</strong> {_role_}</p>\n"
            . "<p><strong>Location:</strong> {_location_}</p>\n"
            . "<p>Thank you for being a part of the {$tenant_details['name']} Team!</p>"
            . "",
        'volunteers-email-reminder-7day-subject'=>'Reminder: Upcoming Volunteer Shift',
        'volunteers-email-reminder-7day-message'=>"<p>Hello {_firstname_},</p>\n"
            . "<p>Your volunteer shift starts next week! Please confirm the details below:</p>\n"
            . "<p><strong>Date:</strong> {_shiftdate_}</p>\n"
            . "<p><strong>Time:</strong> {_starttime_} to&nbsp;{_endtime_}</p>\n"
            . "<p><strong>Volunteer Role:</strong> {_role_}</p>\n"
            . "<p><strong>Location:</strong> {_location_}</p>\n"
            . "<p>If you are unable to attend your shift please notify us immediately so that we may find someone else to fill in.</p>\n"
            . "<p>Thank you for being a part of the {$tenant_details['name']} Team!</p>\n"
            . "",
        'volunteers-email-reminder-24hour-subject'=>'Reminder: Upcoming Volunteer Shift - Tomorrow!',
        'volunteers-email-reminder-24hour-message'=>"<p>Hello {_firstname_},</p>\n"
            . "<p>You are sign up to volunteer tomorrow! Please confirm the details below:</p>\n"
            . "<p><strong>Date:</strong>&nbsp;{_shiftdate_}</p>\n"
            . "<p><strong>Time: </strong>{_starttime_} to&nbsp;{_endtime_}</p>\n"
            . "<p><strong>Volunteer Role: </strong>{_role_}</p>\n"
            . "<p><strong>Location: </strong>{_location_}</p>\n"
            . "<p>If you are unable to attend your shift please notify us immediately so that we may find someone else to fill in.</p>\n"
            . "<p>Thank you for being a part of the {$tenant_details['name']} Team!</p>"
            . "",
        ];
    

    return array('stat'=>'ok', 'emails'=>$emails);
}
?>
