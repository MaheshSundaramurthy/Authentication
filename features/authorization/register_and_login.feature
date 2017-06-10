Feature: Registration and Login
  As an anonymous API user I should be able to access the registration API and sign-up.
  After that, as a registered user, I should be able to login and obtain a JWT token.

  Scenario: An anonymous user accesses the registration API
    Given the following form parameters are set:
      | name | value |
      | username | user@ds |
      | password | user |
    When I request "app_dev.php/registration" using HTTP "POST"
    Then the response is "success"
    Then the response body contains:
    """
      {
        "uuid": "<re>/^[a-f0-9]{8}-?[a-f0-9]{4}-?[1-5][a-f0-9]{3}-?[89ab][a-f0-9]{3}-?[a-f0-9]{12}$/</re>"
      }
    """


  Scenario: An anonymous user accesses the registration API with existing credentials
    Given the following form parameters are set:
      | name | value |
      | username | user@ds |
      | password | user |
    When I request "app_dev.php/registration" using HTTP "POST"
    Then the response is "client error"


  Scenario: A registered user can login and obtain a JWT token that contains her username
    Given the following form parameters are set:
      | name | value |
      | username | user@ds |
      | password | user |
    When I request "app_dev.php/login_check" using HTTP "POST"
    Then the response is success
    And the response body contains JWT token named "token" with "username" property as "user@ds"


  Scenario: A user attempts to sign in using invalid credentials
    Given the following form parameters are set:
      | name | value |
      | username | bogus_user@some_fake_domain |
      | password | any_password |
    When I request "app_dev.php/login_check" using HTTP "POST"
    Then the response is "client error"
