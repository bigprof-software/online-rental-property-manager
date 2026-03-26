<?php

class TFA {
    // Moved configuration to definitions.php (OTP_EXPIRY_MINUTES) and membership_groups table

    public static function beginFlow() {
		// Emergency Bypass
		if(is_file(__DIR__ . '/../../admin/.disable_2fa')) return false;

		$memberInfo = Authentication::getUser();
		
		// Check if group requires 2FA
		$allow_2fa = sqlValue("SELECT `allow_2fa` FROM `membership_groups` WHERE `groupID`='{$memberInfo['groupID']}'");
        if(!$allow_2fa) return '';

		$rememberMe = Request::val('rememberMe') ? true : false;

		$rid = self::createRequest($memberInfo, $rememberMe);
		Authentication::signOut();
		return $rid;
    }

    public static function OTPexpiryMinutes() {
        // otp_expiry_minutes: OTP_EXPIRY_MINUTES if defined and between 1 and 30, else 5
        return (defined('OTP_EXPIRY_MINUTES') && OTP_EXPIRY_MINUTES >=1 && OTP_EXPIRY_MINUTES <=30) ? OTP_EXPIRY_MINUTES : 5;
    }

    public static function OTPlength() {
        // otp_length: OTP_LENGTH if defined and between 4 and 10, else 6
        return (defined('OTP_LENGTH') && OTP_LENGTH >=4 && OTP_LENGTH <=10) ? OTP_LENGTH : 6;
    }

    public static function placeholderOTP() {
        return str_repeat('0', self::OTPlength());
    }

    private static function createRequest($memberInfo, $rememberMe = false) {
        $expiry_seconds = self::OTPexpiryMinutes() * 60;
        
        $request_id = md5(uniqid(microtime(), true));

        $otp_length = self::OTPlength();

        $otp = str_pad(rand(0, pow(10, $otp_length) - 1), $otp_length, '0', STR_PAD_LEFT);
        $expiry = time() + $expiry_seconds;
        $remember_me_val = $rememberMe ? 1 : 0;
        
        // Clean up old requests
        $eo = ['silentErrors' => true];
        sql("DELETE FROM `membership_2fa_requests` WHERE `expiry_ts` < " . time(), $eo);

		if(!insert('membership_2fa_requests', [
			'request_id' => $request_id,
			'memberID' => $memberInfo['memberID'],
			'otp' => $otp,
			'expiry_ts' => $expiry,
			'remember_me' => $remember_me_val
		], $eo)) {
			return false;
		}
        
        // Send Email
        return self::sendEmail($memberInfo, $otp) ? $request_id : false;
    }

    private static function sendEmail($memberInfo, $otp) {
        global $Translation;

        $formattedOTP = '<div style="' . OTP_EMAIL_CSS . '">' . $otp . '</div>';

        return sendmail([
            'to' => $memberInfo['email'],
            'subject' => sprintf($Translation['otp_email_subject'], APP_TITLE),
            'message' => sprintf($Translation['otp_email_message'], $formattedOTP, self::OTPexpiryMinutes())
        ]);
    }

    public static function checkRequest($rid) {
        $eo = ['silentErrors' => true];
        $res = sql("SELECT * FROM `membership_2fa_requests` WHERE `request_id`='" . makeSafe($rid) . "' AND `expiry_ts` > " . time(), $eo);
        return ($res && db_num_rows($res) > 0) ? db_fetch_assoc($res) : false;
    }
    
    public static function deleteRequest($rid) {
        $eo = ['silentErrors' => true];
        sql("DELETE FROM `membership_2fa_requests` WHERE `request_id`='" . makeSafe($rid) . "'", $eo);
    }

    /**
     * Verifies the OTP and logs the user in if successful.
     * 
     * @param string $rid Request ID
     * @param string $submitted_otp The OTP entered by the user
     * @return string Status: 'success', 'expired', 'invalid'
     */
    public static function verify($rid, $submitted_otp) {
        // 1. Validate Request ID from DB
        $row = self::checkRequest($rid);
        if(!$row) return 'expired';

        // 2. Process OTP Comparison
        if($submitted_otp !== $row['otp']) {
			return 'invalid';
		}

		// SUCCESS: Login the user
		Authentication::signInAs($row['memberID']);
		
		// Handle "Remember Me"
		if(!empty($row['remember_me'])) {
			RememberMe::login($row['memberID']);
		}

		// Cleanup used token
		self::deleteRequest($rid);
		
		return 'success';
    }

    public static function enabledForAllGroups() {
        $numGroups = sqlValue("SELECT COUNT(1) FROM `membership_groups`");
        $numWith2FA = sqlValue("SELECT COUNT(1) FROM `membership_groups` WHERE `allow_2fa`=1");
        return ($numGroups > 0 && $numGroups == $numWith2FA);
    }

    public static function disabledForAllGroups() {
        $numWith2FA = sqlValue("SELECT COUNT(1) FROM `membership_groups` WHERE `allow_2fa`=1");
        return ($numWith2FA == 0);
    }
}
