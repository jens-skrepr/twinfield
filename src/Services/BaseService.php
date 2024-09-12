<?php

namespace PhpTwinfield\Services;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Twinfield Soap Client.
 *
 * If you add new WSDL files (services), implement the SOAP functions as convenience methods on these.
 *
 * @author Leon Rowland <leon@rowland.nl>
 */
abstract class BaseService extends \SoapClient implements LoggerAwareInterface
{
    /**
     * Get the WSDL. Can be with or without host.
     *
     * @return string
     */
    abstract protected function WSDL(): string;

    /**
     * @param string|null $wsdl    Note you should always pass null as the first argument, the WSDL will be overridden.
     * @param array       $options
     */
    public function __construct(string $wsdl = null, array $options = [])
    {
        /*
         * Relies heavily on __getLastResponse() etc.
         */
        $options["trace"]       = true;
        $options["compression"] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
        $options["cache_wsdl"]  = WSDL_CACHE_MEMORY; // https://github.com/php-twinfield/twinfield/issues/50
        $options["keep_alive"]  = false;

        if (array_key_exists("cluster", $options)) {
            $wsdl = "{$options["cluster"]}{$this->WSDL()}";
            unset($options["cluster"]);
        } else {
            $wsdl = $this->WSDL();
        }

        parent::__construct($wsdl, $options);
    }

    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false)
    {
        $response = parent::__doRequest($request, $location, $action, $version, $oneWay);

        $this->logRequest($request);

        return $response;
    }

    private function logRequest(string $request)
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->debug(
            "Sending request to Twinfield.",
            [
                'request_data' => $request
            ]
        );
    }
}
