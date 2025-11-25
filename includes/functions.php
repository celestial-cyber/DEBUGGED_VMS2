<?php
/**
 * Common Utility Functions
 * 
 * This file contains reusable functions used throughout the application
 */

/**
 * Sanitize input string
 * 
 * @param string $input The input string to sanitize
 * @return string Sanitized string
 */
function sanitize_input($input) {
    return trim(strip_tags($input ?? ''));
}

/**
 * Validate email address
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validate_phone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10;
}

/**
 * Get current user ID from session
 * 
 * @return int|null User ID or null if not logged in
 */
function get_current_user_id() {
    return $_SESSION['id'] ?? null;
}

/**
 * Get current user name from session
 * 
 * @return string|null User name or null if not logged in
 */
function get_current_user_name() {
    return $_SESSION['name'] ?? null;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['id']) && !empty($_SESSION['id']);
}

/**
 * Redirect to a page
 * 
 * @param string $url URL to redirect to
 * @param int $status_code HTTP status code (default: 302)
 */
function redirect($url, $status_code = 302) {
    header("Location: $url", true, $status_code);
    exit;
}

/**
 * Display success message
 * 
 * @param string $message Success message
 */
function set_success_message($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Display error message
 * 
 * @param string $message Error message
 */
function set_error_message($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 * 
 * @return string|null Success message or null
 */
function get_success_message() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear error message
 * 
 * @return string|null Error message or null
 */
function get_error_message() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format (default: 'M d, Y')
 * @return string Formatted date
 */
function format_date($date, $format = 'M d, Y') {
    if (empty($date)) {
        return '—';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * 
 * @param string $datetime Datetime string
 * @param string $format Datetime format (default: 'M d, Y h:i A')
 * @return string Formatted datetime
 */
function format_datetime($datetime, $format = 'M d, Y h:i A') {
    if (empty($datetime)) {
        return '—';
    }
    return date($format, strtotime($datetime));
}

/**
 * Escape output for HTML
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get pagination offset
 * 
 * @param int $page Current page number
 * @param int $per_page Items per page
 * @return int Offset value
 */
function get_pagination_offset($page, $per_page = 10) {
    $page = max(1, (int)$page);
    return ($page - 1) * $per_page;
}

/**
 * Generate pagination HTML
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $base_url Base URL for pagination links
 * @return string HTML for pagination
 */
function generate_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav><ul class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $prev = $current_page - 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . e($base_url) . '?page=' . $prev . '">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . e($base_url) . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next = $current_page + 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . e($base_url) . '?page=' . $next . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

