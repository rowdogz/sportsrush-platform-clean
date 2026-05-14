/** @type {import('eslint').Linter.Config} */
module.exports = {
  parser: "@typescript-eslint/parser",
  parserOptions: {
    ecmaVersion: "latest",
    sourceType: "module",
  },
  plugins: ["@typescript-eslint", "import"],
  extends: [
    "eslint:recommended",
    "plugin:@typescript-eslint/recommended",
    "plugin:@typescript-eslint/recommended-requiring-type-checking",
  ],
  rules: {
    // --- No any ---
    // Prevents the type system from being silently bypassed.
    "@typescript-eslint/no-explicit-any": "error",
    "@typescript-eslint/no-unsafe-assignment": "error",
    "@typescript-eslint/no-unsafe-member-access": "error",
    "@typescript-eslint/no-unsafe-call": "error",
    "@typescript-eslint/no-unsafe-return": "error",

    // --- Unused code ---
    "@typescript-eslint/no-unused-vars": [
      "error",
      { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
    ],

    // --- Imports ---
    // Prevents circular dependencies between packages (a common monorepo problem).
    "import/no-cycle": "error",
    // Ensures all imports resolve to real files.
    "import/no-unresolved": "off", // handled by TypeScript

    // --- Consistency ---
    "@typescript-eslint/consistent-type-imports": [
      "error",
      { prefer: "type-imports" },
    ],
    "@typescript-eslint/no-import-type-side-effects": "error",

    // --- Console ---
    // Workers use console.log for structured logging; it is allowed.
    // Warn rather than error to allow deliberate use with justification.
    "no-console": "warn",

    // --- Safety ---
    "no-var": "error",
    "prefer-const": "error",
    eqeqeq: ["error", "always"],
  },
  overrides: [
    {
      // Test files may use console and looser assertions
      files: ["**/*.test.ts", "**/*.test.tsx", "**/*.int.test.ts"],
      rules: {
        "no-console": "off",
        "@typescript-eslint/no-unsafe-assignment": "off",
      },
    },
  ],
  ignorePatterns: [
    "node_modules/",
    "dist/",
    ".next/",
    ".wrangler/",
    "coverage/",
  ],
};
