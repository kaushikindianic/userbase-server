$(function() {

    $(document.body).on('keyup', '#searchUser', function() {
        var searchVal = $(this).val().replace(/^\s+/, "");

        if (searchVal.length > 2) {
            var data = 'searchUser=' + searchVal;
            $.ajax({
                async : false,
                url : window.appUser.url,
                type : 'post',
                dataType : 'json',
                data : data,
                beforeSend : '',
                success : function(dataReturn) {
                    $('#userAssignContent').html(dataReturn.html);
                },
                complete : function() {
                },
            });
        } else {
            $('#userAssignContent').html('');
        }
    });

    $('.jsUserRemove').click(function() {

        if (confirm('Are you sure?')) {
            $('#delAssignUser').val($(this).data('username'));
            $('#frmAccUser').submit();
        } else {
            $('#delAssignUser').val('');
        }

    });

    $(document).on('click', '.jsAdduser', function() {
                var userName = $(this).data('username');
                var data = 'userName=' + userName;
                $.ajax({
                    async : false,
                    url : window.appUser.url,
                    type : 'post',
                    dataType : 'json',
                    data : data,
                    beforeSend : '',
                    success : function(dataReturn) {
                    },
                    complete : function() {
                    },
                });
                $(this).closest('tr').slideUp('slow').delay(2000).queue(
                        function() {
                            $(this).remove();
                        });
                $('#msgAppId').html('User Assign to Account').fadeIn(1600)
                        .fadeOut(1600);
            });

    $('#popupAssignUsers').on('hide.bs.modal', function(e) {
        location.reload();
    });
    $('#popupAssignUsers').on('shown.bs.modal', function(e) {
        // do something...	
        $('#searchUser').val('');
    });

});