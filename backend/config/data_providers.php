<?php

return [
    'providers' => [
        'address' => [
            'streetAddress',
            'city',
            'state',
            'postcode',
            'country',
        ],
        'person' => [
            'name',
            'firstName',
            'lastName',
            'title',
        ],
        'company' => [
            'company',
            'catchPhrase',
            'bs',
        ],
        'internet' => [
            'email',
            'userName',
            'password',
            'domainName',
            'url',
        ],
        'datetime' => [
            'date',
            'time',
            'dateTime',
            'dateTimeAD',
            'iso8601',
        ],
        'text' => [
            'word',
            'sentence',
            'paragraph',
        ],
    ],
];