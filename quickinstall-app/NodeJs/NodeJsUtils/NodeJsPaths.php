<?php

declare(strict_types=1);

namespace Hestia\WebApp\Installers\NodeJs\NodeJsUtils;

use Hestia\System\Util;

class NodeJsPaths
{
    private const APP_DIR = 'private/nodeapp';
    private const CONFIG_DIR = 'private/hestiacp_nodejs_config';
    private const APP_CONFIG_FILE_NAME = '.conf';
    private const APP_ENTRYPOINT_NAME = 'ecosystem.config.js';
    private const APP_ENTRYPOINT_TEMPLATE = __DIR__ . '/../templates/web/entrypoint.tpl';
    private const APP_PROXY_CONFIG_FILE_NAME = 'nodejs-app.conf';
    private const APP_PROXY_FALLBACK_CONFIG_FILE_NAME = 'nodejs-app-fallback.conf';
    private const NODEJS_PROXY_CONFIG_TEMPLATE = __DIR__ . '/../templates/nginx/nodejs-app.tpl';
    private const NODEJS_PROXY_FALLBACK_CONFIG_TEMPLATE = __DIR__ . '/../templates/nginx/nodejs-app-fallback.tpl';

    public function getAppDir(string $domainPath, string $relativePath = ''): string
    {
        return Util::joinPaths($domainPath, self::APP_DIR, $relativePath);
    }

    public function getConfigDir(string $domainPath, string $relativePath = ''): string
    {
        return Util::joinPaths($domainPath, self::CONFIG_DIR, $relativePath);
    }

    public function getDomainConfigDir(string $domainPath, string $relativePath = ''): string
    {
        return Util::joinPaths($this->getConfigDir($domainPath), $relativePath);
    }

    public function getConfigFile(string $domainPath): string
    {
        return $this->getDomainConfigDir($domainPath, self::APP_CONFIG_FILE_NAME);
    }

    public function getAppEntryPoint(string $domainPath): string
    {
        return $this->getAppDir($domainPath, self::APP_ENTRYPOINT_NAME);
    }

    public function getAppEntryPointFileName(): string
    {
        return self::APP_ENTRYPOINT_NAME;
    }

    public function getAppProxyConfig(string $domainPath): string
    {
        return $this->getDomainConfigDir($domainPath, self::APP_PROXY_CONFIG_FILE_NAME);
    }

    public function getAppProxyFallbackConfig(string $domainPath): string
    {
        return $this->getDomainConfigDir($domainPath, self::APP_PROXY_FALLBACK_CONFIG_FILE_NAME);
    }

    public function getNodeJsProxyTemplate(): string
    {
        return self::NODEJS_PROXY_CONFIG_TEMPLATE;
    }

    public function getNodeJsProxyFallbackTemplate(): string
    {
        return self::NODEJS_PROXY_FALLBACK_CONFIG_TEMPLATE;
    }

    public function getAppEntrypointTemplate(): string
    {
        return self::APP_ENTRYPOINT_TEMPLATE;
    }
}
