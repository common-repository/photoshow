( function( blocks, element ) {
	var el 		= element.createElement,
		source	= blocks.source;

	/* Plugin Category */
	blocks.getCategories().push({slug: 'smartig', title: 'Smart Image Gallery'});

	/* ICONS */
	const iconSMARTIG = el('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAABHNCSVQICAgIfAhkiAAAAWtJREFUOI2tkz1LQlEcxn/n3MvVxHy5KkKUIIiIOOiQ1BD4BZpaMtqaMsigtamCtjCa+wh9j5pqsXBqaYkoCxKSMhuu53Kvppj4LPe8/P+/+zzncARH9S5TkJwGBEBXg+5BdiKAOL5zgwDOLh/Ze/gYC1BL+qmuzduZdGe46+c2AK9bcQDMi6ehIFWr5HKET4NWx+F7xBH6NNfUXSmF5aTSwKw07OX6ZmI48E9HP10LXTQBqBbC3L+0yca9Iwiy50iX9kQ5AtjIBKiVYpyXYgB099NugKPWAvVLCLy64GTZcpWOeOyt5k6KqF8HbaALiaGB0dvxWN/dfJhEyBgoDnklh0smIOxaDAmGRAf3Cwl6JNu5AM3W9+BvgfWUn9PbN2cEAHQ1UHr/7JCs3sDXiCdYCLGY9KtMPZDzlASgCShGhkOGyHX9q5lZFuZmxmrMB3qtUkVzXGM5F6T8by99jkT5ajLCSlQ5ci9Mql8cvTswSMiZQQAAAABJRU5ErkJggg==" } );

	/* Smart Image Gallery Code */
	blocks.registerBlockType( 'smartig/gallery', {
		title: 'Smart Image Gallery',
		icon: iconSMARTIG,
		category: 'smartig',
		supports: {
			customClassName: false,
			className: false
		},
		attributes: {
			gallery : {
				type 	: 'string',
				default : ''
			}
		},

		edit: function( props ) {
			var focus = props.isSelected;

			window[ 'smartigSetGallery' ] = function(newGallery)
			{
				props.setAttributes({gallery: newGallery});
			};

			if(
				!!focus &&
				(
					typeof props.attributes.gallery == 'undefined' ||
					props.attributes.gallery == ''
				) &&
				jQuery('.wp-dialog').length == 0
			)
			{
				if(typeof photoshowAdmin != 'undefined')
				{
					photoshowAdmin.open();
				}
			}

			function onChangeGallery(evt)
			{
				smartigSetGallery(evt.target.value);
			};

			return 	[
						el(
							'textarea',
							{
								key 	: 'smartig-editable',
								onChange: onChangeGallery,
								value 	: props.attributes.gallery,
								style	: {width:"100%", resize: "vertical"}
							}
						),
						el(
							'div', {className: 'smartig-iframe-container', key: 'smartig_iframe_container'},
							el('div', {className: 'smartig-iframe-overlay', key: 'smartig_iframe_overlay'}),
							el('iframe',
								{
									key: 'smartig_store_iframe',
									src: photoshow_settings.url+encodeURIComponent(props.attributes.gallery),
									height: 0,
									width: 500,
									scrolling: 'no'
								}
							)
						)
					];
		},

		save: function( props ) {
			var g = props.attributes.gallery || '';
			return el(element.RawHTML, null, g);
		}
	});
} )(
	window.wp.blocks,
	window.wp.element
);