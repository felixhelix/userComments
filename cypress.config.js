import { defineConfig } from 'cypress'

export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost/ojs',
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    specPattern: [
      'cypress/tests/**/*.cy.{js,jsx,ts,tsx}',
    ],    
  },
});
