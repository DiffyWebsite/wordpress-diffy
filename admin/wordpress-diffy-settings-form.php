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
  <form method="POST" action="options.php">
    <?php
    settings_fields( 'reading' );
    do_settings_sections( 'reading' );
    ?>
    <?php submit_button(); ?>
  </form>
</div>