//
// This is the main app for the musicfestivals module
//
function ciniki_musicfestivals_main() {
    //
    // The panel to list the festival
    //
    this.menu = new M.panel('festival', 'ciniki_musicfestivals_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search festival',
//            'noData':'No festival found',
//            },
        'festivals':{'label':'Festival', 'type':'simplegrid', 'num_cols':1,
            'noData':'No festival',
            'addTxt':'Add Festival',
            'addFn':'M.ciniki_musicfestivals_main.edit.open(\'M.ciniki_musicfestivals_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.festivalSearch', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'25'}, function(rsp) {
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
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'festivals' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'festivals' ) {
            return 'M.ciniki_musicfestivals_main.festival.open(\'M.ciniki_musicfestivals_main.menu.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.musicfestivals.festivalList', {'business_id':M.curBusinessID}, function(rsp) {
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
    this.menu.addClose('Back');

    //
    // The panel to display Festival
    //
    this.festival = new M.panel('Festival', 'ciniki_musicfestivals_main', 'festival', 'mc', 'large narrowaside', 'sectioned', 'ciniki.musicfestivals.main.festival');
    this.festival.data = null;
    this.festival.festival_id = 0;
    this.festival.schedulesection_id = 0;
    this.festival.scheduledivision_id = 0;
    this.festival.nplists = {};
    this.festival.nplist = [];
    this.festival.sections = {
        'details':{'label':'Details', 'aside':'yes', 'list':{
            'name':{'label':'Name'},
            'start_date':{'label':'Start'},
            'end_date':{'label':'End'},
            'num_registrations':{'label':'# Reg'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'sections', 'tabs':{
            'sections':{'label':'Syllabus', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'sections\');'},
            'registrations':{'label':'Registrations', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'registrations\');'},
            'schedule':{'label':'Schedule', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'schedule\');'},
            'adjudicators':{'label':'Adjudicators', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'adjudicators\');'},
            'files':{'label':'Files', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'files\');'},
            'sponsors':{'label':'Sponsors', 
                'visible':function() { 
                    return (M.curBusiness.modules['ciniki.sponsors'] != null && (M.curBusiness.modules['ciniki.sponsors'].flags&0x02) == 0x02) ? 'yes':'no'; 
                    },
                'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'sponsors\');'},
            }},
        '_stabs':{'label':'', 'type':'paneltabs', 'selected':'sections', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sections' ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'sections\');'},
                'categories':{'label':'Categories', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'categories\');'},
                'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'classes\');'},
            }},
        'program_buttons':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' ? 'yes' : 'no'; },
            'buttons':{
                'pdf':{'label':'Download Program PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadProgramPDF();'},
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
                'copy':{'label':'Copy previous syllabus', 'fn':'M.ciniki_musicfestivals_main.festival.syllabusCopy("previous");'},
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
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'registrations' ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("sections");'},
                'teachers':{'label':'Teachers', 'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("teachers");'},
            }}, 
        'registration_sections':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'registrations' && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'sections' ? 'yes' : 'no'; },
            },
        'registration_teachers':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'registrations' && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'teachers' ? 'yes' : 'no'; },
            },
        'registration_buttons':{'label':'', 'aside':'yes', 
            'visible':function() {return M.ciniki_musicfestivals_main.festival.sections._tabs.selected=='registrations'?'yes':'no';},
            'buttons':{
                'excel':{'label':'Export to Excel', 'fn':'M.ciniki_musicfestivals_main.festival.downloadExcel(M.ciniki_musicfestivals_main.festival.festival_id);'},
                'pdf':{'label':'Registrations PDF ', 'fn':'M.ciniki_musicfestivals_main.festival.downloadPDF(M.ciniki_musicfestivals_main.festival.festival_id);'},
            }},
        'registration_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5,
            'visible':function() {return M.ciniki_musicfestivals_main.festival.sections._tabs.selected=='registrations'?'yes':'no';},
            'hint':'Search',
            'noData':'No registrations found',
            'headerValues':['Class', 'Registrant', 'Teacher', 'Fee', 'Status'],
            'cellClasses':['', '', '', '', ''],
            },
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'registrations' ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant', 'Teacher', 'Fee', 'Status', ''],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'altnumber', 'altnumber', ''],
            'cellClasses':['', '', '', '', '', 'alignright'],
            'addTxt':'Add Registration',
            'addFn':'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'registrations_emailbutton':{'label':'', 
            'visible':function() {return M.ciniki_musicfestivals_main.festival.sections._tabs.selected=='registrations' && M.ciniki_musicfestivals_main.festival.teacher_customer_id > 0 ?'yes':'no';},
            'buttons':{
                'email':{'label':'Email List', 'fn':'M.ciniki_musicfestivals_main.festival.emailTeacherRegistrations();'},
            }},
        'schedule_sections':{'label':'Schedules', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' ? 'yes' : 'no'; },
            'cellClasses':['', 'multiline alignright'],
            'addTxt':'Add Schedule',
            'addFn':'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'schedule_divisions':{'label':'Divisions', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_musicfestivals_main.festival.schedulesection_id>0? 'yes' : 'no'; },
            'cellClasses':['multiline', 'multiline alignright'],
            'addTxt':'Add Division',
            'addFn':'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
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
            'cellClasses':['label', 'multiline'],
            'addTxt':'Add Time Slot',
            'addFn':'M.ciniki_musicfestivals_main.scheduletimeslot.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'adjudicators':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'adjudicators' ? 'yes' : 'no'; },
            'addTxt':'Add Adjudicator',
            'addFn':'M.ciniki_musicfestivals_main.adjudicator.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
            },
        'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'sponsors' ? 'yes' : 'no'; },
            'addTxt':'Manage Sponsors',
            'addFn':'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_musicfestivals_main.festival.open();\',\'mc\',{\'object\':\'ciniki.musicfestivals.festival\',\'object_id\':M.ciniki_musicfestivals_main.festival.festival_id});',
        },
        'files':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._tabs.selected == 'files' ? 'yes' : 'no'; },
            'addTxt':'Add File',
            'addFn':'M.ciniki_musicfestivals_main.addfile.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id);',
            },
    }
    this.festival.downloadProgramPDF = function() {
        var args = {'business_id':M.curBusinessID, 'festival_id':this.festival_id};
        M.api.openPDF('ciniki.musicfestivals.programPDF',args);
    }
    this.festival.downloadSchedulePDF = function() {
        var args = {'business_id':M.curBusinessID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            'names':this.formValue('names'),
            };
        M.api.openPDF('ciniki.musicfestivals.schedulePDF',args);
    }
    this.festival.downloadCertificatesPDF = function() {
        var args = {'business_id':M.curBusinessID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            };
        M.api.openFile('ciniki.musicfestivals.certificatesPDF',args);
    }
    this.festival.downloadCommentsPDF = function() {
        var args = {'business_id':M.curBusinessID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            };
        M.api.openPDF('ciniki.musicfestivals.commentsPDF',args);
    }
    this.festival.listLabel = function(s, i, d) { return d.label; }
    this.festival.listValue = function(s, i, d) { return this.data[i]; }
    this.festival.fieldValue = function(s, i, d) { 
        if( this.data[i] == null ) { return ''; }
        return this.data[i]; 
    }
    this.festival.liveSearchCb = function(s, i, v) {
        if( s == 'registration_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.registrationSearch', {'business_id':M.curBusinessID, 'start_needle':v, 'festival_id':this.festival_id, 'limit':'50'}, function(rsp) {
                    M.ciniki_musicfestivals_main.festival.liveSearchShow('registration_search',null,M.gE(M.ciniki_musicfestivals_main.festival.panelUID + '_' + s), rsp.registrations);
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
    }
    this.festival.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'registration_search' ) { 
            return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.registrations);';
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
                case 3: return d.fee;
                case 4: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.teacher_name;
                case 3: return '$' + d.fee;
                case 4: return d.status_text;
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
                case 1: return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);">Edit</span>';
            }
        }
        if( s == 'schedule_divisions' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.name + ' <span class="subdue">' + d.division_date_text + '</span><span class="subtext">' + d.address + '</span>';
                case 1: return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);">Edit</span>';
            }
        }
        if( s == 'schedule_timeslots' ) {
            switch(j) {
                case 0: return d.slot_time_text;
                case 1: return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.description.replace(/\n/g, '<br/>') + '</span>';
            }
        }
        if( s == 'adjudicators' ) {
            return d.name;
        }
        if( s == 'files' ) {
            return d.name;
        }
        if( s == 'sponsors' && j == 0 ) {
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
        return '';
    }
    this.festival.rowFn = function(s, i, d) {
        switch(s) {
            case 'sections': return 'M.ciniki_musicfestivals_main.section.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.sections);';
            case 'categories': return 'M.ciniki_musicfestivals_main.category.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.categories);';
            case 'classes': return 'M.ciniki_musicfestivals_main.class.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.classes);';
            case 'registrations': return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.registrations);';
            case 'registration_sections': return 'M.ciniki_musicfestivals_main.festival.openSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'registration_teachers': return 'M.ciniki_musicfestivals_main.festival.openTeacher(\'' + d.id + '\',"' + M.eU(d.display_name) + '");';
            case 'schedule_sections': return 'M.ciniki_musicfestivals_main.festival.openScheduleSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_divisions': return 'M.ciniki_musicfestivals_main.festival.openScheduleDivision(\'' + d.id + '\',"' + M.eU(d.name) + '");';
//            case 'schedule_sections': return 'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
//            case 'schedule_divisions': return 'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'schedule_timeslots': return 'M.ciniki_musicfestivals_main.scheduletimeslot.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'adjudicators': return 'M.ciniki_musicfestivals_main.adjudicator.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.adjudicators);';
            case 'files': return 'M.ciniki_musicfestivals_main.editfile.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'sponsors': return 'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_musicfestivals_main.festival.open();\',\'mc\',{\'ref_id\':\'' + d.sponsor.ref_id + '\'});';
        }
    }
    this.festival.switchTab = function(tab, stab) {
        if( tab != null ) { this.sections._tabs.selected = tab; }
        if( stab != null ) { this.sections._stabs.selected = stab; }
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
            this.sections.registrations.label = 'Registrations - ' + decodeURIComponent(n);
        } else {
            this.sections.registrations.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openTeacher = function(id,n) {
        this.teacher_customer_id = id;
        this.section_id = 0;
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
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
    this.festival.downloadExcel = function(fid) {
        M.api.openFile('ciniki.musicfestivals.registrationsExcel', {'business_id':M.curBusinessID, 'festival_id':fid});
    }
    this.festival.downloadPDF = function(fid) {
        M.api.openFile('ciniki.musicfestivals.registrationsPDF', {'business_id':M.curBusinessID, 'festival_id':fid});
    }
    this.festival.open = function(cb, fid, list) {
        if( fid != null ) { this.festival_id = fid; }
        var args = {'business_id':M.curBusinessID, 'festival_id':this.festival_id};
        this.size = 'large narrowaside';
        if( this.sections._tabs.selected == 'sections' ) {
            args['sections'] = 'yes';
            args['categories'] = 'yes';
            args['classes'] = 'yes';
        } else if( this.sections._tabs.selected == 'registrations' ) {
            args['sections'] = 'yes';
            args['registrations'] = 'yes';
        } else if( this.sections._tabs.selected == 'schedule' ) {
            this.size = 'medium mediumaside';
            args['schedule'] = 'yes';
            args['ssection_id'] = this.schedulesection_id;
            args['sdivision_id'] = this.scheduledivision_id;
        } else if( this.sections._tabs.selected == 'adjudicators' ) {
            args['adjudicators'] = 'yes';
        } else if( this.sections._tabs.selected == 'files' ) {
            args['files'] = 'yes';
        } else if( this.sections._tabs.selected == 'sponsors' ) {
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
            if( rsp.festival.sections != null ) {
                p.data.registration_sections = [];
                for(var i in rsp.festival.sections) {
                    p.data.registration_sections.push({'id':rsp.festival.sections[i].id, 'name':rsp.festival.sections[i].name});
                }
//                p.data.registration_sections = rsp.festival.sections;
                p.data.registration_sections.push({'id':0, 'name':'All'});
            }
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.festival.syllabusCopy = function(old_fid) {
        M.api.getJSONCb('ciniki.musicfestivals.festivalSyllabusCopy', {'business_id':M.curBusinessID, 'festival_id':this.festival_id, 'old_festival_id':old_fid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.festival.open();
        });
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
                'addDropImage':function(fid) {
                    M.ciniki_musicfestivals_main.edit.setFieldValue(fid,0);
                    return true;
                 },
             },
        }}, */
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'start_date':{'label':'Start', 'type':'date'},
            'end_date':{'label':'End', 'type':'date'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '30':'Published', '60':'Archived'}},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'website', 'tabs':{
            'website':{'label':'Website', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'website\');'},
            'documents':{'label':'Documents', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'documents\');'},
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
        return {'method':'ciniki.musicfestivals.festivalHistory', 'args':{'business_id':M.curBusinessID, 'festival_id':this.festival_id, 'field':i}};
    }
    this.edit.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.showHideSection('_primary_image_id');
        this.showHideSection('_description');
        this.showHideSection('_document_logo_id');
        this.showHideSection('_document_header_msg');
        this.showHideSection('_document_footer_msg');
        this.refreshSection('_tabs');
    }
    this.edit.updateNames = function() {
        M.api.getJSONCb('ciniki.musicfestivals.registrationNamesUpdate', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert("Done");
        });
    }
    this.edit.open = function(cb, fid, list) {
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.festivalGet', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.edit;
            p.data = rsp.festival;
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.edit.close();'; }
        if( this.festival_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.festivalUpdate', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.festivalAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove festival?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.festivalDelete', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.edit.close();
            });
        }
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
                'addDropImage':function(fid) {
                    M.ciniki_musicfestivals_main.section.setFieldValue(fid,0);
                    return true;
                 },
             },
        }},
        'general':{'label':'Section', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text', 'required':'yes'},
            'sequence':{'label':'Order', 'type':'text', 'required':'yes', 'size':'small'},
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
        return {'method':'ciniki.musicfestivals.sectionHistory', 'args':{'business_id':M.curBusinessID, 'section_id':this.section_id, 'field':i}};
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
        M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'business_id':M.curBusinessID, 'festival_id':this.festival_id, 'section_id':this.section_id});
    }
    this.section.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.section_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.sectionGet', {'business_id':M.curBusinessID, 'section_id':this.section_id, 'festival_id':this.festival_id, 'categories':'yes'}, function(rsp) {
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
                M.api.postJSONCb('ciniki.musicfestivals.sectionUpdate', {'business_id':M.curBusinessID, 'section_id':this.section_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.sectionAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove section?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.sectionDelete', {'business_id':M.curBusinessID, 'section_id':this.section_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.section.close();
            });
        }
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
                'addDropImage':function(fid) {
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
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.category.sections._tabs.selected == 'classes' ? 'yes' : 'hidden'; },
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
        return {'method':'ciniki.musicfestivals.categoryHistory', 'args':{'business_id':M.curBusinessID, 'category_id':this.category_id, 'field':i}};
    }
    this.category.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.code + ' - ' + d.name;
            case 1: return d.fee;
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
        M.api.getJSONCb('ciniki.musicfestivals.categoryGet', {'business_id':M.curBusinessID, 
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
                M.api.postJSONCb('ciniki.musicfestivals.categoryUpdate', {'business_id':M.curBusinessID, 'category_id':this.category_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.categoryAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove category?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.categoryDelete', {'business_id':M.curBusinessID, 'category_id':this.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.category.close();
            });
        }
    }
    this.category.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.category.save(\'M.ciniki_musicfestivals_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) + 1] + ');\');';
        }
        return null;
    }
    this.category.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.category.save(\'M.ciniki_musicfestivals_main.category_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) - 1] + ');\');';
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
            'fee':{'label':'Fee', 'type':'text', 'size':'small'},
            }},
        'registration':{'label':'Registration Options', 'aside':'yes', 'fields':{
            'flags1':{'label':'Online Registrations', 'type':'flagtoggle', 'default':'on', 'bit':0x01, 'field':'flags'},
            'flags2':{'label':'Multiple/Registrant', 'type':'flagtoggle', 'default':'on', 'bit':0x02, 'field':'flags'},
            }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3, 
            'headerValues':['Competitor', 'Teacher', 'Status'],
            'addTxt':'Add Registration',
            'addFn':'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.class.class_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
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
        return {'method':'ciniki.musicfestivals.classHistory', 'args':{'business_id':M.curBusinessID, 'class_id':this.class_id, 'field':i}};
    }
    this.class.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name;
            case 1: return d.teacher_name;
            case 2: return d.status_text;
        }
    }
    this.class.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.class.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.class.festival_id, null);';
    }
    this.class.open = function(cb, iid, cid, fid, list) {
        if( iid != null ) { this.class_id = iid; }
        if( cid != null ) { this.category_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.classGet', {'business_id':M.curBusinessID, 'class_id':this.class_id, 'festival_id':this.festival_id, 'category_id':this.category_id, 
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
                M.api.postJSONCb('ciniki.musicfestivals.classUpdate', {'business_id':M.curBusinessID, 'class_id':this.class_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.classAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove class?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.classDelete', {'business_id':M.curBusinessID, 'class_id':this.class_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.class.close();
            });
        }
    }
    this.class.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.class_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.class.save(\'M.ciniki_musicfestivals_main.class.open(null,' + this.nplist[this.nplist.indexOf('' + this.class_id) + 1] + ');\');';
        }
        return null;
    }
    this.class.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.class_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.class.save(\'M.ciniki_musicfestivals_main.class_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.class_id) - 1] + ');\');';
        }
        return null;
    }
    this.class.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.class.save();');
    this.class.addClose('Cancel');
    this.class.addButton('next', 'Next');
    this.class.addLeftButton('prev', 'Prev');

    //
    // Registrations
    //
    this.registration = new M.panel('Registration', 'ciniki_musicfestivals_main', 'registration', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.registration');
    this.registration.data = null;
    this.registration.festival_id = 0;
    this.registration.teacher_customer_id = 0;
    this.registration.competitor1_id = 0;
    this.registration.competitor2_id = 0;
    this.registration.competitor3_id = 0;
    this.registration.competitor4_id = 0;
    this.registration.competitor5_id = 0;
    this.registration.registration_id = 0;
    this.registration.nplist = [];
    this.registration.sections = {
        '_tabs':{'label':'', 'type':'paneltabs', 'field_id':'rtype', 'selected':'30', 'tabs':{
            '30':{'label':'Individual', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("30");'},
            '50':{'label':'Duet', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("50");'},
            '60':{'label':'Trio', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("60");'},
            '90':{'label':'Ensemble', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("90");'},
            }},
        '_display_name':{'label':'Duet/Trio/Ensemble Name', 'aside':'yes',
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
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
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>30?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 2);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 2);',
            },
        'competitor3_details':{'label':'Competitor 3', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>50?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 3);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 3);',
            },
        'competitor4_details':{'label':'Competitor 4', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 4);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 4);',
            },
        'competitor5_details':{'label':'Competitor 5', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 5);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 5);',
            },
/*        '_competitor1':{'label':'', 'aside':'yes', 'fields':{
            'competitor1_id':{'label':'Competitor 1', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                'editFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(1);',
                },
            }},
        '_competitor2':{'label':'', 'aside':'yes',
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>30?'yes':'hidden');},
            'fields':{
                'competitor2_id':{'label':'Competitor 2', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                    'editFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(2);',
                    },
            }},
        '_competitor3':{'label':'', 'aside':'yes',
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>50?'yes':'hidden');},
            'fields':{
                'competitor3_id':{'label':'Competitor 3', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                    'editFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(3);',
                    },
            }},
        '_competitor4':{'label':'', 'aside':'yes',
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'fields':{
                'competitor4_id':{'label':'Competitor 4', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                    'editFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(4);',
                    },
            }},
        '_competitor5':{'label':'', 'aside':'yes',
            'visible':function(){return (parseInt(M.ciniki_musicfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'fields':{
                'competitor5_id':{'label':'Competitor 5', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                    'editFn':'M.ciniki_musicfestivals_main.registration.addCompetitor(5);',
                    },
            }}, */
        'teacher_details':{'label':'Teacher', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.registration.updateTeacher();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.registration.updateTeacher\',\'customer_id\':M.ciniki_musicfestivals_main.registration.teacher_customer_id});',
            'changeTxt':'Change',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.registration.updateTeacher();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.registration.updateTeacher\',\'customer_id\':0});',
            },
        '_class':{'label':'', 'fields':{
            'status':{'label':'Status', 'required':'yes', 'type':'toggle', 'toggles':{'10':'Applied', '50':'Paid'}},
            'payment_type':{'label':'Payment', 'type':'toggle', 'toggles':{'20':'Square', '50':'Visa', '55':'Mastercard', '100':'Cash', '105':'Cheque', '110':'Email', '120':'Other'}},
            'class_id':{'label':'Class', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_musicfestivals_main.registration.setFee',
                },
            'title':{'label':'Title', 'type':'text'},
            'perf_time':{'label':'PerfTime', 'type':'text', 'size':'small'},
            'fee':{'label':'Fee', 'type':'text', 'size':'small'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
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
    this.registration.fieldValue = function(s, i, d) { return this.data[i]; }
    this.registration.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.registrationHistory', 'args':{'business_id':M.curBusinessID, 'registration_id':this.registration_id, 'field':i}};
    }
    this.registration.cellValue = function(s, i, j, d) {
        if( s == 'competitor1_details' || s == 'competitor2_details' || s == 'competitor3_details' || s == 'competitor4_details' || s == 'competitor5_details' ) {
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
    }
    this.registration.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.showHideSection('_display_name');
        this.showHideSection('competitor2_details');
        this.showHideSection('competitor3_details');
        this.showHideSection('competitor4_details');
        this.showHideSection('competitor5_details');
    }
    this.registration.setFee = function(s, i) {
//        if( this.registration_id == 0 ) {
            var cid = this.formValue('class_id');
            for(var i in this.classes) {
                if( this.classes[i].id == cid ) {
                    this.setFieldValue('fee', this.classes[i].fee);
                }
            }
//        }
    }
    this.registration.addCompetitor = function(cid,c) {
        M.ciniki_musicfestivals_main.competitor.open('M.ciniki_musicfestivals_main.registration.updateCompetitor(' + c + ');',cid,this.festival_id,null);
    }
    this.registration.updateCompetitor = function(c) {
        var p = M.ciniki_musicfestivals_main.competitor;
        this['competitor' + c + '_id'] = p.competitor_id;
        M.api.getJSONCb('ciniki.musicfestivals.competitorGet', {'business_id':M.curBusinessID, 'competitor_id':this['competitor'+c+'_id']}, function(rsp) {
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
        });
    }
    this.registration.updateTeacher = function(cid) {
        if( cid != null ) { 
            this.teacher_customer_id = cid;
            if( this.teacher_customer_id > 0 ) {
                M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID, 'customer_id':this.teacher_customer_id}, function(rsp) {
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
        M.api.openFile('ciniki.musicfestivals.registrationCertificatesPDF', {'business_id':M.curBusinessID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
    this.registration.printComments = function() {
        M.api.openFile('ciniki.musicfestivals.registrationCommentsPDF', {'business_id':M.curBusinessID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
    this.registration.open = function(cb, rid, tid, cid, fid, list) {
        if( rid != null ) { this.registration_id = rid; }
        if( tid != null ) { this.teacher_customer_id = tid; }
        if( fid != null ) { this.festival_id = fid; }
        if( cid != null ) { this.class_id = cid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.registrationGet', {'business_id':M.curBusinessID, 'registration_id':this.registration_id, 
            'teacher_customer_id':this.teacher_customer_id, 'festival_id':this.festival_id, 'class_id':this.class_id, 
            }, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.registration;
            p.data = rsp.registration;
            p.classes = rsp.classes;
            p.sections._tabs.selected = rsp.registration.rtype;
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
            for(var i = 1; i<= 5; i++) {
                p['competitor' + i + '_id'] = parseInt(rsp.registration['competitor' + i + '_id']);
                if( p['competitor' + i + '_id'] == 0 ) {
                    p.sections['competitor' + i + '_details'].addTxt = '';
                    p.sections['competitor' + i + '_details'].changeTxt = 'Add';
                } else {
                    p.sections['competitor' + i + '_details'].addTxt = 'Edit';
                    p.sections['competitor' + i + '_details'].changeTxt = 'Change';
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.registration.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.registration.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.formValue('class_id') == 0 ) {
            alert("You must select a class.");
            return false;
        }
        if( this.competitor1_id == 0 ) {
            alert("You must have a competitor.");
            return false;
        }
        if( this.registration_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.teacher_customer_id != this.data.teacher_customer_id ) { c += '&teacher_customer_id=' + this.teacher_customer_id; }
            if( this.competitor1_id != this.data.competitor1_id ) { c += '&competitor1_id=' + this.competitor1_id; }
            if( this.competitor2_id != this.data.competitor2_id ) { c += '&competitor2_id=' + this.competitor2_id; }
            if( this.competitor3_id != this.data.competitor3_id ) { c += '&competitor3_id=' + this.competitor3_id; }
            if( this.competitor4_id != this.data.competitor4_id ) { c += '&competitor4_id=' + this.competitor4_id; }
            if( this.competitor5_id != this.data.competitor5_id ) { c += '&competitor5_id=' + this.competitor5_id; }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.registrationUpdate', {'business_id':M.curBusinessID, 'registration_id':this.registration_id, 'festival_id':this.festival_id}, c, function(rsp) {
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
            c += '&competitor5_id=' + this.competitor5_id;
            M.api.postJSONCb('ciniki.musicfestivals.registrationAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove registration?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.registrationDelete', {'business_id':M.curBusinessID, 'registration_id':this.registration_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.registration.close();
            });
        }
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
            'parent':{'label':'Parent', 'type':'text'},
            }},
        '_other':{'label':'', 'aside':'yes', 'fields':{
            'age':{'label':'Age', 'type':'text'},
            'study_level':{'label':'Study/Level', 'type':'text'},
            'instrument':{'label':'Instrument', 'type':'text'},
            }},
        '_notes':{'label':'Notes', 'aside':'yes', 'fields':{
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
        return {'method':'ciniki.musicfestivals.competitorHistory', 'args':{'business_id':M.curBusinessID, 'competitor_id':this.competitor_id, 'field':i}};
    }
    this.competitor.liveSearchCb = function(s, i, value) {
        if( i == 'name' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.competitorSearch', 
                {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25}, function(rsp) { 
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
        M.api.getJSONCb('ciniki.musicfestivals.competitorGet', {'business_id':M.curBusinessID, 'festival_id':this.festival_id, 'competitor_id':this.competitor_id}, function(rsp) {
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
                M.api.postJSONCb('ciniki.musicfestivals.competitorUpdate', {'business_id':M.curBusinessID, 'festival_id':this.festival_id, 'competitor_id':this.competitor_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.competitorAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove competitor?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.competitorDelete', {'business_id':M.curBusinessID, 'competitor_id':this.competitor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.competitor.close();
            });
        }
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
        return {'method':'ciniki.musicfestivals.scheduleSectionHistory', 'args':{'business_id':M.curBusinessID, 'schedulesection_id':this.schedulesection_id, 'field':i}};
    }
    this.schedulesection.downloadPDF = function(f,i,n) {
        M.api.openFile('ciniki.musicfestivals.schedulePDF',{'business_id':M.curBusinessID, 'festival_id':f, 'schedulesection_id':i, 'names':n});
    }
    this.schedulesection.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.schedulesection_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleSectionGet', 
            {'business_id':M.curBusinessID, 'schedulesection_id':this.schedulesection_id, 'festival_id':this.festival_id}, function(rsp) {
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
                    {'business_id':M.curBusinessID, 'schedulesection_id':this.schedulesection_id, 'festival_id':this.festival_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.scheduleSectionAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove scheduleSection?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleSectionDelete', {'business_id':M.curBusinessID, 'schedulesection_id':this.schedulesection_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.schedulesection.close();
            });
        }
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
        return {'method':'ciniki.musicfestivals.scheduleDivisionHistory', 'args':{'business_id':M.curBusinessID, 'scheduledivision_id':this.scheduledivision_id, 'field':i}};
    }
    this.scheduledivision.open = function(cb, sid, ssid, fid, list) {
        if( sid != null ) { this.scheduledivision_id = sid; }
        if( ssid != null ) { this.ssection_id = ssid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleDivisionGet', 
            {'business_id':M.curBusinessID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id, 'ssection_id':this.ssection_id}, function(rsp) {
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
                    {'business_id':M.curBusinessID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.scheduleDivisionAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove scheduleDivision?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleDivisionDelete', {'business_id':M.curBusinessID, 'scheduledivision_id':this.scheduledivision_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduledivision.close();
            });
        }
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
        return {'method':'ciniki.musicfestivals.scheduleTimeslotHistory', 'args':{'business_id':M.curBusinessID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'field':i}};
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
            {'business_id':M.curBusinessID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id, 'sdivision_id':this.sdivision_id}, function(rsp) {
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
                    {'business_id':M.curBusinessID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.scheduleTimeslotAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove scheduleTimeslot?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleTimeslotDelete', {'business_id':M.curBusinessID, 'scheduletimeslot_id':this.scheduletimeslot_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduletimeslot.close();
            });
        }
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
    // Adjudicators
    //
    this.adjudicator = new M.panel('Adjudicator', 'ciniki_musicfestivals_main', 'adjudicator', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.adjudicator');
    this.adjudicator.data = null;
    this.adjudicator.festival_id = 0;
    this.adjudicator.adjudicator_id = 0;
    this.adjudicator.customer_id = 0;
    this.adjudicator.nplist = [];
    this.adjudicator.sections = {
        'customer_details':{'label':'Adjudicator', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer\',\'customer_id\':M.ciniki_musicfestivals_main.adjudicator.data.customer_id});',
            'changeTxt':'Change customer',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer();\',\'mc\',{\'next\':\'M.ciniki_musicfestivals_main.adjudicator.updateCustomer\',\'customer_id\':0});',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.adjudicator.save();'},
            'delete':{'label':'Remove Adjudicator', 
                'visible':function() {return M.ciniki_musicfestivals_main.adjudicator.adjudicator_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.adjudicator.remove();'},
            }},
        };
    this.adjudicator.fieldValue = function(s, i, d) { return this.data[i]; }
    this.adjudicator.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.adjudicatorHistory', 'args':{'business_id':M.curBusinessID, 'adjudicator_id':this.adjudicator_id, 'field':i}};
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
        M.api.getJSONCb('ciniki.musicfestivals.adjudicatorGet', {'business_id':M.curBusinessID, 'customer_id':this.customer_id, 'adjudicator_id':this.adjudicator_id}, function(rsp) {
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
        M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID, 'customer_id':this.customer_id}, function(rsp) {
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
                M.api.postJSONCb('ciniki.musicfestivals.adjudicatorUpdate', {'business_id':M.curBusinessID, 'adjudicator_id':this.adjudicator_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.adjudicatorAdd', {'business_id':M.curBusinessID, 'customer_id':this.customer_id, 'festival_id':this.festival_id}, c, function(rsp) {
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
        if( confirm('Are you sure you want to remove adjudicator?') ) {
            M.api.getJSONCb('ciniki.musicfestivals.adjudicatorDelete', {'business_id':M.curBusinessID, 'adjudicator_id':this.adjudicator_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.adjudicator.close();
            });
        }
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
            M.api.postJSONFormData('ciniki.musicfestivals.fileAdd', {'business_id':M.curBusinessID, 'festival_id':this.festival_id}, c,
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
        return {'method':'ciniki.musicfestivals.fileHistory', 'args':{'business_id':M.curBusinessID, 'file_id':this.file_id, 'field':i}};
    };
    this.editfile.open = function(cb, fid) {
        if( fid != null ) { this.file_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.fileGet', {'business_id':M.curBusinessID, 'file_id':this.file_id}, function(rsp) {
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
            M.api.postJSONFormData('ciniki.musicfestivals.fileUpdate', {'business_id':M.curBusinessID, 'file_id':this.file_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_musicfestivals_main.editfile.close();
            });
        }
    };
    this.editfile.remove = function() {
        if( confirm('Are you sure you want to delete \'' + this.data.name + '\'?  All information about it will be removed and unrecoverable.') ) {
            M.api.getJSONCb('ciniki.musicfestivals.fileDelete', {'business_id':M.curBusinessID, 'file_id':this.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_musicfestivals_main.editfile.close();
            });
        }
    };
    this.editfile.download = function(fid) {
        M.api.openFile('ciniki.musicfestivals.fileDownload', {'business_id':M.curBusinessID, 'file_id':fid});
    };
    this.editfile.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.editfile.save();');
    this.editfile.addClose('Cancel');

    //
    // The panel to email a teacher their list of registrations
    //
    this.emailregistrations = new M.panel('Email Registrations', 'ciniki_musicfestivals_main', 'emailregistrations', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.editfile');
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
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Class', 'Registrant', 'Title', 'Time'],
            },
    };
    this.emailregistrations.fieldValue = function(s, i, d) { return ''; }
    this.emailregistrations.cellValue = function(s, i, j, d) {
        if( s == 'registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.title;
                case 3: return d.perf_time;
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
            {'business_id':M.curBusinessID, 'teacher_id':M.ciniki_musicfestivals_main.festival.teacher_customer_id, 'festival_id':M.ciniki_musicfestivals_main.festival.festival_id}, c, 
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
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}

