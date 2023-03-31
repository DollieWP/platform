<?php

namespace WPD_Platform;


use WPD_Platform\Utils\HostInterface;

class Admin extends Singleton {

	public function settings_page() {

		// show just for externally hosted.
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
		wp_enqueue_script( 'alpinejs', PLATFORM_PLUGIN_URL . 'assets/js/alpine.min.js', array(), null, true );

	}

	public function ajax_callback_remove_site() {

		// Check for nonce security
		if ( ! wp_verify_nonce( $_POST['nonce'], 'dollie_connect_ajax_nonce' ) ) {
			wp_send_json_error( 'Not allowed!' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed!' );
		}

        $signature = sha1(Plugin::instance()->get_host()->get_token() . Plugin::instance()->get_host()->get_partner_hash());

		$response = wp_remote_request( HostInterface::API_URL . 'external-sites/' . get_option('wpd_connection_id'), array(
			'method'  => 'DELETE',
			'headers' => array(
				'Authorization' => $signature
			),

			'timeout' => 30,
		) );

		if ( ! is_wp_error( $response ) ) {
			Plugin::instance()->get_host()->remove_connection();
			wp_send_json_success( 'Successfully removed site!' );
		} else {
			wp_send_json_error( $response->get_error_message() );
		}
		wp_die();
	}

	/**
	 * Render the settings page content.
	 *
	 * @return void
	 */
	public function settings_page_content() {
		$wpd_token = get_option( 'wpd_token' );
		?>
        <div x-data="{
                isLoading: false,
                isSuccess: false,
                isError: false,
                message: '',
                token: '<?php echo esc_attr( $wpd_token ); ?>',
                }" class="max-w-xl mx-auto mt-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-gray-700 shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <img src="<?php echo esc_attr( PLATFORM_PLUGIN_URL . 'assets/img/control-hq.svg' ); ?>"
                         alt="Control HQ"
                         class="h-12 w-auto">
                    <h2 class="text-lg leading-6 font-medium text-white mt-2">Dollie Connect</h2>
                </div>
                <div x-show="token" class="border-t border-gray-200 px-4 py-5 sm:p-0">
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
                                <button x-on:click="
                                    isLoading = true;
                                    fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                                        method: 'POST',
                                        body: new FormData(),
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        body: new URLSearchParams({
                                            'action': 'dollie_connect_remove_site',
                                            'nonce': '<?php echo wp_create_nonce( 'dollie_connect_ajax_nonce' ); ?>'
                                        })
                                    })
                                    .then(response =>response.json())
                                    .then(json=> {
                                    console.log(json);
                                        if (json.success) {
                                            message = json.data;
                                            token = '';
                                            isSuccess = true;
                                        } else {
                                            // Handle error response
                                            console.error('Failed to remove site');
                                            message = json.data ?? 'An error occurred. Please try again!';
                                        }
                                        isLoading = false;
                                    })
                                    .catch(error => {
                                        // Handle fetch error
                                        console.error(error);
                                        isLoading = false;
                                        message = 'An error occurred. Please try again!';
                                    });"
                                        class="bg-orange-600 hover:bg-orange-500 text-white font-bold py-2 px-4 rounded"
                                        x-bind:disabled="isLoading"
                                        x-html="isLoading ? `<span class='dashicons dashicons-update spin'></span> Removing Site`: 'Remove Site'"
                                >
                                    Remove Site
                                </button>

                            </dd>
                            <div class="bg-gray-600 text-white text-sm font-medium mt-4 p-2 rounded sm:col-span-3"
                                 x-show="message"
                                 x-html="message"></div>
                        </div>
                    </dl>
                </div>
                <div x-show="!token" class="border-t border-gray-200 px-4 py-5 sm:p-0">
                    <div class="bg-gray-600 text-white text-sm font-medium p-2 rounded sm:col-span-3">
                        This site is not yet connected!
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

}
