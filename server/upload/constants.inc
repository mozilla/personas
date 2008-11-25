<?php

#db access
putenv('PERSONAS_USERNAME=personas');
putenv('PERSONAS_PASSWORD=personas');
putenv('PERSONAS_HOST=localhost');
putenv('PERSONAS_DB=personas');

#path to the generated html/json/images tree
putenv('PERSONAS_STORAGE_PREFIX=/Users/telliott/mozilla/personas/server/store');

#html path to the root of the html/json/images tree
putenv('PERSONAS_URL_PREFIX=http://localhost/personas/store');

#salt for login cookie generation
putenv('PERSONAS_LOGIN_SALT=OurSalt');

?>