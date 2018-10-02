
var admin_header = {
    init: function() {
        $('AdminRiderSelect').addEvents( {
            'change': admin_header.changeRider
        });

        $('AdminDriverSelect').addEvents( {
            'change': admin_header.changeDriver
        });


        $('AdminSortLast').addEvents( {
            'change': admin_header.changeOrder
        });
        
        $('AdminSortFirst').addEvents( {
            'change': admin_header.changeOrder
        });

    },

    changeDriver: function(evt) {
        var sel = $('AdminDriverSelect');
        var opt = sel.options[sel.selectedIndex];

        var userID = opt.value;
        var usersName = opt.firstChild.nodeValue;

        var req = new Request({
            method: 'get',
            url: 'xhr/set_admin_work_as_user.php',
            data: { 'uid': userID },
            onComplete:  function(response) {
                if (response.length != 0) {
                    $('AdminCurrentUser').setStyle('display', '');
                    $('AdminCurrentUserInfo').innerHTML = response;
                } else {
                    $('AdminCurrentUser').setStyle('display', 'none');
                }
                if (typeof(skip_reload_on_effective_user_change) == "undefined" ||
                    !(skip_reload_on_effective_user_change == true)) {
                    window.location = window.location.href;
                }
            }
        }).send();
    },

    changeRider: function(evt) {
        var sel = $('AdminRiderSelect');
        var opt = sel.options[sel.selectedIndex];

        var userID = opt.value;
        var usersName = opt.firstChild.nodeValue;

        var req = new Request({
            method: 'get',
            url: 'xhr/set_admin_work_as_user.php',
            data: { 'uid': userID },
            onComplete:  function(response) {
                if (response.length != 0) {
                    $('AdminCurrentUser').setStyle('display', '');
                    $('AdminCurrentUserInfo').innerHTML = response;
                } else {
                    $('AdminCurrentUser').setStyle('display', 'none');
                }
                if (typeof(skip_reload_on_effective_user_change) == "undefined" ||
                    !(skip_reload_on_effective_user_change == true)) {
                    window.location = window.location.href;
                }
            }
        }).send();
    },

    changeOrder: function(evt) {

        var sortOrder = 'L';
        if ($('AdminSortFirst').checked) {
            sortOrder = 'F';
        }

        var req = new Request({
            method: 'get',
            url: 'xhr/set_admin_work_as_user.php',
            data: { 'SortOrder': sortOrder },
            onComplete:  function(response) {
                if (response.length != 0) {
                    $('AdminCurrentUser').setStyle('display', '');
                    $('AdminCurrentUserInfo').innerHTML = response;
                } else {
                    $('AdminCurrentUser').setStyle('display', 'none');
                }
                if (typeof(skip_reload_on_effective_user_change) == "undefined" ||
                    !(skip_reload_on_effective_user_change == true)) {
                    window.location = window.location.href;
                }
            }
        }).send();
    }

}

window.addEvent('domready', admin_header.init);
