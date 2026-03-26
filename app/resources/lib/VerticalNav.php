<?php
/**
 * VerticalNav class to generate a vertical navigation menu
 * based on the tables accessible to the current user and custom links.
 * Usage: `echo VerticalNav::html();`
 */
class VerticalNav {
	private static $menus = [];

	/**
	 * Generates the HTML for the vertical navigation menu.
	 * It builds the menus based on accessible tables and custom links.
	 *
	 * @return string The HTML for the vertical navigation menu.
	 */
	public static function html() {
		// skip if in setup mode
		if(defined('APPGINI_SETUP') && APPGINI_SETUP) return '';

		// skip if request is embedded
		if(Request::val('Embedded')) return '';

		$userNavMenu = getUserData('navMenu');
		if(!$userNavMenu) $userNavMenu = DEFAULT_NAV_MENU;

		// skip if vertical navigation is disabled
		if($userNavMenu != 'vertical') return '';

		// skip if in homepage and HOMEPAGE_NAVMENUS is not defined or false
		if(defined('HOMEPAGE') && HOMEPAGE && !HOMEPAGE_NAVMENUS) return '';

		self::buildMenus();
		return self::render();
	}

	private static function buildMenus() {
		$tables = getTableList(); // get tables accessible to the current user
		self::buildTableMenus($tables);
		self::addCustomLinks();
	}

	private static function buildTableMenus($tables) {
		$prependPath = defined('PREPEND_PATH') ? PREPEND_PATH : '';
		self::$menus = [];
		foreach ($tables as $table => $info) { // $info is an array with table info: [0] => title, [1] => description, [2] => icon, [3] => menu name
			// skip if the table is hidden in nav menus via checking the array returned from tablesHiddenInNavMenu()
			if (in_array($table, tablesHiddenInNavMenu())) continue;

			$searchFirst = in_array($table, tablesToFilterBeforeTV()) ? '?Filter_x=1' : '';

			$menu = $info[3];
			if (!isset(self::$menus[$menu])) self::$menus[$menu] = [];
			self::$menus[$menu][] = [
				'url' => "{$prependPath}{$table}_view.php{$searchFirst}",
				'title' => $info[0],
				'icon' => $info[2] ?: 'table.gif',
			];
		}

		// if menus has only one key and that key is named 'None', rename to the lang string for 'select a table'
		if (count(self::$menus) === 1 && isset(self::$menus['None'])) {
			global $Translation;
			$selectTable = $Translation['select a table'];
			self::$menus = [$selectTable => self::$menus['None']];
			unset(self::$menus['None']);
		}
	}

	private static function addCustomLinks() {
		global $navLinks;
		if (!is_array($navLinks) || count($navLinks) === 0) {
			return; // no custom links to add
		}

		$menusIdx = array_keys(self::$menus);
		$userGroup = getMemberInfo()['group'] ?? null;
		foreach ($navLinks as $link) {
			// skip links not accessible to the current user
			if (isset($link['groups']) && $link['groups'] !== '*' && !in_array($userGroup, $link['groups']) && !in_array('*', $link['groups'])) {
				continue;
			}
			// skip links without URL or title
			if (!isset($link['url']) || !isset($link['title'])) {
				continue;
			}
			// prepend path if the URL is relative
			if (!preg_match('/^https?:\/\//i', $link['url'])) {
				$link['url'] = (defined('PREPEND_PATH') ? PREPEND_PATH : '') . $link['url'];
			}
			$menu = isset($link['table_group']) && isset($menusIdx[$link['table_group']]) ? $menusIdx[$link['table_group']] : $menusIdx[0];
			self::$menus[$menu][] = [
				'url' => $link['url'],
				'title' => $link['title'],
				'icon' => $link['icon'] ?: 'table.gif',
			];
		}
	}

	private static function render() {
		return self::renderHtml() . self::renderJs();
	}

	private static function currentTableName() {
		// extract table name from uri if present (pattern: /tableName + _view.php)
		$currentUri = $_SERVER['REQUEST_URI'] ?? '';
		if (preg_match("#/(\w+)_view\.php#", $currentUri, $matches)) {
			return $matches[1]; // return the table name
		}
		return false; // no table name found
	}

	private static function linkToCurrentTable($link) {
		$tableName = self::currentTableName();
		if (!$tableName) return false;

		$prependPath = defined('PREPEND_PATH') ? PREPEND_PATH : '';

		// Check if the provided link has a URL that matches the current table
		return isset($link['url']) && strpos($link['url'], "{$prependPath}{$tableName}_view.php") === 0;
	}

	private static function expandedMenu($menu) {
		// extract table name from uri if present (pattern: /tableName + _view.php)
		$tableName = self::currentTableName();
		if (!$tableName) return false;

		// Check if the provided menu has a link to the current table
		$menuLinks = self::$menus[$menu] ?? [];
		foreach ($menuLinks as $link) {
			if (self::linkToCurrentTable($link)) {
				return true; // the menu is expanded
			}
		}
		return false; // the menu is not expanded
	}		

	private static function renderHtml() {
		global $Translation;

		$menus = self::$menus;
		ob_start();
		?>
		<div class="row">
			<div class="vertical-nav hidden-print col-sm-3 col-md-2 hidden-xs">
				<div class="panel-group" role="tablist">
					<?php foreach ($menus as $menu => $links): ?>
						<?php $isExpanded = self::expandedMenu($menu); ?>
						<div class="panel panel-default<?php echo $isExpanded ? ' panel-expanded' : ''; ?>">
							<div class="panel-heading" role="tab">
								<h4 class="panel-title">
									<a class="collapsed" role="button" data-toggle="collapse" tabindex="0">
										<?php echo $menu; ?>
										<span class="indicator pull-right glyphicon glyphicon-chevron-<?php echo $isExpanded ? 'down' : 'right'; ?>"></span>
									</a>
								</h4>
							</div>
							<div class="panel-collapse collapse<?php echo $isExpanded ? ' in show' : ''; ?>" role="tabpanel">
								<ul class="list-group">
									<?php foreach ($links as $link): ?>
										<li class="list-group-item vertical-nav-link">
											<a
												href="<?php echo $link['url']; ?>"
												class="<?php echo self::linkToCurrentTable($link) ? 'text-bold active' : ''; ?>"
											>
												<img src="<?php echo (defined('PREPEND_PATH') ? PREPEND_PATH : '') . $link['icon']; ?>" height="32">
												<?php echo $link['title']; ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<button
			class="btn btn-default btn-xs btn-toggle-vertical-nav hidden-print hidden-xs"
			title="<?php echo html_attr($Translation['hide navigation menu']); ?>"
		>
			<span class="glyphicon glyphicon-chevron-left rtl-mirror"></span>
		</button>
		<?php
		return ob_get_clean();
	}

	private static function renderJs() {
		ob_start();
		?>
		<script>
			// Make the whole panel-heading clickable, except if you click directly on a link (to support keyboard and accessibility)
			$j(() => {
				const $vertNav = $j('.vertical-nav');
				const $mainContent = $j('.main-content');
				const originalBodyBgColor = $j('body').css('background-color');
				
				// get the border colors of a temp .navbar-default element
				const navbarBgColor = (() => {
					const $container = $j('.container-fluid, .container').eq(0);
					const $tempNav = $j('<div class="navbar navbar-default"></div>');
					$tempNav.appendTo($container);
					const bgColor = $tempNav.css('background-color');
					$tempNav.remove();
					return bgColor;
				})();

				const moveBtnToVertNav = (selector) => {
					// move the button to the vertical nav
					$j(selector).eq(0)
						.addClass('hidden')
						.clone()
						.addClass('btn-block btn-lg')
						.removeClass('hidden')
						.appendTo('.vertical-nav');
				};
				
				const adjustVertNavCSS = () => {
					if(screen_size('xs')) {
						$j('body').css({ background: originalBodyBgColor });
						$j('.horizontal-navlinks').removeClass('hidden');
						return;
					}
					
					const navbarTopHeight = $j('.navbar-fixed-top').outerHeight(true) ?? 0;
					const headerTop = (() => {
						if($j('.page-header').length === 0) return 0;
						const ht = $j('.page-header').css('margin-top').replace('px', '') * 1;
						return ht < 200 ? ht : 0; // if the header is too far down (e.g. children in DVP), assume it's not there and set to 0
					})();
					const navbarBottomHeight = $j('.navbar-fixed-bottom').outerHeight(true) ?? 0;
					const stickyTop = $j('.row').eq(0).offset().top ?? 0;
					const vertNavTopOffset = Math.max(navbarTopHeight, stickyTop);

					// set the vertical nav top padding to the value of the top margin of the page header
					const vertNavCss = {
						'padding-top': `${headerTop}px`,
						'min-height': `calc(100vh - ${navbarBottomHeight}px - ${vertNavTopOffset}px)`,
						height: `calc(100vh - ${navbarBottomHeight}px - ${vertNavTopOffset}px)`,
						top: `${stickyTop}px`
					};
					$vertNav.css(vertNavCss);

					$j('.horizontal-navlinks').addClass('hidden');

					// get the outer width of the vertical nav
					// and use it to set the background width
					const navWidth = $vertNav.outerWidth(true);
					const gradientDir = !$j('body > .theme-rtl').length ? 'right' : 'left';
					$j('body').css({ background: `linear-gradient(
						to ${gradientDir},
						${navbarBgColor} ${navWidth}px,
						var(--navbar-border-color) ${navWidth}px calc(${navWidth}px + 1px),
						${originalBodyBgColor} calc(${navWidth}px + 2px)
					)`});

					$j('.btn-toggle-vertical-nav').css({
						top: `${vertNavTopOffset - 10}px`,
						left: !$j('body > .theme-rtl').length ? `${navWidth}px` : 'auto',
						right: $j('body > .theme-rtl').length ? `${navWidth}px` : 'auto',
					});

					// if in rtl mode, adjust the indicator position
					if ($j('body > .theme-rtl').length) {
						const indicators = $vertNav.find('.panel-title .indicator');
						const parentWidth = indicators.eq(0).parent().innerWidth();
						const indicatorWidth = indicators.eq(0).outerWidth(true);
						indicators.css({
							right: `${parentWidth - indicatorWidth - 20}px`,
						});
					}
				};

				const implementToggling = function($panel) {
					const $heading = $panel.find('.panel-heading');
					const $collapse = $panel.find('.panel-collapse');
					const $toggle = $heading.find('a[data-toggle="collapse"]');

					// Click on heading toggles this panel
					$heading.off('click').on('click', function(e) {
						if (!$j(e.target).is('a')) {
							$toggle.trigger('click');
						}
					});
					// Click on the indicator toggles this panel
					$heading.find('.indicator').off('click').on('click', function(e) {
						e.stopPropagation(); // Prevent triggering the panel heading click
						$toggle.trigger('click');
					});

					// Toggle collapse on click
					$toggle.off('click').on('click', function(e) {
						e.preventDefault();
						const isOpen = $collapse.hasClass('in') || $collapse.hasClass('show');
						// Close all
						$vertNav.find('.panel-collapse').removeClass('in show').slideUp(200);
						$vertNav.find('.panel-title .indicator')
							.removeClass('glyphicon-chevron-down')
							.addClass('glyphicon-chevron-right');
						if (!isOpen) {
							$collapse.addClass('in show').slideDown(200);
							$toggle.find('.indicator')
								.removeClass('glyphicon-chevron-right')
								.addClass('glyphicon-chevron-down');
						}
					});
				};

				const toggleNavbar = () => {
					// check if the vertical nav is already hidden or media is print
					const alreadyHidden = $j('.vertical-nav').hasClass('hidden') || window.matchMedia('print').matches;
					$j('.vertical-nav').toggleClass('hidden', !alreadyHidden);
					$j('.main-content')
						.toggleClass('col-sm-9 col-md-10', alreadyHidden)
						.toggleClass('col-sm-12 col-md-12', !alreadyHidden);
					$j('.btn-toggle-vertical-nav')
						.attr('title', AppGini.Translate._map[alreadyHidden ? 'hide navigation menu' : 'show navigation menu'])
						//.css('height', alreadyHidden ? 'inherit' : '70px')
					$j('.btn-toggle-vertical-nav .glyphicon')
						.toggleClass('glyphicon-chevron-left', alreadyHidden)
						.toggleClass('glyphicon-chevron-right', !alreadyHidden);
					$j('body').trigger('resize');
					// persist the toggle state for later
					AppGini.localStorage.setItem('verticalNavHidden', !alreadyHidden);
				}

				// Make main container fluid
				$j('body > .container').removeClass('container').addClass('container-fluid');

				// move main container to the right of the vertical nav unless in print media (where we hide the vertical nav)
				$mainContent
					.toggleClass('col-sm-9 col-md-10', !window.matchMedia('print').matches)
					.appendTo($vertNav.parent());

				// move the import CSV and admin area buttons to the vertical nav
				moveBtnToVertNav('.btn-import-csv');
				moveBtnToVertNav('.btn-admin-area');

				// move the install PWA button (the one with full text) to the vertical nav, and remove the icon-only version
				$j('.hidden-sm.install-pwa-btn').appendTo('.vertical-nav').removeClass('hidden-sm hidden-md hidden-lg');
				$j('.navbar .install-pwa-btn').remove();

				// set the vertical nav background, adapting to screen size
				adjustVertNavCSS();

				// Recalculate background on window resize
				$j(window).on('resize', adjustVertNavCSS);

				// implement the vertical navigation toggle functionality
				$vertNav.find('.panel').each(function() { implementToggling($j(this)); });

				// 
				$j('.btn-toggle-vertical-nav').on('click', toggleNavbar);

				// Restore the vertical nav state from localStorage if available
				const verticalNavHidden = AppGini.localStorage.getItem('verticalNavHidden');
				if (verticalNavHidden) toggleNavbar();
			});
		</script>
		<?php
		return ob_get_clean();
	}
}
