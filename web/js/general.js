$(function() {

    $(document.body).on('keyup', '#searchAccUser', function() {
        var searchVal = $(this).val().replace(/^\s+/, "");

        if (searchVal.length > 2) {
            var data = 'searchAccUser=' + searchVal;
            $.ajax({
                async : false,
                url : window.accUser.url,
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
                    url : window.accUser.url,
                    type : 'post',
                    dataType : 'json',
                    data : data,
                    beforeSend : '',
                    success : function(dataReturn) {
                    },
                    complete : function() {
                    },
                });
                $(this).closest('tr').slideUp('slow').delay(2000).queue(function() {
                            $(this).remove();
                        });
                $('#msgAppId').html('User Assign to Account').fadeIn(1600).fadeOut(1600);
            });

    $('#popupAssignUsers').on('hide.bs.modal', function(e) {
        location.reload();
    });
    $('#popupAssignUsers').on('shown.bs.modal', function(e) {
        // do something...	
        $('#searchAccUser').val('');
    });

});

//-- APPLICATION SECTION --//
$(function() {

    $(document.body).on('keyup', '#searchAppUser', function() {
        var searchVal = $(this).val().replace(/^\s+/, "");

        if (searchVal.length > 2) {
            var data = 'searchAppUser=' + searchVal;
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

    $('.jsAppUserRemove').click(function() {

        if (confirm('Are you sure?')) {
            $('#delAssignUser').val($(this).data('username'));
            $('#frmAppUser').submit();
        } else {
            $('#delAssignUser').val('');
        }

    });

    $(document).on('click', '.jsAddAppuser', function() {
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
            $(this).closest('tr').slideUp('slow').delay(2000).queue( function() {
                        $(this).remove();
                    });
            $('#msgAppId').html('User Assign to Application').fadeIn(1600).fadeOut(1600);
    });

    $('#popUpAppAssignUser').on('hide.bs.modal', function(e) {
        location.reload();
    });
    $('#popUpAppAssignUser').on('shown.bs.modal', function(e) {
        // do something...  
        $( '#searchAppUser' ).val('');
    });

});

//-- USER SECTION/MODULE //

$(function() {

    $('#_username').keyup(function(){
        
        var strVal = $(this).val();
        strVal = strVal.toLowerCase().replace(/ /g, '');
        $(this).val(strVal);
        
    });
    
   $('#_username').blur(function() {
       
        var valUsername = $.trim( $(this).val());
       if ( valUsername != '') {
           
           var regex = /^[a-z 0-9]+$/;
           if (!regex.test(valUsername)) {
               $('#btnSubmit').attr('disabled', 'disabled');
               $( '#chkUserExist' ).html('Allow only small letter and number').addClass('alert alert-danger').slideDown( "slow" );
               return false;
           } else {
               $( '#chkUserExist').html('').slideUp( "slow" ).removeClass('alert alert-danger');
               $( '#btnSubmit' ).prop('disabled', false);
           }           
           
           var data = 'username=' + valUsername;
           $.ajax({
               async : false,
               url : window.checkUser.url,
               type : 'post',
               dataType : 'json',
               data : data,
               beforeSend : '',
               success : function(dataReturn) {
                      
                      if(!dataReturn.success) { 
                          $( '#chkUserExist' ).html(dataReturn.html).addClass('alert alert-danger').slideDown( "slow" );
                          $('#btnSubmit').attr('disabled', 'disabled');
                      } else {
                          $( '#chkUserExist' ).html(dataReturn.html).slideUp( "slow" ).removeClass('alert alert-danger');
                          $( '#btnSubmit' ).prop('disabled', false);
                      }
                      
               },
               complete : function() {
                   
               },
           });           
       } else {
           $( '#chkUserExist' ).html('').slideUp( "slow" );
           $( '#btnSubmit' ).prop('disabled', false);
       }
       
   }) ;
});

// ADMIN LOG

$(function(){
    
    $('.js-data-toggle').click(function(){
        $(this).parent().next("tr").find('td').slideToggle("slow"); 
    });
});
