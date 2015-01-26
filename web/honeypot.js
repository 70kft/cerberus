define([
    'jquery'
  ],
  function($) {
    'use strict';

    var token;

    $.post(
      'cerberus/getUUIDToken',
      'action=cerberus%2FgetUUIDToken',
      function(response) {
        token = response;
      }
    );

    // Forms: AJAX Submission
    $('.your-form').submit(function(e) {
      // Prevent form from submitting
      e.preventDefault();

      var fromEmail = $(this).find('.fromEmail').val();

      // Get post data
      var data = $(this).serialize();
      data = data + '\r\n' + fromEmail;
      data = data + '&token='+ token;

      // Send to server and respond to user
      $.post(
        'cerberus/sendMessage',
        data,
        function(response) {
          if (response.success) {
            window.location.replace('/thanks');
            console.log('success');
          } else {
            console.log('fail');
          }
        }
      );
    });
  }
);