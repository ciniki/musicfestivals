//
// This is the socialposts app for the musicfestivals module
//
function ciniki_musicfestivals_socialposts() {
    // // The panel to list the social posts //
    this.menu = new M.panel('Social Content', 'ciniki_musicfestivals_socialposts', 'menu', 'mc', 'full', 'sectioned', 'ciniki.musicfestivals.socialposts.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search socialpost',
//            'noData':'No socialpost found',
//            },
        'socialposts':{'label':'Social Content', 'type':'simplethumbs', 'imgsize':'xlarge',
            'noData':'No socialpost',
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.musicfestivals.socialPostSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_musicfestivals_socialposts.menu.liveSearchShow('search',null,M.gE(M.ciniki_musicfestivals_socialposts.menu.panelUID + '_' + s), rsp.socialposts);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_musicfestivals_socialposts.socialpost.open(\'M.ciniki_musicfestivals_socialposts.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'socialposts' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'socialposts' ) {
            return 'M.ciniki_musicfestivals_socialposts.socialpost.open(\'M.ciniki_musicfestivals_socialposts.menu.open();\',\'' + d.id + '\',M.ciniki_musicfestivals_socialposts.socialpost.nplist);';
        }
    }
    this.menu.thumbFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_socialposts.socialpost.open(\'M.ciniki_musicfestivals_socialposts.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.musicfestivals.socialPostList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_socialposts.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('add', 'Add', 'M.ciniki_musicfestivals_socialposts.socialpost.open(\'M.ciniki_musicfestivals_socialposts.menu.open();\',0);');
    this.menu.addClose('Back');

    //
    // The panel to edit Social Post
    //
    this.socialpost = new M.panel('Social Post', 'ciniki_musicfestivals_socialposts', 'socialpost', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.socialposts.socialpost');
    this.socialpost.data = null;
    this.socialpost.socialpost_id = 0;
    this.socialpost.nplist = [];
    this.socialpost.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_socialposts.socialpost.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'visible':'no',
            'fields':{
                'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Share with other festivals'}}},
            }},
        'info':{'label':'', 'visible':'no',
            'fields':{
                'tenant_name':{'label':'From', 'type':'text', 'editable':'no'},
                'user_display_name':{'label':'By', 'type':'text', 'editable':'no'},
            }},
        '_content':{'label':'Content', 'fields':{
            'content':{'label':'', 'editable':'no', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'editable':'no', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'visible':'no', 
            'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_socialposts.socialpost.save();'},
                'delete':{'label':'Delete', 
                    'visible':function() {return M.ciniki_musicfestivals_socialposts.socialpost.socialpost_id > 0 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_musicfestivals_socialposts.socialpost.remove();'},
            }},
        };
    this.socialpost.imageURL = function(s, i, field, img_id, nM) {
        return M.api.getBinaryURL('ciniki.musicfestivals.socialPostImage', {'tnid':M.curTenantID, 'socialpost_id':this.socialpost_id, 'image_id':img_id});
    }
    this.socialpost.fieldValue = function(s, i, d) { return this.data[i]; }
    this.socialpost.open = function(cb, sid, list) {
        if( sid != null ) { this.socialpost_id = sid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.musicfestivals.socialPostGet', {'tnid':M.curTenantID, 'socialpost_id':this.socialpost_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_socialposts.socialpost;
            p.data = rsp.socialpost;
            if( p.data.tnid == M.curTenantID ) {
                p.sections.general.visible = 'yes';
                p.sections.info.visible = 'no';
                p.sections._content.fields.content.editable = 'yes';
                p.sections._notes.fields.notes.editable = 'yes';
                p.sections._buttons.visible = 'yes';
            } else {
                p.sections.general.visible = 'no';
                p.sections.info.visible = 'yes';
                p.sections._content.fields.content.editable = 'no';
                p.sections._notes.fields.notes.editable = 'no';
                p.sections._buttons.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.socialpost.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_socialposts.socialpost.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.socialpost_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.musicfestivals.socialPostUpdate', {'tnid':M.curTenantID, 'socialpost_id':this.socialpost_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.musicfestivals.socialPostAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_socialposts.socialpost.socialpost_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.socialpost.remove = function() {
        M.confirm('Are you sure you want to remove social post?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.socialPostDelete', {'tnid':M.curTenantID, 'socialpost_id':M.ciniki_musicfestivals_socialposts.socialpost.socialpost_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_socialposts.socialpost.close();
            });
        });
    }
    this.socialpost.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.socialpost_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_musicfestivals_socialposts.socialpost.save(\'M.ciniki_musicfestivals_socialposts.socialpost.open(null,' + this.nplist[this.nplist.indexOf('' + this.socialpost_id) + 1] + ');\');';
        }
        return null;
    }
    this.socialpost.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.socialpost_id) > 0 ) {
            return 'M.ciniki_musicfestivals_socialposts.socialpost.save(\'M.ciniki_musicfestivals_socialposts.socialpost.open(null,' + this.nplist[this.nplist.indexOf('' + this.socialpost_id) - 1] + ');\');';
        }
        return null;
    }
    this.socialpost.addButton('save', 'Save', 'M.ciniki_musicfestivals_socialposts.socialpost.save();');
    this.socialpost.addClose('Cancel');
    this.socialpost.addButton('next', 'Next');
    this.socialpost.addLeftButton('prev', 'Prev');

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
        var ac = M.createContainer(ap, 'ciniki_musicfestivals_socialposts', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }

        this.menu.open(cb);
    }
}
