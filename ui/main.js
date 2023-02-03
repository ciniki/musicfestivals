//
// This is the main app for the musicfestivals module
//
function ciniki_musicfestivals_main() {
    //
    // The panel to list the festival
    //
    this.menu = new M.panel('Music Festivals', 'ciniki_musicfestivals_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search festival',
//            'noData':'No festival found',
//            },
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'festivals',
            'visible':function() {return M.modFlagSet('ciniki.musicfestivals', 0x40); },
            'tabs':{
                'festivals':{'label':'Festivals', 'fn':'M.ciniki_musicfestivals_main.menu.switchTab("festivals");'},
                'trophies':{'label':'Trophies', 'fn':'M.ciniki_musicfestivals_main.menu.switchTab("trophies");'},
            }},
        'festivals':{'label':'Festival', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected == 'festivals' ? 'yes' : 'no';},
            'noData':'No Festivals',
            'addTxt':'Add Festival',
            'addFn':'M.ciniki_musicfestivals_main.edit.open(\'M.ciniki_musicfestivals_main.menu.open();\',0,null);'
            },
        'trophies':{'label':'Trophies', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected == 'trophies' ? 'yes' : 'no';},
            'noData':'No Trophies',
//            'headerValues':['Category', 'Name'],
            'addTxt':'Add Trophy',
            'addFn':'M.ciniki_musicfestivals_main.trophy.open(\'M.ciniki_musicfestivals_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.festivalSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_musicfestivals_main.menu.liveSearchShow('search',null,M.gE(M.ciniki_musicfestivals_main.menu.panelUID + '_' + s), rsp.festivals);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_musicfestivals_main.festival.open(\'M.ciniki_musicfestivals_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.switchTab = function(tab) {
        if( tab != null ) { this.sections._tabs.selected = tab; }
        this.open();
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'festivals' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
            }
        }
        if( s == 'trophies' ) {
            switch(j) {
                case 0: return d.category;
                case 1: return d.name;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'festivals' ) {
            return 'M.ciniki_musicfestivals_main.festival.open(\'M.ciniki_musicfestivals_main.menu.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.nplist);';
        }
        if( s == 'trophies' ) {
            return 'M.ciniki_musicfestivals_main.trophy.open(\'M.ciniki_musicfestivals_main.menu.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.menu.nplist);';
        }
    }
    this.menu.open = function(cb) {
        if( this.sections._tabs.selected == 'trophies' ) {
            M.api.getJSONCb('ciniki.musicfestivals.trophyList', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.menu;
                p.data = rsp;
                p.nplist = (rsp.nplist != null ? rsp.nplist : null);
                p.refresh();
                p.show(cb);
            });
        } else {
            M.api.getJSONCb('ciniki.musicfestivals.festivalList', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.menu;
                p.data = rsp;
                p.nplist = (rsp.nplist != null ? rsp.nplist : null);
                p.refresh();
                p.show(cb);
            });
        }
    }
    this.menu.addClose('Back');

    //
    // The panel to display Festival
    //
    this.festival = new M.panel('Festival', 'ciniki_musicfestivals_main', 'festival', 'mc', 'large narrowaside', 'sectioned', 'ciniki.musicfestivals.main.festival');
    this.festival.data = null;
    this.festival.festival_id = 0;
    this.festival.schedulesection_id = 0;
    this.festival.scheduledivision_id = 0;
    this.festival.list_id = 0;
    this.festival.listsection_id = 0;
    this.festival.nplists = {};
    this.festival.nplist = [];
    this.festival.sections = {
        '_tabs':{'label':'', 'type':'menutabs', 'selected':'sections', 'tabs':{
            'sections':{'label':'Syllabus', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'sections\');'},
            'registrations':{'label':'Registrations', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'registrations\');'},
            'schedule':{'label':'Schedule', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'schedule\');'},
            'videos':{'label':'Videos', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'videos\');',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no'},
                },
            'comments':{'label':'Comments', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'comments\');',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no'},
                },
            'competitors':{'label':'Competitors', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'competitors\');'},
//            'adjudicators':{'label':'Adjudicators', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'adjudicators\');'},
//            'files':{'label':'Files', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'files\');'},
            'photos':{'label':'Photos', 
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x04); },
                'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'photos\');',
                },
//            'sponsors':{'label':'Sponsors', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'sponsors\');',
//                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x10); },
//                },
            'lists':{'label':'Lists', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'lists\');',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x20); },
                },
//            'sponsors-old':{'label':'Sponsors', 
//                'visible':function() { 
//                    return (M.curTenant.modules['ciniki.sponsors'] != null && (M.curTenant.modules['ciniki.sponsors'].flags&0x02) == 0x02) ? 'yes':'no'; 
//                    },
//                'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'sponsors-old\');',
//                },
            'more':{'label':'More...', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'more\');'},
            }},
        'details':{'label':'Details', 'aside':'yes', 'list':{
            'name':{'label':'Name'},
            'start_date':{'label':'Start'},
            'end_date':{'label':'End'},
            'num_registrations':{'label':'# Reg'},
            }},
//        '_more':{'label':'', 'aside':'yes', 
//            'list':{
//                'adjudicators':{'label':'Adjudicators', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'adjudicators\');'},
//            }},
        '_stabs':{'label':'', 'type':'paneltabs', 'selected':'sections', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sections' ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'sections\');'},
                'categories':{'label':'Categories', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'categories\');'},
                'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'classes\');'},
            }},
        '_moretabs':{'label':'', 'type':'paneltabs', 'selected':'adjudicators', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'more' ? 'yes' : 'no'; },
            'tabs':{
                'adjudicators':{'label':'Adjudicators', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'adjudicators\');'},
                'certificates':{'label':'Certificates', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'certificates\');'},
                'files':{'label':'Files', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'files\');'},
                'sponsors':{'label':'Sponsors', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'sponsors\');',
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x10); },
                    },
                'sponsors-old':{'label':'Sponsors', 
                    'visible':function() { 
                        return (M.curTenant.modules['ciniki.sponsors'] != null && (M.curTenant.modules['ciniki.sponsors'].flags&0x02) == 0x02) ? 'yes':'no'; 
                        },
                    'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'sponsors-old\');',
                    },
            }},
        'program_options':{'label':'Download Program', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' ? 'yes' : 'no'; },
            'fields':{
                'ipv':{'label':'Type', 'type':'toggle', 'default':'all', 'toggles':{'all':'All', 'inperson':'In Person', 'virtual':'Virtual'}},
            }},
        'program_buttons':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' ? 'yes' : 'no'; },
            'buttons':{
                'pdf':{'label':'Download Program PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadProgramPDF();'},
            }},
        'download_buttons':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'sections' ? 'yes' : 'no'; },
            'buttons':{
                'download':{'label':'Download Syllabus (PDF)', 
                    'fn':'M.ciniki_musicfestivals_main.festival.syllabusDownload();',
                    },
            }},
        'sections':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'sections' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'number'],
            'headerValues':['Section', 'Registrations'],
            'addTxt':'Add Section',
            'addFn':'M.ciniki_musicfestivals_main.section.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'si_buttons':{'label':'', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'sections' && M.ciniki_musicfestivals_main.festival.data.sections.length == 0 ? 'yes' : 'no'; },
            'buttons':{
                'copy':{'label':'Copy previous syllabus', 
                    'fn':'M.ciniki_musicfestivals_main.festival.syllabusCopy("previous");',
                    },
            }},
        'categories':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'categories' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'number'],
            'headerValues':['Section', 'Category', 'Registrations'],
            'addTxt':'Add Category',
            'addFn':'M.ciniki_musicfestivals_main.category.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'classes':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'classes' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'number', 'number'],
            'headerValues':['Section', 'Category', 'Class', 'Fee', 'Registrations'],
            'addTxt':'Add Class',
            'addFn':'M.ciniki_musicfestivals_main.class.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'registration_tabs':{'label':'', 'aside':'yes', 'type':'paneltabs', 'selected':'sections',
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.sections._tabs.selected) >= 0 ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("sections");'},
                'teachers':{'label':'Teachers', 'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("teachers");'},
            }}, 
        'registration_sections':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.sections._tabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'sections' ? 'yes' : 'no'; },
            },
        'registration_teachers':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.sections._tabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'teachers' ? 'yes' : 'no'; },
            },
        'registration_buttons':{'label':'', 'aside':'yes', 
            'visible':function() {return M.ciniki_musicfestivals_main.festival.sections._tabs.selected=='registrations'?'yes':'no';},
            'buttons':{
                'excel':{'label':'Export to Excel', 
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadExcel(M.ciniki_musicfestivals_main.festival.festival_id);',
                    'visible':function() {return M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections'?'yes':'no';},
                    },
                'pdf':{'label':'Registrations PDF ', 
                    'visible':function() {return M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections'?'yes':'no';},
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadPDF(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
            }},
        'registration_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5,
            'visible':function() {return M.ciniki_musicfestivals_main.festival.sections._tabs.selected=='registrations'?'yes':'no';},
            'hint':'Search',
            'noData':'No registrations found',
            'headerValues':['Class', 'Registrant', 'Teacher', 'Fee', 'Status'],
            'cellClasses':['', '', '', '', ''],
            },
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'registrations' ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant', 'Teacher', 'Fee', 'Status', 'Virtual'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'altnumber', 'altnumber', 'text'],
            'cellClasses':['', 'multiline', '', '', '', 'alignright'],
//            'addTxt':'Add Registration',
//            'addFn':'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'registrations_emailbutton':{'label':'', 
            'visible':function() {return M.ciniki_musicfestivals_main.festival.sections._tabs.selected=='registrations' && M.ciniki_musicfestivals_main.festival.teacher_customer_id > 0 ?'yes':'no';},
            'buttons':{
                'email':{'label':'Email List', 'fn':'M.ciniki_musicfestivals_main.festival.emailTeacherRegistrations();'},
                'comments':{'label':'Comments PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadTeacherComments();'},
                'registrations':{'label':'Registrations PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadTeacherRegistrations();'},
            }},
        'schedule_sections':{'label':'Schedules', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' ? 'yes' : 'no'; },
            'visible':function() { return ['schedule', 'comments', 'photos'].indexOf(M.ciniki_musicfestivals_main.festival.sections._tabs.selected) >= 0 ? 'yes' : 'no'; },
            'cellClasses':['', 'multiline alignright'],
            'addTxt':'Unscheduled',
            'addFn':'M.ciniki_musicfestivals_main.festival.openScheduleSection(\'unscheduled\',"Unscheduled");',
            'changeTxt':'Add Schedule',
            'changeFn':'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            'editFn':function(s, i, d) {
                if( M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'comments' ) {
                    return '';
                }
                if( M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'photos' ) {
                    return '';
                }
                return 'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
                },
            },
        'schedule_divisions':{'label':'Divisions', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return ['schedule', 'comments', 'photos'].indexOf(M.ciniki_musicfestivals_main.festival.sections._tabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.schedulesection_id>0? 'yes' : 'no'; },
            'cellClasses':['multiline', 'multiline alignright'],
            'addTxt':'Add Division',
            'addFn':'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            'editFn':function(s, i, d) {
                if( M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'comments' ) {
                    return '';
                }
                if( M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'photos' ) {
                    return '';
                }
                return 'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
                },
            },
        'schedule_download':{'label':'Schedule PDF', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_musicfestivals_main.festival.schedulesection_id>0? 'yes' : 'no'; },
            'fields':{
                'names':{'label':'Full Names', 'type':'toggle', 'default':'public', 'toggles':{'public':'No', 'private':'yes'}},
            }},
        'schedule_buttons':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_musicfestivals_main.festival.schedulesection_id>0? 'yes' : 'no'; },
            'buttons':{
                'pdf':{'label':'Download PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadSchedulePDF();'},
                'certs':{'label':'Certificates PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCertificatesPDF();'},
                'comments':{'label':'Adjudicators Comments PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCommentsPDF();'},
            }},
        'schedule_timeslots':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':2, 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_musicfestivals_main.festival.schedulesection_id>0 && M.ciniki_musicfestivals_main.festival.scheduledivision_id>0 ? 'yes' : 'no'; },
            'cellClasses':['label', 'multiline', 'fabuttons'],
            'addTxt':'Add Time Slot',
            'addFn':'M.ciniki_musicfestivals_main.scheduletimeslot.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'timeslot_photos':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'photos' && M.ciniki_musicfestivals_main.festival.schedulesection_id>0 && M.ciniki_musicfestivals_main.festival.scheduledivision_id>0 ? 'yes' : 'no'; },
            'cellClasses':['multiline', 'thumbnails', 'alignright fabuttons'],
            },
        'timeslot_comments':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':5, 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'comments' && M.ciniki_musicfestivals_main.festival.schedulesection_id>0 && M.ciniki_musicfestivals_main.festival.scheduledivision_id>0 ? 'yes' : 'no'; },
            'headerValues':['Time', 'Name', '', '', ''],
            'headerClasses':['', '', 'aligncenter', 'aligncenter', 'aligncenter'],
            'cellClasses':['', '', 'aligncenter', 'aligncenter', 'aligncenter'],
            },
        'unscheduled_registrations':{'label':'Unscheduled', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_musicfestivals_main.festival.schedulesection_id == 'unscheduled' ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant', 'Status'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text'],
            'cellClasses':['', 'multiline', ''],
            },
        'video_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5,
            'visible':function() {return M.ciniki_musicfestivals_main.festival.sections._tabs.selected=='videos'?'yes':'no';},
            'hint':'Search',
            'noData':'No registrations found',
            'headerValues':['Class', 'Registrant', 'Video Link', 'PDF', 'Status'],
            'cellClasses':['', '', '', '', ''],
            },
        'videos':{'label':'Registrations', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'videos' ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant', 'Video Link', 'PDF', 'Status', ''],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'altnumber', ''],
            'cellClasses':['', 'multiline', '', '', '', 'alignright'],
//            'addTxt':'Add Registration',
//            'addFn':'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'competitors':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'competitors' ? 'yes' : 'no'; },
            'headerValues':['Name', 'Classes', 'Waiver'],
            },
        'lists':{'label':'Lists', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'lists' ? 'yes' : 'no'; },
            'addTxt':'Add List',
            'addFn':'M.ciniki_musicfestivals_main.list.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            'editFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.list.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
                },
            },
        'listsections':{'label':'Sections', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'lists' && M.ciniki_musicfestivals_main.festival.list_id > 0 ? 'yes' : 'no'; },
            'addTxt':'Add Section',
            'addFn':'M.ciniki_musicfestivals_main.listsection.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.list_id,null);',
            'editFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.listsection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.list_id,null);';
                },
            },
        'listentries':{'label':'Sections', 'type':'simplegrid', 'num_cols':4, 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'lists' && M.ciniki_musicfestivals_main.festival.listsection_id > 0 ? 'yes' : 'no'; },
            'headerValues':['Award', 'Amount', 'Donor', 'Winner'],
            'addTxt':'Add Entry',
            'addFn':'M.ciniki_musicfestivals_main.listentry.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.listsection_id,null);',
            'seqDrop':function(e,from,to) {
                M.api.getJSONCb('ciniki.musicfestivals.festivalGet', {'tnid':M.curTenantID, 
                    'action':'listentrysequenceupdate',
                    'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                    'lists':'yes',
                    'list_id':M.ciniki_musicfestivals_main.festival.list_id,
                    'listsection_id':M.ciniki_musicfestivals_main.festival.listsection_id,
                    'entry_id':M.ciniki_musicfestivals_main.festival.data.listentries[from].id, 
                    'sequence':M.ciniki_musicfestivals_main.festival.data.listentries[to].sequence, 
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_musicfestivals_main.festival;
                        p.data.listentries = rsp.festival.listentries;
                        p.refreshSection("listentries");
                    });
                },
            },
        'sponsors':{'label':'', 'type':'simplegrid', 'num_cols':2,
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sponsors' ? 'yes' : 'no'; },
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'sponsors'); },
            'headerValues':['Name', 'Level'],
            'addTxt':'Add Sponsor',
            'addFn':'M.ciniki_musicfestivals_main.sponsor.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id);',
        },
        'sponsors-old':{'label':'', 'type':'simplegrid', 'num_cols':1,
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sponsors-old' ? 'yes' : 'no'; },
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'sponsors-old'); },
            'addTxt':'Manage Sponsors',
            'addFn':'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_musicfestivals_main.festival.open();\',\'mc\',{\'object\':\'ciniki.musicfestivals.festival\',\'object_id\':M.ciniki_musicfestivals_main.festival.festival_id});',
        },
        'adjudicators':{'label':'', 'type':'simplegrid', 'num_cols':1,
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'adjudicators' ? 'yes' : 'no'; },
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'adjudicators'); },
            'addTxt':'Add Adjudicator',
            'addFn':'M.ciniki_musicfestivals_main.adjudicator.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'files':{'label':'', 'type':'simplegrid', 'num_cols':2,
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'files' ? 'yes' : 'no'; },
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'files'); },
            'addTxt':'Add File',
            'addFn':'M.ciniki_musicfestivals_main.addfile.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id);',
            },
        'certificates':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'certificates'); },
            'headerValues':['Name', 'Section', 'Min Score'],
            'addTxt':'Add Certificate',
            'addFn':'M.ciniki_musicfestivals_main.certificate.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id);',
            },
    }
    this.festival.isSelected = function(t, m) {
        if( this.sections._tabs.selected == t ) {
            if( t == 'more' ) {
                return this.sections._moretabs.selected == m ? 'yes' : 'no';
            }
            return 'yes';
        }
        return 'no';
    }
    this.festival.sectionData = function(s) {
        if( s == 'videos' ) {
            return this.data.registrations;
        }
        return M.panel.prototype.sectionData.call(this, s);
    }
    this.festival.downloadProgramPDF = function() {
        var args = {
            'tnid':M.curTenantID, 
            'festival_id':this.festival_id, 
            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.programPDF',args);
    }
    this.festival.downloadSchedulePDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            'names':this.formValue('names'),
            };
        M.api.openPDF('ciniki.musicfestivals.schedulePDF',args);
    }
    this.festival.downloadCertificatesPDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            };
        M.api.openFile('ciniki.musicfestivals.certificatesPDF',args);
    }
    this.festival.downloadCommentsPDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            };
        M.api.openPDF('ciniki.musicfestivals.commentsPDF',args);
    }
    this.festival.downloadTeacherComments = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'teacher_customer_id':this.teacher_customer_id,
            };
        M.api.openPDF('ciniki.musicfestivals.commentsPDF',args);
    }
    this.festival.downloadTeacherRegistrations = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'teacher_customer_id':this.teacher_customer_id,
            };
        M.api.openPDF('ciniki.musicfestivals.teacherRegistrationsPDF',args);
    }
    this.festival.listLabel = function(s, i, d) { 
        if( s == 'details' ) {
            return d.label; 
        }
        return '';
    }
    this.festival.listValue = function(s, i, d) { 
        if( s == 'details' ) {
            return this.data[i]; 
        }
        if( s == '_more' ) {
            return d.label;
        }
    }
    this.festival.fieldValue = function(s, i, d) { 
        if( this.data[i] == null ) { return ''; }
        return this.data[i]; 
    }
    this.festival.liveSearchCb = function(s, i, v) {
        if( (s == 'registration_search' || s == 'video_search') && v != '' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.registrationSearch', {'tnid':M.curTenantID, 'start_needle':v, 'festival_id':this.festival_id, 'limit':'50'}, function(rsp) {
                    M.ciniki_musicfestivals_main.festival.liveSearchShow(s,null,M.gE(M.ciniki_musicfestivals_main.festival.panelUID + '_' + s), rsp.registrations);
                });
        }
    }
    this.festival.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'registration_search' ) { 
            switch(j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.teacher_name;
                case 3: return '$' + d.fee;
                case 4: return d.status_text;
            }
        }
        if( s == 'video_search' ) { 
            switch(j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return M.hyperlink(d.video1_url);
                case 3: return d.music_orgfilename;
                case 4: return d.status_text;
            }
        }
    }
    this.festival.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'registration_search' || s == 'video_search' ) { 
            return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.registrations,\'festival\');';
        }
    }
    this.festival.cellValue = function(s, i, j, d) {
        if( s == 'sections' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'categories' ) {
            switch(j) {
                case 0: return d.section_name;
                case 1: return d.name;
                case 2: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'classes' ) {
            switch(j) {
                case 0: return d.section_name;
                case 1: return d.category_name;
                case 2: return d.code + ' - ' + d.name;
                case 3: return d.earlybird_fee + '/' + d.fee;
                case 4: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'unscheduled_registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + d.title1 + '</span>';
                case 2: return d.status_text;
            }
        }
        if( s == 'registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + d.title1 + '</span>';
                case 2: return d.teacher_name;
                case 3: return '$' + d.fee;
                case 4: return d.status_text;
                case 5: return (d.participation == 1 ? 'Virtual' : 'In Person');
            }
        }
        if( s == 'registration_sections' ) {
            return d.name + (d.num_registrations > 0 ? ' <span class="count">' + d.num_registrations + '</span>' : '');
        }
        if( s == 'registration_teachers' ) {
            return d.display_name + (d.num_registrations > 0 ? ' <span class="count">' + d.num_registrations + '</span>' : '');
        }
        if( s == 'schedule_sections' ) {
            switch(j) {
                case 0: return d.name;
//                case 1: return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);">Edit</span>';
            }
        }
        if( s == 'schedule_divisions' && M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'comments' ) {
            return '<span class="maintext">' + d.name + ' <span class="subtext">' + d.division_date_text + '</span>';
        }
        if( s == 'schedule_divisions' ) {
            return '<span class="maintext">' + d.name + ' <span class="subdue">' + d.division_date_text + '</span><span class="subtext">' + d.address + '</span>';
        }
        if( s == 'schedule_timeslots' ) {
            switch(j) {
                case 0: return d.slot_time_text;
                case 1: return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.description.replace(/\n/g, '<br/>') + '</span>';
            }
        }
        if( s == 'timeslot_photos' ) {
            if( j == 1 && d.images != null && d.images.length > 0 ) {
                var thumbs = '';
                for(var k in d.images) {
                    thumbs += '<img class="clickable" onclick="M.ciniki_musicfestivals_main.timeslotimage.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.images[k].timeslot_image_id + '\');" width="50px" height="50px" src=\'' + d.images[k].image + '\' />';
                }
                return thumbs;
            }
            switch(j) {
                case 0: return M.multiline(d.slot_time_text, d.name);
                case 1: return '';
                case 2: return M.faBtn('&#xf030;', 'Photos', 'M.ciniki_musicfestivals_main.festival.timeslotImageAdd(' + d.id + ',' + i + ');');
            }
        }
        if( s == 'timeslot_comments' ) {
            switch(j) {
                case 0: return d.time;
                case 1: return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.description.replace(/\n/g, '<br/>') + '</span>';
                case 2: return d.status1;
                case 3: return d.status2;
                case 4: return d.status3;
            }
        }
        if( s == 'videos' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + d.title + '</span>';
                case 2: return M.hyperlink(d.video1_url);
                case 3: return d.music_orgfilename;
                case 4: return d.status_text;
            }
        }
        if( s == 'competitors' ) {
            switch(j) {
                case 0: return d.name + M.subdue(' (',d.pronoun,')');
                case 1: return d.classcodes;
                case 2: return d.waiver_signed;
            }
        }
        if( s == 'adjudicators' ) {
            return d.name;
        }
        if( s == 'certificates' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.section_name;
                case 2: return d.min_score;
            }
        }
        if( s == 'files' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return (d.webflags&0x01) == 0x01 ? 'Visible' : 'Hidden';
            }
        }
        if( s == 'lists' ) {
            switch(j) { 
                case 0: return d.name;
            }
        }
        if( s == 'listsections' ) {
            switch(j) { 
                case 0: return d.name;
            }
        }
        if( s == 'listentries' ) {
            switch(j) { 
                case 0: return d.award;
                case 1: return d.amount;
                case 2: return d.donor;
                case 3: return d.winner;
            }
        }
        if( s == 'sponsors' ) {
            switch(j) { 
                case 0: return d.name;
                case 1: return d.level;
            }
        }
        if( s == 'sponsors-old' && j == 0 ) {
            return '<span class="maintext">' + d.sponsor.title + '</span>';
        }
    }
    this.festival.cellSortValue = function(s, i , j, d) {
        if( s == 'registrations' ) {
            switch(j) {
                case 3: return d.fee;
                case 4: return d.status;
            }
        }
        if( s == 'videos' ) {
            switch(j) {
                case 4: return d.status;
            }
        }
        return '';
    }
    this.festival.rowFn = function(s, i, d) {
        switch(s) {
            case 'sections': return 'M.ciniki_musicfestivals_main.section.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.sections);';
            case 'categories': return 'M.ciniki_musicfestivals_main.category.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',\'' + d.section_id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.categories);';
            case 'classes': return 'M.ciniki_musicfestivals_main.class.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.classes);';
            case 'unscheduled_registrations': 
            case 'registrations': 
            case 'videos':
                return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.registrations,\'festival\');';
            case 'registration_sections': return 'M.ciniki_musicfestivals_main.festival.openSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'registration_teachers': return 'M.ciniki_musicfestivals_main.festival.openTeacher(\'' + d.id + '\',"' + M.eU(d.display_name) + '");';
            case 'schedule_sections': return 'M.ciniki_musicfestivals_main.festival.openScheduleSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_divisions': return 'M.ciniki_musicfestivals_main.festival.openScheduleDivision(\'' + d.id + '\',"' + M.eU(d.name) + '");';
//            case 'schedule_sections': return 'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
//            case 'schedule_divisions': return 'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'schedule_timeslots': return 'M.ciniki_musicfestivals_main.scheduletimeslot.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'timeslot_comments': return 'M.ciniki_musicfestivals_main.timeslotcomments.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'timeslot_photos': return null;
            case 'competitors': return 'M.ciniki_musicfestivals_main.competitor.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id);';
            case 'adjudicators': return 'M.ciniki_musicfestivals_main.adjudicator.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.adjudicators);';
            case 'certificates': return 'M.ciniki_musicfestivals_main.certificate.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'files': return 'M.ciniki_musicfestivals_main.editfile.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'lists': return 'M.ciniki_musicfestivals_main.festival.openList(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'listsections': return 'M.ciniki_musicfestivals_main.festival.openListSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'listentries': return 'M.ciniki_musicfestivals_main.listentry.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'sponsors': return 'M.ciniki_musicfestivals_main.sponsor.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'sponsors-old': return 'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_musicfestivals_main.festival.open();\',\'mc\',{\'ref_id\':\'' + d.sponsor.ref_id + '\'});';
        }
        return '';
    }
    this.festival.rowClass = function(s, i, d) {
        if( s == 'schedule_sections' && this.schedulesection_id == d.id ) {
            return 'highlight';
        }
        if( s == 'schedule_divisions' && this.scheduledivision_id == d.id ) {
            return 'highlight';
        }
        if( s == 'registration_sections' && this.section_id == d.id ) {
            return 'highlight';
        }
        if( s == 'registration_teachers' && this.teacher_customer_id == d.id ) {
            return 'highlight';
        }
        if( s == 'lists' && this.list_id == d.id ) {
            return 'highlight';
        }
        if( s == 'listsections' && this.listsection_id == d.id ) {
            return 'highlight';
        }
    }
    this.festival.switchTab = function(tab, stab) {
        if( tab != null ) { this.sections._tabs.selected = tab; }
        if( stab != null ) { this.sections._stabs.selected = stab; }
        this.open();
    }
    this.festival.switchMTab = function(t) {
        this.sections._moretabs.selected = t;
        this.open();
    }
    this.festival.switchRegTab = function(t) {
        this.sections.registration_tabs.selected = t;
        this.open();
    }
    this.festival.emailTeacherRegistrations = function() {
        M.ciniki_musicfestivals_main.emailregistrations.open('M.ciniki_musicfestivals_main.festival.show();');
    }
    this.festival.openSection = function(id,n) {
        this.section_id = id;
        this.teacher_customer_id = 0;
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openTeacher = function(id,n) {
        this.teacher_customer_id = id;
        this.section_id = 0;
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openScheduleSection = function(i, n) {
        this.schedulesection_id = i;
        this.sections.schedule_divisions.label = M.dU(n);
        this.scheduledivision_id = 0;
        this.open();
    }
    this.festival.openScheduleDivision = function(i, n) {
        this.scheduledivision_id = i;
        this.sections.schedule_timeslots.label = M.dU(n);
        this.open();
    }
    this.festival.openList = function(i, n) {
        this.list_id = i;
        this.sections.listsections.label = M.dU(n);
        this.scheduledivision_id = 0;
        this.open();
    }
    this.festival.openListSection = function(i, n) {
        this.listsection_id = i;
        this.sections.listentries.label = M.dU(n);
        this.open();
    }
    this.festival.downloadExcel = function(fid) {
        M.api.openFile('ciniki.musicfestivals.registrationsExcel', {'tnid':M.curTenantID, 'festival_id':fid});
    }
    this.festival.downloadPDF = function(fid) {
        M.api.openFile('ciniki.musicfestivals.registrationsPDF', {'tnid':M.curTenantID, 'festival_id':fid});
    }
    this.festival.open = function(cb, fid, list) {
        if( fid != null ) { this.festival_id = fid; }
        var args = {'tnid':M.curTenantID, 'festival_id':this.festival_id};
        this.size = 'xlarge narrowaside';
        if( this.sections._tabs.selected == 'sections' ) {
            args['sections'] = 'yes';
            args['categories'] = 'yes';
            args['classes'] = 'yes';
        } else if( this.sections._tabs.selected == 'registrations' || this.sections._tabs.selected == 'videos' ) {
            this.size = 'xlarge narrowaside';
            args['sections'] = 'yes';
            args['registrations'] = 'yes';
        } else if( this.sections._tabs.selected == 'schedule' ) {
            this.size = 'medium mediumaside';
            args['schedule'] = 'yes';
            args['ssection_id'] = this.schedulesection_id;
            args['sdivision_id'] = this.scheduledivision_id;
            this.sections.schedule_sections.changeTxt = 'Add Schedule';
            this.sections.schedule_sections.addTxt = 'Unscheduled';
            this.sections.schedule_divisions.addTxt = 'Add Division';
        } else if( this.sections._tabs.selected == 'comments' ) {
            this.size = 'xlarge narrowaside';
            args['schedule'] = 'yes';
            args['comments'] = 'yes';
            args['ssection_id'] = this.schedulesection_id;
            args['sdivision_id'] = this.scheduledivision_id;
            args['adjudicators'] = 'yes';
            this.sections.schedule_sections.addTxt = '';
            this.sections.schedule_sections.changeTxt = '';
            this.sections.schedule_divisions.addTxt = '';
        } else if( this.sections._tabs.selected == 'competitors' ) {
            this.size = 'xlarge narrowaside';
            args['competitors'] = 'yes';
        } else if( this.sections._tabs.selected == 'photos' ) {
            this.size = 'xlarge narrowaside';
            args['schedule'] = 'yes';
            args['photos'] = 'yes';
            args['ssection_id'] = this.schedulesection_id;
            args['sdivision_id'] = this.scheduledivision_id;
            args['adjudicators'] = 'no';
            this.sections.schedule_sections.addTxt = '';
            this.sections.schedule_sections.changeTxt = '';
            this.sections.schedule_divisions.addTxt = '';
            this.sections.schedule_divisions.changeTxt = '';
        } else if( this.sections._tabs.selected == 'lists' ) {
            args['lists'] = 'yes';
            args['list_id'] = this.list_id;
            args['listsection_id'] = this.listsection_id;
        } else if( this.isSelected('more', 'adjudicators') == 'yes' ) {
            args['adjudicators'] = 'yes';
        } else if( this.isSelected('more', 'files') == 'yes' ) {
            args['files'] = 'yes';
        } else if( this.isSelected('more', 'certificates') == 'yes' ) {
            args['certificates'] = 'yes';
        } else if( this.isSelected('more', 'sponsors') == 'yes' ) {
        //} else if( this.sections._tabs.selected == 'sponsors' ) {
            args['sponsors'] = 'yes';
        } else if( this.isSelected('more', 'sponsors-old') == 'yes' ) {
        //} else if( this.sections._tabs.selected == 'sponsors-old' ) {
            args['sponsors'] = 'yes';
        }
        if( this.section_id > 0 ) {
            args['section_id'] = this.section_id;
        }
        if( this.teacher_customer_id > 0 ) {
            args['teacher_customer_id'] = this.teacher_customer_id;
        }
        M.api.getJSONCb('ciniki.musicfestivals.festivalGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.festival;
            p.data = rsp.festival;
            p.sections.timeslot_comments.headerValues[2] = '';
            p.sections.timeslot_comments.headerValues[3] = '';
            p.sections.timeslot_comments.headerValues[4] = '';
            if( rsp.festival.sections != null ) {
                p.data.registration_sections = [];
                for(var i in rsp.festival.sections) {
                    p.data.registration_sections.push({'id':rsp.festival.sections[i].id, 'name':rsp.festival.sections[i].name});
                }
//                p.data.registration_sections = rsp.festival.sections;
                p.data.registration_sections.push({'id':0, 'name':'All'});
            }
            if( rsp.festival.schedule_sections != null ) {
                for(var i in rsp.festival.schedule_sections) {
                    if( p.schedulesection_id > 0 && rsp.festival.schedule_sections[i].id == p.schedulesection_id ) {
                        if( rsp.festival.schedule_sections[i].adjudicator1_id > 0 && rsp.festival.adjudicators != null && rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator1_id] != null ) {
                            p.sections.timeslot_comments.headerValues[2] = rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator1_id].name;
                        }
                        if( rsp.festival.schedule_sections[i].adjudicator2_id > 0 && rsp.festival.adjudicators != null && rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator2_id] != null ) {
                            p.sections.timeslot_comments.headerValues[2] = rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator2_id].name;
                        }
                        if( rsp.festival.schedule_sections[i].adjudicator3_id > 0 && rsp.festival.adjudicators != null && rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator3_id] != null ) {
                            p.sections.timeslot_comments.headerValues[2] = rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator3_id].name;
                        }
                    }
                }
            }
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.festival.timeslotImageAdd = function(tid, row) {
        this.timeslot_image_uploader_tid = tid;
        this.timeslot_image_uploader_row = row;
        this.image_uploader = M.aE('input', this.panelUID + '_' + tid + '_upload', 'file_uploader');
        this.image_uploader.setAttribute('name', tid);
        this.image_uploader.setAttribute('type', 'file');
        this.image_uploader.setAttribute('onchange', 'M.ciniki_musicfestivals_main.festival.timeslotImageUpload();');
        this.image_uploader.click();
    }
    this.festival.timeslotImageUpload = function() {
        var files = this.image_uploader.files;
        M.startLoad();
        M.api.postJSONFile('ciniki.musicfestivals.timeslotImageDrop', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 
            'timeslot_id':this.timeslot_image_uploader_tid},
            files[0],
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.stopLoad();
                    M.api.err(rsp);
                    return false;
                }
                M.stopLoad();
                var p = M.ciniki_musicfestivals_main.festival;
                var t = M.gE(p.panelUID + '_timeslot_photos_grid');
                var cell = t.children[0].children[p.timeslot_image_uploader_row].children[1];
                cell.innerHTML += '<img class="clickable" onclick="M.ciniki_musicfestivals_main.timeslotimage.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + rsp.id + '\');" width="50px" height="50px" src=\'' + rsp.image + '\' />';
            });
    }
    this.festival.syllabusCopy = function(old_fid) {
        M.api.getJSONCb('ciniki.musicfestivals.festivalSyllabusCopy', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'old_festival_id':old_fid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.festival.open();
        });
    }
    this.festival.syllabusDownload = function() {
        M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id});
    }
    this.festival.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.festival.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) + 1] + ');';
        }
        return null;
    }
    this.festival.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.festival_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) - 1] + ');';
        }
        return null;
    }
    this.festival.addButton('edit', 'Edit', 'M.ciniki_musicfestivals_main.edit.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id);');
    this.festival.addClose('Back');
    this.festival.addButton('next', 'Next');
    this.festival.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Festival
    //
    this.edit = new M.panel('Festival', 'ciniki_musicfestivals_main', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.edit');
    this.edit.data = null;
    this.edit.festival_id = 0;
    this.edit.nplist = [];
    this.edit.sections = {
/*        '_document_logo_id':{'label':'Document Header Logo', 'type':'imageform', 'aside':'yes', 'fields':{
            'header_logo_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.edit.setFieldValue('header_logo_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_musicfestivals_main.edit.setFieldValue(fid,0);
                    return true;
                 },
             },
        }}, */
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'start_date':{'label':'Start', 'type':'date'},
            'end_date':{'label':'End', 'type':'date'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '30':'Current', '60':'Archived'}},
            'flags1':{'label':'Registrations Open', 'type':'flagtoggle', 'default':'off', 'bit':0x01, 'field':'flags'},
            'flags2':{'label':'Virtual Option', 'type':'flagtoggle', 'default':'off', 'bit':0x02, 'field':'flags',
                'on_fields':['flags3','virtual_date'],
                },
            'flags3':{'label':'Virtual Pricing', 'type':'flagtoggle', 'default':'off', 'bit':0x04, 'field':'flags', 'visible':'no'},
            'flags4':{'label':'Section End Dates', 'type':'flagtoggle', 'default':'off', 'bit':0x08, 'field':'flags'},
            'earlybird_date':{'label':'Earlybird Deadline', 'type':'datetime'},
            'live_date':{'label':'Live Deadline', 'type':'datetime'},
            'virtual_date':{'label':'Virtual Deadline', 'type':'datetime', 'visible':'no'},
            }},
        '_settings':{'label':'', 'aside':'yes', 'fields':{
            'age-restriction-msg':{'label':'Age Restriction Message', 'type':'text'},
            'president-name':{'label':'President Name', 'type':'text'},
            }},
// Remove 2022, could be readded in future
//        '_hybrid':{'label':'In Person/Virtual Choices', 'aside':'yes', 'fields':{
//            'inperson-choice-msg':{'label':'In Person Choice', 'type':'text', 'hint':'in person on a scheduled date'},
//            'virtual-choice-msg':{'label':'Virtual Choice', 'type':'text', 'hint':'virtually and submit a video'},
//            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'website', 'tabs':{
            'website':{'label':'Website', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'website\');'},
            'documents':{'label':'Documents', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'documents\');'},
            'registrations':{'label':'Registrations', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'registrations\');'},
            }},
        '_primary_image_id':{'label':'Primary Image', 'type':'imageform', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'; },
            'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_musicfestivals_main.edit.setFieldValue('primary_image_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                        M.ciniki_musicfestivals_main.edit.setFieldValue(fid,0);
                        return true;
                     },
                 },
        }},
        '_description':{'label':'Description', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'; },
            'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_document_logo_id':{'label':'Document Image', 'type':'imageform',
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'document_logo_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_musicfestivals_main.edit.setFieldValue('document_logo_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                        M.ciniki_musicfestivals_main.edit.setFieldValue(fid,0);
                        return true;
                     },
                 },
        }},
        '_document_header_msg':{'label':'Header Message', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'document_header_msg':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_document_footer_msg':{'label':'Footer Message', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'document_footer_msg':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_registration_parent_msg':{'label':'Registration Message - Parents', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'registration-parent-msg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_registration_teacher_msg':{'label':'Registration Message - Teachers', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'registration-teacher-msg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_registration_adult_msg':{'label':'Registration Message - Adults', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'registration-adult-msg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_competitor_parent_msg':{'label':'Competitor Message - Parents', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'competitor-parent-msg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_competitor_teacher_msg':{'label':'Competitor Message - Teachers', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'competitor-teacher-msg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_competitor_adult_msg':{'label':'Competitor Message - Adults', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'competitor-adult-msg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_waiver':{'label':'Waiver Message', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'waiver-title':{'label':'Title', 'type':'text'},
                'waiver-msg':{'label':'Message', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.edit.save();'},
            'updatename':{'label':'Update Public Names', 
                'visible':function() {return M.ciniki_musicfestivals_main.edit.festival_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.edit.updateNames();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.edit.festival_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.edit.save();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.festivalHistory', 'args':{'tnid':M.curTenantID, 'festival_id':this.festival_id, 'field':i}};
    }
    this.edit.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.showHideSection('_primary_image_id');
        this.showHideSection('_description');
        this.showHideSection('_document_logo_id');
        this.showHideSection('_document_header_msg');
        this.showHideSection('_document_footer_msg');
        this.showHideSection('_registration_parent_msg');
        this.showHideSection('_registration_teacher_msg');
        this.showHideSection('_registration_adult_msg');
        this.showHideSection('_competitor_parent_msg');
        this.showHideSection('_competitor_teacher_msg');
        this.showHideSection('_competitor_adult_msg');
        this.showHideSection('_waiver');
        this.refreshSection('_tabs');
    }
    this.edit.updateNames = function() {
        M.api.getJSONCb('ciniki.musicfestivals.registrationNamesUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert("Done");
        });
    }
    this.edit.open = function(cb, fid, list) {
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.festivalGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.edit;
            p.data = rsp.festival;
            if( (rsp.festival.flags&0x02) == 0x02 ) {
                p.sections.general.fields.flags3.visible = 'yes';
                p.sections.general.fields.virtual_date.visible = 'yes';
            } else {
                p.sections.general.fields.virtual_date.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.edit.close();'; }
        if( this.festival_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.festivalUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.festivalAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.edit.festival_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.edit.remove = function() {
        M.confirm('Are you sure you want to remove festival?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.festivalDelete', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.edit.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.edit.close();
            });
        });
    }
    this.edit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.edit.save(\'M.ciniki_musicfestivals_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) + 1] + ');\');';
        }
        return null;
    }
    this.edit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.edit.save(\'M.ciniki_musicfestivals_main.festival_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) - 1] + ');\');';
        }
        return null;
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.edit.save();');
    this.edit.addClose('Cancel');
    this.edit.addButton('next', 'Next');
    this.edit.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Section
    //
    this.section = new M.panel('Section', 'ciniki_musicfestivals_main', 'section', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.section');
    this.section.data = null;
    this.section.festival_id = 0;
    this.section.section_id = 0;
    this.section.nplists = {};
    this.section.nplist = [];
    this.section.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.section.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_musicfestivals_main.section.setFieldValue('primary_image_id',0);
                    return true;
                 },
             },
        }},
        'general':{'label':'Section', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text', 'required':'yes'},
            'sequence':{'label':'Order', 'type':'text', 'required':'yes', 'size':'small'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Hidden'}}},
            'live_end_dt':{'label':'Live Deadline', 'type':'datetime',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x08) == 0x08 ? 'yes' : 'no';},
                },
            'virtual_end_dt':{'label':'Virtual Deadline', 'type':'datetime',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x0a) == 0x0a ? 'yes' : 'no';},
                },
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'categories', 'tabs':{
            'categories':{'label':'Categories', 'fn':'M.ciniki_musicfestivals_main.section.switchTab(\'categories\');'},
            'synopsis':{'label':'Description', 'fn':'M.ciniki_musicfestivals_main.section.switchTab(\'synopsis\');'},
            }},
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'}},
            },
        '_description':{'label':'Description', 
            'visible':function() { return M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'}},
            },
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'categories' ? 'yes' : 'hidden'; },
            'addTxt':'Add Category',
            'addFn':'M.ciniki_musicfestivals_main.section.openCategory(0);',
            },
        '_buttons':{'label':'', 'buttons':{
            'syllabuspdf':{'label':'Download Syllabus (PDF)', 'fn':'M.ciniki_musicfestivals_main.section.downloadSyllabusPDF();'},
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.section.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.section.section_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.section.remove();'},
            }},
        };
    this.section.fieldValue = function(s, i, d) { return this.data[i]; }
    this.section.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.sectionHistory', 'args':{'tnid':M.curTenantID, 'section_id':this.section_id, 'field':i}};
    }
    this.section.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.name;
        }
    }
    this.section.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.section.openCategory(\'' + d.id + '\');';
    }
    this.section.openCategory = function(cid) {
        this.save("M.ciniki_musicfestivals_main.category.open('M.ciniki_musicfestivals_main.section.open();', '" + cid + "', this.section_id, this.festival_id, this.nplists.categories);");
    }
    this.section.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.refresh();
        this.show();
    }
    this.section.downloadSyllabusPDF = function() {
        M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_id':this.section_id});
    }
    this.section.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.section_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.sectionGet', {'tnid':M.curTenantID, 'section_id':this.section_id, 'festival_id':this.festival_id, 'categories':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.section;
            p.data = rsp.section;
            p.festival_id = rsp.section.festival_id;
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.section.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.section.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.section_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.sectionUpdate', {'tnid':M.curTenantID, 'section_id':this.section_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.sectionAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.section.section_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.section.remove = function() {
        M.confirm('Are you sure you want to remove section?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.sectionDelete', {'tnid':M.curTenantID, 'section_id':M.ciniki_musicfestivals_main.section.section_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.section.close();
            });
        });
    }
    this.section.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.section.save(\'M.ciniki_musicfestivals_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) + 1] + ');\');';
        }
        return null;
    }
    this.section.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.section.save(\'M.ciniki_musicfestivals_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) - 1] + ');\');';
        }
        return null;
    }
    this.section.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.section.save();');
    this.section.addClose('Cancel');
    this.section.addButton('next', 'Next');
    this.section.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Category
    //
    this.category = new M.panel('Category', 'ciniki_musicfestivals_main', 'category', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.category');
    this.category.data = null;
    this.category.category_id = 0;
    this.category.nplists = {};
    this.category.nplist = [];
    this.category.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.category.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_musicfestivals_main.category.setFieldValue(fid,0);
                    return true;
                 },
             },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'section_id':{'label':'Section', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'sequence':{'label':'Order', 'required':'yes', 'type':'text'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'classes', 'tabs':{
            'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.category.switchTab(\'classes\');'},
            'synopsis':{'label':'Description', 'fn':'M.ciniki_musicfestivals_main.category.switchTab(\'synopsis\');'},
            }},
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_musicfestivals_main.category.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'}},
            },
        '_description':{'label':'Description', 
            'visible':function() { return M.ciniki_musicfestivals_main.category.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'}},
            },
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_musicfestivals_main.category.sections._tabs.selected == 'classes' ? 'yes' : 'hidden'; },
            'headerValues':['Code', 'Name', 'Earlybird', 'Fee', 'Virtual'],
            'addTxt':'Add Class',
            'addFn':'M.ciniki_musicfestivals_main.category.openClass(0);',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.category.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.category.category_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.category.remove();'},
            }},
        };
    this.category.fieldValue = function(s, i, d) { return this.data[i]; }
    this.category.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.categoryHistory', 'args':{'tnid':M.curTenantID, 'category_id':this.category_id, 'field':i}};
    }
    this.category.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.code;
            case 1: return d.name;
            case 2: return (d.earlybird_fee > 0 ? M.formatDollar(d.earlybird_fee) : '');
            case 3: return (d.fee > 0 ? M.formatDollar(d.fee) : '');
            case 4: return (d.virtual_fee > 0 ? M.formatDollar(d.virtual_fee) : '');
        }
    }
    this.category.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.category.openClass(\'' + d.id + '\');';
    }
    this.category.openClass = function(cid) {
        this.save("M.ciniki_musicfestivals_main.class.open('M.ciniki_musicfestivals_main.category.open();','" + cid + "', this.category_id, this.festival_id, this.nplists.classes);");
    }
    this.category.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.refresh();
        this.show();
    }
    this.category.open = function(cb, cid, sid,fid,list) {
        if( cid != null ) { this.category_id = cid; }
        if( sid != null ) { this.section_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.categoryGet', {'tnid':M.curTenantID, 
            'category_id':this.category_id, 'festival_id':this.festival_id, 'section_id':this.section_id,
            'sections':'yes', 'classes':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.category;
            p.data = rsp.category;
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.sections.general.fields.section_id.options = rsp.sections;
            if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                && (M.ciniki_musicfestivals_main.festival.data.flags&0x04) == 0x04 
                ) {
                p.sections.classes.num_cols = 5;
            } else {
                p.sections.classes.num_cols = 4;
            }

            p.refresh();
            p.show(cb);
        });
    }
    this.category.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.category.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.category_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.categoryUpdate', {'tnid':M.curTenantID, 'category_id':this.category_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.categoryAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.category.category_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.category.remove = function() {
        M.confirm('Are you sure you want to remove category?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.categoryDelete', {'tnid':M.curTenantID, 'category_id':M.ciniki_musicfestivals_main.category.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.category.close();
            });
        });
    }
    this.category.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.category.save(\'M.ciniki_musicfestivals_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) + 1] + ');\');';
        }
        return null;
    }
    this.category.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.category.save(\'M.ciniki_musicfestivals_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) - 1] + ');\');';
        }
        return null;
    }
    this.category.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.category.save();');
    this.category.addClose('Cancel');
    this.category.addButton('next', 'Next');
    this.category.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Class
    //
    this.class = new M.panel('Class', 'ciniki_musicfestivals_main', 'class', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.class');
    this.class.data = null;
    this.class.festival_id = 0;
    this.class.class_id = 0;
    this.class.nplists = {};
    this.class.nplist = [];
    this.class.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'category_id':{'label':'Category', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'code':{'label':'Code', 'type':'text', 'size':'small'},
            'name':{'label':'Name', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text'},
            'earlybird_fee':{'label':'Earlybird Fee', 'type':'text', 'size':'small'},
            'fee':{'label':'Fee', 'type':'text', 'size':'small'},
            'virtual_fee':{'label':'Virtual Fee', 'type':'text', 'size':'small',
                'visible':function() { 
                    if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                        && (M.ciniki_musicfestivals_main.festival.data.flags&0x04) == 0x04 
                        ) {
                        return 'yes';
                    }
                    return 'no';
                    },
                },
            }},
        'registration':{'label':'Registration Options', 'aside':'yes', 'fields':{
            'flags1':{'label':'Online Registrations', 'type':'flagtoggle', 'default':'on', 'bit':0x01, 'field':'flags'},
            'flags2':{'label':'Multiple/Registrant', 'type':'flagtoggle', 'default':'on', 'bit':0x02, 'field':'flags'},
            'flags5':{'label':'2nd Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x10, 'field':'flags'},
            'flags6':{'label':'3rd Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x20, 'field':'flags'},
            'flags7':{'label':'4th Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x40, 'field':'flags'},
            'flags13':{'label':'2nd Title & Time', 'type':'flagtoggle', 'default':'off', 'bit':0x1000, 'field':'flags'},
            'flags14':{'label':'2nd Title & Time Optional', 'type':'flagtoggle', 'default':'off', 'bit':0x2000, 'field':'flags'},
            'flags15':{'label':'3rd Title & Time', 'type':'flagtoggle', 'default':'off', 'bit':0x4000, 'field':'flags'},
            'flags16':{'label':'3rd Title & Time Optional', 'type':'flagtoggle', 'default':'off', 'bit':0x8000, 'field':'flags'},
            }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3, 
            'headerValues':['Competitor', 'Teacher', 'Status'],
//            'addTxt':'Add Registration',
//            'addFn':'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.class.class_id,M.ciniki_musicfestivals_main.festival.festival_id,null,\'festival\');',
            },
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.class.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.class.class_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.class.remove();'},
            }},
        };
    this.class.fieldValue = function(s, i, d) { return this.data[i]; }
    this.class.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.classHistory', 'args':{'tnid':M.curTenantID, 'class_id':this.class_id, 'field':i}};
    }
    this.class.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name + M.subdue(' (',d.pronoun,')');
            case 1: return d.teacher_name;
            case 2: return d.status_text;
        }
    }
    this.class.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.class.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.class.festival_id, null,\'festival\');';
    }
    this.class.open = function(cb, iid, cid, fid, list) {
        if( iid != null ) { this.class_id = iid; }
        if( cid != null ) { this.category_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.classGet', {'tnid':M.curTenantID, 'class_id':this.class_id, 'festival_id':this.festival_id, 'category_id':this.category_id, 
            'registrations':'yes', 'categories':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.class;
            p.data = rsp.class;
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.sections.general.fields.category_id.options = rsp.categories;
            p.refresh();
            p.show(cb);
        });
    }
    this.class.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.class.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.class_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.classUpdate', {'tnid':M.curTenantID, 'class_id':this.class_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.classAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.class.class_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.class.remove = function() {
        M.confirm('Are you sure you want to remove class?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.classDelete', {'tnid':M.curTenantID, 'class_id':M.ciniki_musicfestivals_main.class.class_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.class.close();
            });
        });
    }
    this.class.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.class_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.class.save(\'M.ciniki_musicfestivals_main.class.open(null,' + this.nplist[this.nplist.indexOf('' + this.class_id) + 1] + ');\');';
        }
        return null;
    }
    this.class.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.class_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.class.save(\'M.ciniki_musicfestivals_main.classd.open(null,' + this.nplist[this.nplist.indexOf('' + this.class_id) - 1] + ');\');';
        }
        return null;
    }
    this.class.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.class.save();');
    this.class.addClose('Cancel');
    this.class.addButton('next', 'Next');
    this.class.addLeftButton('prev', 'Prev');

    //
    // Registration
    //
    this.registration = new M.panel('Registration', 'ciniki_musicfestivals_main', 'registration', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.registration');
    this.registration.data = null;
    this.registration.festival_id = 0;
    this.registration.teacher_customer_id = 0;
    this.registration.competitor1_id = 0;
    this.registration.competitor2_id = 0;
    this.registration.competitor3_id = 0;
    this.registration.competitor4_id = 0;
//    this.registration.competitor5_id = 0;
    this.registration.registration_id = 0;
    this.registration.nplist = [];
    this.registration._source = '';
    this.registration.sections = {
//        '_tabs':{'label':'', 'type':'paneltabs', 'field_id':'rtype', 'selected':'30', 'tabs':{
//            '30':{'label':'Individual', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("30");'},
//            '50':{'label':'Duet', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("50");'},
//            '60':{'label':'Trio', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("60");'},
//            '90':{'label':'Ensemble', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("90");'},
//            }},
        'teacher_details':{'label':'Teacher', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.registration.updateTeacher();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.registration.updateTeacher\',\'customer_id\':M.ciniki_musicfestivals_main.registration.teacher_customer_id});',
            'changeTxt':'Change',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.registration.updateTeacher();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.registration.updateTeacher\',\'customer_id\':0});',
            },
        '_display_name':{'label':'Duet/Trio/Ensemble Name', 'aside':'yes',
            'visible':'hidden',
//            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'fields':{ 
                'display_name':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        'competitor1_details':{'label':'Competitor 1', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            'addTxt':'',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 1);',
            'changeTxt':'Add',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 1);',
            },
        'competitor2_details':{'label':'Competitor 2', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':'hidden',
//            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>30?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 2);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 2);',
            },
        'competitor3_details':{'label':'Competitor 3', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':'hidden',
//            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>50?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 3);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 3);',
            },
        'competitor4_details':{'label':'Competitor 4', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':'hidden',
//            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 4);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 4);',
            },
/*        'competitor5_details':{'label':'Competitor 5', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 5);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 5);',
            }, */
        'invoice_details':{'label':'Invoice', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            },
        '_class':{'label':'Registration', 'fields':{
//            'status':{'label':'Status', 'required':'yes', 'type':'toggle', 'toggles':{'5':'Draft', '10':'Applied', '50':'Paid', '60':'Cancelled'}},
//            'payment_type':{'label':'Payment', 'type':'toggle', 'toggles':{'20':'Square', '50':'Visa', '55':'Mastercard', '100':'Cash', '105':'Cheque', '110':'Email', '120':'Other', '121':'Online'}},
            'class_id':{'label':'Class', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_musicfestivals_main.registration.updateForm',
                },
            'title1':{'label':'Title', 'type':'text'},
            'perf_time1':{'label':'Perf Time', 'type':'text', 'size':'small'},
            'title2':{'label':'2nd Title', 'type':'text',
                'visible':'no',
                },
            'perf_time2':{'label':'2nd Time', 'type':'text', 'size':'small',
                'visible':'no',
                },
            'title3':{'label':'3rd Title', 'type':'text',
                'visible':'no',
                },
            'perf_time3':{'label':'3rd Time', 'type':'text', 'size':'small',
                'visible':'no',
                },
            'fee':{'label':'Fee', 'type':'text', 'size':'small'},
            'participation':{'label':'Participate', 'type':'select', 
                'visible':function() { return (M.ciniki_musicfestivals_main.registration.data.festival.flags&0x02) == 0x02 ? 'yes' : 'no'},
                'onchangeFn':'M.ciniki_musicfestivals_main.registration.updateForm',
                'options':{
                    '0':'in person on a date to be scheduled',
                    '1':'virtually and submit a video online',
                }},
            'video1_url':{'label':'1st Video', 'type':'text', 
                'visible':'no',
                },
            'music1_orgfilename':{'label':'1st Music', 'type':'file',
                'visible':'no',
                'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(1);',
                },
            'video2_url':{'label':'2nd Video', 'type':'text', 
                'visible':'no',
                },
            'music2_orgfilename':{'label':'2nd Music', 'type':'file',
                'visible':'no',
                'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(2);',
                },
            'video3_url':{'label':'3rd Video', 'type':'text', 
                'visible':'no',
                },
            'music3_orgfilename':{'label':'3rd Music', 'type':'file',
                'visible':'no',
                'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(3);',
                },
            }},
/*        'music_buttons':{'label':'', 
            'visible':function() { return (M.ciniki_musicfestivals_main.registration.data.festival.flags&0x02) == 0x02 ? 'yes' : 'no'},
            'buttons':{
                'add':{'label':'Upload Music PDF', 'fn':'M.ciniki_musicfestivals_main.registration.uploadPDF();',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.data.music_orgfilename == '' ? 'yes' : 'no'},
                    },
                'upload':{'label':'Replace Music PDF', 'fn':'M.ciniki_musicfestivals_main.registration.uploadPDF();',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.data.music_orgfilename != '' ? 'yes' : 'no'},
                    },
                'download':{'label':'Download PDF', 'fn':'M.ciniki_musicfestivals_main.registration.downloadPDF();',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.data.music_orgfilename != '' ? 'yes' : 'no'},
                    },
            }}, */
        '_notes':{'label':'Registration Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_internal_notes':{'label':'Internal Admin Notes', 'fields':{
            'internal_notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.registration.save();'},
            'printcert':{'label':'Download Certificate PDF', 
                'visible':function() {return M.ciniki_musicfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.registration.printCert();'},
            'printcomments':{'label':'Download Comments PDF', 
                'visible':function() {return M.ciniki_musicfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.registration.printComments();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.registration.remove();'},
            }},
        };
    this.registration.fieldValue = function(s, i, d) { 
//        if( i == 'music_orgfilename' ) {
//            if( this.data[i] == '' ) {
//                return '<button>Upload</button>';
//            } else {
//                return this.data[i] + ' <button>Upload</button>';
//            }
//        }
        return this.data[i]; 
    }
    this.registration.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.registrationHistory', 'args':{'tnid':M.curTenantID, 'registration_id':this.registration_id, 'field':i}};
    }
    this.registration.cellValue = function(s, i, j, d) {
        if( s == 'competitor1_details' || s == 'competitor2_details' || s == 'competitor3_details' || s == 'competitor4_details' ) {
            switch(j) {
                case 0 : return d.label;
                case 1 : 
                    if( d.label == 'Email' ) {
                        return M.linkEmail(d.value);
                    } else if( d.label == 'Address' ) {
                        return d.value.replace(/\n/g, '<br/>');
                    }
                    return d.value;
            }
        }
        if( s == 'teacher_details' ) {
            switch(j) {
                case 0: return d.detail.label;
                case 1:
                    if( d.detail.label == 'Email' ) {
                        return M.linkEmail(d.detail.value);
                    } else if( d.detail.label == 'Address' ) {
                        return d.detail.value.replace(/\n/g, '<br/>');
                    }
                    return d.detail.value;
            }
        }
        if( s == 'invoice_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value.replace(/\n/, '<br/>');
            }
        }
    }
    this.registration.rowFn = function(s, i, d) {
        if( s == 'invoice_details' && this._source != 'invoice' && this._source != 'pos' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_musicfestivals_main.registration.open();\',\'mc\',{\'invoice_id\':\'' + this.data.invoice_id + '\'});';
        }
    }
    this.registration.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.showHideSection('_display_name');
        this.showHideSection('competitor2_details');
        this.showHideSection('competitor3_details');
        this.showHideSection('competitor4_details');
//        this.showHideSection('competitor5_details');
    }
    this.registration.updateForm = function(s, i, cf) {
        var festival = this.data.festival;
        var cid = this.formValue('class_id');
        var participation = this.formValue('participation');
        for(var i in this.classes) {
            if( this.classes[i].id == cid ) {
                var c = this.classes[i];
   
                if( cf == null ) {
                    if( (festival.flags&0x04) == 0x04 && participation == 1 ) {
                        this.setFieldValue('fee', c.virtual_fee);
                    } else if( festival.earlybird == 'yes' && c.earlybird_fee > 0 ) {
                        this.setFieldValue('fee', c.earlybird_fee);
                    } else {
                        this.setFieldValue('fee', c.fee);
                    }
                }

                this.sections._class.fields.title2.visible = (c.flags&0x1000) == 0x1000 ? 'yes' : 'no';
                this.sections._class.fields.perf_time2.visible = (c.flags&0x1000) == 0x1000 ? 'yes' : 'no';
                this.sections._class.fields.title3.visible = (c.flags&0x2000) == 0x2000 ? 'yes' : 'no';
                this.sections._class.fields.perf_time3.visible = (c.flags&0x2000) == 0x2000 ? 'yes' : 'no';
                this.sections._class.fields.video1_url.visible = (participation == 1 ? 'yes' : 'no');
                this.sections._class.fields.video2_url.visible = (participation == 1 && (c.flags&0x1000) == 0x1000 ? 'yes' : 'no');
                this.sections._class.fields.video3_url.visible = (participation == 1 && (c.flags&0x2000) == 0x2000 ? 'yes' : 'no');
                this.sections._class.fields.music1_orgfilename.visible = (participation == 1 ? 'yes' : 'no');
                this.sections._class.fields.music2_orgfilename.visible = (participation == 1 && (c.flags&0x1000) == 0x1000 ? 'yes' : 'no');
                this.sections._class.fields.music3_orgfilename.visible = (participation == 1 && (c.flags&0x2000) == 0x2000 ? 'yes' : 'no');

                this.sections._display_name.visible = (c.flags&0x70) > 0 ? 'yes' : 'hidden';
                this.sections.competitor2_details.visible = (c.flags&0x10) == 0x10 ? 'yes' : 'hidden';
                this.sections.competitor3_details.visible = (c.flags&0x20) == 0x20 ? 'yes' : 'hidden';
                this.sections.competitor4_details.visible = (c.flags&0x40) == 0x40 ? 'yes' : 'hidden';
                this.showHideSection('competitor2_details');
                this.showHideSection('competitor3_details');
                this.showHideSection('competitor4_details');
                this.showHideSection('_display_name');
                this.showHideFormField('_class', 'title2');
                this.showHideFormField('_class', 'perf_time2');
                this.showHideFormField('_class', 'title3');
                this.showHideFormField('_class', 'perf_time3');
                this.showHideFormField('_class', 'video1_url');
                this.showHideFormField('_class', 'video2_url');
                this.showHideFormField('_class', 'video3_url');
                this.showHideFormField('_class', 'music1_orgfilename');
                this.showHideFormField('_class', 'music2_orgfilename');
                this.showHideFormField('_class', 'music2_orgfilename');
            }
        }
    }
    this.registration.addCompetitor = function(cid,c) {
        this.save("M.ciniki_musicfestivals_main.competitor.open('M.ciniki_musicfestivals_main.registration.updateCompetitor(" + c + ");'," + cid + "," + this.festival_id + ",null);");
    }
    this.registration.updateCompetitor = function(c) {
        var p = M.ciniki_musicfestivals_main.competitor;
        if( this['competitor' + c + '_id'] != p.competitor_id ) {
            this['competitor' + c + '_id'] = p.competitor_id;
            this.save("M.ciniki_musicfestivals_main.registration.open();");
        } else {    
            this.open();
        }
/*
        M.api.getJSONCb('ciniki.musicfestivals.competitorGet', {'tnid':M.curTenantID, 'competitor_id':this['competitor'+c+'_id']}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.registration;
            p.data['competitor'+c+'_details'] = rsp.details;
            if( p['competitor' + c + '_id'] == 0 ) {
                p.sections['competitor'+c+'_details'].addTxt = '';
                p.sections['competitor'+c+'_details'].changeTxt = 'Add';
            } else {
                p.sections['competitor'+c+'_details'].addTxt = 'Edit';
                p.sections['competitor'+c+'_details'].changeTxt = 'Change';
            }
            p.refreshSection('competitor'+c+'_details');
            p.show();
        }); */
    }
    this.registration.updateTeacher = function(cid) {
        if( cid != null ) { 
            this.teacher_customer_id = cid;
            if( this.teacher_customer_id > 0 ) {
                M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.teacher_customer_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_musicfestivals_main.registration;
                    p.data.teacher_details = rsp.details;
                    if( p.customer_id == 0 ) {
                        p.sections.teacher_details.addTxt = '';
                        p.sections.teacher_details.changeTxt = 'Add';
                    } else {
                        p.sections.teacher_details.addTxt = 'Edit';
                        p.sections.teacher_details.changeTxt = 'Change';
                    }
                    p.refreshSection('teacher_details');
                    p.show();
                });
            } else {
                this.data.teacher_details = [];
                this.sections.teacher_details.addTxt = '';
                this.sections.teacher_details.changeTxt = 'Add';
                this.refreshSection('teacher_details');
                this.show();
            }
        } else {
            this.show();
        }
    }
    this.registration.printCert = function() {
        M.api.openFile('ciniki.musicfestivals.registrationCertificatesPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
    this.registration.printComments = function() {
        M.api.openFile('ciniki.musicfestivals.registrationCommentsPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
/*    this.registration.uploadPDF = function() {
        if( this.upload == null ) {
            this.upload = M.aE('input', this.panelUID + '_music_orgfilename_upload', 'image_uploader');
            this.upload.setAttribute('name', 'music_orgfilename');
            this.upload.setAttribute('type', 'file');
            this.upload.setAttribute('onchange', this.panelRef + '.uploadFile();');
        }
        this.upload.value = '';
        this.upload.click();
    }
    this.registration.uploadFile = function() {
        var f = this.upload;
        M.api.postJSONFile('ciniki.musicfestivals.registrationMusicAdd', 
            {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id}, 
            f.files[0], 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.registration;
                p.data.music_orgfilename = rsp.registration.music_orgfilename;
//                p.refreshSection('music_buttons');
                p.setFieldValue('music_orgfilename', rsp.registration.music_orgfilename);
            });
    } */
    this.registration.downloadMusic = function(i) {
        M.api.openFile('ciniki.musicfestivals.registrationMusicPDF',{'tnid':M.curTenantID, 'registration_id':this.registration_id, 'num':i});
    }
//    this.registration.downloadPDF = function() {
//        M.api.openFile('ciniki.musicfestivals.registrationMusicPDF',{'tnid':M.curTenantID, 'registration_id':this.registration_id});
//    }
    this.registration.open = function(cb, rid, tid, cid, fid, list, source) {
        if( rid != null ) { this.registration_id = rid; }
        if( tid != null ) { this.teacher_customer_id = tid; }
        if( fid != null ) { this.festival_id = fid; }
        if( cid != null ) { this.class_id = cid; }
        if( list != null ) { this.nplist = list; }
        if( source != null ) { this._source = source; }
        M.api.getJSONCb('ciniki.musicfestivals.registrationGet', {'tnid':M.curTenantID, 'registration_id':this.registration_id, 
            'teacher_customer_id':this.teacher_customer_id, 'festival_id':this.festival_id, 'class_id':this.class_id, 
            }, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.registration;
            p.data = rsp.registration;
            p.classes = rsp.classes;
            if( p.festival_id == 0 ) {
                p.festival_id = rsp.registration.festival_id;
            }
//            p.sections._tabs.selected = rsp.registration.rtype;
            p.sections._class.fields.class_id.options = rsp.classes;
            p.sections._class.fields.class_id.options.unshift({'id':0, 'name':''});
            p.teacher_customer_id = parseInt(rsp.registration.teacher_customer_id);
            if( p.teacher_customer_id == 0 ) {
                p.sections.teacher_details.addTxt = '';
                p.sections.teacher_details.changeTxt = 'Add';
            } else {
                p.sections.teacher_details.addTxt = 'Edit';
                p.sections.teacher_details.changeTxt = 'Change';
            }
            for(var i = 1; i<= 4; i++) {
                p['competitor' + i + '_id'] = parseInt(rsp.registration['competitor' + i + '_id']);
                if( p['competitor' + i + '_id'] == 0 ) {
                    p.sections['competitor' + i + '_details'].addTxt = '';
                    p.sections['competitor' + i + '_details'].changeTxt = 'Add';
                } else {
                    p.sections['competitor' + i + '_details'].addTxt = 'Edit';
                    p.sections['competitor' + i + '_details'].changeTxt = 'Change';
                }
            }
            p.sections._class.fields.participation.options = {
                '0':'in person on a date to be scheduled',
                '1':'virtually and submit a video online',
                };
            if( p.data.festival['inperson-choice-msg'] != null && p.data.festival['inperson-choice-msg'] != '' ) {
                p.sections._class.fields.participation.options[0] = p.data.festival['inperson-choice-msg'];
            }
            if( p.data.festival['virtual-choice-msg'] != null && p.data.festival['virtual-choice-msg'] != '' ) {
                p.sections._class.fields.participation.options[1] = p.data.festival['virtual-choice-msg'];
            }
            p.refresh();
            p.show(cb);
            p.updateForm(null,null,'no');
        });
    }
    this.registration.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.registration.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.formValue('class_id') == 0 ) {
            M.alert("You must select a class.");
            return false;
        }
//        if( this.competitor1_id == 0 ) {
//            M.alert("You must have a competitor.");
//            return false;
//        }
        if( this.registration_id > 0 ) {
            var c = this.serializeFormData('no');
            if( this.teacher_customer_id != this.data.teacher_customer_id ) {
                c.append('teacher_customer_id', this.teacher_customer_id);
            }
            if( this.competitor1_id != this.data.competitor1_id ) { c.append('&competitor1_id', this.competitor1_id); }
            if( this.competitor2_id != this.data.competitor2_id ) { c.append('competitor2_id', this.competitor2_id); }
            if( this.competitor3_id != this.data.competitor3_id ) { c.append('competitor3_id', this.competitor3_id); }
            if( this.competitor4_id != this.data.competitor4_id ) { c.append('competitor4_id', this.competitor4_id); }
//            if( this.competitor5_id != this.data.competitor5_id ) { c.append('competitor5_id', this.competitor5_id); }
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':this.registration_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&teacher_customer_id=' + this.teacher_customer_id;
            c += '&competitor1_id=' + this.competitor1_id;
            c += '&competitor2_id=' + this.competitor2_id;
            c += '&competitor3_id=' + this.competitor3_id;
            c += '&competitor4_id=' + this.competitor4_id;
//            c += '&competitor5_id=' + this.competitor5_id;
            M.api.postJSONCb('ciniki.musicfestivals.registrationAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.registration.registration_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.registration.remove = function() {
        var msg = 'Are you sure you want to remove this registration?';
        if( this.data.invoice_id > 0 && this.data.invoice_status >= 50 ) {
            msg = '**WARNING** Removing this registration will NOT remove the item from the Invoice. You will need make sure they have received a refund for the registration.';
        }
        M.confirm(msg,null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.registrationDelete', {'tnid':M.curTenantID, 'registration_id':M.ciniki_musicfestivals_main.registration.registration_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.registration.close();
            });
        });
    }
    this.registration.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.registration_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.registration.save(\'M.ciniki_musicfestivals_main.registration.open(null,' + this.nplist[this.nplist.indexOf('' + this.registration_id) + 1] + ');\');';
        }
        return null;
    }
    this.registration.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.registration_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.registration.save(\'M.ciniki_musicfestivals_main.registration_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.registration_id) - 1] + ');\');';
        }
        return null;
    }
    this.registration.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.registration.save();');
    this.registration.addClose('Cancel');
    this.registration.addButton('next', 'Next');
    this.registration.addLeftButton('prev', 'Prev');


    //
    // The panel to add/edit a competitor
    //
    this.competitor = new M.panel('Competitor', 'ciniki_musicfestivals_main', 'competitor', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.competitor');
    this.competitor.data = null;
    this.competitor.festival_id = 0;
    this.competitor.competitor_id = 0;
    this.competitor.nplist = [];
    this.competitor.sections = {
        'general':{'label':'Competitor', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text', 'livesearch':'yes'},
            'public_name':{'label':'Public Name', 'type':'text'},
            'pronoun':{'label':'Pronoun', 'type':'text',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x80); },
                },
            'parent':{'label':'Parent', 'type':'text'},
            }},
        '_other':{'label':'', 'aside':'yes', 'fields':{
            'age':{'label':'Age', 'type':'text'},
            'study_level':{'label':'Study/Level', 'type':'text'},
            'instrument':{'label':'Instrument', 'type':'text'},
            'flags1':{'label':'Waiver', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 'toggles':{'':'Unsigned', 'signed':'Signed'}},
            }},
        '_notes':{'label':'Competitor Notes', 'aside':'yes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_address':{'label':'', 'fields':{
            'address':{'label':'Address', 'type':'text'},
            'city':{'label':'City', 'type':'text', 'size':'small'},
            'province':{'label':'Province', 'type':'text', 'size':'small'},
            'postal':{'label':'Postal Code', 'type':'text', 'size':'small'},
            'phone_home':{'label':'Home Phone', 'type':'text', 'size':'small'},
            'phone_cell':{'label':'Cell Phone', 'type':'text', 'size':'small'},
            'email':{'label':'Email', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.competitor.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.competitor.competitor_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.competitor.remove();'},
            }},
        };
    this.competitor.fieldValue = function(s, i, d) { return this.data[i]; }
    this.competitor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.competitorHistory', 'args':{'tnid':M.curTenantID, 'competitor_id':this.competitor_id, 'field':i}};
    }
    this.competitor.liveSearchCb = function(s, i, value) {
        if( i == 'name' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.competitorSearch', 
                {'tnid':M.curTenantID, 'start_needle':value, 'limit':25}, function(rsp) { 
                    M.ciniki_musicfestivals_main.competitor.liveSearchShow(s, i, M.gE(M.ciniki_musicfestivals_main.competitor.panelUID + '_' + i), rsp.competitors); 
                });
        }
    }
    this.competitor.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.competitor.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_musicfestivals_main.competitor.open(null,\'' + d.id + '\');';
    }
    this.competitor.open = function(cb, cid, fid, list) {
        if( cid != null ) { this.competitor_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.competitorGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'competitor_id':this.competitor_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.competitor;
            p.data = rsp.competitor;
            p.refresh();
            p.show(cb);
        });
    }
    this.competitor.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.competitor.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.competitor_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.competitorUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'competitor_id':this.competitor_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.competitorAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.competitor.competitor_name = rsp.name;
                M.ciniki_musicfestivals_main.competitor.competitor_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.competitor.remove = function() {
        M.confirm('Are you sure you want to remove competitor?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.competitorDelete', {'tnid':M.curTenantID, 'competitor_id':M.ciniki_musicfestivals_main.competitor.competitor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.competitor.close();
            });
        });
    }
    this.competitor.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.competitor_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.competitor.save(\'M.ciniki_musicfestivals_main.competitor.open(null,' + this.nplist[this.nplist.indexOf('' + this.competitor_id) + 1] + ');\');';
        }
        return null;
    }
    this.competitor.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.competitor_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.competitor.save(\'M.ciniki_musicfestivals_main.competitor_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.competitor_id) - 1] + ');\');';
        }
        return null;
    }
    this.competitor.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.competitor.save();');
    this.competitor.addClose('Cancel');
    this.competitor.addButton('next', 'Next');
    this.competitor.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Section
    //
    this.schedulesection = new M.panel('Schedule Section', 'ciniki_musicfestivals_main', 'schedulesection', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.schedulesection');
    this.schedulesection.data = null;
    this.schedulesection.festival_id = 0;
    this.schedulesection.schedulesection_id = 0;
    this.schedulesection.nplist = [];
    this.schedulesection.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            }},
        'adjudicators':{'label':'Adjudicators', 'fields':{
            'adjudicator1_id':{'label':'First', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'adjudicator2_id':{'label':'Second', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'adjudicator3_id':{'label':'Third', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.schedulesection.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.schedulesection.schedulesection_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.schedulesection.remove();'},
            }},
        };
    this.schedulesection.fieldValue = function(s, i, d) { return this.data[i]; }
    this.schedulesection.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.scheduleSectionHistory', 'args':{'tnid':M.curTenantID, 'schedulesection_id':this.schedulesection_id, 'field':i}};
    }
    this.schedulesection.downloadPDF = function(f,i,n) {
        M.api.openFile('ciniki.musicfestivals.schedulePDF',{'tnid':M.curTenantID, 'festival_id':f, 'schedulesection_id':i, 'names':n});
    }
    this.schedulesection.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.schedulesection_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleSectionGet', 
            {'tnid':M.curTenantID, 'schedulesection_id':this.schedulesection_id, 'festival_id':this.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.schedulesection;
                p.data = rsp.schedulesection;
                rsp.adjudicators.unshift({'id':'0', 'name':'None'});
                p.sections.adjudicators.fields.adjudicator1_id.options = rsp.adjudicators;
                p.sections.adjudicators.fields.adjudicator2_id.options = rsp.adjudicators;
                p.sections.adjudicators.fields.adjudicator3_id.options = rsp.adjudicators;
                p.refresh();
                p.show(cb);
            });
    }
    this.schedulesection.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.schedulesection.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.schedulesection_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.scheduleSectionUpdate', 
                    {'tnid':M.curTenantID, 'schedulesection_id':this.schedulesection_id, 'festival_id':this.festival_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.scheduleSectionAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.schedulesection.schedulesection_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.schedulesection.remove = function() {
        M.confirm('Are you sure you want to remove this section?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleSectionDelete', {'tnid':M.curTenantID, 'schedulesection_id':M.ciniki_musicfestivals_main.schedulesection.schedulesection_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.schedulesection.close();
            });
        });
    }
    this.schedulesection.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.schedulesection_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.schedulesection.save(\'M.ciniki_musicfestivals_main.schedulesection.open(null,' + this.nplist[this.nplist.indexOf('' + this.schedulesection_id) + 1] + ');\');';
        }
        return null;
    }
    this.schedulesection.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.schedulesection_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.schedulesection.save(\'M.ciniki_musicfestivals_main.schedulesection_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.schedulesection_id) - 1] + ');\');';
        }
        return null;
    }
    this.schedulesection.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.schedulesection.save();');
    this.schedulesection.addClose('Cancel');
    this.schedulesection.addButton('next', 'Next');
    this.schedulesection.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Division
    //
    this.scheduledivision = new M.panel('Schedule Division', 'ciniki_musicfestivals_main', 'scheduledivision', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.scheduledivision');
    this.scheduledivision.data = null;
    this.scheduledivision.festival_id = 0;
    this.scheduledivision.ssection_id = 0;
    this.scheduledivision.scheduledivision_id = 0;
    this.scheduledivision.nplist = [];
    this.scheduledivision.sections = {
        'general':{'label':'', 'fields':{
            'ssection_id':{'label':'Section', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'division_date':{'label':'Date', 'required':'yes', 'type':'date'},
            'address':{'label':'Address', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.scheduledivision.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.scheduledivision.scheduledivision_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.scheduledivision.remove();'},
            }},
        };
    this.scheduledivision.fieldValue = function(s, i, d) { return this.data[i]; }
    this.scheduledivision.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.scheduleDivisionHistory', 'args':{'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'field':i}};
    }
    this.scheduledivision.open = function(cb, sid, ssid, fid, list) {
        if( sid != null ) { this.scheduledivision_id = sid; }
        if( ssid != null ) { this.ssection_id = ssid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleDivisionGet', 
            {'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id, 'ssection_id':this.ssection_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.scheduledivision;
                p.data = rsp.scheduledivision;
                p.sections.general.fields.ssection_id.options = rsp.schedulesections;
                p.refresh();
                p.show(cb);
            });
    }
    this.scheduledivision.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.scheduledivision.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.scheduledivision_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.scheduleDivisionUpdate', 
                    {'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.scheduleDivisionAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduledivision.scheduledivision_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.scheduledivision.remove = function() {
        M.confirm('Are you sure you want to remove this division?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleDivisionDelete', {'tnid':M.curTenantID, 'scheduledivision_id':M.ciniki_musicfestivals_main.scheduledivision.scheduledivision_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduledivision.close();
            });
        });
    }
    this.scheduledivision.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduledivision_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.scheduledivision.save(\'M.ciniki_musicfestivals_main.scheduledivision.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduledivision_id) + 1] + ');\');';
        }
        return null;
    }
    this.scheduledivision.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduledivision_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.scheduledivision.save(\'M.ciniki_musicfestivals_main.scheduledivision_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduledivision_id) - 1] + ');\');';
        }
        return null;
    }
    this.scheduledivision.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.scheduledivision.save();');
    this.scheduledivision.addClose('Cancel');
    this.scheduledivision.addButton('next', 'Next');
    this.scheduledivision.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Time Slot
    //
    this.scheduletimeslot = new M.panel('Schedule Time Slot', 'ciniki_musicfestivals_main', 'scheduletimeslot', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.scheduletimeslot');
    this.scheduletimeslot.data = null;
    this.scheduletimeslot.festival_id = 0;
    this.scheduletimeslot.scheduletimeslot_id = 0;
    this.scheduletimeslot.sdivision_id = 0;
    this.scheduletimeslot.nplist = [];
    this.scheduletimeslot.sections = {
        'general':{'label':'', 'fields':{
            'sdivision_id':{'label':'Division', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'slot_time':{'label':'Time', 'required':'yes', 'type':'text', 'size':'small'},
            'class1_id':{'label':'Class 1', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations'},
            'class2_id':{'label':'Class 2', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations'},
            'class3_id':{'label':'Class 3', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations'},
            'name':{'label':'Name', 'type':'text'},
            }},
        '_options':{'label':'',
            'visible':function() {
                var p = M.ciniki_musicfestivals_main.scheduletimeslot;
                var c1 = p.formValue('class1_id');
                var c2 = p.formValue('class2_id');
                var c3 = p.formValue('class3_id');
                if( c1 == null && p.data.class1_id > 0 && p.data.class2_id == 0 && p.data.class3_id == 0 ) { return 'yes'; }
                return (c1 != null && c1 > 0 && (c2 == null || c2 == 0) && (c3 == null || c3 == 0) ? 'yes' : 'hidden');
                },
            'fields':{
                'flags1':{'label':'Split Class', 'type':'flagtoggle', 'default':'off', 'bit':0x01, 'field':'flags', 
                    'onchange':'M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations'},
            }},
        '_registrations':{'label':'Registrations', 
            'visible':'hidden',
            'fields':{
                'registrations':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[]},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'Description', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.scheduletimeslot.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.scheduletimeslot.scheduletimeslot_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.scheduletimeslot.remove();'},
            }},
        };
    this.scheduletimeslot.fieldValue = function(s, i, d) { return this.data[i]; }
    this.scheduletimeslot.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.scheduleTimeslotHistory', 'args':{'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'field':i}};
    }
    this.scheduletimeslot.updateRegistrations = function() {
        this.sections._registrations.visible = 'hidden';
        if( this.formValue('flags1') == 'on' && this.formValue('class1_id') > 0 && this.formValue('class2_id') == 0 && this.formValue('class3_id') == 0 && this.data.classes != null ) {
            for(var i in this.data.classes) {
                if( this.data.classes[i].id == this.formValue('class1_id') ) {
                    if( this.data.classes[i].registrations != null ) {
                        this.sections._registrations.visible = 'yes';
                        this.sections._registrations.fields.registrations.list = this.data.classes[i].registrations;
                    }
                }
            }
        }
        this.showHideSection('_options');
        this.showHideSection('_registrations');
        if( this.sections._registrations.visible == 'yes' ) {
            this.refreshSection('_registrations');
        }
    }
    this.scheduletimeslot.open = function(cb, sid, did, fid, list) {
        if( sid != null ) { this.scheduletimeslot_id = sid; }
        if( did != null ) { this.sdivision_id = did; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleTimeslotGet', 
            {'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id, 'sdivision_id':this.sdivision_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.scheduletimeslot;
                p.data = rsp.scheduletimeslot;
                p.data.classes = rsp.classes;
                p.sections.general.fields.sdivision_id.options = rsp.scheduledivisions;
                rsp.classes.unshift({'id':0, 'name':'No Class'});
                p.sections.general.fields.class1_id.options = rsp.classes;
                p.sections.general.fields.class2_id.options = rsp.classes;
                p.sections.general.fields.class3_id.options = rsp.classes;
                p.sections._registrations.visible = 'hidden';
                if( rsp.scheduletimeslot.class1_id > 0 && rsp.classes != null ) {
                    for(var i in rsp.classes) {
                        if( rsp.classes[i].id == rsp.scheduletimeslot.class1_id ) {
                            if( rsp.classes[i].registrations != null ) {
                                if( (rsp.scheduletimeslot.flags&0x01) > 0 ) {
                                    p.sections._registrations.visible = 'yes';
                                }
                                p.sections._registrations.fields.registrations.list = rsp.classes[i].registrations;
                            }
                        }
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.scheduletimeslot.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.scheduletimeslot.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.scheduletimeslot_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.scheduleTimeslotUpdate', 
                    {'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.scheduleTimeslotAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduletimeslot.scheduletimeslot_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.scheduletimeslot.remove = function() {
        M.confirm('Are you sure you want to remove timeslot?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleTimeslotDelete', {'tnid':M.curTenantID, 'scheduletimeslot_id':M.ciniki_musicfestivals_main.scheduletimeslot.scheduletimeslot_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduletimeslot.close();
            });
        });
    }
    this.scheduletimeslot.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduletimeslot_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.scheduletimeslot.save(\'M.ciniki_musicfestivals_main.scheduletimeslot.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduletimeslot_id) + 1] + ');\');';
        }
        return null;
    }
    this.scheduletimeslot.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduletimeslot_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.scheduletimeslot.save(\'M.ciniki_musicfestivals_main.scheduletimeslot_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduletimeslot_id) - 1] + ');\');';
        }
        return null;
    }
    this.scheduletimeslot.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.scheduletimeslot.save();');
    this.scheduletimeslot.addClose('Cancel');
    this.scheduletimeslot.addButton('next', 'Next');
    this.scheduletimeslot.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Time Slot Comments
    //
    this.timeslotcomments = new M.panel('Comments', 'ciniki_musicfestivals_main', 'timeslotcomments', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.timeslotcomments');
    this.timeslotcomments.data = null;
    this.timeslotcomments.festival_id = 0;
    this.timeslotcomments.timeslot_id = 0;
    this.timeslotcomments.nplist = [];
    this.timeslotcomments.sections = {};
    this.timeslotcomments.fieldValue = function(s, i, d) { return this.data[i]; }
    this.timeslotcomments.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.scheduleTimeslotHistory', 'args':{'tnid':M.curTenantID, 'scheduletimeslot_id':this.timeslot_id, 'field':i}};
    }
    this.timeslotcomments.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0 : return d.label;
            case 1 : return d.value;
            }
    }
    this.timeslotcomments.open = function(cb, tid, fid, list) {
        if( tid != null ) { this.timeslot_id = tid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleTimeslotCommentsGet', 
            {'tnid':M.curTenantID, 'timeslot_id':this.timeslot_id, 'festival_id':this.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.timeslotcomments;
                p.data = rsp.timeslot;
                p.sections = {};
                for(var i in rsp.timeslot.registrations) {
                    var registration = rsp.timeslot.registrations[i];
                    p.sections['details_' + i] = {'label':'Registration', 'type':'simplegrid', 'num_cols':2, 'aside':'yes'};
                    p.data['details_' + i] = [
                        {'label':'Class', 'value':registration.reg_class_name},
                        {'label':'Participant', 'value':registration.name},
                        {'label':'Title', 'value':registration.title1},
                        {'label':'Video', 'value':M.hyperlink(registration.video1_url)},
                        {'label':'Music', 'value':registration.music1_orgfilename},
                        ];
                    if( (registration.reg_flags&0x1000) == 0x1000 ) {
                        p.data['details_' + i].push({'label':'2nd Title', 'value':registration.title2});
                        p.data['details_' + i].push({'label':'2nd Video', 'value':M.hyperlink(registration.video2_url)});
                        p.data['details_' + i].push({'label':'2nd Music', 'value':registration.music2_orgfilename});
                    }
                    if( (registration.reg_flags&0x4000) == 0x4000 ) {
                        p.data['details_' + i].push({'label':'3rd Title', 'value':registration.title3});
                        p.data['details_' + i].push({'label':'3rd Video', 'value':M.hyperlink(registration.video3_url)});
                        p.data['details_' + i].push({'label':'3rd Music', 'value':registration.music3_orgfilename});
                    }
                    // 
                    // Setup the comment, grade & score fields, could be for multiple adjudicators
                    //
                    for(var j in rsp.adjudicators) {
                        p.sections['comments_' + i] = {'label':rsp.adjudicators[j].display_name, 'fields':{}};
                        p.sections['comments_' + i].fields['comments_' + rsp.timeslot.registrations[i].id + '_' + rsp.adjudicators[j].id] = {
                            'label':'Comments', 
                            'type':'textarea', 
                            'size':'large',
                            };
//                        p.sections['comments_' + i].fields['grade_' + rsp.timeslot.registrations[i].id + '_' + rsp.adjudicators[j].id] = {
//                            'label':'Grade', 
//                            'type':'text', 
//                            'size':'small',
//                            };
                        p.sections['comments_' + i].fields['score_' + rsp.timeslot.registrations[i].id + '_' + rsp.adjudicators[j].id] = {
                            'label':'Mark', 
                            'type':'text', 
                            'size':'small',
                            };
                        if( M.modFlagOn('ciniki.musicfestivals', 0x08) ) {
                            p.sections['comments_' + i].fields['placement_' + rsp.timeslot.registrations[i].id + '_' + rsp.adjudicators[j].id] = {
                                'label':'Placement', 
                                'type':'text', 
                                'size':'large',
                                };
                        }
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.timeslotcomments.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.timeslotcomments.close();'; }
        if( !this.checkForm() ) { return false; }
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.musicfestivals.scheduleTimeslotCommentsUpdate', 
                {'tnid':M.curTenantID, 'timeslot_id':this.timeslot_id, 'festival_id':this.festival_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
        } else {
            eval(cb);
        }
    }
    this.timeslotcomments.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.timeslotcomments.save();');
    this.timeslotcomments.addClose('Cancel');


    //
    // Adjudicators
    //
    this.adjudicator = new M.panel('Adjudicator', 'ciniki_musicfestivals_main', 'adjudicator', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.adjudicator');
    this.adjudicator.data = null;
    this.adjudicator.festival_id = 0;
    this.adjudicator.adjudicator_id = 0;
    this.adjudicator.customer_id = 0;
    this.adjudicator.nplist = [];
    this.adjudicator.sections = {
        '_image_id':{'label':'Adjudicator Photo', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.adjudicator.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_musicfestivals_main.adjudicator.setFieldValue(fid,0);
                    return true;
                 },
             },
        }}, 
        'customer_details':{'label':'Adjudicator', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer\',\'customer_id\':M.ciniki_musicfestivals_main.adjudicator.data.customer_id});',
            'changeTxt':'Change customer',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer\',\'customer_id\':0});',
            },
        '_description':{'label':'Full Bio', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.adjudicator.save();'},
            'delete':{'label':'Remove Adjudicator', 
                'visible':function() {return M.ciniki_musicfestivals_main.adjudicator.adjudicator_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.adjudicator.remove();'},
            }},
        };
    this.adjudicator.fieldValue = function(s, i, d) { return this.data[i]; }
    this.adjudicator.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.adjudicatorHistory', 'args':{'tnid':M.curTenantID, 'adjudicator_id':this.adjudicator_id, 'field':i}};
    }
    this.adjudicator.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' && j == 0 ) { return d.detail.label; }
        if( s == 'customer_details' && j == 1 ) {
            if( d.detail.label == 'Email' ) {
                return M.linkEmail(d.detail.value);
            } else if( d.detail.label == 'Address' ) {
                return d.detail.value.replace(/\n/g, '<br/>');
            }
            return d.detail.value;
        }
    };
    this.adjudicator.open = function(cb, aid, cid, fid, list) {
        if( cb != null ) { this.cb = cb; }
        if( aid != null ) { this.adjudicator_id = aid; }
        if( cid != null ) { this.customer_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        if( aid != null && aid == 0 && cid != null && cid == 0 ) {
            M.startApp('ciniki.customers.edit',null,this.cb,'mc',{'next':'M.ciniki_musicfestivals_main.adjudicator.openCustomer', 'customer_id':0});
            return true;
        }
        M.api.getJSONCb('ciniki.musicfestivals.adjudicatorGet', {'tnid':M.curTenantID, 'customer_id':this.customer_id, 'adjudicator_id':this.adjudicator_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.adjudicator;
            p.data = rsp.adjudicator;
            if( rsp.adjudicator.id > 0 ) {
                p.festival_id = rsp.adjudicator.festival_id;
            }
            p.customer_id = rsp.adjudicator.customer_id;
            if( p.customer_id == 0 ) {
                p.sections.customer_details.addTxt = '';
                p.sections.customer_details.changeTxt = 'Add';
            } else {
                p.sections.customer_details.addTxt = 'Edit';
                p.sections.customer_details.changeTxt = 'Change';
            }
            p.refresh();
            p.show();
        });
    }
    this.adjudicator.openCustomer = function(cid) {
        this.open(null,null,cid);
    }
    this.adjudicator.updateCustomer = function(cid) {
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.adjudicator;
            p.data.customer_details = rsp.details;
            if( p.customer_id == 0 ) {
                p.sections.customer_details.addTxt = '';
                p.sections.customer_details.changeTxt = 'Add';
            } else {
                p.sections.customer_details.addTxt = 'Edit';
                p.sections.customer_details.changeTxt = 'Change';
            }
            p.refreshSection('customer_details');
            p.show();
        });
    }
    this.adjudicator.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.adjudicator.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.adjudicator_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.adjudicatorUpdate', {'tnid':M.curTenantID, 'adjudicator_id':this.adjudicator_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.adjudicatorAdd', {'tnid':M.curTenantID, 'customer_id':this.customer_id, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.adjudicator.adjudicator_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.adjudicator.remove = function() {
        M.confirm('Are you sure you want to remove adjudicator?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.adjudicatorDelete', {'tnid':M.curTenantID, 'adjudicator_id':M.ciniki_musicfestivals_main.adjudicator.adjudicator_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.adjudicator.close();
            });
        });
    }
    this.adjudicator.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.adjudicator_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.adjudicator.save(\'M.ciniki_musicfestivals_main.adjudicator.open(null,' + this.nplist[this.nplist.indexOf('' + this.adjudicator_id) + 1] + ');\');';
        }
        return null;
    }
    this.adjudicator.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.adjudicator_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.adjudicator.save(\'M.ciniki_musicfestivals_main.adjudicator_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.adjudicator_id) - 1] + ');\');';
        }
        return null;
    }
    this.adjudicator.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.adjudicator.save();');
    this.adjudicator.addClose('Cancel');
    this.adjudicator.addButton('next', 'Next');
    this.adjudicator.addLeftButton('prev', 'Prev');

    //
    // The panel to display the add form
    //
    this.addfile = new M.panel('Add File', 'ciniki_musicfestivals_main', 'addfile', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.addfile');
    this.addfile.default_data = {'type':'20'};
    this.addfile.festival_id = 0;
    this.addfile.data = {}; 
    this.addfile.sections = {
        '_file':{'label':'File', 'fields':{
            'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
        }},
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'webflags':{'label':'Website', 'type':'flags', 'default':'1', 'flags':{'1':{'name':'Visible'}}},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.addfile.save();'},
        }},
    };
    this.addfile.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) { return this.data[i]; } 
        return ''; 
    };
    this.addfile.open = function(cb, eid) {
        this.reset();
        this.data = {'name':''};
        this.file_id = 0;
        this.festival_id = eid;
        this.refresh();
        this.show(cb);
    };
    this.addfile.save = function() {
        var c = this.serializeFormData('yes');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.musicfestivals.fileAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_musicfestivals_main.addfile.file_id = rsp.id;
                    M.ciniki_musicfestivals_main.addfile.close();
                });
        } else {
            this.close();
        }
    };
    this.addfile.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.addfile.save();');
    this.addfile.addClose('Cancel');

    //
    // The panel to display the edit form
    //
    this.editfile = new M.panel('File', 'ciniki_musicfestivals_main', 'editfile', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.info.editfile');
    this.editfile.file_id = 0;
    this.editfile.data = null;
    this.editfile.sections = {
        'info':{'label':'Details', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'webflags':{'label':'Website', 'type':'flags', 'default':'1', 'flags':{'1':{'name':'Visible'}}},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.editfile.save();'},
            'download':{'label':'Download', 'fn':'M.ciniki_musicfestivals_main.editfile.download(M.ciniki_musicfestivals_main.editfile.file_id);'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_musicfestivals_main.editfile.remove();'},
        }},
    };
    this.editfile.fieldValue = function(s, i, d) { 
        return this.data[i]; 
    }
    this.editfile.sectionData = function(s) {
        return this.data[s];
    };
    this.editfile.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.fileHistory', 'args':{'tnid':M.curTenantID, 'file_id':this.file_id, 'field':i}};
    };
    this.editfile.open = function(cb, fid) {
        if( fid != null ) { this.file_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.fileGet', {'tnid':M.curTenantID, 'file_id':this.file_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.editfile;
            p.data = rsp.file;
            p.refresh();
            p.show(cb);
        });
    };
    this.editfile.save = function() {
        var c = this.serializeFormData('no');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.musicfestivals.fileUpdate', {'tnid':M.curTenantID, 'file_id':this.file_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_musicfestivals_main.editfile.close();
            });
        }
    };
    this.editfile.remove = function() {
        M.confirm('Are you sure you want to delete \'' + this.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.fileDelete', {'tnid':M.curTenantID, 'file_id':M.ciniki_musicfestivals_main.editfile.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_musicfestivals_main.editfile.close();
            });
        });
    };
    this.editfile.download = function(fid) {
        M.api.openFile('ciniki.musicfestivals.fileDownload', {'tnid':M.curTenantID, 'file_id':fid});
    };
    this.editfile.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.editfile.save();');
    this.editfile.addClose('Cancel');

    //
    // The panel to email a teacher their list of registrations
    //
    this.emailregistrations = new M.panel('Email Registrations', 'ciniki_musicfestivals_main', 'emailregistrations', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.emailregistrations');
    this.emailregistrations.data = {};
    this.emailregistrations.sections = {
        '_subject':{'label':'', 'type':'simpleform', 'aside':'yes', 'fields':{
            'subject':{'label':'Subject', 'type':'text'},
        }},
        '_message':{'label':'Message', 'type':'simpleform', 'aside':'yes', 'fields':{
            'message':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
        }},
        '_save':{'label':'', 'aside':'yes', 'buttons':{
            'send':{'label':'Send', 'fn':'M.ciniki_musicfestivals_main.emailregistrations.send();'},
        }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['Class', 'Registrant', 'Title', 'Time', 'Virtual'],
            'cellClasses':['', '', '', '', ''],
            },
    };
    this.emailregistrations.fieldValue = function(s, i, d) { return ''; }
    this.emailregistrations.cellValue = function(s, i, j, d) {
        if( s == 'registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.title1;
                case 3: return d.perf_time1;
                case 4: return (d.participation == 1 ? 'Virtual' : 'In Person');
            }
        }
    }
    this.emailregistrations.open = function(cb, reg) {
        this.sections.registrations.label = M.ciniki_musicfestivals_main.festival.sections.registrations.label;
        this.data.registrations = M.ciniki_musicfestivals_main.festival.data.registrations;
        this.refresh();
        this.show(cb);
    };
    this.emailregistrations.send = function() {
        var c = this.serializeForm('yes');
        M.api.postJSONCb('ciniki.musicfestivals.registrationsEmailSend', 
            {'tnid':M.curTenantID, 'teacher_id':M.ciniki_musicfestivals_main.festival.teacher_customer_id, 'festival_id':M.ciniki_musicfestivals_main.festival.festival_id}, c, 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_musicfestivals_main.emailregistrations.close();
            });
    }
    this.emailregistrations.addButton('send', 'Send', 'M.ciniki_musicfestivals_main.emailregistrations.send();');
    this.emailregistrations.addClose('Cancel');

    //
    // The panel to edit Sponsor
    //
    this.sponsor = new M.panel('Sponsor', 'ciniki_musicfestivals_main', 'sponsor', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.sponsor');
    this.sponsor.data = null;
    this.sponsor.festival_id = 0;
    this.sponsor.sponsor_id = 0;
    this.sponsor.nplist = [];
    this.sponsor.sections = {
        '_image_id':{'label':'Logo', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.sponsor.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_musicfestivals_main.sponsor.setFieldValue(fid, 0);
                    return true;
                    },
             },
        }},
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'url':{'label':'Website', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Level 1'}, '2':{'name':'Level 2'}}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.sponsor.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.sponsor.sponsor_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.sponsor.remove();'},
            }},
        };
    this.sponsor.fieldValue = function(s, i, d) { return this.data[i]; }
    this.sponsor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.sponsorHistory', 'args':{'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id, 'field':i}};
    }
    this.sponsor.open = function(cb, sid, fid) {
        if( sid != null ) { this.sponsor_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.sponsorGet', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.sponsor;
            p.data = rsp.sponsor;
            p.refresh();
            p.show(cb);
        });
    }
    this.sponsor.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.sponsor.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.sponsor_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.sponsorUpdate', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.sponsorAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.sponsor.sponsor_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.sponsor.remove = function() {
        if( confirm('Are you sure you want to remove sponsor?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.sponsorDelete', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.sponsor.close();
            });
        }
    }
    this.sponsor.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.sponsor_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.sponsor.save(\'M.ciniki_musicfestivals_main.sponsor.open(null,' + this.nplist[this.nplist.indexOf('' + this.sponsor_id) + 1] + ');\');';
        }
        return null;
    }
    this.sponsor.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.sponsor_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.sponsor.save(\'M.ciniki_musicfestivals_main.sponsor.open(null,' + this.nplist[this.nplist.indexOf('' + this.sponsor_id) - 1] + ');\');';
        }
        return null;
    }
    this.sponsor.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.sponsor.save();');
    this.sponsor.addClose('Cancel');
    this.sponsor.addButton('next', 'Next');
    this.sponsor.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Time Slot Image
    //
    this.timeslotimage = new M.panel('Schedule Time Slot Image', 'ciniki_musicfestivals_main', 'timeslotimage', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.timeslotimage');
    this.timeslotimage.data = null;
    this.timeslotimage.timeslot_image_id = 0;
    this.timeslotimage.nplist = [];
    this.timeslotimage.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.timeslotimage.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'fields':{
            'title':{'label':'Title', 'type':'text'},
            'flags':{'label':'Options', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.timeslotimage.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.timeslotimage.timeslot_image_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.timeslotimage.remove();'},
            }},
        };
    this.timeslotimage.fieldValue = function(s, i, d) { return this.data[i]; }
    this.timeslotimage.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.timeslotImageHistory', 'args':{'tnid':M.curTenantID, 'timeslot_image_id':this.timeslot_image_id, 'field':i}};
    }
    this.timeslotimage.open = function(cb, tid, list) {
        if( tid != null ) { this.timeslot_image_id = tid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.timeslotImageGet', {'tnid':M.curTenantID, 'timeslot_image_id':this.timeslot_image_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.timeslotimage;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    }
    this.timeslotimage.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.timeslotimage.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.timeslot_image_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.timeslotImageUpdate', {'tnid':M.curTenantID, 'timeslot_image_id':this.timeslot_image_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.timeslotImageAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.timeslotimage.timeslot_image_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.timeslotimage.remove = function() {
        if( confirm('Are you sure you want to remove timeslotimage?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.timeslotImageDelete', {'tnid':M.curTenantID, 'timeslot_image_id':this.timeslot_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.timeslotimage.close();
            });
        }
    }
    this.timeslotimage.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.timeslot_image_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.timeslotimage.save(\'M.ciniki_musicfestivals_main.timeslotimage.open(null,' + this.nplist[this.nplist.indexOf('' + this.timeslot_image_id) + 1] + ');\');';
        }
        return null;
    }
    this.timeslotimage.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.timeslot_image_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.timeslotimage.save(\'M.ciniki_musicfestivals_main.timeslotimage.open(null,' + this.nplist[this.nplist.indexOf('' + this.timeslot_image_id) - 1] + ');\');';
        }
        return null;
    }
    this.timeslotimage.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.timeslotimage.save();');
    this.timeslotimage.addClose('Cancel');
    this.timeslotimage.addButton('next', 'Next');
    this.timeslotimage.addLeftButton('prev', 'Prev');

    //
    // The panel to edit a List
    //
    this.list = new M.panel('List', 'ciniki_musicfestivals_main', 'list', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.list');
    this.list.data = null;
    this.list.list_id = 0;
    this.list.festival_id = 0;
    this.list.nplist = [];
    this.list.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'category':{'label':'Category', 'required':'yes', 'type':'text'},
            }},
        '_intro':{'label':'Introduction', 'fields':{
            'intro':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.list.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.list.list_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.list.remove();'},
            }},
        };
    this.list.fieldValue = function(s, i, d) { return this.data[i]; }
    this.list.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.listHistory', 'args':{'tnid':M.curTenantID, 'list_id':this.list_id, 'field':i}};
    }
    this.list.open = function(cb, lid, fid, list) {
        if( lid != null ) { this.list_id = lid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.listGet', {'tnid':M.curTenantID, 'list_id':this.list_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.list;
            p.data = rsp.list;
            p.refresh();
            p.show(cb);
        });
    }
    this.list.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.list.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.list_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.listUpdate', {'tnid':M.curTenantID, 'list_id':this.list_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.listAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.list.list_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.list.remove = function() {
        if( confirm('Are you sure you want to remove list?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.listDelete', {'tnid':M.curTenantID, 'list_id':this.list_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.list.close();
            });
        }
    }
    this.list.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.list_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.list.save(\'M.ciniki_musicfestivals_main.list.open(null,' + this.nplist[this.nplist.indexOf('' + this.list_id) + 1] + ');\');';
        }
        return null;
    }
    this.list.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.list_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.list.save(\'M.ciniki_musicfestivals_main.list.open(null,' + this.nplist[this.nplist.indexOf('' + this.list_id) - 1] + ');\');';
        }
        return null;
    }
    this.list.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.list.save();');
    this.list.addClose('Cancel');
    this.list.addButton('next', 'Next');
    this.list.addLeftButton('prev', 'Prev');

    //
    // The panel to edit List Section
    //
    this.listsection = new M.panel('List Section', 'ciniki_musicfestivals_main', 'listsection', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.listsection');
    this.listsection.data = null;
    this.listsection.list_id = 0;
    this.listsection.listsection_id = 0;
    this.listsection.nplist = [];
    this.listsection.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.listsection.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.listsection.listsection_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.listsection.remove();'},
            }},
        };
    this.listsection.fieldValue = function(s, i, d) { return this.data[i]; }
    this.listsection.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.listSectionHistory', 'args':{'tnid':M.curTenantID, 'listsection_id':this.listsection_id, 'field':i}};
    }
    this.listsection.open = function(cb, lid, list_id, list) {
        if( lid != null ) { this.listsection_id = lid; }
        if( list_id != null ) { this.list_id = list_id; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.listSectionGet', {'tnid':M.curTenantID, 'listsection_id':this.listsection_id, 'list_id':this.list_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.listsection;
            p.data = rsp.listsection;
            p.refresh();
            p.show(cb);
        });
    }
    this.listsection.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.listsection.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.listsection_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.listSectionUpdate', {'tnid':M.curTenantID, 'listsection_id':this.listsection_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.listSectionAdd', {'tnid':M.curTenantID, 'list_id':this.list_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.listsection.listsection_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.listsection.remove = function() {
        if( confirm('Are you sure you want to remove listsection?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.listSectionDelete', {'tnid':M.curTenantID, 'listsection_id':this.listsection_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.listsection.close();
            });
        }
    }
    this.listsection.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.listsection_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.listsection.save(\'M.ciniki_musicfestivals_main.listsection.open(null,' + this.nplist[this.nplist.indexOf('' + this.listsection_id) + 1] + ');\');';
        }
        return null;
    }
    this.listsection.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.listsection_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.listsection.save(\'M.ciniki_musicfestivals_main.listsection.open(null,' + this.nplist[this.nplist.indexOf('' + this.listsection_id) - 1] + ');\');';
        }
        return null;
    }
    this.listsection.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.listsection.save();');
    this.listsection.addClose('Cancel');
    this.listsection.addButton('next', 'Next');
    this.listsection.addLeftButton('prev', 'Prev');

    //
    // The panel to edit List Entry
    //
    this.listentry = new M.panel('List Entry', 'ciniki_musicfestivals_main', 'listentry', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.listentry');
    this.listentry.data = null;
    this.listentry.listsection_id = 0;
    this.listentry.listentry_id = 0;
    this.listentry.nplist = [];
    this.listentry.sections = {
        'general':{'label':'List Entry', 'fields':{
            'sequence':{'label':'Number', 'type':'text'},
            'award':{'label':'Award', 'type':'text'},
            'amount':{'label':'Amount', 'type':'text'},
            'donor':{'label':'Donor', 'type':'text'},
            'winner':{'label':'Winner', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.listentry.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.listentry.listentry_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.listentry.remove();'},
            }},
        };
    this.listentry.fieldValue = function(s, i, d) { return this.data[i]; }
    this.listentry.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.listEntryHistory', 'args':{'tnid':M.curTenantID, 'listentry_id':this.listentry_id, 'field':i}};
    }
    this.listentry.open = function(cb, lid, sid, list) {
        if( lid != null ) { this.listentry_id = lid; }
        if( sid != null ) { this.listsection_id = sid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.listEntryGet', {'tnid':M.curTenantID, 'listentry_id':this.listentry_id, 'section_id':this.listsection_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.listentry;
            p.data = rsp.listentry;
            p.refresh();
            p.show(cb);
        });
    }
    this.listentry.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.listentry.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.listentry_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.listEntryUpdate', {'tnid':M.curTenantID, 'listentry_id':this.listentry_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.listEntryAdd', {'tnid':M.curTenantID, 'section_id':this.listsection_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.listentry.listentry_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.listentry.remove = function() {
        if( confirm('Are you sure you want to remove listentry?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.listEntryDelete', {'tnid':M.curTenantID, 'listentry_id':this.listentry_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.listentry.close();
            });
        }
    }
    this.listentry.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.listentry_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.listentry.save(\'M.ciniki_musicfestivals_main.listentry.open(null,' + this.nplist[this.nplist.indexOf('' + this.listentry_id) + 1] + ');\');';
        }
        return null;
    }
    this.listentry.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.listentry_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.listentry.save(\'M.ciniki_musicfestivals_main.listentry.open(null,' + this.nplist[this.nplist.indexOf('' + this.listentry_id) - 1] + ');\');';
        }
        return null;
    }
    this.listentry.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.listentry.save();');
    this.listentry.addClose('Cancel');
    this.listentry.addButton('next', 'Next');
    this.listentry.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Certificate
    //
    this.certificate = new M.panel('Certificate', 'ciniki_musicfestivals_main', 'certificate', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.certificate');
    this.certificate.data = null;
    this.certificate.festival_id = 0;
    this.certificate.certificate_id = 0;
    this.certificate.nplist = [];
    this.certificate.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.certificate.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'Certificate', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
// FIXME: Add section support and min score support
//            'section_id':{'label':'Section', 'type':'select', 'options':{}, 'complex_options':{'name':'name', 'value':'id'}},
//            'min_score':{'label':'Minimum Score', 'type':'text', 'size':'small'},
            }},
        'fields':{'label':'Auto Filled Fields', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Field',
            'addFn':'M.ciniki_musicfestivals_main.certificate.save("M.ciniki_musicfestivals_main.certfield.open(\'M.ciniki_musicfestivals_main.certificate.open();\',0,M.ciniki_musicfestivals_main.certificate.certificate_id);");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.certificate.save();'},
            'download':{'label':'Generate Test', 
                'visible':function() {return M.ciniki_musicfestivals_main.certificate.certificate_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.certificate.generateTestOutlines();',
                },
            'download2':{'label':'Generate Test No Outlines', 
                'visible':function() {return M.ciniki_musicfestivals_main.certificate.certificate_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.certificate.generateTest();',
                },
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.certificate.certificate_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.certificate.remove();',
                },
            }},
        };
    this.certificate.fieldValue = function(s, i, d) { return this.data[i]; }
    this.certificate.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.certificateHistory', 'args':{'tnid':M.curTenantID, 'certificate_id':this.certificate_id, 'field':i}};
    }
    this.certificate.cellValue = function(s, i, j, d) {
        if( s == 'fields' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.certificate.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.certificate.save("M.ciniki_musicfestivals_main.certfield.open(\'M.ciniki_musicfestivals_main.certificate.open();\',' + d.id + ',M.ciniki_musicfestivals_main.certificate.certificate_id);");';
    }
    this.certificate.open = function(cb, cid, fid, list) {
        if( cid != null ) { this.certificate_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.certificateGet', {'tnid':M.curTenantID, 'certificate_id':this.certificate_id, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.certificate;
            p.data = rsp.certificate;
//            p.sections.general.fields.section_id.options = rsp.sections;
            p.refresh();
            p.show(cb);
        });
    }
    this.certificate.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.certificate.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.certificate_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.certificateUpdate', {'tnid':M.curTenantID, 'certificate_id':this.certificate_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.certificateAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.certificate.certificate_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.certificate.generateTestOutlines = function() {
        M.api.openFile('ciniki.musicfestivals.certificateGet', {'tnid':M.curTenantID, 'certificate_id':this.certificate_id, 'festival_id':this.festival_id, 'output':'pdf', 'outlines':'yes'});
    }
    this.certificate.generateTest = function() {
        M.api.openFile('ciniki.musicfestivals.certificateGet', {'tnid':M.curTenantID, 'certificate_id':this.certificate_id, 'festival_id':this.festival_id, 'output':'pdf'});
    }
    this.certificate.remove = function() {
        if( confirm('Are you sure you want to remove certificate?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.certificateDelete', {'tnid':M.curTenantID, 'certificate_id':this.certificate_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.certificate.close();
            });
        }
    }
    this.certificate.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.certificate_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.certificate.save(\'M.ciniki_musicfestivals_main.certificate.open(null,' + this.nplist[this.nplist.indexOf('' + this.certificate_id) + 1] + ');\');';
        }
        return null;
    }
    this.certificate.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.certificate_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.certificate.save(\'M.ciniki_musicfestivals_main.certificate.open(null,' + this.nplist[this.nplist.indexOf('' + this.certificate_id) - 1] + ');\');';
        }
        return null;
    }
    this.certificate.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.certificate.save();');
    this.certificate.addClose('Cancel');
    this.certificate.addButton('next', 'Next');
    this.certificate.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Certificate Field
    //
    this.certfield = new M.panel('Certificate Field', 'ciniki_musicfestivals_main', 'certfield', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.certfield');
    this.certfield.data = null;
    this.certfield.field_id = 0;
    this.certfield.certificate_id = 0;
    this.certfield.nplist = [];
    this.certfield.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'field':{'label':'Field', 'type':'select', 'options':{
                'class':'Class',
                'participant':'Participant',
                'adjudicator':'Adjudicator',
                'text':'Text',
                }},
            'xpos':{'label':'X Position', 'required':'yes', 'type':'text'},
            'ypos':{'label':'Y Position', 'required':'yes', 'type':'text'},
            'width':{'label':'Width', 'required':'yes', 'type':'text'},
            'height':{'label':'Height', 'required':'yes', 'type':'text'},
            'font':{'label':'Font', 'type':'select', 'options':{
                'times':'Times',
                'helvetica':'Helvetica',
                'vidaloka':'Vidaloka',
                'scriptina':'Scriptina',
                'allison':'Allison',
                'greatvibes':'Great Vibes',
                }},
            'size':{'label':'Size', 'type':'text'},
            'style':{'label':'Style', 'type':'select', 'options':{
                '':'Normal',
                'B':'Bold',
                'I':'Italic',
                'BI':'Bold Italic',
                }},
            'align':{'label':'Align', 'type':'select', 'options':{
                'L':'Left',
                'C':'Center',
                'R':'Right',
                }},
            'valign':{'label':'Vertial', 'type':'select', 'options':{
                'T':'Top',
                'M':'Middle',
                'B':'Bottom',
                }},
//            'color':{'label':'Color', 'type':'text'},
//            'bgcolor':{'label':'Background Color', 'type':'text'},
            'text':{'label':'Text', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.certfield.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.certfield.field_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.certfield.remove();'},
            }},
        };
    this.certfield.fieldValue = function(s, i, d) { return this.data[i]; }
    this.certfield.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.certfieldHistory', 'args':{'tnid':M.curTenantID, 'field_id':this.field_id, 'field':i}};
    }
    this.certfield.open = function(cb, fid, cid, list) {
        if( fid != null ) { this.field_id = fid; }
        if( cid != null ) { this.certificate_id = cid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.certfieldGet', {'tnid':M.curTenantID, 'field_id':this.field_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.certfield;
            p.data = rsp.field;
            p.refresh();
            p.show(cb);
        });
    }
    this.certfield.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.certfield.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.field_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.certfieldUpdate', {'tnid':M.curTenantID, 'field_id':this.field_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.certfieldAdd', {'tnid':M.curTenantID, 'certificate_id':this.certificate_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.certfield.field_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.certfield.remove = function() {
        if( confirm('Are you sure you want to remove certfield?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.certfieldDelete', {'tnid':M.curTenantID, 'field_id':this.field_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.certfield.close();
            });
        }
    }
    this.certfield.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.field_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.certfield.save(\'M.ciniki_musicfestivals_main.certfield.open(null,' + this.nplist[this.nplist.indexOf('' + this.field_id) + 1] + ');\');';
        }
        return null;
    }
    this.certfield.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.field_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.certfield.save(\'M.ciniki_musicfestivals_main.certfield.open(null,' + this.nplist[this.nplist.indexOf('' + this.field_id) - 1] + ');\');';
        }
        return null;
    }
    this.certfield.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.certfield.save();');
    this.certfield.addClose('Cancel');
    this.certfield.addButton('next', 'Next');
    this.certfield.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Trophy
    //
    this.trophy = new M.panel('Trophy', 'ciniki_musicfestivals_main', 'trophy', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.trophy');
    this.trophy.data = null;
    this.trophy.trophy_id = 0;
    this.trophy.nplist = [];
    this.trophy.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.trophy.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'category':{'label':'Category', 'type':'text'},
            'donated_by':{'label':'Donated By', 'type':'text'},
            'first_presented':{'label':'First Presented', 'type':'text'},
            'criteria':{'label':'Criteria', 'type':'text'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'winners':{'label':'Winners', 'type':'simplegrid', 'num_cols':2, 
            'addTxt':'Add Winner',
            'addFn':'M.ciniki_musicfestivals_main.trophy.save("M.ciniki_musicfestivals_main.trophywinner.open(\'M.ciniki_musicfestivals_main.trophy.open();\',0,M.ciniki_musicfestivals_main.trophy.trophy_id);");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.trophy.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.trophy.trophy_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.trophy.remove();'},
            }},
        };
    this.trophy.fieldValue = function(s, i, d) { return this.data[i]; }
    this.trophy.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.trophyHistory', 'args':{'tnid':M.curTenantID, 'trophy_id':this.trophy_id, 'field':i}};
    }
    this.trophy.cellValue = function(s, i, j, d) {
        if( s == 'winners' ) {
            switch(j) {
                case 0: return d.year;
                case 1: return d.name;
            }
        }
    }
    this.trophy.rowFn = function(s, i, d) {
        if( s == 'winners' ) {
            return 'M.ciniki_musicfestivals_main.trophy.save("M.ciniki_musicfestivals_main.trophywinner.open(\'M.ciniki_musicfestivals_main.trophy.open();\',' + d.id + ',M.ciniki_musicfestivals_main.trophy.trophy_id);");';
        }
    }
    this.trophy.open = function(cb, tid, list) {
        if( tid != null ) { this.trophy_id = tid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.trophyGet', {'tnid':M.curTenantID, 'trophy_id':this.trophy_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.trophy;
            p.data = rsp.trophy;
            p.refresh();
            p.show(cb);
        });
    }
    this.trophy.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.trophy.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.trophy_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.trophyUpdate', {'tnid':M.curTenantID, 'trophy_id':this.trophy_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.trophyAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.trophy.trophy_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.trophy.remove = function() {
        if( confirm('Are you sure you want to remove trophy?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.trophyDelete', {'tnid':M.curTenantID, 'trophy_id':this.trophy_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.trophy.close();
            });
        }
    }
    this.trophy.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.trophy_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.trophy.save(\'M.ciniki_musicfestivals_main.trophy.open(null,' + this.nplist[this.nplist.indexOf('' + this.trophy_id) + 1] + ');\');';
        }
        return null;
    }
    this.trophy.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.trophy_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.trophy.save(\'M.ciniki_musicfestivals_main.trophy.open(null,' + this.nplist[this.nplist.indexOf('' + this.trophy_id) - 1] + ');\');';
        }
        return null;
    }
    this.trophy.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.trophy.save();');
    this.trophy.addClose('Cancel');
    this.trophy.addButton('next', 'Next');
    this.trophy.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Trophy Winner
    //
    this.trophywinner = new M.panel('Trophy Winner', 'ciniki_musicfestivals_main', 'trophywinner', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.trophywinner');
    this.trophywinner.data = null;
    this.trophywinner.trophy_id = 0;
    this.trophywinner.winner_id = 0;
    this.trophywinner.nplist = [];
    this.trophywinner.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'year':{'label':'Year', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.trophywinner.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.trophywinner.winner_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.trophywinner.remove();'},
            }},
        };
    this.trophywinner.fieldValue = function(s, i, d) { return this.data[i]; }
    this.trophywinner.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.trophyWinnerHistory', 'args':{'tnid':M.curTenantID, 'winner_id':this.winner_id, 'field':i}};
    }
    this.trophywinner.open = function(cb, wid, tid, list) {
        if( wid != null ) { this.winner_id = wid; }
        if( tid != null ) { this.trophy_id = tid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.trophyWinnerGet', {'tnid':M.curTenantID, 'winner_id':this.winner_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.trophywinner;
            p.data = rsp.winner;
            p.refresh();
            p.show(cb);
        });
    }
    this.trophywinner.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.trophywinner.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.winner_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.trophyWinnerUpdate', {'tnid':M.curTenantID, 'winner_id':this.winner_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.musicfestivals.trophyWinnerAdd', {'tnid':M.curTenantID, 'trophy_id':this.trophy_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.trophywinner.winner_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.trophywinner.remove = function() {
        if( confirm('Are you sure you want to remove trophywinner?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.trophyWinnerDelete', {'tnid':M.curTenantID, 'winner_id':this.winner_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.trophywinner.close();
            });
        }
    }
    this.trophywinner.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.winner_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.trophywinner.save(\'M.ciniki_musicfestivals_main.trophywinner.open(null,' + this.nplist[this.nplist.indexOf('' + this.winner_id) + 1] + ');\');';
        }
        return null;
    }
    this.trophywinner.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.winner_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.trophywinner.save(\'M.ciniki_musicfestivals_main.trophywinner.open(null,' + this.nplist[this.nplist.indexOf('' + this.winner_id) - 1] + ');\');';
        }
        return null;
    }
    this.trophywinner.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.trophywinner.save();');
    this.trophywinner.addClose('Cancel');
    this.trophywinner.addButton('next', 'Next');
    this.trophywinner.addLeftButton('prev', 'Prev');



    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
       
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_musicfestivals_main', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.item_object != null && args.item_object == 'ciniki.musicfestivals.registration' && args.item_object_id != null ) {
            this.registration.open(cb, args.item_object_id, 0, 0, 0, null, args.source);
        } else if( args.registration_id != null && args.registration_id != '' ) {
            this.registration.open(cb, args.registration_id, 0, 0, 0, null, '');
        } else if( args.festival_id != null && args.festival_id != '' ) {
            this.festival.list_id = 0;
            this.festival.open(cb, args.festival_id, null);
        } else {
            this.festival.list_id = 0;
            this.menu.sections._tabs.selected = 'festivals';
            this.menu.open(cb);
        }
    }
}
