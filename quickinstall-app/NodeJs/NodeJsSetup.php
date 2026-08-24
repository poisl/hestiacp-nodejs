<?php

declare(strict_types=1);

namespace Hestia\WebApp\Installers\NodeJs;

use Hestia\System\HestiaApp;
use Hestia\WebApp\BaseSetup;
use Hestia\WebApp\InstallationTarget\InstallationTarget;
use Hestia\WebApp\Installers\NodeJs\NodeJsUtils\NodeJsPaths;
use Hestia\WebApp\Installers\NodeJs\NodeJsUtils\NodeJsUtil;
use RuntimeException;
use Symfony\Component\Process\Process;

use function file_exists;
use function sprintf;
use function str_replace;
use function trim;

class NodeJsSetup extends BaseSetup
{
    protected const TEMPLATE_PROXY_VARS = ['%nginx_port%'];
    protected const TEMPLATE_ENTRYPOINT_VARS = ['%app_name%', '%app_start_script%', '%app_cwd%'];

    protected array $info = [
        'name' => 'NodeJs',
        'group' => 'node',
        'version' => '1.2.0',
        'thumbnail' => 'nodejs.png',
    ];

    protected array $config = [
        'form' => [
            'node_version' => [
                'type' => 'select',
                'value' => '22',
                'options' => ['20', '22', '24'],
            ],
            'start_script' => [
                'type' => 'text',
                'placeholder' => 'npm start',
            ],
            'port' => [
                'type' => 'text',
                'placeholder' => '3000',
            ],
        ],
        'database' => false,
        'resources' => [],
        'server' => [
            'nginx' => [
                'template' => 'NodeJS',
            ],
            'php' => [
                'supported' => ['8.2', '8.3', '8.4', '8.5'],
            ],
        ],
    ];

    private NodeJsPaths $nodeJsPaths;
    private NodeJsUtil $nodeJsUtils;

    public function __construct(HestiaApp $appcontext)
    {
        parent::__construct($appcontext);

        $this->nodeJsPaths = new NodeJsPaths();
        $this->nodeJsUtils = new NodeJsUtil();
    }

    protected function setupApplication(InstallationTarget $target, array $options): void
    {
        $port = $this->normalizePort($options['port'] ?? '');
        $startScript = $this->normalizeRequiredOption($options['start_script'] ?? '', 'start script');
        $nodeVersion = $this->normalizeRequiredOption($options['node_version'] ?? '', 'Node.js version');
        $domain = $target->domain->domainName;
        $domainPath = $target->domain->domainPath;
        $this->createAppDir($domainPath);
        $this->createConfDir($domainPath);
        $this->createAppEntryPoint($domain, $domainPath, $startScript);
        $this->createAppNvmVersion($domainPath, $nodeVersion);
        $this->createAppEnv($domainPath, $port);
        $this->createPublicHtmlConfigFile($target);
        $this->createAppProxyTemplates($domainPath, $port);
        $this->createAppConfig($domain, $domainPath, $port, $startScript, $nodeVersion);
        $this->applyWebTemplates($domain);
        $this->pm2StartApp($domain);
    }

    private function createAppEntryPoint(string $domain, string $domainPath, string $startScript): void
    {
        $pm2Name = $this->createPm2Name($domain);

        $contents = $this->nodeJsUtils->parseTemplate(
            $this->nodeJsPaths->getAppEntrypointTemplate(),
            self::TEMPLATE_ENTRYPOINT_VARS,
            [
                $pm2Name,
                $startScript,
                $this->nodeJsPaths->getAppDir($domainPath),
            ],
        );

        $this->appcontext->createFile(
            $this->nodeJsPaths->getAppEntryPoint($domainPath),
            $contents,
        );
    }

    private function createAppNvmVersion(string $domainPath, string $nodeVersion): void
    {
        $this->appcontext->createFile(
            $this->nodeJsPaths->getAppDir($domainPath, '.nvmrc'),
            $nodeVersion,
        );
    }

    private function createAppEnv(string $domainPath, string $port): void
    {
        $this->appcontext->createFile(
            $this->nodeJsPaths->getAppDir($domainPath, '.env'),
            'PORT="' . $port . '"',
        );
    }

    private function createAppProxyTemplates(string $domainPath, string $port): void
    {
        $replace = [$port];

        $this->appcontext->createFile(
            $this->nodeJsPaths->getAppProxyConfig($domainPath),
            $this->nodeJsUtils->parseTemplate(
                $this->nodeJsPaths->getNodeJsProxyTemplate(),
                self::TEMPLATE_PROXY_VARS,
                $replace,
            ),
        );

        $this->appcontext->createFile(
            $this->nodeJsPaths->getAppProxyFallbackConfig($domainPath),
            $this->nodeJsUtils->parseTemplate(
                $this->nodeJsPaths->getNodeJsProxyFallbackTemplate(),
                self::TEMPLATE_PROXY_VARS,
                $replace,
            ),
        );
    }

    private function createAppConfig(
        string $domain,
        string $domainPath,
        string $port,
        string $startScript,
        string $nodeVersion,
    ): void {
        $appDir = $this->nodeJsPaths->getAppDir($domainPath);
        $config = implode("\n", [
            $this->formatConfigValue('DOMAIN', $domain),
            $this->formatConfigValue('PORT', $port),
            $this->formatConfigValue('NODE_VERSION', $nodeVersion),
            $this->formatConfigValue('APP_NAME', $domain),
            $this->formatConfigValue('APP_DIR', $appDir),
            $this->formatConfigValue('START_SCRIPT', $startScript),
            $this->formatConfigValue('ENTRYPOINT', $this->nodeJsPaths->getAppEntryPointFileName()),
            $this->formatConfigValue('PM2_NAME', $this->createPm2Name($domain)),
            $this->formatConfigValue('PM2_HOME', $appDir . '/.pm2'),
        ]) . "\n";

        $this->appcontext->createFile(
            $this->nodeJsPaths->getConfigFile($domainPath),
            $config,
        );
    }

    private function createPublicHtmlConfigFile(InstallationTarget $target): void
    {
        // Leave a marker so the domain is no longer considered a clean install target.
        $this->appcontext->createFile($target->getDocRoot('app.conf'), "\n");
    }

    private function createAppDir(string $domainPath): void
    {
        $appDir = $this->nodeJsPaths->getAppDir($domainPath);
        if (!file_exists($appDir)) {
            $this->appcontext->addDirectory($appDir);
        }
    }

    private function createConfDir(string $domainPath): void
    {
        $directories = [
            $this->nodeJsPaths->getConfigDir($domainPath),
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                $this->appcontext->addDirectory($directory);
            }
        }
    }

    private function pm2StartApp(string $domain): void
    {
        if (!file_exists('/usr/local/hestia/bin/v-add-nodejs-app')) {
            throw new RuntimeException('Node.js PM2 integration command is not installed');
        }

        $this->runHestiaCommand(
            '/usr/local/hestia/bin/v-add-nodejs-app',
            [$domain],
            'Failed to start Node.js application with PM2: %s',
        );
    }

    private function applyWebTemplates(string $domain): void
    {
        if (($_SESSION['WEB_SYSTEM'] ?? '') === 'nginx') {
            return;
        }

        if (!file_exists('/usr/local/hestia/bin/v-change-web-domain-proxy-tpl')) {
            throw new RuntimeException('Node.js proxy template command is not installed');
        }

        $this->runHestiaCommand(
            '/usr/local/hestia/bin/v-change-web-domain-proxy-tpl',
            [$domain, 'NodeJS'],
            'Failed to apply Node.js proxy template: %s',
        );
    }

    private function runHestiaCommand(string $command, array $arguments, string $errorMessage): void
    {
        $process = new Process([
            '/usr/bin/sudo',
            $command,
            $this->appcontext->user(),
            ...$arguments,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf($errorMessage, trim($process->getErrorOutput())));
        }
    }

    private function normalizePort(string $port): string
    {
        $port = trim($port);

        if ($port === '' || !ctype_digit($port)) {
            throw new RuntimeException('Node.js port must be a numeric value');
        }

        $portNumber = (int) $port;
        if ($portNumber < 1024 || $portNumber > 65535) {
            throw new RuntimeException('Node.js port must be between 1024 and 65535');
        }

        return $port;
    }

    private function normalizeRequiredOption(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new RuntimeException(sprintf('Node.js %s is required', $label));
        }

        return str_replace(["\r", "\n"], '', $value);
    }

    private function createPm2Name(string $domain): string
    {
        $baseName = 'hestia-' . $this->appcontext->user() . '-' . $domain;

        return (string) preg_replace('/[^a-z0-9.-]+/i', '-', $baseName);
    }

    private function formatConfigValue(string $key, string $value): string
    {
        return sprintf("%s='%s'", $key, str_replace("'", "'\\''", $value));
    }
}
