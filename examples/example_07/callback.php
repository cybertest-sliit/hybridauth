<?php
/**
 * A simple example that shows how to use multiple providers, opening provider authentication in a pop-up.
 */

require 'path/to/vendor/autoload.php';
require 'config.php';

use Hybridauth\Exception\Exception;
use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;
use Hybridauth\Storage\Session;

try {

    $hybridauth = new Hybridauth($config);
    $storage = new Session();
    $error = false;

    //
    // Event 1: User clicked SIGN-IN link
    //
    if(
	isset( $_GET['provider'])
	&& wp_verify_nonce(sanitize_key($_GET['provider']), 'provider_action')
    ) {
        // Validate provider exists in the $config
        if (in_array($_GET['provider'], $hybridauth->getProviders())) {
            // Store the provider for the callback event
            $storage->set('provider', sanitize_key($_GET['provider']));
        } else {
            $error = sanitize_key($_GET['provider']);
        }
    }

    //
    // Event 2: User clicked LOGOUT link
    //
    if (isset($_GET['logout'])) {
        if (in_array($_GET['logout'], $hybridauth->getProviders())) {
            // Disconnect the adapter
            $adapter = $hybridauth->getAdapter(sanitize_key($_GET['logout']));
            $adapter->disconnect();
        } else {
            $error = sanitize_key($_GET['logout']);
        }
    }

    //
    // Handle invalid provider errors
    //
    if ($error) {
        error_log('HybridAuth Error: Provider '. json_encode($error) .' not found or not enabled in $config');
        // Close the pop-up window
        echo "
            <script>
                window.opener.location.reload();
                window.close();
            </script>";
        exit;
    }

    //
    // Event 3: Provider returns via CALLBACK
    //
    if ($provider = $storage->get('provider')) {

        $hybridauth->authenticate($provider);
        $storage->set('provider', null);

        // Retrieve the provider record
        $adapter = $hybridauth->getAdapter($provider);
        $userProfile = $adapter->getUserProfile();
        $accessToken = $adapter->getAccessToken();

        // add your custom AUTH functions (if any) here
        // ...
        $data = [
            'token'         => $accessToken,
            'identifier'    => $userProfile->identifier,
            'email'         => $userProfile->email,
            'first_name'    => $userProfile->firstName,
            'last_name'     => $userProfile->lastName,
            'photoURL'      => strtok($userProfile->photoURL,'?'),
            ];
        // ...

        // Close pop-up window
        echo "
            <script>
                window.opener.location.reload();
                window.close();
            </script>";

    }

} catch (Exception $e) {
    error_log( $e->getMessage());
    echo $e->getMessage();
}
