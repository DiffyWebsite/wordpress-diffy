<?php

/**
 * Admin Settings form for plugin
 */
?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
  <div id="icon-themes" class="icon32"></div>
  <h2>Diffy Settings</h2>
  <?php settings_errors(); ?>
  <?php if (empty(get_option('diffy_api_key'))): ?>
  <br/><p class="diffy-register-wrapper">
      Do not have an account with Diffy? <button id="diffy-register" class="button button-primary">One click register</button> with your current email address and random password.<br/>
  </p>
  <?php endif; ?>

  <form method="POST" action="options.php">
    <?php
    settings_fields( 'diffy' );
    do_settings_sections( 'diffy' );
    ?>
    <?php submit_button(); ?>
  </form>
</div>
