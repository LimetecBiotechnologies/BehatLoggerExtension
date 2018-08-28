Feature: first test feature
  this is the first test feature

  Scenario: first scenario in first feature
    Given The user "test" exists
    And i logged in as "test"
    When i click button one
    Then i should see the text "blubb"

  Scenario: first scenario in first feature
    Given The user "test2" exists
    And i logged in as "test2"
    When i click button one
    Then i should see the text "bla"