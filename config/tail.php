<?php

return [

    'connections' => [

        /*
         * The environment name. You can use this value in the tail command.
         */
        'production' => [

            /*
             * The hostname of the server where the logs are located
             */
            'host' => 'st-joris-turnhout.be',

            /*
             * The username to be used when connecting to the server where the logs are located
             */
            'user' => 'sn1145',

            /*
             * The port to be used when connecting to the server where the logs are located
             */
            'port' => '22',

            /*
             * The full path to the directory where the logs are located
             */
            'logDirectory' => 'storage/logs',
        ],
    ],
];
