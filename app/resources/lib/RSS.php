<?php

class RSS {
	/**
	 * Reads an RSS feed from the specified URL and returns an array of entries.
	 *
	 * @param string $url The URL of the RSS feed.
	 * @return array An array of entries, where each entry is an associative array
	 *               containing 'title', 'description', and 'url' keys.
	 *               Returns an empty array if the URL is unreachable or the feed is empty.
	 */
	public static function read($url) {
		$entries = [];
		$noEntries = [['title' => 'No entries', 'description' => 'No entries', 'url' => 'https://bigprof.com/appgini/']];
		$noRss = [['title' => 'No RSS feed', 'description' => 'No RSS feed', 'url' => 'https://bigprof.com/appgini/']];

		// Fetch the RSS feed content
		$content = @file_get_contents($url);

		// Check if the URL is unreachable or the feed is empty
		if ($content === false || empty($content)) {
			return $noRss;
		}

		// Create a new SimpleXMLElement object from the RSS content
		$xml = new SimpleXMLElement($content);

		// Check if the RSS feed is empty
		if (empty($xml->channel->item)) {
			return $noEntries;
		}

		// Loop through each item in the RSS feed
		foreach ($xml->channel->item as $item) {
			$entry = [
				'title' => (string) $item->title,
				'description' => (string) $item->description,
				'url' => (string) $item->link,
			];

			// item has an enclosure of type image/jpeg or image/png or image/gif?
			if (isset($item->enclosure['type']) && in_array($item->enclosure['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
				$entry['image'] = (string) $item->enclosure['url'];
			}

			// item has a video enclosure?
			if (isset($item->enclosure['type']) && strpos($item->enclosure['type'], 'video/') === 0) {
				$entry['video'] = (string) $item->enclosure['url'];
			}

			$entries[] = $entry;
		}

		return $entries;
	}

	/**
	 * Creates an RSS feed file at the specified path with the given entries.
	 *
	 * @param string $path The path to the RSS feed file.
	 * @param string $title The title of the RSS feed.
	 * @param string $link The link to the RSS feed.
	 * @param array $entries An array of entries, where each entry is an associative array
	 *                       containing 'title', 'description', and 'url' keys.
	 * 					     'description' can contain HTML tags, and will be encoded as CDATA.
	 *                       'image' is an optional key that contains the URL of an image.
	 *                       'video' is an optional key that contains the URL of a video.
	 * @return bool True if the RSS feed file was successfully created, false otherwise.
	 */
	public static function create($path, $title, $link, $entries) {
		// Create a new SimpleXMLElement object for the RSS feed
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
		$channel = $xml->addChild('channel');

		// Add the title and link elements to the channel
		$channel->addChild('title', $title);
		$channel->addChild('link', $link);

		// Loop through each entry and add it to the channel
		foreach ($entries as $entry) {
			$item = $channel->addChild('item');
			$item->addChild('title', $entry['title']);
			$item->addChild('description', '<![CDATA[' . $entry['description'] . ']]>');
			$item->addChild('link', $entry['url']);
			// item has an image?
			if (!empty($entry['image'])) {
				$enclosure = $item->addChild('enclosure');
				$enclosure->addAttribute('url', $entry['image']);
				// set the type attribute based on the image extension
				$enclosure->addAttribute('type', 'image/' . pathinfo($entry['image'], PATHINFO_EXTENSION));
			}
			// item has a video?
			if (!empty($entry['video'])) {
				$enclosure = $item->addChild('enclosure');
				$enclosure->addAttribute('url', $entry['video']);
				// set the type attribute based on the video extension
				$enclosure->addAttribute('type', 'video/' . pathinfo($entry['video'], PATHINFO_EXTENSION));
			}
		}

		// Save the XML content to the specified file
		return $xml->asXML($path) !== false;
	}

	/**
	 * Renders the given entries as an HTML list group inside a panel.
	 *
	 * @param array $entries An array of entries, where each entry is an associative array
	 *                       containing 'title', 'description', 'url', 'image', and 'video' keys.
	 * @param string $title The title of the panel. Default is 'News'.
	 * @return string The HTML content of the panel.
	 */
	public static function render($entries, $title = 'News') {
		if (empty($entries) || !is_array($entries)) {
			return '';
		}

		// show entries as list groups inside a panel
		ob_start();
		?>
		<div class="panel panel-info rss-feed">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo htmlspecialchars($title); ?></h3>
			</div>
			<ul class="list-group">
				<?php foreach ($entries as $entry): ?>
					<li class="list-group-item">
						<h4>
							<a href="<?php echo htmlspecialchars($entry['url']); ?>" target="_blank">
								<?php echo htmlspecialchars($entry['title']); ?>
							</a>
						</h4>
						<p>
							<?php echo $entry['description']; ?>
						</p>
						<?php if (isset($entry['image'])): ?>
							<img src="<?php echo htmlspecialchars($entry['image']); ?>" class="img-responsive" alt="Image">
						<?php endif; ?>
						<?php if (isset($entry['video'])): ?>
							<video controls class="img-responsive">
								<source src="<?php echo htmlspecialchars($entry['video']); ?>" type="video/mp4">
								Your browser does not support the video tag.
							</video>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<style>
			.rss-feed .list-group-item {
				max-width: 100%;
				overflow-x: auto;
			}
		</style>
		<?php
		return ob_get_clean();
	}
}