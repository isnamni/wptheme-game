<?php
/**
 * Comments template.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="tka-comments tka-card">

	<?php if ( have_comments() ) : ?>
		<h2 class="tka-comments-title">
			<?php
			printf(
				/* translators: %s: comments count */
				esc_html__( '%s دیدگاه', 'toykindangel' ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="tka-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'avatar_size' => 48,
					'short_ping'  => true,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => '&laquo; ' . __( 'دیدگاه‌های قبلی', 'toykindangel' ),
				'next_text' => __( 'دیدگاه‌های بعدی', 'toykindangel' ) . ' &raquo;',
			)
		);
	endif;

	if ( ! comments_open() && get_comments_number() ) :
		?>
		<p class="tka-no-comments tka-muted"><?php esc_html_e( 'امکان ارسال دیدگاه برای این مطلب بسته شده است.', 'toykindangel' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'         => __( 'دیدگاه خود را بنویسید', 'toykindangel' ),
			'label_submit'        => __( 'ارسال دیدگاه', 'toykindangel' ),
			'comment_notes_before' => '',
			'comment_field'       => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'دیدگاه *', 'toykindangel' ) . '</label><textarea id="comment" name="comment" rows="5" required></textarea></p>',
		)
	);
	?>
</div>
