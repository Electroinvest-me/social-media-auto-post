/**
 * Social Media Auto Post – admin screens.
 *
 * Settings: tabs, save bar, post type cards with live preview, connection test.
 * Editor: share switch, live preview, media picker, share now and status polling.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.a4smap || {};
	var i18n = cfg.i18n || {};
	var FORM_TABS = [ 'connection', 'post-types', 'advanced' ];

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	function debounce( fn, wait ) {
		var timer = null;

		return function () {
			var args = arguments;
			var self = this;

			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				fn.apply( self, args );
			}, wait );
		};
	}

	function note( message, type ) {
		return $( '<p class="a4smap-note"/>' )
			.addClass( 'a4smap-note--' + ( type || 'error' ) )
			.append( $( '<span/>' ).text( message || i18n.failed ) );
	}

	function busy( $button, isBusy ) {
		$button.prop( 'disabled', isBusy ).toggleClass( 'is-busy', isBusy ).attr( 'aria-busy', isBusy ? 'true' : null );
	}

	/** Show "See more" under captions that are cut off. */
	function clampCaptions( $root ) {
		$root.find( '[data-a4smap-caption]' ).each( function () {
			var clipped = this.scrollHeight > this.clientHeight + 2;

			$( this ).toggleClass( 'is-clamped', clipped );
			$( this ).next( '[data-a4smap-more]' ).prop( 'hidden', ! clipped );
		} );
	}

	$( document ).on( 'click', '[data-a4smap-more]', function () {
		$( this ).prev( '[data-a4smap-caption]' ).addClass( 'is-expanded' ).removeClass( 'is-clamped' );
		this.hidden = true;
	} );

	/* ------------------------------------------------------------------ */
	/* Conditional fields: [data-a4smap-when="field"][data-a4smap-values]  */
	/* ------------------------------------------------------------------ */

	function fieldValue( $scope, name ) {
		var $fields = $scope.find( '[data-a4smap-field="' + name + '"]' );

		if ( ! $fields.length ) {
			return '';
		}

		if ( $fields.first().is( ':radio' ) ) {
			return String( $fields.filter( ':checked' ).val() || '' );
		}

		if ( $fields.first().is( ':checkbox' ) ) {
			return $fields.first().is( ':checked' ) ? '1' : '0';
		}

		return String( $fields.first().val() || '' );
	}

	function applyConditions( $scope ) {
		$scope.find( '[data-a4smap-when]' ).each( function () {
			var $el = $( this );

			if ( $el.closest( '[data-a4smap-scope]' )[ 0 ] !== $scope[ 0 ] ) {
				return;
			}

			var values = String( $el.attr( 'data-a4smap-values' ) ).split( ',' );
			this.hidden = values.indexOf( fieldValue( $scope, $el.attr( 'data-a4smap-when' ) ) ) === -1;
		} );
	}

	$( document ).on( 'change', '[data-a4smap-field]', function () {
		applyConditions( $( this ).closest( '[data-a4smap-scope]' ) );
	} );

	/* Insert a placeholder at the cursor of the nearest caption field. */
	$( document ).on( 'click', '[data-a4smap-insert]', function () {
		var $scope = $( this ).closest( '[data-a4smap-scope], .a4smap-section, .a4smap-field' );
		var textarea = $scope.find( '[data-a4smap-caption-input]' ).get( 0 ) || $( this ).closest( '.a4smap-field, .a4smap-section' ).find( 'textarea' ).get( 0 );
		var token = $( this ).attr( 'data-a4smap-insert' );

		if ( ! textarea ) {
			return;
		}

		var start = textarea.selectionStart || 0;
		var end = textarea.selectionEnd || 0;
		var value = textarea.value;
		var spacer = start > 0 && ! /\s$/.test( value.slice( 0, start ) ) ? ' ' : '';

		textarea.value = value.slice( 0, start ) + spacer + token + value.slice( end );
		textarea.focus();
		textarea.selectionStart = textarea.selectionEnd = start + spacer.length + token.length;
		$( textarea ).trigger( 'input' );
	} );

	/* ------------------------------------------------------------------ */
	/* Settings screen                                                     */
	/* ------------------------------------------------------------------ */

	function initSettings( $app ) {
		var $form = $app.find( '[data-a4smap-form]' );
		var $savebar = $app.find( '[data-a4smap-savebar]' );
		var dirty = false;

		function showTab( tab ) {
			var $tabs = $app.find( '.a4smap-nav [data-a4smap-tab]' );

			if ( ! $tabs.filter( '[data-a4smap-tab="' + tab + '"]' ).length ) {
				tab = 'overview';
			}

			$tabs.removeClass( 'is-active' ).removeAttr( 'aria-current' );
			$tabs.filter( '[data-a4smap-tab="' + tab + '"]' ).addClass( 'is-active' ).attr( 'aria-current', 'page' );

			$app.find( '[data-a4smap-panel]' ).each( function () {
				this.hidden = $( this ).attr( 'data-a4smap-panel' ) !== tab;
			} );

			$savebar.prop( 'hidden', FORM_TABS.indexOf( tab ) === -1 );

			// Return to the same tab after "Save changes".
			var $referer = $form.find( 'input[name="_wp_http_referer"]' );
			if ( $referer.length ) {
				$referer.val( String( $referer.val() ).split( '#' )[ 0 ] + '#' + tab );
			}

			if ( tab === 'post-types' ) {
				$app.find( '.a4smap-type.is-open' ).each( function () {
					loadPreview( $( this ) );
				} );
			}
		}

		$app.on( 'click', '[data-a4smap-tab]', function ( event ) {
			event.preventDefault();

			var tab = $( this ).attr( 'data-a4smap-tab' );

			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', '#' + tab );
			}

			showTab( tab );
		} );

		/* Unsaved changes */
		function markDirty() {
			if ( dirty ) {
				return;
			}

			dirty = true;
			$savebar.addClass( 'is-dirty' );

			var $state = $savebar.find( '[data-a4smap-save-state]' );
			$state.text( $state.attr( 'data-dirty' ) );
		}

		$form.on( 'input change', ':input', function ( event ) {
			if ( event.target.name ) {
				markDirty();
			}
		} );

		$form.on( 'submit', function () {
			dirty = false;
		} );

		$( window ).on( 'beforeunload', function () {
			return dirty ? true : undefined;
		} );

		/* Post type cards */
		function setOpen( $card, open ) {
			$card.toggleClass( 'is-open', open );
			$card.find( '.a4smap-type__body' ).prop( 'hidden', ! open );
			$card.find( '[data-a4smap-expand]' ).attr( 'aria-expanded', open ? 'true' : 'false' );

			if ( open ) {
				loadPreview( $card );
			}
		}

		$app.on( 'click', '[data-a4smap-expand]', function () {
			var $card = $( this ).closest( '.a4smap-type' );
			setOpen( $card, ! $card.hasClass( 'is-open' ) );
		} );

		$app.on( 'change', '[data-a4smap-enable]', function () {
			var $card = $( this ).closest( '.a4smap-type' );
			var enabled = this.checked;

			$card.toggleClass( 'is-enabled', enabled );
			$card.find( '[data-a4smap-expand]' ).prop( 'hidden', ! enabled );
			setOpen( $card, enabled );
		} );

		function updateSummary( $card ) {
			var trigger = $card.find( 'input[name$="[trigger]"]:checked' ).attr( 'data-label' );
			var format = $card.find( 'input[name$="[format]"]:checked' ).attr( 'data-label' );
			var profile = $card.find( '[data-a4smap-profile] option:selected' ).attr( 'data-label' ) || $card.find( '[data-a4smap-profile-input]' ).val();
			var $chips = $card.find( '.a4smap-chip input:checked' );
			var $logos = ( $chips.length ? $chips : $card.find( '.a4smap-chip input' ) ).map( function () {
				return $( this ).closest( '.a4smap-chip' ).find( '.a4smap-logo-tile' ).first().clone().css( { width: 20, height: 20 } ).get( 0 );
			} );

			$card.find( '[data-a4smap-summary="trigger"] span' ).text( trigger || '' );
			$card.find( '[data-a4smap-summary="format"] span' ).text( format || '' );
			$card.find( '[data-a4smap-summary="profile"] span' ).text( profile || '' );
			$card.find( '[data-a4smap-summary="networks"]' ).empty().append( $logos );
		}

		$app.on( 'change', '.a4smap-type :input', function () {
			updateSummary( $( this ).closest( '.a4smap-type' ) );
		} );

		/* Networks not connected in the chosen profile */
		function syncNetworks( $card ) {
			var $select = $card.find( '[data-a4smap-profile]' );

			if ( ! $select.length ) {
				return;
			}

			var raw = $select.find( 'option:selected' ).attr( 'data-networks' );
			var networks = raw ? raw.split( ',' ) : null;

			$card.find( '[data-a4smap-network]' ).each( function () {
				var $chip = $( this );
				var missing = networks !== null && networks.indexOf( $chip.attr( 'data-a4smap-network' ) ) === -1;

				$chip.toggleClass( 'is-unavailable', missing ).attr( 'title', missing ? i18n.notInProfile : null );
			} );
		}

		$app.on( 'change', '[data-a4smap-profile]', function () {
			syncNetworks( $( this ).closest( '.a4smap-type' ) );
		} );

		/* Live preview with the latest post of the type */
		function loadPreview( $card ) {
			var $target = $card.find( '[data-a4smap-settings-preview]' );
			var data = $card.find( ':input[name^="a4smap_settings["]' ).serializeArray();

			data.push(
				{ name: 'action', value: 'a4smap_settings_preview' },
				{ name: 'nonce', value: cfg.settingsNonce },
				{ name: 'post_type', value: $card.attr( 'data-a4smap-type' ) }
			);

			$target.css( 'opacity', 0.55 );

			$.post( cfg.ajaxUrl, data )
				.done( function ( response ) {
					if ( response && response.success ) {
						$target.html( response.data.html );
						clampCaptions( $target );
					} else {
						$target.empty().append( note( response && response.data && response.data.message ) );
					}
				} )
				.fail( function () {
					$target.empty().append( note( i18n.failed ) );
				} )
				.always( function () {
					$target.css( 'opacity', '' );
				} );
		}

		var queuePreview = debounce( function ( $card ) {
			loadPreview( $card );
		}, 450 );

		$app.on( 'input change', '.a4smap-type [data-a4smap-preview-input]', function () {
			queuePreview( $( this ).closest( '.a4smap-type' ) );
		} );

		$app.on( 'click', '[data-a4smap-preview-refresh]', function () {
			loadPreview( $( this ).closest( '.a4smap-type' ) );
		} );

		/* Connection test */
		$app.on( 'click', '[data-a4smap-test]', function () {
			var $button = $( this );
			var $output = $app.find( '[data-a4smap-profiles]' );

			busy( $button, true );

			$.post( cfg.ajaxUrl, { action: 'a4smap_test_connection', nonce: cfg.settingsNonce } )
				.done( function ( response ) {
					if ( response && response.success ) {
						$output.html( response.data.html );

						if ( response.data.profiles.length && $app.find( '[data-a4smap-profile-input]' ).length ) {
							window.location.reload();
						}
					} else {
						$output.empty().append( note( response && response.data && response.data.message ) );
					}
				} )
				.fail( function () {
					$output.empty().append( note( i18n.failed ) );
				} )
				.always( function () {
					busy( $button, false );
				} );
		} );

		/* Show / hide the API key */
		$app.on( 'click', '[data-a4smap-reveal]', function () {
			var input = $app.find( $( this ).attr( 'data-a4smap-reveal' ) ).get( 0 );

			if ( input ) {
				input.type = input.type === 'password' ? 'text' : 'password';
				input.focus();
			}
		} );

		/* Activity */
		$app.on( 'change', '[data-a4smap-log-filter]', function () {
			var level = this.value;

			$app.find( '.a4smap-event' ).each( function () {
				this.hidden = level !== 'all' && $( this ).attr( 'data-level' ) !== level;
			} );
		} );

		$app.on( 'click', '[data-a4smap-clear-log]', function () {
			var $button = $( this );

			if ( ! window.confirm( i18n.confirmLog ) ) {
				return;
			}

			busy( $button, true );

			$.post( cfg.ajaxUrl, { action: 'a4smap_clear_log', nonce: cfg.settingsNonce } ).done( function () {
				window.location.reload();
			} );
		} );

		/* Copy placeholders */
		$app.on( 'click', '[data-a4smap-copy]', function () {
			var $button = $( this );
			var text = $button.attr( 'data-a4smap-copy' );

			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( text );
			}

			$button.addClass( 'is-copied' );
			window.setTimeout( function () {
				$button.removeClass( 'is-copied' );
			}, 1200 );
		} );

		$app.find( '.a4smap-type' ).each( function () {
			syncNetworks( $( this ) );
		} );

		showTab( ( window.location.hash || '#overview' ).slice( 1 ) );
	}

	/* ------------------------------------------------------------------ */
	/* Editor meta box                                                     */
	/* ------------------------------------------------------------------ */

	function initMetaBox( $box ) {
		var pollTimer = null;
		var polls = 0;
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
			$box.find( '[data-a4smap-status]' ).prepend( note( message ).addClass( 'a4smap-box-error' ) );
		}

		function render( response ) {
			if ( ! response || ! response.success ) {
				showError( response && response.data && response.data.message );
				$box.find( '[data-a4smap-share]' ).each( function () {
					busy( $( this ), false );
				} );
				return;
			}

			$box.find( '[data-a4smap-status]' ).html( response.data.html );

			if ( response.data.status ) {
				$box.find( '[data-a4smap-share-toggle]' ).prop( 'hidden', true );
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
			if ( ! $box.find( '[data-a4smap-preview-wrap]' ).prop( 'open' ) ) {
				return;
			}

			var $target = $box.find( '[data-a4smap-preview]' ).css( 'opacity', 0.55 );

			request( 'a4smap_preview', true )
				.done( function ( response ) {
					if ( response && response.success ) {
						$target.html( response.data.html );
						clampCaptions( $target );
					}
				} )
				.always( function () {
					$target.css( 'opacity', '' );
				} );
		}

		$box.on( 'click', '[data-a4smap-share]', function () {
			busy( $( this ), true );
			polls = 0;

			request( 'a4smap_share', true )
				.done( render )
				.fail( function () {
					showError( i18n.failed );
				} );
		} );

		$box.on( 'click', '[data-a4smap-refresh]', function () {
			polls = 0;
			refreshStatus();
		} );

		// The "toggle" event does not bubble, so bind it directly.
		$box.find( '[data-a4smap-preview-wrap]' ).on( 'toggle', function () {
			refreshPreview();
		} );

		$box.on( 'input change', '[data-a4smap-preview-input]', debounce( refreshPreview, 500 ) );

		/* Media picker for "Images I choose for this post" */
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

		// Block editor: meta boxes are saved after the post; refresh the status afterwards.
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

		clampCaptions( $box );
		schedulePoll( $box.find( '.a4smap-status' ).attr( 'data-in-progress' ) === '1' );
	}

	/* ------------------------------------------------------------------ */
	/* Boot                                                                */
	/* ------------------------------------------------------------------ */

	$( function () {
		$( '[data-a4smap-scope]' ).each( function () {
			applyConditions( $( this ) );
		} );

		$( '[data-a4smap-app]' ).each( function () {
			initSettings( $( this ) );
		} );

		$( '[data-a4smap-box]' ).each( function () {
			initMetaBox( $( this ) );
		} );
	} );
}( jQuery ) );
