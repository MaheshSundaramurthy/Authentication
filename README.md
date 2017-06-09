# Authentication
Authentication Microservice

##Tests
Delete `composer.lock` and run `composer update` to install the required Behat extensions. 

Then run tests from the project directory:
```
./vendor/bin/behat
```

If you wish to use PhantomJS as a browser, make sure it's installed 
(see http://phantomjs.org/download.html), then run the tests as follows:
 ```
 ./vendor/bin/behat -p phantomjs
 ```
