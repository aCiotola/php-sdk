<?php
namespace PaymentRails;

use InvalidArgumentException;

/**
 * PaymentRails Payment processor
 * Creates and manages transactions
 *
 *
 * <b>== More information ==</b>
 *
 * For more detailed information on Payment, see {@link http://docs.paymentrails.com/#create-a-payment}
 *
 * @package    PaymentRails
 * @category   Resources
 */

class PaymentGateway
{
    private $_gateway;
    private $_config;
    private $_http;

    public function __construct($gateway)
    {
        $this->_gateway = $gateway;
        $this->_config = $gateway->config;
        $this->_config->assertHasAccessTokenOrKeys();
        $this->_http = new Http($gateway->config);
    }

    /**
     * Returns a ResourceCollection of transactions matching the search query.
     *
     * If <b>query</b> is a string, the search will be a basic search.
     * If <b>query</b> is a hash, the search will be an advanced search.
     * For more detailed information and examples, see {@link http://docs.paymentrails.com/#recipients}
     *
     * @param mixed $query search query
     * @param array $options options such as page number
     * @return ResourceCollection
     * @throws InvalidArgumentException
     */
    public function search($query)
    {
        $response = $this->_http->get('/v1/recipients', $query);

        if ($response['ok']) {
            $pager = [
                'object' => $this,
                'method' => 'search',
                'methodArgs' => $query
            ];

            $items = array_map(function ($item) {
                return Recipient::factory($item);
            }, $response['recipients']);

            return new ResourceCollection($response, $items, $pager);
        } else {
            throw new Exception\DownForMaintenance();
        }
    }

    /**
     * Fetch a recipient by ID
     */
    public function find($id) {
        $response = $this->_http->get('/v1/recipients/' . $id, null);

        if ($response['ok']) {
            return Recipient::factory($response['recipient']);
        } else {
            throw new Exception\DownForMaintenance();
        }
    }

    public function create($attrib) {
        $response = $this->_http->post('/v1/recipients', $attrib);
        if ($response['ok']) {
            return Recipient::factory($response['recipient']);
        } else {
            throw new Exception\DownForMaintenance();
        }
    }

    public function update($id, $attrib) {
        $response = $this->_http->patch('/v1/recipients/' . $id, $attrib);
        print_r($response);
        if ($response['ok']) {
            return Recipient::factory($response['recipient']);
        } else {
            throw new Exception\DownForMaintenance();
        }
    }

    public function delete($id) {
        $response = $this->_http->delete('/v1/recipients/' . $id);
        if ($response) {
            return true;
        } else {
            throw new Exception\DownForMaintenance();
        }
    }


    /**
     * generic method for validating incoming gateway responses
     *
     * creates a new Transaction object and encapsulates
     * it inside a Result\Successful object, or
     * encapsulates a Errors object inside a Result\Error
     * alternatively, throws an Unexpected exception if the response is invalid.
     *
     * @ignore
     * @param array $response gateway response values
     * @return Result\Successful|Result\Error
     * @throws Exception\Unexpected
     */
    private function _verifyGatewayResponse($response)
    {
        if (isset($response['transaction'])) {
            // return a populated instance of Transaction
            return new Result\Successful(
                    Transaction::factory($response['transaction'])
            );
        } else if (isset($response['apiErrorResponse'])) {
            return new Result\Error($response['apiErrorResponse']);
        } else {
            throw new Exception\Unexpected(
            "Expected transaction or apiErrorResponse"
            );
        }
    }
}

class_alias('PaymentRails\PaymentGateway', 'PaymentRails_PaymentGateway');
