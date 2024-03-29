<?php

namespace WPD_Platform;

use WPD_Platform\Utils\HostInterface;

class Admin extends Singleton {

	public function settings_page() {
		// Show just for externally hosted.
		if ( ! Plugin::instance()->get_host()->is_type_external() ) {
			return;
		}

		$hook_suffix = add_options_page(
			'Dollie Connect',
			'Dollie Connect',
			'manage_options',
			'dollie-connect',
			[ $this, 'settings_page_content' ]
		);
		add_action( "load-{$hook_suffix}", [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'wpd-admin', PLATFORM_PLUGIN_URL . 'assets/dst/admin.css' );
		wp_enqueue_script( 'alpine', PLATFORM_PLUGIN_URL . 'assets/js/alpine.min.js', array(), null, true );
	}

	public function ajax_callback_remove_site() {
		// Check for nonce security.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'dollie_connect_ajax_nonce' ) ) {
			wp_send_json_error( 'Not allowed!' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed!' );
		}

		if ( ! get_option( 'wpd_connection_id' ) ) {
			wp_send_json_error( 'Site is not connected!' );
		}

		$signature = sha1( Plugin::instance()->get_host()->get_token() . Plugin::instance()->get_host()->get_partner_hash() );

		$api_host = defined( 'WPD_WORKER_API_URL' ) ? WPD_WORKER_API_URL : HostInterface::API_URL;

		$response = wp_remote_request(
			$api_host . 'external-sites/' . get_option( 'wpd_connection_id' ),
			array(
				'method'  => 'DELETE',
				'headers' => array(
					'Authorization' => $signature,
				),

				'timeout' => 30,
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$data = wp_remote_retrieve_body( $response );
			$data = @json_decode( $data );

			if ( isset( $data->message ) ) {
				Plugin::instance()->get_host()->remove_connection();
				wp_send_json_success( $data->message );
			}
			wp_send_json_error( json_encode( $response ) );
		}

		wp_send_json_error( 'Something went wrong!' );
		wp_die();
	}

	public function ajax_callback_connect_site() {
		// Check for nonce security.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'dollie_connect_ajax_nonce' ) ) {
			wp_send_json_error( 'Not allowed!' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed!' );
		}

		if ( ! Plugin::instance()->get_host()->get_partner_hash() ) {
			wp_send_json_error( 'Partner is not defined!' );
		}

		$registered = Plugin::instance()->get_host()->register_site();

		if ( $registered ) {
			wp_send_json_success(
				[
					'site'  => $registered,
					'data'  => 'Site registered successfully',
					'token' => Plugin::instance()->get_host()->get_token(),
				]
			);
		}

		wp_send_json_error( 'Something went wrong!' );
		wp_die();
	}

	/**
	 * Render the settings page content.
	 *
	 * @return void
	 */
	public function settings_page_content() {
		$wpd_token = get_option( 'wpd_token' );
		$site_hash = get_option( 'wpd_connection_id' );
		$image_url = Whitelabel::instance()->get_text( 'connect_img_url' ) === 'connect_img_url' ? esc_attr( PLATFORM_PLUGIN_URL . 'assets/img/control-hq.svg' ) : Whitelabel::instance()->get_text( 'connect_img_url' );
		?>
		<div x-data="{
				isLoading: false,
				isSuccess: false,
				isError: false,
				message: '',
				token: '<?php echo esc_attr( $wpd_token ); ?>',
				site: '<?php echo esc_attr( $site_hash ); ?>'
				}" class="max-w-xl mx-auto mt-6 px-4 sm:px-6 lg:px-8">
			<div class="bg-gray-700 shadow sm:rounded-lg">
				<div class="px-4 py-5 sm:px-6">
					<img src="<?php echo esc_attr( $image_url ); ?>"
						 alt="Control HQ"
						 class="h-12 w-auto">
					<h2 class="text-lg leading-6 font-medium text-white mt-2">
						<?php echo esc_html( Whitelabel::instance()->get_text( 'Dollie Connect' ) ); ?>
					</h2>
				</div>

				<div x-show="token && site" class="border-t border-gray-200 px-4 py-5 sm:p-0">
					<dl class="sm:divide-y sm:divide-gray-200">
						<div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
							<dt class="text-sm font-medium text-gray-100">
								<?php esc_html_e( 'Auth Token', 'platform' ); ?>
							</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								<label>
									<input type="text" x-model="token" disabled
										   class="bg-gray-100 rounded-md border-gray-200 py-2 px-3 w-full">
								</label>
							</dd>
						</div>
						<div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
							<dt class="text-sm font-medium text-gray-100">
								<?php esc_html_e( 'Remove Site from Control HQ', 'platform' ); ?>
							</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								<button
										class="bg-orange-600 hover:bg-orange-500 text-white font-bold py-2 px-4 rounded"
										x-bind="RemoveButton"
										x-html="isLoading ? `<span class='dashicons dashicons-update spin'></span> <?php esc_html_e( 'Removing Site', 'platform' ); ?>`: '<?php esc_html_e( 'Remove Site', 'platform' ); ?>'"
								>
									<?php esc_html_e( 'Remove Site', 'platform' ); ?>
								</button>

							</dd>
						</div>
					</dl>
				</div>
				<div x-show="!token || ! site" class="border-t border-gray-200 px-4 py-5 sm:p-0">
					<div class="bg-gray-600 text-white text-sm font-medium p-2 rounded sm:col-span-3">

						<p><?php esc_html_e( 'Connect your site easily and manage it with ease!', 'platform' ); ?></p>

						<button
								class="bg-orange-600 hover:bg-orange-500 text-white font-bold py-2 px-4 mt-4 rounded"
								x-bind="ConnectButton"
								x-html="isLoading ? `<span class='dashicons dashicons-update spin'></span> <?php esc_html_e( 'Connecting Site', 'platform' ); ?>`: '<?php esc_html_e( 'Connect Site', 'platform' ); ?>'"
						>
							<?php esc_html_e( 'Connect site', 'platform' ); ?>
						</button>
					</div>
				</div>
			</div>
			<div class="bg-gray-600 text-white text-sm font-medium mt-4 p-2 rounded sm:col-span-3"
				 x-show="message"
				 x-html="message"></div>
		</div>
		<script>
		  document.addEventListener('alpine:init', () => {
			Alpine.bind('RemoveButton', () => ({
			  type: 'button',

			  '@click'() {
				this.isLoading = true;
				fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				  method: 'POST',
				  headers: {
					'X-Requested-With': 'XMLHttpRequest'
				  },
				  body: new URLSearchParams({
					'action': 'dollie_connect_remove_site',
					'nonce': '<?php echo wp_create_nonce( 'dollie_connect_ajax_nonce' ); ?>'
				  })
				})
				  .then(response => response.json())
				  .then(json => {
					if (json.success) {
					  this.message = json.data;
					  this.token = '';
					  this.isSuccess = true;
					} else {
					  // Handle error response
					  this.message = json.data ?? 'An error occurred. Please try again!';
					}
					this.isLoading = false;
				  })
				  .catch(error => {
					// Handle fetch error
					this.isLoading = false;
					this.message = 'An error occurred. Please try again!';
				  });
			  },

			  ':disabled'() {
				return this.isLoading
			  },
			}));
			Alpine.bind('ConnectButton', () => ({
			  type: 'button',

			  '@click'() {
				this.isLoading = true;
				fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				  method: 'POST',
				  headers: {
					'X-Requested-With': 'XMLHttpRequest'
				  },
				  body: new URLSearchParams({
					'action': 'dollie_connect_site',
					'nonce': '<?php echo wp_create_nonce( 'dollie_connect_ajax_nonce' ); ?>'
				  })
				})
				  .then(response => response.json())
				  .then(json => {
					if (json.success) {
					  this.message = json.data.message;
					  this.token = json.data.token;
					  this.site = json.data.site;
					  this.isSuccess = true;
					} else {
					  // Handle error response
					  this.message = json.data ?? 'An error occurred. Please try again!';
					}
					this.isLoading = false;
				  })
				  .catch(error => {
					// Handle fetch error
					this.isLoading = false;
					this.message = 'An error occurred. Please try again!';
				  });
			  },

			  ':disabled'() {
				return this.isLoading
			  },
			}))
		  })
		</script>
		<?php
	}

	public function trigger_update_hook() {
		$payload = array(
			'hash' => Plugin::instance()->get_host()->get_token(),
		);

		$signature = hash_hmac( 'sha256', json_encode( $payload ), HostInterface::API_SIGNATURE );

		wp_remote_request(
			HostInterface::API_BASE_URL . 'trigger-update',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Signature'    => $signature,
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 60,
			)
		);
	}
}
