( function( $ ) {

  $( document ).ready( function() {
    $('#diffy-register').click(function() {
      $('.diffy-register-error').remove();

      $('#diffy-register').html('Registering a user and creating project...');
      $('#diffy-register').attr('disabled', true);

      var data = {
        'action' : 'diffy_register'
      };
      $.post( settings.ajaxurl, data, function( response ) {
        var data = response.data;

        if (response.success) {
          var success_message = '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"> \n' +
              '<p><strong>Diffy account & project created.</strong> It is created by using your email address "' + data.email + '" and password "' + data.password + '". Please note these credentials. But no worries you can always reset the password if you need to.</p></div>';
          $('.diffy-register-wrapper').html(success_message);

          $('input[name=diffy_api_key]').val(data.diffy_api_key);
          $('input[name=diffy_project_id]').val(data.diffy_project_id);
        }
        else {
          var error_message = '<div id="setting-error-settings_updated" class="diffy-register-error notice notice-error settings-error is-dismissible"> \n' +
              '<p><strong>' + data.error_message + '.</strong> Please go to <a target="_blank" href="https://app.diffy.website">Diffy app</a> and retrieve API Key and set up the project manually.</p></div>';
          $('.diffy-register-wrapper').append(error_message);

          $('#diffy-register').html('One click register');
          $('#diffy-register').attr('disabled', false);
        }
      })
      .fail(function() {
        console.log( "Ajax error" );
      });
    });
  });

})( jQuery );
