<?php

/**
 * MIT License
 *
 * Copyright (c) 2019 Samuel CHEMLA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PhpBg\WatchTv\Server;

use PhpBg\WatchTv\Api\CheckConfiguration;
use PhpBg\WatchTv\Api\InitialScanFiles;
use PhpBg\WatchTv\Pages\Channels\Channels;
use PhpBg\WatchTv\Pages\Configure\Configure;
use PhpBg\MiniHttpd\Model\Route;
use PhpBg\MiniHttpd\Renderer\Json;
use PhpBg\MiniHttpd\Renderer\Phtml\Phtml;
use PhpBg\MiniHttpd\ServerFactory;
use React\Socket\Server;

class HTTPServer
{
    public function __construct(Context $dvbContext)
    {
        $defaultRenderer = new Phtml($dvbContext->rootPath . '/src/Pages/layout.phtml');
        $routes = [
            '/' => new Route(new Channels($dvbContext->rtspPort, $dvbContext->channels), $defaultRenderer),
            '/configure' => new Route(new Configure($dvbContext->channels), $defaultRenderer),
            '/api/check-configuration' => new Route(new CheckConfiguration($dvbContext->loop), new Json()),
            '/api/initial-scan-files' => new Route(new InitialScanFiles($dvbContext->channels), new Json()),
            '/api/channels/reload' => new Route([new \PhpBg\WatchTv\Api\Channels($dvbContext->channels), 'reload'], new Json())
        ];
        $dvbContext->routes = $routes;
        $dvbContext->publicPath = $dvbContext->rootPath . '/public';
        $dvbContext->defaultRenderer = $defaultRenderer;
        $httpServer = ServerFactory::create($dvbContext);
        $socket = new Server("tcp://0.0.0.0:{$dvbContext->httpPort}", $dvbContext->loop);
        $httpServer->listen($socket);
        $dvbContext->logger->notice("HTTP server started");
    }
}