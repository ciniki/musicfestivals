//
// This is the photos app for the musicfestivals module
//
function ciniki_musicfestivals_photos() {
    //
    // The panel to list the divisions
    //
    this.menu = new M.panel('Music Festivals Photos', 'ciniki_musicfestivals_photos', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.photos.menu');
    this.menu.festival_id = 0;
    this.menu.data = {};
    this.menu.sections = {
        'divisions':{'label':'Schedule', 'type':'simplegrid', 'num_cols':2,
            'noData':'No Schedule',
            'cellClasses':['', ''],
            },
    }
    this.menu.cellValue = function(s, i, j, d) {
//        return d.division_date + ' - ' + d.section_name + ' - ' + d.division_name;
        switch(j) {
            case 0: return d.division_date;
            case 1: return d.section_name + ' - ' + d.division_name;
        }
    }
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_musicfestivals_photos.timeslots.open(\'M.ciniki_musicfestivals_photos.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.open = function(cb, fid) {
        if( fid != null ) { this.festival_id = fid; this.division_id = 0; }
        M.api.getJSONCb('ciniki.musicfestivals.photos', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_photos.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to list the timeslots
    //
    this.timeslots = new M.panel('Music Festivals Photos', 'ciniki_musicfestivals_photos', 'timeslots', 'mc', 'medium', 'sectioned', 'ciniki.musicfestivals.photos.timeslots');
    this.timeslots.division_id = 0;
    this.timeslots.data = {};
    this.timeslots.sections = {
        'timeslots':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':3,
            'cellClasses':['multiline', 'thumbnails', 'alignright fabuttons'],
            'addDropImage':function(iid, i) {
                var row = M.ciniki_musicfestivals_photos.festival.data.timeslot_photos[i];
                M.api.getJSONCb('ciniki.musicfestivals.timeslotImageAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 
                    'timeslot_id':row.id, 'image_id':iid},
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_musicfestivals_photos.timeslots;
                        var t = M.gE(p.panelUID + '_timeslots_grid');
                        var cell = t.children[0].children[i].children[1];
                        cell.innerHTML += '<img class="clickable" onclick="M.ciniki_musicfestivals_photos.timeslotimage.open(\'M.ciniki_musicfestivals_photos.timeslots.open();\',\'' + rsp.id + '\');" width="50px" height="50px" src=\'' + rsp.image + '\' />';
                    });
                return true;
                },
            },
    }
    this.timeslots.cellValue = function(s, i, j, d) {
        if( j == 1 && d.images != null && d.images.length > 0 ) {
            var thumbs = '';
            for(var k in d.images) {
                thumbs += '<img class="clickable" onclick="M.ciniki_musicfestivals_photos.timeslotimage.open(\'M.ciniki_musicfestivals_photos.timeslots.open();\',\'' + d.images[k].timeslot_image_id + '\');" width="50px" height="50px" src=\'' + d.images[k].image + '\' />';
            }
            return thumbs;
        }
        switch(j) {
            case 0: return M.multiline(d.slot_time_text, d.name);
            case 1: return '';
            case 2: return M.faBtn('&#xf030;', 'Photos', 'M.ciniki_musicfestivals_photos.timeslots.timeslotImageAdd(' + d.id + ',' + i + ');');
        }
    }
    this.timeslots.open = function(cb, did) {
        if( did != null ) { this.division_id = did; }
        M.api.getJSONCb('ciniki.musicfestivals.photos', {'tnid':M.curTenantID, 'festival_id':M.ciniki_musicfestivals_photos.menu.festival_id, 'division_id':this.division_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_photos.timeslots;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.timeslots.timeslotImageAdd = function(tid, row) {
        this.timeslot_image_uploader_tid = tid;
        this.timeslot_image_uploader_row = row;
        this.image_uploader = M.aE('input', this.panelUID + '_' + tid + '_upload', 'file_uploader');
        this.image_uploader.setAttribute('name', tid);
        this.image_uploader.setAttribute('type', 'file');
        this.image_uploader.setAttribute('onchange', 'M.ciniki_musicfestivals_photos.timeslots.timeslotImageUpload();');
        this.image_uploader.click();
    }
    this.timeslots.timeslotImageUpload = function() {
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
                var p = M.ciniki_musicfestivals_photos.timeslots;
                var t = M.gE(p.panelUID + '_timeslots_grid');
                var cell = t.children[0].children[p.timeslot_image_uploader_row].children[1];
                cell.innerHTML += '<img class="clickable" onclick="M.ciniki_musicfestivals_photos.timeslotimage.open(\'M.ciniki_musicfestivals_photos.timeslots.open();\',\'' + rsp.id + '\');" width="50px" height="50px" src=\'' + rsp.image + '\' />';
            });
    }
    this.timeslots.addClose('Back');

    //
    // The panel to edit Schedule Time Slot Image
    //
    this.timeslotimage = new M.panel('Schedule Time Slot Image', 'ciniki_musicfestivals_photos', 'timeslotimage', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.musicfestivals.photos.timeslotimage');
    this.timeslotimage.data = null;
    this.timeslotimage.timeslot_image_id = 0;
    this.timeslotimage.nplist = [];
    this.timeslotimage.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_musicfestivals_photos.timeslotimage.setFieldValue('image_id', iid);
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
            'save':{'label':'Save', 'fn':'M.ciniki_musicfestivals_photos.timeslotimage.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_musicfestivals_photos.timeslotimage.timeslot_image_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_musicfestivals_photos.timeslotimage.remove();'},
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
            var p = M.ciniki_musicfestivals_photos.timeslotimage;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    }
    this.timeslotimage.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_musicfestivals_photos.timeslotimage.close();'; }
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
                M.ciniki_musicfestivals_photos.timeslotimage.timeslot_image_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.timeslotimage.remove = function() {
        M.confirm('Are you sure you want to remove timeslotimage?', null, function(rsp) {
            M.api.getJSONCb('ciniki.musicfestivals.timeslotImageDelete', {'tnid':M.curTenantID, 'timeslot_image_id':M.ciniki_musicfestivals_photos.timeslotimage.timeslot_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_musicfestivals_photos.timeslotimage.close();
            });
        });
    }
    this.timeslotimage.addButton('save', 'Save', 'M.ciniki_musicfestivals_photos.timeslotimage.save();');
    this.timeslotimage.addClose('Cancel');

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
        var ac = M.createContainer(ap, 'ciniki_musicfestivals_photos', 'yes');
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

        this.menu.open(cb, args.festival_id);
    }

    this.tenantInit = function() {
        this.menu.festival_id = 0;
        this.menu.division_id = 0;
    }
}
