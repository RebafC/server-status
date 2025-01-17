<?php

declare(strict_types=1);

// Inclusion of namespace will not cause any issue even if PHPMailer is not used
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// TODO Any other way to handle this include? Should we just do the include form each index.php?
if (file_exists('libs/php_idn/idna.php')) {
    require_once __DIR__ . '/libs/php_idn/idna.php';
} else {
    // Include for Admin section
    require_once __DIR__ . '/../libs/php_idn/idna.php';
}

class mailer
{
    /**
     * Generic function to submit mail messages via mail og PHPmailer().
     *
     * @param string $to      Email address to which mail should be sent
     * @param string $message Body of email
     * @param bool   $html    Set to true if we are sending HTML Mailer
     * @param mixed  $subject
     *
     * @return bool True if success
     */
    public function send_mail($to, $subject, $message, $html = true): bool
    {
        // TODO -Handle $to as an array in order to send to muliple recipients without having
        //       to call the entire send_mail function over and over..

        $content_type = ($html) ? 'text/html' : 'text/plain';

        // Convert IDN/punycode domain to ascii
        // TODO Handle IDN in left hand side of email address
        if ($this->is_utf8($to)) {
            $elements = explode('@', $to);
            $domainpart = EncodePunycodeIDN(array_pop($elements));  // Convert domain part to ascii
            $to = $elements[0] . '@' . $domainpart;  // Reassemble tge full email address
        }

        // Send using PHP mailer if it is enabled
        if (PHP_MAILER) {
            require_once PHP_MAILER_PATH . '/Exception.php'; // Exception class.

            require_once PHP_MAILER_PATH . '/PHPMailer.php'; // The main PHPMailer class.

            if (PHP_MAILER_SMTP) {
                require_once PHP_MAILER_PATH . '/SMTP.php';  // SMTP class, needed if you want to use SMTP.
            }

            $phpMailer = new PHPMailer(false);

            $phpMailer->setFrom(MAILER_ADDRESS, MAILER_NAME);
            $phpMailer->addReplyTo(MAILER_ADDRESS, MAILER_NAME);
            // $phpmail->Debugoutput = error_log;

            // Define SMTP parameters if enabled
            if (PHP_MAILER_SMTP) {
                $phpMailer->isSMTP();
                $phpMailer->Host = PHP_MAILER_HOST;
                $phpMailer->Port = PHP_MAILER_PORT;
                $phpMailer->SMTPSecure = PHP_MAILER_SECURE;
                // $phpmail->SMTPDebug = 2; // Enable for debugging

                // Handle authentication for SMTP if enabled
                if (!empty(PHP_MAILER_USER)) {
                    $phpMailer->SMTPAuth = true;
                    $phpMailer->Username = PHP_MAILER_USER;
                    $phpMailer->Password = PHP_MAILER_PASS;
                }
            }

            $phpMailer->addAddress($to);
            $phpMailer->Subject = $subject;
            // Send HMTL mail
            if ($html) {
                $phpMailer->msgHtml($message);
                $phpMailer->AltBody = $this->convert_html_to_plain_txt($message, false);
            } else {
                $phpMailer->Body = $message;  // Send plain text
            }

            $phpMailer->isHtml($html);

            // use htmlmail if enabled
            // TODO Log error message $phpmail->ErrorInfo;
            return (bool) $phpMailer->send();
        }

        // Use standard PHP mail() function
        $headers = sprintf('Content-Type: %s; "charset=utf-8" ', $content_type) . PHP_EOL;
        $headers .= 'MIME-Version: 1.0 ' . PHP_EOL;
        $headers .= 'From: ' . MAILER_NAME . ' <' . MAILER_ADDRESS . '>' . PHP_EOL;
        $headers .= 'Reply-To: ' . MAILER_NAME . ' <' . MAILER_ADDRESS . '>' . PHP_EOL;

        mail($to, $subject, $message, $headers);

        // TODO log error message if mail fails
        return true;
    }

    /**
     * Tries to verify the domain using dns request against an MX record of the domain part
     * of the passed email address. The code also handles IDN/Punycode formatted addresses which
     * contains utf8 characters.
     * Original code from https://stackoverflow.com/questions/19261987/how-to-check-if-an-email-address-is-real-or-valid-using-php/19262381.
     *
     * @param string $email Email address to check
     *
     * @return bool True if MX record exits, false if otherwise
     */
    public function verify_domain($email): bool
    {
        // TODO - Handle idn/punycode domain names without being dependent on PHP native libs.
        $domain = explode('@', $email);
        $domain = EncodePunycodeIDN(array_pop($domain) . '.');  // Add dot at end of domain to avoid local domain lookups
        syslog(1, $domain);

        return checkdnsrr($domain, 'MX');
    }

    /**
     * Check if string contains non-english characters (detect IDN/Punycode enabled domains)
     * Original code from: https://stackoverflow.com/questions/13120475/detect-non-english-chars-in-a-string.
     *
     * @param string $str String to check for extended characters
     *
     * @return bool True if extended characters, false otherwise
     */
    public function is_utf8($str): bool
    {
        return strlen($str) !== strlen(utf8_decode($str));
    }

    /**
     * Takes the input from an HTML email and convert it to plain text
     * This is commonly used when sending HTML emails as a backup for email clients who can only view, or who choose to only view,
     * Original code from https://github.com/DukeOfMarshall/PHP---JSON-Email-Verification/blob/master/EmailVerify.class.php
     * plain text emails.
     *
     * @param string $content      the body part of the email to convert to plain text
     * @param bool   $remove_links Set to true if links should be removed from email
     *
     * @return string pain text version
     */
    public function convert_html_to_plain_txt($content, $remove_links = false): ?string
    {
        // TODO does not handle unsubscribe/manage subscription text very well.
        // Replace HTML line breaks with text line breaks
        $plain_text = str_ireplace(['<br>', '<br />'], "\n\r", $content);

        // Remove the content between the tags that wouldn't normally get removed with the strip_tags function
        $plain_text = preg_replace(['@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
        ], '', $plain_text); // Remove everything from between the tags that doesn't get removed with strip_tags function

        // If the user has chosen to preserve the addresses from links
        if (!$remove_links) {
            $plain_text = strip_tags(preg_replace('/<a href="(.*)">/', ' $1 ', $plain_text));
        }

        // Remove HTML spaces
        $plain_text = str_replace('&nbsp;', '', $plain_text);

        // Replace multiple line breaks with a single line break
        return preg_replace('/(\\s){3,}/', "\r\n\r\n", trim($plain_text));
    }
}
