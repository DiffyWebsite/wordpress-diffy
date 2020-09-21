( function( $ ) {

  /**
   * Call backend to check the job status and display its status to the user.
   *
   * @param string jobType
   */
  showDiffyStatus = function(jobType) {
    var showDiffyStatusInterval = setInterval(function() {

      var data = {
        'action': jobType
      };
      $.post(settings.ajaxurl, data, function (response) {
        var data = response.data;

        if (response.success) {
          if (response.data.status == 'completed') {
            clearInterval(showDiffyStatusInterval);
          }
          $('.diffy-message').html(response.data.message);
        } else {

        }
      })
      .fail(function () {
        console.log("Diffy ajax error");
      });
    }, 5000);
  };

  // Triggered right before ajax request is sent to update plugin.
  $( document ).on('wp-plugin-updating', function(){
    $('.update-message').after('<div class="notice inline notice-alt notice-info"><p class="diffy-message">Diffy creates first set of screenshots (before update).</p></div>');

    showDiffyStatus('diffy_first_screenshots');
  });

  // Triggered after plugin was successfully updated.
  $( document ).on('wp-plugin-update-success', function(){
    $('.diffy-message').html('Updates completed. Now Diffy creates second set of screenshots and comparison.');

    showDiffyStatus('diffy_second_screenshots');
  });



})( jQuery );