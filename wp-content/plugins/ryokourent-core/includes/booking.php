<?php
/**
 * Booking Processing, Customer Data Validation & Anti-Spam Engine for Ryokourent
 *
 * Implements server-side customer data validation (Indonesian cellular phone format,
 * emergency contact separation, name sanitization, address checks), anti-spam honeypot
 * detection, and transient-based IP rate-limiting.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/includes
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// -----------------------------------------------------------------------------
// Rate Limiting Constants
// -----------------------------------------------------------------------------
if (!defined('RYOKOURENT_BOOKING_MAX_ATTEMPTS')) {
    define('RYOKOURENT_BOOKING_MAX_ATTEMPTS', 5);
}
if (!defined('RYOKOURENT_BOOKING_RATE_WINDOW')) {
    define('RYOKOURENT_BOOKING_RATE_WINDOW', 600); // 10 minutes in seconds
}

/**
 * Retrieve and sanitize client IP address.
 *
 * Checks HTTP headers with validation against private/reserved ranges if needed.
 *
 * @since 1.0.0
 * @return string Validated IP address string or fallback '127.0.0.1'.
 */
function ryokourent_get_client_ip() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        $ip_list   = explode(',', $forwarded);
        $ip        = trim($ip_list[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '127.0.0.1';
}

/**
 * Check if the submission IP has exceeded the allowed rate limit.
 *
 * Uses WordPress Transients API to store attempt counters per IP hash.
 *
 * @since 1.0.0
 * @param string $ip             Optional IP address to check. Defaults to client IP.
 * @param int    $max_attempts   Maximum allowed attempts in the time window.
 * @param int    $decay_seconds  Time window in seconds before counter expires.
 * @return array Array with keys: 'allowed' (bool), 'attempts' (int), 'remaining' (int).
 */
function ryokourent_check_booking_rate_limit($ip = '', $max_attempts = null, $decay_seconds = null) {
    if (empty($ip)) {
        $ip = ryokourent_get_client_ip();
    }

    if (null === $max_attempts) {
        $max_attempts = RYOKOURENT_BOOKING_MAX_ATTEMPTS;
    }

    if (null === $decay_seconds) {
        $decay_seconds = RYOKOURENT_BOOKING_RATE_WINDOW;
    }

    $transient_key = 'ryokou_rate_' . md5($ip);
    $attempts      = (int) get_transient($transient_key);

    if ($attempts >= $max_attempts) {
        return array(
            'allowed'   => false,
            'attempts'  => $attempts,
            'remaining' => 0,
        );
    }

    // Increment attempts counter
    $new_attempts = $attempts + 1;
    set_transient($transient_key, $new_attempts, $decay_seconds);

    return array(
        'allowed'   => true,
        'attempts'  => $new_attempts,
        'remaining' => max(0, $max_attempts - $new_attempts),
    );
}

/**
 * Check if the hidden honeypot anti-spam field was filled out.
 *
 * Legitimate human users will not see or fill this field due to CSS concealment.
 * Automated bots filling every input form field will trigger this check.
 *
 * @since 1.0.0
 * @param array  $data       Input data array (usually $_POST).
 * @param string $field_name Honeypot field name.
 * @return bool True if honeypot was triggered (bot detected), false if clean.
 */
function ryokourent_is_honeypot_triggered($data = array(), $field_name = 'ryokourent_hp') {
    if (empty($data)) {
        $data = $_POST;
    }

    if (isset($data[$field_name]) && '' !== trim((string) $data[$field_name])) {
        return true;
    }

    return false;
}

/**
 * Validate customer identity information.
 *
 * Enforces strict validation rules:
 * - Full name according to e-KTP (minimum 3 characters, valid personal name chars).
 * - Indonesian cellular WhatsApp number (10 to 15 digits, starting with 628 / 08).
 * - Family emergency contact number (valid Indonesian phone).
 * - Emergency contact MUST NOT be identical to customer WhatsApp number.
 * - Origin KTP address and Malang/Batu accommodation stay address non-empty.
 *
 * @since 1.0.0
 * @param array $input Raw or unslashed customer data array.
 * @return array Array with keys: 'is_valid' (bool), 'errors' (array), 'data' (sanitized array).
 */
function ryokourent_validate_customer_data($input = array()) {
    $errors = array();
    $clean  = array();

    // 1. Customer Full Name (Nama Lengkap sesuai e-KTP)
    $raw_name = isset($input['customer_name']) ? trim(wp_unslash($input['customer_name'])) : '';
    if (empty($raw_name)) {
        $errors['customer_name'] = __('Nama lengkap wajib diisi sesuai e-KTP.', 'ryokourent');
    } elseif (mb_strlen($raw_name, 'UTF-8') < 3) {
        $errors['customer_name'] = __('Nama lengkap minimal 3 karakter.', 'ryokourent');
    } elseif (mb_strlen($raw_name, 'UTF-8') > 100) {
        $errors['customer_name'] = __('Nama lengkap maksimal 100 karakter.', 'ryokourent');
    } else {
        // Allow letters, spaces, dots, commas, hyphens, and apostrophes
        if (!preg_match('/^[\p{L}\s\.\'\,\-]+$/u', $raw_name)) {
            $errors['customer_name'] = __('Nama lengkap hanya boleh memuat huruf dan tanda baca nama wajar.', 'ryokourent');
        } else {
            $clean['customer_name'] = sanitize_text_field($raw_name);
        }
    }

    // 2. Customer WhatsApp Phone Number (Nomor WhatsApp Aktif)
    $raw_wa = isset($input['customer_whatsapp']) ? trim(wp_unslash($input['customer_whatsapp'])) : '';
    if (empty($raw_wa)) {
        $errors['customer_whatsapp'] = __('Nomor WhatsApp aktif wajib diisi.', 'ryokourent');
    } else {
        $clean_wa = ryokourent_sanitize_phone($raw_wa);
        if (!ryokourent_is_valid_phone($clean_wa)) {
            $errors['customer_whatsapp'] = __('Nomor WhatsApp harus nomor seluler Indonesia yang valid (format 08... atau 628..., 10-15 digit).', 'ryokourent');
        } else {
            $clean['customer_whatsapp'] = $clean_wa;
        }
    }

    // 3. Emergency Contact Phone Number (Kontak Darurat Keluarga)
    $raw_emg = isset($input['customer_emergency_phone']) ? trim(wp_unslash($input['customer_emergency_phone'])) : '';
    if (empty($raw_emg)) {
        $errors['customer_emergency_phone'] = __('Nomor kontak darurat keluarga wajib diisi.', 'ryokourent');
    } else {
        $clean_emg = ryokourent_sanitize_phone($raw_emg);
        if (!ryokourent_is_valid_phone($clean_emg)) {
            $errors['customer_emergency_phone'] = __('Nomor kontak darurat harus nomor seluler Indonesia yang valid (format 08... atau 628..., 10-15 digit).', 'ryokourent');
        } elseif (!empty($clean['customer_whatsapp']) && $clean_emg === $clean['customer_whatsapp']) {
            // Critical Rule: Emergency contact must NOT be identical to the customer's phone
            $errors['customer_emergency_phone'] = __('Nomor kontak darurat keluarga tidak boleh sama dengan nomor WhatsApp Anda.', 'ryokourent');
        } else {
            $clean['customer_emergency_phone'] = $clean_emg;
        }
    }

    // 4. Origin KTP Address (Alamat Sesuai KTP)
    $raw_ktp_addr = isset($input['customer_ktp_address']) ? trim(wp_unslash($input['customer_ktp_address'])) : '';
    if (empty($raw_ktp_addr)) {
        $errors['customer_ktp_address'] = __('Alamat domisili asal sesuai e-KTP wajib diisi.', 'ryokourent');
    } elseif (mb_strlen($raw_ktp_addr, 'UTF-8') < 5) {
        $errors['customer_ktp_address'] = __('Alamat KTP minimal 5 karakter.', 'ryokourent');
    } else {
        $clean['customer_ktp_address'] = sanitize_textarea_field($raw_ktp_addr);
    }

    // 5. Accommodation Address in Malang / Batu (Tempat Menginap)
    $raw_stay_addr = isset($input['customer_stay_address']) ? trim(wp_unslash($input['customer_stay_address'])) : '';
    if (empty($raw_stay_addr)) {
        $errors['customer_stay_address'] = __('Tempat menginap di Malang atau Kota Batu wajib diisi (Hotel/Homestay/Kost).', 'ryokourent');
    } elseif (mb_strlen($raw_stay_addr, 'UTF-8') < 3) {
        $errors['customer_stay_address'] = __('Tempat menginap minimal 3 karakter.', 'ryokourent');
    } else {
        $clean['customer_stay_address'] = sanitize_textarea_field($raw_stay_addr);
    }

    // 6. Social Media ID (Instagram / Facebook - Optional)
    $raw_social = isset($input['customer_social_media']) ? trim(wp_unslash($input['customer_social_media'])) : '';
    $clean['customer_social_media'] = !empty($raw_social) ? sanitize_text_field($raw_social) : '';

    return array(
        'is_valid' => empty($errors),
        'errors'   => $errors,
        'data'     => $clean,
    );
}

/**
 * Validate rental schedule datetime range and operating hours.
 *
 * Enforces business rules:
 * - Datetimes must be parseable in Asia/Jakarta (WIB) timezone.
 * - End datetime must be strictly later than start datetime.
 * - Minimum rental duration is 1 hour.
 * - Both start and end times must fall within operating hours (07:00 – 23:00 WIB).
 * - Accurately computes duration in hours and billable rental days (with 2h tolerance).
 *
 * @since 1.0.0
 * @param string $start_str Raw start datetime string (e.g. '2026-10-02T08:30').
 * @param string $end_str   Raw end datetime string (e.g. '2026-10-04T17:00').
 * @param int    $tolerance_hours Overtime grace period in hours. Default 2.
 * @return array Array with keys: 'is_valid' (bool), 'errors' (array), 'duration_hours' (float), 'billable_days' (int), 'summary_label' (string), 'start_iso' (string), 'end_iso' (string).
 */
function ryokourent_validate_rental_schedule($start_str, $end_str, $tolerance_hours = 2) {
    $errors = array();
    $tz = function_exists('ryokourent_get_timezone') ? ryokourent_get_timezone() : new DateTimeZone('Asia/Jakarta');

    $clean_start = sanitize_text_field(wp_unslash($start_str));
    $clean_end   = sanitize_text_field(wp_unslash($end_str));

    if (empty($clean_start)) {
        $errors['start_datetime'] = __('Waktu mulai sewa wajib ditentukan.', 'ryokourent');
    }
    if (empty($clean_end)) {
        $errors['end_datetime'] = __('Waktu selesai sewa wajib ditentukan.', 'ryokourent');
    }

    if (!empty($errors)) {
        return array(
            'is_valid'       => false,
            'errors'         => $errors,
            'duration_hours' => 0.0,
            'billable_days'  => 0,
            'summary_label'  => '',
            'start_iso'      => $clean_start,
            'end_iso'        => $clean_end,
        );
    }

    try {
        $start_dt = new DateTime($clean_start, $tz);
    } catch (Exception $e) {
        $errors['start_datetime'] = __('Format tanggal/jam mulai sewa tidak valid.', 'ryokourent');
    }

    try {
        $end_dt = new DateTime($clean_end, $tz);
    } catch (Exception $e) {
        $errors['end_datetime'] = __('Format tanggal/jam selesai sewa tidak valid.', 'ryokourent');
    }

    if (!empty($errors)) {
        return array(
            'is_valid'       => false,
            'errors'         => $errors,
            'duration_hours' => 0.0,
            'billable_days'  => 0,
            'summary_label'  => '',
            'start_iso'      => $clean_start,
            'end_iso'        => $clean_end,
        );
    }

    // Check end > start
    if ($end_dt <= $start_dt) {
        $errors['end_datetime'] = __('Waktu selesai sewa harus lebih akhir dari waktu mulai sewa.', 'ryokourent');
    }

    // Check past date: start datetime must not be in the past (with 15-min submission buffer)
    $now_wib = new DateTime('now', $tz);
    if ($start_dt->getTimestamp() < ($now_wib->getTimestamp() - 900)) {
        $errors['start_datetime'] = __('Waktu mulai sewa tidak boleh berada di masa lalu.', 'ryokourent');
    }

    // Check operating hours for start & end time (07:00 - 23:00 WIB)
    if (function_exists('ryokourent_is_within_operating_hours')) {
        if (!ryokourent_is_within_operating_hours($clean_start)) {
            $errors['start_datetime'] = __('Jam mulai sewa harus berada dalam jam operasional pool (07:00 – 23:00 WIB).', 'ryokourent');
        }
        if (!ryokourent_is_within_operating_hours($clean_end)) {
            $errors['end_datetime'] = __('Jam selesai sewa harus berada dalam jam operasional pool (07:00 – 23:00 WIB).', 'ryokourent');
        }
    }

    // Calculate duration in hours
    $diff_seconds = $end_dt->getTimestamp() - $start_dt->getTimestamp();
    $duration_hours = max(0.0, round($diff_seconds / 3600, 2));

    if (empty($errors) && $duration_hours < 1.0) {
        $errors['end_datetime'] = __('Durasi pemesanan minimal adalah 1 jam.', 'ryokourent');
    }

    // Calculate billable days with 2-hour tolerance grace period
    $billable_days = 0;
    if (function_exists('ryokourent_calculate_rental_days')) {
        $billable_days = ryokourent_calculate_rental_days($clean_start, $clean_end, $tolerance_hours);
    } else {
        if ($duration_hours <= (24 + $tolerance_hours)) {
            $billable_days = 1;
        } else {
            $full_days = floor($duration_hours / 24);
            $extra = fmod($duration_hours, 24);
            $billable_days = ($extra > $tolerance_hours) ? (int) ($full_days + 1) : (int) $full_days;
        }
    }
    $billable_days = max(1, (int) $billable_days);

    // Format human-friendly duration summary
    $display_hours = (fmod($duration_hours, 1) == 0.0) ? number_format($duration_hours, 0) : number_format($duration_hours, 1, '.', '');
    $summary_label = sprintf(
        /* translators: 1: Days count, 2: Hours */
        _n('%1$d Hari (~%2$s Jam)', '%1$d Hari (~%2$s Jam)', $billable_days, 'ryokourent'),
        $billable_days,
        $display_hours
    );

    return array(
        'is_valid'       => empty($errors),
        'errors'         => $errors,
        'duration_hours' => $duration_hours,
        'billable_days'  => $billable_days,
        'summary_label'  => $summary_label,
        'start_iso'      => $start_dt->format('Y-m-d H:i'),
        'end_iso'        => $end_dt->format('Y-m-d H:i'),
    );
}

/**
 * Validate complete booking form submission.
 *
 * Verifies:
 * - CSRF Nonce token
 * - Anti-spam honeypot
 * - Rate limiting
 * - Customer identity fields
 * - Rental schedule & operating hours (07:00 - 23:00 WIB)
 * - Rental fleet selection & location
 *
 * @since 1.0.0
 * @param array $raw_data Array of input data ($_POST).
 * @return array Array with keys: 'success' (bool), 'code' (string), 'errors' (array), 'clean_data' (array).
 */
function ryokourent_validate_booking_submission($raw_data = array()) {
    if (empty($raw_data)) {
        $raw_data = $_POST;
    }

    // 1. Anti-spam Honeypot Check
    if (ryokourent_is_honeypot_triggered($raw_data)) {
        return array(
            'success'    => false,
            'code'       => 'spam_bot_detected',
            'message'    => __('Permintaan ditolak: Aktivitas bot/spam terdeteksi.', 'ryokourent'),
            'errors'     => array('general' => __('Aktivitas bot terdeteksi.', 'ryokourent')),
            'status'     => 400,
            'clean_data' => array(),
        );
    }

    // 2. Nonce Verification Check
    $nonce = isset($raw_data['ryokourent_booking_nonce']) ? sanitize_text_field(wp_unslash($raw_data['ryokourent_booking_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ryokourent_booking_form_action')) {
        return array(
            'success'         => false,
            'code'            => 'invalid_nonce',
            'message'         => __('Sesi keamanan formulir telah kedaluwarsa karena halaman tersimpan di cache. Memperbarui sesi...', 'ryokourent'),
            'refreshed_nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('ryokourent_booking_form_action') : '',
            'errors'          => array('general' => __('Token keamanan formulir kedaluwarsa.', 'ryokourent')),
            'status'          => 403,
            'clean_data'      => array(),
        );
    }

    // 3. Transient-based Rate Limiting Check
    $rate_status = ryokourent_check_booking_rate_limit();
    if (!$rate_status['allowed']) {
        return array(
            'success'    => false,
            'code'       => 'rate_limit_exceeded',
            'message'    => __('Terlalu banyak permintaan pemesanan dalam waktu singkat. Mohon tunggu beberapa menit sebelum mencoba kembali.', 'ryokourent'),
            'errors'     => array('general' => __('Batas laju pemesanan tercapai. Mohon tunggu sejenak.', 'ryokourent')),
            'status'     => 429,
            'clean_data' => array(),
        );
    }

    // 4. Validate Rental Schedule (Dates, Operating Hours 07:00-23:00 WIB, Tolerance Grace)
    $start_raw = isset($raw_data['start_datetime']) ? $raw_data['start_datetime'] : '';
    $end_raw   = isset($raw_data['end_datetime']) ? $raw_data['end_datetime'] : '';
    $schedule_result = ryokourent_validate_rental_schedule($start_raw, $end_raw);
    if (!$schedule_result['is_valid']) {
        return array(
            'success'    => false,
            'code'       => 'invalid_schedule',
            'message'    => __('Jadwal sewa yang dipilih tidak valid atau berada di luar jam operasional (07:00 – 23:00 WIB).', 'ryokourent'),
            'errors'     => $schedule_result['errors'],
            'status'     => 400,
            'clean_data' => array(),
        );
    }

    // 5. Validate Customer Identity Data
    $customer_result = ryokourent_validate_customer_data($raw_data);
    if (!$customer_result['is_valid']) {
        return array(
            'success'    => false,
            'code'       => 'validation_error',
            'message'    => __('Terdapat kesalahan pada data identitas pelanggan. Mohon periksa kembali isian Anda.', 'ryokourent'),
            'errors'     => $customer_result['errors'],
            'status'     => 400,
            'clean_data' => $customer_result['data'],
        );
    }

    $clean = $customer_result['data'];

    // Append Schedule Data
    $clean['start_datetime'] = $schedule_result['start_iso'];
    $clean['end_datetime']   = $schedule_result['end_iso'];
    $clean['duration_hours'] = $schedule_result['duration_hours'];
    $clean['total_days']     = $schedule_result['billable_days'];
    $clean['duration_label'] = $schedule_result['summary_label'];

    // 6. Validate Motor Selection
    $motor_id = isset($raw_data['rented_motor_id']) ? absint(wp_unslash($raw_data['rented_motor_id'])) : 0;
    if ($motor_id <= 0) {
        return array(
            'success'    => false,
            'code'       => 'missing_motor_selection',
            'message'    => __('Silakan pilih model armada motor yang ingin disewa.', 'ryokourent'),
            'errors'     => array('rented_motor_id' => __('Pilihan armada motor wajib dipilih.', 'ryokourent')),
            'status'     => 400,
            'clean_data' => $clean,
        );
    }
    $clean['rented_motor_id'] = $motor_id;

    // 7. Atomic Fleet Availability Check (Point 1 Critical Checkpoint - Anti Double-Booking)
    if (function_exists('ryokourent_check_availability')) {
        $check_result = function_exists('ryokourent_with_motor_lock')
            ? ryokourent_with_motor_lock($motor_id, function () use ($motor_id, $clean) {
                return ryokourent_check_availability($motor_id, $clean['start_datetime'], $clean['end_datetime']);
            })
            : ryokourent_check_availability($motor_id, $clean['start_datetime'], $clean['end_datetime']);

        if (is_wp_error($check_result)) {
            return array(
                'success'    => false,
                'code'       => 'concurrency_busy',
                'message'    => $check_result->get_error_message(),
                'errors'     => array('rented_motor_id' => $check_result->get_error_message()),
                'status'     => 429,
                'clean_data' => array(),
            );
        }

        if (!$check_result) {
            return array(
                'success'    => false,
                'code'       => 'unit_fully_booked',
                'message'    => __('Armada ini telah terpesan penuh pada jadwal tersebut. Silakan pilih armada lain atau sesuaikan jadwal Anda.', 'ryokourent'),
                'errors'     => array('rented_motor_id' => __('Armada telah terpesan penuh pada jadwal tersebut.', 'ryokourent')),
                'status'     => 400,
                'clean_data' => array(),
            );
        }
    }

    // 8. Calculate Server-Side Authoritative Pricing (Tamper-Proof)
    if (function_exists('ryokourent_calculate_booking_quote')) {
        $quote = ryokourent_calculate_booking_quote($motor_id, $start_raw, $end_raw);
        $clean['total_price']           = $quote['total_price'];
        $clean['formatted_price']       = $quote['formatted_price'];
        $clean['requires_consultation'] = !empty($quote['requires_consultation']);
        $clean['price_breakdown']       = isset($quote['breakdown']) ? $quote['breakdown'] : array();
    } else {
        $clean['total_price']           = 0;
        $clean['formatted_price']       = 'Rp 0';
        $clean['requires_consultation'] = false;
        $clean['price_breakdown']       = array();
    }

    // 8. Validate Pickup Location
    $pickup = isset($raw_data['pickup_location']) ? sanitize_text_field(wp_unslash($raw_data['pickup_location'])) : '';
    $allowed_pickups = array('Pool Dinoyo', 'Pool Batu', 'Stasiun Malang', 'Hotel/Homestay');
    if (empty($pickup) || !in_array($pickup, $allowed_pickups, true)) {
        $pickup = 'Pool Dinoyo';
    }
    $clean['pickup_location'] = $pickup;

    // 9. Destination Route
    $route = isset($raw_data['trip_destination']) ? sanitize_key(wp_unslash($raw_data['trip_destination'])) : 'malang_batu';
    if ($route !== 'bromo') {
        $route = 'malang_batu';
    }
    $clean['trip_destination'] = $route;

    // 10. Rental Notes (Optional)
    $notes = isset($raw_data['rental_notes']) ? sanitize_text_field(wp_unslash($raw_data['rental_notes'])) : '';
    $clean['rental_notes'] = $notes;

    return array(
        'success'    => true,
        'code'       => 'validation_passed',
        'message'    => __('Data identitas pelanggan dan formulir valid.', 'ryokourent'),
        'errors'     => array(),
        'status'     => 200,
        'clean_data' => $clean,
    );
}

/**
 * AJAX Handler for Real-Time Rental Duration Calculation.
 *
 * @since 1.0.0
 * @return void Sends JSON response.
 */
function ryokourent_ajax_calculate_duration() {
    $start_str = isset($_POST['start_datetime']) ? sanitize_text_field(wp_unslash($_POST['start_datetime'])) : '';
    $end_str   = isset($_POST['end_datetime']) ? sanitize_text_field(wp_unslash($_POST['end_datetime'])) : '';

    $result = ryokourent_validate_rental_schedule($start_str, $end_str);

    if (!$result['is_valid']) {
        wp_send_json_error(array(
            'message' => reset($result['errors']),
            'errors'  => $result['errors'],
        ), 400);
    }

    wp_send_json_success(array(
        'duration_hours' => $result['duration_hours'],
        'billable_days'  => $result['billable_days'],
        'summary_label'  => $result['summary_label'],
        'start_iso'      => $result['start_iso'],
        'end_iso'        => $result['end_iso'],
    ), 200);
}
add_action('wp_ajax_ryokourent_calculate_duration', 'ryokourent_ajax_calculate_duration');
add_action('wp_ajax_nopriv_ryokourent_calculate_duration', 'ryokourent_ajax_calculate_duration');

/**
 * Generate a unique, recognizable booking code for Ryokourent.
 *
 * Format: RYK-YYYYMMDD-XXXX (e.g. RYK-20261001-A4B7).
 *
 * @since 1.0.0
 * @return string Unique uppercase booking code.
 */
function ryokourent_generate_booking_code() {
    global $wpdb;

    $tz = function_exists('ryokourent_get_timezone') ? ryokourent_get_timezone() : new DateTimeZone('Asia/Jakarta');
    try {
        $now = new DateTime('now', $tz);
        $date_prefix = $now->format('Ymd');
    } catch (Exception $e) {
        $date_prefix = gmdate('Ymd');
    }

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $rand_str = function_exists('wp_generate_password')
            ? strtoupper(wp_generate_password(4, false, false))
            : strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));

        $candidate_code = 'RYK-' . $date_prefix . '-' . $rand_str;

        // Check uniqueness in database if postmeta table accessible
        if (is_object($wpdb) && method_exists($wpdb, 'get_var')) {
            $meta_table = isset($wpdb->postmeta) ? $wpdb->postmeta : $wpdb->prefix . 'postmeta';
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$meta_table} WHERE meta_key = '_ryokou_booking_code' AND meta_value = %s LIMIT 1",
                $candidate_code
            ));
            if (!$existing) {
                return $candidate_code;
            }
        } else {
            return $candidate_code;
        }
    }

    // Fallback if loop exhausted
    return 'RYK-' . $date_prefix . '-' . strtoupper(substr(md5(microtime()), 0, 4));
}

/**
 * Persist validated booking data to CPT 'penyewaan' with status 'status_menunggu'.
 *
 * Executes inside atomic motor lock to guarantee zero double-booking race condition.
 *
 * @since 1.0.0
 * @param array $clean_data Validated clean form data from ryokourent_validate_booking_submission().
 * @return array Array with keys: 'success' (bool), 'booking_id' (int), 'booking_code' (string), 'message' (string), 'code' (string).
 */
function ryokourent_save_booking_entry($clean_data) {
    if (empty($clean_data) || !is_array($clean_data)) {
        return array(
            'success' => false,
            'code'    => 'empty_data',
            'message' => __('Data pemesanan tidak valid.', 'ryokourent'),
        );
    }

    $motor_id = isset($clean_data['rented_motor_id']) ? absint($clean_data['rented_motor_id']) : 0;
    if ($motor_id <= 0) {
        return array(
            'success' => false,
            'code'    => 'invalid_motor',
            'message' => __('Pilihan armada tidak valid.', 'ryokourent'),
        );
    }

    $save_logic = function () use ($clean_data, $motor_id) {
        // Final availability verification inside atomic lock
        if (function_exists('ryokourent_check_availability')) {
            $is_available = ryokourent_check_availability(
                $motor_id,
                $clean_data['start_datetime'],
                $clean_data['end_datetime']
            );
            if (!$is_available) {
                return array(
                    'success' => false,
                    'code'    => 'unit_fully_booked',
                    'message' => __('Armada ini telah terpesan penuh pada jadwal tersebut. Silakan pilih armada lain atau sesuaikan jadwal Anda.', 'ryokourent'),
                );
            }
        }

        // Generate unique booking code
        $booking_code  = ryokourent_generate_booking_code();
        $customer_name = isset($clean_data['customer_name']) ? sanitize_text_field($clean_data['customer_name']) : 'Pelanggan';
        $post_title    = sprintf('%s - %s', $booking_code, $customer_name);
        $now_wib       = function_exists('ryokourent_get_now_wib') ? ryokourent_get_now_wib() : gmdate('Y-m-d H:i:s');

        $post_data = array(
            'post_type'    => 'penyewaan',
            'post_status'  => 'status_menunggu',
            'post_title'   => $post_title,
            'post_content' => '',
            'post_date'    => $now_wib,
        );

        $booking_id = wp_insert_post($post_data);

        if (is_wp_error($booking_id) || !$booking_id) {
            return array(
                'success' => false,
                'code'    => 'db_insert_failed',
                'message' => __('Gagal menyimpan data transaksi pemesanan ke database.', 'ryokourent'),
            );
        }

        // Save CPT 'penyewaan' metadata per DATA_MODEL.md specification
        update_post_meta($booking_id, '_ryokou_booking_code', $booking_code);
        update_post_meta($booking_id, '_ryokou_booking_name', $clean_data['customer_name']);
        update_post_meta($booking_id, '_ryokou_booking_whatsapp', $clean_data['customer_whatsapp']);
        update_post_meta($booking_id, '_ryokou_booking_emergency', $clean_data['customer_emergency_phone']);
        update_post_meta($booking_id, '_ryokou_booking_ktp_address', $clean_data['customer_ktp_address']);
        update_post_meta($booking_id, '_ryokou_booking_stay_address', $clean_data['customer_stay_address']);
        update_post_meta($booking_id, '_ryokou_booking_social_media', isset($clean_data['customer_social_media']) ? $clean_data['customer_social_media'] : '');
        update_post_meta($booking_id, '_ryokou_booking_motor_id', $motor_id);
        update_post_meta($booking_id, '_ryokou_booking_pickup_loc', $clean_data['pickup_location']);
        update_post_meta($booking_id, '_ryokou_booking_trip_destination', $clean_data['trip_destination']);
        update_post_meta($booking_id, '_ryokou_booking_start_datetime', $clean_data['start_datetime']);
        update_post_meta($booking_id, '_ryokou_booking_end_datetime', $clean_data['end_datetime']);
        update_post_meta($booking_id, '_ryokou_booking_total_days', isset($clean_data['total_days']) ? absint($clean_data['total_days']) : 1);
        update_post_meta($booking_id, '_ryokou_booking_duration_hours', isset($clean_data['duration_hours']) ? (float) $clean_data['duration_hours'] : 0.0);
        update_post_meta($booking_id, '_ryokou_booking_duration_label', isset($clean_data['duration_label']) ? $clean_data['duration_label'] : '');
        update_post_meta($booking_id, '_ryokou_booking_total_price', isset($clean_data['total_price']) ? (int) $clean_data['total_price'] : 0);
        update_post_meta($booking_id, '_ryokou_booking_price_breakdown', isset($clean_data['price_breakdown']) ? $clean_data['price_breakdown'] : array());
        update_post_meta($booking_id, '_ryokou_booking_notes', isset($clean_data['rental_notes']) ? $clean_data['rental_notes'] : '');
        update_post_meta($booking_id, '_ryokou_booking_allocated_plate', ''); // Kosong sebelum konfirmasi admin
        update_post_meta($booking_id, '_ryokou_booking_created_at', $now_wib);

        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!empty($client_ip)) {
            update_post_meta($booking_id, '_ryokou_booking_ip_address', $client_ip);
        }

        return array(
            'success'      => true,
            'code'         => 'booking_saved',
            'booking_id'   => $booking_id,
            'booking_code' => $booking_code,
            'message'      => sprintf(__('Pesanan #%s berhasil disimpan ke sistem.', 'ryokourent'), $booking_code),
        );
    };

    if (function_exists('ryokourent_with_motor_lock')) {
        $result = ryokourent_with_motor_lock($motor_id, $save_logic);
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'code'    => 'lock_busy',
                'message' => $result->get_error_message(),
            );
        }
        return $result;
    }

    return call_user_func($save_logic);
}

/**
 * AJAX Handler for Fetching a Fresh Nonce on Cached Pages.
 *
 * Compatible with LiteSpeed Cache / WP Rocket / Cloudflare edge caching.
 *
 * @since 1.0.0
 * @return void Sends JSON response with fresh nonce token.
 */
function ryokourent_ajax_refresh_nonce() {
    if (!headers_sent()) {
        header('X-LiteSpeed-Cache-Control: no-cache');
        nocache_headers();
    }

    wp_send_json_success(array(
        'nonce' => wp_create_nonce('ryokourent_booking_form_action'),
    ), 200);
}
add_action('wp_ajax_ryokourent_refresh_nonce', 'ryokourent_ajax_refresh_nonce');
add_action('wp_ajax_nopriv_ryokourent_refresh_nonce', 'ryokourent_ajax_refresh_nonce');

/**
 * AJAX Handler for Booking Form Submission & Database Storage.
 *
 * Persists data to CPT 'penyewaan' with status 'status_menunggu',
 * generates unique RYK- booking code, resolves WhatsApp deep link, and returns JSON.
 *
 * Supported Hooks:
 * - wp_ajax_ryokourent_process_booking & wp_ajax_nopriv_ryokourent_process_booking
 * - wp_ajax_ryokourent_submit_booking & wp_ajax_nopriv_ryokourent_submit_booking (alias)
 *
 * @since 1.0.0
 * @return void Sends JSON response and exits.
 */
function ryokourent_ajax_process_booking() {
    // Disable cache on LiteSpeed / Nginx
    if (!headers_sent()) {
        header('X-LiteSpeed-Cache-Control: no-cache');
        nocache_headers();
    }

    $validation = ryokourent_validate_booking_submission($_POST);

    if (!$validation['success']) {
        $error_response = array(
            'code'    => $validation['code'],
            'message' => $validation['message'],
            'errors'  => $validation['errors'],
        );
        if (!empty($validation['refreshed_nonce'])) {
            $error_response['refreshed_nonce'] = $validation['refreshed_nonce'];
        }
        wp_send_json_error($error_response, $validation['status']);
    }

    // Persist booking to database (CPT 'penyewaan')
    $saved = ryokourent_save_booking_entry($validation['clean_data']);

    if (!$saved['success']) {
        wp_send_json_error(array(
            'code'    => $saved['code'],
            'message' => $saved['message'],
            'errors'  => array('general' => $saved['message']),
        ), 400);
    }

    $booking_id   = $saved['booking_id'];
    $booking_code = $saved['booking_code'];

    // Inject booking code into clean data for official WhatsApp generator
    $validation['clean_data']['booking_code'] = $booking_code;

    // Generate official WhatsApp URL & formatted message
    $wa_url = function_exists('ryokourent_get_whatsapp_url')
        ? ryokourent_get_whatsapp_url($validation['clean_data'])
        : '';
    $wa_message = function_exists('ryokourent_build_whatsapp_message')
        ? ryokourent_build_whatsapp_message($validation['clean_data'])
        : '';

    // Success response with validated data, database post ID, and official WhatsApp deep link
    wp_send_json_success(array(
        'code'         => 'booking_saved',
        'message'      => sprintf(__('Pesanan Anda #%s berhasil disimpan ke sistem! Menghubungkan ke WhatsApp Admin...', 'ryokourent'), $booking_code),
        'booking_id'   => $booking_id,
        'booking_code' => $booking_code,
        'clean_data'   => $validation['clean_data'],
        'wa_url'       => $wa_url,
        'wa_message'   => $wa_message,
    ), 200);
}
// Primary hooks (TASK-019 specification)
add_action('wp_ajax_ryokourent_process_booking', 'ryokourent_ajax_process_booking');
add_action('wp_ajax_nopriv_ryokourent_process_booking', 'ryokourent_ajax_process_booking');
// Alias hooks for backward compatibility
add_action('wp_ajax_ryokourent_submit_booking', 'ryokourent_ajax_process_booking');
add_action('wp_ajax_nopriv_ryokourent_submit_booking', 'ryokourent_ajax_process_booking');

// =============================================================================
// TASK-023: Perubahan Status Booking (aturan transisi, plat nomor, kuota)
// =============================================================================

/**
 * Matriks transisi status booking yang diizinkan (ADR-011).
 *
 * Alur lapangan: Menunggu -> Dikonfirmasi (admin approve) -> Berjalan (serah terima unit,
 * plat nomor diinput) -> Selesai (unit kembali). Pembatalan hanya sebelum unit dikirim,
 * yaitu dari Menunggu atau Dikonfirmasi. Selesai dan Dibatalkan bersifat final.
 *
 * @since 1.0.0
 * @return array Peta status asal => daftar status tujuan yang diizinkan.
 */
function ryokourent_get_status_transitions() {
    return array(
        'status_menunggu'     => array('status_dikonfirmasi', 'status_dibatalkan'),
        'status_dikonfirmasi' => array('status_berjalan', 'status_dibatalkan'),
        'status_berjalan'     => array('status_selesai', 'status_dibatalkan'),
        'status_selesai'      => array(),
        'status_dibatalkan'   => array(),
    );
}

/**
 * Cek apakah transisi status diizinkan oleh matriks.
 *
 * @since 1.0.0
 * @param string $from Status asal.
 * @param string $to   Status tujuan.
 * @return bool
 */
function ryokourent_is_allowed_status_transition($from, $to) {
    $map = ryokourent_get_status_transitions();
    return isset($map[$from]) && in_array($to, $map[$from], true);
}

/**
 * Normalisasi plat nomor untuk disimpan (huruf kapital, tanpa simbol, spasi tunggal).
 *
 * @since 1.0.0
 * @param string $raw Input mentah.
 * @return string
 */
function ryokourent_normalize_plate_for_storage($raw) {
    $clean = sanitize_text_field((string) $raw);
    $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $clean);
    $clean = preg_replace('/\s+/', ' ', $clean);
    return strtoupper(trim($clean));
}

/**
 * Bentuk array hasil standar.
 *
 * @param bool   $success Berhasil atau tidak.
 * @param string $code    Kode mesin.
 * @param string $message Pesan untuk operator.
 * @param array  $extra   Data tambahan.
 * @return array
 */
function ryokourent_status_result($success, $code, $message, $extra = array()) {
    return array_merge(
        array('success' => (bool) $success, 'code' => $code, 'message' => $message, 'plate' => ''),
        $extra
    );
}

/**
 * Validasi aturan transisi status (tanpa mengubah data). Dipakai bersama oleh
 * quick action dan jalur dropdown metabox agar aturannya tidak bisa dilewati.
 *
 * Aturan:
 * 1. Status tujuan harus salah satu dari 5 status resmi.
 * 2. Status sama = tidak ada perubahan (code 'no_change', success true).
 * 3. Booking baru (belum punya status resmi) hanya boleh dimulai dari Menunggu.
 * 4. Transisi harus ada di ryokourent_get_status_transitions().
 * 5. Menuju Berjalan: plat nomor wajib, terdaftar pada model motor, dan tidak
 *    bertabrakan dengan sewa aktif lain (ryokourent_validate_allocated_plate).
 *
 * Kuota untuk Dikonfirmasi TIDAK dicek di sini (butuh lock); lihat
 * ryokourent_transition_booking_status() dan ryokourent_intercept_booking_confirmation().
 *
 * @since 1.0.0
 * @param int    $booking_id ID booking.
 * @param string $from       Status asal.
 * @param string $to         Status tujuan.
 * @param string $plate      Plat nomor mentah (dipakai bila tujuan Berjalan).
 * @return array Hasil standar; 'plate' berisi plat ternormalisasi bila tujuan Berjalan.
 */
function ryokourent_validate_status_transition($booking_id, $from, $to, $plate = '') {
    $map = ryokourent_get_status_transitions();

    if (!isset($map[$to])) {
        return ryokourent_status_result(false, 'invalid_status', __('Status tujuan tidak dikenal.', 'ryokourent'));
    }

    if ($from === $to) {
        return ryokourent_status_result(true, 'no_change', '');
    }

    if (!isset($map[$from])) {
        if ('status_menunggu' === $to) {
            return ryokourent_status_result(true, 'new_booking', '');
        }
        return ryokourent_status_result(false, 'invalid_transition', __('Booking baru harus dimulai dari status Menunggu Konfirmasi.', 'ryokourent'));
    }

    if (!ryokourent_is_allowed_status_transition($from, $to)) {
        return ryokourent_status_result(false, 'invalid_transition', __('Perubahan status ini tidak diizinkan. Alur: Menunggu, Dikonfirmasi, Berjalan, Selesai. Pembatalan hanya sebelum unit diserahkan.', 'ryokourent'));
    }

    if ('status_berjalan' === $to) {
        $booking_id = absint($booking_id);
        $clean      = ryokourent_normalize_plate_for_storage($plate);
        if ('' === $clean) {
            return ryokourent_status_result(false, 'plate_required', __('Plat nomor unit wajib diisi saat serah terima (status Berjalan).', 'ryokourent'));
        }

        $check = ryokourent_validate_allocated_plate(
            (int) get_post_meta($booking_id, '_ryokou_booking_motor_id', true),
            $clean,
            (string) get_post_meta($booking_id, '_ryokou_booking_start_datetime', true),
            (string) get_post_meta($booking_id, '_ryokou_booking_end_datetime', true),
            $booking_id
        );
        if (empty($check['is_valid'])) {
            return ryokourent_status_result(false, 'plate_invalid', $check['warning'], array('plate' => $clean));
        }

        return ryokourent_status_result(true, 'ok', '', array('plate' => $clean));
    }

    return ryokourent_status_result(true, 'ok', '');
}

/**
 * Ubah status booking secara aman (jalur quick action).
 *
 * - Menuju Dikonfirmasi: cek ulang kuota (kecualikan booking itu sendiri) di dalam
 *   ryokourent_with_motor_lock; tolak bila penuh.
 * - Menuju Berjalan: plat wajib dan divalidasi di dalam lock; plat disimpan bersama status.
 * - Cache dashboard dibuang oleh hook transition_post_status (admin/dashboard.php).
 *
 * Otorisasi (nonce + capability) adalah tanggung jawab pemanggil.
 *
 * @since 1.0.0
 * @param int    $booking_id ID booking (CPT penyewaan).
 * @param string $new_status Status tujuan.
 * @param string $plate      Plat nomor mentah (wajib bila tujuan Berjalan).
 * @return array Hasil standar: success, code, message, plate.
 */
function ryokourent_transition_booking_status($booking_id, $new_status, $plate = '') {
    $booking_id = absint($booking_id);
    $new_status = sanitize_key($new_status);

    if ($booking_id <= 0 || 'penyewaan' !== get_post_type($booking_id)) {
        return ryokourent_status_result(false, 'invalid_booking', __('Data pemesanan tidak valid.', 'ryokourent'));
    }

    $old_status = (string) get_post_status($booking_id);

    $apply = function () use ($booking_id, $new_status, $old_status, $plate) {
        $check = ryokourent_validate_status_transition($booking_id, $old_status, $new_status, $plate);
        if (!$check['success']) {
            return $check;
        }
        if ('no_change' === $check['code']) {
            return ryokourent_status_result(false, 'no_change', __('Pemesanan sudah berstatus tersebut.', 'ryokourent'));
        }

        if ('status_dikonfirmasi' === $new_status) {
            $motor_id = (int) get_post_meta($booking_id, '_ryokou_booking_motor_id', true);
            $start    = (string) get_post_meta($booking_id, '_ryokou_booking_start_datetime', true);
            $end      = (string) get_post_meta($booking_id, '_ryokou_booking_end_datetime', true);
            if (!ryokourent_check_availability($motor_id, $start, $end, $booking_id)) {
                return ryokourent_status_result(false, 'quota_full', __('Gagal mengonfirmasi: kuota armada motor sudah penuh pada jadwal sewa tersebut.', 'ryokourent'));
            }
        }

        $previous_plate = '';
        if ('status_berjalan' === $new_status) {
            $previous_plate = (string) get_post_meta($booking_id, '_ryokou_booking_allocated_plate', true);
            update_post_meta($booking_id, '_ryokou_booking_allocated_plate', $check['plate']);
        }

        // Tandai agar intercept transition_post_status tidak mengulang cek kuota.
        $GLOBALS['ryokourent_status_validated'][$booking_id] = true;
        $updated = wp_update_post(array('ID' => $booking_id, 'post_status' => $new_status), true);
        unset($GLOBALS['ryokourent_status_validated'][$booking_id]);

        if (is_wp_error($updated) || !$updated) {
            if ('status_berjalan' === $new_status) {
                update_post_meta($booking_id, '_ryokou_booking_allocated_plate', $previous_plate);
            }
            return ryokourent_status_result(false, 'update_failed', __('Gagal memperbarui status pemesanan di database.', 'ryokourent'));
        }

        return ryokourent_status_result(true, 'updated', __('Status pemesanan berhasil diperbarui.', 'ryokourent'), array('plate' => $check['plate']));
    };

    // Kuota dan plat hanya perlu dilindungi lock pada dua transisi ini.
    if (in_array($new_status, array('status_dikonfirmasi', 'status_berjalan'), true)) {
        $motor_id = (int) get_post_meta($booking_id, '_ryokou_booking_motor_id', true);
        if ($motor_id <= 0) {
            return ryokourent_status_result(false, 'missing_booking_meta', __('Data armada pemesanan tidak lengkap.', 'ryokourent'));
        }
        $result = ryokourent_with_motor_lock($motor_id, $apply);
        if (is_wp_error($result)) {
            return ryokourent_status_result(false, 'lock_busy', $result->get_error_message());
        }
        return $result;
    }

    return $apply();
}

/**
 * Mendapatkan informasi status overtime (keterlambatan pengembalian unit) untuk admin.
 *
 * Aturan Bisnis:
 * - Overtime waktu (jam) TETAP dihitung karena terkait stok fisik unit yang masih di luar
 *   dan notifikasi penting ke dashboard/daftar booking admin.
 * - Denda TIDAK dihitung otomatis (denda_auto = 0), melainkan ditentukan secara manual oleh admin.
 *
 * @since 1.0.0
 * @param int $booking_id ID booking CPT 'penyewaan'.
 * @return array{is_overdue:bool,overdue_hours:float,label:string,auto_fine:int}
 */
function ryokourent_get_booking_overtime_info($booking_id) {
    $booking_id = absint($booking_id);
    if ($booking_id <= 0 || 'status_berjalan' !== get_post_status($booking_id)) {
        return array(
            'is_overdue'    => false,
            'overdue_hours' => 0.0,
            'label'         => '',
            'auto_fine'     => 0,
        );
    }

    $end_raw = (string) get_post_meta($booking_id, '_ryokou_booking_end_datetime', true);
    if (empty($end_raw)) {
        return array(
            'is_overdue'    => false,
            'overdue_hours' => 0.0,
            'label'         => '',
            'auto_fine'     => 0,
        );
    }

    $tz = function_exists('ryokourent_get_timezone') ? ryokourent_get_timezone() : new DateTimeZone('Asia/Jakarta');

    try {
        $end_dt = new DateTime($end_raw, $tz);
        $now_dt = new DateTime('now', $tz);

        if ($now_dt > $end_dt) {
            $diff_seconds = $now_dt->getTimestamp() - $end_dt->getTimestamp();
            $hours = round($diff_seconds / 3600, 1);

            return array(
                'is_overdue'    => true,
                'overdue_hours' => $hours,
                'label'         => sprintf(__('Terlambat +%s Jam', 'ryokourent'), number_format($hours, 1, ',', '.')),
                'auto_fine'     => 0, // Denda tidak dihitung otomatis, dihitung manual oleh admin
            );
        }
    } catch (Exception $e) {
        // Fallback jika format tanggal tidak valid
    }

    return array(
        'is_overdue'    => false,
        'overdue_hours' => 0.0,
        'label'         => '',
        'auto_fine'     => 0,
    );
}

/**
 * Perpanjang durasi masa sewa (Extend Rental) oleh Admin.
 *
 * Aturan Bisnis:
 * - Default perpanjangan: +1 Hari (24 Jam), dengan opsi pilihan jumlah hari ($extra_days).
 * - Jika admin memilih opsi perpanjangan saat overtime, maka waktu keterlambatan tersebut
 *   menjadi akumulasi perpanjangan sewa resmi (bukan overtime lagi).
 * - Jadwal selesai sewa (end_datetime) dimajukan kelipatan 24 jam.
 * - Total hari dan total biaya sewa diakumulasikan.
 * - Kuota ketersediaan diverifikasi dengan lock untuk mencegah bentrok jadwal berikutnya.
 *
 * @since 1.0.0
 * @param int    $booking_id  ID booking CPT 'penyewaan'.
 * @param int    $extra_days  Jumlah hari perpanjangan (kelipatan 24 jam). Default 1.
 * @param string $admin_notes Catatan tambahan opsional.
 * @return array Hasil standar: success, code, message, new_end, total_price.
 */
function ryokourent_extend_rental_duration($booking_id, $extra_days = 1, $admin_notes = '') {
    $booking_id = absint($booking_id);
    $extra_days = max(1, absint($extra_days));

    if ($booking_id <= 0 || 'penyewaan' !== get_post_type($booking_id)) {
        return array(
            'success' => false,
            'code'    => 'invalid_booking',
            'message' => __('Data pemesanan tidak valid.', 'ryokourent'),
        );
    }

    $current_status = get_post_status($booking_id);
    if (!in_array($current_status, array('status_berjalan', 'status_dikonfirmasi'), true)) {
        return array(
            'success' => false,
            'code'    => 'invalid_status_for_extension',
            'message' => __('Perpanjangan sewa hanya dapat dilakukan untuk pesanan yang sedang berjalan atau sudah dikonfirmasi.', 'ryokourent'),
        );
    }

    $motor_id  = absint(get_post_meta($booking_id, '_ryokou_booking_motor_id', true));
    $start_raw = (string) get_post_meta($booking_id, '_ryokou_booking_start_datetime', true);
    $old_end   = (string) get_post_meta($booking_id, '_ryokou_booking_end_datetime', true);

    if ($motor_id <= 0 || empty($start_raw) || empty($old_end)) {
        return array(
            'success' => false,
            'code'    => 'incomplete_booking_meta',
            'message' => __('Data jadwal pemesanan tidak lengkap untuk perpanjangan.', 'ryokourent'),
        );
    }

    $tz = function_exists('ryokourent_get_timezone') ? ryokourent_get_timezone() : new DateTimeZone('Asia/Jakarta');

    try {
        $old_end_dt = new DateTime($old_end, $tz);
        $new_end_dt = clone $old_end_dt;
        $new_end_dt->modify('+' . $extra_days . ' days'); // Kelipatan 24 jam per hari
        $new_end_str = $new_end_dt->format('Y-m-d H:i');
    } catch (Exception $e) {
        return array(
            'success' => false,
            'code'    => 'datetime_parse_error',
            'message' => __('Format tanggal sewa tidak valid.', 'ryokourent'),
        );
    }

    $extend_logic = function () use ($booking_id, $motor_id, $start_raw, $old_end, $new_end_str, $extra_days, $admin_notes) {
        // Cek ketersediaan unit untuk jadwal sewa yang baru diperpanjang
        if (function_exists('ryokourent_check_availability')) {
            $is_available = ryokourent_check_availability($motor_id, $start_raw, $new_end_str, $booking_id);
            if (!$is_available) {
                return array(
                    'success' => false,
                    'code'    => 'unit_conflict',
                    'message' => sprintf(__('Gagal memperpanjang sewa: Unit motor ini telah memiliki reservasi lain yang terkonfirmasi setelah jadwal %s.', 'ryokourent'), $old_end),
                );
            }
        }

        // Ambil data tarif motor
        $rates = function_exists('ryokourent_get_motor_pricing') ? ryokourent_get_motor_pricing($motor_id) : array('daily' => 0);
        $daily_rate = isset($rates['daily']) ? (int) $rates['daily'] : 0;

        $old_total_days = absint(get_post_meta($booking_id, '_ryokou_booking_total_days', true));
        $new_total_days = max(1, $old_total_days + $extra_days);

        $old_hours = (float) get_post_meta($booking_id, '_ryokou_booking_duration_hours', true);
        $new_hours = $old_hours + ($extra_days * 24.0);

        $old_price = (int) get_post_meta($booking_id, '_ryokou_booking_total_price', true);

        // Hitung ulang tarif optimal atau tambahkan tarif harian
        if (function_exists('ryokourent_calculate_optimal_rental_price') && $daily_rate > 0) {
            $weekly_rate  = isset($rates['weekly']) ? (int) $rates['weekly'] : 0;
            $monthly_rate = isset($rates['monthly']) ? (int) $rates['monthly'] : 0;
            $optimal = ryokourent_calculate_optimal_rental_price($new_total_days, $daily_rate, $weekly_rate, $monthly_rate);
            $new_price = $optimal['total_price'];
        } else {
            $new_price = $old_price + ($daily_rate * $extra_days);
        }

        // Simpan perubahan ke post meta
        update_post_meta($booking_id, '_ryokou_booking_end_datetime', $new_end_str);
        update_post_meta($booking_id, '_ryokou_booking_total_days', $new_total_days);
        update_post_meta($booking_id, '_ryokou_booking_duration_hours', $new_hours);
        update_post_meta($booking_id, '_ryokou_booking_duration_label', sprintf(__('%d Hari (~%s Jam)', 'ryokourent'), $new_total_days, number_format($new_hours, 0)));
        update_post_meta($booking_id, '_ryokou_booking_total_price', $new_price);

        // Catat riwayat perpanjangan sewa
        $now_wib = function_exists('ryokourent_get_now_wib') ? ryokourent_get_now_wib() : gmdate('Y-m-d H:i:s');
        $history = get_post_meta($booking_id, '_ryokou_extension_history', true);
        if (!is_array($history)) {
            $history = array();
        }
        $history[] = array(
            'extended_at' => $now_wib,
            'extra_days'  => $extra_days,
            'old_end'     => $old_end,
            'new_end'     => $new_end_str,
            'notes'       => sanitize_text_field($admin_notes),
            'admin_user'  => function_exists('get_current_user_id') ? get_current_user_id() : 0,
        );
        update_post_meta($booking_id, '_ryokou_extension_history', $history);

        return array(
            'success'     => true,
            'code'        => 'rental_extended',
            'message'     => sprintf(__('Sewa berhasil diperpanjang +%d Hari (24 Jam) hingga %s. Waktu tersebut terakumulasi sebagai sewa resmi.', 'ryokourent'), $extra_days, $new_end_str),
            'new_end'     => $new_end_str,
            'total_days'  => $new_total_days,
            'total_price' => $new_price,
        );
    };

    if (function_exists('ryokourent_with_motor_lock')) {
        return ryokourent_with_motor_lock($motor_id, $extend_logic);
    }

    return call_user_func($extend_logic);
}

/**
 * Batalkan pemesanan sewa oleh Admin dengan catatan alasan pembatalan.
 *
 * Aturan Bisnis:
 * - Pembatalan dilakukan oleh Admin via tombol "Cancel Booking".
 * - Menyediakan kolom catatan alasan pembatalan opsional.
 * - Mengubah status menjadi 'status_dibatalkan' dan membebaskan alokasi stok unit seketika.
 *
 * @since 1.0.0
 * @param int    $booking_id          ID booking CPT 'penyewaan'.
 * @param string $cancellation_reason Alasan pembatalan (opsional).
 * @return array Hasil standar status.
 */
function ryokourent_cancel_booking($booking_id, $cancellation_reason = '') {
    $booking_id = absint($booking_id);
    if ($booking_id <= 0 || 'penyewaan' !== get_post_type($booking_id)) {
        return array(
            'success' => false,
            'code'    => 'invalid_booking',
            'message' => __('Data pemesanan tidak valid.', 'ryokourent'),
        );
    }

    $now_wib = function_exists('ryokourent_get_now_wib') ? ryokourent_get_now_wib() : gmdate('Y-m-d H:i:s');
    $reason  = sanitize_text_field(wp_unslash($cancellation_reason));

    if (!empty($reason)) {
        update_post_meta($booking_id, '_ryokou_cancellation_reason', $reason);
    }
    update_post_meta($booking_id, '_ryokou_cancelled_at', $now_wib);

    // Ubah status ke status_dibatalkan
    $result = ryokourent_transition_booking_status($booking_id, 'status_dibatalkan');

    if ($result['success']) {
        $result['message'] = __('Pemesanan berhasil dibatalkan. Kuota unit motor telah dikembalikan ke sistem.', 'ryokourent');
    }

    return $result;
}
