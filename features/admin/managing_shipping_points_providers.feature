@managing_shipping_providers
Feature: Managing shipping points providers
    In order to allow users to choose a shipping point
    As an Administrator
    I want to be able to manage shipping point providers

  Background:
      Given the store operates on a channel named "Web-US" in "USD" currency
      And the store is available in "English (United States)"
      And the store has a zone "United States" with code "US"
      And I am logged in as an administrator

    @ui @javascript
    Scenario: Adding new shipping provider
        When I want to create a new shipping method
        And I specify its code as "gls"
        And I specify its position as 0
        And I name it "GLS" in "English (United States)"
        And I define it for the zone named "United States"
        And I select "GLS" as pickup point provider
        And I specify its amount as 5 for "Web-US" channel
        And I add it
        Then I should be notified that it has been successfully created
        And the shipping method "GLS" should appear in the registry
