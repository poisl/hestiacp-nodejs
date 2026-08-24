<?php

if (empty($_GET['domain']) || empty($user) || empty($user_plain)) {
    return;
}

$nodejsDeleteConfig = '/home/' . $user_plain . '/web/' . $_GET['domain'] . '/private/hestiacp_nodejs_config/.conf';
if (!file_exists($nodejsDeleteConfig)) {
    return;
}

exec(
    HESTIA_CMD .
        'v-delete-nodejs-app ' .
        $user .
        ' ' .
        \Hestiacp\quoteshellarg\quoteshellarg($_GET['domain']),
    $output,
    $return_var,
);

if ($return_var !== 0) {
    $error = implode('<br>', $output);
    $_SESSION['error_msg'] = !empty($error)
        ? $error
        : _('Node.js application cleanup failed.');
    header('Location: /edit/web/?' . http_build_query(['domain' => $_GET['domain']]));
    exit();
}

unset($output);
