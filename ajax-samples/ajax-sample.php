<?php
/**
 * ajax-sample.php description: uses the WP REST API to process an AJAX request. On the front-end of the site
 * is a tutorial in a pop-up div. There are next/prev buttons going through the tutorial. There is also a
 * "Skip Tutorial" button that a user can click to bypass the tutorial. That button click initiates a call to
 * this script.
 * @var [type]
 */

// If the main plugin class doesn't exists then kill all processes.
// This is primarily for security but also because database tables won't be present and queries would fail.
if ( ! class_exists( 'DATAPORT' ) ) {
	die();
}


class NewUserMsgProcessor extends WP_REST_Controller {

  public function __construct()
  {
    add_action('rest_api_init', array($this, 'register_new_user_msg_routes'));
  }

  public function register_new_user_msg_routes() {
    register_rest_route( 'dataport/v1', '/new_user_msg', array(
      'methods'  => WP_REST_Server::ALLMETHODS, // READABLE = 'GET', CREATABLE = 'POST', EDITABLE = 'POST, PUT, PATCH', DELETABLE = 'DELETE', ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE'
      'callback' => array( $this, 'DP_dismiss_new_user_tutorial' ),
      'args'  => [
        'staffID' => [
          'type' => 'string',
          'sanitize_callback' => 'sanitize_text_field',
          'validate_callback' => 'rest_validate_request_arg'
        ],
        'rest_nonce' => [
          'type' => 'string',
          'sanitize_callback' => 'sanitize_text_field',
          'validate_callback' => 'rest_validate_request_arg'
        ],
        'nonce' => [
          'type' => 'string',
          'sanitize_callback' => 'sanitize_text_field',
          'validate_callback' => 'rest_validate_request_arg'
        ],
        'action' => [
          'type' => 'string',
          'sanitize_callback' => 'sanitize_text_field',
          'validate_callback' => 'rest_validate_request_arg'
        ]
      ]
    ) );
  }



  /**
   * DP_dismiss_new_user_tutorial description: does what it says, dismisses the tutorial
   * @param  [object] $request - WP_REST_Request Object
   * @return [object]          - data to return to originating JS script
   */
  public function DP_dismiss_new_user_tutorial( $request )
  {
    $parameters = $request->get_params();
    $rest_nonce = sanitize_text_field($parameters['rest_nonce']);
    $data = []; // return this data to the originating script
		// I'm pretty sure that the nonce is already parsed by this point and that the data being passed is sanitized, but I like to be extra sure...
		if( false !== wp_verify_nonce( $rest_nonce, 'wp_rest' ) ) {
      $data['staffID'] = $staff_ID = isset($parameters['staffID']) ? sanitize_text_field($parameters['staffID']) : 0;
			if ( $staff_ID == 0 ) {
        return new WP_REST_Response( ["status" => "error", "message" => "Staff ID not found."], 401 );
      } else {
				// In this application staff are custom user accounts separate from wp_user accounts, and stored in a custom table in the database
				// The staff settings data is JSON encoded and stored in a column in the custom staff table. Accessing the table and making staff
				// account changes is all handled by the Staff class.
				$settings = Staff::get_settings($staff_ID);
				$settings['new_user'] = 'false';
				$result = Staff::update_staff_field( $staff_ID, 'settings', json_encode($settings) );
				if ( is_null($result) ) {
					// Failure Response
					$data["status"] = "failure";
					$data["msg"] = "Failure: update query failed.";
					wlog("Error 987: Dismiss tutorial failure"); // custom logging that an expected action failed. Helps with debugging if users report an issue.
					return new WP_REST_Response( $data, 200 );
				} elseif ( 0 == $result ) {
					// No changes were made
					unset($_SESSION['user_msg']); // holds the current step in the tutorial. Without this $_SESSION key=>value set no tutorial will show.
					$data["status"] = "success";
					$data["msg"] = "No settings were updated.";
					return new WP_REST_Response( $data, 200 );
				} else {
					// Successful response
					unset($_SESSION['user_msg']); // holds the current step in the tutorial. Without this $_SESSION key=>value set no tutorial will show.
					$data["status"] = "success";
					$data["msg"] = "New user tutorial finished.";
					return new WP_REST_Response( $data, 200 );
				}
      }
    } else {
      return new WP_REST_Response( ["status" => "error", "message" => "Security check failed."], 401 );
    }
    // Ultimate fallback, shouldn't even be possible to occur. But my JAVA instructor always said we should be thorough...
    // Error Response
		wlog("nonce failed 888");
    $error = new WP_Error();
    $error->add( 'nonce_test_failure', 'Environment unstable', [ 'status' => 404 ] );
    return $error;
  }
}




$NewUserMsgProcessor = new NewUserMsgProcessor();
