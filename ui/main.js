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
//            'visible':function() {return M.modFlagAny('ciniki.musicfestivals', 0x010040); },
            'visible':function() {return M.modFlagAny('ciniki.musicfestivals', 0x40); },
            'tabs':{
                'festivals':{'label':'Festivals', 'fn':'M.ciniki_musicfestivals_main.menu.switchTab("festivals");'},
                'trophies':{'label':'Trophies & Awards', 'fn':'M.ciniki_musicfestivals_main.menu.switchTab("trophies");',
                    'visible':function() {return M.modFlagSet('ciniki.musicfestivals', 0x40); },
                    },
//                'awards':{'label':'Awards', 'fn':'M.ciniki_musicfestivals_main.menu.switchTab("awards");',
//                    'visible':function() {return M.modFlagSet('ciniki.musicfestivals', 0x40); },
//                    },
//                'members':{'label':'Members', 'fn':'M.ciniki_musicfestivals_main.menu.switchTab("members");',
//                    'visible':function() {return M.modFlagSet('ciniki.musicfestivals', 0x010000); },
//                    },
            }},
        'festivals':{'label':'Festival', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected == 'festivals' ? 'yes' : 'no';},
            'noData':'No Festivals',
            'menu':{
                'add':{
                    'label':'Add Festival',
                    'fn':'M.ciniki_musicfestivals_main.edit.open(\'M.ciniki_musicfestivals_main.menu.open();\',0,null);'
                    },
                },
            },
        'trophy_types':{'label':'Trophy Types', 'type':'simplegrid', 'aside':'yes', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected != 'festivals' ? 'yes' : 'no';},
            'selected':'All',
            },
        'trophy_categories':{'label':'Categories', 'type':'simplegrid', 'aside':'yes', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected != 'festivals' ? 'yes' : 'no';},
            'selected':'All',
            },
        'trophies':{'label':'Trophies & Awards', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected == 'trophies' ? 'yes' : 'no';},
            'noData':'No Trophies',
            'headerValues':['Type', 'Category', 'Name'],
            'menu':{
                'add':{
                    'label':'Add Trophy',
                    'fn':'M.ciniki_musicfestivals_main.trophy.open(\'M.ciniki_musicfestivals_main.menu.open();\',0,null);'
                    },
                },
            },
/*        'awards':{'label':'Awards', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected == 'awards' ? 'yes' : 'no';},
            'noData':'No Awards',
            'headerValues':['Name', 'Amount'],
            'menu':{
                'add':{
                    'label':'Add Award',
                    'fn':'M.ciniki_musicfestivals_main.trophy.open(\'M.ciniki_musicfestivals_main.menu.open();\',0,null);'
                    },
                },
            }, */
        'members':{'label':'Member Festivals', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.menu.sections._tabs.selected == 'members' ? 'yes' : 'no';},
            'noData':'No Members',
            'menu':{
                'add':{
                    'label':'Add Member',
                    'fn':'M.ciniki_musicfestivals_main.member.open(\'M.ciniki_musicfestivals_main.menu.open();\',0,M.ciniki_musicfestivals_main.menu.festival_id);'
                    },
                },
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
    this.menu.switchTrophyType = function(t) {
        this.sections.trophy_types.selected = M.dU(t);
        this.sections.trophy_categories.selected = 'All';
        this.open();
    }
    this.menu.switchTrophyCategory = function(t) {
        this.sections.trophy_categories.selected = M.dU(t);
//        this.sections.trophies.label = M.dU(t) + ' Trophies';
//        this.sections.awards.label = M.dU(t) + ' Awards';
        this.open();
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'festivals' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
            }
        }
        if( s == 'trophy_types' ) {
            return d.name;
        }
        if( s == 'trophy_categories' ) {
            return d.name;
        }
        if( s == 'trophies' ) {
            switch(j) {
                case 0: return d.typename;
                case 1: return d.category;
                case 2: return d.name;
            }
        }
    }
    this.menu.rowClass = function(s, i, d) {
        if( s == 'trophy_types' && d.name == this.sections.trophy_types.selected ) {
            return 'highlight';
        }
        if( s == 'trophy_categories' && d.name == this.sections.trophy_categories.selected ) {
            return 'highlight';
        }
        return '';
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'festivals' ) {
            return 'M.ciniki_musicfestivals_main.festival.open(\'M.ciniki_musicfestivals_main.menu.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.nplist);';
        }
        if( s == 'trophy_types' ) {
            return 'M.ciniki_musicfestivals_main.menu.switchTrophyType("' + M.eU(d.name) + '");';
        }
        if( s == 'trophy_categories' ) {
            return 'M.ciniki_musicfestivals_main.menu.switchTrophyCategory("' + M.eU(d.name) + '");';
        }
        if( s == 'trophies' ) {
            return 'M.ciniki_musicfestivals_main.trophy.open(\'M.ciniki_musicfestivals_main.menu.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.menu.nplist);';
        }
        if( s == 'awards' ) {
            return 'M.ciniki_musicfestivals_main.trophy.open(\'M.ciniki_musicfestivals_main.menu.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.menu.nplist);';
        }
    }
    this.menu.open = function(cb) {
        if( this.sections._tabs.selected == 'trophies' || this.sections._tabs.selected == 'awards' ) {
            this.size = 'xlarge narrowaside';
            var args = {
                'tnid':M.curTenantID, 
                'typename':this.sections.trophy_types.selected, 
                'category':this.sections.trophy_categories.selected,
                }; 
            M.api.getJSONCb('ciniki.musicfestivals.trophyList', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.menu;
                p.data = rsp;
                p.data.awards = rsp.trophies;
                p.nplist = (rsp.nplist != null ? rsp.nplist : null);
                p.refresh();
                p.show(cb);
            });
        } else {
            this.size = 'medium';
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
    this.festival.syllabi = '';
    this.festival.section_id = -1;
    this.festival.groupname = 'all';
    this.festival.class_id = 0;
    this.festival.member_id = 0;
    this.festival.statuses = 0;
    this.festival.colour = 'white';
    this.festival.schedulesection_id = 0;
    this.festival.schedulelocation_id = 0;
    this.festival.scheduledivision_id = 0;
    this.festival.teacher_customer_id = 0;
    this.festival.accompanist_customer_id = 0;
    this.festival.adjudicator_id = 0;
    this.festival.invoice_typestatus = '';
    this.festival.list_id = 0;
    this.festival.listsection_id = 0;
    this.festival.nplists = {};
    this.festival.nplist = [];
    this.festival.messages_status = 10;
    this.festival.city_prov = 'All';
    this.festival.province = 'All';
    this.festival.registration_tag = '';
    this.festival.liveSearchSS = 0;
    this.festival.liveSearchRS = 0;
    this.festival.menutabs = {'label':'', 'type':'menutabs', 'selected':'syllabus', 'tabs':{
            'syllabus':{'label':'Syllabus', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'syllabus\');'},
            'members':{'label':'Members', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'members\');',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); },
                },
            'recommendations':{'label':'Recommendations', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'recommendations\');',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); },
                },
            'registrations':{'label':'Registrations', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'registrations\');'},
            'schedule':{'label':'Schedule', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'schedule\');'},
            'videos':{'label':'Videos', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'videos\');',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no'},
                },
            'competitors':{'label':'Competitors', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'competitors\');'},
            'messages':{'label':'Messages', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'messages\');',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x0400); },
                },
            'more':{'label':'More...', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(\'more\');'},
            }};
    this.festival.sections = {
        '_moretabs':{'label':'', 'type':'menutabs', 'selected':'adjudicators', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'more' ? 'yes' : 'no'; },
            'tabs':{
                'invoices':{'label':'Invoices', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'invoices\');'},
                'adjudicators':{'label':'Adjudicators', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'adjudicators\');'},
                'locations':{'label':'Locations', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'locations\');'},
                'certificates':{'label':'Certificates', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'certificates\');'},
                'lists':{'label':'Lists', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'lists\');',
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x20); },
                    },
                'emails':{'label':'Emails', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'emails\');',
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x0200); },
                    },
                'files':{'label':'Files', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'files\');',
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x200000); },
                    },
                'sponsors':{'label':'Sponsors', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'sponsors\');',
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x10); },
                    },
                'statistics':{'label':'Statistics', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'statistics\');',
                    },
                'ssam':{'label':'SSAM', 'fn':'M.ciniki_musicfestivals_main.festival.switchMTab(\'ssam\');',
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); },
                    },
            }},
//        'details':{'label':'Details', 'aside':'yes', 
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' ? 'yes' : 'no';},
//            'list':{
//                'name':{'label':'Name'},
//                'start_date':{'label':'Start'},
//                'end_date':{'label':'End'},
//                'num_registrations':{'label':'# Reg'},
//            }},
//        'download_buttons':{'label':'', 'aside':'yes',
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'sections' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'sections' ? 'yes' : 'no'; },
//            'buttons':{
//                'download':{'label':'Download Syllabus (PDF)', 
//                    'fn':'M.ciniki_musicfestivals_main.festival.syllabusDownload();',
//                    },
//            }},
        'syllabi_tabs':{'label':'', 'type':'paneltabs', 'selected':null, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && (M.ciniki_musicfestivals_main.festival.data.flags&0x0800) == 0x0800 ? 'yes' : 'no'; },
            'tabs':{
            }},
        'syllabus_tabs':{'label':'', 'type':'paneltabs', 'selected':'sections', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_musicfestivals_main.festival.switchSyllabusList("sections");'},
                'categories':{'label':'Categories', 'fn':'M.ciniki_musicfestivals_main.festival.switchSyllabusList("categories");'},
            }},
        'sections':{'label':'Sections', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'sections' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'number'],
            'noData':'No Sections Added',
//            'headerValues':['Section', 'Registrations'],
            'menu':{
                'add':{
                    'label':'Add Section',
                    'fn':'M.ciniki_musicfestivals_main.section.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                'levels':{
                    'label':'Update Levels',
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x1000); },
                    'fn':'M.ciniki_musicfestivals_main.levels.open(\'M.ciniki_musicfestivals_main.festival.open();\');',
                    },
                'updateearlybirdfees':{
                    'label':'Update Earlybird Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x20) == 0x20 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(0, "earlybird", "earlybird_fee");',
                    },
                'updatefees':{
                    'label':'Update Fees',
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(0, "", "fee");',
                    },
                'updatevirtualfees':{
                    'label':'Update Virtual Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x04) == 0x04 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(0, "virtual", "virtual_fee");',
                    },
                'updateearlybirdplusfees':{
                    'label':'Update Earlybird Plus Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x30) == 0x30 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(0, "earlybird plus", "earlybird_plus_fee");',
                    },
                'updateplusfees':{
                    'label':'Update Plus Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x10) == 0x10 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(0, "plus", "plus_fee");',
                    },
                'updatemarking':{
                    'label':'Update Marking',
                    'fn':'M.ciniki_musicfestivals_main.marking.open(\'M.ciniki_musicfestivals_main.festival.open();\',0);',
                    },
                'viewsections':{
                    'label':'View Sections',
                    'fn':'M.ciniki_musicfestivals_main.sections.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.sections.syllabi_tabs.selected,M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                // Add set movements/composers - (none/hidden/required)
                'download':{
                    'label':'Download Complete Syllabus (PDF)', 
                    'fn':'M.ciniki_musicfestivals_main.festival.syllabusDownload();',
                    },
                'downloadsection':{
//                    'label':'Download ' + M.ciniki_musicfestivals_main.festival.data.sectionsSyllabus (PDF)', 
                    'label':'Download Section Syllabus (PDF)', 
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.syllabusSectionDownload();',
                    },
                'updatekeywords':{
                    'label':'Update Search Keywords',
//                    'visible':function() { return (M.userPerms&0x01) == 0x01 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateKeywords();',
                    },
                'copy':{
                    'label':'Copy Previous Syllabus, Lists & Settings', 
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.data.sections.length == 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.festivalCopy("previous");',
                    },
                },
            'mailFn':function(s, i, d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.section\',\'' + d.id + '\');';
                } 
                return '';
                },
            'editFn':function(s,i,d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.section.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.sections);';
                }
                return '';
                },
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.musicfestivals.sectionUpdate', {'tnid':M.curTenantID, 
                    'section_id':M.ciniki_musicfestivals_main.festival.data.sections[from].id,
                    'sequence':M.ciniki_musicfestivals_main.festival.data.sections[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.festival.open();
                    });
                },
            },
        'syllabus_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' ? 'yes' : 'no'; },
            'hint':'Search class names',
            'noData':'No classes found',
            'headerValues':['Section', 'Category', 'Class', 'Fee', 'Registrations'],
            'headerClasses':[],
            'cellClasses':[],
            'dataMaps':[],
            },
        'syllabus_sections':{'label':'Section', 'aside':'yes', 'type':'select',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ? 'yes' : 'no'; },
            'fields':{
                'section_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.festival.switchSyllabusSection',
                    },
            }},
        'syllabus_groups':{'label':'Groups', 'aside':'yes', 'type':'select',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' && M.ciniki_musicfestivals_main.festival.section_id > 0 && (M.ciniki_musicfestivals_main.festival.data.flags&0x0400) == 0x0400 ? 'yes' : 'no'; },
            'fields':{
                'groupname':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.festival.switchSyllabusGroup',
                    },
            }},
        'syllabus_categories':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'cellClasses':['multiline'],
            'editFn':function(s,i,d) {
                if( d != null && d.name != 'All' ) {
                    return 'M.ciniki_musicfestivals_main.category.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.section_id,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.categories);';
                }
                return '';
                },
            'seqDrop':function(e,from,to){
                if( from == 0 || to == 0 ) {
                    return true;
                }
                M.api.getJSONCb('ciniki.musicfestivals.categoryUpdate', {'tnid':M.curTenantID, 
                    'category_id':M.ciniki_musicfestivals_main.festival.data.syllabus_categories[from].id,
                    'sequence':M.ciniki_musicfestivals_main.festival.data.syllabus_categories[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.festival.open();
                    });
                },
            'menu':{
                'add':{
                    'label':'Add Category',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.category.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.section_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                },
            },
        '_stabs':{'label':'', 'type':'paneltabs', 'selected':'fees', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'tabs':{
//                'sections':{'label':'Sections', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'sections\');'},
//                'categories':{'label':'Categories', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'categories\');'},
                'fees':{'label':'Fees', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'fees\');'},
                'competitors':{'label':'Competitors', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'competitors\');'},
                'titles':{'label':'Titles', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'titles\');'},
                'levels':{'label':'Levels', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x1000); },
                    'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'levels\');',
                    },
                'marking':{'label':'Marking', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'marking\');'},
                'trophies':{'label':'Trophies & Awards', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x40); },
                    'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'trophies\');',
                    },
                'scheduling':{'label':'Scheduling', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'scheduling\');'},
                'synopsis':{'label':'Synopsis', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'synopsis\');'},
//                'descriptions':{'label':'Descriptions', 'fn':'M.ciniki_musicfestivals_main.festival.switchTab(null,\'descriptions\');'},
            }},
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'categories' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'number'],
            'headerValues':['Section', 'Category', 'Registrations'],
            'menu':{
                'add':{
                    'label':'Add Category',
                    'fn':'M.ciniki_musicfestivals_main.category.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                },
            'mailFn':function(s, i, d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.category\',\'' + d.id + '\');';
                } 
                return '';
                },
            },
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected != 'descriptions' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected != 'levels' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'number', 'number'],
            'headerValues':['Category', 'Class', 'Fee'],
            'menu':{
                'add':{
                    'label':'Add Class',
                    'fn':'M.ciniki_musicfestivals_main.class.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.category_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                'updateearlybirdfees':{
                    'label':'Update Earlybird Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x20) == 0x20 && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'fees' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(M.ciniki_musicfestivals_main.festival.section_id, "earlybird", "earlybird_fee");',
                    },
                'updatefees':{
                    'label':'Update Fees',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'fees' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(M.ciniki_musicfestivals_main.festival.section_id, "", "fee");',
                    },
                'updatevirtualfees':{
                    'label':'Update Virtual Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x04) == 0x04 && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'fees' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(M.ciniki_musicfestivals_main.festival.section_id, "virtual", "virtual_fee");',
                    },
                'updateearlybirdplusfees':{
                    'label':'Update Earlybird Plus Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x30) == 0x30 && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'fees' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(M.ciniki_musicfestivals_main.festival.section_id, "earlybird plus", "earlybird_plus_fee");',
                    },
                'updateplusfees':{
                    'label':'Update Plus Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x10) == 0x10 && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'fees' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateFees(M.ciniki_musicfestivals_main.festival.section_id, "plus", "plus_fee");',
                    },
                'instrumentyes':{
                    'label':'Set Instrument to Required',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'competitors' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateInstrument(M.ciniki_musicfestivals_main.festival.section_id, "yes");',
                    },
                'instrumentno':{
                    'label':'Set Instrument to Hidden',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'competitors' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateInstrument(M.ciniki_musicfestivals_main.festival.section_id, "no");',
                    },
                'accompanistnone':{
                    'label':'Set Accompanist to None',
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x8000) && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'competitors' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setAccompanist(M.ciniki_musicfestivals_main.festival.section_id, "None");',
                    },
                'accompanistrequired':{
                    'label':'Set Accompanist to Required',
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x8000) && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'competitors' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setAccompanist(M.ciniki_musicfestivals_main.festival.section_id, "Required");',
                    },
                'accompanistoptions':{
                    'label':'Set Accompanist to Optional',
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x8000) && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'competitors' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setAccompanist(M.ciniki_musicfestivals_main.festival.section_id, "Optional");',
                    },
                'multiregyes':{
                    'label':'Allow Multiple Registrations per Class',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'competitors' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateMultireg(M.ciniki_musicfestivals_main.festival.section_id, "yes");',
                    },
                'multiregno':{
                    'label':'Remove Multiple Registrations per Class',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'competitors' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.updateMultireg(M.ciniki_musicfestivals_main.festival.section_id, "no");',
                    },
                'movementsnone':{
                    'label':'Set Movements to None',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setMovements(M.ciniki_musicfestivals_main.festival.section_id, "None");',
                    },
                'movementsrequired':{
                    'label':'Set Movements to Required',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setMovements(M.ciniki_musicfestivals_main.festival.section_id, "Required");',
                    },
                'movementsoptions':{
                    'label':'Set Movements to Optional',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setMovements(M.ciniki_musicfestivals_main.festival.section_id, "Optional");',
                    },
                'composernone':{
                    'label':'Set Composer to None',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setComposer(M.ciniki_musicfestivals_main.festival.section_id, "None");',
                    },
                'composerrequired':{
                    'label':'Set Composer to Required',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setComposer(M.ciniki_musicfestivals_main.festival.section_id, "Required");',
                    },
                'composeroptions':{
                    'label':'Set Composer to Optional',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setComposer(M.ciniki_musicfestivals_main.festival.section_id, "Optional");',
                    },
                'backtracknone':{
                    'label':'Set Backtrack to None',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setBacktrack(M.ciniki_musicfestivals_main.festival.section_id, "None");',
                    },
                'backtrackrequired':{
                    'label':'Set Backtrack to Required',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setBacktrack(M.ciniki_musicfestivals_main.festival.section_id, "Required");',
                    },
                'backtrackoptions':{
                    'label':'Set Backtrack to Optional',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setBacktrack(M.ciniki_musicfestivals_main.festival.section_id, "Optional");',
                    },
                'artworknone':{
                    'label':'Set Artwork to None',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setArtwork(M.ciniki_musicfestivals_main.festival.section_id, "None");',
                    },
                'artworkrequired':{
                    'label':'Set Artwork to Required',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setArtwork(M.ciniki_musicfestivals_main.festival.section_id, "Required");',
                    },
                'artworkoptions':{
                    'label':'Set Artwork to Optional',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setArtwork(M.ciniki_musicfestivals_main.festival.section_id, "Optional");',
                    },
                'videonone':{
                    'label':'Set Video to None',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setVideo(M.ciniki_musicfestivals_main.festival.section_id, "None");',
                    },
                'videorequired':{
                    'label':'Set Video to Required',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setVideo(M.ciniki_musicfestivals_main.festival.section_id, "Required");',
                    },
                'videooptions':{
                    'label':'Set Video to Optional',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setVideo(M.ciniki_musicfestivals_main.festival.section_id, "Optional");',
                    },
                'musicnone':{
                    'label':'Set Music to None',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setMusic(M.ciniki_musicfestivals_main.festival.section_id, "None");',
                    },
                'musicrequired':{
                    'label':'Set Music to Required',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setMusic(M.ciniki_musicfestivals_main.festival.section_id, "Required");',
                    },
                'musicoptions':{
                    'label':'Set Music to Optional',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'titles' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.festival.setMusic(M.ciniki_musicfestivals_main.festival.section_id, "Optional");',
                    },
                'updatemarking':{
                    'label':'Update Marking',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'marking' && M.ciniki_musicfestivals_main.festival.section_id > 0 && M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'sections' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.marking.open(\"M.ciniki_musicfestivals_main.festival.open();\",M.ciniki_musicfestivals_main.festival.section_id);',
                    },
                'view':{
                    'label':'Open All Details',
                    'fn':'M.ciniki_musicfestivals_main.classes.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.section_id,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.sections);',
                    },
                },
            'mailFn':function(s, i, d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.class\',\'' + d.id + '\');';
                } 
                return '';
                },
            },
/*        'section_descriptions':{'label':'Section',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'descriptions' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'fields':{
                'synopsis':{'label':'Synopsis', 'type':'textarea', 'size':'small'},
                'description':{'label':'Description', 'type':'textarea', 'size':'medium',
                    'active':function() { return !M.modFlagOn('ciniki.musicfestivals', 0x020000) ? 'yes' : 'no'; },
                    },
                'active':{'label':'Live Description', 'type':'textarea', 'size':'medium',
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x020000) ? 'yes' : 'no'; },
                    },
                'virtual_description':{'label':'Virtual Description', 'type':'textarea', 'size':'medium',
                    'active':function() { return M.modFlagOn('ciniki.musicfestivals', 0x020000) ? 'yes' : 'no'; },
                    },
//                'recommendations_description':{'label':'Adjudicator Recommendations', 'hidelabel':'yes', 'type':'textarea', 'size':'large',
//                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x010000) && M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'recommendations' ? 'yes' : 'hidden'; },
//                    },
                },
            },
        'category_descriptions':{'label':'Categories',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'descriptions' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'fields':{
                },
            },
        'description_edit_buttons':{'label':'',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'descriptions' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.festival.saveDescriptions();'},
                }}, */
        'registration_tabs':{'label':'', 'type':'menutabs', 'selected':'sections',
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("sections");'},
                'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("classes");'},
                'teachers':{'label':'Teachers', 'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("teachers");'},
                'accompanists':{'label':'Accompanists', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x8000); },
                    'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("accompanists");',
                    },
                'members':{'label':'Members', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); },
                    'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("members");',
                    },
                'tags':{'label':'Tags', 
                    'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("tags");',
                    },
                'statuses':{'label':'Statuses', 
                    'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("statuses");',
                    },
//                'colours':{'label':'Colours', 
//                    'fn':'M.ciniki_musicfestivals_main.festival.switchRegTab("colours");',
//                    },
            }}, 
        'schedule_tabs':{'label':'', 'type':'menutabs', 'selected':'timeslots', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'schedule' ? 'yes' : 'no'; },
            'tabs':{
                'timeslots':{'label':'Timeslots', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("timeslots");'},
                'locations':{'label':'Locations', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("locations");'},
//                'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("classes");'},
                'competitors':{'label':'Competitors', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("competitors");'},
                'teachers':{'label':'Teachers', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("teachers");'},
                'accompanists':{'label':'Accompanists', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("accompanists");'},
                'adjudicators':{'label':'Adjudicators', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("adjudicators");'},
                'comments':{'label':'Comments', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("comments");',
//                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no'},
                    },
                'results':{'label':'Results', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("results");', },
                'provincials':{'label':'Provincials', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("provincials");',
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x010000) ? 'no' : 'yes'}, // Provincials not set
                    },
                'photos':{'label':'Photos', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x04); },
                    'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("photos");',
                    },
                'downloads':{'label':'Downloads', 'fn':'M.ciniki_musicfestivals_main.festival.switchSTab("downloads");'},
                }},
        'ipv_tabs':{'label':'', 'aside':'yes', 'type':'paneltabs', 'selected':'all',
            'visible':function() { 
                if( ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ) {
                    return 'yes';
                }
                if( M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'adjudicators') == 'yes' && (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ) {
                    return 'yes';
                }
                return 'no';
            },
            'tabs':{
                'all':{'label':'All', 'fn':'M.ciniki_musicfestivals_main.festival.switchLVTab("all");'},
                'inperson':{'label':'Live', 'fn':'M.ciniki_musicfestivals_main.festival.switchLVTab("inperson");'},
                'virtual':{'label':'Virtual', 'fn':'M.ciniki_musicfestivals_main.festival.switchLVTab("virtual");'},
            }}, 
        'registration_sections':{'label':'Sections', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'sections' ? 'yes' : 'no'; },
            'noData':'No syllabus',
            'mailFn':function(s, i, d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.section\',\'' + d.id + '\');';
                } 
                return '';
                },
            'menu':{
                'excel':{'label':'Export to Excel', 
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadExcel(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'pdf':{'label':'Registrations PDF ', 
                    'visible':function() {return M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections'?'yes':'no';},
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadPDF(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'word':{'label':'Registrations Word ', 
                    'visible':function() {return M.modFlagOn('ciniki.musicfestivals', 0x4000) && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections'?'yes':'no';},
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadWord(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'trophiespdf':{'label':'Trophy Registrations PDF ', 
                    'visible':function() {return M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections' && M.modFlagOn('ciniki.musicfestivals', 0x40) ?'yes':'no';},
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadTrophiesPDF(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            },
        'class_sections':{'label':'Section', 'aside':'yes', 'type':'select',
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'classes' ? 'yes' : 'no'; },
            'fields':{
                'section_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.festival.switchRegistrationSection',
                    },
            }},
        'registration_classes':{'label':'Classes', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'classes' ? 'yes' : 'no'; },
//            'headerValues':['Class'],
//            'headerClasses':[''],
            'cellClasses':['multiline'],
            },
        'registration_teachers':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'teachers' ? 'yes' : 'no'; },
            'noData':'No teachers',
            'mailFn':function(s, i, d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.students\',\'' + d.id + '\');';
                } 
                return '';
                },
            },
        'registration_accompanists':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'accompanists' ? 'yes' : 'no'; },
            'noData':'No teachers',
//            'mailFn':function(s, i, d) {
//                if( d != null ) {
//                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.students\',\'' + d.id + '\');';
//                } 
//                return '';
//                },
            },
        'registration_tags':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'tags' ? 'yes' : 'no'; },
            'noData':'No tags',
            'mailFn':function(s, i, d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.registrationtag\',\'' + d.name + '\');';
                } 
                return '';
                },
            },
        'registration_members':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'members' ? 'yes' : 'no'; },
            'noData':'No members',
//            'mailFn':function(s, i, d) {
//                if( d != null ) {
//                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.registrationtag\',\'' + d.name + '\');';
//                } 
//                return '';
//                },
            },
        'registration_statuses':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'statuses' ? 'yes' : 'no'; },
            'noData':'No registrations',
            'mailFn':function(s, i, d) {
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.registrationstatus\',\'' + d.status + '\');';
                } 
                return '';
                },
            },
//        'registration_colours':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
//            'visible':function() { return ['registrations','videos'].indexOf(M.ciniki_musicfestivals_main.festival.menutabs.selected) >= 0 && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected == 'colours' ? 'yes' : 'no'; },
//            'noData':'No colours',
//            },
/*        'registration_buttons':{'label':'', 'aside':'yes', 
            'visible':function() {return M.ciniki_musicfestivals_main.festival.menutabs.selected=='registrations'?'yes':'no';},
            'buttons':{
                'excel':{'label':'Export to Excel', 
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadExcel(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'pdf':{'label':'Registrations PDF ', 
                    'visible':function() {return M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections'?'yes':'no';},
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadPDF(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'word':{'label':'Registrations Word ', 
                    'visible':function() {return M.modFlagOn('ciniki.musicfestivals', 0x4000) && M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections'?'yes':'no';},
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadWord(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'trophiespdf':{'label':'Trophy Registrations PDF ', 
                    'visible':function() {return M.ciniki_musicfestivals_main.festival.sections.registration_tabs.selected=='sections' && M.modFlagOn('ciniki.musicfestivals', 0x40) ?'yes':'no';},
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadTrophiesPDF(M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
            }}, */
        'registration_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5,
            'visible':function() {return M.ciniki_musicfestivals_main.festival.menutabs.selected=='registrations'?'yes':'no';},
            'hint':'Search',
            'noData':'No registrations found',
            'headerValues':['Class', 'Registrant', 'Teacher', 'Invoice', 'Status', 'Virtual'],
            'cellClasses':['', 'multiline', '', 'multiline', '', 'alignright'],
            },
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'registrations' && M.ciniki_musicfestivals_main.festival.section_id > -1 ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant', 'Teacher', 'Invoice', 'Status', 'Virtual'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'altnumber', 'text'],
            'cellClasses':['', 'multiline', '', 'multiline', 'multiline', 'alignright'],
            },
        'registrations_emailbutton':{'label':'', 
            'visible':function() {return M.ciniki_musicfestivals_main.festival.menutabs.selected=='registrations' && M.ciniki_musicfestivals_main.festival.teacher_customer_id > 0 ?'yes':'no';},
            'buttons':{
                'email':{'label':'Email List to Teacher', 'fn':'M.ciniki_musicfestivals_main.festival.emailTeacherRegistrations();'},
                'comments':{'label':'Download Comments PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadTeacherComments();'},
                'registrations':{'label':'Download Registrations PDF', 'fn':'M.ciniki_musicfestivals_main.festival.downloadTeacherRegistrations();'},
            }},
        'schedule_sections':{'label':'Schedules', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', ['timeslots','comments','provincials','results','photos','downloads']); },
            'noData':'No schedule',
            'cellClasses':['multiline', 'multiline alignright'],
            'menu':{
                'unscheduled':{
                    'label':'Unscheduled',
                    'fn':'M.ciniki_musicfestivals_main.festival.openScheduleSection(\'unscheduled\',"Unscheduled");',
                    },
                'add':{
                    'label':'Add Schedule',
                    'fn':'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                'advancedscheduler':{
                    'label':'Open Scheduler', 
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x4000) && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'timeslots') == 'yes' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.scheduledivisions.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'importunscheduled':{
                    'label':'Import Unscheduled Registrations', 
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x4000) && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'timeslots') == 'yes' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.unscheduledimport.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'scheduler':{
                    'label':'Open Class Scheduler', 
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x010000) && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'timeslots') == 'yes' ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.schedulemultislot.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            'mailFn':function(s, i, d) {
/*                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'comments' ) {
                    return null;
                }
                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'photos' ) {
                    return null;
                } */
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.schedulesection\',\'' + d.id + '\');';
                } 
                return '';
                },
            'editFn':function(s, i, d) {
                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'comments' ) {
                    return '';
                }
                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'photos' ) {
                    return '';
                }
                return 'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
                },
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.musicfestivals.scheduleSectionUpdate', {'tnid':M.curTenantID, 
                    'schedulesection_id':M.ciniki_musicfestivals_main.festival.data.schedule_sections[from].id,
                    'sequence':M.ciniki_musicfestivals_main.festival.data.schedule_sections[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.festival.open();
                    });
                },
            },
        'schedule_locations':{'label':'Locations', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', ['locations']); },
            'noData':'No schedule',
            'cellClasses':['multiline'],
            },
        'schedule_teachers':{'label':'Teachers', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'teachers'); },
            'noData':'No Scheduled Accompanists',
            'headerValues':['Accompanist', '#'],
            'headerClasses':['', 'alignright'],
            'cellClasses':['', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'number'],
            },
        'schedule_accompanists':{'label':'Accompanists', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'accompanists'); },
            'noData':'No Scheduled Accompanists',
            'headerValues':['Accompanist', '#'],
            'headerClasses':['', 'alignright'],
            'cellClasses':['', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'number'],
            },
        'schedule_adjudicators':{'label':'Adjudicators', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'adjudicators'); },
            'noData':'No Scheduled Adjuciators',
            'headerValues':['Adjudicator', 'Completed'],
            'headerClasses':['', 'alignright'],
            'cellClasses':['', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'number'],
            },
        'adjudicator_schedule':{'label':'Adjudicator Schedule', 'type':'simplegrid', 'num_cols':5, 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.adjudicator_id > 0 && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'adjudicators') == 'yes' ? 'yes' : 'no'; },
            'noData':'No assigned registrations',
            'headerValues':['Section/Division', 'Date', 'Time', 'Registration', 'Placement'],
            'headerClasses':['', '', '', '', ''],
            'cellClasses':['', '', '', '', ''],
            'sortable':'yes',
            'sortTypes':['text', 'date', 'time', 'text', 'text'],
            'menu':{
                'add':{
                    'label':'Runsheets PDF',
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadAdjudicatorRunSheetsPDF(M.ciniki_musicfestivals_main.festival.adjudicator_id);',
                    },
                },
            },
        'schedule_divisions':{'label':'Divisions', 'type':'simplegrid', 'num_cols':2, 'aside':'no', 'panelcolumn':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id != 'unscheduled' && M.ciniki_musicfestivals_main.festival.isSelected('schedule', ['timeslots','locations', 'comments','results','photos']) == 'yes' ? 'yes' : 'no'; },
            'headerValues':['Division', 'Date', 'Adjudicator'],
            'cellClasses':['multiline', 'multiline', ''],
            'menu':{
                'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', ['timeslots']) == 'yes' && M.ciniki_musicfestivals_main.festival.schedulesection_id > 0 ? 'yes' : 'no'; },
                'add':{
                    'label':'Add Division',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', ['timeslots']) == 'yes' && M.ciniki_musicfestivals_main.festival.schedulesection_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                },
            'mailFn':function(s, i, d) {
                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'comments' ) {
                    return null;
                }
                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'photos' ) {
                    return null;
                }
                if( d != null ) {
                    return 'M.ciniki_musicfestivals_main.message.addnew(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id,\'ciniki.musicfestivals.scheduledivision\',\'' + d.id + '\');';
                } 
                return '';
                },
            'editFn':function(s, i, d) {
                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'comments' ) {
                    return '';
                }
                if( M.ciniki_musicfestivals_main.festival.menutabs.selected == 'photos' ) {
                    return '';
                }
                return 'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
                },
            'noData':'Select a section',
            },
        'program_options':{'label':'Download Options', 'aside':'no',
            'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'downloads') == 'yes' ? 'yes' : 'no'; },
            'fields':{
                'ipv':{'label':'Type', 'type':'toggle', 'default':'all', 
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no'; },
                    'toggles':{'all':'All', 'inperson':'In Person', 'virtual':'Virtual'},
                    },
            }},
        'sbuttons1':{'label':'Download', 'aside':'no',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'downloads'); },
            'size':'flex',
            'buttons':{
                'pdf':{'label':'Complete Program', 'fn':'M.ciniki_musicfestivals_main.festival.downloadProgramPDF(0);'},
                'certs':{'label':'All Certificates', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCertificatesPDF(0);'},
                'comments':{'label':'All Adjudicators Comments', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCommentsPDF(0);'},
                'runsheets':{'label':'All Run Sheets', 'fn':'M.ciniki_musicfestivals_main.festival.downloadRunSheetsPDF(0);'},
                'dailyschedule':{'label':'Compact Schedule', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCompactSchedulePDF(0);'},
                'competitors':{'label':'All Daily Venue Competitors', 'fn':'M.ciniki_musicfestivals_main.festival.downloadDailyVenueCompetitorsPDF(0);'},
                'trophies':{'label':'Trophy Registrations', 'fn':'M.ciniki_musicfestivals_main.festival.downloadTrophyMarksPDF();'},
                'resultsexcel':{'label':'Results Excel', 'fn':'M.ciniki_musicfestivals_main.festival.downloadResultsExcel(0);'},
                'recommendations':{'label':'Provincial Recommendations', 'fn':'M.ciniki_musicfestivals_main.festival.downloadProvincialRecommendations(0);'},
                'backtracks':{'label':'All Backtracks', 'fn':'M.ciniki_musicfestivals_main.festival.downloadBacktracks(0);'},
                'artwork':{'label':'All Artwork', 'fn':'M.ciniki_musicfestivals_main.festival.downloadArtwork(0);'},
                'blankcomments':{'label':'Blank Comments', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCommentsPDF("blank");'},
            }},
        'sbuttons2':{'label':'Current Section Downloads', 'aside':'no',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id > 0 && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'downloads') == 'yes' ? 'yes' : 'no'; },
            'size':'flex',
            'buttons':{
                'pdf':{'label':'Program', 'fn':'M.ciniki_musicfestivals_main.festival.downloadProgramPDF();'},
                'certs':{'label':'Certificates', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCertificatesPDF();'},
                'comments':{'label':'Adjudicators Comments', 'fn':'M.ciniki_musicfestivals_main.festival.downloadCommentsPDF();'},
                'runsheets':{'label':'Run Sheets', 'fn':'M.ciniki_musicfestivals_main.festival.downloadRunSheetsPDF();'},
                'resultsexcel':{'label':'Results Excel', 'fn':'M.ciniki_musicfestivals_main.festival.downloadResultsExcel();'},
                'backtracks':{'label':'Backtracks', 'fn':'M.ciniki_musicfestivals_main.festival.downloadBacktracks();'},
                'artwork':{'label':'Artwork', 'fn':'M.ciniki_musicfestivals_main.festival.downloadArtwork();'},
            }},
        'scheduleoptions':{'label':'Schedule Options', 'aside':'no',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'downloads'); },
            'fields':{
                's_ipv':{'label':'Type', 'type':'toggle', 'default':'all', 
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no'; },
                    'toggles':{'all':'All', 'inperson':'In Person', 'virtual':'Virtual'},
                    },
                'schedule-division-header-format':{'label':'Division Header Format', 'type':'select', 'default':'default', 'options':{
                    'default':'Date-Division, Location', 
                    'namedate-adjudicatorlocation':'Division-Date, Adjudicator-Location', 
                    'name-adjudicator-location':'Division, Adjudicator, Location',
                    'date-adjudicator-location':'Date, Adjudicator, Location',
                    'date-name-adjudicator-location':'Date, Division, Adjudicator, Location',
                    'name-date-adjudicator-location':'Division, Date, Adjudicator, Location',
                    }},
                'schedule-division-header-labels':{'label':'Division Header Labels', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
//                's_division_header_adjudicator':{'label':'Include Adjudi', 'type':'toggle', 'default':'default', 'toggles':{
//                    'default':'name - date<br/>address', 
//                    'name-date-adjudicator-address':'Name<br/>Date: date<br/>Adjudicator: adjudicator<br/>Address: address', 
//                    }},
                'schedule-section-adjudicator-bios':{'label':'Section Adjudicator Bios', 'type':'toggle', 'default':'no', 
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x0800) ? 'no' : 'yes'; },
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                        }},
                'schedule-names':{'label':'Competitor Full Names', 'type':'toggle', 'default':'public', 'toggles':{'public':'No', 'private':'Yes'}},
                'schedule-titles':{'label':'Titles', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-video-urls':{'label':'Include YouTube Links', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-header':{'label':'Document Header', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-footer':{'label':'Document Footer', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-footerdate':{'label':'Footer Date', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-section-page-break':{'label':'Section Page Break', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
        'schedule_buttons':{'label':'', 'aside':'no',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'downloads'); },
            'size':'half',
            'buttons':{
                'complete':{'label':'Complete Schedule', 'fn':'M.ciniki_musicfestivals_main.festival.downloadSchedulePDF(0);'},
                'partial':{'label':'Current Section', 'fn':'M.ciniki_musicfestivals_main.festival.downloadSchedulePDF();',
                    'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id > 0 ? 'yes' : 'no'; },
                    },
            }},
        'schedule_timeslots':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':2,  'panelcolumn':2,
            'visible':function() { 
                var p = M.ciniki_musicfestivals_main.festival;
                if( p.schedulelocation_id > 0 && p.scheduledivision_id > 0 && p.isSelected('schedule','locations') == 'yes' ) {
                    return 'yes';
                }
                if( p.schedulesection_id > 0 && p.scheduledivision_id > 0 && p.isSelected('schedule', 'timeslots') == 'yes' ) {
                    return 'yes';
                } 
                return 'no';
                },
            'cellClasses':['label multiline', 'multiline', 'fabuttons'],
            'menu':{
                'add':{
                    'label':'Add Time Slot',
                    'fn':'M.ciniki_musicfestivals_main.scheduletimeslot.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                'import':{
                    'label':'Import Unscheduled Section Classes',
                    'fn':'M.ciniki_musicfestivals_main.scheduledivisionimport.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'timingsreport':{
                    'label':'Timings PDF',
                    'fn':'M.ciniki_musicfestivals_main.festival.scheduleTimingsPDF();',
                    },
                },
            },
        'schedule_competitors':{'label':'Competitor Schedules', 'type':'simplegrid', 'num_cols':13, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'competitors'); },
            'cellClasses':['', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline', 'multiline','multiline', 'multiline', 'multiline', 'multiline', 'multiline','multiline'],
            },
        'teacher_schedule':{'label':'Teacher Schedule', 'type':'simplegrid', 'num_cols':5, 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'teachers'); },
            'noData':'Select Teacher',
            'headerValues':['Date', 'Time', 'Location', 'Registration', 'Class'],
            'cellClasses':[''],
/*            'menu':{
                'add':{
                    'label':'Schedule PDF',
                    'alt':"Download the accompanist's schedule",
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadAccompanistSchedulePDF();',
                    },
                }, */
            },
        'accompanist_schedule':{'label':'Accompanist Schedule', 'type':'simplegrid', 'num_cols':5, 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'accompanists'); },
            'noData':'Select Accompanist',
            'headerValues':['Date', 'Time', 'Location', 'Registration', 'Class'],
            'cellClasses':[''],
            'menu':{
                'add':{
                    'label':'Schedule PDF',
                    'alt':"Download the accompanist's schedule",
                    'fn':'M.ciniki_musicfestivals_main.festival.downloadAccompanistSchedulePDF();',
                    },
                },
            },
//        'accompanist_buttons':{'label':'', 'aside':'no',
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'accompanists') == 'yes' && M.ciniki_musicfestivals_main.festival.accompanist_customer_id > 0 ? 'yes' : 'no'; },
//            'buttons':{
//                'download':{'label':'Download Schedule', 'fn':'M.ciniki_musicfestivals_main.festival.downloadAccompanistSchedulePDF();'},
//            }},
        'schedule_results':{'label':'Results', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id>0 && M.ciniki_musicfestivals_main.festival.scheduledivision_id>0 && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'results') == 'yes' ? 'yes' : 'no'; },
            'dataMaps':['', '', '', '', 'mark', 'placement', 'level'],
            'headerValues':['Time', '#', 'Name', 'Class/Titles', 'Mark', 'Place'],
            'cellClasses':['', '', '', 'multiline', '', ''],
            'menu':{
                'add':{
                    'label':'Update Results',
                    'fn':'M.ciniki_musicfestivals_main.results.open(\'M.ciniki_musicfestivals_main.festival.open();\', M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.scheduledivision_id);',
                    },
                },
//            'sortable':'yes',
//            'sortTypes':['text', 'time', 'number', 'text', 'number', 'text'],
            },
        'schedule_provincials':{'label':'Provincial Recommendations', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id>0 && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'provincials') == 'yes' ? 'yes' : 'no'; },
            'dataMaps':['', '', '', '', 'mark', 'placement', 'level'],
            'headerValues':['Provincial Code', 'Position', 'Name', 'Class/Titles', 'Mark', 'Place'],
            'cellClasses':['', 'multiline', '', 'multiline', '', ''],
            'noData':'No provincials class codes setup',
            'addTopTxt':'Update Provincial Class Codes',
            'addTopFn':'M.ciniki_musicfestivals_main.pcodes.open(\'M.ciniki_musicfestivals_main.festival.open();\', M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.schedulesection_id);',
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'number', 'text', 'text'],
            },
        'timeslot_photos':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id>0 && M.ciniki_musicfestivals_main.festival.scheduledivision_id>0 && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'photos') == 'yes' ? 'yes' : 'no'; },
            'cellClasses':['multiline', 'thumbnails', 'alignright fabuttons'],
            'addDropImage':function(iid, i) {
                var row = M.ciniki_musicfestivals_main.festival.data.timeslot_photos[i];
                M.api.getJSONCb('ciniki.musicfestivals.timeslotImageAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 
                    'timeslot_id':row.id, 'image_id':iid},
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_musicfestivals_main.festival;
                        var t = M.gE(p.panelUID + '_timeslot_photos_grid');
                        var cell = t.children[0].children[i].children[1];
                        cell.innerHTML += '<img class="clickable" onclick="M.ciniki_musicfestivals_main.timeslotimage.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + rsp.id + '\');" width="50px" height="50px" src=\'' + rsp.image + '\' />';
                    });
                return true;
                },
            },
        'timeslot_comments':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':3, 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id>0 && M.ciniki_musicfestivals_main.festival.scheduledivision_id>0 && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'comments') == 'yes' ? 'yes' : 'no'; },
            'headerValues':['Time', 'Name', '', '', ''],
            'headerClasses':['', '', 'aligncenter', 'aligncenter', 'aligncenter'],
            'cellClasses':['', 'multiline', 'aligncenter', 'aligncenter', 'aligncenter'],
            },
        'unscheduled_registrations':{'label':'Unscheduled', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.schedulesection_id == 'unscheduled' && M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'timeslots') == 'yes' ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant', 'Status'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text'],
            'cellClasses':['', 'multiline', ''],
            },
        'video_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5,
            'visible':function() {return M.ciniki_musicfestivals_main.festival.menutabs.selected=='videos'?'yes':'no';},
            'hint':'Search',
            'noData':'No registrations found',
            'headerValues':['Class', 'Registrant', 'Video Link', 'PDF', 'Status'],
            'cellClasses':['', '', '', '', ''],
            },
        'videos':{'label':'Registrations', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'videos' ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant/Title', 'Video Link/PDF', 'Status', ''],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'altnumber', ''],
            'cellClasses':['multiline', 'multiline', 'multiline', '', '', 'alignright'],
            },
        'competitor_tabs':{'label':'', 'aside':'yes', 'type':'paneltabs', 'selected':'cities',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'competitors' ? 'yes' : 'no'; },
            'tabs':{
                'cities':{'label':'Cities', 'fn':'M.ciniki_musicfestivals_main.festival.switchCompTab("cities");'},
                'provinces':{'label':'Provinces', 'fn':'M.ciniki_musicfestivals_main.festival.switchCompTab("provinces");'},
            }}, 
        'competitor_cities':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'competitors' && M.ciniki_musicfestivals_main.festival.sections.competitor_tabs.selected == 'cities' ? 'yes' : 'no'; },
            'editFn':function(s, i, d) {
                if( d.city != null && d.province != null ) {
                    return 'M.ciniki_musicfestivals_main.editcityprov.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + escape(d.city) + '\',\'' + escape(d.province) + '\');';
                }
                return '';
                },
            },
        'competitor_provinces':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'competitors' && M.ciniki_musicfestivals_main.festival.sections.competitor_tabs.selected == 'provinces' ? 'yes' : 'no'; },
            'editFn':function(s, i, d) {
                if( d.province != null ) {
                    return 'M.ciniki_musicfestivals_main.editcityprov.open(\'M.ciniki_musicfestivals_main.festival.open();\',null,\'' + escape(d.province) + '\');';
                }
                return '';
                },
            },
        'competitors_tabs':{'label':'', 'type':'paneltabs', 'selected':'classes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'competitors' ? 'yes' : 'no'; },
            'tabs':{
                'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.festival.switchCompsTab("classes");'},
                'notes':{'label':'Notes', 'fn':'M.ciniki_musicfestivals_main.festival.switchCompsTab("notes");'},
            }}, 
        'competitors':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'competitors' ? 'yes' : 'no'; },
            'headerValues':['Name', 'Classes', 'Waiver'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text'],
            },
        'lists':{'label':'Lists', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'lists'); },
            'menu':{
                'add':{
                    'label':'Add List',
                    'fn':'M.ciniki_musicfestivals_main.list.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                },
            'editFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.list.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
                },
            },
        'listsections':{'label':'Sections', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return (M.ciniki_musicfestivals_main.festival.isSelected('more', 'lists') == 'yes' && M.ciniki_musicfestivals_main.festival.list_id > 0) ? 'yes' : 'no'; },
            'menu':{
                'add':{
                    'label':'Add Section',
                    'fn':'M.ciniki_musicfestivals_main.listsection.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.list_id,null);',
                    },
                },
            'editFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.listsection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.list_id,null);';
                },
            },
        'listentries':{'label':'Sections', 'type':'simplegrid', 'num_cols':4, 
            'visible':function() { return (M.ciniki_musicfestivals_main.festival.isSelected('more', 'lists') == 'yes' && M.ciniki_musicfestivals_main.festival.listsection_id > 0) ? 'yes' : 'no'; },
            'headerValues':['Award', 'Amount', 'Donor', 'Winner'],
            'menu':{
                'add':{
                    'label':'Add Entry',
                    'label':'M.ciniki_musicfestivals_main.listentry.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.listsection_id,null);',
                    },
                },
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
        'invoice_statuses':{'label':'Status', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'invoices'); },
            },
        'invoices':{'label':'Invoices', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'invoices'); },
            'headerValues':['#', 'Customer', 'Students', 'Total', 'Status'],
            'noData':'No invoices',
            'sortable':'yes',
            'sortTypes':['number', 'text', 'text', 'number', 'text', ''],
            },
        'adjudicators':{'label':'Adjudicators', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'adjudicators'); },
            'headerValues':['Name', 'Discipline'],
            'sortable':'yes',
            'sortTypes':['text', 'text'],
            'noData':'No adjudicators for this festival',
            'menu':{
                'add':{
                    'label':'Add Adjudicator',
                    'fn':'M.ciniki_musicfestivals_main.adjudicator.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                },
            },
        'locations':{'label':'Locations', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'locations'); },
            'headerValues':['Category', 'Name', 'Address', 'City'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text'],
            'noData':'No locations for this festival',
            'menu':{
                'add':{
                    'label':'Add Location',
                    'fn':'M.ciniki_musicfestivals_main.location.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                },
            },
        'files':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'files'); },
            'menu':{
                'add':{
                    'label':'Add File',
                    'fn':'M.ciniki_musicfestivals_main.addfile.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            },
        'certificates':{'label':'Certificates', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'certificates'); },
            'headerValues':['Name', 'Section', 'Min Score'],
            'menu':{
                'add':{
                    'label':'Add Certificate',
                    'fn':'M.ciniki_musicfestivals_main.certificate.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            },
        'lists':{'label':'Lists', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'lists'); },
            'menu':{
                'add':{
                    'label':'Add List',
                    'fn':'M.ciniki_musicfestivals_main.list.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id,null);',
                    },
                },
            'editFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.list.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
                },
            },
        'message_statuses':{'label':'', 'type':'simplegrid', 'aside':'yes', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('messages', ''); },
            },
        'message_buttons':{'label':'', 'aside':'yes', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('messages', ''); },
            'buttons':{
                'add':{'label':'Add Message', 'fn':'M.ciniki_musicfestivals_main.message.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id);'},
            }},
        'messages':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('messages', ''); },
            'headerValues':['Subject', 'Date'],
            'noData':'No Messages',
            },
        'members':{'label':'Member Festivals', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'members' ? 'yes' : 'no'; },
//            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'members'); },
            'headerValues':['Name', 'Category/Admin', 'Reg Open', 'Reg Close', 'Late', '# Reg'],
            'headerClasses':['', '', '', '', 'alignright', 'alignright'],
            'cellClasses':['multiline', 'multiline', '', '', 'alignright', 'alignright'],
            'noData':'No Member Festivals',
            'sortable':'yes',
            'sortTypes':['text', 'text', 'date', 'date', 'number', 'number'],
            'menu':{
                'add':{
                    'label':'Add Member',
                    'fn':'M.ciniki_musicfestivals_main.member.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            },
        'members_buttons':{'label':'', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'members' ? 'yes' : 'no'; },
            'buttons':{
                'email':{'label':'Email Members', 'fn':'M.ciniki_musicfestivals_main.festival.emailMembers();'},
                },
            },
        'recommendation_tabs':{'label':'', 'type':'menutabs', 'selected':'classes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('recommendations', ''); },
            'tabs':{
                'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.festival.switchRecommendationTab("classes");'},
                'submissions':{'label':'Submissions', 'fn':'M.ciniki_musicfestivals_main.festival.switchRecommendationTab("submissions");'},
            }},
        'recommendation_members':{'label':'Member Festivals', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'recommendations' && M.ciniki_musicfestivals_main.festival.sections.recommendation_tabs.selected == 'submissions' ? 'yes' : 'no'; },
            },
        'recommendation_sections':{'label':'Section', 'aside':'yes', 'type':'select',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'recommendations' && M.ciniki_musicfestivals_main.festival.sections.recommendation_tabs.selected == 'classes' ? 'yes' : 'no'; },
            'fields':{
                'section_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.festival.switchRecommendationSection',
                    },
            }},
        'recommendation_classes':{'label':'Classes', 'aside':'yes', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'recommendations' && M.ciniki_musicfestivals_main.festival.sections.recommendation_tabs.selected == 'classes' ? 'yes' : 'no'; },
            'headerValues':['Class', 'W', 'G'],
            'headerClasses':['', 'alignright', 'alignright', 'alignright'],
            'cellClasses':['', 'alignright', 'alignright', 'alignright'],
            },
        'recommendation_buttons1':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'recommendations' && M.ciniki_musicfestivals_main.festival.sections.recommendation_tabs.selected == 'classes' ? 'yes' : 'no'; },
            'buttons':{
                'sectionexcel':{'label':'Section Excel', 'fn':'M.ciniki_musicfestivals_main.festival.downloadRecommendationsSectionExcel();'},
                'fullexcel':{'label':'Full Excel', 'fn':'M.ciniki_musicfestivals_main.festival.downloadRecommendationsExcel();'},
            }},
        'recommendation_submissions':{'label':'Submissions', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'recommendations' && M.ciniki_musicfestivals_main.festival.sections.recommendation_tabs.selected == 'submissions' ? 'yes' : 'no'; },
            'headerValues':['Adjudicator', 'Section', 'Date Submitted', 'Entries'],
            'headerClasses':['', '', 'alignright', 'alignright'],
            'cellClasses':['', '', 'alignright', 'alignright'],
            }, 
        'recommendation_buttons2':{'label':'', 'aside':'no',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'recommendations' && M.ciniki_musicfestivals_main.festival.sections.recommendation_tabs.selected == 'submissions' && M.ciniki_musicfestivals_main.festival.member_id > 0 ? 'yes' : 'no'; },
            'buttons':{
                'memberexcel':{'label':'Download Member Excel', 'fn':'M.ciniki_musicfestivals_main.festival.downloadRecommendationsMemberExcel();'},
            }},
        'recommendation_entries':{'label':'Recommendations', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'recommendations' && M.ciniki_musicfestivals_main.festival.sections.recommendation_tabs.selected == 'classes' ? 'yes' : 'no'; },
            'headerValues':['Name', 'Position', 'Mark', 'Festival', 'Date Submitted', 'Deadline'],
            'sortable':'yes', 
            'sortTypes':['text', 'text', 'number', 'text', 'date', ''],
            },
        'emails_tabs':{'label':'', 'aside':'yes', 'type':'paneltabs', 'selected':'all',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'emails'); },
            'tabs':{
                'all':{'label':'All', 'fn':'M.ciniki_musicfestivals_main.festival.switchEmailsTab("all");'},
                'teachers':{'label':'Teachers', 'fn':'M.ciniki_musicfestivals_main.festival.switchEmailsTab("teachers");'},
                'competitors':{'label':'Competitors', 'fn':'M.ciniki_musicfestivals_main.festival.switchEmailsTab("competitors");'},
            }}, 
        'emails_sections':{'label':'Sections', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'emails'); },
            },
        'emails_html':{'label':'Emails', 'type':'html', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'emails'); },
            },
        'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'sponsors'); },
            'headerValues':['Name', 'Tags'],
            'menu':{
                'add':{
                    'label':'Add Sponsor',
                    'fn':'M.ciniki_musicfestivals_main.sponsor.open(\'M.ciniki_musicfestivals_main.festival.open();\',0,M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                'copyprevious':{
                    'label':'Copy Previous Years Sponsors',
                    'fn':'M.ciniki_musicfestivals_main.festival.festivalSponsorsCopy("previous");',
                    },
                },
        },
        'sponsors-old':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'sponsors-old'); },
            'addTxt':'Manage Sponsors',
            'addFn':'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_musicfestivals_main.festival.open();\',\'mc\',{\'object\':\'ciniki.musicfestivals.festival\',\'object_id\':M.ciniki_musicfestivals_main.festival.festival_id});',
        },
        'statistics':{'label':'Statistics', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'statistics') == 'yes' && M.ciniki_musicfestivals_main.festival.sections.stats_tabs.selected != 'members' ? 'yes' : 'no'; },
            'cellClasses':['flexlabel', 'alignleft'],
            'noData':'No Statistics yet',
        },
        'stats_placements':{'label':'Placements', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'statistics') == 'yes' && M.ciniki_musicfestivals_main.festival.data.stats_placements != null && M.ciniki_musicfestivals_main.festival.sections.stats_tabs.selected != 'members' ? 'yes' : 'no'; },
            'cellClasses':['flexlabel', 'alignleft'],
            'noData':'No Statistics yet',
        },
        'stats_tabs':{'label':'', 'type':'paneltabs', 'selected':'cities', 
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'statistics') == 'yes' && M.modFlagOn('ciniki.musicfestivals', 0x010000) ? 'yes' : 'no'; },
            'tabs':{
                'cities':{'label':'Cities', 'fn':'M.ciniki_musicfestivals_main.festival.switchStatsTab("cities");'},
                'members':{'label':'Members', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); },
                    'fn':'M.ciniki_musicfestivals_main.festival.switchStatsTab("members");',
                    },
            }},
        'stats_cities':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'statistics') == 'yes' && M.ciniki_musicfestivals_main.festival.sections.stats_tabs.selected == 'cities' ? 'yes' : 'no'; },
            'cellClasses':['flexlabel', 'alignleft'],
            'headerValues':['City, Province', 'Number of Competitors', 'Num of Registrations'],
            'sortable':'yes',
            'sortTypes':['text', 'number', 'number'],
            'noData':'No Statistics yet',
        },
        'stats_members':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'statistics') == 'yes' && M.ciniki_musicfestivals_main.festival.sections.stats_tabs.selected == 'members' ? 'yes' : 'no'; },
            'cellClasses':['flexlabel', 'alignleft'],
            'headerValues':['Name'],
            'dataMaps':['name'],
            'sortable':'yes',
            'sortTypes':['text', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number'],
            'noData':'No Statistics yet',
        },
        'ssam_sections':{'label':'Sections', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'ssam'); },
            'selected':'',
            'menu':{
                'add':{
                    'label':'Add Section',
                    'fn':'M.ciniki_musicfestivals_main.ssamsection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'\',M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            'editFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.ssamsection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + M.eU(d.name) + '\',M.ciniki_musicfestivals_main.festival.festival_id);';
                },
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.musicfestivals.ssamSectionUpdate', {'tnid':M.curTenantID, 
                    'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                    'section_name':M.ciniki_musicfestivals_main.festival.data.ssam_sections[from].name,
                    'moveto_name':M.ciniki_musicfestivals_main.festival.data.ssam_sections[to].name,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.festival.open();
                    });
                },
            },
        'ssam_categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'ssam') == 'yes' && M.ciniki_musicfestivals_main.festival.sections.ssam_sections.selected != '' ? 'yes' : 'no'; },
            'selected':'',
            'menu':{
                'add':{
                    'label':'Add Category',
                    'fn':'M.ciniki_musicfestivals_main.ssamcategory.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.sections.ssam_sections.selected,\'\',M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            'editFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.ssamcategory.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.sections.ssam_sections.selected,\'' + M.eU(d.name) + '\',M.ciniki_musicfestivals_main.festival.festival_id);';
                },
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.musicfestivals.ssamCategoryUpdate', {'tnid':M.curTenantID, 
                    'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                    'section_name':M.ciniki_musicfestivals_main.festival.sections.ssam_sections.selected,
                    'category_name':M.ciniki_musicfestivals_main.festival.data.ssam_categories[from].name,
                    'moveto_name':M.ciniki_musicfestivals_main.festival.data.ssam_categories[to].name,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.festival.open();
                    });
                },
            },
        'ssam_items':{'label':'List', 'type':'simplegrid', 'num_cols':10, 
            'headerValues':['Name', 'Song 1', 'Song 2', 'Song 3', 'Song 4', 'Song 5', 'Song 6', 'Song 7', 'Song 8', 'Song 9'],
            'visible':function() { return M.ciniki_musicfestivals_main.festival.isSelected('more', 'ssam') == 'yes' && M.ciniki_musicfestivals_main.festival.sections.ssam_sections.selected != '' && M.ciniki_musicfestivals_main.festival.sections.ssam_categories.selected != '' ? 'yes' : 'no'; },
            'menu':{
                'add':{
                    'label':'Add Movie/Show',
                    'fn':'M.ciniki_musicfestivals_main.ssamitem.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.sections.ssam_sections.selected,M.ciniki_musicfestivals_main.festival.sections.ssam_categories.selected,\'\',M.ciniki_musicfestivals_main.festival.festival_id);',
                    },
                },
            },

    }
    this.festival.isSelected = function(t, m) {
        if( this.menutabs.selected == t ) {
            if( t == 'more' ) {
                return this.sections._moretabs.selected == m ? 'yes' : 'no';
            }
            else if( t == 'schedule' ) {
                if( typeof m == 'object' ) {
                    return m.indexOf(this.sections.schedule_tabs.selected) >= 0 ? 'yes' : 'no';
                } else {
                    return this.sections.schedule_tabs.selected == m ? 'yes' : 'no';
                }
            }
            return 'yes';
        }
        return 'no';
    }
    this.festival.sectionData = function(s) {
        if( s == 'videos' ) {
            this.data.videos = [];
            for(var i in this.data.registrations) {
                for(var j = 1; j <= 8; j++) {
                    if( j <= this.data.registrations[i].min_titles ||
                        (j <= this.data.registrations[i].max_titles && this.data.registrations[i]['title'+j] != '')
                        ) {
                        this.data.videos.push({
                            'id': this.data.registrations[i].id,
                            'class_code': this.data.registrations[i].class_code,
                            'class_name': this.data.registrations[i].class_name,
                            'display_name': this.data.registrations[i].display_name,
                            'title': this.data.registrations[i]['title'+j],
                            'video_url': this.data.registrations[i]['video_url'+j],
                            'music_orgfilename': this.data.registrations[i]['music_orgfilename'+j],
                            'status_text': this.data.registrations[i].status_text,
                            });
                    }
                }
            }
            return this.data.videos;
        }
        return M.panel.prototype.sectionData.call(this, s);
    }
    this.festival.downloadProgramPDF = function(s) {
        var args = {
            'tnid':M.curTenantID, 
            'festival_id':this.festival_id, 
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.programPDF',args);
    }
    this.festival.downloadSchedulePDF = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'division_header_format':this.formValue('schedule-division-header-format'),
            'division_header_labels':this.formValue('schedule-division-header-labels'),
            'section_adjudicator_bios':this.formValue('schedule-section-adjudicator-bios'),
            'names':this.formValue('schedule-names'),
            'ipv':this.formValue('s_ipv'),
            'titles':this.formValue('schedule-titles'),
            'video_urls':this.formValue('schedule-video-urls'),
            'header':this.formValue('schedule-header'),
            'footer':this.formValue('schedule-footer'),
            'section_page_break':this.formValue('schedule-section-page-break'),
            'footerdate':this.formValue('schedule-footerdate'),
            };
        M.api.openPDF('ciniki.musicfestivals.schedulePDF',args);
    }
    this.festival.downloadCertificatesPDF = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'ipv':this.formValue('ipv'),
            };
        M.api.openFile('ciniki.musicfestivals.certificatesPDF',args);
    }
    this.festival.downloadCommentsPDF = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.commentsPDF',args);
    }
    this.festival.downloadRunSheetsPDF = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.runsheetsPDF',args);
    }
    this.festival.downloadAdjudicatorRunSheetsPDF = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'adjudicator_id':this.adjudicator_id,
//            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.runsheetsPDF',args);
    }
    this.festival.downloadCompactSchedulePDF = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.compactSchedulePDF',args);
    }
    this.festival.downloadDailyVenueCompetitorsPDF = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.dailyVenueCompetitorsPDF',args);
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
    this.festival.downloadRecommendationsSectionExcel = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'section_id':this.section_id,
            };
        M.api.openFile('ciniki.musicfestivals.recommendationsExcel',args);
    }
    this.festival.downloadRecommendationsMemberExcel = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'member_id':this.member_id,
            };
        M.api.openFile('ciniki.musicfestivals.recommendationsExcel',args);
    }
    this.festival.downloadRecommendationsExcel = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            };
        M.api.openFile('ciniki.musicfestivals.recommendationsExcel',args);
    }
    this.festival.downloadResultsExcel = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            'ipv':this.formValue('ipv'),
            };
        M.api.openPDF('ciniki.musicfestivals.scheduleResultsExcel',args);
    }
    this.festival.downloadProvincialRecommendations = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            };
        M.api.openPDF('ciniki.musicfestivals.provincialRecommendationsPDF',args);
    }
    this.festival.downloadBacktracks = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            };
        M.api.openFile('ciniki.musicfestivals.backtracksZip',args);
    }
    this.festival.downloadArtwork = function(s) {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':(s==null ? this.schedulesection_id : s),
            };
        M.api.openFile('ciniki.musicfestivals.artworkZip',args);
    }
    this.festival.downloadAccompanistSchedulePDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'customer_id':this.accompanist_customer_id,
            };
        M.api.openPDF('ciniki.musicfestivals.accompanistSchedulePDF',args);
    }
    this.festival.scheduleTimingsPDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'ssection_id':this.schedulesection_id,
            'sdivision_id':this.scheduledivision_id,
            };
        M.api.openPDF('ciniki.musicfestivals.scheduleTimingsPDF',args);
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
        if( i == 'section_id' ) { return this.section_id; }
        if( i == 'groupname' ) { return this.groupname; }
        if( s == 'section_descriptions' ) {
            return this.data[s][i];
        }
        if( this.data[i] == null ) { return ''; }
        return this.data[i]; 
    }
    this.festival.liveSearchCb = function(s, i, v) {
        if( s == 'syllabus_search' && v != '' ) {
            this.liveSearchSS++;
            var sN = this.liveSearchSS;
            M.api.getJSONBgCb('ciniki.musicfestivals.syllabusSearch', {'tnid':M.curTenantID, 'start_needle':v, 'festival_id':this.festival_id, 'limit':'50'}, function(rsp) {
                    if( sN == M.ciniki_musicfestivals_main.festival.liveSearchSS ) {
                        M.ciniki_musicfestivals_main.festival.liveSearchShow(s,null,M.gE(M.ciniki_musicfestivals_main.festival.panelUID + '_' + s), rsp.classes);
                        if( M.ciniki_musicfestivals_main.festival.lastY > 0 ) {
                            window.scrollTo(0,M.ciniki_musicfestivals_main.festival.lastY);
                        }
                    }
                });
        }
        if( (s == 'registration_search' || s == 'video_search') && v != '' ) {
            this.liveSearchRS++;
            var sN = this.liveSearchRS;
            M.api.getJSONBgCb('ciniki.musicfestivals.registrationSearch', {'tnid':M.curTenantID, 'start_needle':v, 'festival_id':this.festival_id, 'limit':'50'}, function(rsp) {
                    if( sN == M.ciniki_musicfestivals_main.festival.liveSearchRS ) {
                        M.ciniki_musicfestivals_main.festival.liveSearchShow(s,null,M.gE(M.ciniki_musicfestivals_main.festival.panelUID + '_' + s), rsp.registrations);
                        if( M.ciniki_musicfestivals_main.festival.lastY > 0 ) {
                            window.scrollTo(0,M.ciniki_musicfestivals_main.festival.lastY);
                        }
                    }
                });
        }
    }
    this.festival.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'syllabus_search' ) { 
            return this.cellValue(s, i, j, d);
        }
        if( s == 'registration_search' ) { 
            return this.cellValue(s, i, j, d);
/*            switch(j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.teacher_name;
                case 3: return '$' + d.fee;
                case 4: return d.status_text;
            } */
        }
        if( s == 'video_search' ) { 
            switch(j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return M.hyperlink(d.video_url1);
                case 3: return d.music_orgfilename;
                case 4: return d.status_text;
            }
        }
    }
    this.festival.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'syllabus_search' ) { 
            return 'M.ciniki_musicfestivals_main.festival.savePos();M.ciniki_musicfestivals_main.class.open(\'M.ciniki_musicfestivals_main.festival.reopen();\',\'' + d.id + '\',0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.classes);';
        }
        if( s == 'registration_search' || s == 'video_search' ) { 
            return 'M.ciniki_musicfestivals_main.festival.savePos();M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.reopen();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.registrations,\'festival\');';
        }
    }
    this.festival.cellValue = function(s, i, j, d) {
        if( s == 'sections' ) {
            switch(j) {
                case 0: return d.name + ((d.flags&0x01) == 0x01 ? ' <span class="subdue">(hidden)</span>' : '');
                case 1: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'syllabus_categories' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
        if( s == 'categories' ) {
            switch(j) {
                case 0: return d.section_name;
                case 1: return d.name;
                case 2: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
/*        if( s == 'syllabus_search' ) {
            switch(j) {
//                case 0: return d.section_name;
                case 0: return d.category_name;
                case 1: return d.code + ' - ' + d.name;
                case 2: return d.earlybird_fee + '/' + d.fee;
                case 3: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        } */
        if( s == 'classes' || s == 'syllabus_search' ) {
            if( this.sections[s].dataMaps[j] == 'num_competitors' ) {
                if( d.min_competitors == d.max_competitors ) {
                    return d.min_competitors;
                }
                return d.min_competitors + ' - ' + d.max_competitors;
            }
            else if( this.sections[s].dataMaps[j] == 'num_titles' ) {
                if( d.min_titles == d.max_titles ) {
                    return d.min_titles;
                }
                return d.min_titles + ' - ' + d.max_titles;
            }
            else if( this.sections[s].dataMaps[j] == 'synopsis' ) {
                return d.synopsis.replace('\n', '<br/>');
            }
            return d[this.sections[s].dataMaps[j]];
        }
        if( s == 'unscheduled_registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + d.titles + '</span>';
                case 2: return d.status_text;
            }
        }
        if( s == 'registrations' || s == 'registration_search' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + d.titles + '</span>';
                case 2: return d.teacher_name + (d.teacher2_name != null && d.teacher2_name != '' ? ', ' + d.teacher2_name : '');
                case 3: return M.multiline(d.invoice_status_text, '$' + d.fee);
                case 4: return M.multiline(d.status_text, d.invoice_date);
            }
            if( j == 5 && (this.data.flags&0x10) == 0x10 ) {
                return (d.participation == 2 ? 'Plus' : '');
            } else if( j == 5 && (this.data.flags&0x02) == 0x02 ) {
                return (d.participation == 1 ? 'Virtual' : 'Live');
            }
        }
        if( s == 'registration_sections' || s == 'emails_sections' ) {
            return M.textCount(d.name, d.num_registrations > 0 ? d.num_registrations: null);
        }
        if( s == 'registration_classes' ) {
            return M.multiline(M.textCount(d.code + ' - ' + d.name, (d.num_registrations > 0 ? d.num_registrations : null)), d.total_perf_time_display);
        }
        if( s == 'registration_teachers' ) {
            return M.textCount(d.display_name, (d.num_registrations > 0 ? d.num_registrations: null));
        }
        if( s == 'registration_accompanists' ) {
            return M.textCount(d.display_name, (d.num_registrations > 0 ? d.num_registrations: null));
        }
        if( s == 'registration_tags' ) {
            return M.textCount(d.name, d.num_registrations > 0 ? d.num_registrations: null);
        }
        if( s == 'registration_members' ) {
            return M.textCount(d.name, d.num_registrations > 0 ? d.num_registrations: null);
        }
        if( s == 'registration_statuses' ) {
            return M.textCount(d.name, d.num_registrations > 0 ? d.num_registrations: null);
        }
//        if( s == 'registration_colours' ) {
//            return M.textCount(d.name, d.num_registrations > 0 ? d.num_registrations: null);
//        }
        if( s == 'schedule_sections' ) {
            switch(j) {
                case 0: return M.multiline(d.name, d.options);
            }
        }
        if( s == 'schedule_locations' ) {
            return d.name;
        }
//        if( s == 'schedule_divisions' && M.ciniki_musicfestivals_main.festival.menutabs.selected == 'comments' ) {
//            return '<span class="maintext">' + d.name + ' <span class="subtext">' + d.division_date_text + '</span>';
//            return '<span class="maintext">' + d.name + ' <span class="subdue">' + d.division_date_text + '</span><span class="subtext">' + d.options + '</span>';
//        }
        if( s == 'schedule_divisions' ) {
//            return '<span class="maintext">' + d.name + ' <span class="subdue">' + d.division_date_text + '</span><span class="subtext">' + d.options + '</span>';
            switch(j) {
                case 0: return M.multiline(d.name, d.options);
                case 1: 
                    if( M.ciniki_musicfestivals_main.festival.isSelected('schedule', 'locations') == 'yes' ) {
                        return M.multiline(d.division_date_text, d.first_timeslot != null ? (d.first_timeslot + ' - ' + d.last_timeslot) : '');
                    }
                    return M.multiline(d.division_date_text, d.location_name);
                case 2: return d.adjudicator_name;
            }
        }
        if( s == 'schedule_timeslots' ) {
            switch(j) {
                case 0: return M.multiline(d.slot_time_text, d.perf_time_text);
                case 1: return '<span class="maintext">' + d.name + (d.groupname != '' ? ' - ' + d.groupname : '') + '</span><span class="subtext">' + d.description.replace(/\n/g, '<br/>') + '</span>';
            }
        }
        if( s == 'schedule_competitors' ) {
            if( j == 0 ) {
                return d.name;
            }
            if( d.timeslots != null && d.timeslots[(j-1)] != null ) {
                return M.multiline(d.timeslots[(j-1)].section_name, d.timeslots[(j-1)]['date_text'] + ' - ' + d.timeslots[(j-1)]['time_text']);
            }
            return '';
        }
        if( s == 'schedule_teachers' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.num_registrations;
            } 
        }
        if( s == 'schedule_accompanists' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.num_registrations;
            } 
        }
        if( s == 'teacher_schedule' ) {
            switch(j) {
                case 0: return d.division_date_text;
                case 1: return d.slot_time_text;
                case 2: return d.location_name;
                case 3: return d.display_name;
                case 4: return d.class_code + ' - ' + d.class_name;
            }
        }
        if( s == 'accompanist_schedule' ) {
            switch(j) {
                case 0: return d.division_date_text;
                case 1: return d.slot_time_text;
                case 2: return d.location_name;
                case 3: return d.display_name;
                case 4: return d.class_code + ' - ' + d.class_name;
            }
        }
        if( s == 'schedule_adjudicators' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.num_completed + ' of ' + d.num_registrations;
            } 
        }
        if( s == 'adjudicator_schedule' ) {
            switch(j) {
                case 0: return d.section_name + ' - ' + d.division_name + (d.timeslot_name != '' ? ' - ' + d.timeslot_name : '');
                case 1: return d.participation == 1 ? 'Virtual' : d.date_text;
                case 2: return d.participation == 1 ? '' : d.time_text;
                case 3: return d.display_name;
                // FIXME: switch to check of which fields are used
                case 4: return d.placement;
            }
        }
        if( s == 'schedule_results' ) {
            switch(j) {
                case 0: return d.slot_time_text;
                case 1: 
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] == 'yes'
                        ) {
                        return d.timeslot_number;
                    }
                    return d.timeslot_sequence;
                case 2: return d.display_name + ((d.flags&0x10) == 0x10 ? '<br/><b>Best In Class</b>' : '');
                case 3: return M.multiline(d.class_code + ' - ' + d.class_name + (d.groupname != '' ? ' - ' + d.groupname : ''), d.titles);
            }
            return d[this.sections[s].dataMaps[j]];
        }
        if( s == 'schedule_provincials' ) {
            switch(j) {
                case 0: return d.provincials_code;
                case 1: return M.multiline(d.provincials_position_text, d.provincials_status_text);
                case 2: return d.display_name;
                case 3: return M.multiline(d.class_code + ' - ' + d.class_name + (d.groupname != '' ? ' - ' + d.groupname : ''), d.titles);
            }
            return d[this.sections[s].dataMaps[j]];
        }
        if( s == 'timeslot_photos' ) {
            if( j == 1 && d.images != null && d.images.length > 0 ) {
                var thumbs = '';
                for(var k in d.images) {
                    thumbs += '<img class="clickable" onclick="M.ciniki_musicfestivals_main.timeslotimage.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.images[k].timeslot_image_id + '\');" width="50px" height="50px" src=\'' + d.images[k].image + '\' />';
                }
                if( d.nophoto_names != null && d.nophoto_names != '' ) {
                    thumbs += '<div class="thumbtext">NO PHOTOS (' + d.nophoto_names + ')</div>';
                }
                return thumbs;
            }
            switch(j) {
                case 0: return M.multiline(d.slot_time_text, d.name + (d.groupname != '' ? ' - ' + d.groupname : ''));
                case 1: return '';
                case 2: return M.faBtn('&#xf030;', 'Photos', 'M.ciniki_musicfestivals_main.festival.timeslotImageAdd(' + d.id + ',' + i + ');');
            }
        }
        if( s == 'timeslot_comments' ) {
            switch(j) {
                case 0: return d.time;
                case 1: return '<span class="maintext">' + d.name + (d.groupname != '' ? ' - ' + d.groupname : '') + '</span><span class="subtext">' + d.description.replace(/\n/g, '<br/>') + '</span>';
                case 2: return d.status;
//                case 3: return d.status2;
//                case 4: return d.status3;
            }
        }
        if( s == 'videos' ) {
            switch (j) {
                case 0: return M.multiline(d.class_code, d.class_name);
                case 1: return M.multiline(d.display_name, d.title);
                case 2: return M.multiline(M.hyperlink(d.video_url), d.music_orgfilename);
                case 3: return d.status_text;
            }
        }
        if( s == 'competitor_cities' ) {
            return M.textCount(d.name, d.num_competitors);
        }
        if( s == 'competitor_provinces' ) {
            return M.textCount(d.name, d.num_competitors);
        }
        if( s == 'competitors' ) {
            if( this.sections[s].dataMaps[j] == 'name' ) {
                return d.name + M.subdue(' (',d.pronoun,')');
            }
            else if( this.sections[s].dataMaps[j] == 'notes' ) {
                return d.notes.replace(/\n/g, '<br/>');
            }
            return d[this.sections[s].dataMaps[j]];
/*            switch(j) {
                case 0: return d.name + M.subdue(' (',d.pronoun,')');
                case 1: return d.classcodes;
                case 2: return d.waiver_signed;
            } */
        }
        if( s == 'invoice_statuses' ) {
            return M.textCount(d.status_text, d.num_invoices);
        }
        if( s == 'invoices' ) {
            switch(j) { 
                case 0: return d.invoice_number;
                case 1: return d.customer_name;
                case 2: return d.competitor_names;
                case 3: return M.formatDollar(d.total_amount);
                case 4: return d.status_text;
            }
            if( j == 5 && d.status < 50 ) {
                return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.festival.invoiceTransaction(\'' + d.id + '\',\'' + M.formatDollar(d.balance_amount) + '\');">Paid</button>';
            }
        }
        if( s == 'adjudicators' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.discipline;
            }
        }
        if( s == 'locations' ) {
            switch(j) {
                case 0: return d.category;
                case 1: return d.name;
                case 2: return d.address1;
                case 3: return d.city;
            }
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
        if( s == 'message_statuses' ) {
            return M.textCount(d.label, d.num_messages);
        }
        if( s == 'messages' ) {
            switch(j) {
                case 0: return d.subject;
                case 1: return d.date_text;
            }
        }
        if( s == 'members' ) {
            switch(j) {
                case 0: return M.multiline(d.shortname, d.name);
//                case 1: return M.multiline(d.category, (d.customer_name != '' ? d.customer_name + ' [' + d.emails + ']' : ''));
                case 1: return M.multiline(d.category, d.admins);
                case 2: return d.reg_start_dt_display;
                case 3: return d.reg_end_dt_display;
                case 4: return d.latedays;
                case 5: return d.num_registrations;
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
                case 1: return d.tags;
            }
        }
        if( s == 'recommendation_members' ) {
            switch(j) { 
                case 0: return d.name;
                case 1: return (d.num_entries > 0 ? d.num_entries : '');
            }
        }
        if( s == 'recommendation_submissions' ) {
            switch(j) { 
                case 0: return d.adjudicator_name;
                case 1: return d.section_name;
                case 2: return d.date_submitted;
                case 3: return d.num_entries;
            }
        }
        if( s == 'recommendation_classes' ) {
            switch(j) { 
                case 0: return d.code + ' - ' + d.name;
                case 1: return (d.num_new > 0 ? d.num_new : '');
                case 2: return (d.num_acceptedreg > 0 ? d.num_acceptedreg : ''); // accepted or registered status
//                case 3: return (d.num_entries > 0 ? d.num_entries : '');
            }
        }
        if( s == 'recommendation_entries' ) {
            switch(j) { 
                case 0: return d.name;
                case 1: return d.position;
                case 2: return d.mark;
                case 3: return d.member_name;
                case 4: return d.date_submitted;
                case 5: return d.end_date + ' [+' + d.latedays + ']';
            }
        }
        if( s == 'sponsors-old' && j == 0 ) {
            return '<span class="maintext">' + d.sponsor.title + '</span>';
        }
        if( s == 'statistics' || s == 'stats_placements' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'stats_cities' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.num_competitors;
                case 2: return d.num_registrations;
            }
        }
        if( s == 'stats_members' ) {
            return d[this.sections[s].dataMaps[j]];
        }
        if( s == 'ssam_sections' ) {
            return d.name;
        }
        if( s == 'ssam_categories' ) {
            return d.name;
        }
        if( s == 'ssam_items' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.song1;
                case 2: return d.song2;
                case 3: return d.song3;
                case 4: return d.song4;
                case 5: return d.song5;
                case 6: return d.song6;
                case 7: return d.song7;
                case 8: return d.song8;
                case 9: return d.song9;
            }
        }
    }
    this.festival.cellSortValue = function(s, i, j, d) {
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
    this.festival.cellFn = function(s, i, j, d) {
        if( s == 'schedule_results' && this.sections.schedule_results.dataMaps[j] == 'mark' ) {
            return 'event.stopPropagation();M.ciniki_musicfestivals_main.festival.registrationMarkChange(\'' + d.id + '\',\'' + escape(d.mark) + '\');';
        }
        return '';
    }
    this.festival.rowFn = function(s, i, d) {
        switch(s) {
            case 'sections': return 'M.ciniki_musicfestivals_main.festival.switchSection(\'' + d.id + '\');'; 
//            case 'sections': return 'M.ciniki_musicfestivals_main.classes.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.sections);';
            case 'syllabus_categories': return 'M.ciniki_musicfestivals_main.festival.switchSyllabusCategory(\'' + d.id + '\');';
            case 'categories': return 'M.ciniki_musicfestivals_main.category.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',\'' + d.section_id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.categories);';
            case 'classes': return 'M.ciniki_musicfestivals_main.class.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.classes);';
            case 'unscheduled_registrations': 
            case 'registrations': 
            case 'videos':
            case 'adjudicator_schedule':
                return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.registrations,\'festival\');';
            case 'registration_sections': return 'M.ciniki_musicfestivals_main.festival.openSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'registration_classes': return 'M.ciniki_musicfestivals_main.festival.switchRegistrationClass(\'' + d.id + '\');';
            case 'emails_sections': return 'M.ciniki_musicfestivals_main.festival.openSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'registration_teachers': return 'M.ciniki_musicfestivals_main.festival.openTeacher(\'' + d.id + '\',"' + M.eU(d.display_name) + '");';
            case 'registration_accompanists': return 'M.ciniki_musicfestivals_main.festival.openAccompanist(\'' + d.id + '\',"' + M.eU(d.display_name) + '");';
            case 'registration_tags': return 'M.ciniki_musicfestivals_main.festival.openTag(\'' + M.eU(d.name) + '\',"' + M.eU(d.name) + '");';
            case 'registration_members': return 'M.ciniki_musicfestivals_main.festival.openMember(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'registration_statuses': return 'M.ciniki_musicfestivals_main.festival.openStatus(\'' + d.status + '\',"' + M.eU(d.name) + '");';
//            case 'registration_colours': return 'M.ciniki_musicfestivals_main.festival.openColour(\'' + d.name + '\',"' + M.eU(d.name) + '");';
            case 'schedule_sections': return 'M.ciniki_musicfestivals_main.festival.openScheduleSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_locations': return 'M.ciniki_musicfestivals_main.festival.openScheduleLocation(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_teachers': return 'M.ciniki_musicfestivals_main.festival.openScheduleTeacher(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_accompanists': return 'M.ciniki_musicfestivals_main.festival.openScheduleAccompanist(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_adjudicators': return 'M.ciniki_musicfestivals_main.festival.openScheduleAdjudicator(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_divisions': return 'M.ciniki_musicfestivals_main.festival.openScheduleDivision(\'' + d.id + '\',"' + M.eU(d.name) + '");';
//            case 'schedule_sections': return 'M.ciniki_musicfestivals_main.schedulesection.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id,null);';
//            case 'schedule_divisions': return 'M.ciniki_musicfestivals_main.scheduledivision.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.schedulesection_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'schedule_timeslots': return 'M.ciniki_musicfestivals_main.scheduletimeslot.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'timeslot_comments': return 'M.ciniki_musicfestivals_main.timeslotcomments.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.scheduledivision_id,M.ciniki_musicfestivals_main.festival.festival_id,null);';
            case 'schedule_results': return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, null, \'festival\');';
            case 'schedule_provincials': return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.festival.festival_id, null,\'festival\');';
            case 'timeslot_photos': return null;
            case 'competitor_cities': return 'M.ciniki_musicfestivals_main.festival.openCompetitorCity(\'' + escape(d.name) + '\');';
            case 'competitor_provinces': return 'M.ciniki_musicfestivals_main.festival.openCompetitorProv(\'' + escape(d.name) + '\');';
            case 'competitors': return 'M.ciniki_musicfestivals_main.competitor.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id);';
            case 'invoice_statuses': return 'M.ciniki_musicfestivals_main.festival.openInvoiceStatus(\'' + d.typestatus + '\');';
            case 'invoices': return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_musicfestivals_main.festival.open();\',\'mc\',{\'invoice_id\':\'' + d.id + '\'});';
            case 'adjudicators': return 'M.ciniki_musicfestivals_main.adjudicator.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.adjudicators);';
            case 'locations': return 'M.ciniki_musicfestivals_main.location.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id, M.ciniki_musicfestivals_main.festival.nplists.locations);';
            case 'certificates': return 'M.ciniki_musicfestivals_main.certificate.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'files': return 'M.ciniki_musicfestivals_main.editfile.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'message_statuses': return 'M.ciniki_musicfestivals_main.festival.openMessageStatus(' + d.status + ');';
            case 'messages': return 'M.ciniki_musicfestivals_main.message.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'members': return 'M.ciniki_musicfestivals_main.member.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.festival.festival_id);';
            case 'lists': return 'M.ciniki_musicfestivals_main.festival.openList(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'listsections': return 'M.ciniki_musicfestivals_main.festival.openListSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'listentries': return 'M.ciniki_musicfestivals_main.listentry.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'sponsors': return 'M.ciniki_musicfestivals_main.sponsor.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'sponsors-old': return 'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_musicfestivals_main.festival.open();\',\'mc\',{\'ref_id\':\'' + d.sponsor.ref_id + '\'});';
            case 'recommendation_members': return 'M.ciniki_musicfestivals_main.festival.switchRecommendationMember(\'' + d.id + '\');';
            case 'recommendation_classes': return 'M.ciniki_musicfestivals_main.festival.switchRecommendationClass(\'' + d.id + '\');';
            case 'recommendation_entries': return 'M.ciniki_musicfestivals_main.recommendationentry.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\',\'' + d.section_id + '\');';
            case 'recommendation_submissions': return 'M.ciniki_musicfestivals_main.recommendation.open(\'M.ciniki_musicfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'ssam_sections': return 'M.ciniki_musicfestivals_main.festival.ssamSectionSwitch(\'' + M.eU(d.name) + '\');';
            case 'ssam_categories': return 'M.ciniki_musicfestivals_main.festival.ssamCategorySwitch(\'' + M.eU(d.name) + '\');';
            case 'ssam_items': return 'M.ciniki_musicfestivals_main.ssamitem.open(\'M.ciniki_musicfestivals_main.festival.open();\',M.ciniki_musicfestivals_main.festival.sections.ssam_sections.selected,M.ciniki_musicfestivals_main.festival.sections.ssam_categories.selected,\'' + M.eU(d.name) + '\',M.ciniki_musicfestivals_main.festival.festival_id);';
        }
        return '';
    }
    this.festival.rowClass = function(s, i, d) {
        if( s == 'sections' && this.section_id == d.id ) {
            return 'highlight';
        }
        if( s == 'syllabus_categories' && this.category_id == d.id ) {
            return 'highlight';
        }
        if( s == 'competitor_cities' && this.city_prov == d.name ) {
            return 'highlight';
        }
        if( s == 'competitor_provinces' && this.province == d.name ) {
            return 'highlight';
        }
        if( s == 'schedule_sections' && this.schedulesection_id == d.id ) {
            return 'highlight';
        }
        if( s == 'schedule_locations' && this.schedulelocation_id == d.id ) {
            return 'highlight';
        }
        if( s == 'schedule_divisions' && this.scheduledivision_id == d.id ) {
            return 'highlight';
        }
        if( s == 'schedule_teachers' && this.teacher_customer_id == d.id ) {
            return 'highlight';
        }
        if( s == 'schedule_accompanists' && this.accompanist_customer_id == d.id ) {
            return 'highlight';
        }
        if( s == 'schedule_adjudicators' && this.adjudicator_id == d.id ) {
            return 'highlight';
        }
        if( (s == 'registration_sections' || s == 'emails_sections') && this.section_id == d.id ) {
            return 'highlight';
        }
        if( s == 'registration_classes' && this.class_id == d.id ) {
            return 'highlight';
        }
        if( s == 'registration_teachers' && this.teacher_customer_id == d.id ) {
            return 'highlight';
        }
        if( s == 'registration_accompanists' && this.accompanist_customer_id == d.id ) {
            return 'highlight';
        }
        if( s == 'registration_tags' && this.registration_tag == d.name ) {
            return 'highlight';
        }
        if( s == 'registration_members' && this.member_id == d.id ) {
            return 'highlight';
        }
        if( s == 'registration_statuses' && this.registration_status == d.name ) {
            return 'highlight';
        }
        if( s == 'registrations' 
            && d.status != null 
            && this.data['registration-status-' + d.status + '-colour'] != null 
            && this.data['registration-status-' + d.status + '-colour'] != '' 
            && this.data['registration-status-' + d.status + '-colour'] != '#ffffff' 
            ) {
            return 'colored';
        }
//        if( s == 'registration_colours' && this.colour == d.name ) {
//            return 'highlight';
//        }
        if( s == 'lists' && this.list_id == d.id ) {
            return 'highlight';
        }
        if( s == 'listsections' && this.listsection_id == d.id ) {
            return 'highlight';
        }
        if( s == 'message_statuses' && this.messages_status == d.status ) {
            return 'highlight';
        }
        if( s == 'invoice_statuses' && this.invoice_typestatus == d.typestatus ) {
            return 'highlight';
        }
        if( s == 'invoices' && this.invoice_typestatus == '' && s == 'invoices' ) {
            switch(d.status) {  
                case '10': return 'statusorange';
                case '15': return 'statusorange';
                case '40': return 'statusorange';
                case '42': return 'statusred';
                case '50': return 'statusgreen';
                case '55': return 'statusorange';
                case '60': return 'statusgrey';
                case '65': return 'statusgrey';
            }
        }
        if( s == 'recommendation_members' && this.member_id == d.id ) {
            return 'highlight';
        }
        if( s == 'recommendation_classes' && this.class_id == d.id ) {
            return 'highlight';
        }
        if( s == 'recommendation_entries' ) {
            switch(d.status) {
                case '10': 
                    if( d.position == '1st Alternate' || d.position == '2nd Alternate' || d.position == '3rd Alternate' ) {
                        return 'statusyellow';
                    }
                    return '';
                case '30': return 'statusorange';
                case '50': return 'statusgreen';
                case '70': return 'statusred';
                case '90': return 'statusred';
            }
        }
        if( s == 'ssam_sections' && this.sections.ssam_sections.selected == d.name ) {
            return 'highlight';
        }
        if( s == 'ssam_categories' && this.sections.ssam_categories.selected == d.name ) {
            return 'highlight';
        }
        return '';

    }
    this.festival.rowStyle = function(s, i, d) {
        if( s == 'registrations' || s == 'unscheduled_registrations' ) {
            return M.ciniki_musicfestivals_main.regStatusColour(this.data, d);
        }
        return '';
    }
    this.festival.emailMembers = function() {
        var customers = [];
        for(var i in this.data.members) {
            if( this.data.members[i].customer_id > 0 ) {
                customers[i] = {
                    'id':this.data.members[i].customer_id,
                    'name':this.data.members[i].customer_name,
                    };
            }
        }
        M.startApp('ciniki.mail.omessage',
            null,
            'M.ciniki_musicfestivals_main.festival.open();',
            'mc',
            {'subject':'',
                'list':customers, 
//                'object':'ciniki.customers.',
//                'object_id':this.offering_id,
                'removeable':'yes',
            });
        
    }
    this.festival.switchTab = function(tab, stab) {
        if( tab != null ) { this.menutabs.selected = tab; }
        if( stab != null ) { this.sections._stabs.selected = stab; }
        this.open();
//        this.updateClasses();
//        this.refreshSections(['_stabs', 'classes']);
//        this.liveSearchCb('syllabus_search', 0, M.gE(this.panelUID + '_syllabus_search').value);
    }
    this.festival.switchMTab = function(t) {
        this.sections._moretabs.selected = t;
        this.open();
    }
    this.festival.switchSTab = function(t) {
        this.sections.schedule_tabs.selected = t;
        this.open();
    }
    this.festival.switchStatsTab = function(t) {
        this.sections.stats_tabs.selected = t;
        this.open();
    }
    this.festival.switchSyllabus = function(s) {
        this.sections.syllabi_tabs.selected = s;
        this.open();
    }
    this.festival.switchSyllabusList = function(l) {
        this.sections.syllabus_tabs.selected = l;
        this.open();
    }
    this.festival.switchSyllabusSection = function(s,i) {
        this.section_id = this.formValue(i);
        this.category_id = 0;
        this.groupname = 'all';
        this.open();
    }
    this.festival.switchSyllabusGroup = function(s, i) {
        this.groupname = this.formValue(i);
        this.category_id = 0;
        this.open();
    }
    this.festival.switchSyllabusCategory = function(c) {
        this.lastY = 0;
        this.category_id = c;
        this.open();
    }
    this.festival.switchSection = function(sid) {
        this.section_id = sid;
        this.open();
    }
    this.festival.updateFees = function(sid, label, field) {
        if( sid == 0 ) {
            this.popupMenuClose('sections');
            var str = 'Enter how much to add to each ' + label + ' fee in all classes:';
        } else {
            this.popupMenuClose('classes');
            var str = 'Enter how much to add to each ' + label + ' fee in the list:';
            for(var i in this.data.sections) {
                if( this.data.sections[i].id == sid ) {
                    var str = 'Enter how much to add to each ' + label + ' fee in ' + this.data.sections[i].name + ' classes:';
                    break;
                }
            }
        }
        M.prompt(str, '', 'Update', function(n) {
            if( n != 0 && n != '0' && n != '' ) {
                var args = {
                    'tnid':M.curTenantID, 
                    'section_id':sid,
                    'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                    }; 
                if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                    args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
                }
                args[field+'_update'] = n;
                M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.festival.open();
                    });
            }
        });
    }
    this.festival.updateInstrument = function(sid, yesno) {
        var msg = "Are you sure you remove the instrument field for these classes?";
        if( yesno == 'yes' ) {
            msg = "Are you sure you enable the instrument field for these classes?";
        }
        M.confirm(msg, "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'instrument':yesno,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.setAccompanist = function(sid, label) {
        M.confirm("Are you sure you want to update Accompanist to " + label + "?", "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'accompanist':label,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.updateMultireg = function(sid, yesno) {
        var msg = "Are you sure you do NOT want to allow competitors to register multiple times for these classes?";
        if( yesno == 'yes' ) {
            msg = "Are you sure you want to allow competitors to register multiple times for these classes?";
        }
        M.confirm(msg, "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'multireg':yesno,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.setMovements = function(sid, label) {
        M.confirm("Are you sure you want to update Movements to " + label + "?", "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'movements':label,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.setComposer = function(sid, label) {
        M.confirm("Are you sure you want to update Composer to " + label + "?", "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'composer':label,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.setBacktrack = function(sid, label) {
        M.confirm("Are you sure you want to update Backtrack to " + label + "?", "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'backtrack':label,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.setArtwork = function(sid, label) {
        M.confirm("Are you sure you want to update Artwork to " + label + "?", "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'artwork':label,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.setVideo = function(sid, label) {
        M.confirm("Are you sure you want to update Virtual Video to " + label + "?", "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'video':label,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.setMusic = function(sid, label) {
        M.confirm("Are you sure you want to update Virtual Music to " + label + "?", "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':sid,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'music':label,
                }; 
            if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
            }
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
            });
    }
    this.festival.registrationMarkChange = function(rid, m) {
        m = unescape(m);
        var label = 'Mark';
        if( this.data['comments-mark-label'] != null && this.data['comments-mark-label'] != '' ) {
            var label = this.data['comments-mark-label'];
        }
        M.prompt('New ' + label + ':', '', 'Update', function(n) {
            if( m != n ) {
                var args = {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.festival.festival_id, 'registration_id':rid, 'mark':n};
                var p = M.ciniki_musicfestivals_main.placementAutofill(n);
                if( p != '' ) {
                    args['placement'] = p;
                }
                var l = M.ciniki_musicfestivals_main.levelAutofill(n);
                if( l != '' ) {
                    args['level'] = l;
                }
                if( M.ciniki_musicfestivals_main.festival.sections.syllabus_tabs.selected == 'categories' ) {
                    args['category_id'] = M.ciniki_musicfestivals_main.festival.category_id;
                }
                M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', args, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.festival.open();
                });
            }
        });
    }
    this.festival.switchRegTab = function(t) {
        this.sections.registration_tabs.selected = t;
        this.open();
    }
    this.festival.switchRecommendationTab = function(t) {
        this.sections.recommendation_tabs.selected = t;
        this.open();
    }
    this.festival.switchRecommendationMember = function(m) {
        this.lastY = 0;
        this.member_id = m;
        this.open();
    }
    this.festival.switchRecommendationSection = function(s,i) {
        this.section_id = this.formValue(i);
        this.class_id = 0;
        this.open();
    }
    this.festival.switchRegistrationSection = function(s,i) {
        this.section_id = this.formValue(i);
        this.class_id = 0;
        this.open();
    }
    this.festival.switchRegistrationClass = function(c) {
        this.class_id = c;
        this.open();
    }
    this.festival.switchRecommendationClass = function(c) {
        this.class_id = c;
        this.open();
    }
    this.festival.switchCompTab = function(t) {
        this.sections.competitor_tabs.selected = t;
        this.open();
    }
    this.festival.switchCompsTab = function(t) {
        this.sections.competitors_tabs.selected = t;
        this.open();
    }
    this.festival.switchLVTab = function(t) {
        this.sections.ipv_tabs.selected = t;
        this.open();
    }
    this.festival.switchEmailsTab = function(t) {
        this.sections.emails_tabs.selected = t;
        this.open();
    }
    this.festival.emailTeacherRegistrations = function() {
        M.ciniki_musicfestivals_main.emailregistrations.open('M.ciniki_musicfestivals_main.festival.show();');
    }
    this.festival.openSection = function(id,n) {
        this.lastY = 0;
        this.section_id = id;
        this.teacher_customer_id = 0;
        this.accompanist_customer_id = 0;
        this.registration_tag = '';
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
//            this.sections.emails_list.label = 'Emails - ' + M.dU(n);
            this.sections.emails_html.label = 'Emails - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
//            this.sections.emails_list.label = 'Emails';
            this.sections.emails_html.label = 'Emails';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openTeacher = function(id,n) {
        this.lastY = 0;
        this.teacher_customer_id = id;
        this.accompanist_customer_id = 0;
        this.section_id = 0;
        this.registration_tag = '';
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openAccompanist = function(id,n) {
        this.lastY = 0;
        this.teacher_customer_id = 0;
        this.accompanist_customer_id = id;
        this.section_id = 0;
        this.registration_tag = '';
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openTag = function(name, n) {
        this.lastY = 0;
        this.section_id = 0;
        this.teacher_customer_id = 0;
        this.accompanist_customer_id = 0;
        this.registration_tag = unescape(name);
        if( name != '' ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openMember = function(i, n) {
        this.lastY = 0;
        this.section_id = 0;
        this.teacher_customer_id = 0;
        this.accompanist_customer_id = 0;
        this.member_id = i;
        if( n != '' ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openStatus = function(i, n) {
        this.lastY = 0;
        this.section_id = 0;
        this.teacher_customer_id = 0;
        this.accompanist_customer_id = 0;
        this.member_id = 0;
        this.registration_status = i;
        if( n != '' ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    }
/*    this.festival.openColour = function(i, n) {
        this.lastY = 0;
        this.section_id = 0;
        this.teacher_customer_id = 0;
        this.accompanist_customer_id = 0;
        this.member_id = 0;
        this.colour = n;
        if( n != '' ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
            this.sections.videos.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
            this.sections.videos.label = 'Registrations';
        }
        this.open();
    } */
    this.festival.openScheduleSection = function(i, n) {
        this.schedulesection_id = i;
        this.sections.schedule_divisions.label = M.dU(n);
        this.scheduledivision_id = 0;
        this.open();
    }
    this.festival.openScheduleLocation = function(i, n) {
        this.schedulelocation_id = i;
        this.sections.schedule_divisions.label = M.dU(n);
        this.scheduledivision_id = 0;
        this.open();
    }
    this.festival.openScheduleDivision = function(i, n) {
        this.lastY = 0;
        this.scheduledivision_id = i;
        this.sections.schedule_timeslots.label = M.dU(n) + ' Time Slots';
        this.open();
    }
    this.festival.openScheduleTeacher = function(i, n) {
        this.teacher_customer_id = i;
        this.sections.teacher_schedule.label = M.dU(n);
        this.open();
    }
    this.festival.openScheduleAccompanist = function(i, n) {
        this.accompanist_customer_id = i;
        this.sections.accompanist_schedule.label = M.dU(n);
        this.open();
    }
    this.festival.openScheduleAdjudicator = function(i, n) {
        this.adjudicator_id = i;
        this.sections.adjudicator_schedule.label = M.dU(n);
        this.open();
    }
    this.festival.openList = function(i, n) {
        this.list_id = i;
        this.sections.listsections.label = M.dU(n);
        this.scheduledivision_id = 0;
        this.open();
    }
    this.festival.openListSection = function(i, n) {
        this.lastY = 0;
        this.listsection_id = i;
        this.sections.listentries.label = M.dU(n);
        this.open();
    }
    this.festival.downloadExcel = function(fid) {
        var args = {
            'tnid':M.curTenantID, 
            'festival_id':fid,
            'ipv':this.sections.ipv_tabs.selected
            };
        if( this.sections.registration_tabs.selected == 'sections' && this.section_id > 0 ) {
            this.popupMenuClose('registration_sections');
            args['section_id'] = this.section_id;
        } else if( this.sections.registration_tabs.selected == 'classes' && this.section_id > 0 && this.class_id > 0 ) {
            args['section_id'] = this.section_id;
            args['class_id'] = this.class_id;
        } else if( this.sections.registration_tabs.selected == 'classes' && this.section_id > 0 ) {
            args['section_id'] = this.section_id;
        } else if( this.sections.registration_tabs.selected == 'teachers' && this.teacher_customer_id > 0 ) {
            args['teacher_customer_id'] = this.teacher_customer_id;
        } else if( this.sections.registration_tabs.selected == 'accompanists' && this.accompanist_customer_id > 0 ) {
            args['accompanist_customer_id'] = this.accompanist_customer_id;
        } else if( this.sections.registration_tabs.selected == 'tags' && this.registration_tag != '' ) {
            args['registration_tag'] = this.registration_tag;
        } else if( this.sections.registration_tabs.selected == 'members' && this.member_id > 0 ) {
            args['member_id'] = this.member_id;
        }
        M.api.openFile('ciniki.musicfestivals.registrationsExcel', args);
    }
    this.festival.downloadPDF = function(fid) {
        this.popupMenuClose('registration_sections');
        if( this.sections.registration_tabs.selected == 'sections' && this.section_id > 0 ) {
            M.api.openFile('ciniki.musicfestivals.registrationsPDF', {'tnid':M.curTenantID, 'festival_id':fid, 'section_id':this.section_id});
        } else {
            M.api.openFile('ciniki.musicfestivals.registrationsPDF', {'tnid':M.curTenantID, 'festival_id':fid});
        }
    }
    this.festival.downloadWord = function(fid) {
        this.popupMenuClose('registration_sections');
        if( this.sections.registration_tabs.selected == 'sections' && this.section_id > 0 ) {
            M.api.openFile('ciniki.musicfestivals.registrationsWord', {'tnid':M.curTenantID, 'festival_id':fid, 'section_id':this.section_id});
        } else {
            M.api.openFile('ciniki.musicfestivals.registrationsWord', {'tnid':M.curTenantID, 'festival_id':fid});
        }
    }
    this.festival.downloadTrophiesPDF = function(fid) {
        M.api.openFile('ciniki.musicfestivals.trophyRegistrationsPDF', {'tnid':M.curTenantID, 'festival_id':fid});
    }
    this.festival.downloadTrophyMarksPDF = function() {
        M.api.openFile('ciniki.musicfestivals.trophyRegistrationsPDF', {
            'tnid':M.curTenantID, 
            'festival_id':M.ciniki_musicfestivals_main.festival.festival_id, 
            'marks':'yes',
            });
    }
    this.festival.openInvoiceStatus = function(s) {
        this.lastY = 0;
        this.invoice_typestatus = s;
        this.open();
    }
    this.festival.invoiceTransaction = function(i, t) {
        M.startApp('ciniki.sapos.invoice', null, 'M.ciniki_musicfestivals_main.festival.open();', 'mc', {'invoice_id':i, 'transaction_amount':t});
    }
    this.festival.openCompetitorCity = function(c) {
        this.lastY = 0;
        this.city_prov = unescape(c);
        this.open();
    }
    this.festival.openCompetitorProv = function(c) {
        this.lastY = 0;
        this.province = unescape(c);
        this.open();
    }
    this.festival.openMessageStatus = function(s) {
        this.messages_status = s;
        this.open();
    }
    this.festival.isVirtual = function() {
        if( (this.data.flags&0x02) == 0x02 ) {
            return 'yes';
        }
        return 'no';
    }
    this.festival.ssamSectionSwitch = function(s) {
        this.sections.ssam_sections.selected = M.dU(s);
        this.sections.ssam_categories.selected = '';
        this.open();
    }
    this.festival.ssamCategorySwitch = function(c) {
        this.sections.ssam_categories.selected = M.dU(c);
        this.open();
    }
    this.festival.updateClasses = function() {
        // Syllabus class lists
        this.sections.classes.cellClasses = ['', '', ''];
        this.sections.classes.dataMaps = ['category_name', 'code', 'name'];
        this.sections.classes.sortTypes = ['text', 'text', 'text'];
        this.sections.classes.num_cols = 3;
        this.sections.classes.headerValues = ['Category', 'Code', 'Class'];
        if( this.sections._stabs.selected == 'fees' ) {
            if( (this.data.flags&0x20) == 0x20 ) {
                this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Earlybird';
                this.sections.classes.cellClasses[this.sections.classes.num_cols] = 'alignright';
                this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'earlybird_fee';
                this.sections.classes.sortTypes[this.sections.classes.num_cols] = 'number';
                this.sections.classes.num_cols++; 
            }
            this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Fee';
            this.sections.classes.cellClasses[this.sections.classes.num_cols] = 'alignright';
            this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'fee';
            this.sections.classes.sortTypes[this.sections.classes.num_cols] = 'number';
            this.sections.classes.num_cols++; 
            if( (this.data.flags&0x04) == 0x04 ) {
                this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Virtual';
                this.sections.classes.cellClasses[this.sections.classes.num_cols] = 'alignright';
                this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'virtual_fee';
                this.sections.classes.sortTypes[this.sections.classes.num_cols] = 'number';
                this.sections.classes.num_cols++; 
            }
            if( (this.data.flags&0x30) == 0x30 ) {
                this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Early Plus';
                this.sections.classes.cellClasses[this.sections.classes.num_cols] = 'alignright';
                this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'earlybird_plus_fee';
                this.sections.classes.sortTypes[this.sections.classes.num_cols] = 'number';
                this.sections.classes.num_cols++; 
            }
            if( (this.data.flags&0x10) == 0x10 ) {
                this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Plus Fee';
                this.sections.classes.cellClasses[this.sections.classes.num_cols] = 'alignright';
                this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'plus_fee';
                this.sections.classes.sortTypes[this.sections.classes.num_cols] = 'number';
                this.sections.classes.num_cols++; 
            }
        }
        else if( this.sections._stabs.selected == 'competitors' ) {
            this.sections.classes.headerValues = ['Category', 'Code', 'Class', '#', 'Competitors', 'Instrument'];
            this.sections.classes.cellClasses = ['', '', '', 'alignright', '', 'aligncenter'];
            this.sections.classes.dataMaps = ['category_name', 'code', 'name', 'num_competitors', 'competitor_type', 'instrument'];
            this.sections.classes.sortTypes = ['text', 'text', 'text', 'number', 'text', 'text'];
            this.sections.classes.num_cols = 6;
            if( M.modFlagOn('ciniki.musicfestivals', 0x8000) ) {
                this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Accompanist';
                this.sections.classes.cellClasses[this.sections.classes.num_cols] = 'aligncenter';
                this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'accompanist';
                this.sections.classes.sortTypes[this.sections.classes.num_cols] = 'text';
                this.sections.classes.num_cols++; 
            }
            this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Multi';
            this.sections.classes.cellClasses[this.sections.classes.num_cols] = 'aligncenter';
            this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'multireg';
            this.sections.classes.sortTypes[this.sections.classes.num_cols] = 'text';
            this.sections.classes.num_cols++; 
        }
        else if( this.sections._stabs.selected == 'titles' ) {
            this.sections.classes.headerValues = ['Category', 'Code', 'Class', 'Titles', 'Movements', 'Composer', 'Backtrack', 'Art'];
            this.sections.classes.cellClasses = ['', '', '', 'aligncenter', '', '', '', '', ''];
            this.sections.classes.dataMaps = ['category_name', 'code', 'name', 'num_titles', 'movements', 'composer', 'backtrack', 'artwork'];
            this.sections.classes.sortTypes = ['text', 'text', 'text', 'number', 'text', 'text', 'text', 'text'];
            this.sections.classes.num_cols = 8;
            if( (this.data.flags&0x02) == 0x02 ) {
                this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Video';
                this.sections.classes.cellClasses[this.sections.classes.num_cols] = '';
                this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'video';
                this.sections.classes.sortTypes[this.sections.classes.num_cols] = '';
                this.sections.classes.num_cols++; 
                this.sections.classes.headerValues[this.sections.classes.num_cols] = 'Music';
                this.sections.classes.cellClasses[this.sections.classes.num_cols] = '';
                this.sections.classes.dataMaps[this.sections.classes.num_cols] = 'music';
                this.sections.classes.sortTypes[this.sections.classes.num_cols] = '';
                this.sections.classes.num_cols++; 
            }
        }
        else if( this.sections._stabs.selected == 'levels' ) {
            this.sections.classes.headerValues = ['Category', 'Code', 'Class', 'Levels'];
            this.sections.classes.cellClasses = ['', '', '', ''];
            this.sections.classes.dataMaps = ['category_name', 'code', 'name', 'levels'];
            this.sections.classes.sortTypes = ['text', 'text', 'text', 'text'];
            this.sections.classes.num_cols = 4;
        }
        else if( this.sections._stabs.selected == 'marking' ) {
            this.sections.classes.headerValues = ['Category', 'Code', 'Class', 'Mark', 'Placement', 'Level', 'Provincials'];
            this.sections.classes.cellClasses = ['', '', '', '', '', '', ''];
            this.sections.classes.dataMaps = ['category_name', 'code', 'name', 'mark', 'placement', 'level', 'provincials_code'];
            this.sections.classes.sortTypes = ['text', 'text', 'text', 'text', 'text', 'text', 'text'];
            this.sections.classes.num_cols = 7;
        }
        else if( this.sections._stabs.selected == 'trophies' ) {
            this.sections.classes.headerValues = ['Category', 'Code', 'Class', 'Trophies & Awards'];
            this.sections.classes.cellClasses = ['', '', '', ''];
            this.sections.classes.dataMaps = ['category_name', 'code', 'name', 'trophies'];
            this.sections.classes.sortTypes = ['text', 'text', 'text', 'text'];
            this.sections.classes.num_cols = 4;
        }
        else if( this.sections._stabs.selected == 'scheduling' ) {
            this.sections.classes.headerValues = ['Category', 'Code', 'Class', 'Scheduling', 'Time', 'Talk', 'Total', '# Reg'];
            this.sections.classes.cellClasses = ['', '', '', '', '', '', '', 'aligncenter'];
            this.sections.classes.dataMaps = ['category_name', 'code', 'name', 'schedule_type', 'schedule_time', 'talk_time', 'total_time', 'num_registrations'];
            this.sections.classes.sortTypes = ['text', 'text', 'text', 'text', 'text', 'text', 'text', 'number'];
            this.sections.classes.num_cols = 5;
            if( this.data['scheduling-at-times'] != null && this.data['scheduling-at-times'] == 'yes' ) {
                this.sections.classes.num_cols+=3;
            }
        } 
        else if( this.sections._stabs.selected == 'synopsis' ) {
            this.sections.classes.headerValues = ['Category', 'Code', 'Class', 'Synopsis'];
            this.sections.classes.cellClasses = ['', '', '', ''];
            this.sections.classes.dataMaps = ['category_name', 'code', 'name', 'synopsis'];
            this.sections.classes.sortTypes = ['text', 'text', 'text', 'text'];
            this.sections.classes.num_cols = 4;
        }
        if( (this.data.flags&0x0400) == 0x0400 ) {
            this.sections.classes.headerValues.unshift('Group');
            this.sections.classes.cellClasses.unshift('');
            this.sections.classes.dataMaps.unshift('groupname');
            this.sections.classes.sortTypes.unshift('text');
            this.sections.classes.num_cols++;
        }
        this.sections.classes.headerClasses = this.sections.classes.cellClasses;
        // Syllabus search results
        this.sections.syllabus_search.headerValues = [];
        this.sections.syllabus_search.cellClasses = [];
        this.sections.syllabus_search.dataMaps = [];
        for(var i in this.sections.classes.headerValues) {
            this.sections.syllabus_search.headerValues[i] = this.sections.classes.headerValues[i];
            this.sections.syllabus_search.cellClasses[i] = this.sections.classes.cellClasses[i];
            this.sections.syllabus_search.dataMaps[i] = this.sections.classes.dataMaps[i];
        }
        this.sections.syllabus_search.headerValues.unshift('Section');
        this.sections.syllabus_search.cellClasses.unshift('');
        this.sections.syllabus_search.dataMaps.unshift('section_name');
        this.sections.syllabus_search.headerClasses = this.sections.syllabus_search.cellClasses;
        this.sections.syllabus_search.livesearchcols = this.sections.syllabus_search.dataMaps.length;
    }
    this.festival.updateCompetitors = function() {
/*        'competitors':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'competitors' ? 'yes' : 'no'; },
            'headerValues':['Name', 'Classes', 'Waiver'],
            },
                case 0: return d.name + M.subdue(' (',d.pronoun,')');
                case 1: return d.classcodes;
                case 2: return d.waiver_signed; */
        // Syllabus class lists
        this.sections.competitors.cellClasses = [''];
        this.sections.competitors.dataMaps = ['name'];
        this.sections.competitors.sortTypes = ['text'];
        this.sections.competitors.num_cols = 1;
        this.sections.competitors.headerValues = ['Name'];
        if( this.sections.competitors_tabs.selected == 'classes' ) {
            this.sections.competitors.headerValues.push('Classes');
            this.sections.competitors.cellClasses.push('');
            this.sections.competitors.dataMaps.push('classcodes');
            this.sections.competitors.sortTypes.push('text');
            this.sections.competitors.num_cols++;
        } else if( this.sections.competitors_tabs.selected == 'notes' ) {
            this.sections.competitors.headerValues.push('Waiver');
            this.sections.competitors.cellClasses.push('');
            this.sections.competitors.dataMaps.push('waiver_signed');
            this.sections.competitors.sortTypes.push('text');
            this.sections.competitors.num_cols++;

            if( this.data['waiver-photo-status'] != null 
                && (this.data['waiver-photo-status'] == 'internal' || this.data['waiver-photo-status'] == 'on')
                ) {
                this.sections.competitors.headerValues.push('Photos');
                this.sections.competitors.cellClasses.push('');
                this.sections.competitors.dataMaps.push('photos');
                this.sections.competitors.sortTypes.push('text');
                this.sections.competitors.num_cols++;
            }
            if( this.data['waiver-name-status'] != null 
                && (this.data['waiver-name-status'] == 'internal' || this.data['waiver-name-status'] == 'on')
                ) {
                this.sections.competitors.headerValues.push('Name');
                this.sections.competitors.cellClasses.push('');
                this.sections.competitors.dataMaps.push('name_published');
                this.sections.competitors.sortTypes.push('text');
                this.sections.competitors.num_cols++;
            }
            this.sections.competitors.headerValues.push('Notes');
            this.sections.competitors.cellClasses.push('');
            this.sections.competitors.dataMaps.push('notes');
            this.sections.competitors.sortTypes.push('text');
            this.sections.competitors.num_cols++;
        }
        this.sections.competitors.headerClasses = this.sections.competitors.cellClasses;
    }
    this.festival.reopen = function(cb,fid,list) {
        if( this.menutabs.selected == 'syllabus' ) {
            if( M.gE(this.panelUID + '_syllabus_search').value != '' ) {
                this.sections.syllabus_search.lastsearch = M.gE(this.panelUID + '_syllabus_search').value;
            }
        }
        if( this.menutabs.selected == 'registrations' ) {
            if( M.gE(this.panelUID + '_registration_search').value != '' ) {
                this.sections.registration_search.lastsearch = M.gE(this.panelUID + '_registration_search').value;
            }
        }
        this.open(cb,fid,list);
    }
    this.festival.open = function(cb, fid, list) {
        if( fid != null ) { 
            if( this.festival_id != fid ) {
                this.section_id = -1;
                this.sections.syllabi_tabs.selected = null;
            }
            this.festival_id = fid; 
        }
        var args = {'tnid':M.curTenantID, 'festival_id':this.festival_id};
        this.size = 'xlarge narrowaside';
        if( this.menutabs.selected == 'syllabus' ) {
            args['sections'] = 'yes';
            args['section_id'] = this.section_id;
            args['classes'] = 'yes';
            if( this.sections._stabs.selected == 'levels' ) {
                args['levels'] = 'yes';
            } else if( this.sections._stabs.selected == 'trophies' ) {
                args['trophies'] = 'yes';
            } else if( this.sections._stabs.selected == 'descriptions' ) {
                args['descriptions'] = 'yes';
                args['classes'] = 'no';
            }
            if( this.sections.syllabi_tabs.selected != null ) {
                args['syllabus'] = this.sections.syllabi_tabs.selected;
            }
            if( this.sections.syllabus_tabs.selected == 'categories' && this.section_id > 0 ) {
                args['categories'] = 'yes';
                args['category_id'] = this.category_id;
                args['groups'] = 'yes';
                if( this.groupname != 'all' ) {
                    args['groupname'] = this.groupname;
                }
            }
        } else if( this.menutabs.selected == 'registrations' || this.menutabs.selected == 'videos' ) {
            this.size = 'xlarge mediumaside';
            args['registrations'] = 'yes';
            args['ipv'] = this.sections.ipv_tabs.selected;
                args['sections'] = 'yes';
            if( this.sections.registration_tabs.selected == 'sections' ) {
                args['sections'] = 'yes';
            } else if( this.sections.registration_tabs.selected == 'classes' ) {
                args['sections'] = 'yes';
                args['section_id'] = this.section_id;
                args['class_id'] = this.class_id;
            } else if( this.sections.registration_tabs.selected == 'teachers' && this.teacher_customer_id > 0 ) {
                args['teacher_customer_id'] = this.teacher_customer_id;
            } else if( this.sections.registration_tabs.selected == 'accompanists' && this.accompanist_customer_id > 0 ) {
                args['accompanist_customer_id'] = this.accompanist_customer_id;
            } else if( this.sections.registration_tabs.selected == 'members' ) {
                args['member_id'] = this.member_id;
            } else if( this.sections.registration_tabs.selected == 'tags' && this.registration_tag != '' ) {
                args['registration_tag'] = this.registration_tag;
            } else if( this.sections.registration_tabs.selected == 'statuses' ) {
                args['registration_status'] = this.registration_status;
//            } else if( this.sections.registration_tabs.selected == 'colours' ) {
//                args['colour'] = this.colour;
            }
            if( this.section_id == 0 ) {
                args['section_id'] = 0;
            }
            args['registrations_list'] = this.sections.registration_tabs.selected;
        } else if( this.menutabs.selected == 'schedule' ) {
            this.size = 'xlarge mediumaside';
            args['schedule'] = 'yes';
            args['ssection_id'] = this.schedulesection_id;
            args['sdivision_id'] = this.scheduledivision_id;
            if( this.sections.schedule_tabs.selected == 'timeslots' ) {
//                this.size = 'xlarge mediumaside columns';
            } else if( this.sections.schedule_tabs.selected == 'locations' ) {
                delete args['ssection_id'];
                args['locations'] = 'yes';
                args['location_id'] = this.schedulelocation_id;
            } else if( this.sections.schedule_tabs.selected == 'comments' ) {
                args['comments'] = 'yes';
            } else if( this.sections.schedule_tabs.selected == 'photos' ) {
                args['photos'] = 'yes';
            } else if( this.sections.schedule_tabs.selected == 'results' ) {
                args['results'] = 'yes';
            } else if( this.sections.schedule_tabs.selected == 'provincials' ) {
                args['provincials'] = 'yes';
            } else if( this.sections.schedule_tabs.selected == 'competitors' ) {
                this.size = 'full';
                args['schedule'] = 'competitors';
            } else if( this.sections.schedule_tabs.selected == 'adjudicators' ) {
                args['schedule'] = 'adjudicators';
                args['ipv'] = this.sections.ipv_tabs.selected;
                args['adjudicator_id'] = this.adjudicator_id;
            } else if( this.sections.schedule_tabs.selected == 'teachers' ) {
                this.size = 'xlarge mediumaside';
                args['schedule'] = 'teachers';
                args['teacher_customer_id'] = this.teacher_customer_id;
            } else if( this.sections.schedule_tabs.selected == 'accompanists' ) {
                this.size = 'xlarge mediumaside';
                args['schedule'] = 'accompanists';
                args['accompanist_customer_id'] = this.accompanist_customer_id;
            }
            this.sections.schedule_divisions.num_cols = 2;
            if( M.modFlagOn('ciniki.musicfestivals', 0x0800) ) {
                this.sections.schedule_divisions.num_cols = 3;
            }
        } else if( this.menutabs.selected == 'competitors' ) {
            this.size = 'xlarge narrowaside';
            args['competitors'] = 'yes';
            if( this.sections.competitor_tabs.selected == 'cities' ) {
                args['city_prov'] = M.eU(this.city_prov);
            } else if( this.sections.competitor_tabs.selected == 'provinces' ) {
                args['province'] = M.eU(this.province);
            } 
        } else if( this.isSelected('more', 'lists') == 'yes' ) {
            args['lists'] = 'yes';
            args['list_id'] = this.list_id;
            args['listsection_id'] = this.listsection_id;
        } else if( this.isSelected('more', 'invoices') == 'yes' ) {
            this.size = 'xlarge narrowaside';
            args['invoices'] = 'yes';
            if( this.invoice_typestatus > 0 ) {
                args['invoice_typestatus'] = this.invoice_typestatus;
            }
        } else if( this.isSelected('more', 'adjudicators') == 'yes' ) {
            this.size = 'xlarge';
            args['adjudicators'] = 'yes';
        } else if( this.isSelected('more', 'locations') == 'yes' ) {
            this.size = 'xlarge';
            args['locations'] = 'yes';
        } else if( this.isSelected('more', 'files') == 'yes' ) {
            this.size = 'large';
            args['files'] = 'yes';
        } else if( this.isSelected('more', 'certificates') == 'yes' ) {
            this.size = 'large';
            args['certificates'] = 'yes';
        } else if( this.menutabs.selected == 'messages' ) {
            args['messages'] = 'yes';
            // Which emails to get
            args['messages_status'] = this.messages_status;
            this.sections.messages.headerValues[1] = 'Date';
            if( this.messages_status == 30 ) {
                this.sections.messages.headerValues[1] = 'Scheduled';
            } else if( this.messages_status == 50 ) {
                this.sections.messages.headerValues[1] = 'Sent';
            }
//        } else if( this.isSelected('more', 'members') == 'yes' ) {
        } else if( this.menutabs.selected == 'members' ) {
            this.size = 'full';
            args['members'] = 'yes';
        } else if( this.isSelected('recommendations', '') == 'yes' ) {
            this.size = 'xlarge narrowaside';
            if( this.sections.recommendation_tabs.selected == 'classes' ) {
                args['recommendations'] = 'yes';
                args['sections'] = 'yes';
                args['section_id'] = this.section_id;
                args['class_id'] = this.class_id;
            } else {
                args['recommendations'] = 'yes';
                args['members'] = 'yes';
                args['member_id'] = this.member_id;
            }
        } else if( this.isSelected('more', 'emails') == 'yes' ) {
            args['sections'] = 'yes';
            // Which emails to get
            args['emails_list'] = this.sections.emails_tabs.selected;
        } else if( this.isSelected('more', 'sponsors') == 'yes' ) {
            this.size = 'large';
            args['sponsors'] = 'yes';
        } else if( this.isSelected('more', 'statistics') == 'yes' ) {
            this.size = 'large mediumaside';
            args['statistics'] = 'cities';
            if( this.sections.stats_tabs.selected == 'members' ) {
                args['statistics'] = 'members';
                this.size = 'full';
            }
        } else if( this.isSelected('more', 'ssam') == 'yes' ) {
            this.size = 'large mediumaside';
            args['ssam'] = 'yes';
        } else if( this.isSelected('more', 'sponsors-old') == 'yes' ) {
            args['sponsors'] = 'yes';
        }
        if( this.section_id > 0 ) {
            args['section_id'] = this.section_id;
        }
        M.api.getJSONCb('ciniki.musicfestivals.festivalGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.festival;
            p.data = rsp.festival;
            p.title = rsp.festival.name;
            p.sections.syllabus_sections.fields.section_id.options = [];
            for(var i in rsp.festival.sections) {
                p.sections.syllabus_sections.fields.section_id.options[i] = rsp.festival.sections[i];
            }
            if( p.section_id <= 0 ) {
                p.sections.syllabus_sections.fields.section_id.options.unshift({'id':0, 'name':'Select Section'});
            }
            p.sections.syllabus_groups.fields.groupname.options = [];
            for(var i in rsp.festival.groups) {
                p.sections.syllabus_groups.fields.groupname.options[i] = rsp.festival.groups[i];
            }
            p.sections.syllabus_groups.fields.groupname.options.unshift({'id':'all', 'name':'All'});
            p.data.syllabus_categories = [];
            if( rsp.festival.categories != null ) {
                p.data.syllabus_categories = rsp.festival.categories; 
            }
            p.data.syllabus_categories.unshift({'id':0, 'name':'All'});
            p.updateClasses();
            p.updateCompetitors();
            // Syllabus tabs
            if( (rsp.festival.flags&0x0800) == 0x0800 ) {
                p.sections.syllabi_tabs.tabs = {};
                for(var i in rsp.festival.syllabi) {
                    if( p.sections.syllabi_tabs.selected == null ) {
                        p.sections.syllabi_tabs.selected = rsp.festival.syllabi[i].name;   
                    }
                    p.sections.syllabi_tabs.tabs[rsp.festival.syllabi[i].name] = {
                        'label':(rsp.festival.syllabi[i].name == '' ? 'Default' : rsp.festival.syllabi[i].name),
                        'fn':'M.ciniki_musicfestivals_main.festival.switchSyllabus("' + rsp.festival.syllabi[i].name + '");',
                        };
                }
            }
            // Download Section Syllabus button
            p.sections.sections.menu.downloadsection.label = 'Download Section Syllabus (PDF)';
            if( p.section_id > 0 && rsp.festival.sections != null ) {
                for(var i in rsp.festival.sections) {
                    if( rsp.festival.sections[i].id == p.section_id ) {
                        p.sections.sections.menu.downloadsection.label = 'Download ' + rsp.festival.sections[i].name + ' Syllabus (PDF)';
                    }
                }
            }
            // Registration lists
            p.sections.registration_search.livesearchcols = 5;
            p.sections.registrations.num_cols = 5;
            p.sections.registration_search.headerValues = ['Class', 'Registrant', 'Teacher', 'Invoice', 'Status'];
            if( (rsp.festival.flags&0x10) == 0x10 ) {
                p.sections.registration_search.livesearchcols = 6;
                p.sections.registrations.num_cols = 6;
                p.sections.registration_search.headerValues = ['Class', 'Registrant', 'Teacher', 'Invoice', 'Status', 'Plus'];
                p.sections.registrations.headerValues = ['Class', 'Registrant', 'Teacher', 'Invoice', 'Status', 'Plus'];
            } else if( (rsp.festival.flags&0x02) == 0x02 ) {
                p.sections.registration_search.livesearchcols = 6;
                p.sections.registrations.num_cols = 6;
                p.sections.registration_search.headerValues = ['Class', 'Registrant', 'Teacher', 'Invoice', 'Status', 'Virtual'];
                p.sections.registrations.headerValues = ['Class', 'Registrant', 'Teacher', 'Invoice', 'Status', 'Virtual'];
            }
            p.sections.schedule_results.num_cols = 4;
            p.sections.schedule_provincials.num_cols = 4;
            if( p.data['comments-mark-ui'] != null && p.data['comments-mark-ui'] == 'yes' ) {
                if( p.data['comments-mark-label'] != null && p.data['comments-mark-label'] != '' ) {
                    p.sections.schedule_results.headerValues[p.sections.schedule_results.num_cols] = p.data['comments-mark-label'];
                    p.sections.schedule_provincials.headerValues[p.sections.schedule_provincials.num_cols] = p.data['comments-mark-label'];
                } else {
                    p.sections.schedule_results.headerValues[p.sections.schedule_results.num_cols] = 'Mark';
                    p.sections.schedule_provincials.headerValues[p.sections.schedule_provincials.num_cols] = 'Mark';
                }
                p.sections.schedule_results.dataMaps[p.sections.schedule_results.num_cols] = 'mark';
                p.sections.schedule_provincials.dataMaps[p.sections.schedule_results.num_cols] = 'mark';
                p.sections.schedule_results.num_cols++;
                p.sections.schedule_provincials.num_cols++;
            }
            if( p.data['comments-placement-ui'] != null && p.data['comments-placement-ui'] == 'yes' ) {
                if( p.data['comments-placement-label'] != null && p.data['comments-placement-label'] != '' ) {
                    p.sections.schedule_results.headerValues[p.sections.schedule_results.num_cols] = p.data['comments-placement-label'];
                    p.sections.schedule_provincials.headerValues[p.sections.schedule_provincials.num_cols] = p.data['comments-placement-label'];
                } else {
                    p.sections.schedule_results.headerValues[p.sections.schedule_results.num_cols] = 'Placement';
                    p.sections.schedule_provincials.headerValues[p.sections.schedule_provincials.num_cols] = 'Placement';
                }
                p.sections.schedule_results.dataMaps[p.sections.schedule_results.num_cols] = 'placement';
                p.sections.schedule_provincials.dataMaps[p.sections.schedule_provincials.num_cols] = 'placement';
                p.sections.schedule_results.num_cols++;
                p.sections.schedule_provincials.num_cols++;
            }
            if( p.data['comments-level-ui'] != null && p.data['comments-level-ui'] == 'yes' ) {
                if( p.data['comments-level-label'] != null && p.data['comments-level-label'] != '' ) {
                    p.sections.schedule_results.headerValues[p.sections.schedule_results.num_cols] = p.data['comments-level-label'];
                    p.sections.schedule_provincials.headerValues[p.sections.schedule_provincials.num_cols] = p.data['comments-level-label'];
                } else {
                    p.sections.schedule_results.headerValues[p.sections.schedule_results.num_cols] = 'Level';
                    p.sections.schedule_provincials.headerValues[p.sections.schedule_provincials.num_cols] = 'Level';
                }
                p.sections.schedule_results.dataMaps[p.sections.schedule_results.num_cols] = 'level';
                p.sections.schedule_provincials.dataMaps[p.sections.schedule_provincials.num_cols] = 'level';
                p.sections.schedule_results.num_cols++;
                p.sections.schedule_provincials.num_cols++;
            }
            p.sections.timeslot_comments.headerValues[2] = '';
            p.sections.schedule_competitors.num_cols = 2;
            if( rsp.festival['schedule_competitors_max_timeslots'] != null 
                && parseInt(rsp.festival['schedule_competitors_max_timeslots']) > 1
                ) {
                p.sections.schedule_competitors.num_cols = parseInt(rsp.festival['schedule_competitors_max_timeslots']) + 1;
            }
            if( rsp.festival.sections != null ) {
                p.data.registration_sections = [];
                p.data.emails_sections = [];
                p.data.registration_sections.push({'id':0, 'name':'All'});
                p.data.emails_sections.push({'id':0, 'name':'All'});
                p.sections.recommendation_sections.fields.section_id.options = rsp.festival.sections;
                p.sections.class_sections.fields.section_id.options = rsp.festival.sections;
                if( rsp.festival.section_id != null && rsp.festival.section_id >= 0 ) {
                    p.section_id = rsp.festival.section_id;
                }
                if( rsp.festival.class_id != null && rsp.festival.class_id > 0 ) {
                    p.class_id = rsp.festival.class_id;
                }
                for(var i in rsp.festival.sections) {
//                    p.data.registration_sections.push({'id':rsp.festival.sections[i].id, 'name':rsp.festival.sections[i].name});
                    p.data.registration_sections.push(rsp.festival.sections[i]);
                    p.data.emails_sections.push({'id':rsp.festival.sections[i].id, 'name':rsp.festival.sections[i].name});
                }
//                p.data.registration_sections = rsp.festival.sections;
            }
            if( rsp.festival.schedule_sections != null ) {
                for(var i in rsp.festival.schedule_sections) {
                    if( p.schedulesection_id > 0 && rsp.festival.schedule_sections[i].id == p.schedulesection_id ) {
                        p.sections.sbuttons2.label = rsp.festival.schedule_sections[i].name + ' Downloads';
                        p.sections.schedule_buttons.buttons.partial.label =  rsp.festival.schedule_sections[i].name + ' Section';
                        if( rsp.festival.schedule_sections[i].adjudicator1_id > 0 && rsp.festival.adjudicators != null && rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator1_id] != null ) {
                            p.sections.timeslot_comments.headerValues[2] = rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator1_id].name;
                        }
                    }
                }
            }
            if( rsp.festival.schedule_divisions != null ) {
                for(var i in rsp.festival.schedule_divisions) {
                    if( rsp.festival.schedule_divisions[i].id == p.scheduledivision_id ) {
                        p.sections.schedule_timeslots.label = rsp.festival.schedule_divisions[i].name + ' Time Slots';
                    }
                }
            }
            if( rsp.festival.stats_members_headerValues != null ) {
                p.sections.stats_members.num_cols = rsp.festival.stats_members_headerValues.length;
                p.sections.stats_members.headerValues = rsp.festival.stats_members_headerValues;
                p.sections.stats_members.dataMaps = rsp.festival.stats_members_dataMaps;
            }
            if( rsp.festival.ssam != null ) {
                p.data.ssam_sections = [];
                p.data.ssam_categories = [];
                p.data.ssam_items = [];
                if( rsp.festival.ssam.sections != null ) {
                    p.data.ssam_sections = rsp.festival.ssam.sections;
                    for(var i in rsp.festival.ssam.sections) {
                        if( rsp.festival.ssam.sections[i].name == p.sections.ssam_sections.selected ) {
                            p.data.ssam_categories = rsp.festival.ssam.sections[i].categories;
                            for(var j in rsp.festival.ssam.sections[i].categories) {
                                if( rsp.festival.ssam.sections[i].categories[j].name == p.sections.ssam_categories.selected ) {
                                    p.data.ssam_items = rsp.festival.ssam.sections[i].categories[j].items;
                                }
                            }
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
            // 
            // Auto remember last search
            //
            if( p.sections['syllabus_search'].lastsearch != null 
                && p.sections['syllabus_search'].lastsearch != '' 
                ) {
                M.gE(p.panelUID + '_syllabus_search').value = p.sections['syllabus_search'].lastsearch;
                var t = M.gE(p.panelUID + '_syllabus_search_livesearch_grid');
                t.style.display = 'table';
                p.liveSearchCb('syllabus_search', null, p.sections['syllabus_search'].lastsearch);
                delete p.sections['syllabus_search'].lastsearch;
            }
            else if( p.sections['registration_search'].lastsearch != null 
                && p.sections['registration_search'].lastsearch != '' 
                ) {
                M.gE(p.panelUID + '_registration_search').value = p.sections['registration_search'].lastsearch;
                var t = M.gE(p.panelUID + '_registration_search_livesearch_grid');
                t.style.display = 'table';
                p.liveSearchCb('registration_search', null, p.sections['registration_search'].lastsearch);
                delete p.sections['registration_search'].lastsearch;
            }
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
    this.festival.updateKeywords = function() {
        this.popupMenuClose('sections');
        M.api.getJSONCb('ciniki.musicfestivals.festivalKeywordsUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert('Search Keywords Updated');
        });
    }
    this.festival.festivalCopy = function(old_fid) {
        M.api.getJSONCb('ciniki.musicfestivals.festivalCopy', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'old_festival_id':old_fid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.festival.open();
        });
    }
    this.festival.festivalSponsorsCopy = function(old_fid) {
        M.api.getJSONCb('ciniki.musicfestivals.festivalSponsorsCopy', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'old_festival_id':old_fid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.festival.open();
        });
    }
    this.festival.syllabusDownload = function() {
        this.popupMenuClose('sections');
        if( this.sections.syllabi_tabs.selected != null ) {
            M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'syllabus':this.sections.syllabi_tabs.selected});
        } else {
            M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id});
        }
    }
    this.festival.syllabusSectionDownload = function() {
        this.popupMenuClose('sections');
        M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_id':this.section_id});
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
    this.edit = new M.panel('Festival', 'ciniki_musicfestivals_main', 'edit', 'mc', 'large mediumaside', 'sectioned', 'ciniki.musicfestivals.main.edit');
    this.edit.data = null;
    this.edit.festival_id = 0;
    this.edit.nplist = [];
    this.edit.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'start_date':{'label':'Start', 'type':'date'},
            'end_date':{'label':'End', 'type':'date'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '30':'Current', '60':'Archived'}},
            'flags1':{'label':'Registrations Open', 'type':'flagtoggle', 'default':'off', 'bit':0x01, 'field':'flags'},
            'flags2':{'label':'Virtual Option', 'type':'flagtoggle', 'default':'off', 'bit':0x02, 'field':'flags',
                'on_fields':['flags3','virtual_date', 'upload_end_dt'],
                },
            'flags3':{'label':'Virtual Pricing', 'type':'flagtoggle', 'default':'off', 'bit':0x04, 'field':'flags', 'visible':'no'},
            'flags4':{'label':'Section End Dates', 'type':'flagtoggle', 'default':'off', 'bit':0x08, 'field':'flags'},
            'flags6':{'label':'Earlybird Fees', 'type':'flagtoggle', 'default':'off', 'bit':0x20, 'field':'flags',
                },
            'flags5':{'label':'Adjudication Plus', 'type':'flagtoggle', 'default':'off', 'bit':0x10, 'field':'flags',
                },
            'flags10':{'label':'Live Music PDF', 'type':'flagtoggle', 'default':'off', 'bit':0x0200, 'field':'flags'},
            'earlybird_date':{'label':'Earlybird Deadline', 'type':'datetime'},
            'live_date':{'label':'Live Deadline', 'type':'datetime'},
            'virtual_date':{'label':'Virtual Deadline', 'type':'datetime', 'visible':'no'},
            'titles_end_dt':{'label':'Edit Titles Deadline', 'type':'datetime'},
            'accompanist_end_dt':{'label':'Accompanist Deadline', 'type':'datetime',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x8000); },
                },
            'upload_end_dt':{'label':'Upload Deadline', 'type':'datetime', 'visible':'no'},
            }},
//        '_settings':{'label':'', 'aside':'yes', 'fields':{
//            'age-restriction-msg':{'label':'Age Restriction Message', 'type':'text'},
//            'president-name':{'label':'President Name', 'type':'text'},
//            }},
// Remove 2022, could be readded in future
//        '_hybrid':{'label':'In Person/Virtual Choices', 'aside':'yes', 'fields':{
//            'inperson-choice-msg':{'label':'In Person Choice', 'type':'text', 'hint':'in person on a scheduled date'},
//            'virtual-choice-msg':{'label':'Virtual Choice', 'type':'text', 'hint':'virtually and submit a video'},
//            }},
        '_provincials':{'label':'Provincial Festival', 'aside':'yes',
            'visible':function() { return !M.modFlagOn('ciniki.musicfestivals', 0x010000) ? 'yes' : 'no'; },
            'fields':{
                'provincial-festival-id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':[],
                    'complex_options':{'value':'id', 'name':'name'}},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'documents', 'tabs':{
//            'website':{'label':'Website', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'website\');'},
            'documents':{'label':'Documents', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'documents\');'},
            'registrations':{'label':'Registrations', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'registrations\');'},
            'competitors':{'label':'Competitors', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'competitors\');'},
            'scheduling':{'label':'Scheduling', 'fn':'M.ciniki_musicfestivals_main.edit.switchTab(\'scheduling\');'},
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
        '_comments_pdf':{'label':'Online Adjudications & Comments PDFs', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
//                'flags6':{'label':'Header Adjudicator Name', 'type':'flagtoggle', 'default':'off', 'bit':0x20, 'field':'flags'},
//                'flags7':{'label':'Timeslot Date/Time', 'type':'flagtoggle', 'default':'off', 'bit':0x40, 'field':'flags'},
//                'comments_grade_label':{'label':'Grade Label', 'default':'Mark', 'type':'text'},
//                'comments_footer_msg':{'label':'Footer Message', 'type':'text'},
                'comments-include-pronouns':{'label':'Include Pronouns', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-class-format':{'label':'Class Format', 'type':'select', 'default':'default', 'options':{
                    'default':'Class', 
                    'section-category-class':'Section - Category - Class',
                    'category-class':'Category - Class',
                    'code-section-category-class':'Code - Section - Category - Class',
                    'code-category-class':'Code - Category - Class',
                    }},
                'comments-mark-ui':{'label':'Mark Field', 'type':'toggle', 'default':'no', 'separator':'yes', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-mark-label':{'label':'Mark Label', 'default':'Mark', 'type':'text'},
                'comments-mark-adjudicator':{'label':'Adjudicator Form', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
// FIXME: Currently competitors can't see mark or placement online, they must download PDF
//                'comments-mark-competitor':{'label':'Competitor Account', 'type':'toggle', 'default':'no', 'toggles':{
//                    'no':'No',
//                    'yes':'Yes',
//                    }},
                'comments-mark-pdf':{'label':'On Comments PDF', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-placement-ui':{'label':'Use Placement Field', 'type':'toggle', 'default':'no', 'separator':'yes', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-placement-label':{'label':'Placement Label', 'default':'Mark', 'type':'text'},
                'comments-placement-autofill':{'label':'Placement Autofill', 'default':'', 'type':'text'},
                'comments-placement-options':{'label':'Dropdown Options', 'default':'', 'type':'text'},
                'comments-placement-adjudicator':{'label':'Adjudicator Form', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
//                'comments-placement-competitor':{'label':'Competitor Account', 'type':'toggle', 'default':'no', 'toggles':{
//                    'no':'No',
//                    'yes':'Yes',
//                    }},
                'comments-placement-pdf':{'label':'On Comments PDF', 'default':'yes', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-level-ui':{'label':'Use Level Field', 'type':'toggle', 'default':'no', 'separator':'yes', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-level-label':{'label':'Level Label', 'default':'Mark', 'type':'text'},
                'comments-level-autofill':{'label':'Level Autofill', 'default':'', 'type':'text'},
                'comments-level-adjudicator':{'label':'Adjudicator Form', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
//                'comments-level-competitor':{'label':'Competitor Account', 'type':'toggle', 'default':'no', 'toggles':{
//                    'no':'No',
//                    'yes':'Yes',
//                    }},
                'comments-level-pdf':{'label':'On Comments PDF', 'default':'yes', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-header-adjudicator':{'label':'PDF Header Adjudicator Name', 'type':'toggle', 'separator':'yes', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-timeslot-datetime':{'label':'PDF Timeslot Date/Time', 'type':'toggle', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'comments-adjudicator-signature':{'label':'Adjudicator Signature', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'filledout':'Filled Out',
                    'always':'Always',
                    }},
                'comments-adjudicator-fontsig':{'label':'Adjudicator Font Signature', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'filledout':'Filled Out',
                    'always':'Always',
                    }},
                'comments-footer-msg':{'label':'PDF Footer Message', 'type':'text'},
                'comments-paper-size':{'label':'Paper Size', 'type':'toggle', 'default':'letter', 'toggles':{
                    'letter':'Letter',
                    'legal':'Legal',
                    }},
//                'comments_footer_msg':{'label':'OLD Footer Message', 'type':'text', 'editable':'no'},
                'comments-sorting':{'label':'Sorting', 'type':'toggle', 'default':'schedule', 'toggles':{
                    'schedule':'Schedule',
                    'byclass':'By Class',
                    }},
                'comments-live-adjudication-online':{'label':'Live Online Adjudications', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
            }},
        '_schedule_pdf':{'label':'Schedule Default Options', 
            // These options can be changed on the download screen
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'schedule-division-header-format':{'label':'Division Header Format', 'type':'select', 'default':'default', 'options':{
                    'default':'Date-Division, Location', 
                    'namedate-adjudicatorlocation':'Division-Date, Adjudicator-Location', 
                    'name-adjudicator-location':'Division, Adjudicator, Location',
                    'date-adjudicator-location':'Date, Adjudicator, Location',
                    'date-name-adjudicator-location':'Date, Division, Adjudicator, Location',
                    'name-date-adjudicator-location':'Division, Date, Adjudicator, Location',
                    }},
                'schedule-division-header-labels':{'label':'Division Header Labels', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
//                's_division_header_adjudicator':{'label':'Include Adjudi', 'type':'toggle', 'default':'default', 'toggles':{
//                    'default':'name - date<br/>address', 
//                    'name-date-adjudicator-address':'Name<br/>Date: date<br/>Adjudicator: adjudicator<br/>Address: address', 
//                    }},
                'schedule-date-format':{'label':'Date Format', 'type':'select', 'default':'default', 
                    'options':{
                        '%W, %M %D, %Y':'Monday, January 1st, 2025',
                        '%W, %M %e, %Y':'Monday, January 1, 2025',
                        '%a, %b %e, %Y':'Mon, Jan 1, 2025',
                        '%b %e, %Y':'Jan 1, 2025',
                    }},
                'schedule-section-adjudicator-bios':{'label':'Section Adjudicator Bios', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'schedule-names':{'label':'Competitor Full Names', 'type':'toggle', 'default':'public', 'toggles':{
                    'public':'No', 
                    'private':'Yes',
                    }},
                'schedule-include-pronouns':{'label':'Include Pronouns', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'schedule-separate-classes':{'label':'Separate Classes', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}}, 
                'schedule-class-format':{'label':'Class Format', 'type':'select', 'default':'default', 'options':{
                    'default':'Code - Class', 
                    'code-section-category-class':'Code - Section - Category - Class',
                    'code-category-class':'Code - Category - Class',
                    }},
                'schedule-titles':{'label':'Titles', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-video-urls':{'label':'Include YouTube Links', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-header':{'label':'Document Header', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-footer':{'label':'Document Footer', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-footerdate':{'label':'Footer Date', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'schedule-continued-label':{'label':'Show (continued...)', 'type':'toggle', 'default':'yes', 
                    'toggles':{
                        'no':'No', 
                        'yes':'Yes',
                    }},
                'schedule-section-page-break':{'label':'Section Page Break', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
        '_program_pdf':{'label':'Program PDF Default Options', 
            // These options can be changed on the download screen
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'program-separate-classes':{'label':'Separate Classes', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}}, 
                'program-class-format':{'label':'Class Format', 'type':'select', 'default':'default', 'options':{
                    'default':'Class', 
                    'code-section-category-class':'Code - Section - Category - Class',
                    'code-category-class':'Code - Category - Class',
                    'code-class':'Code - Class',
                    }},
            }},
        '_runsheets_pdf':{'label':'Run Sheets PDF Options', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'runsheets-page-orientation':{'label':'Page Orientation', 'type':'toggle', 'default':'portrait', 'toggles':{
                    'portrait':'Portrait',
                    'landscape':'Landscape',
                    }},
                'runsheets-include-pronouns':{'label':'Include Pronouns', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                // Separate classes in timeslots
                'runsheets-separate-classes':{'label':'Separate Classes', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}}, 
                'runsheets-class-format':{'label':'Class Format', 'type':'select', 'default':'default', 'options':{
                    'default':'Code - Class', 
                    'code-section-category-class':'Code - Section - Category - Class',
                    'code-category-class':'Code - Category - Class',
                    }},
                'runsheets-timeslot-singlepage':{'label':'Single Timeslot/Page', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-mark':{'label':'Include Mark', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-advance-to':{'label':'Include Advance To', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-internal-notes':{'label':'Include Internal Admin Notes', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-registration-runnotes':{'label':'Include Runsheet Notes', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-registration-notes':{'label':'Include Registration Notes', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-competitor-notes':{'label':'Include Competitor Notes', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-competitor-age':{'label':'Include Competitor Age', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'runsheets-competitor-city':{'label':'Include Competitor City', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
        '_certificates_pdf':{'label':'Certificates PDF Options', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'certificates-include-pronouns':{'label':'Include Pronouns', 'type':'toggle', 'default':'no', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'certificates-class-format':{'label':'Class Format', 'type':'select', 'default':'default', 'options':{
                    'default':'Class', 
                    'section-category-class':'Section - Category - Class',
                    'category-class':'Category - Class',
                    'code-section-category-class':'Code - Section - Category - Class',
                    'code-category-class':'Code - Category - Class',
                    }},
                'certificates-use-group-numpeople':{'label':'Print for Each Group Member', 'type':'toggle', 'default':'yes', 'toggles':{
                    'no':'No',
                    'yes':'Yes',
                    }},
                'certificates-sorting':{'label':'Sorting', 'type':'toggle', 'default':'schedule', 'toggles':{
                    'schedule':'Schedule',
                    'byclass':'By Class',
                    }},
            }},
        '_syllabus':{'label':'Syllabus Options', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'flags9':{'label':'Include Section/Category as Class Name', 'type':'flagtoggle', 'default':'off', 'bit':0x0100, 'field':'flags'},
                'flags11':{'label':'Category Group Names', 'type':'flagtoggle', 'default':'off', 'bit':0x0400, 'field':'flags'},
                'flags12':{'label':'Multiple Syllabii', 'type':'flagtoggle', 'default':'off', 'bit':0x0800, 'field':'flags'},
//                'syllabus-schedule-time':{'label':'Class Time', 'type':'toggle', 'default':'none', 'toggles':{
//                    'none':'None', 'total':'Playing + Adjudication', 'adjudication':'Adjudication', 
//                    }},
            }},
        // Add for 2025
/*        '_syllabus_pdf':{'label':'Syllabus PDF Options', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                // Don't know which option is better
                'syllabus-pdf-prices':{'label':'Show Earlybird Prices', 'type':'toggle', 'default':'either', 'toggles':{
                    'either':'Earlybird OR regular',
                    'both':'Earlybird AND Regular',
                    }},
                // Or these???
                'syllabus-pdf-earlybird':{'label':'Show Earlybird Prices', 'type':'toggle', 'default':'off', 'toggles':{
                    'valid':'Until Deadline',
                    'always':'Always',
                    }},
                'syllabus-pdf-regular':{'label':'Show Regular Prices', 'type':'toggle', 'default':'off', 'toggles':{
                    'valid':'After Earlybird',
                    'always':'Always',
                    }},
            }}, */
        '_customer_types':{'label':'Customer Type Buttons', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'customer-type-intro-msg':{'label':'Intro', 'type':'textarea'},
                'customer-type-parent-button-label':{'label':'Parent Label', 'type':'text'},
                'customer-type-teacher-button-label':{'label':'Teacher Label', 'type':'text'},
                'customer-type-adult-button-label':{'label':'Adult Label', 'type':'text'},
            }},
        '_registration_form':{'label':'Registration Form', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'registration-parent-msg':{'label':'Parents Intro', 'type':'textarea', 'size':'medium'},
                'registration-teacher-msg':{'label':'Teachers Intro', 'type':'textarea', 'size':'medium'},
                'registration-adult-msg':{'label':'Adult Intro', 'type':'textarea', 'size':'medium'},
                'registration-participation-label':{'label':'Participation Label', 'type':'text', 'hint':'I would like to participate'},
                'registration-title-label':{'label':'Title Label', 'type':'text', 'hint':'Title'},
                'registration-movements-label':{'label':'Movements Label', 'type':'text', 
                    'hint':'Movements/Musical',
                    },
                'registration-composer-label':{'label':'Composer Label', 'type':'text', 
                    'hint':'Composer',
                    },
                'registration-length-label':{'label':'Piece Length Label', 'type':'text', 'hint':'Piece Length'},
                'registration-length-format':{'label':'Piece Length', 'type':'toggle', 'default':'minsec', 'toggles':{
                    'minsec':'Minutes/Seconds',
                    'minonly':'Minutes Only',
                    }},
            }},
        '_registration_statuses':{'label':'Registration Status', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden'; },
            'fields':{
                'registration-status-5-colour':{'label':'Draft(unpaid) Colour', 'type':'colour'},
                'registration-status-10-colour':{'label':'Registered(paid) Colour', 'type':'colour'},
                'registration-status-31-label':{'label':'Incomplete #1 Label', 'type':'text', 'separator':'yes'},
                'registration-status-31-colour':{'label':'Incomplete #1 Colour', 'type':'colour'},
                'registration-status-32-label':{'label':'Incomplete #2 Label', 'type':'text'},
                'registration-status-32-colour':{'label':'Incomplete #2 Colour', 'type':'colour'},
                'registration-status-33-label':{'label':'Incomplete #3 Label', 'type':'text'},
                'registration-status-33-colour':{'label':'Incomplete #3 Colour', 'type':'colour'},
                'registration-status-34-label':{'label':'Incomplete #4 Label', 'type':'text'},
                'registration-status-34-colour':{'label':'Incomplete #4 Colour', 'type':'colour'},
                'registration-status-35-label':{'label':'Incomplete #5 Label', 'type':'text'},
                'registration-status-35-colour':{'label':'Incomplete #5 Colour', 'type':'colour'},
                'registration-status-36-label':{'label':'Incomplete #6 Label', 'type':'text'},
                'registration-status-36-colour':{'label':'Incomplete #6 Colour', 'type':'colour'},
                'registration-status-37-label':{'label':'Incomplete #7 Label', 'type':'text'},
                'registration-status-37-colour':{'label':'Incomplete #7 Colour', 'type':'colour'},
                'registration-status-38-label':{'label':'Incomplete #8 Label', 'type':'text'},
                'registration-status-38-colour':{'label':'Incomplete #8 Colour', 'type':'colour'},
                'registration-status-50-label':{'label':'Approved #1 Label', 'type':'text', 'separator':'yes'},
                'registration-status-50-colour':{'label':'Approved #1 Colour', 'type':'colour'},
                'registration-status-51-label':{'label':'Approved #2 Label', 'type':'text'},
                'registration-status-51-colour':{'label':'Approved #2 Colour', 'type':'colour'},
                'registration-status-52-label':{'label':'Approved #3 Label', 'type':'text'},
                'registration-status-52-colour':{'label':'Approved #3 Colour', 'type':'colour'},
                'registration-status-53-label':{'label':'Approved #4 Label', 'type':'text'},
                'registration-status-53-colour':{'label':'Approved #4 Colour', 'type':'colour'},
                'registration-status-54-label':{'label':'Approved #5 Label', 'type':'text'},
                'registration-status-54-colour':{'label':'Approved #5 Colour', 'type':'colour'},
                'registration-status-55-label':{'label':'Approved #6 Label', 'type':'text'},
                'registration-status-55-colour':{'label':'Approved #6 Colour', 'type':'colour'},
                'registration-status-70-colour':{'label':'Disqualified Colour', 'type':'colour', 'separator':'yes'},
                'registration-status-75-colour':{'label':'Withdrawn Colour', 'type':'colour'},
                'registration-status-80-colour':{'label':'Cancelled Colour', 'type':'colour'},
            },
            'menu':{
                'default':{
                    'label':'Set Default Colours',
                    'fn':'M.ciniki_musicfestivals_main.edit.setDefaultColours();',
                    },
                },
            },
        '_competitor_general':{'label':'Competitors', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'competitors' ? 'yes' : 'hidden'; },
            'fields':{
                'competitor-label-singular':{'label':'Label Singular', 'type':'text'},
                'competitor-label-plural':{'label':'Label Plural', 'type':'text'},
                }},
        '_competitor_parent_msg':{'label':'Individual Competitor Form', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'competitors' ? 'yes' : 'hidden'; },
            'fields':{
                'competitor-parent-msg':{'label':'Parent Intro', 'type':'textarea', 'size':'medium'},
                'competitor-teacher-msg':{'label':'Teacher Intro', 'type':'textarea', 'size':'medium'},
                'competitor-adult-msg':{'label':'Adult Intro', 'type':'textarea', 'size':'medium'},
                'competitor-individual-study-level':{'label':'Study Level', 'type':'toggle', 'default':'hidden', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
                'competitor-individual-instrument':{'label':'Instrument', 'type':'toggle', 'default':'hidden', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
                'competitor-individual-age':{'label':'Age', 'type':'toggle', 'default':'required', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
                'competitor-individual-age-label':{'label':'Age Label', 'type':'text'},
                'competitor-individual-etransfer-email':{'label':'e-transfer Email', 'type':'toggle', 'default':'hidden', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
            }},
        '_competitor_group_parent_msg':{'label':'Group/Ensemble Competitor Form', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'competitors' ? 'yes' : 'hidden'; },
            'fields':{
                'competitor-group-parent-msg':{'label':'Parent Intro', 'type':'textarea', 'size':'medium'},
                'competitor-group-teacher-msg':{'label':'Teacher Intro', 'type':'textarea', 'size':'medium'},
                'competitor-group-adult-msg':{'label':'Adult Intro', 'type':'textarea', 'size':'medium'},
                'competitor-group-study-level':{'label':'Study Level', 'type':'toggle', 'default':'hidden', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
                'competitor-group-instrument':{'label':'Instrument', 'type':'toggle', 'default':'hidden', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
                'competitor-group-age':{'label':'Age', 'type':'toggle', 'default':'required', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
                'competitor-group-age-label':{'label':'Age Label', 'type':'text'},
                'competitor-group-etransfer-email':{'label':'e-transfer Email', 'type':'toggle', 'default':'hidden', 'toggles':{
                    'hidden':'Hidden', 'optional':'Optional', 'required':'Required', 
                    }},
            }},
        '_waiver':{'label':'General Waiver Message', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'competitors' ? 'yes' : 'hidden'; },
            'fields':{
                'waiver-general-title':{'label':'Title', 'type':'text'},
                'waiver-general-msg':{'label':'Message', 'type':'textarea', 'size':'medium'},
            }},
        '_photowaiver':{'label':'Photo Waiver Message', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'competitors' ? 'yes' : 'hidden'; },
            'fields':{
                'waiver-photo-status':{'label':'Status', 'type':'toggle', 'default':'off', 
                    'onchange':'M.ciniki_musicfestivals_main.edit.updateForm',
                    'toggles':{
                        'off':'Disabled',
                        'internal':'Internal Only',
                        'on':'Enabled',
                    }},
                'waiver-photo-title':{'label':'Title', 'type':'text', 
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-photo-status') == 'on' ? 'yes' : 'no'; },
                    },
                'waiver-photo-msg':{'label':'Message', 'type':'textarea', 'size':'medium', 
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-photo-status') == 'on' ? 'yes' : 'no'; },
                    },
                'waiver-photo-option-yes':{'label':'Yes Option Text', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-photo-status') != 'disabled' ? 'yes' : 'no'; },
                    },
                'waiver-photo-option-no':{'label':'No Option Text', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-photo-status') != 'disabled' ? 'yes' : 'no'; },
                    },
            }},
        '_namewaiver':{'label':'Name Waiver Message', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'competitors' ? 'yes' : 'hidden'; },
            'fields':{
                'waiver-name-status':{'label':'Status', 'type':'toggle', 'default':'off', 
                    'onchange':'M.ciniki_musicfestivals_main.edit.updateForm',
                    'toggles':{
                        'off':'Disabled',
                        'internal':'Internal Only',
                        'on':'Enabled',
                    }},
                'waiver-name-title':{'label':'Title', 'type':'text', 
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-name-status') == 'on' ? 'yes' : 'no'; },
                    },
                'waiver-name-msg':{'label':'Message', 'type':'textarea', 'size':'medium',
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-name-status') == 'on' ? 'yes' : 'no'; },
                    },
                'waiver-name-option-yes':{'label':'Yes Option Text', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-name-status') != 'disabled' ? 'yes' : 'no'; },
                    },
                'waiver-name-option-no':{'label':'No Option Text', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.edit.formValue('waiver-name-status') != 'disabled' ? 'yes' : 'no'; },
                    },
            }},
        '_scheduleoptions':{'label':'Schedule Options', 
            'visible':function() { return M.ciniki_musicfestivals_main.edit.sections._tabs.selected == 'scheduling' ? 'yes' : 'hidden'; },
            'fields':{
                'scheduling-age-show':{'label':'Show Age', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-draft-show':{'label':'Show Draft', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-disqualified-show':{'label':'Show Disqualified', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-withdrawn-show':{'label':'Show Withdrawn', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-cancelled-show':{'label':'Show Cancelled', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-teacher-show':{'label':'Show Teacher', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-accompanist-show':{'label':'Show Accompanist', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-at-times':{'label':'Adjudicator Talk Time', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'advanced-scheduler-num-divisions':{'label':'Number of Divisions', 'type':'toggle', 'default':'2', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x4000); },
                    'toggles':{
                        '2':'2',
                        '3':'3',
                        '4':'4',
                        '5':'5',
                        '6':'6',
                        '7':'7',
                        '8':'8',
                        '9':'9',
                    }},
                'scheduling-seconds-show':{'label':'Timeslot Seconds', 'type':'toggle', 'default':'no', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x4000); },
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-timeslot-length':{'label':'Timeslot Length', 'type':'toggle', 'default':'no', 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x4000); },
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
                'scheduling-timeslot-startnum':{'label':'Timeslot Starting Number', 'type':'toggle', 'default':'no', 
                    'toggles':{
                        'no':'No',
                        'yes':'Yes',
                    }},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.edit.save();'},
            'updatename':{'label':'Update Public Names', 
                'visible':function() {return M.ciniki_musicfestivals_main.edit.festival_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.edit.updateNames();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.edit.festival_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.edit.remove();'},
            'deletefile':{'label':'Delete All Uploaded Music PDFs, Backtracks and Artwork', 'class':'delete',
                'visible':function() {return M.ciniki_musicfestivals_main.edit.festival_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.edit.removeFiles();'},
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
        this.showHideSection('_comments_pdf');
        this.showHideSection('_schedule_pdf');
        this.showHideSection('_program_pdf');
        this.showHideSection('_runsheets_pdf');
        this.showHideSection('_certificates_pdf');
        this.showHideSection('_syllabus');
//        this.showHideSection('_syllabus_pdf');
        this.showHideSection('_customer_types');
        this.showHideSection('_registration_form');
        this.showHideSection('_registration_statuses');
        this.showHideSection('_registration_teacher_msg');
        this.showHideSection('_registration_adult_msg');
        this.showHideSection('_competitor_general');
        this.showHideSection('_competitor_parent_msg');
        this.showHideSection('_competitor_teacher_msg');
        this.showHideSection('_competitor_adult_msg');
        this.showHideSection('_competitor_group_parent_msg');
        this.showHideSection('_competitor_group_teacher_msg');
        this.showHideSection('_competitor_group_adult_msg');
        this.showHideSection('_waiver');
        this.showHideSection('_scheduleoptions');
        this.refreshSection('_tabs');
        this.updateForm();
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
    this.edit.setDefaultColours = function() {
        this.popupMenuClose('_registration_statuses');
        M.gE(this.panelUID + '_registration-status-5-colour').style.background = '#f0f0f0';
        M.gE(this.panelUID + '_registration-status-10-colour').style.background = '#ffffff';
        M.gE(this.panelUID + '_registration-status-31-colour').style.background = '#e0e0e0'; // grey
        M.gE(this.panelUID + '_registration-status-32-colour').style.background = '#fffdc5'; // yellow
        M.gE(this.panelUID + '_registration-status-33-colour').style.background = '#ffefdd'; // orange
        M.gE(this.panelUID + '_registration-status-34-colour').style.background = '#ffdddd'; // red
        M.gE(this.panelUID + '_registration-status-35-colour').style.background = '#ddf1ff'; // blue
        M.gE(this.panelUID + '_registration-status-36-colour').style.background = '#f0ddff'; // purple
        M.gE(this.panelUID + '_registration-status-37-colour').style.background = '#ceffff'; // teal
        M.gE(this.panelUID + '_registration-status-38-colour').style.background = '#ffddee'; // pink
        M.gE(this.panelUID + '_registration-status-50-colour').style.background = '#ddffdd'; // green 
        M.gE(this.panelUID + '_registration-status-51-colour').style.background = '#ddffdd'; // green 
        M.gE(this.panelUID + '_registration-status-52-colour').style.background = '#ddffdd'; // green 
        M.gE(this.panelUID + '_registration-status-53-colour').style.background = '#ddffdd'; // green 
        M.gE(this.panelUID + '_registration-status-54-colour').style.background = '#ddffdd'; // green 
        M.gE(this.panelUID + '_registration-status-55-colour').style.background = '#ddffdd'; // green 
        M.gE(this.panelUID + '_registration-status-70-colour').style.background = '#e0e0e0';
        M.gE(this.panelUID + '_registration-status-80-colour').style.background = '#e0e0e0';
    }
    this.edit.updateForm = function() {
        this.showHideSection('_photowaiver');
        this.showHideSection('_namewaiver');
        this.showHideFormField('_photowaiver', 'waiver-photo-title');
        this.showHideFormField('_photowaiver', 'waiver-photo-msg');
        this.showHideFormField('_photowaiver', 'waiver-photo-option-yes');
        this.showHideFormField('_photowaiver', 'waiver-photo-option-no');
        this.showHideFormField('_namewaiver', 'waiver-name-title');
        this.showHideFormField('_namewaiver', 'waiver-name-msg');
        this.showHideFormField('_namewaiver', 'waiver-name-option-yes');
        this.showHideFormField('_namewaiver', 'waiver-name-option-no');
    }
    this.edit.open = function(cb, fid, list) {
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.festivalGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'provincials':'festivals'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.edit;
            p.data = rsp.festival;
            if( (rsp.festival.flags&0x02) == 0x02 ) {
                p.sections.general.fields.flags3.visible = 'yes';
                p.sections.general.fields.virtual_date.visible = 'yes';
                p.sections.general.fields.upload_end_dt.visible = 'yes';
            } else {
                p.sections.general.fields.virtual_date.visible = 'no';
                p.sections.general.fields.upload_end_dt.visible = 'no';
            }
            p.sections._provincials.fields['provincial-festival-id'].options = [{'id':0, 'name':'None'}];
            if( rsp.festival.provincial_festivals != null ) {
                for(var i in rsp.festival.provincial_festivals) {
                    p.sections._provincials.fields['provincial-festival-id'].options.push(rsp.festival.provincial_festivals[i]);
                }
            }

            p.refresh();
            p.show(cb);
            p.updateForm();
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
    this.edit.removeFiles = function() {
        M.confirm('Are you sure you want to remove all uploaded music files, backtrack and artwork files for this festival?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.festivalFilesDelete', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.edit.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.edit.close();
            });
        });
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
    // The panel to view the list of sections and their dates and late fees
    //
    this.sections = new M.panel('Syllabus Sections', 'ciniki_musicfestivals_main', 'sections', 'mc', 'xlarge', 'sectioned', 'ciniki.musicfestivals.main.sections');
    this.sections.data = null;
    this.sections.syllabi = '';
    this.sections.festival_id = 0;
    this.sections.nplist = [];
    this.sections.sections = {
        'sections':{'label':'Sections', 'type':'simplegrid', 'num_cols':1,
            'headerValues':[],
            'cellClasses':[],
            'dataMaps':[],
            'menu':{
                'latefees':{
                    'label':'Set Late Fees',
                    'fn':'M.ciniki_musicfestivals_main.latefees.open(\'M.ciniki_musicfestivals_main.sections.open();\',M.ciniki_musicfestivals_main.sections.syllabi);',
                    },
                'adminfees':{
                    'label':'Set Admin Fees',
                    'fn':'M.ciniki_musicfestivals_main.adminfees.open(\'M.ciniki_musicfestivals_main.sections.open();\',M.ciniki_musicfestivals_main.sections.syllabi);',
                    },
                },
            },
        };
    this.sections.cellValue = function(s, i, j, d) {
        if( s == 'sections' ) {
            return d[this.sections.sections.dataMaps[j]];
        }
    }
    this.sections.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.section.open(\'M.ciniki_musicfestivals_main.sections.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_main.sections.festival_id, M.ciniki_musicfestivals_main.sections.nplist);';
    }
    this.sections.open = function(cb, sid, fid) {
        if( sid != null ) { this.syllabi = sid; }
        if( fid != null ) { this.festival_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.sectionList', {'tnid':M.curTenantID, 'syllabus':this.syllabi, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.sections;
            p.data = rsp;
            p.nplist = rsp.nplist;
            p.sections.sections.headerValues = [];
            p.sections.sections.cellClasses = [];
            p.sections.sections.dataMaps = [];
            if( M.ciniki_musicfestivals_main.festival.sections.syllabi_tabs.tabs.length > 1 ) {
                p.sections.sections.headerValues.push('Syllabus');
                p.sections.sections.cellClasses.push('');
                p.sections.sections.dataMaps.push('syllabus');
            }
            p.sections.sections.headerValues.push('Section');
            p.sections.sections.cellClasses.push('');
            p.sections.sections.dataMaps.push('name');
            p.sections.sections.headerValues.push('Live End');
            p.sections.sections.cellClasses.push('');
            p.sections.sections.dataMaps.push('live_end_dt');
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x04) == 0x04 ) {
                p.sections.sections.headerValues.push('Virtual End');
                p.sections.sections.cellClasses.push('');
                p.sections.sections.dataMaps.push('virtual_end_dt');
            }
            p.sections.sections.headerValues.push('Late Fees');
            p.sections.sections.cellClasses.push('');
            p.sections.sections.dataMaps.push('latefees_text');
            p.sections.sections.headerValues.push('Amount');
            p.sections.sections.cellClasses.push('alignright');
            p.sections.sections.dataMaps.push('latefees_start_amount');
            p.sections.sections.headerValues.push('Increase');
            p.sections.sections.cellClasses.push('alignright');
            p.sections.sections.dataMaps.push('latefees_daily_increase');
            p.sections.sections.headerValues.push('Days');
            p.sections.sections.cellClasses.push('alignright');
            p.sections.sections.dataMaps.push('latefees_days');
            p.sections.sections.headerValues.push('Admin Fees');
            p.sections.sections.cellClasses.push('alignright');
            p.sections.sections.dataMaps.push('adminfees_amount');
            p.sections.sections.num_cols = p.sections.sections.headerValues.length;
            p.sections.sections.headerClasses = p.sections.sections.cellClasses;

            p.refresh();
            p.show(cb);
        });
    }
    this.sections.addClose('Close');



    //
    // The panel to edit Section
    //
    this.section = new M.panel('Section', 'ciniki_musicfestivals_main', 'section', 'mc', 'large mediumaside', 'sectioned', 'ciniki.musicfestivals.main.section');
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
            'syllabus':{'label':'Syllabus', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x0800) == 0x0800 ? 'yes' : 'no'; },
                },
            'name':{'label':'Name', 'type':'text', 'required':'yes'},
            'sequence':{'label':'Order', 'type':'text', 'required':'yes', 'size':'small'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Hidden'}}},
            'live_end_dt':{'label':'Live Deadline', 'type':'datetime',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x08) == 0x08 ? 'yes' : 'no';},
                },
            'virtual_end_dt':{'label':'Virtual Deadline', 'type':'datetime',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x0a) == 0x0a ? 'yes' : 'no';},
                },
            'titles_end_dt':{'label':'Edit Titles Deadline', 'type':'datetime',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x0a) == 0x0a ? 'yes' : 'no';},
                },
            'upload_end_dt':{'label':'Upload Deadline', 'type':'datetime',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x0a) == 0x0a ? 'yes' : 'no';},
                },
            }},
        'fees':{'label':'Extra Fees', 'aside':'yes', 
            'visible':function() { return !M.modFlagOn('ciniki.musicfestivals', 0x010000) ? 'yes' : 'hidden'; },
            'fields':{
                'flags5':{'label':'Late Fee', 'type':'flagspiece', 'mask':0x30, 'field':'flags', 'join':'yes', 'toggle':'yes',
                    'onchange':'M.ciniki_musicfestivals_main.section.updateForm',
                    'flags':{'0':{'name':'None'}, '5':{'name':'per Cart'}},// **future**, '6':{'name':'per Registration'}},
                    },
                'latefees_start_amount':{'label':'First Day Amount', 'type':'text', 'size':'small', 'visible':'no'},
                'latefees_daily_increase':{'label':'Daily Increase', 'type':'text', 'size':'small', 'visible':'no'},
                'latefees_days':{'label':'Number of Days', 'type':'text', 'size':'small', 'visible':'no'},
                'flags7':{'label':'Admin Fee', 'type':'flagspiece', 'mask':0xC0, 'field':'flags', 'join':'yes', 'toggle':'yes',
                    'onchange':'M.ciniki_musicfestivals_main.section.updateForm',
                    'flags':{'0':{'name':'None'}, '7':{'name':'per Cart'}},// **future**, '8':{'name':'per Registration'}},
                    },
                'adminfees_amount':{'label':'Admin Fee Amount', 'type':'text', 'size':'small', 'visible':'no'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'categories', 'tabs':{
            'categories':{'label':'Categories', 'fn':'M.ciniki_musicfestivals_main.section.switchTab(\'categories\');'},
            'synopsis':{'label':'Description', 'fn':'M.ciniki_musicfestivals_main.section.switchTab(\'synopsis\');'},
            'live':{'label':'Live', 
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x020000); }, // live/virtual split festivals
                'fn':'M.ciniki_musicfestivals_main.section.switchTab(\'live\');',
                },
            'virtual':{'label':'Virtual', 
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x020000); }, // live/virtual split festivals
                'fn':'M.ciniki_musicfestivals_main.section.switchTab(\'virtual\');',
                },
            'recommendations':{'label':'Recommendations', 
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); }, // provincials
                'fn':'M.ciniki_musicfestivals_main.section.switchTab(\'recommendations\');',
                },
            }},
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'}},
            },
        '_description':{'label':'Description', 
            'visible':function() { return !M.modFlagOn('ciniki.musicfestivals', 0x020000) && M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'}},
            },
        '_live_description':{'label':'Live Description', 
            'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x020000) && M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'live' ? 'yes' : 'hidden'; },
            'fields':{
                'live_description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'}},
            },
        '_virtual_description':{'label':'Virtual Description', 
            'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x020000) && M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'virtual' ? 'yes' : 'hidden'; },
            'fields':{
                'virtual_description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'}},
            },
        '_recommendations_description':{'label':'Adjudicator Recommendations Description', 
            'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x010000) && M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'recommendations' ? 'yes' : 'hidden'; },
            'fields':{
                'recommendations_description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'}},
            },
        'category_descriptions':{'label':'Categories',
            'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x020000) && ['live','virtual'].indexOf(M.ciniki_musicfestivals_main.section.sections._tabs.selected) >= 0 ? 'yes' : 'hidden'; },
            'fields':{
                },
            },
/*        'section_descriptions':{'label':'Section',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'descriptions' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'fields':{
                'synopsis':{'label':'Synopsis', 'type':'textarea', 'size':'small'},
                'description':{'label':'Description', 'type':'textarea', 'size':'medium',
                    'active':function() { return !M.modFlagOn('ciniki.musicfestivals', 0x020000) ? 'yes' : 'no'; },
                    },
                'active':{'label':'Live Description', 'type':'textarea', 'size':'medium',
                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x020000) ? 'yes' : 'no'; },
                    },
                'virtual_description':{'label':'Virtual Description', 'type':'textarea', 'size':'medium',
                    'active':function() { return M.modFlagOn('ciniki.musicfestivals', 0x020000) ? 'yes' : 'no'; },
                    },
//                'recommendations_description':{'label':'Adjudicator Recommendations', 'hidelabel':'yes', 'type':'textarea', 'size':'large',
//                    'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x010000) && M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'recommendations' ? 'yes' : 'hidden'; },
//                    },
                },
            },
        'description_edit_buttons':{'label':'',
            'visible':function() { return M.ciniki_musicfestivals_main.festival.menutabs.selected == 'syllabus' && M.ciniki_musicfestivals_main.festival.sections._stabs.selected == 'descriptions' && M.ciniki_musicfestivals_main.festival.section_id > 0 ? 'yes' : 'no'; },
            'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.festival.saveDescriptions();'},
                }}, */
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_musicfestivals_main.section.sections._tabs.selected == 'categories' ? 'yes' : 'hidden'; },
            'headerValues':null,
            'menu':{
                'add':{
                    'label':'Add Category',
                    'fn':'M.ciniki_musicfestivals_main.section.openCategory(0);',
                    },
                },
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.musicfestivals.categoryUpdate', {'tnid':M.curTenantID, 
                    'category_id':M.ciniki_musicfestivals_main.section.data.categories[from].id,
                    'sequence':M.ciniki_musicfestivals_main.section.data.categories[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.section.refreshCategories();
                    });
                },
            },
        '_buttons':{'label':'', 'buttons':{
            'syllabuspdf':{'label':'Download Syllabus (PDF)', 'fn':'M.ciniki_musicfestivals_main.section.downloadSyllabusPDF();'},
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.section.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.section.section_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.section.remove();'},
            }},
        };
    this.section.fieldValue = function(s, i, d) { 
        if( s == 'category_descriptions' ) {
            return this.data.categories[this.sections.category_descriptions.fields[i].idx].description;
        }
        return this.data[i]; 
        }
    this.section.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.sectionHistory', 'args':{'tnid':M.curTenantID, 'section_id':this.section_id, 'field':i}};
    }
    this.section.liveSearchCb = function(s, i, value) {
        if( i == 'syllabus' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.sectionFieldSearch', 
                {'tnid':M.curTenantID, 'field':i, 'festival_id':this.festival_id, 'start_needle':value, 'limit':25}, function(rsp) { 
                    M.ciniki_musicfestivals_main.section.liveSearchShow(s, i, M.gE(M.ciniki_musicfestivals_main.section.panelUID + '_' + i), rsp.results); 
                });
        }
    }
    this.section.liveSearchResultValue = function(s, f, i, j, d) {
        return d.value;
    }
    this.section.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_musicfestivals_main.section.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.value) + '\');';
    }
    this.section.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.section.cellValue = function(s, i, j, d) {
        if( this.sections.categories.num_cols == 2 ) {
            switch (j) {
                case 0: return d.groupname;
                case 1: return d.name;
            }
        } else {
            switch (j) {
                case 0: return d.name;
            }
        }
    }
    this.section.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.section.openCategory(\'' + d.id + '\');';
    }
    this.section.openCategory = function(cid) {
        this.save("M.ciniki_musicfestivals_main.category.open('M.ciniki_musicfestivals_main.section.open();', '" + cid + "', M.ciniki_musicfestivals_main.section.section_id, M.ciniki_musicfestivals_main.section.festival_id, M.ciniki_musicfestivals_main.section.nplists.categories);");
    }
    this.section.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.showHideSections(['categories', '_synopsis', '_description', '_live_description', '_virtual_description', '_recommendations_description', 'category_descriptions']);
        this.refreshSection('_tabs');
    }
    this.section.downloadSyllabusPDF = function() {
        M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_id':this.section_id});
    }
    this.section.refreshCategories = function() {
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
            p.refreshSection('categories');
        });
    }
    this.section.updateForm = function() {
        var f = this.formValue('flags7');
        if( (f&0xC0) > 0 ) {
            this.sections.fees.fields.adminfees_amount.visible = 'yes';
        } else {
            this.sections.fees.fields.adminfees_amount.visible = 'no';
        }
        var f = this.formValue('flags5');
        if( (f&0x30) > 0 ) {
            this.sections.fees.fields.latefees_start_amount.visible = 'yes';
            this.sections.fees.fields.latefees_daily_increase.visible = 'yes';
            this.sections.fees.fields.latefees_days.visible = 'yes';
        } else {
            this.sections.fees.fields.latefees_start_amount.visible = 'no';
            this.sections.fees.fields.latefees_daily_increase.visible = 'no';
            this.sections.fees.fields.latefees_days.visible = 'no';
        }
        this.showHideFormField('fees', 'latefees_start_amount');
        this.showHideFormField('fees', 'latefees_daily_increase');
        this.showHideFormField('fees', 'latefees_days');
        this.showHideFormField('fees', 'adminfees_amount');
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
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x0400) == 0x0400 ) {
                p.sections.categories.headerValues = ['Group', 'Category'];
                p.sections.categories.num_cols = 2;
            } else {
                p.sections.categories.headerValues = null;
                p.sections.categories.num_cols = 1;
            }
            p.sections.category_descriptions.fields = {};
            if( rsp.section.categories != null ) {
                for(var i in rsp.section.categories) {
                    p.sections.category_descriptions.fields['category_' + rsp.section.categories[i].id] = {
                        'label':rsp.section.categories[i].name,
                        'idx':i,
                        'type':'textarea',
                        'size':'xlarge',
                        'autosize':'yes',
                        };
                }
            }
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.refresh();
            p.show(cb);
            p.updateForm();
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
    // The panel to edit Section Classes
    //
    this.classes = new M.panel('Section Classes', 'ciniki_musicfestivals_main', 'classes', 'mc', 'full', 'sectioned', 'ciniki.musicfestivals.main.classes');
    this.classes.data = null;
    this.classes.festival_id = 0;
    this.classes.section_id = 0;
    this.classes.nplists = {};
    this.classes.nplist = [];
    this.classes.sections = {
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'fees', 
            'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x40); },
            'tabs':{
                'fees':{'label':'Fees', 'fn':'M.ciniki_musicfestivals_main.classes.switchTab("fees");'},
                'trophies':{'label':'Trophies', 'fn':'M.ciniki_musicfestivals_main.classes.switchTab("trophies");'},
            }},
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':9,
            'headerValues':['Order', 'Category', 'Code', 'Class', '#', 'Competitors', 'Titles', 'Levels', 'Earlybird', 'Live', 'Virtual'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'number', 'text', 'number', 'number', 'number'],
            'dataMaps':['joined_sequence', 'category_name', 'code', 'class_name', 'num_competitors', 'competitor_type', 'num_titles', 'level', 'earlybird_fee', 'fee', 'virtual_fee'],
            'noData':'No classes',
            'menu':{
                'visible':function() { return M.ciniki_musicfestivals_main.classes.sections._tabs.selected == 'fees' ? 'yes' : 'no'; },
                'updateearlybirdfees':{
                    'label':'Update Earlybird Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x20) == 0x20 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.classes.updateFees("earlybird", "earlybird_fee");',
                    },
                'updatefees':{
                    'label':'Update Fees',
                    'fn':'M.ciniki_musicfestivals_main.classes.updateFees("", "fee");',
                    },
                'updatevirtualfees':{
                    'label':'Update Virtual Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x04) == 0x04 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.classes.updateFees("virtual", "virtual_fee");',
                    },
                'updateearlybirdplusfees':{
                    'label':'Update Earlybird Plus Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x30) == 0x30 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.classes.updateFees("earlybird plus", "earlybird_plus_fee");',
                    },
                'updateplusfees':{
                    'label':'Update Plus Fees',
                    'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x10) == 0x10 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.classes.updateFees("plus", "plus_fee");',
                    },
                },
            },
/*        '_buttons':{'label':'', 'halfsize':'yes', 'buttons':{
            'syllabuspdf':{'label':'Download Syllabus (PDF)', 'fn':'M.ciniki_musicfestivals_main.section.downloadSyllabusPDF();'},
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.section.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.section.section_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.section.remove();'},
            }}, */
        };
    this.classes.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.open();
    }
    this.classes.cellValue = function(s, i, j, d) {
        if( this.sections.classes.dataMaps[j] != null ) {
            if( this.sections.classes.dataMaps[j].match(/fee/) ) {
                return M.formatDollar(d[this.sections.classes.dataMaps[j]]);
            }
            else if( this.sections.classes.dataMaps[j] == 'num_competitors' ) {
                if( d.min_competitors == d.max_competitors ) {
                    return d.min_competitors;
                }
                return d.min_competitors + ' - ' + d.max_competitors;
            }
            else if( this.sections.classes.dataMaps[j] == 'competitor_type' ) {
                var ig = ' [I or G]';
                if( (d.flags&0xC000) == 0x4000 ) {
                    ig = ' person';
                    if( d.min_competitors > 1 || d.max_competitors > 1 ) {
                        ig = ' people';
                    }
                } else if( (d.flags&0xC000) == 0x8000 ) {
                    ig = ' group';
                    if( d.min_competitors > 1 || d.max_competitors > 1 ) {
                        ig = ' groups';
                    }
                }
                return ig;
            }
            else if( this.sections.classes.dataMaps[j] == 'num_titles' ) {
                if( d.min_titles == d.max_titles ) {
                    return d.min_titles;
                }
                return d.min_titles + ' - ' + d.max_titles;
            }
            if( this.sections.classes.dataMaps[j] == 'num_registrations' && d[this.sections.classes.dataMaps[j]] == 0 ) {
                return '';
            }
            return d[this.sections.classes.dataMaps[j]];
        }
    }
    this.classes.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.class.open(\'M.ciniki_musicfestivals_main.classes.open();\',\'' + d.id + '\',\'' + d.category_id + '\',\'' + this.festival_id + '\',M.ciniki_musicfestivals_main.classes.nplists.classes);';
    }
    this.classes.downloadSyllabusPDF = function() {
        M.api.openPDF('ciniki.musicfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_id':this.section_id});
    }
    this.classes.updateFees = function(label, field) {
        this.popupMenuClose('classes');
        M.prompt('Enter how much to add to each ' + label + ' fee in this list:', '', 'Update', function(n) {
            if( n != 0 && n != '0' && n != '' ) {
                var args = {
                    'tnid':M.curTenantID, 
                    'section_id':M.ciniki_musicfestivals_main.classes.section_id, 
                    'festival_id':M.ciniki_musicfestivals_main.classes.festival_id,
                    }; 
                args[field+'_update'] = n;
                M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.classes.open();
                    });
            }
        });
    }
    this.classes.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.section_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.sectionClasses', {'tnid':M.curTenantID, 'section_id':this.section_id, 'festival_id':this.festival_id, 'list':this.sections._tabs.selected}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.classes;
            p.data = rsp;
            p.sections.classes.headerValues = ['Order', 'Category', 'Code', 'Class', '#', 'Competitors', 'Titles'];
            p.sections.classes.sortTypes = ['text', 'text', 'text', 'text', 'number', 'text', 'number'];
            p.sections.classes.dataMaps = ['joined_sequence', 'category_name', 'code', 'class_name', 'num_competitors', 'competitor_type', 'num_titles'];
            p.sections.classes.cellClasses = ['', '', '', '', 'alignright', '', 'aligncenter'];
            if( p.sections._tabs.selected == 'trophies' ) {
                p.sections.classes.headerValues.push('Trophies');
                p.sections.classes.sortTypes.push('text');
                p.sections.classes.dataMaps.push('trophies');
                p.sections.classes.cellClasses.push('');
                p.sections.classes.headerValues.push('# Reg');
                p.sections.classes.sortTypes.push('number');
                p.sections.classes.dataMaps.push('num_registrations');
                p.sections.classes.cellClasses.push('alignright');
            } else {
                if( M.modFlagOn('ciniki.musicfestivals', 0x1000) ) {
                    p.sections.classes.headerValues.push('Levels');
                    p.sections.classes.sortTypes.push('text');
                    p.sections.classes.dataMaps.push('levels');
                    p.sections.classes.cellClasses.push('');
                }
                if( (rsp.festival.flags&0x20) == 0x20 ) {
                    p.sections.classes.headerValues.push('Earlybird');
                    p.sections.classes.sortTypes.push('number');
                    p.sections.classes.dataMaps.push('earlybird_fee');
                    p.sections.classes.cellClasses.push('alignright');
                }
                p.sections.classes.headerValues.push('Fee');
                p.sections.classes.sortTypes.push('number');
                p.sections.classes.dataMaps.push('fee');
                p.sections.classes.cellClasses.push('alignright');
                if( (rsp.festival.flags&0x04) == 0x04 ) {
                    p.sections.classes.headerValues.push('Virtual');
                    p.sections.classes.sortTypes.push('number');
                    p.sections.classes.dataMaps.push('virtual_fee');
                    p.sections.classes.cellClasses.push('alignright');
                }
                if( (rsp.festival.flags&0x10) == 0x10 ) {   // Adjudication plus
                    if( (rsp.festival.flags&0x20) == 0x20 ) {   // Earlybird Pricing
                        p.sections.classes.headerValues.push('Earlybird Plus');
                        p.sections.classes.sortTypes.push('number');
                        p.sections.classes.dataMaps.push('earlybird_plus_fee');
                        p.sections.classes.cellClasses.push('alignright');
                    }
                    p.sections.classes.headerValues.push('Plus');
                    p.sections.classes.sortTypes.push('number');
                    p.sections.classes.dataMaps.push('plus_fee');
                    p.sections.classes.cellClasses.push('alignright');
                }
                p.sections.classes.headerValues.push('# Reg');
                p.sections.classes.sortTypes.push('number');
                p.sections.classes.dataMaps.push('num_registrations');
                p.sections.classes.cellClasses.push('alignright');
            }
            p.sections.classes.headerValues.push('Time');
            p.sections.classes.sortTypes.push('number');
            p.sections.classes.dataMaps.push('schedule_time');
            p.sections.classes.cellClasses.push('');
            p.sections.classes.headerClasses = p.sections.classes.cellClasses;
            p.sections.classes.num_cols = p.sections.classes.headerValues.length;

            p.festival_id = rsp.section.festival_id;
            p.sections.classes.label = rsp.section.name + ' - Classes';
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.classes.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.classes.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) + 1] + ');';
        }
        return null;
    }
    this.classes.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.classes.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) - 1] + ');';
        }
        return null;
    }
    this.classes.addClose('Cancel');
    this.classes.addButton('next', 'Next');
    this.classes.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Category
    //
    this.category = new M.panel('Section Category', 'ciniki_musicfestivals_main', 'category', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.category');
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
            'section_id':{'label':'Section', 'type':'select', 'complex_options':{'value':'id', 'name':'syllabus_name'}, 'options':{}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'groupname':{'label':'Group', 'type':'text',
                'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x0400) == 0x0400 ? 'yes' : 'no'; },
                'livesearch':'yes', 'livesearchempty':'yes',
                },
            'sequence':{'label':'Order', 'required':'yes', 'type':'text'},
            }},
//        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'classes', 'tabs':{
//            'classes':{'label':'Classes', 'fn':'M.ciniki_musicfestivals_main.category.switchTab(\'classes\');'},
//            'synopsis':{'label':'Description', 'fn':'M.ciniki_musicfestivals_main.category.switchTab(\'synopsis\');'},
//            }},
//        '_synopsis':{'label':'Synopsis', 
//            'visible':function() { return M.ciniki_musicfestivals_main.category.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
//            'fields':{'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'}},
//            },
        '_description':{'label':'Description', 
//            'visible':function() { return M.ciniki_musicfestivals_main.category.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'}},
            },
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':5,
//            'visible':function() { return M.ciniki_musicfestivals_main.category.sections._tabs.selected == 'classes' ? 'yes' : 'hidden'; },
            'headerValues':['Code', 'Name', 'Earlybird', 'Fee', 'Virtual'],
            'cellClasses':['', ''],
            'dataMaps':['code', 'name'],
            'menu':{
                'add':{
                    'label':'Add Class',
                    'fn':'M.ciniki_musicfestivals_main.category.openClass(0);',
                    },
                },
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.musicfestivals.classUpdate', {'tnid':M.curTenantID, 
                    'class_id':M.ciniki_musicfestivals_main.category.data.classes[from].id,
                    'sequence':M.ciniki_musicfestivals_main.category.data.classes[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.category.refreshClasses();
                    });
                },
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
    this.category.liveSearchCb = function(s, i, value) {
        if( i == 'groupname' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.categoryFieldSearch', 
                {'tnid':M.curTenantID, 'field':i, 'festival_id':this.festival_id, 'start_needle':value, 'limit':25}, function(rsp) { 
                    M.ciniki_musicfestivals_main.category.liveSearchShow(s, i, M.gE(M.ciniki_musicfestivals_main.category.panelUID + '_' + i), rsp.results); 
                });
        }
    }
    this.category.liveSearchResultValue = function(s, f, i, j, d) {
        return d.value;
    }
    this.category.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_musicfestivals_main.category.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.value) + '\');';
    }
    this.category.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.category.cellValue = function(s, i, j, d) {
        return d[this.sections[s].dataMaps[j]];
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
        this.save("M.ciniki_musicfestivals_main.class.open('M.ciniki_musicfestivals_main.category.open();','" + cid + "', M.ciniki_musicfestivals_main.category.category_id, M.ciniki_musicfestivals_main.category.festival_id, M.ciniki_musicfestivals_main.category.nplists.classes);");
    }
    this.category.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.refresh();
        this.show();
    }
    this.category.refreshClasses = function() {
        M.api.getJSONCb('ciniki.musicfestivals.categoryGet', {'tnid':M.curTenantID, 'category_id':this.category_id, 'festival_id':this.festival_id, 'section_id':this.section_id, 'classes':'yes'}, function(rsp) {
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
            p.refreshSection('classes');
        });
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
            p.sections.classes.headerValues = ['Code', 'Name'];
            p.sections.classes.dataMaps = ['code', 'name'];
            p.sections.classes.cellClasses = ['', ''];
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x20) == 0x20 ) {
                p.sections.classes.headerValues.push('Earlybird');
                p.sections.classes.cellClasses.push('alignright');
                p.sections.classes.dataMaps.push('earlybird_fee');
            }
            p.sections.classes.headerValues.push('Fee');
            p.sections.classes.cellClasses.push('alignright');
            p.sections.classes.dataMaps.push('fee');
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x04) == 0x04 ) {
                p.sections.classes.headerValues.push('Virtual');
                p.sections.classes.cellClasses.push('alignright');
                p.sections.classes.dataMaps.push('virtual_fee');
            }
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x30) == 0x30 ) {
                p.sections.classes.headerValues.push('Earlybird Plus');
                p.sections.classes.cellClasses.push('alignright');
                p.sections.classes.dataMaps.push('earlybird_plus_fee');
            }
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x10) == 0x10 ) {
                p.sections.classes.headerValues.push('Plus');
                p.sections.classes.cellClasses.push('alignright');
                p.sections.classes.dataMaps.push('plus_fee');
            }
            p.sections.classes.headerClasses = p.sections.classes.cellClasses;
            p.sections.classes.num_cols = p.sections.classes.headerValues.length;
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
        M.confirm('Are you sure you want to remove category? All classes in the category will be deleted.',null,function() {
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
        'general':{'label':'Class Details', 'aside':'yes', 'fields':{
            'category_id':{'label':'Category', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'code':{'label':'Code', 'type':'text', 'size':'small'},
            'name':{'label':'Name', 'type':'text'},
//            'level':{'label':'Level', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
//                'visible':function() {return M.modFlagSet('ciniki.musicfestivals', 0x1000); },
//                },
            'levels':{'label':'Level', 'type':'tags', 'tags':[], 'hint':'Enter a new level:', 'sort':'no',
                'visible':function() {return M.modFlagSet('ciniki.musicfestivals', 0x1000); },
                },
            'sequence':{'label':'Order', 'type':'text'},
            'feeflags1':{'label':'Earlybird', 'type':'flagtoggle', 'bit':0x01, 'default':'on', 'field':'feeflags',
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm',
                'visible':function() {
                    if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                        && (M.ciniki_musicfestivals_main.festival.data.flags&0x20) == 0x20 ) {
                        return 'yes';
                    }
                    return 'no';
                }},
            'earlybird_fee':{'label':'Earlybird Fee', 'type':'text', 'size':'small', 'visible':'no', },
            'feeflags2':{'label':'Regular', 'type':'flagtoggle', 'bit':0x02, 'default':'on', 'field':'feeflags',
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm',
                },
            'fee':{'label':'Fee', 'type':'text', 'size':'small', 'visible':'no'},
            'feeflags4':{'label':'Virtual', 'type':'flagtoggle', 'bit':0x08, 'default':'on', 'field':'feeflags',
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm',
                'visible':function() {
                    if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                        && (M.ciniki_musicfestivals_main.festival.data.flags&0x06) == 0x06 ) {
                        return 'yes';
                    }
                    return 'no';
                }},
            'virtual_fee':{'label':'Virtual Fee', 'type':'text', 'size':'small', 'visible':'no'},
            'feeflags5':{'label':'Earlybird Plus', 'type':'flagtoggle', 'bit':0x10, 'default':'on', 'field':'feeflags',
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm',
                'visible':function() {
                    if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                        && (M.ciniki_musicfestivals_main.festival.data.flags&0x30) == 0x30 ) {
                        return 'yes';
                    }
                    return 'no';
                }},
            'earlybird_plus_fee':{'label':'Earlybird Plus Fee', 'type':'text', 'size':'small', 'visible':'no'},
            'feeflags6':{'label':'Plus', 'type':'flagtoggle', 'bit':0x20, 'default':'on', 'field':'feeflags',
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm',
                'visible':function() {
                    if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                        && (M.ciniki_musicfestivals_main.festival.data.flags&0x10) == 0x10 ) {
                        return 'yes';
                    }
                    return 'no';
                }},
            'plus_fee':{'label':'Plus Fee', 'type':'text', 'size':'small', 'visible':'no'},
            'flags6':{'label':'Virtual Only', 'type':'flagtoggle', 'bit':0x20, 'default':'off', 'field':'flags', 
                'visible':function() {
                    // Only show Virtual Only option when virtual option but no virtual pricing for festival
                    if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                        && (M.ciniki_musicfestivals_main.festival.data.flags&0x06) == 0x02 ) {
                        return 'yes';
                    }
                    return 'no';
                }},
            }},
        'registration':{'label':'Registration Options', 'aside':'yes', 'fields':{
            'flags1':{'label':'Online Registrations', 'type':'flagtoggle', 'default':'on', 'bit':0x01, 'field':'flags'},
            'flags2':{'label':'Multiple/Registrant', 'type':'flagtoggle', 'default':'on', 'bit':0x02, 'field':'flags'},
            'flags15':{'label':'Individuals/Group', 'type':'flagspiece', 'mask':0xC000, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm',
                'flags':{'0':{'name':'Either'}, '15':{'name':'Individual(s)'}, '16':{'name':'Group/Ensemble'}},
                },
            'min_competitors':{'label':'Min Competitors', 'type':'toggle', 'default':'1', 
                'toggles':{'0':'0', '1':'1', '2':'2', '3':'3', '4':'4'},
                },
            'max_competitors':{'label':'Max Competitors', 'type':'toggle', 'default':'1', 
                'toggles':{'0':'0', '1':'1', '2':'2', '3':'3', '4':'4'},
                },
//            'flags5':{'label':'2nd Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x10, 'field':'flags', 'visible':'yes'},
//            'flags6':{'label':'3rd Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x20, 'field':'flags', 'visible':'yes'},
//            'flags7':{'label':'4th Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x40, 'field':'flags', 'visible':'yes'},
            'flags3':{'label':'Instrument Required', 'type':'flagtoggle', 'default':'off', 'bit':0x04, 'field':'flags'},
            'flags13':{'label':'Accompanist', 'type':'flagspiece', 'mask':0x3000, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x8000); },
                'flags':{'0':{'name':'None'}, '13':{'name':'Required'}, '14':{'name':'Optional'}},
                },
            }},
        'titles':{'label':'Registration Titles', 'aside':'yes', 'fields':{
            'min_titles':{'label':'Minimum Titles', 'type':'toggle', 'default':'1', 'separator':'yes',
                'toggles':{'0':'0', '1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', '7':'7', '8':'8'},
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm();',
                },
            'max_titles':{'label':'Maximum Titles', 'type':'toggle', 'default':'1', 
                'toggles':{'0':'0', '1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', '7':'7', '8':'8'},
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm();',
                },
            'flags5':{'label':'Fixed Title(s)', 'type':'flagtoggle', 'bit':0x10, 'field':'flags', 'default':'off',
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm();',
                },
            'flags27':{'label':'Movements/Musical', 'type':'flagspiece', 'mask':0x0C000000, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'flags':{'0':{'name':'Hidden'}, '27':{'name':'Required'}, '28':{'name':'Optional'}},
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm();',
                },
            'flags29':{'label':'Composer', 'type':'flagspiece', 'mask':0x30000000, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'flags':{'0':{'name':'Hidden'}, '29':{'name':'Required'}, '30':{'name':'Optional'}},
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm();',
                },
            'flags17':{'label':'Virtual - Video', 'type':'flagspiece', 'mask':0x030000, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'visible':function() { return M.ciniki_musicfestivals_main.festival.isVirtual(); },
                'flags':{' 18':{'name':'Hidden'}, ' 17':{'name':'Required'}, ' 0':{'name':'Optional'}},
                },
            'flags21':{'label':'Virtual - Music', 'type':'flagspiece', 'mask':0x300000, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'visible':function() { return M.ciniki_musicfestivals_main.festival.isVirtual(); },
                'flags':{' 22':{'name':'Hidden'}, ' 21':{'name':'Required'}, ' 0':{'name':'Optional'}},
                },
//            'flags23':{'label':'Live Music PDF', 'type':'flagspiece', 'mask':0xC00000, 'field':'flags', 'join':'yes', 'toggle':'yes',
//                'flags':{'0':{'name':'Hidden'}, '23':{'name':'Required'}, '24':{'name':'Optional'}},
//                },
            'flags25':{'label':'Backtrack', 'type':'flagspiece', 'mask':0x03000000, 'field':'flags', 'join':'yes', 'toggle':'yes',
//                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); }, 
                'flags':{'0':{'name':'None'}, '25':{'name':'Required'}, '26':{'name':'Optional'}},
                },
            'titleflags9':{'label':'Artwork', 'type':'flagspiece', 'mask':0x0300, 'field':'titleflags', 'join':'yes', 'toggle':'yes',
                'flags':{'0':{'name':'None'}, '9':{'name':'Required'}, '10':{'name':'Optional'}},
                },
            'flags9':{'label':'Marking', 'type':'flagspiece', 'mask':0x0700, 'field':'flags', 'join':'yes', 'none':'yes',
                'flags':{'9':{'name':'Mark'}, '10':{'name':'Placement'}, '11':{'name':'Level'}},
                },
            'provincials_code':{'label':'Provincials Code', 'type':'text'},
            }},
        'scheduling':{'label':'Scheduling Options', 'aside':'yes', 'fields':{
            'flags19':{'label':'Schedule Time', 'type':'flagspiece', 'mask':0x0C0000, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'flags':{'0':{'name':'None'}, '19':{'name':'Perf+Adj'}, '20':{'name':'Total'}},
                'onchange':'M.ciniki_musicfestivals_main.class.updateForm();',
                },
            'schedule_seconds':{'label':'Schedule Time/Registration', 'type':'minsec', 'visible':'no'},
            'schedule_at_seconds':{'label':'Talk Time/Class', 'type':'minsec', 'visible':'no'},
            'schedule_ata_seconds':{'label':'Additional Talk Time/Reg', 'type':'minsec', 'visible':'no'},
            }},
        '_fixed_title1':{'label':'Fixed Title #1', 
            'visible':'hidden',
            'fields':{
                'title1':{'label':'Title', 'type':'text'},
                'movements1':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer1':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time1':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_fixed_title2':{'label':'Fixed Title #2', 
            'visible':'hidden',
            'fields':{
                'title2':{'label':'Title', 'type':'text'},
                'movements2':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer2':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time2':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_fixed_title3':{'label':'Fixed Title #3', 
            'visible':'hidden',
            'fields':{
                'title3':{'label':'Title', 'type':'text'},
                'movements3':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer3':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time3':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_fixed_title4':{'label':'Fixed Title #4', 
            'visible':'hidden',
            'fields':{
                'title4':{'label':'Title', 'type':'text'},
                'movements4':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer4':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time4':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_fixed_title5':{'label':'Fixed Title #5', 
            'visible':'hidden',
            'fields':{
                'title5':{'label':'Title', 'type':'text'},
                'movements5':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer5':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time5':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_fixed_title6':{'label':'Fixed Title #6', 
            'visible':'hidden',
            'fields':{
                'title6':{'label':'Title', 'type':'text'},
                'movements6':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer6':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time6':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_fixed_title7':{'label':'Fixed Title #7', 
            'visible':'hidden',
            'fields':{
                'title7':{'label':'Title', 'type':'text'},
                'movements7':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer7':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time7':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_fixed_title8':{'label':'Fixed Title #8', 
            'visible':'hidden',
            'fields':{
                'title8':{'label':'Title', 'type':'text'},
                'movements8':{'label':'Movements/Musical', 'type':'text', 'visible':'no'},
                'composer8':{'label':'Composer', 'type':'text', 'visible':'no'},
                'perf_time8':{'label':'Performance Time', 'type':'minsec'},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3, 
            'headerValues':['Competitor', 'Teacher', 'Status'],
            'noData':'No registrations',
            },
        'trophies':{'label':'Trophies & Awards', 'type':'simplegrid', 'num_cols':3, 
            'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x40); }, 
            'headerValues':['Type', 'Category', 'Name'],
            'cellClasses':['', '', 'alignright'],
            'noData':'No trophies',
            'menu':{
                'add':{
                    'label':'Add Trophy/Award',
                    'fn':'M.ciniki_musicfestivals_main.class.save("M.ciniki_musicfestivals_main.class.addTrophy();");',
                    },
                },
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.class.save();'},
            'duplicate':{'label':'Duplicate', 
                'visible':function() {return M.ciniki_musicfestivals_main.class.class_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.class.save("M.ciniki_musicfestivals_main.class.duplicate();");',
                },
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.class.class_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.class.remove();',
                },
            }},
        };
    this.class.fieldValue = function(s, i, d) { return this.data[i]; }
    this.class.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.classHistory', 'args':{'tnid':M.curTenantID, 'class_id':this.class_id, 'field':i}};
    }
    this.class.cellValue = function(s, i, j, d) {
        if( s == 'registrations' ) {
            switch(j) {
                case 0: return d.display_name; // + M.subdue(' (',d.pronoun,')');
                case 1: return d.teacher_name;
                case 2: return d.status_text;
            }
        }
        if( s == 'trophies' || s == 'awards' ) {
            switch(j) {
                case 0: return d.category;
                case 1: return d.name;
                case 2: return '<button onclick="M.ciniki_musicfestivals_main.class.removeTrophy(\'' + d.id + '\');">Remove</button>';
            }
        }
    }
    this.class.rowFn = function(s, i, d) {
        if( s == 'registrations' ) {
            return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.class.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.class.festival_id, null,\'festival\');';
        }
        return '';
    }
    this.class.addTrophy = function() {
        M.ciniki_musicfestivals_main.classtrophy.open('M.ciniki_musicfestivals_main.class.open();',this.class_id);
    }
//    this.class.addAward = function() {
//        M.ciniki_musicfestivals_main.classtrophy.open('M.ciniki_musicfestivals_main.class.open();',this.class_id,40);
//    }
    this.class.attachTrophy = function(i) {
        M.api.getJSONCb('ciniki.musicfestivals.classTrophyAdd', {'tnid':M.curTenantID, 'class_id':this.class_id, 'trophy_id':i}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.class.open();
            });
    }
    this.class.removeTrophy = function(i) {
        M.api.getJSONCb('ciniki.musicfestivals.classTrophyRemove', {'tnid':M.curTenantID, 'class_id':this.class_id, 'tc_id':i}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.class.open();
            });
    }
    this.class.updateForm = function() {
        this.sections.general.fields.earlybird_fee.visible = 'no';
        if( M.ciniki_musicfestivals_main.festival.data.flags != null 
            && (M.ciniki_musicfestivals_main.festival.data.flags&0x20) == 0x20 
            && this.formValue('feeflags1') == 'on' 
            ) {
            this.sections.general.fields.earlybird_fee.visible = 'yes';
        }
        this.showHideFormField('general','fee');
        this.sections.general.fields.fee.visible = 'no';
        if( this.formValue('feeflags2') == 'on' ) {
            this.sections.general.fields.fee.visible = 'yes';
        }
        this.sections.general.fields.virtual_fee.visible = 'no';
        if( M.ciniki_musicfestivals_main.festival.data.flags != null 
            && (M.ciniki_musicfestivals_main.festival.data.flags&0x06) == 0x06 
            && this.formValue('feeflags4') == 'on' 
            ) {
            this.sections.general.fields.virtual_fee.visible = 'yes';
        }
        this.sections.general.fields.earlybird_plus_fee.visible = 'no';
        if( M.ciniki_musicfestivals_main.festival.data.flags != null 
            && (M.ciniki_musicfestivals_main.festival.data.flags&0x30) == 0x30 
            && this.formValue('feeflags5') == 'on' 
            ) {
            this.sections.general.fields.earlybird_plus_fee.visible = 'yes';
        }
        this.sections.general.fields.plus_fee.visible = 'no';
        if( M.ciniki_musicfestivals_main.festival.data.flags != null 
            && (M.ciniki_musicfestivals_main.festival.data.flags&0x10) == 0x10 
            && this.formValue('feeflags6') == 'on' 
            ) {
            this.sections.general.fields.plus_fee.visible = 'yes';
        }
        // Force fee visible when no other options for pricing
        if( M.ciniki_musicfestivals_main.festival.data.flags != null 
            && (M.ciniki_musicfestivals_main.festival.data.flags&0x34) == 0 
            ) {
            this.sections.general.fields.fee.visible = 'yes';
        }
        this.showHideFormField('general','earlybird_fee');
        this.showHideFormField('general','fee');
        this.showHideFormField('general','virtual_fee');
        this.showHideFormField('general','earlybird_plus_fee');
        this.showHideFormField('general','plus_fee');
        var f = this.formValue('flags15');
        if( (f&0xC000) == 0x8000 ) {
            M.gE(this.panelUID + '_min_competitors_formlabel').innerHTML = 'Min Groups';
            M.gE(this.panelUID + '_max_competitors_formlabel').innerHTML = 'Max Groups';
        } else if( (f&0xC000) == 0x4000 ) {
            M.gE(this.panelUID + '_min_competitors_formlabel').innerHTML = 'Min Individuals';
            M.gE(this.panelUID + '_max_competitors_formlabel').innerHTML = 'Max Individuals';
        } else {
            M.gE(this.panelUID + '_min_competitors_formlabel').innerHTML = 'Min Competitors';
            M.gE(this.panelUID + '_max_competitors_formlabel').innerHTML = 'Max Competitors';
        }
        var n = this.formValue('max_titles');
        var m = parseInt(this.formValue('flags27'))&0x0C000000;
        var c = parseInt(this.formValue('flags29'))&0x30000000;
        var f = this.formValue('flags5');
        for(var i = 1;i<=8;i++) {
            if( i <= n && f == 'on' ) {
                this.sections['_fixed_title'+i].visible = 'yes';
                if( m > 0 ) {
                    this.sections['_fixed_title'+i].fields['movements'+i].visible = 'yes';
                } else {
                    this.sections['_fixed_title'+i].fields['movements'+i].visible = 'no';
                }
                if( c > 0 ) {
                    this.sections['_fixed_title'+i].fields['composer'+i].visible = 'yes';
                } else {
                    this.sections['_fixed_title'+i].fields['composer'+i].visible = 'no';
                }
            } else {
                this.sections['_fixed_title'+i].visible = 'hidden';
            }
            this.showHideSection('_fixed_title'+i);
            this.showHideFormField('_fixed_title'+i,'movements'+i);
            this.showHideFormField('_fixed_title'+i,'composer'+i);
        }
/*        if( M.ciniki_musicfestivals_main.festival.data['syllabus-schedule-time'] != null 
            && M.ciniki_musicfestivals_main.festival.data['syllabus-schedule-time'] == 'total'
            ) {
            this.sections.titles.fields.schedule_seconds.visible = 'yes';
            this.sections.titles.fields.schedule_seconds.label = 'Schedule Time';
        } else if( M.ciniki_musicfestivals_main.festival.data['syllabus-schedule-time'] != null 
            && M.ciniki_musicfestivals_main.festival.data['syllabus-schedule-time'] == 'adjudication'
            ) {
            this.sections.titles.fields.schedule_seconds.visible = 'yes';
            this.sections.titles.fields.schedule_seconds.label = 'Adjudication Time'; */
        var f = this.formValue('flags19');
        if( (f&0x0C0000) == 0x040000 ) {
            this.sections.scheduling.fields.schedule_seconds.visible = 'yes';
            this.sections.scheduling.fields.schedule_seconds.label = 'Adjudication Time/Reg';
        } else if( (f&0x0C0000) == 0x080000 ) {
            this.sections.scheduling.fields.schedule_seconds.visible = 'yes';
            this.sections.scheduling.fields.schedule_seconds.label = 'Total Time/Reg';
        } else {
            this.sections.scheduling.fields.schedule_seconds.visible = 'no';
        }
        if( (f&0x0C0000) > 0 
            && M.ciniki_musicfestivals_main.festival.data['scheduling-at-times'] != null
            && M.ciniki_musicfestivals_main.festival.data['scheduling-at-times'] == 'yes'
            ) {
            this.sections.scheduling.fields.schedule_at_seconds.visible = 'yes';
            this.sections.scheduling.fields.schedule_ata_seconds.visible = 'yes';
        } else {
            this.sections.scheduling.fields.schedule_at_seconds.visible = 'no';
            this.sections.scheduling.fields.schedule_ata_seconds.visible = 'no';
        }
        this.showHideFormField('scheduling', 'schedule_seconds');
        this.showHideFormField('scheduling', 'schedule_at_seconds');
        this.showHideFormField('scheduling', 'schedule_ata_seconds');
            
/*        if( (f&0xC000) == 0x8000 ) {
            this.sections.registration.fields.flags5.visible = 'no';
            this.sections.registration.fields.flags6.visible = 'no';
            this.sections.registration.fields.flags7.visible = 'no';
            this.showHideFormField('registration', 'flags5');
            this.showHideFormField('registration', 'flags6');
            this.showHideFormField('registration', 'flags7');
        } else {
            this.sections.registration.fields.flags5.visible = 'yes';
            this.sections.registration.fields.flags6.visible = 'yes';
            this.sections.registration.fields.flags7.visible = 'yes';
            this.showHideFormField('registration', 'flags5');
            this.showHideFormField('registration', 'flags6');
            this.showHideFormField('registration', 'flags7');
        } */
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
            p.sections.general.fields.levels.tags = [];
            if( rsp.levels != null ) {
                p.sections.general.fields.levels.tags = rsp.levels;
            }
            p.sections.general.fields.feeflags2.visible = 'no';
            if( M.ciniki_musicfestivals_main.festival.data.flags != null 
                && (M.ciniki_musicfestivals_main.festival.data.flags&0x34) > 0 
                ) {
                p.sections.general.fields.feeflags2.visible = 'yes';
            } else {
                p.sections.general.fields.fee.visible = 'yes';
            }
            p.refresh();
            p.show(cb);
            p.updateForm();
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
    this.class.duplicate = function() {
        this.class_id = 0;
        this.data.code += ' copy';
        this.data.name += ' copy';
        this.refreshFormField('general', 'code');
        this.refreshFormField('general', 'name');
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
            return 'M.ciniki_musicfestivals_main.class.save(\'M.ciniki_musicfestivals_main.class.open(null,' + this.nplist[this.nplist.indexOf('' + this.class_id) - 1] + ');\');';
        }
        return null;
    }
    this.class.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.class.save();');
    this.class.addClose('Cancel');
    this.class.addButton('next', 'Next');
    this.class.addLeftButton('prev', 'Prev');

    //
    // View and edit levels
    //
    this.levels = new M.panel('Levels', 'ciniki_musicfestivals_main', 'levels', 'mc', 'large', 'sectioned', 'ciniki.musicfestivals.main.levels');
    this.levels.festival_id = 0;
    this.levels.sections = {
        'levels':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'headerValues':['Level', 'Sorting'],
            },
        };
    this.levels.cellValue = function(s, i, j, d) {
        if( s == 'levels' ) {
            switch (j) {
                case 0: return d.tag_name;
                case 1: return d.tag_sort_name;
            }
        }
    }
    this.levels.cellFn = function(s, i, j, d) {
        if( s == 'levels' && j == 0 ) {
            return 'M.ciniki_musicfestivals_main.levels.classLevelNameChange(\'' + escape(d.tag_name) + '\');';
        } 
        if( s == 'levels' && j == 1 ) {
            return 'M.ciniki_musicfestivals_main.levels.classLevelSortChange(\'' + escape(d.tag_sort_name) + '\');';
        }
        return '';
    }
    this.levels.classLevelNameChange = function(old) {
        old = unescape(old);
        M.prompt('Edit Name:', old, 'Update', function(n) {
            if( old != n ) {
                M.api.getJSONCb('ciniki.musicfestivals.classTagUpdate', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.festival.festival_id, 'old_tag_name':old, 'new_tag_name':n}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.levels.open();
                });
            }
            });
    }
    this.levels.classLevelSortChange = function(old) {
        M.prompt('Edit Sort Name:', unescape(old), 'Update', function(n) {
            if( old != n ) {
                M.api.getJSONCb('ciniki.musicfestivals.classTagUpdate', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.festival.festival_id, 'old_tag_sort_name':old, 'new_tag_sort_name':n}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.levels.open();
                });
            }
            });
    }
    this.levels.open = function(cb) {
        M.api.getJSONCb('ciniki.musicfestivals.festivalGet', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.festival.festival_id, 'levels':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.levels;
            p.data = rsp.festival;
            p.refresh();
            p.show(cb);
        });
    }
    this.levels.addClose('Back');

    //
    // This panel lets the user select a trophy to attach to a class
    //
    this.classtrophy = new M.panel('Select Trophy', 'ciniki_musicfestivals_main', 'classtrophy', 'mc', 'large', 'sectioned', 'ciniki.musicfestivals.main.trophyclass');
    this.classtrophy.sections = {
        'trophies':{'label':'Select Trophy/Award', 'type':'simplegrid', 'num_cols':4,
            'noData':'No trophies',
            'cellClasses':['', '', '', 'alignright'],
            },
        };
    this.classtrophy.cellValue = function(s, i, j, d) {
        if( s == 'trophies' ) {
            switch(j) {
                case 0: return d.typename;
                case 1: return d.category;
                case 2: return d.name;
                case 3: return '<button onclick="M.ciniki_musicfestivals_main.class.attachTrophy(\'' + d.id + '\');">Add</button>';
            }
        }
    }
    this.classtrophy.open = function(cb, cid) {
        M.api.getJSONCb('ciniki.musicfestivals.trophyList', {'tnid':M.curTenantID, 'class_id':cid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.classtrophy;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.classtrophy.addClose('Back');

    //
    // Registration
    //
    this.registration = new M.panel('Registration', 'ciniki_musicfestivals_main', 'registration', 'mc', 'large mediumaside', 'sectioned', 'ciniki.musicfestivals.main.registration');
    this.registration.data = null;
    this.registration.festival_id = 0;
    this.registration.selected_class = null;
    this.registration.competitor1_id = 0;
    this.registration.competitor2_id = 0;
    this.registration.competitor3_id = 0;
    this.registration.competitor4_id = 0;
//    this.registration.competitor5_id = 0;
    this.registration.registration_id = 0;
    this.registration.nplist = [];
    this.registration._source = '';
    this.registration.sections = {
        'teacher_details':{'label':'Teacher', 'type':'customer', 'num_cols':2, 'aside':'yes',
            'customer_id':0,
            'customer_field':'teacher_customer_id',
            'cellClasses':['label', ''],
            'noData':'No Teacher',
            },
        'teacher2_details':{'label':'2nd Teacher', 'type':'customer', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.registration.data.teacher_customer_id > 0 ? 'yes' :'no';},
            'customer_id':0,
            'customer_field':'teacher2_customer_id',
            'cellClasses':['label', ''],
            'noData':'No 2nd Teacher',
            },
        'parent_details':{'label':'Parent', 'type':'customer', 'num_cols':2, 'aside':'yes',
            'visible':function() { 
                // Parent can be linked if teacher did registration
                return M.ciniki_musicfestivals_main.registration.data.teacher_customer_id == M.ciniki_musicfestivals_main.registration.data.billing_customer_id ? 'yes' : 'no';
                },
            'customer_id':0,
            'customer_field':'parent_customer_id',
            'cellClasses':['label', ''],
            'noData':'No Parent',
            },
        'accompanist_details':{'label':'Accompanist', 'type':'customer', 'num_cols':2, 'aside':'yes',
            'customer_id':0,
            'customer_field':'accompanist_customer_id',
            'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x8000); },
            'cellClasses':['label', ''],
            'noData':'No Accompanist',
            },
        '_display_name':{'label':'Duet/Trio/Ensemble Name', 'aside':'yes',
            'visible':function() { return M.ciniki_musicfestivals_main.registration.nameVisible(); },
            'fields':{ 
                'display_name':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        'competitor1_details':{'label':'Competitor 1', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            'menu':{
                'add':{
                    'label':'Add Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor1_id == 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 1);',
                    },
                'change':{
                    'label':'Different Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor1_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 1);',
                    },
                'edit':{
                    'label':'Edit Competitor Details',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor1_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor1_id, 1);',
                    },
                'del':{
                    'label':'No Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor1_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.delCompetitor(1);',
                    },
                },
            },
        'competitor2_details':{'label':'Competitor 2', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.registration.competitorVisible(2); },
            'cellClasses':['label', ''],
            'noData':'No Competitor',
            'menu':{
                'add':{
                    'label':'Add Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor2_id == 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 2);',
                    },
                'change':{
                    'label':'Different Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor2_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 2);',
                    },
                'edit':{
                    'label':'Edit Competitor Details',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor2_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor2_id, 2);',
                    },
                'del':{
                    'label':'No Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor2_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.delCompetitor(2);',
                    },
                },
            },
        'competitor3_details':{'label':'Competitor 3', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.registration.competitorVisible(3); },
            'cellClasses':['label', ''],
            'noData':'No Competitor',
            'menu':{
                'add':{
                    'label':'Add Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor3_id == 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 3);',
                    },
                'change':{
                    'label':'Different Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor3_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 3);',
                    },
                'edit':{
                    'label':'Edit Competitor Details',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor3_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor3_id, 3);',
                    },
                'del':{
                    'label':'No Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor3_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.delCompetitor(3);',
                    },
                },
            },
        'competitor4_details':{'label':'Competitor 4', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.registration.competitorVisible(4); },
            'cellClasses':['label', ''],
            'noData':'No Competitor',
            'menu':{
                'add':{
                    'label':'Add Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor4_id == 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 4);',
                    },
                'change':{
                    'label':'Different Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor4_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(0, 4);',
                    },
                'edit':{
                    'label':'Edit Competitor Details',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor4_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.addCompetitor(M.ciniki_musicfestivals_main.registration.competitor4_id, 4);',
                    },
                'del':{
                    'label':'No Competitor',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.competitor4_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.registration.delCompetitor(4);',
                    },
                },
            },
        '_class':{'label':'Registration', 
            'fields':{
                'status':{'label':'Status', 'type':'select', 'options':[]},
//                'flags13':{'label':'Colour', 'type':'flagspiece', 'mask':0xFF00, 'field':'flags', 'toggle':'yes', 'none':'yes', 'join':'yes', 'separator':'yes',
//                    'flags':{'9':{'name':'Grey'}, '10':{'name':'Teal'}, '11':{'name':'Blue'}, '12':{'name':'Purple'},'13':{'name':'Red'}, '14':{'name':'Orange'}, '15':{'name':'Yellow'}, '16':{'name':'Green'}},
//                    },
                'member_id':{'label':'From', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                    'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x010000); },
                    'onchangeFn':'M.ciniki_musicfestivals_main.registration.updateForm',
                    },
                'class_id':{'label':'Class', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                    'onchangeFn':'M.ciniki_musicfestivals_main.registration.updateForm',
                    },
                'participation':{'label':'Participate', 'type':'select', 
                    'visible':function() { return (M.ciniki_musicfestivals_main.registration.data.festival.flags&0x12) > 0 ? 'yes' : 'no'},
                    'onchangeFn':'M.ciniki_musicfestivals_main.registration.updateForm',
                    'options':{
                        '0':'in person on a date to be scheduled',
                        '1':'virtually and submit a video online',
                    }},
                'scheduled':{'label':'Scheduled', 'type':'text', 'editable':'no', },
                'scheduled_sd':{'label':'Section/Division', 'type':'text', 'editable':'no', },
//              'flags1':{'label':'Options', 'type':'flagspiece', 'visible':'no', 'mask':0x03, 'field':'flags', 'flags':{
//                  '1':{'name':'Share with Teacher'}, 
//                  '2':{'name':'Share with Accompanist'},
//                  }},
                'instrument':{'label':'Instrument', 'type':'text', 
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.instrumentVisible(); },
                    },
                'tags':{'label':'Tags', 'type':'tags', 'tags':[], 'separator':'yes', 'hint':'Enter a new tag:'},
                },
            'menu':{
                'comments':{
                    'label':'Download Comments PDF',
                    'fn':'M.ciniki_musicfestivals_main.registration.printComments();',
                    },
                'cert':{
                    'label':'Download Certificate PDF',
                    'fn':'M.ciniki_musicfestivals_main.registration.printCert();',
                    },
                'nobackgroundcert':{
                    'label':'Download Certificate PDF (no background)',
                    'fn':'M.ciniki_musicfestivals_main.registration.printBackgroundlessCert();',
                    },
                'pdf':{
                    'label':'Download Registration PDF',
                    'fn':'M.ciniki_musicfestivals_main.registration.printRegistration();',
                    },
                }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'titles', 'tabs':{
            'titles':{'label':'Titles', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("titles");'},
            'results':{'label':'Results', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("results");'},
            'payment':{'label':'Payment', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("payment");'},
            'notes':{'label':'Notes', 'fn':'M.ciniki_musicfestivals_main.registration.switchTab("notes");'},
            }},
        '_title1':{'label':'Title', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'titles' ? 'yes' : 'hidden'; },
            'fields':{
                'title1':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements1':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(1); },
                    },
                'composer1':{'label':'Composer', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(1); },
                    },
                'perf_time1':{'label':'Time', 'type':'minsec', 'size':'small'},
                'video_url1':{'label':'Video', 'type':'url',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(1); },
                    },
                'music_orgfilename1':{'label':'Music', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(1); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(1);',
                    },
                'backtrack1':{'label':'Backtrack', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(1); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(1);',
                    },
                'artwork1':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(1); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(1);',
                    },
            }},
        '_title2':{'label':'Title #2', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.titleVisible(2); },
            'fields':{
                'title2':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements2':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(2); },
                    },
                'composer2':{'label':'Composer', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(2); },
                    },
                'perf_time2':{'label':'Time', 'type':'minsec', 'max_minutes':30, 'second_interval':5, 'size':'small'},
                'video_url2':{'label':'Video', 'type':'url',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(2); },
                    },
                'music_orgfilename2':{'label':'Music', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(2); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(2);',
                    },
                'backtrack2':{'label':'Backtrack', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(2); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(2);',
                    },
                'artwork2':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(2); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(2);',
                    },
            }},
        '_title3':{'label':'Title #3', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.titleVisible(3); },
            'fields':{
                'title3':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements3':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(3); },
                    },
                'composer3':{'label':'Composer', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(3); },
                    },
                'perf_time3':{'label':'3rd Time', 'type':'minsec', 'max_minutes':30, 'second_interval':5, 'size':'small'},
                'video_url3':{'label':'Video', 'type':'url',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(3); },
                    },
                'music_orgfilename3':{'label':'Music', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(3); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(3);',
                    },
                'backtrack3':{'label':'Backtrack', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(3); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(3);',
                    },
                'artwork3':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(3); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(3);',
                    },
            }},
        '_title4':{'label':'Title #4', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.titleVisible(4); },
            'fields':{
                'title4':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements4':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(4); },
                    },
                'composer4':{'label':'Composer', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(4); },
                    },
                'perf_time4':{'label':'Time', 'type':'minsec', 'max_minutes':30, 'second_interval':5, 'size':'small'},
                'video_url4':{'label':'Video', 'type':'url',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(4); },
                    },
                'music_orgfilename4':{'label':'Music', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(4); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(4);',
                    },
                'backtrack4':{'label':'Backtrack', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(4); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(4);',
                    },
                'artwork4':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(4); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(4);',
                    },
            }},
        '_title5':{'label':'Title #5', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.titleVisible(5); },
            'fields':{
                'title5':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements5':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(5); },
                    },
                'composer5':{'label':'Composer', 'type':'text', 
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(5); },
                    },
                'perf_time5':{'label':'Time', 'type':'minsec', 'max_minutes':30, 'second_interval':5, 'size':'small'},
                'video_url5':{'label':'Video', 'type':'url',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(5); },
                    },
                'music_orgfilename5':{'label':'Music', 'type':'file', 'visible':'no',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(5); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(5);',
                    },
                'backtrack5':{'label':'Backtrack', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(5); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(5);',
                    },
                'artwork5':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(5); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(5);',
                    },
            }},
        '_title6':{'label':'Title #6', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.titleVisible(6); },
            'fields':{
                'title6':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements6':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(6); },
                    },
                'composer6':{'label':'Composer', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(6); },
                    },
                'perf_time6':{'label':'Time', 'type':'minsec', 'max_minutes':30, 'second_interval':5, 'size':'small'},
                'video_url6':{'label':'Video', 'type':'url',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(6); },
                    },
                'music_orgfilename6':{'label':'Music', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(6); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(6);',
                    },
                'backtrack6':{'label':'Backtrack', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(6); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(6);',
                    },
                'artwork6':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(6); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(6);',
                    },
            }},
        '_title7':{'label':'Title #7', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.titleVisible(7); },
            'fields':{
                'title7':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements7':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(7); },
                    },
                'composer7':{'label':'Composer', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(7); },
                    },
                'perf_time7':{'label':'Time', 'type':'minsec', 'max_minutes':30, 'second_interval':5, 'size':'small'},
                'video_url7':{'label':'Video', 'type':'url', 
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(7); },
                    },
                'music_orgfilename7':{'label':'Music', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(7); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(7);',
                    },
                'backtrack7':{'label':'Backtrack', 'type':'file', 
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(7); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(7);',
                    },
                'artwork7':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(7); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(7);',
                    },
            }},
        '_title8':{'label':'Title #8', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.titleVisible(8); },
            'fields':{
                'title8':{'label':'Title', 'type':'text', 'separator':'yes'},
                'movements8':{'label':'Movements/Musical', 'type':'text',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.movementsVisible(8); },
                    },
                'composer8':{'label':'Composer', 'type':'text', 
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.composerVisible(8); },
                    },
                'perf_time8':{'label':'Time', 'type':'minsec', 'max_minutes':30, 'second_interval':5, 'size':'small'},
                'video_url8':{'label':'Video', 'type':'url', 
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.videoVisible(8); },
                    },
                'music_orgfilename8':{'label':'Music', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.musicVisible(8); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadMusic(8);',
                    },
                'backtrack8':{'label':'Backtrack', 'type':'file', 
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.backtrackVisible(8); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadBacktrack(8);',
                    },
                'artwork8':{'label':'Artwork', 'type':'file',
                    'visible':function() { return M.ciniki_musicfestivals_main.registration.artworkVisible(8); },
                    'deleteFn':'M.ciniki_musicfestivals_main.registration.downloadArtwork(8);',
                    },
            }},
        '_results':{'label':'Results', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'results' ? 'yes' : 'hidden'; },
            'fields':{
                'mark':{'label':'Mark', 'type':'text', 'visible':'yes', 'size':'small', 
                    'onkeyup':'M.ciniki_musicfestivals_main.registration.updatePlacement',
                    },
                'placement':{'label':'Placement', 'type':'text', 'separator':'no', 'visible':'yes'},
                'level':{'label':'Level', 'type':'text', 'separator':'no', 'visible':'yes', 'size':'small'},
                'finals_placement':{'label':'Finals Placement', 'type':'text', 'separator':'no', 'visible':'yes'},
                'flags5':{'label':'Best in Class', 'type':'flagtoggle', 'bit':0x10, 'default':'off', 'field':'flags'},
            }},
        'provincials':{'label':'Provincials Recommendation',
            'visible':function() { return !M.modFlagOn('ciniki.musicfestivals', 0x010000) && M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'results' ? 'yes' : 'hidden';},
            'fields':{
                'provincials_position':{'label':'Place', 'type':'toggle', 'none':'yes', 
                    'visible':function() { return !M.modFlagOn('ciniki.musicfestivals', 0x010000) ? 'yes' : 'no';},
                    'toggles':{
                        '1':'1st',
                        '2':'2nd',
                        '3':'3rd',
                        '4':'4th',
                        '101':'1st Alt',
                        '102':'2nd Alt',
                        '103':'3rd Alt',
                    }},
                'provincials_status':{'label':'Status', 'type':'toggle', 'none':'yes',
                    'toggles':{
                        '30':'Recommended',
                        '50':'Accepted',
                        '70':'Ineligible',
                        '90':'Declined',
                    }},
            }},
        '_comments':{'label':'Adjudicator Comments', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'results' ? 'yes' : 'hidden'; },
            'fields':{
                'comments':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        'invoice_details':{'label':'Invoice', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'payment' ? 'yes' : 'hidden'; },
            'cellClasses':['label', ''],
            },
        '_fee':{'label':'', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'payment' ? 'yes' : 'hidden'; },
            'fields':{
                'fee':{'label':'Fee', 'type':'text', 'size':'small'},
            }},
        '_notes':{'label':'Registration Notes', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'notes' ? 'yes' : 'hidden'; },
            'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_internal_notes':{'label':'Internal Admin Notes', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'notes' ? 'yes' : 'hidden'; },
            'fields':{
                'internal_notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_runsheet_notes':{'label':'Runsheet Notes', 
            'visible':function() { return M.ciniki_musicfestivals_main.registration.sections._tabs.selected == 'notes' ? 'yes' : 'hidden'; },
            'fields':{
                'runsheet_notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.registration.save();'},
//            'printcert':{'label':'Download Certificate PDF', 
//                'visible':function() {return M.ciniki_musicfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
//                'fn':'M.ciniki_musicfestivals_main.registration.printCert();'},
//            'printcomments':{'label':'Download Comments PDF', 
//                'visible':function() {return M.ciniki_musicfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
//                'fn':'M.ciniki_musicfestivals_main.registration.printComments();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.registration.remove();'},
            }},
        };
    this.registration.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.showHideSections(['_results', 'provincials', '_comments', 'invoice_details', '_fee', '_notes', '_internal_notes', '_runsheet_notes']);
        for(var i = 1; i <= 8; i++) {
            this.showHideFormField('_title'+i, 'movements'+i);
            this.showHideFormField('_title'+i, 'composer'+i);
            this.showHideSection('_title'+i);
        }
    }
    this.registration.nameVisible = function() {
        if( (this.selected_class != null && (this.selected_class.flags&0x70) > 0) || this.data.competitor2_id > 0 ) {
            return 'yes';
        }
        return 'hidden';
    }
    this.registration.competitorVisible = function(i) {
        if( (this.selected_class != null && i <= this.selected_class.max_competitors) || this.data['cometitor'+i+'_id'] > 0 ) {
//        if( (this.selected_class != null && (this.selected_class.flags&0x10) == 0x10) || this.data.competitor2_id > 0 ) {
            return 'yes';
        }
        return 'hidden';
    }
    this.registration.instrumentVisible = function() {
        return this.selected_class != null && (this.selected_class.flags&0x04) == 0x04 ? 'yes' : 'no';
    }
    this.registration.titleVisible = function(i) {
        return this.sections._tabs.selected == 'titles' && this.selected_class != null && (this.selected_class.max_titles >= i || this.data['title'+i] != '') ? 'yes' : 'hidden';
    }
    this.registration.movementsVisible = function(i) {
        return (this.selected_class != null && (this.selected_class.flags&0x0C000000) > 0 || this.data['movements'+i] != '') ? 'yes' : 'no';
    }
    this.registration.composerVisible = function(i) {
        return (this.selected_class != null && (this.selected_class.flags&0x30000000) > 0 || this.data['composer'+i] != '') ? 'yes' : 'no';
    }
    this.registration.videoVisible = function(i) {
        return this.formValue('participation') == 1 ? 'yes' : 'no';
    }
    this.registration.musicVisible = function(i) {
        return this.formValue('participation') == 1 ? 'yes' : 'no';
    }
    this.registration.backtrackVisible = function(i) {
        return (this.selected_class != null && this.selected_class.flags&0x03000000) > 0 ? 'yes' : 'no';
    }
    this.registration.artworkVisible = function(i) {
        return (this.selected_class != null && this.selected_class.titleflags&0x0300) > 0 ? 'yes' : 'no';
    }
    this.registration.fieldValue = function(s, i, d) { 
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
        if( s == 'teacher_details' || s == 'teacher2_details' || s == 'parent_details' || s == 'accompanist_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1:
                    if( d.label == 'Email' ) {
                        return M.linkEmail(d.value);
                    } else if( d.label == 'Address' ) {
                        return d.value.replace(/\n/g, '<br/>');
                    }
                    return d.value;
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
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_musicfestivals_main.registration.show();\',\'mc\',{\'invoice_id\':\'' + this.data.invoice_id + '\'});';
        }
    }
    this.registration.updateForm = function(s, i, cf) {
        var festival = this.data.festival;
        var cid = this.formValue('class_id');
        var participation = this.formValue('participation');
        for(var i in this.classes) {
            if( this.classes[i].id == cid ) {
                this.selected_class = this.classes[i];
                var c = this.classes[i];
                var new_fee = 0;
                var old_fee = parseFloat(this.formValue('fee').replace(/[^\d\.]/,''));
                if( cf == null ) {
                    if( (festival.flags&0x10) == 0x10 && participation == 2 && c.earlybird_plus_fee > 0 ) {
                        new_fee = c.earlybird_plus_fee;
                    } else if( (festival.flags&0x10) == 0x10 && participation == 2 && c.plus_fee > 0 ) {
                        new_fee = c.plus_fee;
                    } else if( (festival.flags&0x04) == 0x04 && participation == 1 ) {
                        new_fee = c.virtual_fee;
                    } else if( festival.earlybird == 'yes' && c.earlybird_fee > 0 ) {
                        new_fee = c.earlybird_fee;
                    } else {
                        new_fee = c.fee;
                    }
                    if( new_fee != old_fee ) {
                        if( old_fee < new_fee ) {
                            var msg = 'The new class has a different fee. '
                                + 'Do you want to update the fee from ' + M.formatDollar(old_fee) 
                                + ' to ' + M.formatDollar(new_fee) + '? '
                                + 'This will change the invoice status to payment required.';
                        } else {
                            var msg = 'The new class has a different fee. '
                            + 'Do you want to update the fee from ' + M.formatDollar(old_fee) 
                            + ' to ' + M.formatDollar(new_fee) + '? '
                            + 'This will change the invoice status to refund required.';
                        }
                        M.simpleAsk(msg, 
                            'Update fee to ' + M.formatDollar(new_fee), 
                            'Keep old fee of ' + M.formatDollar(old_fee), 
                            function(yesno) {
                                if( yesno == 'yes' ) {
                                    M.ciniki_musicfestivals_main.registration.setFieldValue('fee', M.formatDollar(new_fee));
                                }
                            });
                    }
                }
                this.showHideSections(['_display_name', 'competitor2_details', 'competitor3_details', 'competitor4_details']);
                for(var i = 1; i <= 8; i++) {
                    this.showHideSection('_title'+i);
                    this.showHideFormField('_title'+i, 'movements'+i);
                    this.showHideFormField('_title'+i, 'composer'+i);
                    this.showHideFormField('_title'+i, 'video_url'+i);
                    this.showHideFormField('_title'+i, 'music_orgfilename'+i);
                    this.showHideFormField('_title'+i, 'backtrack'+i);
                    this.showHideFormField('_title'+i, 'artwork'+i);
                }
                this.showHideFormField('_class', 'instrument');

                this.sections._results.fields.mark.visible = (c.flags&0x0100) == 0x0100 ? 'yes' : 'no';
                this.sections._results.fields.placement.visible = (c.flags&0x0200) == 0x0200 ? 'yes' : 'no';
                this.sections._results.fields.level.visible = (c.flags&0x0400) == 0x0400 ? 'yes' : 'no';
                this.showHideFormField('_results', 'mark');
                this.showHideFormField('_results', 'placement');
                this.showHideFormField('_results', 'level');
            }
        }
    }
    this.registration.updatePlacement = function() {
        var m = this.formValue('mark');
        var p = M.ciniki_musicfestivals_main.placementAutofill(m);
        if( m != '' && p != '' ) {
            this.setFieldValue('placement', p);
        }
        var l = M.ciniki_musicfestivals_main.levelAutofill(m);
        if( m != '' && j != '' ) {
            this.setFieldValue('level', l);
        }
        var m = this.formValue('finals_mark');
        var p = M.ciniki_musicfestivals_main.placementAutofill(m);
        if( m != '' && p != '' ) {
            this.setFieldValue('finals_placement', p);
        }
        var l = M.ciniki_musicfestivals_main.levelAutofill(m);
        if( m != '' && j != '' ) {
            this.setFieldValue('finals_level', l);
        }
    }
    this.registration.addCompetitor = function(cid,c) {
        this.popupMenuClose('competitor'+c+'_details');
        M.ciniki_musicfestivals_main.competitor.open('M.ciniki_musicfestivals_main.registration.updateCompetitor(' + c + ');',cid,this.festival_id,null,M.ciniki_musicfestivals_main.registration.data.billing_customer_id);
    }
    this.registration.updateCompetitor = function(c) {
        var new_cid = M.ciniki_musicfestivals_main.competitor.competitor_id;
        if( new_cid > 0 ) {
            this['competitor' + c + '_id'] = new_cid;
            M.api.getJSONCb('ciniki.musicfestivals.competitorGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'competitor_id':new_cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.registration;
                p.data['competitor'+c+'_details'] = rsp.details;
                p.refreshSection('competitor'+c+'_details');
                p.show();
                });
        } else {
            this.show();
        }
    }
    this.registration.delCompetitor = function(cn) {
        var p = M.ciniki_musicfestivals_main.registration;
        M.ciniki_musicfestivals_main.registration;
        p['competitor'+cn+'_id'] = 0;
        p.data['competitor' + cn + '_details'] = {};
        p.refreshSection('competitor'+cn+'_details');
    }
    this.registration.printCert = function() {
        this.popupMenuClose('_class');
        M.api.openFile('ciniki.musicfestivals.registrationCertificatesPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
    this.registration.printBackgroundlessCert = function() {
        this.popupMenuClose('_class');
        M.api.openFile('ciniki.musicfestivals.registrationCertificatesPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id, 'background':'no'});
    }
    this.registration.printComments = function() {
        this.popupMenuClose('_class');
        M.api.openFile('ciniki.musicfestivals.registrationCommentsPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
    this.registration.printRegistration = function() {
        this.popupMenuClose('_class');
        M.api.openFile('ciniki.musicfestivals.registrationPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
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
    this.registration.downloadBacktrack = function(i) {
        M.api.openFile('ciniki.musicfestivals.registrationBacktrackDownload',{'tnid':M.curTenantID, 'registration_id':this.registration_id, 'num':i});
    }
    this.registration.downloadArtwork = function(i) {
        M.api.openFile('ciniki.musicfestivals.registrationArtworkDownload',{'tnid':M.curTenantID, 'registration_id':this.registration_id, 'num':i});
    }
//    this.registration.downloadPDF = function() {
//        M.api.openFile('ciniki.musicfestivals.registrationMusicPDF',{'tnid':M.curTenantID, 'registration_id':this.registration_id});
//    }
    this.registration.open = function(cb, rid, tid, cid, fid, list, source) {
        if( rid != null ) { this.registration_id = rid; }
        if( tid != null ) { this.sections.teacher_details.customer_id = tid; }
        if( fid != null ) { this.festival_id = fid; }
        if( cid != null ) { this.class_id = cid; }
        if( list != null ) { this.nplist = list; }
        if( source != null ) { this._source = source; }
        M.api.getJSONCb('ciniki.musicfestivals.registrationGet', {'tnid':M.curTenantID, 'registration_id':this.registration_id, 
            'teacher_customer_id':this.sections.teacher_details.customer_id, 'festival_id':this.festival_id, 'class_id':this.class_id, 
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
            p.sections._class.fields.status.editable = (rsp.registration.status < 10 ? 'no' : 'yes');
            p.sections._class.fields.status.options = [];
            p.sections._class.fields.status.options[5] = 'Draft';
            p.sections._class.fields.status.options[10] = 'Registered';
            for(var i = 31;i<=38;i++) {
                if( rsp.registration.festival['registration-status-' + i + '-label'] != null
                    && rsp.registration.festival['registration-status-' + i + '-label'] != ''
                    ) {
                    p.sections._class.fields.status.options[i] = rsp.registration.festival['registration-status-' + i + '-label'];
                }
            }
            p.sections._class.fields.status.options[50] = 'Approved';
            for(var i = 50;i<=55;i++) {
                if( rsp.registration.festival['registration-status-' + i + '-label'] != null
                    && rsp.registration.festival['registration-status-' + i + '-label'] != ''
                    ) {
                    p.sections._class.fields.status.options[i] = rsp.registration.festival['registration-status-' + i + '-label'];
                }
            }
            p.sections._class.fields.status.options[70] = 'Disqualified';
            p.sections._class.fields.status.options[75] = 'Withdrawn';
            p.sections._class.fields.status.options[80] = 'Cancelled';

            p.sections._class.fields.member_id.options = [];
            if( rsp.members != null ) {
                p.sections._class.fields.member_id.options = rsp.members;
            }
//            p.sections._tabs.selected = rsp.registration.rtype;
            p.sections._class.fields.class_id.options = rsp.classes;
            this.selected_class = null;
            for(i in rsp.classes) {
                if( rsp.registration.class_id == rsp.classes[i].id ) {
                    this.selected_class = rsp.classes[i];
                }
            }
            p.sections._class.fields.class_id.options.unshift({'id':0, 'name':''});
            p.sections.teacher_details.customer_id = parseInt(rsp.registration.teacher_customer_id);
            p.sections.teacher2_details.customer_id = parseInt(rsp.registration.teacher2_customer_id);
            p.sections.accompanist_details.customer_id = parseInt(rsp.registration.accompanist_customer_id);
            p.sections.parent_details.customer_id = parseInt(rsp.registration.parent_customer_id);
            for(var i = 1; i<= 4; i++) {
                p['competitor' + i + '_id'] = parseInt(rsp.registration['competitor' + i + '_id']);
            }
            if( (p.data.festival.flags&0x10) == 0x10 ) {
                p.sections._class.fields.participation.options = {
                    '0':'Regular Adjudication',
                    '2':'Adjudication Plus',
                    };
            }
            else if( (p.data.festival.flags&0x02) == 0x02 ) {
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
            }
            if( rsp.tags != null ) {
                p.sections._class.fields.tags.tags = rsp.tags;
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
        if( this.registration_id > 0 ) {
            var c = this.serializeFormData('no');
            if( this.competitor1_id != this.data.competitor1_id ) { c.append('competitor1_id', this.competitor1_id); }
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
            return 'M.ciniki_musicfestivals_main.registration.save(\'M.ciniki_musicfestivals_main.registration.open(null,' + this.nplist[this.nplist.indexOf('' + this.registration_id) - 1] + ');\');';
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
    this.competitor.billing_customer_id = 0;
    this.competitor.nplist = [];
    this.competitor.sections = {
        '_ctype':{'label':'', 'type':'paneltabs', 'selected':10, 'aside':'yes', 'tabs':{
            '10':{'label':'Individual', 'fn':'M.ciniki_musicfestivals_main.competitor.switchType("10");'},
            '50':{'label':'Group/Ensemble', 'fn':'M.ciniki_musicfestivals_main.competitor.switchType("50");'},
            }},
        'general':{'label':'Competitor', 'aside':'yes', 'fields':{
            'first':{'label':'First Name', 'required':'no', 'type':'text', 'livesearch':'yes', 'visible':'yes'},
            'last':{'label':'Last Name', 'required':'no', 'type':'text', 'livesearch':'yes', 'visible':'yes'},
            'name':{'label':'Name', 'required':'yes', 'type':'text', 'livesearch':'yes', 'visible':'hidden'},
            'public_name':{'label':'Public Name', 'type':'text'},
            'pronoun':{'label':'Pronoun', 'type':'text'},
            'conductor':{'label':'Conductor', 'type':'text', 'visible':'no'},
            'num_people':{'label':'# People', 'type':'number', 'size':'small', 'visible':'no'},
            'parent':{'label':'Parent', 'type':'text', 'visible':'yes'},
            }},
        '_other':{'label':'', 'aside':'yes', 'fields':{
            'age':{'label':'Age', 'type':'text'},
            'study_level':{'label':'Study/Level', 'type':'text', 'visible':'no'},
            'instrument':{'label':'Instrument', 'type':'text', 'visible':'no'},
            'flags1':{'label':'Waiver', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 'off':'Unsigned', 'on':'Signed'},
            'flags2':{'label':'Photo Waiver', 'type':'flagtoggle', 'bit':0x02, 'field':'flags', 
                'visible':function() { return ['on','internal'].indexOf(M.ciniki_musicfestivals_main.competitor.festival['waiver-photo-status']) >= 0 ? 'yes' : 'no'; },
                'off':'No Photos', 'on':'Publish',
                },
            'flags3':{'label':'Name Waiver', 'type':'flagtoggle', 'bit':0x04, 'field':'flags', 
                'visible':function() { return ['on','internal'].indexOf(M.ciniki_musicfestivals_main.competitor.festival['waiver-name-status']) >= 0 ? 'yes' : 'no'; },
                'off':'Hide Name', 'on':'Publish',
                },
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'contact', 'visible':'yes',
            'tabs':{
                'contact':{'label':'Contact Info', 'fn':'M.ciniki_musicfestivals_main.competitor.switchTab("contact");'},
                'emails':{'label':'Emails', 'fn':'M.ciniki_musicfestivals_main.competitor.switchTab("emails");'},
                'registrations':{'label':'Registrations', 'fn':'M.ciniki_musicfestivals_main.competitor.switchTab("registrations");'},
            }},
        '_address':{'label':'Contact Info', 
            'visible':function() { return M.ciniki_musicfestivals_main.competitor.sections._tabs.selected == 'contact' ? 'yes' : 'hidden';},
            'fields':{
                'address':{'label':'Address', 'type':'text'},
                'city':{'label':'City', 'type':'text', 'size':'small'},
                'province':{'label':'Province', 'type':'text', 'size':'small'},
                'postal':{'label':'Postal Code', 'type':'text', 'size':'small'},
                'country':{'label':'Country', 'type':'text', 'size':'small'},
                'phone_home':{'label':'Home Phone', 'type':'text', 'size':'small'},
                'phone_cell':{'label':'Cell Phone', 'type':'text', 'size':'small'},
                'email':{'label':'Email', 'type':'text'},
                'etransfer_email':{'label':'etransfer Email', 'type':'text',
                    'visible':function() { 
                        if( (M.ciniki_musicfestivals_main.competitor.festival['competitor-individual-etransfer-email'] != null
                            && M.ciniki_musicfestivals_main.competitor.festival['competitor-individual-etransfer-email'] != 'hidden'
                            && M.ciniki_musicfestivals_main.competitor.festival['competitor-individual-etransfer-email'] != '')
                            || (M.ciniki_musicfestivals_main.competitor.festival['competitor-group-etransfer-email'] != null
                            && M.ciniki_musicfestivals_main.competitor.festival['competitor-group-etransfer-email'] != 'hidden'
                            && M.ciniki_musicfestivals_main.competitor.festival['competitor-group-etransfer-email'] != '')
                            ) {
                            return 'yes';
                        }
                        return 'no';
                    }},
            }},
        '_notes':{'label':'Competitor Notes', 'aside':'no', 
            'visible':function() { return M.ciniki_musicfestivals_main.competitor.sections._tabs.selected == 'contact' ? 'yes' : 'hidden';},
            'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        'messages':{'label':'Draft Emails', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.competitor.sections._tabs.selected == 'emails' ? 'yes' : 'hidden';},
            'headerValues':['Status', 'Subject'],
            'noData':'No drafts or scheduled emails',
            'menu':{
                'add':{
                    'label':'Send Email',
                    'fn':'M.ciniki_musicfestivals_main.competitor.save("M.ciniki_musicfestivals_main.competitor.addmessage();");',
                    },
                },
            },
        'emails':{'label':'Send Emails', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.competitor.sections._tabs.selected == 'emails' ? 'yes' : 'hidden';},
            'headerValues':['Date Sent', 'Subject'],
            'noData':'No emails sent',
            },
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_musicfestivals_main.competitor.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden';},
            'headerValues':['Category', 'Code', 'Class', 'Status'],
            'noData':'No registrations',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.competitor.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.competitor.competitor_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.competitor.remove();'},
            }},
        };
    this.competitor.fieldValue = function(s, i, d) { return this.data[i]; }
    this.competitor.switchType = function(t) {
        this.sections._ctype.selected = t;
        if( t == 50 ) {
            this.sections.general.fields.first.visible = 'no';
            this.sections.general.fields.last.visible = 'no';
            this.sections.general.fields.name.visible = 'yes';
            this.sections.general.fields.name.required = 'yes';
            this.sections.general.fields.public_name.visible = 'no';
            this.sections.general.fields.pronoun.visible = 'no';
            this.sections.general.fields.conductor.visible = 'yes';
            this.sections.general.fields.num_people.visible = 'yes';
            this.sections.general.fields.parent.label = 'Contact Person';
            if( M.ciniki_musicfestivals_main.competitor.festival['competitor-group-study-level'] != null
                && ['optional','required'].indexOf(M.ciniki_musicfestivals_main.competitor.festival['competitor-group-study-level']) >= 0 
                ) {
                this.sections._other.fields.study_level.visible = 'yes';
            } else {
                this.sections._other.fields.study_level.visible = 'no';
            }
            if( M.ciniki_musicfestivals_main.competitor.festival['competitor-group-instrument'] != null
                && ['optional','required'].indexOf(M.ciniki_musicfestivals_main.competitor.festival['competitor-group-instrument']) >= 0 
                ) {
                this.sections._other.fields.instrument.visible = 'yes';
            } else {
                this.sections._other.fields.instrument.visible = 'no';
            }
        } else {
            this.sections.general.fields.first.visible = 'yes';
            this.sections.general.fields.last.visible = 'yes';
            this.sections.general.fields.name.visible = 'no';
            this.sections.general.fields.name.required = 'no';
            this.sections.general.fields.public_name.visible = 'yes';
            this.sections.general.fields.pronoun.visible = M.modFlagSet('ciniki.musicfestivals', 0x80);
            this.sections.general.fields.conductor.visible = 'no';
            this.sections.general.fields.num_people.visible = 'no';
            this.sections.general.fields.parent.label = 'Parent';
            if( M.ciniki_musicfestivals_main.competitor.festival['competitor-individual-study-level'] != null
                && ['optional','required'].indexOf(M.ciniki_musicfestivals_main.competitor.festival['competitor-individual-study-level']) >= 0 
                ) {
                this.sections._other.fields.study_level.visible = 'yes';
            } else {
                this.sections._other.fields.study_level.visible = 'no';
            }
            if( M.ciniki_musicfestivals_main.competitor.festival['competitor-individual-instrument'] != null
                && ['optional','required'].indexOf(M.ciniki_musicfestivals_main.competitor.festival['competitor-individual-instrument']) >= 0 
                ) {
                this.sections._other.fields.instrument.visible = 'yes';
            } else {
                this.sections._other.fields.instrument.visible = 'no';
            }
        }
        this.showHideFormField('general', 'first');
        this.showHideFormField('general', 'last');
        this.showHideFormField('general', 'name');
        this.showHideFormField('general', 'public_name');
        this.showHideFormField('general', 'pronoun');
        this.showHideFormField('general', 'conductor');
        this.showHideFormField('general', 'num_people');
        this.showHideFormField('general', 'parent');
        this.showHideFormField('_other', 'study_level');
        this.showHideFormField('_other', 'instrument');
        this.refreshSections(['_ctype']);
    }
    this.competitor.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSections(['_tabs', '_address','_notes','messages', 'emails', 'registrations']);
    }
    this.competitor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.competitorHistory', 'args':{'tnid':M.curTenantID, 'competitor_id':this.competitor_id, 'field':i}};
    }
    this.competitor.liveSearchCb = function(s, i, value) {
        if( i == 'name' || i == 'first' || i == 'last' ) {
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
    this.competitor.cellValue = function(s, i, j, d) {
        if( s == 'messages' ) {
            switch(j) {
                case 0: return d.status_text;
                case 1: return d.subject;
            }
        }
        if( s == 'emails' ) {
            switch(j) {
                case 0: return (d.status != 30 ? d.status_text : d.date_sent);
                case 1: return d.subject;
            }
        }
        if( s == 'registrations' ) {
            switch(j) {
                case 0: return d.section_name + ' - ' + d.category_name;
                case 1: return d.class_code;
                case 2: return d.class_name;
                case 3: return d.status_text;
            }
        }
    }
    this.competitor.rowFn = function(s, i, d) {
        if( s == 'messages' ) {
            return 'M.ciniki_musicfestivals_main.competitor.save("M.ciniki_musicfestivals_main.message.open(\'M.ciniki_musicfestivals_main.competitor.open();\',\'' + d.id + '\');");';
        }
        if( s == 'emails' ) {
            return 'M.startApp(\'ciniki.mail.main\',null,\'M.ciniki_musicfestivals_main.competitor.reopen();\',\'mc\',{\'message_id\':\'' + d.id + '\'});';
        }
        return '';
    }
    this.competitor.addmessage = function() {
        M.ciniki_musicfestivals_main.message.addnew('M.ciniki_musicfestivals_main.competitor.open();',this.festival_id,'ciniki.musicfestivals.competitor',this.competitor_id);
    }
    this.competitor.reopen = function() {
        this.show();
    }
    this.competitor.open = function(cb, cid, fid, list, bci) {
        if( cid != null ) { this.competitor_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        if( bci != null ) { this.billing_customer_id = bci; }
        M.api.getJSONCb('ciniki.musicfestivals.competitorGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'competitor_id':this.competitor_id, 'emails':'yes', 'registrations':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.competitor;
            p.data = rsp.competitor;
            p.festival = rsp.festival;
            if( p.competitor_id == 0 ) {
                p.sections._tabs.selected = 'contact';
                p.sections._tabs.visible = 'no';
            } else {
                p.sections._tabs.visible = 'yes';
            }
            p.sections._ctype.selected = rsp.competitor.ctype;
            if( p.festival['waiver-photo-option-yes'] != null
                && p.festival['waiver-photo-option-yes'] != ''
                ) {
                p.sections._other.fields.flags2.on = p.festival['waiver-photo-option-yes'];
            } else {
                p.sections._other.fields.flags2.on = 'Publish';
            }
            if( p.festival['waiver-photo-option-no'] != null
                && p.festival['waiver-photo-option-no'] != ''
                ) {
                p.sections._other.fields.flags2.off = p.festival['waiver-photo-option-no'];
            } else {
                p.sections._other.fields.flags2.off = 'No Photos';
            }
            if( p.festival['waiver-name-option-yes'] != null
                && p.festival['waiver-name-option-yes'] != ''
                ) {
                p.sections._other.fields.flags3.on = p.festival['waiver-name-option-yes'];
            } else {
                p.sections._other.fields.flags3.on = 'Publish';
            }
            if( p.festival['waiver-name-option-no'] != null
                && p.festival['waiver-name-option-no'] != ''
                ) {
                p.sections._other.fields.flags3.off = p.festival['waiver-name-option-no'];
            } else {
                p.sections._other.fields.flags3.off = 'Hide Name';
            }
            p.refresh();
            p.show(cb);
            p.switchType(p.sections._ctype.selected);
        });
    }
    this.competitor.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.competitor.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.competitor_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.sections._ctype.selected != this.data.ctype ) {
                c += '&ctype=' + this.sections._ctype.selected;
            }
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
            c += '&ctype=' + this.sections._ctype.selected;
            M.api.postJSONCb('ciniki.musicfestivals.competitorAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'billing_customer_id':this.billing_customer_id}, c, function(rsp) {
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
    this.schedulesection = new M.panel('Schedule Section', 'ciniki_musicfestivals_main', 'schedulesection', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.schedulesection');
    this.schedulesection.data = null;
    this.schedulesection.festival_id = 0;
    this.schedulesection.schedulesection_id = 0;
    this.schedulesection.nplist = [];
    this.schedulesection.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
//            'flags':{'label':'Options', 'type':'flags', 'flags':{
//                '1':{'name':'Release Schedule'},
//                '2':{'name':'Release Comments'},
//                '3':{'name':'Release Certificates'},
//                '5':{'name':'Publish Schedule'},
//                }},
            'adjudicator1_id':{'label':'Adjudicator', 'type':'select', 
                'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x0800) ? 'no' : 'yes'; },
                'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            }},
        '_schedule':{'label':'Schedule', 'aside':'yes', 'fields':{
            'flags1':{'label':'Release Schedule to Competitors', 'type':'flagtoggle', 'bit':0x01, 'field':'flags'},
            'flags5':{'label':'Publish Schedule on Website', 'type':'flagtoggle', 'bit':0x10, 'field':'flags'},
            }},
        '_results':{'label':'Results', 'aside':'yes', 'fields':{
            'flags2':{'label':'Release Comments to Competitors', 'type':'flagtoggle', 'bit':0x02, 'field':'flags'},
            'flags3':{'label':'Release Certificates to Competitors', 'type':'flagtoggle', 'bit':0x04, 'field':'flags'},
            'flags6':{'label':'Publish Results on Website', 'type':'flagtoggle', 'bit':0x20, 'field':'flags'},
            }},
/*        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'sponsors', 'visible':'yes',
            'tabs':{
                'Sponsors':{'label':'Sponsors', 'fn':'M.ciniki_musicfestivals_main.schedulesection.switchTab("sponsors");'},
                'provincials':{'label':'Provincials', 'fn':'M.ciniki_musicfestivals_main.schedulesection.switchTab("provincials");'},
            }}, */
        'website_sponsors':{'label':'Website Sponsors', 'aside':'no', 'fields':{
            'webheader_sponsor_ids':{'label':'Intro Sponsors', 'type':'idlist', 'list':{}},
            'webheader_sponsors_title':{'label':'Intro Title', 'type':'text'},
//            'webheader_sponsors_content':{'label':'Intro Content', 'type':'textarea', 'size':'small'},
/*            'top_sponsors_image_ratio':{'label':'Image Ratio', 'type':'select', 'default':'1-1', 'options':{
                '2-1':'Panoramic',
                '16-9':'Letterbox',
                '6-4':'Wider',
                '4-3':'Wide',
                '1-1':'Square',
                '3-4':'Tall',
                '4-6':'Taller',
                }}, */
            }},
        'pdf_sponsors':{'label':'Print Sponsors', 'aside':'no', 'fields':{
            'pdfheader_sponsor_id':{'label':'Header Sponsor', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
            'pdffooter_title':{'label':'Footer Heading', 'type':'text'},
            'pdffooter_image_id':{'label':'Footer Image', 'type':'image_id', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.schedulesection.setFieldValue('pdffooter_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_musicfestivals_main.schedulesection.setFieldValue(fid,0);
                    return true;
                 },
             },
//            'top_sponsor2_id':{'label':'Sponsor 2', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
/*            'top_sponsors_image_ratio':{'label':'Image Ratio', 'type':'select', 'default':'1-1', 'options':{
                '2-1':'Panoramic',
                '16-9':'Letterbox',
                '6-4':'Wider',
                '4-3':'Wide',
                '1-1':'Square',
                '3-4':'Tall',
                '4-6':'Taller',
                }}, */
            }},
/*        'top_sponsors':{'label':'Top Sponsor', 'aside':'no', 'fields':{
            'top_sponsors_title':{'label':'Title', 'type':'text',},
            'top_sponsor_ids':{'label':'Sponsors', 'type':'idlist', 'list':{}},
//            'top_sponsor2_id':{'label':'Sponsor 2', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'top_sponsors_image_ratio':{'label':'Image Ratio', 'type':'select', 'default':'1-1', 'options':{
                '2-1':'Panoramic',
                '16-9':'Letterbox',
                '6-4':'Wider',
                '4-3':'Wide',
                '1-1':'Square',
                '3-4':'Tall',
                '4-6':'Taller',
                }},
            }},
        'bottom_sponsors':{'label':'Bottom Sponsor', 'aside':'no', 'fields':{
            'bottom_sponsors_title':{'label':'Title', 'type':'text',},
            'bottom_sponsors_content':{'label':'Content', 'type':'textarea'},
            'bottom_sponsor_ids':{'label':'Sponsors', 'type':'idlist', 'list':{}},
            'bottom_sponsors_image_ratio':{'label':'Image Ratio', 'type':'select', 'default':'1-1', 'options':{
                '2-1':'Panoramic',
                '16-9':'Letterbox',
                '6-4':'Wider',
                '4-3':'Wide',
                '1-1':'Square',
                '3-4':'Tall',
                '4-6':'Taller',
                }},
//            'bottom_sponsor1_id':{'label':'Sponsor 1', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
//            'bottom_sponsor2_id':{'label':'Sponsor 2', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            }}, */
/*        'provincials':{'label':'Provincials Information', 
            'visible':function() { return M.modFlagOn('ciniki.musicfestivals', 0x010000) ? 'no' : 'yes'; },
            'fields':{
                'provincials_image_id':{'label':'Image/Logo', 'type':'image_id', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_musicfestivals_main.schedulesection.setFieldValue('provincials_image_id', iid);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                        M.ciniki_musicfestivals_main.schedulesection.setFieldValue(fid,0);
                        return true;
                     },
                 },
                'provincials_title':{'label':'Title', 'type':'text'},
                'provincials_content':{'label':'Content', 'type':'textarea'},
            }}, */
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
                p.sections.general.fields.adjudicator1_id.options = rsp.adjudicators;
                p.sections.website_sponsors.fields.webheader_sponsor_ids.list = rsp.sponsors;
//                p.sections.pdf_sponsors.fields.pdffooter_sponsor_ids.list = rsp.sponsors;
                p.sections.pdf_sponsors.fields.pdfheader_sponsor_id.options = [{'id':0, 'name':'None'}];
                for(var i in rsp.sponsors) {
                    p.sections.pdf_sponsors.fields.pdfheader_sponsor_id.options.push({'id':rsp.sponsors[i].id, 'name':rsp.sponsors[i].name});
                    
                }
//                p.sections.top_sponsors.fields.top_sponsor_ids.list = rsp.sponsors;
//                p.sections.bottom_sponsors.fields.bottom_sponsor_ids.list = rsp.sponsors;
//                p.sections.top_sponsors.fields.top_sponsor2_id.options = rsp.sponsors;
//                p.sections.bottom_sponsors.fields.bottom_sponsor1_id.options = rsp.sponsors;
//                p.sections.bottom_sponsors.fields.bottom_sponsor2_id.options = rsp.sponsors;
//                p.sections.adjudicators.fields.adjudicator1_id.options = rsp.adjudicators;
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
                M.ciniki_musicfestivals_main.festival.schedulesection_id = rsp.id;
                M.ciniki_musicfestivals_main.schedulesection.schedulesection_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.schedulesection.remove = function() {
        M.confirm('Are you sure you want to remove this section?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleSectionDelete', {'tnid':M.curTenantID, 'schedulesection_id':M.ciniki_musicfestivals_main.schedulesection.schedulesection_id, 'festival_id':M.ciniki_musicfestivals_main.schedulesection.festival_id}, function(rsp) {
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
    this.scheduledivision = new M.panel('Schedule Division', 'ciniki_musicfestivals_main', 'scheduledivision', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.scheduledivision');
    this.scheduledivision.data = null;
    this.scheduledivision.festival_id = 0;
    this.scheduledivision.ssection_id = 0;
    this.scheduledivision.scheduledivision_id = 0;
    this.scheduledivision.nplist = [];
    this.scheduledivision.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'ssection_id':{'label':'Section', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'division_date':{'label':'Date', 'required':'yes', 'type':'date'},
//            'address':{'label':'Address', 'type':'text'},
            'adjudicator_id':{'label':'Adjudicator', 'type':'select', 
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x0800); },
                'complex_options':{'name':'name', 'value':'id'}, 'options':{},
                },
            'location_id':{'label':'Location', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            }},
        '_results':{'label':'Results', 'aside':'yes', 'fields':{
            'flags2':{'label':'Release Comments to Competitors', 'type':'flagtoggle', 'bit':0x02, 'field':'flags'},
            'flags3':{'label':'Release Certificates to Competitors', 'type':'flagtoggle', 'bit':0x04, 'field':'flags'},
            'flags6':{'label':'Publish Results on Website', 'type':'flagtoggle', 'bit':0x20, 'field':'flags'},
            }},
        '_results_notes':{'label':'Results/Adjudicator Notes', 'fields':{
            'results_notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_results_video_url':{'label':'Results/Adjudicator Video URL', 'fields':{
            'results_video_url':{'label':'', 'hidelabel':'yes', 'type':'text'},
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
                rsp.adjudicators.unshift({'id':'0', 'name':'None'});
                p.sections.general.fields.adjudicator_id.options = rsp.adjudicators;
                rsp.locations.unshift({'id':'0', 'name':'None'});
                p.sections.general.fields.location_id.options = rsp.locations;
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
                M.ciniki_musicfestivals_main.festival.scheduledivision_id = rsp.id;
                M.ciniki_musicfestivals_main.scheduledivision.scheduledivision_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.scheduledivision.remove = function() {
        M.confirm('Are you sure you want to remove this division?',null,function() {
            M.api.getJSONCb('ciniki.musicfestivals.scheduleDivisionDelete', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.scheduledivision.festival_id, 'scheduledivision_id':M.ciniki_musicfestivals_main.scheduledivision.scheduledivision_id}, function(rsp) {
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
    // The panel to display the list of section to import unschedule classes from
    //
    this.scheduledivisionimport = new M.panel('Schedule Division Import Section Classes', 'ciniki_musicfestivals_main', 'scheduledivisionimport', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.scheduledivisionimport');
    this.scheduledivisionimport.scheduledivision_id = 0;
    this.scheduledivisionimport.festival_id = 0;
    this.scheduledivisionimport.sections = {
        'section':{'label':'Choose Section', 'fields':{
            'section_id':{'label':'Syllabus Section', 'type':'select', 'options':[], 'complex_options':{'value':'id', 'name':'name'}},
            }},
        'statuses':{'label':'Include Registrations Status', 'fields':{
            'status_5':{'label':'Draft', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
            'status_70':{'label':'Disqualified', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            'status_75':{'label':'Withdrawn', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            'status_80':{'label':'Cancelled', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'import':{'label':'Import Unscheduled Classes', 'fn':'M.ciniki_musicfestivals_main.scheduledivisionimport.importclasses();'},
            }},
        }
    this.scheduledivisionimport.importclasses = function() {
        var sid = this.formFieldValue('section', 'section_id');
        var c = this.serializeForm('yes');
        M.api.postJSONCb('ciniki.musicfestivals.scheduleSectionClassImport', {'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.scheduledivisionimport.close();
        });
    }
    this.scheduledivisionimport.open = function(cb, did, fid) {
        if( did != null ) { this.scheduledivision_id = did; }
        if( fid != null ) { this.festival_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.sectionList', {'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.scheduledivisionimport;
            p.data = {'section_id':0};
            p.sections.section.fields.section_id.options = rsp.sections;
            p.refresh();
            p.show(cb);
        });
    }
    this.scheduledivisionimport.addClose('Cancel');

    //
    // The panel to display the list of section to import unschedule classes from
    //
    this.unscheduledimport = new M.panel('Import Unscheduled Registrations', 'ciniki_musicfestivals_main', 'unscheduledimport', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.unscheduledimport');
    this.unscheduledimport.festival_id = 0;
    this.unscheduledimport.sections = {
        'options':{'label':'Options', 'fields':{
            'division_date':{'label':'Unschedule Date', 'type':'date'},
            }},
        'statuses':{'label':'Include Registrations Status', 'fields':{
            'status_5':{'label':'Draft', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            'status_70':{'label':'Disqualified', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            'status_75':{'label':'Withdrawn', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            'status_80':{'label':'Cancelled', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'import':{'label':'Import Unscheduled Registrations', 'fn':'M.ciniki_musicfestivals_main.unscheduledimport.importclasses();'},
            }},
        }
    this.unscheduledimport.importclasses = function() {
        var sid = this.formFieldValue('section', 'section_id');
        var c = this.serializeForm('yes');
        M.api.postJSONCb('ciniki.musicfestivals.unscheduledImport', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
//            M.ciniki_musicfestivals_main.unscheduledimport.close();
        });
    }
    this.unscheduledimport.open = function(cb, fid) {
        this.festival_id = fid;
        this.data = {
            'division_date':'',
            };
        this.refresh();
        this.show(cb);
    }
    this.unscheduledimport.addClose('Cancel');

    //
    // The panel to edit Schedule Time Slot
    //
    this.scheduletimeslot = new M.panel('Schedule Time Slot', 'ciniki_musicfestivals_main', 'scheduletimeslot', 'mc', 'fiftyfifty', 'sectioned', 'ciniki.musicfestivals.main.scheduletimeslot');
    this.scheduletimeslot.data = null;
    this.scheduletimeslot.festival_id = 0;
    this.scheduletimeslot.scheduletimeslot_id = 0;
    this.scheduletimeslot.sdivision_id = 0;
    this.scheduletimeslot.section_id = 0;
    this.scheduletimeslot.category_id = 0;
    this.scheduletimeslot.class_id = 0;
    this.scheduletimeslot.unscheduled_registrations = {};
    this.scheduletimeslot.nplist = [];
    this.scheduletimeslot.sections = {
        'general':{'label':'Timeslot', 'aside':'yes', 'fields':{
            'sdivision_id':{'label':'Division', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'slot_time':{'label':'Time', 'required':'yes', 'type':'text', 'size':'small'},
            'name':{'label':'Name', 'type':'text'},
            'groupname':{'label':'Group Name', 'type':'text'},
            'flags2':{'label':'Type', 'type':'flagspiece', 'mask':0x02, 'field':'flags', 'join':'yes', 'toggle':'yes', 
                'flags':{'0':{'name':'Regular Timeslot'}, '2':{'name':'Finals/Playoff Timeslot'}},
                'onchange':'M.ciniki_musicfestivals_main.scheduletimeslot.switchType',
                },
            'slot_seconds':{'label':'Length', 'type':'hourmin', 'max_hours':8, 'minute_interval':5,
                'visible':function() { return M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-length'] != null
                    && M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-length'] == 'yes' ? 'yes' : 'no';
                    }},
            'start_num':{'label':'Starting at', 'type':'text', 'size':'small',
                'visible':function() { return M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] != null
                    && M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] == 'yes' ? 'yes' : 'no';
                    },
                'onkeyupFn':'M.ciniki_musicfestivals_main.scheduletimeslot.updateStartnum',
                },
            },
            'menu':{
                'split':{
                    'label':'Split Class',
                    'fn':'M.ciniki_musicfestivals_main.scheduletimeslot.save("M.ciniki_musicfestivals_main.scheduletimeslot.splitclass();");',
                    }
                },
            },
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':4, 'aside':'yes', 
            'headerValues':[],
            'cellClasses':[],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 
                    'registration_id':M.ciniki_musicfestivals_main.scheduletimeslot.data.registrations[from].id,
                    'timeslot_sequence':M.ciniki_musicfestivals_main.scheduletimeslot.data.registrations[to].timeslot_sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations();
                    });
                },
            },
        '_description':{'label':'Description', 'aside':'yes', 'fields':{
            'description':{'label':'Description', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_runsheet_notes':{'label':'Run Sheet Notes', 'aside':'yes', 'fields':{
            'runsheet_notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_results_notes':{'label':'Results/Adjudicator Notes', 'aside':'yes', 
            'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no';},
            'fields':{
                'results_notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_results_video_url':{'label':'Results/Adjudicator Video URL', 'aside':'yes', 
            'visible':function() { return (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no';},
            'fields':{
                'results_video_url':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.scheduletimeslot.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.scheduletimeslot.scheduletimeslot_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.scheduletimeslot.remove();'},
            }},
        '_section':{'label':'Section', 'fields':{
            'section_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                'onchange':'M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations',
                },
            }},
/*        '_category':{'label':'', 
            'visible':function() {return !M.modFlagOn('ciniki.musicfestivals', 0x010000) && M.ciniki_musicfestivals_main.scheduletimeslot.section_id > 0 ? 'yes' : 'hidden';},
            'fields':{
                'category_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                    'onchange':'M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations'
                    }
                }}, */
        '_class':{'label':'Class', 
//            'visible':function() {return M.modFlagOn('ciniki.musicfestivals', 0x010000) && M.ciniki_musicfestivals_main.scheduletimeslot.section_id > 0 ? 'yes' : 'hidden';},
            'visible':function() {return M.ciniki_musicfestivals_main.scheduletimeslot.section_id > 0 ? 'yes' : 'hidden';},
            'fields':{
                'class_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                    'onchange':'M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations'
                    }
                }},
        'unscheduled_registrations':{'label':'Unscheduled Registrations', 'type':'simplegrid', 'num_cols':5,
            'visible':function() {return M.ciniki_musicfestivals_main.scheduletimeslot.section_id > 0 ? 'yes' : 'hidden';},
            'headerValues':['', 'Class/Category', 'Name/Titles', 'Status', 'Type'],
            'cellClasses':['fabuttons alignleft', 'multiline', 'multiline', '', '', ''],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'noData':'No Unscheduled Registrations',
            },
        };
    this.scheduletimeslot.switchType = function() {
        if( this.data.registrations != null && this.data.registrations.length > 0 ) {
            this.refreshFormField('general', 'flags2');
            M.alert('You must remove registrations before you can change the timeslot type');
        } else {
            this.save("M.ciniki_musicfestivals_main.scheduletimeslot.open();");
        }
    }
    this.scheduletimeslot.fieldValue = function(s, i, d) { 
/*        if( i == 'registrations1' || i == 'registrations2' || i == 'registrations3' || i == 'registrations4' || i == 'registrations5' ) {
            return this.data.registrations;
        } */
        if( i == 'section_id' ) {
            return this.section_id;
        }
        if( i == 'category_id' ) {
            return this.category_id;
        }
        if( i == 'class_id' ) {
            return this.class_id;
        }
        if( i == 'unscheduled_registrations' ) {
            return this.unscheduled_registrations;
        }
        return this.data[i]; 
    }
    this.scheduletimeslot.sectionData = function(s) {
        if( s == 'unscheduled_registrations' ) {
            return this.unscheduled_registrations;
        }
        return this.data[s]; 
    }
    this.scheduletimeslot.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.scheduleTimeslotHistory', 'args':{'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'field':i}};
    }
    this.scheduletimeslot.cellValue = function(s, i, j, d) {
        if( s == 'registrations' ) {
            switch(j) {
                case 0: 
                    if( M.modFlagOn('ciniki.musicfestivals', 0x080000) ) {
                        return M.multiline(d.timeslot_sequence, d.timeslot_time);
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] == 'yes'
                        ) {
                        return d.timeslot_number;
                    }
                    return d.timeslot_sequence;
                case 1: return M.multiline(d.class_code + ' - ' + d.class_name, d.category_name);
                case 2: 
                    var accompanist = '';
                    var teacher = '';
                    var teacher2 = '';
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                        && d.accompanist_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                            ) {
                            accompanist = ' <b>A: ' + d.accompanist_name + '</b>';
                        } else {
                            accompanist = ' <b>' + d.accompanist_name + '</b>';
                        }
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                        && d.teacher_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                            ) {
                            teacher = ' <b>T: ' + d.teacher_name + '</b>';
                        } else {
                            teacher = (accompanist != '' ? ', <b>' : ' <b>') + d.teacher_name + '</b>';
                        }
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                        && d.teacher2_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                            ) {
                            teacher2 = ' <b>T: ' + d.teacher2_name + '</b>';
                        } else {
                            teacher2 = (accompanist != '' || teacher != '' ? ', <b>' : ' <b>') + d.teacher2_name + '</b>';
                        }
                    }
                    return M.multiline(d.display_name + accompanist + teacher + teacher2, d.titles.replace(/\n/g, '<br/>')
                        + (d.notes != '' ? '<br><b>' + d.notes + '</b>' : '')
                        );
//                case 3: return d.accompanist_name;
                case 3: 
                    if( (M.ciniki_musicfestivals_main.festival.data.flags&0x16) > 0 && d.member_name != null && d.member_name != '' ) {
                        return M.multiline(d.participation + ' - ' + d.status_text, d.member_name);
                    } else if( (M.ciniki_musicfestivals_main.festival.data.flags&0x16) > 0 ) {
                        return M.multiline(d.participation, d.status_text);
                    } else if( d.member_name != null && d.member_name != '' ) {
                        return M.multiline(d.status_text, d.member_name);
                    } else {
                        return d.status_text;
                    } 
                case 4: return M.faBtn('&#xf00d;', 'Remove', 'M.ciniki_musicfestivals_main.scheduletimeslot.removeRegistration(' + d.id + ');');
            }
        }
        if( s == 'unscheduled_registrations' ) {
            switch(j) {
                case 0: return M.faBtn('&#xf067;', 'Add', 'M.ciniki_musicfestivals_main.scheduletimeslot.addRegistration(' + d.id + ');');
//                case 0: return M.btn('Add', 'M.ciniki_musicfestivals_main.scheduletimeslot.addRegistration(' + d.id + ');');
                case 1: return M.multiline(d.class_code + ' - ' + d.class_name, d.category_name);
                case 2: 
                    var accompanist = '';
                    var teacher = '';
                    var teacher2 = '';
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                        && d.accompanist_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                            ) {
                            accompanist = ' <b>A: ' + d.accompanist_name + '</b>';
                        } else {
                            accompanist = ' <b>' + d.accompanist_name + '</b>';
                        }
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                        && d.teacher_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                            ) {
                            teacher = ' <b>T: ' + d.teacher_name + '</b>';
                        } else {
                            teacher = (accompanist != '' ? ', <b>' : ' <b>') + d.teacher_name + '</b>';
                        }
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                        && d.teacher2_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                            ) {
                            teacher2 = ' <b>T: ' + d.teacher2_name + '</b>';
                        } else {
                            teacher2 = (accompanist != '' || teacher != '' ? ', <b>' : ' <b>') + d.teacher2_name + '</b>';
                        }
                    }
                    return M.multiline(d.display_name + accompanist + teacher + teacher2, d.titles.replace(/\n/g, '<br/>')
                        + (d.notes != '' ? '<br><b>' + d.notes + '</b>' : '')
                        );
//                case 3: return d.accompanist_name;
                case 3: 
                    // Add the type of festival if virtual or plus is enabled
                    if( (M.ciniki_musicfestivals_main.festival.data.flags&0x16) > 0 && d.member_name != null && d.member_name != '' ) {
                        return M.multiline(d.participation + ' - ' + d.status_text, d.member_name);
                    } else if( (M.ciniki_musicfestivals_main.festival.data.flags&0x16) > 0 ) {
                        return M.multiline(d.participation, d.status_text);
                    } else if( d.member_name != null && d.member_name != '' ) {
                        return M.multiline(d.status_text, d.member_name);
                    } 
                    return d.status_text;
            }
        }
    }
    this.scheduletimeslot.cellFn = function(s, i, j, d) {
        if( M.modFlagOn('ciniki.musicfestivals', 0x080000) && s == 'registrations' && j == 0 ) {
            return 'event.stopPropagation();M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrationTime(\'' + d.id + '\');';
        }
        return '';
    }
    this.scheduletimeslot.rowClass = function(s, i, d) {
        if( (s == 'registrations' || s == 'unscheduled_registrations')
            && d.status != null 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != null 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != '' 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != '#ffffff' 
            ) {
            return 'colored';
        }
        return '';
    }
    this.scheduletimeslot.rowStyle = function(s, i, d) {
        if( s == 'registrations' || s == 'unscheduled_registrations' ) {
            return M.ciniki_musicfestivals_main.regStatusColour(M.ciniki_musicfestivals_main.festival.data, d);
        } 
        // FIXME: Convert to status colours
/*            if( (d.flags&0x0100) == 0x0100 ) {
                return 'statusgrey';
            } else if( (d.flags&0x0200) == 0x0200 ) {
                return 'statusteal';
            } else if( (d.flags&0x0400) == 0x0400 ) {
                return 'statusblue';
            } else if( (d.flags&0x0800) == 0x0800 ) {
                return 'statuspurple';
            } else if( (d.flags&0x8000) == 0x8000 ) {
                return 'statusgreen';
            } else if( (d.flags&0x4000) == 0x4000 ) {
                return 'statusyellow';
            } else if( (d.flags&0x2000) == 0x2000 ) {
                return 'statusorange';
            } else if( (d.flags&0x1000) == 0x1000 ) {
                return 'statusred';
            } */
        return '';
    }
    this.scheduletimeslot.rowFn = function(s, i, d) {
        if( s == 'registrations' || s == 'unscheduled_registrations' ) {    
            return 'M.ciniki_musicfestivals_main.scheduletimeslot.save("M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.scheduletimeslot.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.scheduletimeslot.festival_id, null,\'festival\');");';
        }
    }
    this.scheduletimeslot.updateRegistrationTime = function(rid) {
        if( this.formValue('flags2') == 0x02 ) {
            M.prompt('New Finals Time:', '', 'Update', function(n) {
                M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':rid, 'finals_timeslot_time':n}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations();
                });
            });
        } else {
            M.prompt('New Time:', '', 'Update', function(n) {
                M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':rid, 'timeslot_time':n}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations();
                });
            });
        }
    }
    this.scheduletimeslot.updateStartnum = function() {
        var n = parseInt(this.formValue('start_num'));
        if( isNaN(n) || n == null || n < 1 ) {
            n = 1;
        }
        for(var i in this.data.registrations) {
            this.data.registrations[i].timeslot_number = n++;
        }
        this.refreshSection('registrations');
    }
    this.scheduletimeslot.updateRegistrations = function() {
        var sid = this.formValue('section_id');
        if( sid != this.section_id ) {
            this.section_id = sid;
            this.category_id = 0;
            this.class_id = 0;
        }
//        } else if( M.modFlagOn('ciniki.musicfestivals', 0x010000) ) {
            var cid = this.formValue('class_id');
            if( cid != this.class_id ) {
                this.class_id = cid;
            }
//        } else {
//            var cid = this.formValue('category_id');
//            if( cid != this.category_id ) {
//                this.category_id = cid;
//            }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleTimeslotGet', 
            {'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id, 'section_id':this.section_id, 'category_id':this.category_id, 'class_id':this.class_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.scheduletimeslot;
                p.data.registrations = rsp.scheduletimeslot.registrations;
                if( rsp.scheduletimeslot.total_perf_time != null && rsp.scheduletimeslot.total_perf_time != '' ) {
                    p.sections.registrations.label = 'Registrations [' + rsp.scheduletimeslot.total_perf_time + ']';
                } else {
                    p.sections.registrations.label = 'Registrations';
                }
                p.sections._section.fields.section_id.options = rsp.sections;
//                p.sections._category.fields.category_id.options = rsp.categories;
                p.sections._class.fields.class_id.options = rsp.classes;
                p.unscheduled_registrations = rsp.unscheduled_registrations;
                p.refreshSections(['registrations', '_section', '_category', '_class', 'unscheduled_registrations']);
                p.showHideSections(['_category', '_class', 'unscheduled_registrations']);
            });
    }
    this.scheduletimeslot.addRegistration = function(id) {
        if( this.scheduletimeslot_id == 0 ) {
            this.save("M.ciniki_musicfestivals_main.scheduletimeslot.addRegistration(" + id + ");");
        } else if( this.formValue('flags2') == 0x02 ) {
            M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':id, 'finals_timeslot_id':this.scheduletimeslot_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations();
                });

        } else {
            M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':id, 'timeslot_id':this.scheduletimeslot_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations();
                });
        }
    }
    this.scheduletimeslot.removeRegistration = function(id) {
        if( this.formValue('flags2') == 0x02 ) {
            M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':id, 'finals_timeslot_id':0}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations();
                });
        } else {
            M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':id, 'timeslot_id':0}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.scheduletimeslot.updateRegistrations();
                });
        }
    }
    this.scheduletimeslot.splitclass = function() {
        this.popupMenuClose('general');
        this.scheduletimeslot_id = 0;
        this.updateRegistrations();
    }
    this.scheduletimeslot.open = function(cb, sid, did, fid, list) {
        if( sid != null ) { this.scheduletimeslot_id = sid; }
        if( did != null ) { this.sdivision_id = did; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleTimeslotGet', 
            {'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id, 'section_id':this.section_id, 'category_id':this.category_id, 'class_id':this.class_id, 'sdivision_id':this.sdivision_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.scheduletimeslot;
                p.data = rsp.scheduletimeslot;
                p.sections.general.fields.sdivision_id.options = rsp.scheduledivisions;
                p.sections._section.fields.section_id.options = rsp.sections;
                if( rsp.scheduletimeslot.total_perf_time != null && rsp.scheduletimeslot.total_perf_time != '' ) {
                    p.sections.registrations.label = 'Registrations [' + rsp.scheduletimeslot.total_perf_time + ']';
                } else {
                    p.sections.registrations.label = 'Registrations';
                }
//                p.sections._category.fields.category_id.options = rsp.categories;
                p.unscheduled_registrations = rsp.unscheduled_registrations;
                if( (M.ciniki_musicfestivals_main.festival.data.flags&0x16) > 0 ) {
                    p.sections.registrations.num_cols = 5;
                    p.sections.registrations.headerValues = ['#', 'Class/Category', 'Name[Accompanist]/Titles', 'Type', ''];
                    p.sections.registrations.cellClasses = ['', 'multiline', 'multiline', 'multiline', 'alignright fabuttons'];
                    p.sections.unscheduled_registrations.headerValues = ['', 'Class/Category', 'Name[Accompanist]/Titles', 'Type'];
                    p.sections.unscheduled_registrations.cellClasses = ['fabuttons', 'multiline', 'multiline', 'multiline'];
                    p.sections.unscheduled_registrations.num_cols = 4;
                } else {
                    p.sections.registrations.num_cols = 5;
                    p.sections.registrations.headerValues = ['#', 'Class/Category', 'Name[Accompanist]/Titles', 'Status', ''];
                    p.sections.registrations.cellClasses = ['', 'multiline', 'multiline', '', 'alignright fabuttons'];
                    p.sections.unscheduled_registrations.headerValues = ['', 'Class/Category', 'Name[Accompanist]/Titles', 'Status'];
                    p.sections.unscheduled_registrations.cellClasses = ['fabuttons', 'multiline', 'multiline', ''];
                    p.sections.unscheduled_registrations.num_cols = 4;
                }
                if( M.modFlagOn('ciniki.musicfestivals', 0x080000) ) {
                    p.sections.registrations.cellClasses[0] = 'multiline';
                }
                p.refresh();
                p.show(cb);
                p.refreshSections(['unscheduled_registrations']);
            });
    }
    this.scheduletimeslot.save = function(cb) {
        if( !this.checkForm() ) { return false; }
        var did = this.formValue('sdivision_id');
        if( this.data.sdivision_id > 0 && did != this.data.sdivision_id ) {
            M.confirm("Are you sure you want to move this timeslot to another division?", "Yes, move timeslot", function() {
                M.ciniki_musicfestivals_main.scheduletimeslot.saveconfirmed(cb);
                });
        } else {
            this.saveconfirmed(cb);
        }
    }
    this.scheduletimeslot.saveconfirmed = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.scheduletimeslot.close();'; }
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
    // The panel to edit Schedule Time Slot
    //
    this.scheduledivisions = new M.panel('Scheduler', 'ciniki_musicfestivals_main', 'scheduledivisions', 'mc', 'flex', 'sectioned', 'ciniki.musicfestivals.main.scheduledivisions');
    this.scheduledivisions.data = null;
    this.scheduledivisions.num_divisions = 3;
    this.scheduledivisions.division_ids = [];
    this.scheduledivisions.festival_id = 0;
    this.scheduledivisions.section_id = 0;
    this.scheduledivisions.class_id = 0;
    this.scheduledivisions.showtitles = 'yes';
    this.scheduledivisions.participation = 'all';
    this.scheduledivisions.unscheduled_registrations = {};
    this.scheduledivisions.sections = {};
    this.scheduledivisions.fieldValue = function(s, i, d) { 
        if( i == 'division1_id' ) { return this.division_ids[1]; }
        if( i == 'division2_id' ) { return this.division_ids[2]; }
        if( i == 'division3_id' ) { return this.division_ids[3]; }
        if( i == 'division4_id' ) { return this.division_ids[4]; }
        if( i == 'division5_id' ) { return this.division_ids[5]; }
        if( i == 'division6_id' ) { return this.division_ids[6]; }
        if( i == 'division7_id' ) { return this.division_ids[7]; }
        if( i == 'division8_id' ) { return this.division_ids[8]; }
        if( i == 'division9_id' ) { return this.division_ids[9]; }
        if( i == 'section_id' ) { return this.section_id; }
        if( i == 'class_id' ) { return this.class_id; }
        if( i == 'unscheduled_registrations' ) {
            return this.unscheduled_registrations;
        }
        return this.data[i]; 
    }
    this.scheduledivisions.sectionData = function(s) {
        if( s == 'unscheduled_registrations' ) {
            return this.unscheduled_registrations;
        }
        return this.data[s]; 
    }
    this.scheduledivisions.cellValue = function(s, i, j, d) {
        if( s.match(/^timeslot_/) ) {
            switch(j) {
//                case 0: return M.multiline(d.timeslot_sequence, d.timeslot_time);
                case 0: 
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] == 'yes'
                        ) {
                        return d.timeslot_number;
                    }
                    return d.timeslot_sequence;
                case 1: 
                    var accompanist = '';
                    var teacher = '';
                    var teacher2 = '';
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                        && d.accompanist_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                            ) {
                            accompanist = ' <b>A: ' + d.accompanist_name + '</b>';
                        } else {
                            accompanist = ' <b>' + d.accompanist_name + '</b>';
                        }
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                        && d.teacher_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                            ) {
                            teacher = ' <b>T: ' + d.teacher_name + '</b>';
                        } else {
                            teacher = (accompanist != '' ? ', <b>' : ' <b>') + d.teacher_name + '</b>';
                        }
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                        && d.teacher2_name != '' 
                        ) {
                        if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                            && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                            ) {
                            teacher2 = ' <b>T: ' + d.teacher2_name + '</b>';
                        } else {
                            teacher2 = (accompanist != '' || teacher != '' ? ', <b>' : ' <b>') + d.teacher2_name + '</b>';
                        }
                    }
                    return M.multiline((this.showtitles == 'no' ? '<span class="subdue">[' + d.perf_time + ']</span> ': '') 
                    + d.class_code + ' - ' 
                    + (d.participation != '' ? '[' + d.participation + '] ' : '')
                    + d.display_name + accompanist + teacher + teacher2,
                    (this.showtitles == 'yes' ? '' + d.titles.replace(/\n/g, '<br/>') : '')) 
                    + (d.notes != '' ? '<b>' + d.notes + '</b>' : '');
            }
        }
        if( s == 'unscheduled_registrations' ) {
            var accompanist = '';
            var teacher = '';
            var teacher2 = '';
            if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                && d.accompanist_name != '' 
                ) {
                if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                    && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                    ) {
                    accompanist = ' <b>A: ' + d.accompanist_name + '</b>';
                } else {
                    accompanist = ' <b>' + d.accompanist_name + '</b>';
                }
            }
            if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                && d.teacher_name != '' 
                ) {
                if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                    && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                    ) {
                    teacher = ' <b>T: ' + d.teacher_name + '</b>';
                } else {
                    teacher = (accompanist != '' ? ', <b>' : ' <b>') + d.teacher_name + '</b>';
                }
            }
            if( M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] != null 
                && M.ciniki_musicfestivals_main.festival.data['scheduling-teacher-show'] == 'yes'
                && d.teacher2_name != '' 
                ) {
                if( M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] != null 
                    && M.ciniki_musicfestivals_main.festival.data['scheduling-accompanist-show'] == 'yes'
                    ) {
                    teacher2 = ' <b>T: ' + d.teacher2_name + '</b>';
                } else {
                    teacher2 = (accompanist != '' || teacher != '' ? ', <b>' : ' <b>') + d.teacher2_name + '</b>';
                }
            }
            return M.multiline((this.showtitles == 'no' ? '<span class="subdue">[' + d.perf_time + ']</span> ': '') 
                + d.class_code + ' - ' 
                + (d.participation != '' ? '[' + d.participation + '] ' : '')
                + d.display_name + accompanist + teacher + teacher2,
                (this.showtitles == 'yes' ? '' + d.titles.replace(/\n/g, '<br/>') : '')) + (d.notes != '' ? '<b>' + d.notes + '</b>' : '');
        }
    }
    this.scheduledivisions.cellFn = function(s, i, j, d) {
        if( M.modFlagOn('ciniki.musicfestivals', 0x080000) && s.match(/registrations[0-6]/) && j <= 1 ) {
            return 'event.stopPropagation();M.ciniki_musicfestivals_main.scheduledivisions.savePos();M.ciniki_musicfestivals_main.scheduledivisions.updateRegistrationTime(\'' + d.id + '\');';
        }
        return '';
    }
    this.scheduledivisions.rowClass = function(s, i, d) {
        if( s.match(/^timeslot_/|| s == 'unscheduled_registrations')
            && d.status != null 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != null 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != '' 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != '#ffffff' 
            ) {
            return 'colored';
        }
        return '';
    }
    this.scheduledivisions.rowStyle = function(s, i, d) {
        if( s.match(/^timeslot/) || s == 'unscheduled_registrations') {
            return M.ciniki_musicfestivals_main.regStatusColour(M.ciniki_musicfestivals_main.festival.data, d);
        } 
        return '';
    }
    this.scheduledivisions.rowFn = function(s, i, d) {
        if( s.match(/^timeslot/) || s == 'unscheduled_registrations' ) {    
            return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.scheduledivisions.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.scheduledivisions.festival_id, null,\'festival\');';
        }
    }
    this.scheduledivisions.cellDragStart = function(s, i, j, d) {
        return 'M.ciniki_musicfestivals_main.scheduledivisions.dragstart(\'' + s + '\',' + i + ',' + d.id + ');';
    }
    this.scheduledivisions.cellDrag = function(s, i, j, d) {
        return 'M.ciniki_musicfestivals_main.scheduledivisions.drag(event,\'' + s + '\',' + i + ',' + d.id + ');';
    }
    this.scheduledivisions.cellDragStop = function(s, i, j, d) {
//        return 'M.ciniki_musicfestivals_main.scheduledivisions.dragstop(' + d.id + ');';
    }
    this.scheduledivisions.cellDrop = function(s, i, j, d) {
        return 'M.ciniki_musicfestivals_main.scheduledivisions.celldrop(\'' + s + '\',' + i + ');';
    }
    this.scheduledivisions.noDataDrop = function(s) {
        return 'M.ciniki_musicfestivals_main.scheduledivisions.setTimeslot(' + this.sections[s].timeslot_id + ');';
        switch(s) {
            case 'registrations1': return 'M.ciniki_musicfestivals_main.scheduledivisions.setTimeslot(' + this.timeslot1_id + ');';
            case 'registrations2': return 'M.ciniki_musicfestivals_main.scheduledivisions.setTimeslot(' + this.timeslot2_id + ');';
            case 'registrations3': return 'M.ciniki_musicfestivals_main.scheduledivisions.setTimeslot(' + this.timeslot3_id + ');';
            case 'registrations4': return 'M.ciniki_musicfestivals_main.scheduledivisions.setTimeslot(' + this.timeslot4_id + ');';
            case 'registrations5': return 'M.ciniki_musicfestivals_main.scheduledivisions.setTimeslot(' + this.timeslot5_id + ');';
            case 'registrations6': return 'M.ciniki_musicfestivals_main.scheduledivisions.setTimeslot(' + this.timeslot6_id + ');';
        }
        return '';
    }
    this.scheduledivisions.dragstart = function(s,i,rid) {
        this.lastY = 0;
        if( s == 'unscheduled_registrations' ) {
            this.dragseq = 0;
        } else {
            this.dragseq = this.data[s][i].timeslot_sequence;
        }
        this.dragrid = rid;
    }
    this.scheduledivisions.drag = function(e,s,i,d) {
        if( e.clientY < 100 ) {
            window.scrollTo(0, window.scrollY-10);
        }
        if( e.clientY > (window.innerHeight - 100) ) {
            window.scrollTo(0, window.scrollY+10);
        }
    }
    this.scheduledivisions.dragstop = function(rid) {
//        console.log('drag stop: ' + rid);
    } 
    this.scheduledivisions.celldrop = function(s, to) {
        var args = {'tnid':M.curTenantID, 
            'registration_id':this.dragrid,
            };
        if( s == 'unscheduled_registrations' ) {
            args['timeslot_id'] = 0;
            args['timeslot_sequence'] = 0;
        } else {
            args['timeslot_id'] = M.ciniki_musicfestivals_main.scheduledivisions.data[s][to].timeslot_id;
            args['timeslot_sequence'] = M.ciniki_musicfestivals_main.scheduledivisions.data[s][to].timeslot_sequence;
            if( M.ciniki_musicfestivals_main.scheduledivisions.data[s][(to+1)] == null ) {
                args['timeslot_sequence'] += 1;
            }
        }
        M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduledivisions.open();
            }); 

    }
    this.scheduledivisions.setTimeslot = function(tid) {
        M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':this.dragrid, 'timeslot_id':tid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.scheduledivisions.updateRegistrations();
            });
    }
    this.scheduledivisions.divisionAddOpen = function(i) {    
        this.division_ids[i] = M.ciniki_musicfestivals_main.festival.scheduledivision_id;
        this.open();
    }
    this.scheduledivisions.switchParticipationTab = function(t) {
        this.participation = t;
        this.open();
    }
    this.scheduledivisions.reopen = function() {    
        for(var i = 1; i <= this.num_divisions; i++) {
            this.division_ids[i] = this.formValue('division'+i+'_id');
        }
        this.open();
    }
    this.scheduledivisions.open = function(cb, fid) {
        if( fid != null ) { this.festival_id = fid; }
        if( M.ciniki_musicfestivals_main.festival.data['advanced-scheduler-num-divisions'] != null ) {
            this.num_divisions = parseInt(M.ciniki_musicfestivals_main.festival.data['advanced-scheduler-num-divisions']);
            if( this.num_divisions < 2 || this.num_divisions > 9 ) {
                this.num_divisions = 3;
            }
        }
        var args = {
            'tnid':M.curTenantID, 
            'festival_id':this.festival_id, 
            'section_id':this.section_id, 
            'class_id':this.class_id,
            'participation':this.participation,
            };
        for(var i = 1; i <= this.num_divisions; i++) {
            if( this.division_ids[i] == null ) {
                this.division_ids[i] = 0;
            }
            args['division'+i+'_id'] = this.division_ids[i];
        }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleDivisions', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.scheduledivisions;
            p.data = rsp;
            rsp.divisions.unshift({'id':0, 'name':''});
            p.sections = {};
            for(var i = 1; i <= p.num_divisions; i++) {
                p.sections['division'+i] = {
                    'label':'Division ' + i, 'flexcolumn':i, 'flexBasis':'10%', 'type':'select',
                    'fields':{},
                    'menu':{    
                        'add':{
                            'label':'Add Division',
                            'fn':'M.ciniki_musicfestivals_main.scheduledivisions.divisionAdd('+i+');',
                            },
                        'edit':{
                            'label':'Edit Division',
                            'visible':(p.division_ids[i] != null && p.division_ids[i] > 0 ? 'yes' : 'no'),
                            'fn':'M.ciniki_musicfestivals_main.scheduledivisions.divisionEdit('+i+');',
                            },
                        },
                    };
                p.sections['division'+i]['fields']['division'+i+'_id'] = {
                    'label': '', 'hidelabel':'yes', 'type':'select',
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':rsp.divisions,
                    'onchange':'M.ciniki_musicfestivals_main.scheduledivisions.reopen',
                    };
                if( rsp['timeslots'+i] != null && rsp['timeslots'+i].length > 0 ) {
                    for(var j in rsp['timeslots'+i]) {
                        if( rsp['timeslots'+i][j].groupname != '' ) {
                            rsp['timeslots'+i][j].name += ' - ' + rsp['timeslots'+i][j].groupname;
                        }
                        p.sections['timeslot_'+i+'_'+j] = {
                            'label':rsp['timeslots'+i][j].name + ' ' + rsp['timeslots'+i][j].perf_time_text, 
                            'flexcolumn':i, 'type':'simplegrid', 'num_cols':2,
                            'headerValues':[],
                            'cellClasses':['', 'multiline'],
                            'sortable':'yes', 
                            'sortTypes':['', 'text', 'text', 'text', 'text'],
                            'noData':'No Registrations',
                            'timeslot_id':rsp['timeslots'+i][j].id,
                            'menu':{
                                'edit':{
                                    'label':'Edit', 
                                    'fn':'M.ciniki_musicfestivals_main.scheduledivisions.timeslotEdit('+i+','+rsp['timeslots'+i][j].id+');',
                                    },
                                },
                            };
                        p.data['timeslot_'+i+'_'+j] = rsp['timeslots'+i][j].registrations;
                    }
                } 
                if(p.division_ids[i] != null && p.division_ids[i] > 0 ) {
                    p.sections['timeslot_'+i+'_buttons'] = {
                        'label':'', 'flexcolumn':i, 'buttons':{
                            'add':{'label':'Add Timeslot', 
                            'fn':'M.ciniki_musicfestivals_main.scheduledivisions.timeslotAdd('+i+');'},
                            },
                        };
                    p.data['timeslot_'+i+'_none'] = [];
                }
            }
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ) {
                p.sections['lv_tabs'] = {'label':'', 'flexcolumn':i, 'flexBasis':'10%', 'type':'paneltabs', 'selected':p.participation, 'tabs':{
                    'all':{'label':'All', 'fn':'M.ciniki_musicfestivals_main.scheduledivisions.switchParticipationTab("all");'},
                    'live':{'label':'Live', 'fn':'M.ciniki_musicfestivals_main.scheduledivisions.switchParticipationTab("live");'},
                    'virtual':{'label':'Virtual', 'fn':'M.ciniki_musicfestivals_main.scheduledivisions.switchParticipationTab("virtual");'},
                    }};
            }
            p.sections['_section'] = {'label':'Section', 'flexcolumn':i, 'flexBasis':'10%', 'fields':{
                'section_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'}, 'options':rsp.sections,
                    'onchange':'M.ciniki_musicfestivals_main.scheduledivisions.updateRegistrations',
                    },
                }};
            p.sections['_class'] = {'label':'','flexcolumn':i,  
                'visible':function() {return M.ciniki_musicfestivals_main.scheduledivisions.section_id > 0 ? 'yes' : 'hidden';},
                'fields':{
                    'class_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                        'complex_options':{'value':'id', 'name':'name'}, 'options':rsp.classes,
                        'onchange':'M.ciniki_musicfestivals_main.scheduledivisions.updateRegistrations'
                        }
                    }};
            p.sections['unscheduled_registrations'] = {'label':'Unscheduled Registrations', 'flexcolumn':i, 'type':'simplegrid', 'num_cols':1,
                'visible':function() {return M.ciniki_musicfestivals_main.scheduledivisions.class_id > 0 ? 'yes' : 'hidden';},
//                'headerValues':['Name/Titles', 'Status'],
                'cellClasses':['multiline'],
                'sortable':'yes',
                'sortTypes':['text'],
                'noData':'No Unscheduled Registrations',
                };
            p.unscheduled_registrations = rsp.unscheduled_registrations;
            p.refresh();
            p.show(cb);
//            p.refreshSections(['unscheduled_registrations']);
            });
    }
    this.scheduledivisions.timeslotAdd = function(i) {
        M.ciniki_musicfestivals_main.scheduletimeslot.open("M.ciniki_musicfestivals_main.scheduledivisions.open();", 0, this.division_ids[i], this.festival_id);
    }
    this.scheduledivisions.timeslotEdit = function(i,t) {
        M.ciniki_musicfestivals_main.scheduletimeslot.open("M.ciniki_musicfestivals_main.scheduledivisions.open();", t, this.division_ids[i], this.festival_id);
    }
    this.scheduledivisions.divisionAdd = function(i) {
        M.ciniki_musicfestivals_main.scheduledivision.open("M.ciniki_musicfestivals_main.scheduledivisions.divisionAddOpen("+i+");", 0, 0, this.festival_id);
    }
    this.scheduledivisions.divisionEdit = function(i) {
        M.ciniki_musicfestivals_main.scheduledivision.open("M.ciniki_musicfestivals_main.scheduledivisions.open();", this.division_ids[i], 0, this.festival_id);
    }
    this.scheduledivisions.editVisible = function(i) {
        return (this.division_ids[i] != null && this.division_ids[i] > 0 ? 'yes' : 'no');
    };
    this.scheduledivisions.toggleTitles = function() {
        if( this.showtitles == 'yes' ) {
            this.showtitles = 'no';
        } else {
            this.showtitles = 'yes';
        }
        this.refresh();
    }
    this.scheduledivisions.updateRegistrations = function() {
        var sid = this.formValue('section_id');
        if( sid != this.section_id ) {
            this.section_id = sid;
            this.class_id = 0;
        } else {
            var cid = this.formValue('class_id');
            if( cid != this.class_id ) {
                this.class_id = cid;
            }
        }
        this.open();
    }
    this.scheduledivisions.addButton('toggle', 'Titles', 'M.ciniki_musicfestivals_main.scheduledivisions.toggleTitles();');
    this.scheduledivisions.addClose('Back');

    //
    // The panel to edit Schedule Time Slot
    //
    this.schedulemultislot = new M.panel('Schedule Class', 'ciniki_musicfestivals_main', 'schedulemultislot', 'mc', 'flex', 'sectioned', 'ciniki.musicfestivals.main.schedulemultislot');
    this.schedulemultislot.data = null;
    this.schedulemultislot.timeslot1_id = 0;
    this.schedulemultislot.timeslot2_id = 0;
    this.schedulemultislot.timeslot3_id = 0;
    this.schedulemultislot.timeslot4_id = 0;
    this.schedulemultislot.timeslot5_id = 0;
    this.schedulemultislot.timeslot6_id = 0;
    this.schedulemultislot.festival_id = 0;
    this.schedulemultislot.section_id = 0;
    this.schedulemultislot.class_id = 0;
    this.schedulemultislot.showtitles = 'yes';
    this.schedulemultislot.unscheduled_registrations = {};
    this.schedulemultislot.sections = {
        'timeslot1':{'label':'Timeslot 1', 'flexcolumn':1, 'flexBasis':'10%', 'type':'select', 
            'fields':{
                'timeslot1_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.reopen',
                    },
            }},
        'registrations1':{'label':'Registrations', 'flexcolumn':1, 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'visible':function() { return M.ciniki_musicfestivals_main.schedulemultislot.timeslot1_id > 0 ? 'yes' : 'no';},
            'headerValues':[],
            'cellClasses':['multiline', 'multiline'],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'noData':'No Registrations',
            },
        'timeslot2':{'label':'Timeslot 2', 'flexcolumn':2, 'flexBasis':'10%', 'type':'select', 
            'fields':{
                'timeslot2_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.reopen',
                    },
            }},
        'registrations2':{'label':'Registrations', 'flexcolumn':2, 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'visible':function() { return M.ciniki_musicfestivals_main.schedulemultislot.timeslot2_id > 0 ? 'yes' : 'no';},
            'headerValues':[],
            'cellClasses':['multiline', 'multiline'],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'noData':'No Registrations',
            },
        'timeslot3':{'label':'Timeslot 3', 'flexcolumn':3, 'flexBasis':'10%', 'type':'select', 
            'fields':{
                'timeslot3_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.reopen',
                    },
            }},
        'registrations3':{'label':'Registrations', 'flexcolumn':3, 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'visible':function() { return M.ciniki_musicfestivals_main.schedulemultislot.timeslot3_id > 0 ? 'yes' : 'no';},
            'headerValues':[],
            'cellClasses':['multiline', 'multiline'],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'noData':'No Registrations',
            },
        'timeslot4':{'label':'Timeslot 4', 'flexcolumn':4, 'flexBasis':'10%', 'type':'select', 
            'fields':{
                'timeslot4_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.reopen',
                    },
            }},
        'registrations4':{'label':'Registrations', 'flexcolumn':4, 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'visible':function() { return M.ciniki_musicfestivals_main.schedulemultislot.timeslot4_id > 0 ? 'yes' : 'no';},
            'headerValues':[],
            'cellClasses':['multiline', 'multiline'],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'noData':'No Registrations',
            },
        'timeslot5':{'label':'Timeslot 5', 'flexcolumn':5, 'flexBasis':'10%', 'type':'select', 
            'fields':{
                'timeslot5_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.reopen',
                    },
            }},
        'registrations5':{'label':'Registrations', 'flexcolumn':5, 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'visible':function() { return M.ciniki_musicfestivals_main.schedulemultislot.timeslot5_id > 0 ? 'yes' : 'no';},
            'headerValues':[],
            'cellClasses':['multiline', 'multiline'],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'noData':'No Registrations',
            },
        'timeslot6':{'label':'Timeslot 6', 'flexcolumn':6, 'flexBasis':'10%', 'type':'select', 
            'fields':{
                'timeslot6_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'},
                    'options':[],
                    'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.reopen',
                    },
            }},
        'registrations6':{'label':'Registrations', 'flexcolumn':6, 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'visible':function() { return M.ciniki_musicfestivals_main.schedulemultislot.timeslot6_id > 0 ? 'yes' : 'no';},
            'headerValues':[],
            'cellClasses':['multiline', 'multiline'],
            'sortable':'yes',
            'sortTypes':['', 'text', 'text', 'text', 'text'],
            'noData':'No Registrations',
            },
        '_section':{'label':'Section', 'flexcolumn':7, 'flexBasis':'10%', 'fields':{
            'section_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.updateRegistrations',
                },
            }},
        '_class':{'label':'','flexcolumn':7,  
            'visible':function() {return M.ciniki_musicfestivals_main.schedulemultislot.section_id > 0 ? 'yes' : 'hidden';},
            'fields':{
                'class_id':{'label':'', 'hidelabel':'yes', 'type':'select', 
                    'complex_options':{'value':'id', 'name':'name'}, 'options':{},
                    'onchange':'M.ciniki_musicfestivals_main.schedulemultislot.updateRegistrations'
                    }
                }},
        'unscheduled_registrations':{'label':'Unscheduled Registrations', 'flexcolumn':7, 'type':'simplegrid', 'num_cols':1,
            'visible':function() {return M.ciniki_musicfestivals_main.schedulemultislot.class_id > 0 ? 'yes' : 'hidden';},
            'headerValues':['Name/Titles', 'Status'],
            'cellClasses':['multiline', 'multiline'],
            'sortable':'yes',
            'sortTypes':['text', 'text'],
            'noData':'No Unscheduled Registrations',
            },
        };
    this.schedulemultislot.cellDragStart = function(s, i, j, d) {
        return 'M.ciniki_musicfestivals_main.schedulemultislot.dragstart(\'' + s + '\',' + i + ',' + d.id + ');';
    }
    this.schedulemultislot.cellDrag = function(s, i, j, d) {
        return 'M.ciniki_musicfestivals_main.schedulemultislot.drag(event,\'' + s + '\',' + i + ',' + d.id + ');';
    }
    this.schedulemultislot.cellDragStop = function(s, i, j, d) {
//        return 'M.ciniki_musicfestivals_main.schedulemultislot.dragstop(' + d.id + ');';
    }
    this.schedulemultislot.cellDrop = function(s, i, j, d) {
        return 'M.ciniki_musicfestivals_main.schedulemultislot.celldrop(\'' + s + '\',' + i + ');';
    }
    this.schedulemultislot.noDataDrop = function(s) {
        switch(s) {
            case 'registrations1': return 'M.ciniki_musicfestivals_main.schedulemultislot.setTimeslot(' + this.timeslot1_id + ');';
            case 'registrations2': return 'M.ciniki_musicfestivals_main.schedulemultislot.setTimeslot(' + this.timeslot2_id + ');';
            case 'registrations3': return 'M.ciniki_musicfestivals_main.schedulemultislot.setTimeslot(' + this.timeslot3_id + ');';
            case 'registrations4': return 'M.ciniki_musicfestivals_main.schedulemultislot.setTimeslot(' + this.timeslot4_id + ');';
            case 'registrations5': return 'M.ciniki_musicfestivals_main.schedulemultislot.setTimeslot(' + this.timeslot5_id + ');';
            case 'registrations6': return 'M.ciniki_musicfestivals_main.schedulemultislot.setTimeslot(' + this.timeslot6_id + ');';
        }
        return '';
    }
    this.schedulemultislot.dragstart = function(s,i,rid) {
        this.lastY = 0;
        if( s == 'unscheduled_registrations' ) {
            this.dragseq = 0;
        } else {
            this.dragseq = this.data[s][i].timeslot_sequence;
        }
        this.dragrid = rid;
    }
    this.schedulemultislot.drag = function(e,s,i,d) {
        if( e.clientY < 100 ) {
            window.scrollTo(0, window.scrollY-10);
        }
        if( e.clientY > (window.innerHeight - 100) ) {
            window.scrollTo(0, window.scrollY+10);
        }
    }
    this.schedulemultislot.dragstop = function(rid) {
//        console.log('drag stop: ' + rid);
    } 
    this.schedulemultislot.celldrop = function(s, to) {
        var args = {'tnid':M.curTenantID, 
            'registration_id':this.dragrid,
            };
        if( s == 'unscheduled_registrations' ) {
            args['timeslot_id'] = 0;
            args['timeslot_sequence'] = 0;
        } else {
            args['timeslot_id'] = M.ciniki_musicfestivals_main.schedulemultislot.data[s][to].timeslot_id;
            args['timeslot_sequence'] = M.ciniki_musicfestivals_main.schedulemultislot.data[s][to].timeslot_sequence;
            if( M.ciniki_musicfestivals_main.schedulemultislot.data[s][(to+1)] == null ) {
                args['timeslot_sequence'] += 1;
            }
        }
        M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.schedulemultislot.open();
            });

    }
    this.schedulemultislot.setTimeslot = function(tid) {
        M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':this.dragrid, 'timeslot_id':tid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.schedulemultislot.updateRegistrations();
            });
    }
    this.schedulemultislot.fieldValue = function(s, i, d) { 
        if( i == 'timeslot1_id' ) {
            return this.timeslot1_id;
        }
        if( i == 'timeslot2_id' ) {
            return this.timeslot2_id;
        }
        if( i == 'timeslot3_id' ) {
            return this.timeslot3_id;
        }
        if( i == 'timeslot4_id' ) {
            return this.timeslot4_id;
        }
        if( i == 'timeslot5_id' ) {
            return this.timeslot5_id;
        }
        if( i == 'timeslot6_id' ) {
            return this.timeslot6_id;
        }
        if( i == 'section_id' ) {
            return this.section_id;
        }
        if( i == 'class_id' ) {
            return this.class_id;
        }
        if( i == 'unscheduled_registrations' ) {
            return this.unscheduled_registrations;
        }
        return this.data[i]; 
    }
    this.schedulemultislot.sectionData = function(s) {
        if( s == 'unscheduled_registrations' ) {
            return this.unscheduled_registrations;
        }
        return this.data[s]; 
    }
    this.schedulemultislot.cellValue = function(s, i, j, d) {
        if( s.match(/^registrations/) ) {
            switch(j) {
                case 0: return M.multiline(d.timeslot_sequence, d.timeslot_time);
                case 1: return M.multiline((this.showtitles == 'no' ? '<span class="subdue">[' + d.perf_time + ']</span> ': '') + d.class_code + ' - ' + d.display_name + (d.accompanist_name != '' ? ' <b>[' + d.accompanist_name + ']</b>':''), '<b>' + d.member_name + '</b>' + (this.showtitles == 'yes' ? '<br/>' + d.titles.replace(/\n/g, '<br/>') : '')) + (d.notes != '' ? '<b>' + d.notes + '</b>' : '');
            }
        }
        if( s == 'unscheduled_registrations' ) {
            return M.multiline((this.showtitles == 'no' ? '<span class="subdue">[' + d.perf_time + ']</span> ': '') + d.class_code + ' - ' + d.display_name + (d.accompanist_name != '' ? ' <b>[' + d.accompanist_name + ']</b>':''), '<b>' + d.member_name + '</b>' + (this.showtitles == 'yes' ? '<br/>' + d.titles.replace(/\n/g, '<br/>') : '')) + (d.notes != '' ? '<b>' + d.notes + '</b>' : '');
        }
    }
    this.schedulemultislot.cellFn = function(s, i, j, d) {
        if( M.modFlagOn('ciniki.musicfestivals', 0x080000) && s.match(/registrations[0-6]/) && j <= 1 ) {
            return 'event.stopPropagation();M.ciniki_musicfestivals_main.schedulemultislot.savePos();M.ciniki_musicfestivals_main.schedulemultislot.updateRegistrationTime(\'' + d.id + '\');';
        }
        return '';
    }
    this.schedulemultislot.rowClass = function(s, i, d) {
        if( s.match(/registrations/)
            && d.status != null 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != null 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != '' 
            && M.ciniki_musicfestivals_main.festival.data['registration-status-' + d.status + '-colour'] != '#ffffff' 
            ) {
            return 'colored';
        }
        return '';
    }
    this.schedulemultislot.rowStyle = function(s, i, d) {
        if( s.match(/registrations/) ) {
            return M.ciniki_musicfestivals_main.regStatusColour(M.ciniki_musicfestivals_main.festival.data, d);
        } 
        return '';
    }
    this.schedulemultislot.rowFn = function(s, i, d) {
        if( s.match(/registration/) ) {    
            return 'M.ciniki_musicfestivals_main.registration.open(\'M.ciniki_musicfestivals_main.schedulemultislot.open();\',\'' + d.id + '\',0,0,M.ciniki_musicfestivals_main.schedulemultislot.festival_id, null,\'festival\');';
        }
    }
    this.schedulemultislot.updateRegistrationTime = function(rid) {
        M.prompt('New Time:', '', 'Update', function(n) {
            M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':rid, 'timeslot_time':n}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.schedulemultislot.open();
            });
        });
    }
    this.schedulemultislot.updateRegistrations = function() {
        var sid = this.formValue('section_id');
        if( sid != this.section_id ) {
            this.section_id = sid;
            this.class_id = 0;
        } else {
            var cid = this.formValue('class_id');
            if( cid != this.class_id ) {
                this.class_id = cid;
            }
        }
        this.open();
    }
/*    this.schedulemultislot.addRegistration = function(id) {
        if( this.schedulemultislot_id == 0 ) {
            this.save("M.ciniki_musicfestivals_main.schedulemultislot.addRegistration(" + id + ");");
        } else {
            M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':id, 'timeslot_id':this.schedulemultislot_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_musicfestivals_main.schedulemultislot.updateRegistrations();
                });
        }
    }
    this.schedulemultislot.removeRegistration = function(id) {
        M.api.getJSONCb('ciniki.musicfestivals.registrationUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':id, 'timeslot_id':0}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.schedulemultislot.updateRegistrations();
            });
    } */
    this.schedulemultislot.reopen = function() {    
        this.timeslot1_id = this.formValue('timeslot1_id');
        this.timeslot2_id = this.formValue('timeslot2_id');
        this.timeslot3_id = this.formValue('timeslot3_id');
        this.timeslot4_id = this.formValue('timeslot4_id');
        this.timeslot5_id = this.formValue('timeslot5_id');
        this.timeslot6_id = this.formValue('timeslot6_id');
        this.open();
    }
    this.schedulemultislot.open = function(cb, fid) {
        if( fid != null ) { this.festival_id = fid; }
        var args = {
            'tnid':M.curTenantID, 
            'festival_id':this.festival_id, 
            'section_id':this.section_id, 
            'class_id':this.class_id,
            'timeslot1_id':this.timeslot1_id,
            'timeslot2_id':this.timeslot2_id,
            'timeslot3_id':this.timeslot3_id,
            'timeslot4_id':this.timeslot4_id,
            'timeslot5_id':this.timeslot5_id,
            'timeslot6_id':this.timeslot6_id,
            };
        M.api.getJSONCb('ciniki.musicfestivals.scheduleMultislot', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
            var p = M.ciniki_musicfestivals_main.schedulemultislot;
            p.data = rsp;
            rsp.timeslots.unshift({'id':0, 'name':''});
            p.sections.timeslot1.fields.timeslot1_id.options = rsp.timeslots;
            p.sections.timeslot2.fields.timeslot2_id.options = rsp.timeslots;
            p.sections.timeslot3.fields.timeslot3_id.options = rsp.timeslots;
            p.sections.timeslot4.fields.timeslot4_id.options = rsp.timeslots;
            p.sections.timeslot5.fields.timeslot5_id.options = rsp.timeslots;
            p.sections.timeslot6.fields.timeslot6_id.options = rsp.timeslots;
            p.sections._section.fields.section_id.options = rsp.sections;
            p.sections._class.fields.class_id.options = rsp.classes;
            p.unscheduled_registrations = rsp.unscheduled_registrations;
            p.refresh();
            p.show(cb);
            p.refreshSections(['unscheduled_registrations']);
           });
    }
    this.schedulemultislot.toggleTitles = function() {
        if( this.showtitles == 'yes' ) {
            this.showtitles = 'no';
        } else {
            this.showtitles = 'yes';
        }
        this.refresh();
    }
    this.schedulemultislot.addButton('toggle', 'Titles', 'M.ciniki_musicfestivals_main.schedulemultislot.toggleTitles();');
    this.schedulemultislot.addClose('Back');

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
                        {'label':'Participant', 'value':registration.name},
                        {'label':'Class', 'value':registration.class_name},
                        ];
                    for(var j = 1;j <= 8; j++) {
                        if( j <= registration.max_titles ) {
                            p.data['details_' + i].push({'label':'Title #' + j, 'value':registration['title' + j]});
                            // Now combined into title[j]
//                            if( M.modFlagOn('ciniki.musicfestivals', 0x040000) ) {
//                                p.data['details_' + i].push({'label':'Movements/Musical', 'value':registration['movements' + j]});
//                                p.data['details_' + i].push({'label':'Composer', 'value':registration['composer' + j]});
//                            }
                            if( registration.participation == 1 ) {
                                p.data['details_' + i].push({'label':'Video', 'value':M.hyperlink(registration['video_url' + j])});
                                p.data['details_' + i].push({'label':'Music', 'value':registration['music_orgfilename' + j]});
                            } else if( registration['music_orgfilename' + j] != '' ) {
                                p.data['details_' + i].push({'label':'Music', 'value':registration['music_orgfilename' + j]});
                            }
                            p.data['details_' + i].push({'label':'Backtrack', 'value':registration['backtrack' + j], 'visible':'no'});
                            p.data['details_' + i].push({'label':'Artwork', 'value':registration['artwork' + j], 'visible':'no'});
                        }
                    }
                    // 
                    // Setup the comment, grade & score fields, could be for multiple adjudicators
                    //
//                    p.sections['comments'] = {'label':rsp.adjudicators[j].display_name, 'fields':{}};
                    p.sections['comments_' + i] = {'label':'Adjudicator Comments', 'fields':{}};
                    p.sections['comments_' + i].fields['comments_' + rsp.timeslot.registrations[i].id] = {
                        'label':'Comments', 
                        'type':'textarea', 
                        'size':'xlarge',
                        };
                    var label = 'Mark';
                    if( M.ciniki_musicfestivals_main.festival.data['comments-mark-label'] != null
                        && M.ciniki_musicfestivals_main.festival.data['comments-mark-label'] != ''
                        ) {
                        label = M.ciniki_musicfestivals_main.festival.data['comments-mark-label'];
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['comments-mark-ui'] != null
                        && M.ciniki_musicfestivals_main.festival.data['comments-mark-ui'] == 'yes'
                        ) {
                        p.sections['comments_' + i].fields['mark_' + rsp.timeslot.registrations[i].id] = {
                            'label':label, 
                            'type':'text', 
                            'size':'small',
                            };
                    }
                    label = 'Placement';
                    if( M.ciniki_musicfestivals_main.festival.data['comments-placement-label'] != null
                        && M.ciniki_musicfestivals_main.festival.data['comments-placement-label'] != ''
                        ) {
                        label = M.ciniki_musicfestivals_main.festival.data['comments-placement-label'];
                    }
                    if( M.ciniki_musicfestivals_main.festival.data['comments-placement-ui'] != null
                        && M.ciniki_musicfestivals_main.festival.data['comments-placement-ui'] == 'yes'
                        ) {
                        p.sections['comments_' + i].fields['placement_' + rsp.timeslot.registrations[i].id] = {
                            'label':label, 
                            'type':'text', 
                            //'size':'small',
                            };
                    }
                    label = 'Level';
                    if( M.ciniki_musicfestivals_main.festival.data['comments-level-label'] != null
                        && M.ciniki_musicfestivals_main.festival.data['comments-level-label'] != ''
                        ) {
                        label = M.ciniki_musicfestivals_main.festival.data['comments-level-label'];
                    }

                    if( M.ciniki_musicfestivals_main.festival.data['comments-level-ui'] != null
                        && M.ciniki_musicfestivals_main.festival.data['comments-level-ui'] == 'yes'
                        ) {
                        p.sections['comments_' + i].fields['level_' + rsp.timeslot.registrations[i].id] = {
                            'label':label, 
                            'type':'text', 
                            'size':'small',
                            };
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
    // The results fast entry panel
    //
    this.results = new M.panel('Results', 'ciniki_musicfestivals_main', 'results', 'mc', 'xxlarge', 'sectioned', 'ciniki.musicfestivals.main.results');
    this.results.data = null;
    this.results.festival_id = 0;
    this.results.section_id = 0;
    this.results.division_id = 0;
    this.results.sections = {
        'registrations':{'label':'Results', 'type':'simplegrid', 'num_cols':7,
            'dataMaps':['', '', '', '', 'mark', 'placement', 'level'],
            'headerValues':['Time', '#', 'Name', 'Class/Titles', 'Mark', 'Place', 'Level'],
            'cellClasses':['', '', '', 'multiline', '', '', ''],
            },
        };
    this.results.cellValue = function(s, i, j, d) {
        if( s == 'registrations' ) {
            switch(j) {
                case 0: return d.slot_time_text;
                case 1: 
                    if( M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] != null 
                        && M.ciniki_musicfestivals_main.festival.data['scheduling-timeslot-startnum'] == 'yes'
                        ) {
                        return d.timeslot_number;
                    }
                    return d.timeslot_sequence;
                case 2: return d.display_name;
                case 3: return M.multiline(d.class_name, d.titles);
            }
            if( this.sections[s].dataMaps[j] == 'placementvalue' ) {
                return '<span id="' + this.panelUID + '_' + d.id + '_placement">' + d.placement + '</span>';
            }
            if( this.sections[s].dataMaps[j] == 'placementselect' ) {
                var options = '';
                for(var i in this.data.festival['comments-placement-options']) {
                    options += '<option value="' + this.data.festival['comments-placement-options'][i] + '"'
                        + (this.data.festival['comments-placement-options'][i] == d.placement ? ' selected' : '')
                        + '>' + this.data.festival['comments-placement-options'][i] + '</option>';
                }
                return '<select id="' + this.panelUID + '_' + d.id + '_' + this.sections[s].dataMaps[j] + '" class="text" value="' + d[this.sections[s].dataMaps[j]] + '">' + options + '</select>';
//                return '<span id="' + this.panelUID + '_' + d.id + '_placement">' + d.placement + '</span>';
            }
            if( this.sections[s].dataMaps[j] == 'levelvalue' ) {
                return '<span id="' + this.panelUID + '_' + d.id + '_level">' + d.level + '</span>';
            }
            if( this.sections[s].dataMaps[j] == 'mark' ) {
                return '<input id="' + this.panelUID + '_' + d.id + '_' + this.sections[s].dataMaps[j] + '" class="text" '
                    + 'onkeyup="M.ciniki_musicfestivals_main.results.updateReg(' + d.id + ');" '
                    + 'value="' + d[this.sections[s].dataMaps[j]] + '">';
            } 
            return '<input id="' + this.panelUID + '_' + d.id + '_' + this.sections[s].dataMaps[j] + '" class="text" value="' + d[this.sections[s].dataMaps[j]] + '">';
        }
    }
    this.results.updateReg = function(id) {
        if( this.sections.registrations.dataMaps[4] != 'mark' ) {
            return true;
        }
        var fid = this.panelUID + '_' + id + '_mark';
        var mark = M.gE(fid).value;
        for(var j = 4; j < this.sections.registrations.num_cols; j++) {
            if( this.sections.registrations.dataMaps[j] == 'placementvalue' ) {
                M.gE(this.panelUID + '_' + id + '_placement').innerHTML = M.ciniki_musicfestivals_main.placementAutofill(mark);
            }
            if( this.sections.registrations.dataMaps[j] == 'levelvalue' ) {
                M.gE(this.panelUID + '_' + id + '_level').innerHTML = M.ciniki_musicfestivals_main.levelAutofill(mark);
            }
        }
    }
    this.results.open = function(cb, fid, sid, did) {
        if( fid != null ) { this.festival_id = fid; }
        if( sid != null ) { this.section_id = sid; }
        if( did != null ) { this.division_id = did; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleDivisionResultsGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'ssection_id':this.section_id, 'sdivision_id':this.division_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.results;
            p.data = rsp;
            p.sections.registrations.num_cols = 4;
            if( rsp.festival['comments-mark-ui'] != null && rsp.festival['comments-mark-ui'] == 'yes' ) {
                if( rsp.festival['comments-mark-label'] != null && rsp.festival['comments-mark-label'] != '' ) {
                    p.sections.registrations.headerValues[p.sections.registrations.num_cols] = rsp.festival['comments-mark-label'];
                } else {
                    p.sections.registrations.headerValues[p.sections.registrations.num_cols] = 'Mark';
                }
                p.sections.registrations.dataMaps[p.sections.registrations.num_cols] = 'mark';
                p.sections.registrations.num_cols++;
            }
            if( rsp.festival['comments-placement-ui'] != null && rsp.festival['comments-placement-ui'] == 'yes' ) {
                if( rsp.festival['comments-placement-label'] != null && rsp.festival['comments-placement-label'] != '' ) {
                    p.sections.registrations.headerValues[p.sections.registrations.num_cols] = rsp.festival['comments-placement-label'];
                } else {
                    p.sections.registrations.headerValues[p.sections.registrations.num_cols] = 'Placement';
                }
                p.sections.registrations.cellClasses[p.sections.registrations.num_cols] = '';
                if( M.ciniki_musicfestivals_main.festival.data['comments-placement-autofills'] != null ) {
                    p.sections.registrations.dataMaps[p.sections.registrations.num_cols] = 'placementvalue';
                } else if( M.ciniki_musicfestivals_main.results.data.festival['comments-placement-options'] != null 
                    && typeof M.ciniki_musicfestivals_main.results.data.festival['comments-placement-options'] == 'object' 
                    ) {
                    p.sections.registrations.dataMaps[p.sections.registrations.num_cols] = 'placementselect';
                    p.sections.registrations.cellClasses[p.sections.registrations.num_cols] = 'select';
                } else {
                    p.sections.registrations.dataMaps[p.sections.registrations.num_cols] = 'placement';
                }
                p.sections.registrations.num_cols++;
            }
            if( rsp.festival['comments-level-ui'] != null && rsp.festival['comments-level-ui'] == 'yes' ) {
                if( rsp.festival['comments-level-label'] != null && rsp.festival['comments-level-label'] != '' ) {
                    p.sections.registrations.headerValues[p.sections.registrations.num_cols] = rsp.festival['comments-level-label'];
                } else {
                    p.sections.registrations.headerValues[p.sections.registrations.num_cols] = 'Level';
                }
                if( M.ciniki_musicfestivals_main.festival.data['comments-level-autofills'] != null ) {
                    p.sections.registrations.dataMaps[p.sections.registrations.num_cols] = 'levelvalue';
                } else {
                    p.sections.registrations.dataMaps[p.sections.registrations.num_cols] = 'level';
                }
                p.sections.registrations.num_cols++;
            }
            p.refresh();
            p.show(cb);
            });    
    }
    this.results.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.results.close();'; }
        var c = '';
        for(var i in this.data.registrations) {
            for(var j = 4; j < this.sections.registrations.num_cols; j++) {
                var fid = this.panelUID + '_' + this.data.registrations[i].id + '_' + this.sections.registrations.dataMaps[j];
                var v = '';
                var o = '';
                if( this.sections.registrations.dataMaps[j] == 'placementvalue' ) {
                    v = M.gE(this.panelUID + '_' + this.data.registrations[i].id + '_placement');
                    if( v.innerHTML != this.data.registrations[i].placement ) {
                        c += '&placement_' + this.data.registrations[i].id + '=' + M.eU(v.innerHTML);
                    }
                }
                else if( this.sections.registrations.dataMaps[j] == 'placementselect' ) {
                    v = M.gE(this.panelUID + '_' + this.data.registrations[i].id + '_placementselect').value;
                    if( v != this.data.registrations[i].placement ) {
                        c += '&placement_' + this.data.registrations[i].id + '=' + M.eU(v );
                    }
                }
                else if( this.sections.registrations.dataMaps[j] == 'levelvalue' ) {
                    v = M.gE(this.panelUID + '_' + this.data.registrations[i].id + '_level');
                    if( v.innerHTML != this.data.registrations[i].placement ) {
                        c += '&level_' + this.data.registrations[i].id + '=' + M.eU(v.innerHTML);
                    }
                } else {
                    v = M.gE(fid).value;
                    if( v != this.data.registrations[i][this.sections.registrations.dataMaps[j]] ) {
                        c += '&' + this.sections.registrations.dataMaps[j] + '_' + this.data.registrations[i].id + '=' + M.eU(v);
                    }
                }
            }
        }
        if( c != '' ) {
            M.api.postJSONCb('ciniki.musicfestivals.scheduleDivisionResultsUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'ssection_id':this.section_id, 'sdivision_id':this.division_id}, c, function(rsp) {
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
    this.results.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.results.save();');
    this.results.addClose('Cancel');

    //
    // The provincial class codes fast entry panel
    //
    this.pcodes = new M.panel('Results', 'ciniki_musicfestivals_main', 'pcodes', 'mc', 'xlarge', 'sectioned', 'ciniki.musicfestivals.main.pcodes');
    this.pcodes.data = null;
    this.pcodes.festival_id = 0;
    this.pcodes.section_id = 0;
    this.pcodes.division_id = 0;
    this.pcodes.sections = {
        'classes':{'label':'Provincial Class Codes', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['Section', 'Category', 'Code', 'Class', 'Provincial Class Code'],
            'cellClasses':['', '', '', '', '', '', ''],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'text'],
            },
        };
    this.pcodes.cellValue = function(s, i, j, d) {
        if( s == 'classes' ) {
            switch(j) {
                case 0: return d.syllabus_section_name;
                case 1: return d.category_name;
                case 2: return d.class_code;
                case 3: return d.class_name;
                case 4: return '<input id="' + this.panelUID + '_' + d.id + '_pcode" class="text" '
                    + 'value="' + d.provincials_code + '">';
            }
        }
    }
    this.pcodes.open = function(cb, fid, sid) {
        if( fid != null ) { this.festival_id = fid; }
        if( sid != null ) { this.section_id = sid; }
        M.api.getJSONCb('ciniki.musicfestivals.scheduleProvincialCodesGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'ssection_id':this.section_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.pcodes;
            p.data = rsp;
            p.refresh();
            p.show(cb);
            });    
    }
    this.pcodes.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.pcodes.close();'; }
        var c = '';
        for(var i in this.data.classes) {
            var fid = this.panelUID + '_' + this.data.classes[i].id + '_pcode';
            var v = '';
            v = M.gE(fid).value;
            if( v != this.data.classes[i].provincials_code ) {
                c += '&provincials_code_' + this.data.classes[i].id + '=' + M.eU(v);
            }
        }
        if( c != '' ) {
            M.api.postJSONCb('ciniki.musicfestivals.scheduleProvincialCodesUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'ssection_id':this.section_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        }
    }
    this.pcodes.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.pcodes.save();');
    this.pcodes.addClose('Cancel');

    //
    // Adjudicators
    //
    this.adjudicator = new M.panel('Adjudicator', 'ciniki_musicfestivals_main', 'adjudicator', 'mc', 'large mediumaside', 'sectioned', 'ciniki.musicfestivals.main.adjudicator');
    this.adjudicator.data = null;
    this.adjudicator.festival_id = 0;
    this.adjudicator.adjudicator_id = 0;
//    this.adjudicator.customer_id = 0;
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
        'customer_details':{'label':'Customer Account', 'type':'customer', 'num_cols':2, 'aside':'yes',
            'customer_id':0,
            'customer_field':'customer_id',
            'cellClasses':['label', ''],
            'noData':'No Customer Account',
            },
        '_details':{'label':'Adjudicator Details', 'aside':'yes', 'fields':{
            'discipline':{'label':'Discipline', 'type':'text'},
            'flags1':{'label':'Festivals', 'type':'flagspiece', 'mask':0x03, 'field':'flags', 'join':'yes', 'toggle':'yes',
                'visible':function() { return M.modFlagSet('ciniki.musicfestivals', 0x020000); },   // Split live/virtual festivals
                'flags':{'0':{'name':'Both'}, '1':{'name':'Live'}, '2':{'name':'Virtual'}},
                },
            'flags3':{'label':'Websites', 'type':'flagspiece', 'mask':0x04, 'field':'flags', 'join':'yes', 'toggle':'yes', 'none':'yes',
                'flags':{'3':{'name':'Include Customer Profile Links'}},
                },
            'flags4':{'label':'Visible', 'type':'flagspiece', 'mask':0x08, 'field':'flags', 'join':'yes', 'toggle':'yes', 'none':'yes',
                'flags':{'0':{'name':'Yes'}, '4':{'name':'Hide on website'}},
                },
            }},
        '_sig_image_id':{'label':'Adjudicator Signature', 'aside':'yes', 'type':'imageform', 'fields':{
            'sig_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'size':'small', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_main.adjudicator.setFieldValue('sig_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_musicfestivals_main.adjudicator.setFieldValue(fid,0);
                    return true;
                 },
             },
        }}, 
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
        if( s == 'customer_details' && j == 0 ) { return d.label; }
        if( s == 'customer_details' && j == 1 ) {
            if( d.label == 'Email' ) {
                return M.linkEmail(d.value);
            } else if( d.label == 'Address' ) {
                return d.value.replace(/\n/g, '<br/>');
            }
            return d.value;
        }
    };
    this.adjudicator.open = function(cb, aid, cid, fid, list) {
        if( cb != null ) { this.cb = cb; }
        if( aid != null ) { this.adjudicator_id = aid; }
        if( cid != null ) { this.sections.customer_details.customer_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        if( aid != null && aid == 0 && cid != null && cid == 0 ) {
            M.startApp('ciniki.customers.edit',null,this.cb,'mc',{'next':'M.ciniki_musicfestivals_main.adjudicator.openCustomer', 'customer_id':0});
            return true;
        }
        M.api.getJSONCb('ciniki.musicfestivals.adjudicatorGet', {'tnid':M.curTenantID, 'customer_id':this.sections.customer_details.customer_id, 'adjudicator_id':this.adjudicator_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.adjudicator;
            p.data = rsp.adjudicator;
            if( rsp.adjudicator.id > 0 ) {
                p.festival_id = rsp.adjudicator.festival_id;
            }
            p.sections.customer_details.customer_id = rsp.adjudicator.customer_id;
            p.refresh();
            p.show();
        });
    }
    this.adjudicator.openCustomer = function(cid) {
        this.open(null,null,cid);
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
            M.api.postJSONCb('ciniki.musicfestivals.adjudicatorAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
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
            return 'M.ciniki_musicfestivals_main.adjudicator.save(\'M.ciniki_musicfestivals_main.adjudicator.open(null,' + this.nplist[this.nplist.indexOf('' + this.adjudicator_id) - 1] + ');\');';
        }
        return null;
    }
    this.adjudicator.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.adjudicator.save();');
    this.adjudicator.addClose('Cancel');
    this.adjudicator.addButton('next', 'Next');
    this.adjudicator.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Location
    //
    this.location = new M.panel('Location', 'ciniki_musicfestivals_main', 'location', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.location');
    this.location.data = null;
    this.location.festival_id = 0;
    this.location.location_id = 0;
    this.location.nplist = [];
    this.location.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'category':{'label':'Category', 'type':'text'},
            'address1':{'label':'Address', 'type':'text'},
            'city':{'label':'City', 'type':'text'},
            'province':{'label':'Province', 'type':'text'},
            'postal':{'label':'Postal Code', 'type':'text'},
            'latitude':{'label':'Latitude', 'type':'text'},
            'longitude':{'label':'Longitude', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.location.save();'},
            'lookkup':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_musicfestivals_main.location.lookupLatLong();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.location.location_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.location.remove();'},
            }},
        };
    this.location.fieldValue = function(s, i, d) { return this.data[i]; }
    this.location.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.locationHistory', 'args':{'tnid':M.curTenantID, 'location_id':this.location_id, 'field':i}};
    }
    this.location.open = function(cb, lid, fid, list) {
        if( lid != null ) { this.location_id = lid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.locationGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'location_id':this.location_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.location;
            p.data = rsp.location;
            p.refresh();
            p.show(cb);
        });
    }
    this.location.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.location.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.location_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.locationUpdate', {'tnid':M.curTenantID, 'location_id':this.location_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.locationAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.location.location_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.location.remove = function() {
        M.confirm('Are you sure you want to remove location?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.locationDelete', {'tnid':M.curTenantID, 'location_id':M.ciniki_musicfestivals_main.location.location_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.location.close();
            });
        });
    }
    this.location.lookupLatLong = function() {
        M.startLoad();
        if( document.getElementById('googlemaps_js') == null) {
            var script = document.createElement("script");
            script.id = 'googlemaps_js';
            script.type = "text/javascript";
            script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curTenant.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_musicfestivals_main.location.lookupGoogleLatLong";
            document.body.appendChild(script);
        } else {
            this.lookupGoogleLatLong();
        }
    }
    this.location.lookupGoogleLatLong = function() {
        var address = this.formValue('address1')
            + ', ' + this.formValue('city')
            + ', ' + this.formValue('province')
            + ', ' + this.formValue('country');
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode( { 'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                M.ciniki_musicfestivals_main.location.setFieldValue('latitude', results[0].geometry.location.lat());
                M.ciniki_musicfestivals_main.location.setFieldValue('longitude', results[0].geometry.location.lng());
            } else {
                M.alert('Geocode was not successful for the following reason: ' + status);
            }
        }); 
        M.stopLoad();
    }
    this.location.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.location_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.location.save(\'M.ciniki_musicfestivals_main.location.open(null,' + this.nplist[this.nplist.indexOf('' + this.location_id) + 1] + ');\');';
        }
        return null;
    }
    this.location.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.location_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.location.save(\'M.ciniki_musicfestivals_main.location.open(null,' + this.nplist[this.nplist.indexOf('' + this.location_id) - 1] + ');\');';
        }
        return null;
    }
    this.location.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.location.save();');
    this.location.addClose('Cancel');
//    this.location.addButton('next', 'Next');
//    this.location.addLeftButton('prev', 'Prev');


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
                case 4: return (d.participation == 1 ? 'Virtual' : 'Live');
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
    this.sponsor = new M.panel('Sponsor', 'ciniki_musicfestivals_main', 'sponsor', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.sponsor');
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
        'general':{'label':'Sponsor Information', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'url':{'label':'Website', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
//            'flags':{'label':'Options', 'type':'flags', 'flags':{
//                }},
            }},
        '_tags':{'label':'Tags', 'fields':{
            'tags':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new tag:'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
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
            p.sections._tags.fields.tags.tags = rsp.tags;
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
        M.confirm('Are you sure you want to remove sponsor?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.sponsorDelete', {'tnid':M.curTenantID, 'sponsor_id':M.ciniki_musicfestivals_main.sponsor.sponsor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.sponsor.close();
            });
        });
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
            'flags1':{'label':'Visible', 'type':'flagtoggle', 'field':'flags', 'default':'on', 'bit':0x01},
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
        M.confirm('Are you sure you want to remove timeslotimage?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.timeslotImageDelete', {'tnid':M.curTenantID, 'timeslot_image_id':M.ciniki_musicfestivals_main.timeslotimage.timeslot_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.timeslotimage.close();
            });
        });
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
        M.confirm('Are you sure you want to remove list?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.listDelete', {'tnid':M.curTenantID, 'list_id':this.list_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.list.close();
            });
        });
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
        M.confirm('Are you sure you want to remove listsection?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.listSectionDelete', {'tnid':M.curTenantID, 'listsection_id':M.ciniki_musicfestivals_main.listsection.listsection_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.listsection.close();
            });
        });
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
        M.confirm('Are you sure you want to remove listentry?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.listEntryDelete', {'tnid':M.curTenantID, 'listentry_id':M.ciniki_musicfestivals_main.listentry.listentry_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.listentry.close();
            });
        });
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
            'orientation':{'label':'Orientation', 'type':'toggle', 'toggles':{'L':'Landscape', 'P':'Portrait'}},
// FIXME: Add section support and min score support
//            'section_id':{'label':'Section', 'type':'select', 'options':{}, 'complex_options':{'name':'name', 'value':'id'}},
//            'min_score':{'label':'Minimum Score', 'type':'text', 'size':'small'},
            'participation':{'label':'Participation', 'type':'toggle', 
                'visible':'no',
                'toggles':{'0':'Default'},
                },
            }},
        'fields':{'label':'Auto Filled Fields', 'type':'simplegrid', 'num_cols':1,
            'menu':{
                'add':{
                    'label':'Add Field',
                    'fn':'M.ciniki_musicfestivals_main.certificate.save("M.ciniki_musicfestivals_main.certfield.open(\'M.ciniki_musicfestivals_main.certificate.open();\',0,M.ciniki_musicfestivals_main.certificate.certificate_id);");',
                    },
                },
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
            p.sections.general.fields.participation.visible = 'no';
            p.sections.general.fields.participation.toggles = {'0':'Default'};
            if( (M.ciniki_musicfestivals_main.festival.data.flags&0x02) == 0x02 ) {
                p.sections.general.fields.participation.visible = 'yes';
                p.sections.general.fields.participation.toggles = {'0':'Default', '10':'Live', '20':'Virtual'};
            }
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
        M.confirm('Are you sure you want to remove certificate?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.certificateDelete', {'tnid':M.curTenantID, 'certificate_id':M.ciniki_musicfestivals_main.certificate.certificate_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.certificate.close();
            });
        });
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
                'class-group':'Class - Group',
                'timeslotdate':'Timeslot Date',
                'participant':'Participant',
                'title':'Title',
                'adjudicator':'Adjudicator',
                'adjudicatorsig':'Adjudicator Signature',
                'adjudicatorsigorname':'Adjudicator Signature or Name',
                'placement':'Placement',
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
        M.confirm('Are you sure you want to remove certfield?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.certfieldDelete', {'tnid':M.curTenantID, 'field_id':M.ciniki_musicfestivals_main.certfield.field_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.certfield.close();
            });
        });
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
    this.trophy = new M.panel('Trophy/Award', 'ciniki_musicfestivals_main', 'trophy', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.trophy');
    this.trophy.data = null;
    this.trophy.trophy_id = 0;
    this.trophy.nplist = [];
    this.trophy.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 
            'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_musicfestivals_main.trophy.setFieldValue('primary_image_id', iid);
                        return true;
                        },
                    'deleteImage':function(fid) {
                        M.ciniki_musicfestivals_main.trophy.setFieldValue(fid,0);
                        return true;
                     },
                    'addDropImageRefresh':'',
                 },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'typename':{'label':'Type', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'donated_by':{'label':'Donated By', 'type':'text'},
            'first_presented':{'label':'First Presented', 'type':'text'},
            'criteria':{'label':'Criteria', 'type':'text'},
            'amount':{'label':'Amount', 'type':'text'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'winners':{'label':'Winners', 'type':'simplegrid', 'num_cols':2, 
            'menu':{
                'add':{
                    'label':'Add Winner',
                    'fn':'M.ciniki_musicfestivals_main.trophy.save("M.ciniki_musicfestivals_main.trophywinner.open(\'M.ciniki_musicfestivals_main.trophy.open();\',0,M.ciniki_musicfestivals_main.trophy.trophy_id);");',
                    },
                },
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
    this.trophy.liveSearchCb = function(s, i, v) {
        if( i == 'typename' || i == 'category' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.trophyFieldSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_musicfestivals_main.trophy.liveSearchShow(s,i,M.gE(M.ciniki_musicfestivals_main.trophy.panelUID + '_' + i), rsp.results);
                });
        }
    }
    this.trophy.liveSearchResultValue = function(s, f, i, j, d) {
        return d.value;
    }
    this.trophy.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_musicfestivals_main.trophy.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.value) + '\');';
    }
    this.trophy.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
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
        M.confirm('Are you sure you want to remove trophy?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.trophyDelete', {'tnid':M.curTenantID, 'trophy_id':M.ciniki_musicfestivals_main.trophy.trophy_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.trophy.close();
            });
        });
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
        M.confirm('Are you sure you want to remove trophywinner?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.trophyWinnerDelete', {'tnid':M.curTenantID, 'winner_id':M.ciniki_musicfestivals_main.trophywinner.winner_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.trophywinner.close();
            });
        });
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
    // This panel will allow mass updates to City and Province
    //
    this.editcityprov = new M.panel('Update', 'ciniki_musicfestivals_main', 'editcityprov', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.editcityprov');
    this.editcityprov.data = null;
    this.editcityprov.city = '';
    this.editcityprov.province = '';
    this.editcityprov.sections = {
        'general':{'label':'', 'fields':{
            'city':{'label':'City', 'type':'text', 'visible':'yes'},
            'province':{'label':'Province', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.editcityprov.save();'},
            }},
        };
    this.editcityprov.open = function(cb, c, p) {
        if( c != null ) {
            this.city = unescape(c);
            this.sections.general.fields.city.visible = 'yes';
        } else {
            this.sections.general.fields.city.visible = 'no';
        }
        this.province = unescape(p);
        this.data = {
            'city':unescape(c),
            'province':unescape(p),
            };
        this.refresh();
        this.show(cb);
    }
    this.editcityprov.save = function() {
        var args = {
            'tnid':M.curTenantID, 
            'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
            };
        if( this.sections.general.fields.city.visible == 'yes' ) {
            args['old_city'] = M.eU(this.city);
            args['new_city'] = M.eU(this.formValue('city'));
        }
        args['old_province'] = M.eU(this.province);
        args['new_province'] = M.eU(this.formValue('province'));
        M.api.getJSONCb('ciniki.musicfestivals.competitorCityProvUpdate', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.editcityprov.close();
        });
    }
    this.editcityprov.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.editcityprov.save();');
    this.editcityprov.addClose('Cancel');

    //
    // Create and send a email message to a selection of competitors/teachers with
    // filtering for section, timeslot sections, etc
    //
    this.message = new M.panel('Message',
        'ciniki_musicfestivals_main', 'message',
        'mc', 'xlarge mediumaside', 'sectioned', 'ciniki.musicfestivals.main.message');
    this.message.data = {};
    this.message.festival_id = 0;
    this.message.message_id = 0;
    this.message.upload = null;
    this.message.nplist = [];
    this.message.sections = {
        'details':{'label':'Details', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label mediumlabel', ''],
            // Status
            // # competitors
            // # teachers
            // # accompanists
            // 'dt_sent':{'label':'Year', 'type':'text'},
            },
        'objects':{'label':'Recipients', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            'menu':{
                'add':{
                    'label':'Add/Remove Recipient(s)',
                    'visible':function() { return M.ciniki_musicfestivals_main.message.data.status == 10 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.message.save("M.ciniki_musicfestivals_main.message.openrefs();");',
                    },
                'view':{
                    'label':'View Recipient(s)',
                    'visible':function() { return M.ciniki_musicfestivals_main.message.data.status > 10 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_main.message.save("M.ciniki_musicfestivals_main.message.openrefs();");',
                    },
                },
            },
        '_subject':{'label':'Subject', 'fields':{
            'subject':{'label':'Subject', 'hidelabel':'yes', 'type':'text'},
            }},
        '_content':{'label':'Message', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'files':{'label':'Attachments', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['', 'alignright fabuttons'],
            'noData':'No attachments',
            'menu':{
                'add':{
                    'label':'Attach File',
                    'fn':'M.ciniki_musicfestivals_main.message.save("M.ciniki_musicfestivals_main.message.fileAdd();");',
                    },
                },
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 
                'visible':function() {return M.ciniki_musicfestivals_main.message.data.status == 10 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.message.save();',
                },
            'back':{'label':'Back', 
                'visible':function() {return M.ciniki_musicfestivals_main.message.data.status > 10 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.message.close();',
                },
            'sendtest':{'label':'Send Test Message', 
                'visible':function() {return M.ciniki_musicfestivals_main.message.data.send == 'yes' ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.message.save("M.ciniki_musicfestivals_main.message.sendTest();");',
                },
            'schedule':{'label':'Schedule', 
                'visible':function() {return M.ciniki_musicfestivals_main.message.data.send == 'yes' ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.message.schedule();',
                },
            'unschedule':{'label':'Unschedule', 
                'visible':function() {return M.ciniki_musicfestivals_main.message.data.status == 30 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.message.unschedule();',
                },
            'sendnow':{'label':'Send Now', 
                'visible':function() {return M.ciniki_musicfestivals_main.message.data.send == 'yes' ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.message.save("M.ciniki_musicfestivals_main.message.sendNow();");',
                },
            'emaillist':{'label':'Email Addresses List', 
                'fn':'M.ciniki_musicfestivals_main.message.save("M.ciniki_musicfestivals_main.messagelist.open(\'M.ciniki_musicfestivals_main.message.open();\',M.ciniki_musicfestivals_main.message.message_id,M.ciniki_musicfestivals_main.message.festival_id);");',
                },
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.message.message_id > 0 && M.ciniki_musicfestivals_main.message.data.status == 10 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.message.remove();',
                },
            }},
        };
    this.message.cellValue = function(s, i, j, d) {
        if( s == 'details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'objects' ) {
            switch(j) {
                case 0: return d.type;
                case 1: return d.label;
            }
        }
        if( s == 'files' ) {
            switch(j) {
                case 0: return d.filename;
            }
            if( this.data.status == 10 && j == 1 ) {
                return M.faBtn('&#xf019;', 'Download', 'M.ciniki_musicfestivals_main.message.fileDownload(\'' + escape(d.filename) + '\');')
                    + M.faBtn('&#xf014;', 'Delete', 'M.ciniki_musicfestivals_main.message.fileDelete(\'' + escape(d.filename) + '\');');
            }
            if( this.data.status > 10 && j == 1 ) {
                return M.faBtn('&#xf019;', 'Download', 'M.ciniki_musicfestivals_main.message.fileDownload(\'' + escape(d.filename) + '\');');
            }
            return '';
        }
    }
    // Add a new message with object and object_id
    this.message.addnew = function(cb, fid, o, oid) {
        var args = {'tnid':M.curTenantID, 'festival_id':fid};
        args['subject'] = '';
        args['object'] = o;
        args['object_id'] = oid;
        M.api.getJSONCb('ciniki.musicfestivals.messageAdd', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.message.open(cb, rsp.id);
        });
    }
    this.message.openrefs = function() {
        M.ciniki_musicfestivals_main.messagerefs.open('M.ciniki_musicfestivals_main.message.open();', M.ciniki_musicfestivals_main.message.message_id);
    }
    this.message.fileAdd = function() {
        if( this.upload == null ) {
            this.upload = M.aE('input', this.panelUID + '_file_upload', 'image_uploader');
            this.upload.setAttribute('name', 'filename');
            this.upload.setAttribute('type', 'file');
            this.upload.setAttribute('onchange', this.panelRef + '.uploadFile();');
        }
        this.upload.value = '';
        this.upload.click();
    }
    this.message.uploadFile = function() {
        var f = this.upload;
        M.api.postJSONFile('ciniki.musicfestivals.messageFileAdd', {'tnid':M.curTenantID, 'message_id':this.message_id}, f.files[0],
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.message;
                p.data.files = rsp.files;
                p.refreshSection('files');
            });
    }
    this.message.fileDelete = function(f) {
        M.api.getJSONCb('ciniki.musicfestivals.messageFileDelete', {'tnid':M.curTenantID, 'message_id':this.message_id, 'filename':f}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_musicfestivals_main.message;
                p.data.files = rsp.files;
                p.refreshSection('files');
            });
    }
    this.message.fileDownload = function(f) {
        M.api.openFile('ciniki.musicfestivals.messageFileDownload', {'tnid':M.curTenantID, 'message_id':this.message_id, 'filename':f});
    }
    this.message.open = function(cb, mid, fid, list) {
        if( mid != null ) { this.message_id = mid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.messageGet', {'tnid':M.curTenantID, 'message_id':this.message_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.message;
            p.data = rsp.message;
//            if( rsp.message.status == 10 ) {
//                p.sections.objects.addTxt = "Add/Remove Recipients";
//            } else {
//                p.sections.objects.addTxt = "View Recipients";
//            }
            if( rsp.message.status == 10 ) {
                p.addClose('Cancel');
                p.sections._subject.fields.subject.editable = 'yes';
                p.sections._content.fields.content.editable = 'yes';
            } else {
                p.addClose('Back');
                p.sections._subject.fields.subject.editable = 'no';
                p.sections._content.fields.content.editable = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.message.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.message.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.message_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.messageUpdate', {'tnid':M.curTenantID, 'message_id':this.message_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.messageAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'status':10}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.message.message_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.message.sendTest = function() {
        M.api.getJSONCb('ciniki.musicfestivals.messageSend', {'tnid':M.curTenantID, 'message_id':this.message_id, 'send':'test'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert(rsp.msg);
            M.ciniki_musicfestivals_main.message.open();
        });
    }
    this.message.sendNow = function() {
        if( M.modFlagOn('ciniki.musicfestivals', 0x8000) ) {
            var msg = '<b>' + (this.data.num_teachers == 0 ? 'No' : this.data.num_teachers) + '</b> teacher' + (this.data.num_teachers != 1 ? 's' :'')
                + ', <b>' + (this.data.num_accompanists == 0 ? 'no' : this.data.num_accompanists) + '</b> accompanist' + (this.data.num_accompanists != 1 ? 's' : '') 
                + ' and <b>' + (this.data.num_competitors == 0 ? 'no' : this.data.num_competitors) + '</b> competitor' + (this.data.num_competitors != 1 ? 's' : '') 
                + ' will receive this email. <br/></br>';
        } else {
            var msg = '<b>' + (this.data.num_teachers == 0 ? 'No' : this.data.num_teachers) + '</b> teacher' + (this.data.num_teachers != 1 ? 's' :'')
                + ' and <b>' + (this.data.num_competitors == 0 ? 'no' : this.data.num_competitors) + '</b> competitor' + (this.data.num_competitors != 1 ? 's' : '') 
                + ' will receive this email. <br/></br>';
        }
        M.confirm(msg + ' Is this email correct and ready to send?', null, function() {
            M.api.getJSONCb('ciniki.musicfestivals.messageSend', {'tnid':M.curTenantID, 'message_id':M.ciniki_musicfestivals_main.message.message_id, 'send':'all'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.alert(rsp.msg);
                M.ciniki_musicfestivals_main.message.open();
            });
        });
    }
    this.message.schedule = function() {
        if( M.modFlagOn('ciniki.musicfestivals', 0x8000) ) {
            var msg = '<b>' + (this.data.num_teachers == 0 ? 'No' : this.data.num_teachers) + '</b> teacher' + (this.data.num_teachers != 1 ? 's' :'')
                + ', <b>' + (this.data.num_accompanists == 0 ? 'no' : this.data.num_accompanists) + '</b> accompanist' + (this.data.num_accompanists != 1 ? 's' : '') 
                + ' and <b>' + (this.data.num_competitors == 0 ? 'no' : this.data.num_competitors) + '</b> competitor' + (this.data.num_competitors != 1 ? 's' : '') 
                + ' will receive this email. <br/></br>';

        } else {
            var msg = '<b>' + (this.data.num_teachers == 0 ? 'No' : this.data.num_teachers) + '</b> teacher' + (this.data.num_teachers != 1 ? 's' :'')
                + ' and <b>' + (this.data.num_competitors == 0 ? 'no' : this.data.num_competitors) + '</b> competitor' + (this.data.num_competitors != 1 ? 's' : '') 
                + ' will receive this email. <br/></br>';
        }
        M.confirm(msg + 'Are you sure the email is correct and ready to be sent?', null, function() {
            M.ciniki_musicfestivals_main.messageschedule.open();
        });
    }
    this.message.schedulenow = function() {
        var sd = M.ciniki_musicfestivals_main.messageschedule.formValue('dt_scheduled');
        if( sd != this.data.dt_scheduled ) {
            M.api.getJSONCb('ciniki.musicfestivals.messageUpdate', {'tnid':M.curTenantID, 'message_id':this.message_id, 'dt_scheduled':sd, 'status':30}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.message.close();
            });
        } else {
            this.close();
        }
    }
    this.message.unschedule = function() {
        M.api.getJSONCb('ciniki.musicfestivals.messageUpdate', {'tnid':M.curTenantID, 'message_id':this.message_id, 'status':10}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_musicfestivals_main.message.open();
        });
    }
    this.message.remove = function() {
        M.confirm('Are you sure you want to remove message?', null, function() {
            M.api.getJSONCb('ciniki.musicfestivals.messageDelete', {'tnid':M.curTenantID, 'message_id':M.ciniki_musicfestivals_main.message.message_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.message.close();
            });
        });
    }
    this.message.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.message_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.message.save(\'M.ciniki_musicfestivals_main.message.open(null,' + this.nplist[this.nplist.indexOf('' + this.message_id) + 1] + ');\');';
        }
        return null;
    }
    this.message.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.message_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.message.save(\'M.ciniki_musicfestivals_main.message.open(null,' + this.nplist[this.nplist.indexOf('' + this.message_id) - 1] + ');\');';
        }
        return null;
    }
    this.message.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.message.save();');
    this.message.addButton('next', 'Next');
    this.message.addLeftButton('prev', 'Prev');
    this.message.helpSections = function() {
        return {
            'help':{'label':'Substitutions', 'type':'htmlcontent',
                'html':'The following substitutions are available in the Message:<br/><br/>'
                    + '{_first_} = Teacher/Individual first name, Group/Ensemble full name<br/>'
                    + '{_name_} = Teacher/Individual/Group full name<br/>'
                    },
            };
    }

    //
    // This panel will let the user select a date and time to send the scheduled message
    //
    this.messageschedule = new M.panel('Schedule Message',
        'ciniki_musicfestivals_main', 'messageschedule',
        'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.messageschedule');
    this.messageschedule.data = {};
    this.messageschedule.sections = {
        'general':{'label':'Schedule Date and Time', 'fields':{
            'dt_scheduled':{'label':'', 'hidelabel':'yes', 'type':'datetime'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'send':{'label':'Schedule', 
                'fn':'M.ciniki_musicfestivals_main.message.schedulenow();',
                },
            'delete':{'label':'Cancel',
                'fn':'M.ciniki_musicfestivals_main.message.open();',
                },
            }},
        };
    this.messageschedule.open = function() {
        if( M.ciniki_musicfestivals_main.message.data.dt_scheduled != '0000-00-00 00:00:00' ) {
            this.data = {
                'dt_scheduled':M.ciniki_musicfestivals_main.message.data.dt_scheduled_text,
                };
        } else {
            this.data.dt_scheduled = '';
        }
        this.refresh();
        this.show();
    }


    //
    // This panel shows the available objects that can be used to send a message to.
    //
    this.messagerefs = new M.panel('Message Recipients',
        'ciniki_musicfestivals_main', 'messagerefs',
        'mc', 'xlarge mediumaside', 'sectioned', 'ciniki.musicfestivals.main.messagerefs');
    this.messagerefs.data = {};
    this.messagerefs.festival_id = 0;
    this.messagerefs.message_id = 0;
    this.messagerefs.section_id = 0;
    this.messagerefs.category_id = 0;
    this.messagerefs.schedule_id = 0;
    this.messagerefs.division_id = 0;
    this.messagerefs.nplist = [];
    this.messagerefs.sections = {
        'details':{'label':'Details', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label mediumlabel', ''],
            // Status
            // # competitors
            // # teachers
            // 'dt_sent':{'label':'Year', 'type':'text'},
            },
        'excluded':{'label':'Include', 'aside':'yes', 'fields':{
            'flags1':{'label':'', 'hidelabel':'yes', 'type':'flagspiece', 'default':'off', 'mask':0x07,
            'field':'flags', 'toggle':'no', 'join':'yes',
//            'flags':{'0':{'name':'Everybody'},'6':{'name':'Only Competitors'}, '5':{'name':'Only Teachers'}, '3':{'name':'Only Accompanists'}},
            'flags':{},
            'onchange':'M.ciniki_musicfestivals_main.messagerefs.updateFlags',
                },
            }},
/*        '_excluded':{'label':'', 'aside':'yes', 'fields':{
            'flags1':{'label':'Exclude Competitors', 'type':'flagtoggle', 'default':'off', 'bit':0x01,
                'field':'flags',
                'onchange':'M.ciniki_musicfestivals_main.messagerefs.updateFlags',
            },
            'flags2':{'label':'Exclude Teachers', 'type':'flagtoggle', 'default':'off', 'bit':0x02,
                'field':'flags',
                'onchange':'M.ciniki_musicfestivals_main.messagerefs.updateFlags',
                },
            }}, */
        'objects':{'label':'Recipients', 'type':'simplegrid', 'num_cols':3, 'aside':'yes',
            'cellClasses':['label mediumlabel', '', 'alignright'],
            'noData':'No Recipients',
            },
        '_extract':{'label':'', 'aside':'yes', 'buttons':{
            'extract':{'label':'Extract Recipients', 'fn':'M.ciniki_musicfestivals_main.messagerefs.extractRecipients();'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'sections', 'tabs':{
            'sections':{'label':'Syllabus', 'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("sections");'},
            'categories':{'label':'Categories', 
                'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.section_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("categories");',
                },
            'classes':{'label':'Classes', 
                'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.category_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("classes");',
                },
            'schedule':{'label':'Schedule', 'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("schedule");'},
            'divisions':{'label':'Divisions', 
                'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.schedule_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("divisions");',
                },
            'timeslots':{'label':'Timeslots', 
                'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.division_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("timeslots");',
                },
            'tags':{'label':'Tags', 
                'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("tags");',
                },
            'statuses':{'label':'Status', 
                'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("statuses");',
                },
            'teachers':{'label':'Teachers', 'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("teachers");'},
            'competitors':{'label':'Competitors', 'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("competitors");'},
            'accompanists':{'label':'Accompanists', 'fn':'M.ciniki_musicfestivals_main.messagerefs.switchTab("accompanists");'},
            }},
/*        '_file':{'label':'Attach Files', 
            'fields':{
                'attachment1':{'label':'File 1', 'type':'file', 'hidelabel':'no'},
                'attachment2':{'label':'File 2', 'type':'file', 'hidelabel':'no'},
                'attachment3':{'label':'File 3', 'type':'file', 'hidelabel':'no'},
                'attachment4':{'label':'File 4', 'type':'file', 'hidelabel':'no'},
                'attachment5':{'label':'File 5', 'type':'file', 'hidelabel':'no'},
            }}, */
        'sections':{'label':'Syllabus', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'sections' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'categories' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'classes' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
        'schedule':{'label':'Schedule', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'schedule' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
        'divisions':{'label':'Divisions', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'divisions' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
        'timeslots':{'label':'Timeslots', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'timeslots' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
        'tags':{'label':'Registration Tags', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'tags' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
        'statuses':{'label':'Registration Statuses', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'statuses' ? 'yes' : 'no';},
            'cellClasses':['', 'alignright fabuttons'],
            },
//        'competitor_search':{'label':'Search Competitors', 'type':'simplegrid', 'num_cols':2,
//            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'competitors' ? 'yes' : 'no';},
//            'cellClasses':['', 'alignright fabuttons'],
//            },
        'competitors':{'label':'Competitors', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'competitors' ? 'yes' : 'no';},
            'headerValues':['Name', 'Status'],
            'headerClasses':['', 'alignright'],
            'cellClasses':['', 'alignright fabuttons'],
            'sortable':'yes',
            'sortTypes':['text', 'alttext'],
            },
        'teachers':{'label':'Teachers', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'teachers' ? 'yes' : 'no';},
            'headerValues':['Name', 'Status'],
            'headerClasses':['', 'alignright'],
            'cellClasses':['', 'alignright fabuttons'],
            'sortable':'yes',
            'sortTypes':['text', 'alttext'],
            },
        'accompanists':{'label':'Accompanists', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_musicfestivals_main.messagerefs.sections._tabs.selected == 'accompanists' ? 'yes' : 'no';},
            'headerValues':['Name', 'Status'],
            'headerClasses':['', 'alignright'],
            'cellClasses':['', 'alignright fabuttons'],
            'sortable':'yes',
            'sortTypes':['text', 'alttext'],
            },
        '_buttons':{'label':'', 'buttons':{
            'done':{'label':'Done', 'fn':'M.ciniki_musicfestivals_main.messagerefs.close();'},
            }},
        };
    this.messagerefs.cellSortValue = function(s, i, j, d) {
        if( d.added != null && d.added == 'yes' ) {
            return 1;
        } else if( d.included != null && d.included == 'yes' ) {
            return 2;
        } else {
            return 3;
        }
    }
    this.messagerefs.cellValue = function(s, i, j, d) {
        if( s == 'details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'objects' ) {
            switch(j) {
                case 0: return d.type;
                case 1: return d.label;
                case 2: return '<span class="faicon">&#xf014;</span>&nbsp;';
            }
        }
        if( s == 'sections' || s == 'categories' || s == 'classes' || s == 'schedule' || s == 'divisions' || s == 'timeslots' || s == 'tags' || s == 'statuses' || s == 'competitors' || s == 'accompanists' ) {
            if( j == 0 ) {
                return d.name;
            }
            if( j == 1 ) {
                if( d.added != null && d.added == 'yes' ) {
                    if( this.data.message.status == 10 ) {
                        return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.messagerefs.removeObject(\'' + d.object + '\',\'' + d.id + '\');">Remove</button>';
                    } else {
                        return 'Added';
                    }
                } else if( d.included != null && d.included == 'yes' ) {
                    return 'Included';
                } else if( d.object != null && d.partial == null ) {
                    if( this.data.message.status == 10 ) {
                        return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.messagerefs.addObject(\'' + d.object + '\',\'' + d.id + '\');">Add</button>';
                    } else {
                        return '';
                    }
                } else if( d.object != null && d.partial == null ) {
                    return '';
                }
            }
        }
        if( s == 'teachers' ) {
            if( j == 0 ) {
                return d.name;
            }
            if( j == 1 ) {
                var html = '';
                if( d.included != null ) {
                    return 'Included';
                }
                else if( d.students != null ) {
                    return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.messagerefs.removeObject(\'ciniki.musicfestivals.students\',\'' + d.id + '\');">Remove Teacher & Students</button>';
                }
                else if( d.added != null ) {
                    return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.messagerefs.removeObject(\'ciniki.musicfestivals.teacher\',\'' + d.id + '\');">Remove Teacher</button>';
                }
                else { 
                    return '<button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.messagerefs.addObject(\'ciniki.musicfestivals.students\',\'' + d.id + '\');">Add Teacher & Students</button>'
                        + ' <button onclick="event.stopPropagation();M.ciniki_musicfestivals_main.messagerefs.addObject(\'ciniki.musicfestivals.teacher\',\'' + d.id + '\');">Add Teacher Only</button>';
                }
            }
        }
    }
    this.messagerefs.cellFn = function(s, i, j, d) {
        if( s == 'objects' && j == 2 ) {    
            return 'M.ciniki_musicfestivals_main.messagerefs.removeObject(\'' + d.object + '\',\'' + d.object_id + '\');';
        }
    }
    this.messagerefs.rowClass = function(s, i, d) {
        if( (d.partial != null && d.partial == 'yes') ) {
            return 'statusorange';
        }
        else if( (d.added != null && d.added == 'yes')
            || (d.included != null && d.included == 'yes') 
            || (d.students != null && d.students == 'yes') 
            ) {
            return 'statusgreen';
        }
    }
    this.messagerefs.rowFn = function(s, i, d) {
        if( s == 'sections' || s == 'categories' || s == 'schedule' || s == 'divisions' ) {
            if( d.added == null && d.included == null ) {
                return 'M.ciniki_musicfestivals_main.messagerefs.switchSubTab(\'' + s + '\',' + d.id + ');';
            }
        }
        return '';
    }
    this.messagerefs.extractRecipients = function() {
        M.api.getJSONCb('ciniki.musicfestivals.messageGet', {'tnid':M.curTenantID, 
            'message_id':this.message_id, 
            'allrefs':'yes', 
            'section_id':this.section_id, 
            'category_id':this.category_id,
            'schedule_id':this.schedule_id, 
            'division_id':this.division_id,
            'action':'extractrecipients',
            }, this.openFinish);
    }
    this.messagerefs.switchTab = function(t) {
        this.sections._tabs.selected = t;
        if( t == 'sections' || t == 'schedule' || t == 'teachers' || t == 'competitors' || t == 'tags' || t == 'statuses' || t == 'accompanists' ) {
            this.section_id = 0;
            this.category_id = 0;
            this.schedule_id = 0;
            this.division_id = 0;
            this.registration_tag = '';
            this.registration_status = '';
        }
        else if( t == 'categories' ) {
            this.category_id = 0;
            this.schedule_id = 0;
            this.division_id = 0;
            this.registration_tag = '';
            this.registration_status = '';
        }
        else if( t == 'divisions' ) {
            this.section_id = 0;
            this.category_id = 0;
            this.division_id = 0;
            this.registration_tag = '';
            this.registration_status = '';
        }
        this.open();
    }
    this.messagerefs.switchSubTab = function(s, id) {
        if( s == 'sections' ) {
            this.section_id = id;
            this.switchTab('categories');
        }
        if( s == 'categories' ) {
            this.category_id = id;
            this.switchTab('classes');
        }
        if( s == 'schedule' ) {
            this.schedule_id = id;
            this.switchTab('divisions');
        }
        if( s == 'divisions' ) {
            this.division_id = id;
            this.switchTab('timeslots');
        }
    }
    this.messagerefs.updateFlags = function() {
        var f = this.data.message.flags;
        if( (this.formValue('flags1')&0x01) == 0x01 ) {
            f |= 0x01;
        } else {
            f &= 0xFFFE;
        }
        if( (this.formValue('flags1')&0x02) == 0x02 ) {
            f |= 0x02;
        } else {
            f &= 0xFFFD;
        }
        if( (this.formValue('flags1')&0x04) == 0x04 ) {
            f |= 0x04;
        } else {
            f &= 0xFFFB;
        }
        if( f != this.data.message.flags ) {
            M.api.getJSONCb('ciniki.musicfestivals.messageGet', {'tnid':M.curTenantID, 
                'message_id':this.message_id, 
                'allrefs':'yes', 
                'section_id':this.section_id, 
                'category_id':this.category_id,
                'schedule_id':this.schedule_id, 
                'division_id':this.division_id,
                'action':'updateflags',
                'flags':f,
                }, this.openFinish);
        } 
    }
    this.messagerefs.addObject = function(o, oid) {
        M.api.getJSONCb('ciniki.musicfestivals.messageGet', {'tnid':M.curTenantID, 
            'message_id':this.message_id, 
            'allrefs':'yes', 
            'section_id':this.section_id, 
            'category_id':this.category_id,
            'schedule_id':this.schedule_id, 
            'division_id':this.division_id,
            'action':'addref',
            'object':o,
            'object_id':oid,
            }, this.openFinish);
    }
    this.messagerefs.removeObject = function(o, oid) {
        M.api.getJSONCb('ciniki.musicfestivals.messageGet', {'tnid':M.curTenantID, 
            'message_id':this.message_id, 
            'allrefs':'yes', 
            'section_id':this.section_id, 
            'category_id':this.category_id,
            'schedule_id':this.schedule_id, 
            'division_id':this.division_id,
            'action':'removeref',
            'object':o,
            'object_id':oid,
            }, this.openFinish);
    }
    this.messagerefs.open = function(cb, mid) {
        if( cb != null ) { this.cb = cb; }
        if( mid != null ) { this.message_id = mid; }
        M.api.getJSONCb('ciniki.musicfestivals.messageGet', {'tnid':M.curTenantID, 
            'message_id':this.message_id, 
            'allrefs':'yes', 
            'section_id':this.section_id, 
            'category_id':this.category_id,
            'schedule_id':this.schedule_id, 
            'division_id':this.division_id,
            }, this.openFinish);
    }
    this.messagerefs.openFinish = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_musicfestivals_main.messagerefs;
        p.data = rsp;
        p.data.flags = rsp.message.flags;
        p.data.details = rsp.message.details;
        p.data.objects = rsp.message.objects;
        p.refresh();
        p.show();
    }
    this.messagerefs.goback = function() {
        if( this.sections._tabs.selected == 'categories' ) {
            this.switchTab("sections");
        } else if( this.sections._tabs.selected == 'classes' ) {
            this.switchTab("categories");
        } else if( this.sections._tabs.selected == 'divisions' ) {
            this.switchTab("schedule");
        } else if( this.sections._tabs.selected == 'timeslots' ) {
            this.switchTab("divisions");
        } else {
            this.close();
        }
    }
    this.messagerefs.addLeftButton('back', 'Back', 'M.ciniki_musicfestivals_main.messagerefs.goback();');

    //
    // View the message email list
    //
    this.messagelist = new M.panel('Message Email Addresses',
        'ciniki_musicfestivals_main', 'messagelist',
        'mc', 'xlarge mediumaside', 'sectioned', 'ciniki.musicfestivals.main.messagelist');
    this.messagelist.data = {};
    this.messagelist.festival_id = 0;
    this.messagelist.message_id = 0;
    this.messagelist.nplist = [];
    this.messagelist.sections = {
        'details':{'label':'Details', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label mediumlabel', ''],
            },
        '_subject':{'label':'Subject', 'fields':{
            'subject':{'label':'Subject', 'hidelabel':'yes', 'type':'text', 'editable':'no'},
            }},
        'emails_html':{'label':'Emails', 'type':'html'}, 
        };
    this.messagelist.cellValue = function(s, i, j, d) {
        if( s == 'details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
    }
    this.messagelist.open = function(cb, mid, fid, list) {
        if( mid != null ) { this.message_id = mid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.messageGet', {'tnid':M.curTenantID, 'message_id':this.message_id, 'emaillist':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.messagelist;
            p.data = rsp.message;
            p.data.emails_html = '';
            var comma = '';
            for(var i in p.data.emails) {
                p.data.emails_html += comma + p.data.emails[i].name + ' &lt;' + p.data.emails[i].email + '&gt;';
                comma = ',<br/>';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.messagelist.addClose('Back');

    //
    // 
    //
    this.member = new M.panel('Member Festival',
        'ciniki_musicfestivals_main', 'member',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.main.member');
    this.member.data = {};
    this.member.festival_id = 0;
    this.member.member_id = 0;
//    this.member.customer_id = 0;
    this.member.nplist = [];
    this.member.sections = {
        'general':{'label':'Member Festival', 'aside':'left', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'shortname':{'label':'Short Name', 'type':'text'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{
                '10':'Active',
                '70':'Closed',
                '90':'Archive',
                }},
            'reg_start_dt':{'label':'Reg Start', 'type':'datetime'},
            'reg_end_dt':{'label':'Reg End', 'type':'datetime'},
            'latedays':{'label':'Late Days', 'type':'number', 'size':'small'},
            }},
        'customers':{'label':'Admin Accounts', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'noData':'No Admin Accounts',
            'deleteFn':function(s, i, d) {
                return 'M.ciniki_musicfestivals_main.member.removeCustomer(' + d.id + ');';
                },
            'menu':{
                'add': {
                    'label':'Add Admin',
                    'fn':'M.ciniki_musicfestivals_main.member.save("M.ciniki_musicfestivals_main.member.openCustomer();");',
                    }
                },
            },
//        'customer_details':{'label':'Admin Account', 'type':'customer', 'num_cols':2, 'aside':'yes',
//            'customer_id':0,
//            'customer_field':'customer_id',
//            'cellClasses':['label', ''],
//            'noData':'No Admin Account',
//            },
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.member.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.member.member_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.member.remove();'},
            }},
        };
    this.member.liveSearchCb = function(s, i, v) {
        if( i == 'category' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.memberFieldSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_musicfestivals_main.member.liveSearchShow(s,i,M.gE(M.ciniki_musicfestivals_main.member.panelUID + '_' + i), rsp.results);
                });
        }
    }
    this.member.liveSearchResultValue = function(s, f, i, j, d) {
        return d.value;
    }
    this.member.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_musicfestivals_main.member.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.value) + '\');';
    }
    this.member.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.member.fieldValue = function(s, i, d) { return this.data[i]; }
    this.member.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.memberHistory', 'args':{'tnid':M.curTenantID, 'festival_id':this.festival_id, 'member_id':this.member_id, 'field':i}};
    }
    this.member.cellValue = function(s, i, j, d) {
        if( s == 'customers' ) {
            return d.display_name;
        }
/*        if( s == 'customer_details' && j == 0 ) { return d.label; }
        if( s == 'customer_details' && j == 1 ) {
            if( d.label == 'Email' ) {
                return M.linkEmail(d.value);
            } else if( d.label == 'Address' ) {
                return d.value.replace(/\n/g, '<br/>');
            }
            return d.value;
        } */
    };
    this.member.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_main.member.save("M.ciniki_musicfestivals_main.member.editCustomer(' + d.customer_id + ');");';
    }
    this.member.editCustomer = function(cid) {
        M.startApp('ciniki.customers.edit',null,'M.ciniki_musicfestivals_main.member.open();','mc',{'customer_id':cid});
    }
    this.member.openCustomer = function() {
        this.popupMenuClose('customers');
        M.startApp('ciniki.customers.edit',null,'M.ciniki_musicfestivals_main.member.addCustomer(0);','mc',{'next':'M.ciniki_musicfestivals_main.member.addCustomer', 'customer_id':0});
    }
    this.member.addCustomer = function(cid) {
        if( cid != null && cid > 0 ) {
            M.api.getJSONCb('ciniki.musicfestivals.memberCustomerAdd', {'tnid':M.curTenantID, 'member_id':this.member_id, 'customer_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.member.open();
            });
        }
        this.show();
    }
    this.member.removeCustomer = function(mcid) {
        M.confirm('Are you sure you want to remove the admin?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.memberCustomerDelete', {'tnid':M.curTenantID, 'membercustomer_id':mcid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.member.open();
            });
        });
    }
    this.member.open = function(cb, mid, fid, list) {
        if( mid != null ) { this.member_id = mid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.memberGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'member_id':this.member_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.member;
            p.data = rsp.member;
//            p.sections.customer_details.customer_id = rsp.member.customer_id;
            p.refresh();
            p.show(cb);
        });
    }
    this.member.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.member.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.member_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.memberUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'member_id':this.member_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.memberAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.member.member_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.member.remove = function() {
        M.confirm('Are you sure you want to remove member?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.memberDelete', {'tnid':M.curTenantID, 'member_id':M.ciniki_musicfestivals_main.member.member_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.member.close();
            });
        });
    }
    this.member.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.member_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.member.save(\'M.ciniki_musicfestivals_main.member.open(null,' + this.nplist[this.nplist.indexOf('' + this.member_id) + 1] + ');\');';
        }
        return null;
    }
    this.member.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.member_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.member.save(\'M.ciniki_musicfestivals_main.member.open(null,' + this.nplist[this.nplist.indexOf('' + this.member_id) - 1] + ');\');';
        }
        return null;
    }
    this.member.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.member.save();');
    this.member.addClose('Cancel');
    this.member.addButton('next', 'Next');
    this.member.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Adjudicator Recommendation
    //
    this.recommendation = new M.panel('Adjudicator Submission', 'ciniki_musicfestivals_main', 'recommendation', 'mc', 'large mediumaside', 'sectioned', 'ciniki.musicfestivals.main.recommendation');
    this.recommendation.data = null;
    this.recommendation.recommendation_id = 0;
    this.recommendation.member_id = 0;
    this.recommendation.section_id = 0;
    this.recommendation.nplist = [];
    this.recommendation.sections = {
        'details':{'label':'Submission', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            },
        'adjudicator':{'label':'Adjudicator', 'aside':'yes', 'fields':{
            'adjudicator_name':{'label':'Name', 'required':'yes', 'type':'text'},
            'adjudicator_phone':{'label':'Phone', 'type':'text'},
            'adjudicator_email':{'label':'Email', 'type':'text'},
            }},
        'entries':{'label':'Entries', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['Class', 'Name', 'Position', 'Mark'],
            'menu':{
                'add':{
                    'label':'Add Entry',
                    'fn':'M.ciniki_musicfestivals_main.recommendationentry.open(\'M.ciniki_musicfestivals_main.recommendation.open();\',0,M.ciniki_musicfestivals_main.recommendation.section_id);',
                    },
                },
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.recommendation.save();'},
            'excel':{'label':'Download Excel', 
                'visible':function() {return M.ciniki_musicfestivals_main.recommendation.recommendation_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.recommendation.downloadExcel();'},
            'delete':{'label':'Delete Submission & Entries', 
                'visible':function() {return M.ciniki_musicfestivals_main.recommendation.recommendation_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.recommendation.remove();'},
            }},
        };
    this.recommendation.fieldValue = function(s, i, d) { return this.data[i]; }
    this.recommendation.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.recommendationHistory', 'args':{'tnid':M.curTenantID, 'recommendation_id':this.recommendation_id, 'field':i}};
    }
    this.recommendation.cellValue = function(s, i, j, d) {
        if( s == 'details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'entries' ) {
            switch(j) {
                case 0: return d.class_code + ' - ' + d.class_name;
                case 1: return d.name;
                case 2: return d.position;
                case 3: return d.mark;
            }
        }
    }
    this.recommendation.rowClass = function(s, i, d) {
        if( s == 'entries' ) {
            switch(d.status) {
                case '10': 
                    if( d.position == '1st Alternate' || d.position == '2nd Alternate' || d.position == '3rd Alternate' ) {
                        return 'statusyellow';
                    }
                    return '';
                case '30': return 'statusorange';
                case '50': return 'statusgreen';
                case '70': return 'statusred';
                case '90': return 'statusred';
            }
        }
    }
    this.recommendation.rowFn = function(s, i, d) {
        if( s == 'entries' ) {
            return 'M.ciniki_musicfestivals_main.recommendation.save("M.ciniki_musicfestivals_main.recommendationentry.open(\'M.ciniki_musicfestivals_main.recommendation.open();\',\'' + d.id + '\',\'' + this.section_id + '\');");';
        }
        return '';
    }
    this.recommendation.downloadExcel = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
            'recommendation_id':this.recommendation_id,
            };
        M.api.openFile('ciniki.musicfestivals.recommendationsExcel',args);
    }
    this.recommendation.open = function(cb, rid, list) {
        if( rid != null ) { this.recommendation_id = rid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.recommendationGet', {'tnid':M.curTenantID, 'recommendation_id':this.recommendation_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.recommendation;
            p.data = rsp.recommendation;
            p.member_id = rsp.recommendation.member_id;
            p.section_id = rsp.recommendation.section_id;
            p.refresh();
            p.show(cb);
        });
    }
    this.recommendation.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.recommendation.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.recommendation_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.recommendationUpdate', {'tnid':M.curTenantID, 'recommendation_id':this.recommendation_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.recommendationAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.recommendation.recommendation_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.recommendation.remove = function() {
        M.confirm('Are you sure you want to remove this submission and all the submission entries?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.recommendationDelete', {'tnid':M.curTenantID, 'recommendation_id':M.ciniki_musicfestivals_main.recommendation.recommendation_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.recommendation.close();
            });
        });
    }
    this.recommendation.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.recommendation_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.recommendation.save(\'M.ciniki_musicfestivals_main.recommendation.open(null,' + this.nplist[this.nplist.indexOf('' + this.recommendation_id) + 1] + ');\');';
        }
        return null;
    }
    this.recommendation.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.recommendation_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.recommendation.save(\'M.ciniki_musicfestivals_main.recommendation.open(null,' + this.nplist[this.nplist.indexOf('' + this.recommendation_id) - 1] + ');\');';
        }
        return null;
    }
    this.recommendation.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.recommendation.save();');
    this.recommendation.addClose('Cancel');
    this.recommendation.addButton('next', 'Next');
    this.recommendation.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Adjudicator Recommendation Entry
    //
    this.recommendationentry = new M.panel('Adjudicator Entry', 'ciniki_musicfestivals_main', 'recommendationentry', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.recommendationentry');
    this.recommendationentry.data = null;
    this.recommendationentry.entry_id = 0;
    this.recommendationentry.member_id = 0;
    this.recommendationentry.nplist = [];
    this.recommendationentry.sections = {
        'general':{'label':'', 'fields':{
// Note: This was added by mistake, can be added back if really needs to change from one submission to another
//            'recommendation_id':{'label':'Submission', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{
                '10':'Recommended',
                '30':'Accepted',
                '50':'Registered',
                '70':'Turned Down',
                '90':'Expired',
                }},
            'class_id':{'label':'Class', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'position':{'label':'Position', 'required':'yes', 'type':'toggle', 'toggles':{
                '1':'1st',
                '2':'2nd',
                '3':'3rd',
                '4':'4th',
                '101':'1st Alt',
                '102':'2nd Alt',
                '103':'3rd Alt',
                }},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'mark':{'label':'Mark', 'required':'yes', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.recommendationentry.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_main.recommendationentry.entry_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_main.recommendationentry.remove();'},
            }},
        };
    this.recommendationentry.fieldValue = function(s, i, d) { return this.data[i]; }
    this.recommendationentry.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.musicfestivals.recommendationEntryHistory', 'args':{'tnid':M.curTenantID, 'entry_id':this.entry_id, 'field':i}};
    }
    this.recommendationentry.open = function(cb, eid, sid, list) {
        if( eid != null ) { this.entry_id = eid; }
        if( sid != null ) { this.section_id = sid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.recommendationEntryGet', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_main.festival.festival_id, 'entry_id':this.entry_id, 'section_id':this.section_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.recommendationentry;
            p.data = rsp.entry;
            p.sections.general.fields.class_id.options = rsp.classes;
            p.refresh();
            p.show(cb);
        });
    }
    this.recommendationentry.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_main.recommendationentry.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.entry_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.recommendationEntryUpdate', {'tnid':M.curTenantID, 'entry_id':this.entry_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.recommendationEntryAdd', {'tnid':M.curTenantID, 'recommendation_id':M.ciniki_musicfestivals_main.recommendation.recommendation_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.recommendationentry.entry_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.recommendationentry.remove = function() {
        M.confirm('Are you sure you want to remove this entry?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.recommendationEntryDelete', {'tnid':M.curTenantID, 'entry_id':M.ciniki_musicfestivals_main.recommendationentry.entry_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.recommendationentry.close();
            });
        });
    }
    this.recommendationentry.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.entry_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_main.recommendationentry.save(\'M.ciniki_musicfestivals_main.recommendationentry.open(null,' + this.nplist[this.nplist.indexOf('' + this.entry_id) + 1] + ');\');';
        }
        return null;
    }
    this.recommendationentry.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.entry_id) > 0 ) {
            return 'M.ciniki_musicfestivals_main.recommendationentry.save(\'M.ciniki_musicfestivals_main.recommendationentry.open(null,' + this.nplist[this.nplist.indexOf('' + this.entry_id) - 1] + ');\');';
        }
        return null;
    }
    this.recommendationentry.addButton('save', 'Save', 'M.ciniki_musicfestivals_main.recommendationentry.save();');
    this.recommendationentry.addClose('Cancel');
    this.recommendationentry.addButton('next', 'Next');
    this.recommendationentry.addLeftButton('prev', 'Prev');

    //
    // Window to select the new Mark, Placement, Level for the festival or section
    //
    this.marking = new M.panel('Update Marking', 'ciniki_musicfestivals_main', 'marking', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.marking');
    this.marking.data = {};
    this.marking.section_id = 0;
    this.marking.nplist = [];
    this.marking.sections = {
        'general':{'label':'New Settings', 'fields':{
            'flags9':{'label':'Marking', 'type':'flagspiece', 'mask':0x0700, 'field':'flags', 'join':'yes', 'none':'yes',
                'flags':{'9':{'name':'Mark'}, '10':{'name':'Placement'}, '11':{'name':'Level'}},
                },
            }},
        '_buttons':{'label':'', 'buttons':{
            'update':{'label':'Apply to Classes', 'fn':'M.ciniki_musicfestivals_main.marking.save();'},
            'delete':{'label':'Cancel', 
                'fn':'M.ciniki_musicfestivals_main.marking.close();'},
            }},
    }
    this.marking.open = function(cb, sid) {
        this.section_id = sid;
        this.section_name = '';
        if( sid > 0 ) {
            for(var i in M.ciniki_musicfestivals_main.festival.data.sections) {
                if( M.ciniki_musicfestivals_main.festival.data.sections[i].id == sid ) {
                    this.section_name = M.ciniki_musicfestivals_main.festival.data.sections[i].name; 
                    break;
                }
            }
            this.sections._buttons.buttons.update.label = 'Apply to ' + this.section_name + ' Classes';
        } else {
            this.sections._buttons.buttons.update.label = 'Apply to All Classes';
        }
        this.refresh();
        this.show(cb);
    }
    this.marking.save = function() {
        var newflags = this.formValue('flags9');
        var question = "Are you sure you want to update Mark, Placement and Level on all Classes?";
        if( this.section_id > 0 ) {
            var question = "Are you sure you want to update Mark, Placement and Level on " + this.section_name + " Classes?";
        }
        M.confirm(question, "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'section_id':M.ciniki_musicfestivals_main.marking.section_id,
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'marking':newflags,
                }; 
            M.api.getJSONCb('ciniki.musicfestivals.sectionClassesUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.festival.open();
                });
        });
    }
    this.marking.addClose('Cancel');

    //
    // Window to select the new Mark, Placement, Level for the festival or section
    //
    this.adminfees = new M.panel('Update Admin Fees', 'ciniki_musicfestivals_main', 'adminfees', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.adminfees');
    this.adminfees.data = {};
    this.adminfees.syllabus = '';
    this.adminfees.nplist = [];
    this.adminfees.sections = {
        'general':{'label':'New Admin Fees', 'fields':{
            'flags7':{'label':'Admin Fees', 'type':'flagspiece', 'mask':0xC0, 'field':'flags', 'join':'yes', 'none':'yes', 'toggle':'yes',
                'onchange':'M.ciniki_musicfestivals_main.adminfees.updateForm',
                'flags':{'0':{'name':'None'}, '7':{'name':'per Cart'}},// **future**, '8':{'name':'per Registration'}},
                },
            'adminfees_amount':{'label':'Admin Fee Amount', 'type':'text', 'size':'small', 'visible':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'update':{'label':'Apply to Sections', 'fn':'M.ciniki_musicfestivals_main.adminfees.save();'},
            'delete':{'label':'Cancel', 
                'fn':'M.ciniki_musicfestivals_main.adminfees.close();'},
            }},
    }
    this.adminfees.open = function(cb, syllabus) {
        this.syllabus = syllabus;
        this.refresh();
        this.show(cb);
        this.updateForm();
    }
    this.adminfees.updateForm = function() {
        var f = this.formValue('flags7');
        if( (f&0xC0) > 0 ) {
            this.sections.general.fields.adminfees_amount.visible = 'yes';
        } else {
            this.sections.general.fields.adminfees_amount.visible = 'no';
        }
/*        var f = this.formValue('flags5');
        if( (f&0x30) > 0 ) {
            this.sections.general.fields.latefees_start_amount.visible = 'yes';
            this.sections.general.fields.latefees_daily_increase.visible = 'yes';
            this.sections.general.fields.latefees_days.visible = 'yes';
        } else {
            this.sections.general.fields.latefees_start_amount.visible = 'no';
            this.sections.general.fields.latefees_daily_increase.visible = 'no';
            this.sections.general.fields.latefees_days.visible = 'no';
        }
        this.showHideFormField('general', 'latefees_start_amount');
        this.showHideFormField('general', 'latefees_daily_increase');
        this.showHideFormField('general', 'latefees_days'); */
        this.showHideFormField('general', 'adminfees_amount');
    }
    this.adminfees.save = function() {
        var newflags = this.formValue('flags7');
        var amount = this.formValue('adminfees_amount');
        var question = "Are you sure you want to update Admin Fees on all Sections?";
        M.confirm(question, "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'syllabus':M.ciniki_musicfestivals_main.adminfees.syllabus,
                'adminfees_flags':newflags,
                'adminfees_amount':amount,
                }; 
            M.api.getJSONCb('ciniki.musicfestivals.sectionsUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.adminfees.close();
                });
        });
    }
    this.adminfees.addClose('Cancel');

    //
    // Window to set the late fees for all sections in a syllabus
    //
    this.latefees = new M.panel('Update Late Fees', 'ciniki_musicfestivals_main', 'latefees', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.latefees');
    this.latefees.data = {};
    this.latefees.syllabus = '';
    this.latefees.nplist = [];
    this.latefees.sections = {
        'general':{'label':'New Late Fees', 'fields':{
            'flags5':{'label':'Late Fees', 'type':'flagspiece', 'mask':0x30, 'field':'flags', 'join':'yes', 'none':'yes', 'toggle':'yes',
                'onchange':'M.ciniki_musicfestivals_main.latefees.updateForm',
                'flags':{'0':{'name':'None'}, '5':{'name':'per Cart'}, '6':{'name':'per Registration'}},
                },
            'latefees_start_amount':{'label':'First Day Amount', 'type':'text', 'size':'small', 'visible':'no'},
            'latefees_daily_increase':{'label':'Daily Increase', 'type':'text', 'size':'small', 'visible':'no'},
            'latefees_days':{'label':'Number of Days', 'type':'text', 'size':'small', 'visible':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'update':{'label':'Apply to Sections', 'fn':'M.ciniki_musicfestivals_main.latefees.save();'},
            'delete':{'label':'Cancel', 
                'fn':'M.ciniki_musicfestivals_main.latefees.close();'},
            }},
    }
    this.latefees.open = function(cb, syllabus) {
        console.log(this.sections);
        this.syllabus = syllabus;
        this.refresh();
        this.show(cb);
        this.updateForm();
    }
    this.latefees.updateForm = function() {
        var f = this.formValue('flags5');
        if( (f&0x30) > 0 ) {
            this.sections.general.fields.latefees_start_amount.visible = 'yes';
            this.sections.general.fields.latefees_daily_increase.visible = 'yes';
            this.sections.general.fields.latefees_days.visible = 'yes';
        } else {
            this.sections.general.fields.latefees_start_amount.visible = 'no';
            this.sections.general.fields.latefees_daily_increase.visible = 'no';
            this.sections.general.fields.latefees_days.visible = 'no';
        }
        this.showHideFormField('general', 'latefees_start_amount');
        this.showHideFormField('general', 'latefees_daily_increase');
        this.showHideFormField('general', 'latefees_days'); 
    }
    this.latefees.save = function() {
        var newflags = this.formValue('flags5');
        var start_amount = this.formValue('latefees_start_amount');
        var daily_increase = this.formValue('latefees_daily_increase');
        var days = this.formValue('latefees_days');
        var question = "Are you sure you want to update Late Fees on all Sections?";
        M.confirm(question, "Confirm", function(rsp) {
            var args = {
                'tnid':M.curTenantID, 
                'festival_id':M.ciniki_musicfestivals_main.festival.festival_id,
                'syllabus':M.ciniki_musicfestivals_main.latefees.syllabus,
                'latefees_flags':newflags,
                'latefees_start_amount':start_amount,
                'latefees_daily_increase':daily_increase,
                'latefees_days':days,
                }; 
            M.api.getJSONCb('ciniki.musicfestivals.sectionsUpdate', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.latefees.close();
                });
        });
    }
    this.latefees.addClose('Cancel');

    //
    // ssam section
    //
    this.ssamsection = new M.panel('SSAM Section', 'ciniki_musicfestivals_main', 'ssamsection', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.ssamsection');
    this.ssamsection.data = {};
    this.ssamsection.section_id = 0;
    this.ssamsection.section_name = '';
    this.ssamsection.nplist = [];
    this.ssamsection.sections = {
        'general':{'label':'Section', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'content':{'label':'Content', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.ssamsection.save();'},
            'delete':{'label':'Delete', 
                'fn':'M.ciniki_musicfestivals_main.ssamsection.remove();'},
            }},
    }
    this.ssamsection.open = function(cb,section,fid) {
        this.section_name = section;
        if( fid != null ) { this.festival_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.ssamSectionGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_name':this.section_name}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.ssamsection;
            p.data = rsp.section;
            p.refresh();
            p.show(cb);
        });
    }
    this.ssamsection.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.musicfestivals.ssamSectionUpdate', {'tnid':M.curTenantID, 'section_name':this.section_name, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.ssamsection.close();
            });
        } else {
            M.ciniki_musicfestivals_main.ssamsection.close();
        }
    }
    this.ssamsection.remove = function() {
        M.confirm('Are you sure you want to remove this section and all the categories and items within the section?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.ssamSectionDelete', {'tnid':M.curTenantID, 'section_name':M.ciniki_musicfestivals_main.ssamsection.section_name, 'festival_id':M.ciniki_musicfestivals_main.ssamsection.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.ssamsection.close();
            });
        });
    }
    this.ssamsection.addClose('Cancel');

    //
    // ssam category
    //
    this.ssamcategory = new M.panel('SSAM Category', 'ciniki_musicfestivals_main', 'ssamcategory', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.ssamcategory');
    this.ssamcategory.data = {};
    this.ssamcategory.section_id = 0;
    this.ssamcategory.section_name = '';
    this.ssamcategory.category_name = '';
    this.ssamcategory.nplist = [];
    this.ssamcategory.sections = {
        'general':{'label':'Category', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.ssamcategory.save();'},
            'delete':{'label':'Delete', 
                'fn':'M.ciniki_musicfestivals_main.ssamcategory.remove();'},
            }},
    }
    this.ssamcategory.open = function(cb,section,category,fid) {
        this.section_name = section;
        this.category_name = category;
        if( fid != null ) { this.festival_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.ssamCategoryGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_name':this.section_name, 'category_name':this.category_name}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.ssamcategory;
            p.data = rsp.category;
            p.refresh();
            p.show(cb);
        });
    }
    this.ssamcategory.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.musicfestivals.ssamCategoryUpdate', {'tnid':M.curTenantID, 'section_name':this.section_name, 'category_name':this.category_name, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.ssamcategory.close();
            });
        } else {
            M.ciniki_musicfestivals_main.ssamcategory.close();
        }
    }
    this.ssamcategory.remove = function() {
        M.confirm('Are you sure you want to remove this category and all the items within the category?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.ssamCategoryDelete', {'tnid':M.curTenantID, 'section_name':M.ciniki_musicfestivals_main.ssamcategory.section_name, 'category_name':M.ciniki_musicfestivals_main.ssamcategory.category_name, 'festival_id':M.ciniki_musicfestivals_main.ssamcategory.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.ssamcategory.close();
            });
        });
    }
    this.ssamcategory.addClose('Cancel');

    //
    // ssam item
    //
    this.ssamitem = new M.panel('SSAM Item', 'ciniki_musicfestivals_main', 'ssamitem', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.main.ssamitem');
    this.ssamitem.data = {};
    this.ssamitem.section_id = 0;
    this.ssamitem.section_name = '';
    this.ssamitem.category_name = '';
    this.ssamitem.item_name = '';
    this.ssamitem.nplist = [];
    this.ssamitem.sections = {
        'general':{'label':'Movie/Show', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'song1':{'label':'Song 1', 'type':'text'},
            'song2':{'label':'Song 2', 'type':'text'},
            'song3':{'label':'Song 3', 'type':'text'},
            'song4':{'label':'Song 4', 'type':'text'},
            'song5':{'label':'Song 5', 'type':'text'},
            'song6':{'label':'Song 6', 'type':'text'},
            'song7':{'label':'Song 7', 'type':'text'},
            'song8':{'label':'Song 8', 'type':'text'},
            'song9':{'label':'Song 9', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_main.ssamitem.save();'},
            'delete':{'label':'Delete', 
                'fn':'M.ciniki_musicfestivals_main.ssamitem.remove();'},
            }},
    }
    this.ssamitem.open = function(cb,section,category,item,fid) {
        this.section_name = section;
        this.category_name = category;
        this.item_name = item;
        if( fid != null ) { this.festival_id = fid; }
        M.api.getJSONCb('ciniki.musicfestivals.ssamItemGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_name':this.section_name, 'category_name':this.category_name, 'item_name':this.item_name}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_main.ssamitem;
            p.data = rsp.item;
            p.refresh();
            p.show(cb);
        });
    }
    this.ssamitem.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.musicfestivals.ssamItemUpdate', {'tnid':M.curTenantID, 'section_name':this.section_name, 'category_name':this.category_name, 'item_name':this.item_name, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.ssamitem.close();
            });
        } else {
            M.ciniki_musicfestivals_main.ssamitem.close();
        }
    }
    this.ssamitem.remove = function() {
        M.confirm('Are you sure you want to remove this item?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.ssamItemDelete', {'tnid':M.curTenantID, 'section_name':M.ciniki_musicfestivals_main.ssamitem.section_name, 'category_name':M.ciniki_musicfestivals_main.ssamitem.category_name, 'item_name':M.ciniki_musicfestivals_main.ssamitem.item_name, 'festival_id':M.ciniki_musicfestivals_main.ssamitem.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_main.ssamitem.close();
            });
        });
    }
    this.ssamitem.addClose('Cancel');

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

        //
        // Initialize for tenant
        //
        if( this.curTenantID == null || this.curTenantID != M.curTenantID ) {
            this.tenantInit();
            this.curTenantID = M.curTenantID;
        }

        if( args.item_object != null && args.item_object == 'ciniki.musicfestivals.registration' && args.item_object_id != null ) {
            this.registration.open(cb, args.item_object_id, 0, 0, 0, null, args.source);
        } else if( args.registration_id != null && args.registration_id != '' ) {
            this.registration.open(cb, args.registration_id, 0, 0, 0, null, '');
        } else if( args.festival_id != null && args.festival_id != '' ) {
            this.festival.list_id = 0;
            this.festival.open(cb, args.festival_id, null);
        } else if( args.trophies != null && args.trophies == 1 ) {
            this.festival.list_id = 0;
            this.menu.sections._tabs.selected = 'trophies';
            this.menu.open(cb);
        } else {
            this.festival.list_id = 0;
            this.menu.sections._tabs.selected = 'festivals';
            this.menu.open(cb);
        }
    }

    this.placementAutofill = function(m) {
        m = parseInt(m);
        var p = '';
        if( this.festival.data['comments-placement-autofills'] != null ) {
            for(var i in this.festival.data['comments-placement-autofills']) {
                if( m >= parseInt(i) ) {
                    p = this.festival.data['comments-placement-autofills'][i];
                }
            }
        }
        return p;
    }
    this.levelAutofill = function(m) {
        m = parseInt(m);
        var l = '';
        if( this.festival.data['comments-level-autofills'] != null ) {
            for(var i in this.festival.data['comments-level-autofills']) {
                if( m >= parseInt(i) ) {
                    l = this.festival.data['comments-level-autofills'][i];
                }
            }
        }
        return l;
    }

    this.regStatusColour = function(festival, reg) {
        if( reg.status != null && festival['registration-status-' + reg.status + '-colour'] != null ) {
//            if( festival['registration-status-' + reg.status + '-colour'] != '#ffffff' ) {
//                return 'background: ' + festival['registration-status-' + reg.status + '-colour'] + '; color: #000;';
//            } 
            return 'background: ' + festival['registration-status-' + reg.status + '-colour'] + ';';
        }
        return '';
    }

    this.tenantInit = function() {
        this.menu.sections.trophy_types.selected = 'All';
        this.menu.sections.trophy_categories.selected = 'All';
        this.festival.typestatus = '';
        this.festival.menutabs.selected = 'syllabus';
        this.festival.sections.ipv_tabs.selected = 'all';
        this.festival.sections.syllabi_tabs.selected = null;
        this.festival.sections.syllabi_tabs.tabs = {};
        this.section.sections._tabs.selected = 'categories';
        this.classes.sections._tabs.selected = 'fees';
        this.festival.colour = 'white';
        this.festival.section_id = -1;
        this.festival.groupname = 'all';
        this.festival.category_id = 0;
        this.festival.schedulesection_id = 0;
        this.festival.scheduledivision_id = 0;
        this.festival.accompanist_customer_id = 0;
        this.festival.list_id = 0;
        this.festival.listsection_id = 0;
        this.festival.nplists = {};
        this.festival.nplist = [];
        this.festival.messages_status = 10;
        this.festival.city_prov = 'All';
        this.festival.province = 'All';
        this.festival.registration_tag = '';
        this.festival.sections.sbuttons2.label = 'Current Section Downloads';
        this.festival.sections.stats_tabs.selected = 'cities';
        this.festival.sections.ssam_sections.selected = '';
        this.festival.sections.ssam_categories.selected = '';
        this.scheduletimeslot.section_id = 0;
        this.scheduletimeslot.category_id = 0;
        this.scheduledivisions.section_id = 0;
        this.scheduledivisions.class_id = 0;
        this.scheduledivisions.division_ids = [];
        this.scheduledivisions.participation = 'all';
        if( M.modFlagOn('ciniki.musicfestivals', 0x8000) ) {
            this.messagerefs.sections.excluded.fields.flags1.flags = {
                '1':{'name':'Competitors'}, 
                '2':{'name':'Teachers'}, 
                '3':{'name':'Accompanists'}
                };
        } else {
            this.messagerefs.sections.excluded.fields.flags1.flags = {
                '1':{'name':'Competitors'}, 
                '2':{'name':'Teachers'}
                };
        }
    }
}
