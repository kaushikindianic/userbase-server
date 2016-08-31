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
