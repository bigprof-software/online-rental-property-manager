<?php

/**
 * WindowMessages
 * 
 * A class for displaying messages to the user on the next page load.
 * 
 * Usage:
 * 
 * 1. To inject the window id into the output HTML, use output buffering, setting the callback to `WindowMessages::injectWindowId`:
 * 
 *    ```php
 *    ob_start('WindowMessages::injectWindowId');
 *    ```
 * 
 *    This will inject the window id into the output HTML, right after each `<form>` tag, as a hidden field.
 * 
 * 2. Add the following to your template file(s), where you want the messages to appear:
 * 
 *    ```
 *    <?php echo WindowMessages::getHtml(); ?>
 *    ```
 * 
 * 3. To add a message, use the `WindowMessages::add()` method:
 * 
 *    ```php
 *    WindowMessages::add('Hello world!', 'alert alert-success');
 *    ```
 * 
 *    The first parameter is the message to display, and the second parameter is the CSS classes to apply to the message <div> container.
 *    The message(s) would be displayed on the next page load, and are specific to the current browser window.
 *    That is, if you have multiple browser windows open, each window will have its own set of messages.
 * 
 *    To add a dismissable message, use the `WindowMessages::addDismissable()` method:
 * 
 *    ```php
 *    WindowMessages::addDismissable('Hello world!', 'alert alert-success');
 *    ```
 * 
 * 4. If you want to redirect the user to another page, without depending on the user submitting a form, 
 *    you can use the `WindowMessages::windowIdQuery()` method to get the window id as a query string, and append it to the URL:
 * 
 *    ```php
 *    $url .= (strpos($url, '?') === false ? '?' : '&') . WindowMessages::windowIdQuery();
 *    header("Location: $url");
 *    ```
 */

class WindowMessages {
	private static function getExistingOrNewWindowId() {
		$widFromRequest = Request::val('browser_window_id');
		if($widFromRequest) return $widFromRequest;

		// abort if ajax request
		if(is_ajax()) return;

		// generate a new window id, 12 characters long
		$wid = md5(uniqid(rand(), true));
		$wid = substr($wid, rand(0, 20), 12);

		// inject it into the request
		$_REQUEST['browser_window_id'] = $wid;

		// and into the session
		$_SESSION['window_messages'][$wid] = [];

		return $wid;
	}

	/**
	 * Injects the window id into the buffer, right after each <form> tag, as a hidden field.
	 * 
	 * @param string $buffer The original buffer.
	 * @return string The buffer with the window id injected.
	 * 
	 * @example `ob_start('WindowMessages::injectWindowId');`
	 */
	public static function injectWindowId($buffer) {
		$wid = self::getExistingOrNewWindowId();
		
		// if buffer already contains window id, do nothing
		if(strpos($buffer, 'name="browser_window_id" value="' . $wid) !== false) return $buffer;

		// inject window id into the buffer right after each <form> tag, as a hidden field
		$matches = 0;
		$buffer = preg_replace('/(<form[^>]*>)/i', '$1<input type="hidden" name="browser_window_id" value="' . $wid . '" />', $buffer, -1, $matches);

		return $buffer;
	}

	/**
	 * Adds a message specific to the current browser window to the session, to be displayed on the next page load.
	 * 
	 * @param string $msg The message to add.
	 * @param string $classes The CSS classes to add to the message. Example: `alert alert-success text-center`.
	 * 
	 * @example `WindowMessages::add('Hello world!', 'success');`
	 */
	public static function add($msg, $classes = 'alert alert-info') {
		$wid = self::getExistingOrNewWindowId();

		// add message to the session
		if($wid) $_SESSION['window_messages'][$wid][] = compact('msg', 'classes');
	}

	/**
	 * Adds a dismissable message specific to the current browser window to the session, to be displayed on the next page load.
	 * 
	 * @param string $msg The message to add.
	 * @param string $classes The CSS classes to add to the message. Example: `alert alert-success text-center`.
	 * 
	 * @example `WindowMessages::addDismissable('Hello world!', 'alert alert-success');`
	 */
	public static function addDismissable($msg, $classes = 'alert alert-info') {
		// add alert-dismissible class if not already present
		if(strpos($classes, 'alert-dismissible') === false) $classes .= ' alert-dismissible';

		// add close button
		$msg .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>';

		self::add($msg, $classes);
	}

	/**
	 * Gets all messages specific to the current browser window from the session, and clears them from the session.
	 * 
	 * @return array An array of messages.
	 * 
	 * @example `$messages = WindowMessages::get();`
	 */
	public static function get() {
		// abort if there is a redirection header in headers_list()
		// this is to prevent messages from being cleared without being displayed
		$headers = headers_list();
		foreach($headers as $header) {
			if(strpos($header, 'Location:') === 0) return [];
		}

		$wid = self::getExistingOrNewWindowId();

		// get messages from the session
		$messages = $_SESSION['window_messages'][$wid] ?? [];

		// clear messages from the session
		$_SESSION['window_messages'][$wid] = [];

		return $messages;
	}

	/**
	 * Gets all messages specific to the current browser window from the session, and clears them from the session.
	 * 
	 * @return string HTML containing all messages.
	 * 
	 * @example `$html = WindowMessages::getHtml();`
	 */
	public static function getHtml() {
		// get messages from the session
		$messages = self::get();
		
		// build html
		$html = '';
		foreach($messages as $message) {
			$html .= '<div class="' . $message['classes'] . '">' . $message['msg'] . '</div>';
		}
		
		return $html;
	}

	/**
	 * Gets all messages specific to the current browser window from the session, without clearing them from the session.
	 * 
	 * @return array An array of messages.
	 * 
	 * @example `$messages = WindowMessages::peek();`
	 */
	public static function peek() {
		$wid = self::getExistingOrNewWindowId();

		// get messages from the session
		$messages = $_SESSION['window_messages'][$wid] ?? [];

		return $messages;
	}

	/**
	 * Retrieves the window id from the request, or generates a new one if it doesn't exist.
	 * 
	 * @return string The window id.
	 */
	public static function windowId() {
		return self::getExistingOrNewWindowId();
	}

	/**
	 * Retrieves the window id as a hidden form field.
	 * 
	 * @return string The window id as a hidden form field.
	 */
	public static function includeWindowId() {
		$wid = self::getExistingOrNewWindowId();
		return '<input type="hidden" name="browser_window_id" value="' . $wid . '" />';
	}

	/**
	 * Retrieves the window id as a query string, ready to be appended to a URL.
	 * 
	 * @return string The window id as a query string.
	 */
	public static function windowIdQuery() {
		$wid = self::getExistingOrNewWindowId();
		return 'browser_window_id=' . $wid;
	}
}
