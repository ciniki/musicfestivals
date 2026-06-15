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
function ciniki_musicfestivals_recommendationEntryStatusColour(&$ciniki, $tnid, $recommendation) {


    //
    // This code mirrors 'recommendationEntryStatusColour' in ui/main
    //
    $st = '';
    $strike = 'no';
    if( $recommendation['position'] > 600 && $recommendation['position'] < 799 ) {
        $st = ' statuslinethrough';
        $strike = 'yes';
    }
    if( $recommendation['position'] >= 101 && $recommendation['position'] <= 104 ) {
        if( $recommendation['status'] > 80 ) {
            return array('stat'=>'ok', 'colour'=>'statusgreyfade statuslinethrough', 'fade'=>[238,238,238], 'strike'=>'yes');
        } 
        return array('stat'=>'ok', 'colour'=>'statusgreyfade', 'fade'=>[238,238,238]);
    }
    switch($recommendation['status']) {
        case 10: return array('stat'=>'ok', 'colour'=>'', 'fill'=>[255,255,255], 'strike'=>$strike);
        case 20: return array('stat'=>'ok', 'colour'=>'statusblue' . $st, 'fill'=>[221,241,255], 'strike'=>$strike);
        case 30: return array('stat'=>'ok', 'colour'=>'statusorange' . $st, 'fill'=>[255,239,221], 'strike'=>$strike);
        case 35: return array('stat'=>'ok', 'colour'=>'statusorangefade' . $st, 'fade'=>[255,239,221], 'strike'=>$strike);
        case 40: return array('stat'=>'ok', 'colour'=>'statusteal' . $st, 'fill'=>[206,255,248], 'strike'=>$strike);
        case 45: return array('stat'=>'ok', 'colour'=>'statustealfade' . $st, 'fade'=>[206,255,248], 'strike'=>$strike);
        case 50: 
            if( $recommendation['reg_status'] < 50 ) {
                return array('stat'=>'ok', 'colour'=>'statusgreenfade' . $st, 'fade'=>[221,255,221], 'strike'=>$strike);
            } 
            return array('stat'=>'ok', 'colour'=>'statusgreen' . $st, 'fill'=>[221,255,221], 'strike'=>$strike);
        case 70: return array('stat'=>'ok', 'colour'=>'statusred' . $st, 'fill'=>[255,221,221], 'strike'=>$strike);
        case 80: return array('stat'=>'ok', 'colour'=>'statuspurple' . $st, 'fill'=>[240,221,255], 'strike'=>$strike);
        case 85:
        case 90: return array('stat'=>'ok', 'colour'=>'statusgrey statuslinethrough', 'fill'=>[238,238,238], 'strike'=>'yes');
        case 900: return array('stat'=>'ok', 'colour'=>'statusgreyfade', 'fade'=>[238,238,238]);
        case 950: return array('stat'=>'ok', 'colour'=>'statusgreyfade', 'fade'=>[238,238,238]);
    }

    return array('stat'=>'ok', 'colour'=>'' . $st);
}
?>
