Feature: second test feature
  this is the second test feature

  Scenario: first scenario in second feature
    Given The user "test" exists
    And i logged in as "test"
    When i click button one
    Then i should see the text "blubb"

  Scenario: second scenario in second feature
    Given The user "test2" exists
    And i logged in as "test2"
    When i click button one
    Then i should see the text "bla"