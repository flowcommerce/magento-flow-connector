<?php

namespace FlowCommerce\FlowConnector\Console\Command;

use Symfony\Component\Console\Logger\ConsoleLogger;

class FlowConsoleLogger extends ConsoleLogger
{

    const DATE_FORMAT = 'l jS F, Y - H:i:s';

    public function log($level, $message, array $context = [])
    {
        list($usec, $sec) = explode(' ', microtime());
        $msg = date('Y/m/d H:i:s', $sec) . substr($usec, 1) . ': ' . $message;
        parent::log($level, $msg, $context);
    }
}
