<?php
/**
 * Template Name: Login
 * Description: Lightweight front-end login page optimized for Core Web Vitals.
 *
 * @package Foxiz Child
 */

declare( strict_types=1 );

get_header();

$login_page_url   = function_exists( 'wd4_get_front_login_url' ) ? wd4_get_front_login_url() : home_url( '/login-3/' );
$default_redirect = $login_page_url ?: home_url( '/' );
$redirect_to      = apply_filters( 'wd4_login_redirect', $default_redirect );

$lost_password_url = wp_lostpassword_url( $login_page_url );
$registration_url  = get_option( 'users_can_register' ) ? wp_registration_url() : '';
$is_logged_in      = is_user_logged_in();
$current_user      = $is_logged_in ? wp_get_current_user() : null;
$logout_url        = $is_logged_in ? wp_logout_url( $redirect_to ) : '';
$continue_url      = apply_filters( 'wd4_login_continue_url', home_url( '/' ) );

$page_title  = esc_html__( 'Sign in', 'foxiz-child' );
$page_tagline = '';
$page_intro  = '';

if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        $page_title   = get_the_title();
        $page_tagline = has_excerpt() ? get_the_excerpt() : '';

        ob_start();
        the_content();
        $page_intro = trim( (string) ob_get_clean() );

        break;
    }

    rewind_posts();
}

$form_html = '';

if ( ! $is_logged_in ) {
    $form_html = wp_login_form(
        array(
            'echo'           => false,
            'form_id'        => 'wd-login-form',
            'redirect'       => $redirect_to,
            'label_username' => esc_html__( 'Username or Email Address', 'foxiz-child' ),
            'label_password' => esc_html__( 'Password', 'foxiz-child' ),
            'label_remember' => esc_html__( 'Keep me signed in', 'foxiz-child' ),
            'label_log_in'   => esc_html__( 'Sign in', 'foxiz-child' ),
            'remember'       => true,
        )
    );
}
?>

<main id="primary" class="site-main wd-login" role="main">
    <div class="wd-login__panel">
        <header class="wd-login__header">
            <h1 class="wd-login__title"><?php echo esc_html( $page_title ); ?></h1>

            <?php if ( '' !== $page_tagline ) : ?>
                <p class="wd-login__tagline"><?php echo esc_html( $page_tagline ); ?></p>
            <?php endif; ?>
        </header>

        <div class="wd-login__card">
            <?php if ( '' !== $page_intro ) : ?>
                <div class="wd-login__intro">
                    <?php echo $page_intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>

            <?php if ( $is_logged_in && $current_user instanceof WP_User ) : ?>
                <div class="wd-login__status" role="status">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: current user display name */
                            esc_html__( 'You are signed in as %s.', 'foxiz-child' ),
                            '<strong>' . esc_html( $current_user->display_name ?: $current_user->user_login ) . '</strong>'
                        );
                        ?>
                    </p>

                    <div class="wd-login__actions">
                        <a class="wd-login__button wd-login__button--primary" href="<?php echo esc_url( $continue_url ); ?>">
                            <?php esc_html_e( 'Continue to AI Study Now', 'foxiz-child' ); ?>
                        </a>
                        <a class="wd-login__button wd-login__button--ghost" href="<?php echo esc_url( $logout_url ); ?>">
                            <?php esc_html_e( 'Sign out', 'foxiz-child' ); ?>
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <div class="wd-login__form">
                    <?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>

                <div class="wd-login__extras" aria-label="<?php esc_attr_e( 'Helpful account links', 'foxiz-child' ); ?>">
                    <a class="wd-login__link" href="<?php echo esc_url( $lost_password_url ); ?>">
                        <?php esc_html_e( 'Forgot your password?', 'foxiz-child' ); ?>
                    </a>

                    <?php if ( $registration_url ) : ?>
                        <a class="wd-login__link" href="<?php echo esc_url( $registration_url ); ?>">
                            <?php esc_html_e( 'Create an account', 'foxiz-child' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="wd-login__footer">
            <a class="wd-login__back" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                &larr; <?php esc_html_e( 'Back to AI Study Now', 'foxiz-child' ); ?>
            </a>
        </div>
    </div>
</main>

<?php
get_footer();
