/**
 * Social Media Auto Post – admin screens.
 *
 * Settings tabs, conditional fields, connection test, and the editor meta box
 * (media picker, share now, status polling, live preview).
 */
( function ( $ ) {
	'use strict';

	var cfg = window.a4smap || {};
	var i18n = cfg.i18n || {};

	/* ------------------------------------------------------------------ */
	/* Conditional fields: [data-a4smap-when="field"][data-a4smap-values]  */
	/* ------------------------------------------------------------------ */

	function fieldValue( $scope, name ) {
		var $field = $scope.find( '[data-a4smap-field="' + name + '"]' ).first();

		if ( ! $field.length ) {
			return '';
		}

		if ( $field.is( ':checkbox' ) ) {
			return $field.is( ':checked' ) ? '1' : '0';
		}

		return String( $field.val() || '' );
	}

	function applyConditions( $scope ) {
		$scope.find( '[data-a4smap-when]' ).each( function () {
			var $el = $( this );

			if ( $el.closest( '[data-a4smap-scope]' )[ 0 ] !== $scope[ 0 ] ) {
				return;
			}

			var values = String( $el.attr( 'data-a4smap-values' ) ).split( ',' );
			$el.toggle( values.indexOf( fieldValue( $scope, $el.attr( 'data-a4smap-when' ) ) ) !== -1 );
		} );
	}

	$( document ).on( 'change', '[data-a4smap-field]', function () {
		applyConditions( $( this ).closest( '[data-a4smap-scope]' ) );
	} );

	/* ------------------------------------------------------------------ */
	/* Settings screen                                                     */
	/* ------------------------------------------------------------------ */

	function showTab( tab ) {
		var $wrap = $( '.a4smap' );
		var $tabs = $wrap.find( '.a4smap-tabs [data-a4smap-tab]' );

		if ( ! $tabs.length ) {
			return;
		}

		if ( ! $tabs.filter( '[data-a4smap-tab="' + tab + '"]' ).length ) {
			tab = $tabs.first().attr( 'data-a4smap-tab' );
		}

		$tabs.removeClass( 'nav-tab-active' ).attr( 'aria-current', null );
		$tabs.filter( '[data-a4smap-tab="' + tab + '"]' ).addClass( 'nav-tab-active' ).attr( 'aria-current', 'page' );

		$wrap.find( '[data-a4smap-panel]' ).each( function () {
			this.hidden = $( this ).attr( 'data-a4smap-panel' ) !== tab;
		} );

		$wrap.find( '.a4smap-submit' ).prop( 'hidden', tab === 'activity' || tab === 'help' );

		// Come back to the same tab after "Save Changes".
		var $referer = $wrap.find( 'input[name="_wp_http_referer"]' );
		if ( $referer.length ) {
			$referer.val( String( $referer.val() ).split( '#' )[ 0 ] + '#' + tab );
		}
	}

	$( document ).on( 'click', '[data-a4smap-tab]', function ( event ) {
		event.preventDefault();

		var tab = $( this ).attr( 'data-a4smap-tab' );

		if ( window.history && window.history.replaceState ) {
			window.history.replaceState( null, '', '#' + tab );
		}

		showTab( tab );
		window.scrollTo( 0, 0 );
	} );

	function syncNetworks( $scope ) {
		var $select = $scope.find( '[data-a4smap-profile]' );

		if ( ! $select.length ) {
			return;
		}

		var raw = $select.find( 'option:selected' ).attr( 'data-networks' );
		var networks = raw ? raw.split( ',' ) : null;

		$scope.find( '[data-a4smap-network]' ).each( function () {
			var $label = $( this );
			var missing = networks !== null && networks.indexOf( $label.attr( 'data-a4smap-network' ) ) === -1;

			$label.toggleClass( 'is-unavailable', missing ).attr( 'title', missing ? i18n.notInProfile : null );
		} );
	}

	$( document ).on( 'change', '[data-a4smap-profile]', function () {
		syncNetworks( $( this ).closest( '[data-a4smap-scope]' ) );
	} );

	function notice( message, type ) {
		return $( '<div class="notice inline"/>' )
			.addClass( 'notice-' + ( type || 'info' ) )
			.append( $( '<p/>' ).text( message || i18n.failed ) );
	}

	$( document ).on( 'click', '[data-a4smap-test]', function () {
		var $button = $( this );
		var $output = $( '[data-a4smap-profiles]' );

		$button.prop( 'disabled', true );
		$output.empty().append( $( '<p/>' ).text( i18n.working ) );

		$.post( cfg.ajaxUrl, { action: 'a4smap_test_connection', nonce: cfg.settingsNonce } )
			.done( function ( response ) {
				if ( response && response.success ) {
					$output.html( response.data.html );

					// Profile fields were plain text inputs: reload to show the profile lists.
					if ( response.data.profiles.length && $( '[data-a4smap-profile-input]' ).length ) {
						window.location.reload();
					}
				} else {
					$output.empty().append( notice( response && response.data && response.data.message, 'error' ) );
				}
			} )
			.fail( function () {
				$output.empty().append( notice( i18n.failed, 'error' ) );
			} )
			.always( function () {
				$button.prop( 'disabled', false );
			} );
	} );

	$( document ).on( 'click', '[data-a4smap-clear-log]', function () {
		if ( ! window.confirm( i18n.confirmLog ) ) {
			return;
		}

		$.post( cfg.ajaxUrl, { action: 'a4smap_clear_log', nonce: cfg.settingsNonce } ).done( function () {
			$( '[data-a4smap-log]' ).find( 'table, p' ).remove();
		} );
	} );

	/* ------------------------------------------------------------------ */
	/* Editor meta box                                                     */
	/* ------------------------------------------------------------------ */

	function initMetaBox( $box ) {
		var pollTimer = null;
		var polls = 0;
		var previewTimer = null;
		var frame = null;

		function request( action, withFields ) {
			var data = withFields ? $box.find( ':input[name^="a4smap["]' ).serializeArray() : [];

			data.push(
				{ name: 'action', value: action },
				{ name: 'nonce', value: $box.attr( 'data-nonce' ) },
				{ name: 'post_id', value: $box.attr( 'data-post' ) }
			);

			return $.post( cfg.ajaxUrl, data );
		}

		function showError( message ) {
			$box.find( '[data-a4smap-status] .a4smap-box-error' ).remove();
			$box.find( '[data-a4smap-status]' ).append(
				$( '<p class="a4smap-note a4smap-note--error a4smap-box-error"/>' ).text( message || i18n.failed )
			);
		}

		function render( response ) {
			if ( ! response || ! response.success ) {
				showError( response && response.data && response.data.message );
				return;
			}

			$box.find( '[data-a4smap-status]' ).html( response.data.html );

			if ( response.data.status ) {
				$box.find( '[data-a4smap-share-toggle]' ).hide();
			}

			schedulePoll( response.data.inProgress );
		}

		function schedulePoll( inProgress ) {
			window.clearTimeout( pollTimer );

			if ( ! inProgress || polls >= 40 ) {
				return;
			}

			polls++;
			pollTimer = window.setTimeout( refreshStatus, polls < 6 ? 3000 : 10000 );
		}

		function refreshStatus() {
			request( 'a4smap_panel', false ).done( render );
		}

		function refreshPreview() {
			var $wrap = $box.find( '[data-a4smap-preview-wrap]' );

			if ( ! $wrap.prop( 'open' ) ) {
				return;
			}

			request( 'a4smap_preview', true ).done( function ( response ) {
				if ( response && response.success ) {
					$box.find( '[data-a4smap-preview]' ).html( response.data.html );
				}
			} );
		}

		$box.on( 'click', '[data-a4smap-share]', function () {
			var $button = $( this );

			$button.prop( 'disabled', true ).text( i18n.working );
			polls = 0;

			request( 'a4smap_share', true )
				.done( render )
				.fail( function () {
					showError( i18n.failed );
					$button.prop( 'disabled', false );
				} );
		} );

		$box.on( 'click', '[data-a4smap-refresh]', function () {
			polls = 0;
			refreshStatus();
		} );

		// The "toggle" event does not bubble, so bind it directly.
		$box.find( '[data-a4smap-preview-wrap]' ).on( 'toggle', refreshPreview );

		$box.on( 'change input', '[data-a4smap-preview-input]', function () {
			window.clearTimeout( previewTimer );
			previewTimer = window.setTimeout( refreshPreview, 600 );
		} );

		// Media picker for "Images I choose for this post".
		function setMedia( items ) {
			var $list = $box.find( '[data-a4smap-media-list]' ).empty();

			$box.find( '[data-a4smap-media-ids]' )
				.val( items.map( function ( item ) { return item.id; } ).join( ',' ) )
				.trigger( 'change' );

			items.forEach( function ( item ) {
				$list.append( $( '<li/>' ).append( $( '<img/>', { src: item.thumb, alt: '' } ) ) );
			} );
		}

		function selectedIds() {
			return String( $box.find( '[data-a4smap-media-ids]' ).val() || '' ).split( ',' ).filter( Boolean ).map( Number );
		}

		$box.on( 'click', '[data-a4smap-pick]', function ( event ) {
			event.preventDefault();

			if ( ! window.wp || ! window.wp.media ) {
				return;
			}

			if ( ! frame ) {
				frame = window.wp.media( {
					title: i18n.chooseMedia,
					button: { text: i18n.useMedia },
					multiple: 'add',
					library: { type: [ 'image', 'video' ] }
				} );

				frame.on( 'open', function () {
					var selection = frame.state().get( 'selection' );

					selection.reset();
					selectedIds().forEach( function ( id ) {
						var attachment = window.wp.media.attachment( id );
						attachment.fetch();
						selection.add( attachment );
					} );
				} );

				frame.on( 'select', function () {
					setMedia( frame.state().get( 'selection' ).toJSON().map( function ( item ) {
						var thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : ( item.type === 'image' ? item.url : item.icon );
						return { id: item.id, thumb: thumb };
					} ) );
				} );
			}

			frame.open();
		} );

		$box.on( 'click', '[data-a4smap-clear-media]', function ( event ) {
			event.preventDefault();
			setMedia( [] );
		} );

		// Block editor: the meta box is saved after the post; refresh the status afterwards.
		if ( window.wp && window.wp.data && window.wp.data.select( 'core/edit-post' ) ) {
			var wasSaving = false;

			window.wp.data.subscribe( function () {
				var editPost = window.wp.data.select( 'core/edit-post' );
				var saving = editPost && editPost.isSavingMetaBoxes ? editPost.isSavingMetaBoxes() : false;

				if ( wasSaving && ! saving ) {
					polls = 0;
					window.setTimeout( refreshStatus, 1000 );
					refreshPreview();
				}

				wasSaving = saving;
			} );
		}

		schedulePoll( $box.find( '.a4smap-status' ).attr( 'data-in-progress' ) === '1' );
	}

	$( function () {
		$( '[data-a4smap-scope]' ).each( function () {
			applyConditions( $( this ) );
			syncNetworks( $( this ) );
		} );

		showTab( ( window.location.hash || '#connection' ).slice( 1 ) );

		$( '[data-a4smap-box]' ).each( function () {
			initMetaBox( $( this ) );
		} );
	} );
}( jQuery ) );
