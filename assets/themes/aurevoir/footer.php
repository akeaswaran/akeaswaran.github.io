<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
?>
	</div><!-- #main .wrapper -->
	<footer id="colophon" role="contentinfo">
	<div id="site-generator">

		<?php bloginfo('name'); ?> © <?php echo date('Y'); ?> </br>

















	<span style="font-size:9px">Designed by <a href="http://theblogboat.co.za">The Blog Boat</a></span>



	</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>