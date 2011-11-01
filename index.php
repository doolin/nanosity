<?php
get_header();
while (have_posts()) {
  the_post();
  the_title();
}
get_footer();
?>
