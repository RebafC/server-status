<?php declare(strict_types=1);

declare(strict_types=1);

require_once __DIR__.'/template.php';
if (!file_exists('config.php')) {
    require_once __DIR__.'/install.php';
} else {
    require_once __DIR__.'/config.php';

    Template::render_header('Page not found');
    ?>
  <div class="text-center">
    <h1><?php echo _('Page Not Found'); ?></h1>
    <p><?php echo _('Sorry, but the page you were trying to view does not exist.'); ?></p>
  </div>
<?php
    Template::render_footer();
}
