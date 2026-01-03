//
// This is the status app for the musicfestivals module, used only by sysadmins
//
function ciniki_musicfestivals_status() {
    this.menu = new M.panel('Music Festivals Status', 'ciniki_musicfestivals_status', 'menu', 'mc', 'full', 'sectioned', 'ciniki.musicfestivals.status.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'festivals':{'label':'Festivals', 'type':'simplegrid', 'num_cols':1,
            'sortable':'yes',
            'headerValues':[],
            'cellClasses':[],
            'headerClasses':[],
            'noData':'No active/current festivals',
            },
//        'provincials':{'label':'Provincials', 'type':'simplegrid', 'num_cols':1,
//            'sortable':'yes',
//            'headerValues':[],
//            'cellClasses':[],
//            'headerClasses':[],
//            'noData':'No active/current festivals',
//            },
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'festivals' ) {
            return d[this.sections[s].dataMaps[j]];
        }
        if( s == 'provincials' ) {
            return d[this.sections[s].dataMaps[j]];
        }
    }
    this.menu.footerValue = function(s, i, d) {
        if( s == 'festivals' && i == 13 ) {
            return this.data.totals.num_reg;
        }
        return '';
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'festivals' ) {
            return 'M.ciniki_musicfestivals_status.openFestival(' + d.tnid + ',' + d.id + ');';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.musicfestivals.sysadminStatus', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_musicfestivals_status.menu;
            p.data = rsp;
            // Main festivals list
            p.sections.festivals.headerValues = ['tnid', 'Festival', 'Status', 'Deadline', 'Start', 'End', 'Tenant', 'SMTP', 'Theme', 'Matomo', 'etrans', 'Waiver', 'Stripe', '# Reg', 'Provincials'];
            p.sections.festivals.sortTypes = ['number', 'text', 'status', 'date','date', 'date', 'text', 'text', 'text', 'number', 'text', 'text', 'text', 'number', 'text'];
            p.sections.festivals.dataMaps = ['tnid', 'festival_name', 'status_text', 'live_date', 'start_date', 'end_date', 'tenant_name', 'smtp', 'theme', 'matomo_id', 'etransfer', 'waiver', 'stripe', 'num_reg', 'provincials'];
            p.sections.festivals.num_cols = p.sections.festivals.dataMaps.length;
            p.sections.festivals.label = 'Festivals (' + rsp.nplist.length + ')';
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    this.openFestival = function(tnid, id) {
        M.startApp('ciniki.tenants.main',null,'M.ciniki_core_menu.tenants.show();','mc',{'id':tnid});
        setTimeout(function() {
            M.startApp('ciniki.musicfestivals.main',null,'M.ciniki_musicfestivals_status.start();','mc',{'festival_id':id});
        }, 250);
    }

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
        var ac = M.createContainer(ap, 'ciniki_musicfestivals_status', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }

        this.menu.open(cb);
    }
}
