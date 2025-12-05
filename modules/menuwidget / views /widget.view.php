<?php declare(strict_types = 0);

/**
 * Menu widget view.
 *
 * @var CView $this
 * @var array $data
 */

$menu_orientation = $data['menu_orientation'];
$menu_position = $data['menu_position'];
$font_family = $data['font_family'];
$font_size = $data['font_size'];
$font_color = $data['font_color'];
$bg_color = $data['bg_color'];
$hover_color = $data['hover_color'];
$menu_items = $data['menu_items'];
$max_visible_items = $data['max_visible_items'];
$collapsible = $data['collapsible'];
$collapsed_by_default = $data['collapsed_by_default'];

$container_class = 'menu-widget-container';
if ($menu_orientation === 'horizontal') {
	$container_class .= ' menu-horizontal';
} else {
	$container_class .= ' menu-vertical';
	if ($menu_position === 'top') {
		$container_class .= ' menu-position-top';
	} else {
		$container_class .= ' menu-position-left';
	}
}

if ($collapsed_by_default) {
	$container_class .= ' menu-collapsed';
}

$widget_body = (new CDiv())
	->addClass($container_class)
	->setId('menu-widget-' . uniqid());

$widget_body->addItem(
	(new CTag('style', true, '
		.menu-widget-container {
			width: 100%;
			height: 100%;
			display: flex;
			font-family: ' . $font_family . ';
			font-size: ' . $font_size . 'px;
			color: ' . $font_color . ';
		}
		.menu-widget-container.menu-horizontal {
			flex-direction: column;
		}
		.menu-widget-container.menu-vertical {
			flex-direction: row;
		}
		.menu-nav {
			background-color: ' . $bg_color . ';
			display: flex;
			align-items: center;
			position: relative;
			transition: all 0.3s ease;
		}
		.menu-horizontal .menu-nav {
			flex-direction: row;
			padding: 10px;
			border-bottom: 1px solid #ddd;
		}
		.menu-vertical .menu-nav {
			flex-direction: column;
			padding: 10px;
			border-right: 1px solid #ddd;
			min-width: 200px;
		}
		.menu-vertical.menu-collapsed .menu-nav {
			min-width: 50px;
		}
		.menu-items-wrapper {
			display: flex;
			overflow: hidden;
			flex: 1;
		}
		.menu-horizontal .menu-items-wrapper {
			flex-direction: row;
		}
		.menu-vertical .menu-items-wrapper {
			flex-direction: column;
		}
		.menu-item {
			padding: 10px 15px;
			cursor: pointer;
			transition: background-color 0.2s;
			white-space: nowrap;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.menu-item:hover {
			background-color: ' . $hover_color . ';
		}
		.menu-item.active {
			background-color: ' . $hover_color . ';
			font-weight: bold;
		}
		.menu-item-image {
			width: 24px;
			height: 24px;
			object-fit: contain;
		}
		.menu-collapsed .menu-item-label {
			display: none;
		}
		.menu-nav-arrow {
			padding: 5px 10px;
			cursor: pointer;
			user-select: none;
			font-size: 18px;
		}
		.menu-nav-arrow:hover {
			background-color: ' . $hover_color . ';
		}
		.menu-nav-arrow.disabled {
			opacity: 0.3;
			cursor: not-allowed;
		}
		.menu-content {
			flex: 1;
			overflow: auto;
			padding: 15px;
		}
		.menu-toggle {
			position: absolute;
			top: 5px;
			right: 5px;
			cursor: pointer;
			padding: 5px;
			font-size: 16px;
		}
		.menu-vertical .menu-toggle {
			right: auto;
			left: 5px;
		}
		.menu-toggle:hover {
			background-color: ' . $hover_color . ';
		}
		.menu-content iframe {
			width: 100%;
			height: 100%;
			border: none;
		}
	'))
);

(new CWidgetView($data))
	->addItem($widget_body)
	->show();

?>

<script type="text/javascript">
jQuery(function($) {
	const menuData = <?= json_encode([
		'items' => $menu_items,
		'maxVisible' => $max_visible_items,
		'collapsible' => $collapsible,
		'orientation' => $menu_orientation
	]) ?>;

	const container = $('#menu-widget-<?= uniqid() ?>').closest('.menu-widget-container');
	let currentIndex = 0;
	let activeItemIndex = 0;

	function renderMenu() {
		const nav = $('<div class="menu-nav"></div>');
		
		if (menuData.collapsible) {
			const toggleBtn = $('<div class="menu-toggle">☰</div>');
			toggleBtn.on('click', function() {
				container.toggleClass('menu-collapsed');
			});
			nav.append(toggleBtn);
		}

		if (menuData.items.length > menuData.maxVisible) {
			const prevArrow = $('<div class="menu-nav-arrow menu-prev">◀</div>');
			prevArrow.on('click', function() {
				if (currentIndex > 0) {
					currentIndex--;
					updateMenuItems();
				}
			});
			nav.append(prevArrow);
		}

		const itemsWrapper = $('<div class="menu-items-wrapper"></div>');
		nav.append(itemsWrapper);

		if (menuData.items.length > menuData.maxVisible) {
			const nextArrow = $('<div class="menu-nav-arrow menu-next">▶</div>');
			nextArrow.on('click', function() {
				if (currentIndex < menuData.items.length - menuData.maxVisible) {
					currentIndex++;
					updateMenuItems();
				}
			});
			nav.append(nextArrow);
		}

		const content = $('<div class="menu-content"></div>');
		content.attr('id', 'menu-content-area');

		container.empty().append(nav).append(content);
		updateMenuItems();
	}

	function updateMenuItems() {
		const itemsWrapper = container.find('.menu-items-wrapper');
		itemsWrapper.empty();

		const visibleItems = menuData.items.slice(currentIndex, currentIndex + menuData.maxVisible);

		visibleItems.forEach((item, index) => {
			const globalIndex = currentIndex + index;
			const menuItem = $('<div class="menu-item"></div>');
			
			if (item.image) {
				const img = $('<img class="menu-item-image">').attr('src', item.image);
				menuItem.append(img);
			}
			
			const label = $('<span class="menu-item-label"></span>').text(item.label);
			menuItem.append(label);

			if (globalIndex === activeItemIndex) {
				menuItem.addClass('active');
			}

			menuItem.on('click', function() {
				activeItemIndex = globalIndex;
				$('.menu-item').removeClass('active');
				$(this).addClass('active');
				loadContent(item.url);
			});

			itemsWrapper.append(menuItem);
		});

		// Update arrow states
		$('.menu-prev').toggleClass('disabled', currentIndex === 0);
		$('.menu-next').toggleClass('disabled', currentIndex >= menuData.items.length - menuData.maxVisible);

		// Load first item by default
		if (menuData.items.length > 0 && activeItemIndex === 0) {
			loadContent(menuData.items[0].url);
		}
	}

	function loadContent(url) {
		const contentArea = $('#menu-content-area');
		if (url) {
			const iframe = $('<iframe></iframe>').attr('src', url);
			contentArea.empty().append(iframe);
		} else {
			contentArea.empty().html('<p>Nenhuma URL configurada para este item.</p>');
		}
	}

	renderMenu();
});
</script>
