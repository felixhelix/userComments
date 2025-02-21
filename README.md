# User Comments

This plugin adds a comment thread to the article/preprints details page. For it to work, it needs a user's API key. You can either use an existing user, or add one just for this purpose. It is recommended to use an account with only "Reader" permission.

Comments can be flagged if the content is inappropriate. Editors assigned to the submission are informed about this by email. Editors have the option to either hide the flagged comment, or remove the flag.

Each action is logged in the database for transparency.

## Compatibility

The latest release of this plugin is compatible with the following PKP applications:

* OJS, OPS 3.4.0

## Installation

1. Enter the administration area of ​​your application and navigate to Settings > Website > Plugins > Upload a new plugin.
2. Under Upload file select the file customQuestions.tar.gz.
3. Click Save and the plugin will be installed on your website.
4. Activate the plugin.
5. Add an api key: On the plugin's settings page enter the api of a user with only "Reader" permissions.

## Running Tests

### Unit Tests

To execute PHP Unit Tests individually for this plugin, you can run the following shell command from the plugin directory: 
```
sh phpUnitTests.sh
```

### Integration Tests

To execute Cypress integration tests, run the following command from root of the PKP Appplication directory:
```bash
npx cypress open --config '{"specPattern":["plugins/generic/userComments/cypress/tests/functional/**/*.cy.js"]}'
```

# License
__This plugin is licensed under the GNU General Public License v3.0__

__Copyright (c) 2025 UCL Cologne__



