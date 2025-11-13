<?php
/**
 * Plugin Name: WD Login Security & Rate Limiter
 * Description: Rate limiting + Google reCAPTCHA (v3/Enterprise style) for login, register and forgot-password.
 * Author: AI Study Now
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * === CONFIG ===
 * Limits:
 * - 5 attempts in 15 minutes
 * - 30 minute lockout
 */

if ( ! defined( 'WD_LOGIN_MAX_ATTEMPTS' ) ) {
    define( 'WD_LOGIN_MAX_ATTEMPTS', 5 );
}
if ( ! defined( 'WD_LOGIN_ATTEMPT_WINDOW' ) ) {
    define( 'WD_LOGIN_ATTEMPT_WINDOW', 15 * MINUTE_IN_SECONDS );
}
if ( ! defined( 'WD_LOGIN_LOCKOUT_TIME' ) ) {
    define( 'WD_LOGIN_LOCKOUT_TIME', 30 * MINUTE_IN_SECONDS );
}

/** reCAPTCHA keys (from Google admin) */
if ( ! defined( 'WD_RECAPTCHA_SITE_KEY' ) ) {
    define( 'WD_RECAPTCHA_SITE_KEY', '6LdAlgcsAAAAAJPn05eWT8ZnZxb1aQtgBir6atpd' );
}
if ( ! defined( 'WD_RECAPTCHA_SECRET_KEY' ) ) {
    define( 'WD_RECAPTCHA_SECRET_KEY', '6LdAlgcsAAAAAGwgZBtmMDrQetVBeFIH3OusIsGK' );
}

/** Helper: is reCAPTCHA enabled? */
function wd_recaptcha_is_enabled(): bool {
    return defined( 'WD_RECAPTCHA_SITE_KEY' ) && WD_RECAPTCHA_SITE_KEY
        && defined( 'WD_RECAPTCHA_SECRET_KEY' ) && WD_RECAPTCHA_SECRET_KEY;
}

/** Helper: get client IP (basic; adjust for Cloudflare/proxy if you use one). */
function wd_login_get_client_ip(): string {
    $keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );

    foreach ( $keys as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $value = trim( (string) $_SERVER[ $key ] );
            $parts = explode( ',', $value ); // X_FORWARDED_FOR may be "ip1, ip2"
            return trim( $parts[0] );
        }
    }

    return '';
}

/**
 * Verify a reCAPTCHA token with Google.
 *
 * @return true|WP_Error
 */
function wd_recaptcha_verify( string $token, string $expected_action = '' ) {
    if ( ! wd_recaptcha_is_enabled() ) {
        return true; // Not configured, treat as pass.
    }

    $token = trim( $token );
    if ( '' === $token ) {
        return new WP_Error(
            'recaptcha_missing',
            esc_html__( 'reCAPTCHA verification failed. Please try again.', 'default' )
        );
    }

    $ip = wd_login_get_client_ip();

    $response = wp_remote_post(
        'https://www.google.com/recaptcha/api/siteverify',
        array(
            'timeout' => 5,
            'body'    => array(
                'secret'   => WD_RECAPTCHA_SECRET_KEY,
                'response' => $token,
                'remoteip' => $ip,
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error(
            'recaptcha_http_error',
            esc_html__( 'reCAPTCHA request error. Please try again.', 'default' )
        );
    }

    $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

    if ( empty( $data['success'] ) ) {
        return new WP_Error(
            'recaptcha_failed',
            esc_html__( 'reCAPTCHA check failed. Please try again.', 'default' )
        );
    }

    // Score check (for v3/Enterprise).
    if ( isset( $data['score'] ) && (float) $data['score'] < 0.3 ) {
        return new WP_Error(
            'recaptcha_low_score',
            esc_html__( 'reCAPTCHA thinks this request is suspicious. Please try again later.', 'default' )
        );
    }

    // Optional action check.
    if ( $expected_action && isset( $data['action'] ) && $data['action'] !== $expected_action ) {
        return new WP_Error(
            'recaptcha_bad_action',
            esc_html__( 'reCAPTCHA action mismatch. Please reload the page and try again.', 'default' )
        );
    }

    return true;
}

/**
 * === RATE LIMIT CORE (same as before) ===
 */

/** Increment attempts for a given key (IP or username). */
function wd_login_bump_attempts( string $key ): void {
    if ( empty( $key ) ) {
        return;
    }

    $now     = time();
    $record  = get_transient( $key );
    $max     = WD_LOGIN_MAX_ATTEMPTS;
    $window  = WD_LOGIN_ATTEMPT_WINDOW;
    $lockout = WD_LOGIN_LOCKOUT_TIME;

    if ( ! is_array( $record ) ) {
        $record = array(
            'count'      => 0,
            'last'       => 0,
            'lock_until' => 0,
        );
    }

    if ( ! empty( $record['lock_until'] ) && $record['lock_until'] > $now ) {
        return;
    }

    if ( empty( $record['last'] ) || $record['last'] < ( $now - $window ) ) {
        $record['count'] = 1;
        $record['last']  = $now;
    } else {
        $record['count']++;
        $record['last'] = $now;
    }

    if ( $record['count'] >= $max ) {
        $record['lock_until'] = $now + $lockout;
    }

    set_transient( $key, $record, $window + $lockout + MINUTE_IN_SECONDS );
}

/** Check if IP / username is locked for LOGIN. */
function wd_login_is_locked( ?string $ip, ?string $username ) {
    $now          = time();
    $locked       = false;
    $seconds_left = 0;

    $keys = array();

    if ( ! empty( $ip ) ) {
        $keys[] = 'wd_login_ip_' . md5( $ip );
    }

    if ( ! empty( $username ) ) {
        $safe_user = sanitize_user( $username );
        if ( $safe_user ) {
            $keys[] = 'wd_login_user_' . strtolower( $safe_user );
        }
    }

    foreach ( $keys as $key ) {
        $record = get_transient( $key );
        if ( is_array( $record ) && ! empty( $record['lock_until'] ) && $record['lock_until'] > $now ) {
            $locked       = true;
            $remaining    = $record['lock_until'] - $now;
            $seconds_left = max( $seconds_left, $remaining );
        }
    }

    if ( ! $locked ) {
        return false;
    }

    return array(
        'locked'   => true,
        'seconds'  => $seconds_left,
        'minutes'  => (int) ceil( $seconds_left / 60 ),
    );
}

/**
 * LOGIN: authenticate filter – check rate limit + reCAPTCHA.
 */
function wd_login_rate_limit_authenticate( $user, string $username, string $password ) {
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return $user;
    }

    $ip = wd_login_get_client_ip();

    // 1) Rate-limit check
    $locked = wd_login_is_locked( $ip, $username );
    if ( $locked ) {
        $minutes = max( 1, (int) $locked['minutes'] );

        return new WP_Error(
            'too_many_attempts',
            sprintf(
                esc_html__( 'ERROR: Too many failed login attempts. Please try again in %d minutes.', 'default' ),
                $minutes
            )
        );
    }

    // 2) reCAPTCHA check (only for normal username/password POSTs with our hidden field)
    if ( isset( $_POST['log'], $_POST['pwd'], $_POST['g-recaptcha-login'] ) ) {
        $token      = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-login'] ) );
        $recaptcha  = wd_recaptcha_verify( $token, 'login' );

        if ( is_wp_error( $recaptcha ) ) {
            return $recaptcha;
        }
    }

    return $user;
}
add_filter( 'authenticate', 'wd_login_rate_limit_authenticate', 5, 3 );

/** LOGIN: on failed login, bump counters for IP + username. */
function wd_login_rate_limit_on_fail( string $username ): void {
    $ip = wd_login_get_client_ip();

    if ( $ip ) {
        wd_login_bump_attempts( 'wd_login_ip_' . md5( $ip ) );
    }

    if ( $username ) {
        $safe_user = sanitize_user( $username );
        if ( $safe_user ) {
            wd_login_bump_attempts( 'wd_login_user_' . strtolower( $safe_user ) );
        }
    }
}
add_action( 'wp_login_failed', 'wd_login_rate_limit_on_fail' );

/** LOGIN: on success, clear counters. */
function wd_login_rate_limit_on_success( string $user_login, WP_User $user ): void {
    $ip = wd_login_get_client_ip();

    if ( $ip ) {
        delete_transient( 'wd_login_ip_' . md5( $ip ) );
    }

    $safe_user = sanitize_user( $user_login );
    if ( $safe_user ) {
        delete_transient( 'wd_login_user_' . strtolower( $safe_user ) );
    }
}
add_action( 'wp_login', 'wd_login_rate_limit_on_success', 10, 2 );

/**
 * REGISTER + FORGOT: Rate-limit front-end forms by IP.
 * (login-3/?mode=register and login-3/?mode=forgot)
 */
function wd_rate_limit_register_forgot_check(): void {
    if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        return;
    }

    $is_front_register = isset( $_POST['wd_register_nonce'] );
    $is_front_forgot   = isset( $_POST['wd_forgot_nonce'] );

    if ( ! $is_front_register && ! $is_front_forgot ) {
        return;
    }

    $ip = wd_login_get_client_ip();
    if ( ! $ip ) {
        return;
    }

    $now        = time();
    $key_prefix = $is_front_register ? 'wd_reg_ip_' : 'wd_forgot_ip_';
    $key        = $key_prefix . md5( $ip );

    $record = get_transient( $key );

    if ( is_array( $record ) && ! empty( $record['lock_until'] ) && $record['lock_until'] > $now ) {
        $minutes = (int) ceil( ( $record['lock_until'] - $now ) / 60 );
        $minutes = max( 1, $minutes );

        wp_die(
            sprintf(
                esc_html__( 'Too many attempts from your IP. Please try again in %d minutes.', 'default' ),
                $minutes
            ),
            esc_html__( 'Rate limit', 'default' ),
            array( 'response' => 429 )
        );
    }

    if ( ! is_array( $record ) ) {
        $record = array(
            'count'      => 1,
            'last'       => $now,
            'lock_until' => 0,
        );
    } else {
        if ( empty( $record['last'] ) || $record['last'] < ( $now - WD_LOGIN_ATTEMPT_WINDOW ) ) {
            $record['count'] = 1;
            $record['last']  = $now;
        } else {
            $record['count']++;
            $record['last'] = $now;
        }
    }

    if ( $record['count'] >= WD_LOGIN_MAX_ATTEMPTS ) {
        $record['lock_until'] = $now + WD_LOGIN_LOCKOUT_TIME;
    }

    set_transient( $key, $record, WD_LOGIN_ATTEMPT_WINDOW + WD_LOGIN_LOCKOUT_TIME + MINUTE_IN_SECONDS );
}
add_action( 'init', 'wd_rate_limit_register_forgot_check', 1 );
