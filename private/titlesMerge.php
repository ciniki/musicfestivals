<?php
//
// Description
// -----------
// This function will merge the title, composer and movements into 1 line
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_titlesMerge(&$ciniki, $tnid, $registration, $args=null) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    $newline = isset($args['newline']) ? $args['newline'] : "\n";
    $titles = '';
    $perf_time = 0;
    $num = 1;
    for($i = 1; $i <= 8; $i++) {
        $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
        if( isset($rc['title']) && $rc['title'] != '' ) {
            $title = $rc['title'];
            if( isset($registration["perf_time{$i}"]) && is_numeric($registration["perf_time{$i}"]) ) {
                $perf_time += $registration["perf_time{$i}"];
            }
            
            if( isset($args['times']) && $args['times'] == 'beginning' ) {
                $title = '[' . intval($registration["perf_time{$i}"]/60) 
                    . ':' . str_pad(($registration["perf_time{$i}"]%60), 2, '0', STR_PAD_LEFT) 
                    . '] ' . $title;
            } elseif( isset($args['times']) && $args['times'] == 'end' ) {
                $title = $title . ' [' . intval($registration["perf_time{$i}"]/60) 
                    . ':' . str_pad(($registration["perf_time{$i}"]%60), 2, '0', STR_PAD_LEFT) . ']';
            }
            if( isset($args['prefix']) && $args['prefix'] != '' ) {
                $title = $args['prefix'] . $title;
            }
            if( isset($args['numbers']) && $args['numbers'] == 'yes' ) {
                $title = '#' . $num . '. ' . $title;
            }
            $titles .= ($titles != '' ? $newline : '') . $title;
            $num++;
        }
    }

    if( isset($args['times']) && $args['times'] == 'startsum' ) {
        $titles = '[' . intval($perf_time/60) . ':' . str_pad(($perf_time%60), 2, '0', STR_PAD_LEFT) . '] ' . $titles;
    }

    return array('stat'=>'ok', 'titles'=>$titles);
}
?>
