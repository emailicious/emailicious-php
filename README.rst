emailicious-php
===============

.. image:: https://travis-ci.org/emailicious/emailicious-php.svg?branch=master
    :target: https://travis-ci.org/emailicious/emailicious-php
    :alt: Build Status

.. image:: https://coveralls.io/repos/emailicious/emailicious-php/badge.svg?branch=master
    :target: https://coveralls.io/r/emailicious/emailicious-php?branch=master
    :alt: Coverage Status

PHP client for the Emailicious API

Installation
------------

Add ``"emailicious/emailicious"`` to your ``composer.json`` file.


.. code:: json

    {
      "require": {
        "emailicious/emailicious": "~1.0"
      }
    }

Examples
--------

Adding a subscriber to a list
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code:: php

    <?php

    use Emailicious\Client;
    use Emailicious\Subscribers\Subscriber;
    use Emailicious\Subscribers\Exceptions\SubscriberConflict;
    use Guzzle\Http\Exception\BadResponseException;

    $client = Client($account, $user, $password);
    $data = array(
        'email' => 'email@example.com',
        'first_name' => 'Foo',
        'last_name' => 'Bar'
    );

    try {
        Subscriber::create($client, $listId, $data);
    } catch (SubscriberConflict $conflict) {
        // Email is already registered, the conflicting subscriber can be retrieved.
        $conflictualSubscriber = $conflict->getConflictualSubscriber();
    } catch (BadResponseException $exception) {
        $response = $exception->getResponse();
        if ($response->getStatusCode() == 400) {
            // Validation error, refer to the response body for more details.
            $details = $response->json();
        }
        // Refer to the response status code and response body for more details.
    }
