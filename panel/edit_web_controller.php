<?php

if (!isset($v_domain, $user, $user_plain)) {
    return;
}

$nodejsPanelVisible = false;
$nodejsStatusData = null;
$nodejsStatusLabel = _('Not configured');
$nodejsStatusClass = 'inline-alert-info';
$nodejsStatusError = '';
$nodejsLogOutput = '';
$nodejsLogType = 'out';
$nodejsLogLines = 100;
$nodejsCanStart = false;
$nodejsCanStop = false;
$nodejsCanRestart = false;

$nodejsRedirectParams = ['domain' => $v_domain];
if ($_SESSION['userContext'] === 'admin' && !empty($_GET['user'])) {
    $nodejsRedirectParams['user'] = $user_plain;
}
$nodejsRedirectUrl = '/edit/web/?' . http_build_query($nodejsRedirectParams);

$nodejsConfigFile = '/home/' . $user_plain . '/web/' . $v_domain . '/private/hestiacp_nodejs_config/.conf';
$nodejsPanelVisible = file_exists($nodejsConfigFile);

$nodejsActions = [
    'start' => ['command' => 'v-start-nodejs-app', 'message' => _('Node.js application started.')],
    'stop' => ['command' => 'v-stop-nodejs-app', 'message' => _('Node.js application stopped.')],
    'restart' => ['command' => 'v-restart-nodejs-app', 'message' => _('Node.js application restarted.')],
    'logs' => ['command' => 'v-get-nodejs-app-log'],
];

if (!empty($_POST['nodejs_action'])) {
    verify_csrf($_POST);

    $nodejsAction = $_POST['nodejs_action'];
    if ($nodejsAction === 'logs') {
        $nodejsLogType = $_POST['nodejs_log_type'] ?? 'out';
        $nodejsLogLines = max(1, min(200, (int) ($_POST['nodejs_log_lines'] ?? 100)));

        exec(
            HESTIA_CMD .
                $nodejsActions[$nodejsAction]['command'] .
                ' ' .
                $user .
                ' ' .
                \Hestiacp\quoteshellarg\quoteshellarg($v_domain) .
                ' ' .
                \Hestiacp\quoteshellarg\quoteshellarg($nodejsLogType) .
                ' ' .
                (int) $nodejsLogLines .
                ' json',
            $output,
            $return_var,
        );

        if ($return_var === 0) {
            $nodejsLogData = json_decode(implode('', $output), true);
            $nodejsLogOutput = $nodejsLogData['output'] ?? '';
            unset($_SESSION['error_msg'], $_SESSION['ok_msg']);
        } else {
            $error = implode('<br>', $output);
            $_SESSION['error_msg'] = !empty($error)
                ? $error
                : _('Unable to load Node.js logs.');
            $nodejsLogOutput = '';
        }
        unset($output);
    } elseif (isset($nodejsActions[$nodejsAction])) {
        exec(
            HESTIA_CMD .
                $nodejsActions[$nodejsAction]['command'] .
                ' ' .
                $user .
                ' ' .
                \Hestiacp\quoteshellarg\quoteshellarg($v_domain),
            $output,
            $return_var,
        );

        if ($return_var === 0) {
            $_SESSION['ok_msg'] = $nodejsActions[$nodejsAction]['message'];
            unset($_SESSION['error_msg']);
        } else {
            $error = implode('<br>', $output);
            $_SESSION['error_msg'] = !empty($error)
                ? $error
                : _('Node.js application action failed.');
            unset($_SESSION['ok_msg']);
        }
        unset($output);
    } else {
        $_SESSION['error_msg'] = _('Unknown Node.js action requested.');
        unset($_SESSION['ok_msg']);
    }

    if ($nodejsAction !== 'logs') {
        header('Location: ' . $nodejsRedirectUrl);
        exit();
    }
}

if ($nodejsPanelVisible) {
    exec(
        HESTIA_CMD .
            'v-get-nodejs-app-status ' .
            $user .
            ' ' .
            \Hestiacp\quoteshellarg\quoteshellarg($v_domain) .
            ' json',
        $output,
        $return_var,
    );

    if ($return_var === 0) {
        $nodejsStatusData = json_decode(implode('', $output), true);
        $nodejsStatus = $nodejsStatusData['status'] ?? 'unknown';

        switch ($nodejsStatus) {
            case 'online':
                $nodejsStatusLabel = _('Running');
                $nodejsStatusClass = 'inline-alert-success';
                $nodejsCanStop = true;
                $nodejsCanRestart = true;
                break;
            case 'stopped':
            case 'missing':
                $nodejsStatusLabel = _('Stopped');
                $nodejsStatusClass = 'inline-alert-danger';
                $nodejsCanStart = true;
                break;
            case 'errored':
                $nodejsStatusLabel = _('Error');
                $nodejsStatusClass = 'inline-alert-danger';
                $nodejsCanStart = true;
                $nodejsCanRestart = true;
                break;
            default:
                $nodejsStatusLabel = ucfirst($nodejsStatus);
                $nodejsStatusClass = 'inline-alert-info';
                $nodejsCanStart = $nodejsStatus !== 'online';
                $nodejsCanStop = $nodejsStatus === 'launching' || $nodejsStatus === 'stopping';
                $nodejsCanRestart = $nodejsStatus !== 'missing';
                break;
        }
    } else {
        $nodejsStatusLabel = _('Error');
        $nodejsStatusClass = 'inline-alert-danger';
        $nodejsStatusError = implode('<br>', $output);
    }
    unset($output);
}
